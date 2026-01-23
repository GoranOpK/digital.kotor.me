/**
 * MEGA Browser Upload Handler
 * 
 * Uploaduje fajlove direktno na MEGA iz browser-a koristeći megajs
 * Backend samo čuva metadata (nodeId, link, size, itd.)
 */

// Dinamički učitaj browser build od megajs-a preko CDN-a
// Browser build dolazi sa Buffer polyfill-om
let Storage;
let megajsLoaded = false;

async function loadMegaJS() {
    if (megajsLoaded && Storage) {
        return Storage;
    }
    
    try {
        // Učitaj browser ES module build koji dolazi sa Buffer polyfill-om
        const megajsModule = await import('https://unpkg.com/megajs@1.3.0/dist/main.browser-es.mjs');
        Storage = megajsModule.Storage;
        megajsLoaded = true;
        console.log('MEGA.js loaded successfully from CDN');
        return Storage;
    } catch (error) {
        console.error('Failed to load MEGA.js from CDN:', error);
        throw new Error('MEGA.js library failed to load');
    }
}

let megaStorage = null;

/**
 * Inicijalizuje MEGA Storage sa kredencijalima
 * Kredencijali se dobijaju od backend-a (ne direktno u frontend-u)
 */
async function initMegaStorage() {
    if (megaStorage) {
        return megaStorage;
    }

    try {
        // Učitaj megajs ako nije već učitan
        await loadMegaJS();
        
        if (!Storage) {
            throw new Error('MEGA.js Storage not available');
        }
        // Dobij MEGA kredencijale od backend-a (session token ili credentials)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        console.log('Fetching MEGA session, CSRF token:', csrfToken ? 'present' : 'missing');
        
        const response = await fetch('/api/mega/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        console.log('MEGA session response status:', response.status, response.statusText);
        
        if (!response.ok) {
            const contentType = response.headers.get('content-type');
            let errorText = await response.text();
            console.error('MEGA session error response:', errorText);
            throw new Error(`Failed to get MEGA session: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('MEGA session non-JSON response:', text);
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        console.log('MEGA session data received:', data.email ? 'email present' : 'no email', data.password ? 'password present' : 'no password');
        
        // Inicijalizuj MEGA Storage
        if (data.session_token) {
            // Koristi session token ako postoji
            megaStorage = await new Storage({
                session: data.session_token
            }).ready;
        } else if (data.email && data.password) {
            // Koristi email/password za login
            megaStorage = await new Storage({
                email: data.email,
                password: data.password
            }).ready;
        } else {
            throw new Error('No MEGA credentials provided');
        }

        console.log('MEGA Storage initialized');
        return megaStorage;
        
    } catch (error) {
        console.error('Failed to initialize MEGA Storage:', error);
        throw error;
    }
}

/**
 * Uploaduje fajl direktno na MEGA
 */
async function uploadFileToMega(file, folderPath = 'digital.kotor/documents', userId = null) {
    try {
        // Inicijalizuj MEGA Storage ako nije već inicijalizovan
        const storage = await initMegaStorage();

        // Konstruiši folder putanju sa user ID-om ako je dostupan
        let fullFolderPath = folderPath;
        if (userId) {
            fullFolderPath = `${folderPath}/user_${userId}`;
        }
        
        // Pronađi ili kreiraj folder strukturu
        let targetFolder = await findOrCreateFolder(storage, fullFolderPath);
        
        if (!targetFolder) {
            // Ako nema folder strukture, uploaduj u root
            targetFolder = storage.root;
        }

        // Upload fajla
        // Browser build od megajs-a očekuje Buffer
        // CDN build dolazi sa Buffer polyfill-om koji se postavlja na globalThis
        console.log('Uploading file to MEGA:', file.name, file.size, 'bytes');
        console.log('File type:', file.constructor.name, 'is File?', file instanceof File);
        console.log('File is Blob?', file instanceof Blob);
        console.log('Buffer available?', typeof Buffer !== 'undefined', typeof globalThis.Buffer !== 'undefined');
        
        // Browser build od megajs-a zahteva Buffer
        // Konvertuj File u Buffer koristeći Buffer koji dolazi sa CDN build-om
        const fileData = await file.arrayBuffer();
        
        // Koristi Buffer ako je dostupan (bilo globalni ili globalThis)
        const BufferClass = typeof Buffer !== 'undefined' ? Buffer : (typeof globalThis.Buffer !== 'undefined' ? globalThis.Buffer : null);
        
        if (!BufferClass) {
            throw new Error('Buffer is not available. MEGA.js browser build requires Buffer polyfill.');
        }
        
        const buffer = BufferClass.from(fileData);
        console.log('Using Buffer for upload, length:', buffer.length);
        
        const uploadedFile = await targetFolder.upload({
            name: file.name,
            size: file.size
        }, buffer).complete;
        console.log('Upload completed, uploadedFile:', uploadedFile);

        // Kreiraj public share link
        // link() kreira share link, vraća objekat sa url property
        // megajs vraća File objekat sa različitim property-jima
        // Proveri različite moguće property-je za node ID
        console.log('Creating share link for uploaded file...');
        const nodeId = uploadedFile.nodeId || uploadedFile.handle || uploadedFile.id || uploadedFile.downloadId;
        console.log('Node ID:', nodeId);
        
        const share = await uploadedFile.link();
        console.log('Share object:', share);
        
        // share može biti string (URL) ili objekat sa url property
        const megaLink = typeof share === 'string' ? share : (share.url || share || `https://mega.nz/file/${nodeId}`);
        console.log('MEGA link:', megaLink);
        
        console.log('File uploaded successfully:', {
            nodeId: nodeId,
            name: uploadedFile.name,
            size: uploadedFile.size,
            link: megaLink,
            fileObject: uploadedFile // Debug - videćemo strukturu objekta
        });

        return {
            success: true,
            nodeId: nodeId,
            megaLink: megaLink,
            name: uploadedFile.name,
            size: uploadedFile.size,
            timestamp: uploadedFile.timestamp || Date.now()
        };

    } catch (error) {
        console.error('MEGA upload error:', error);
        return {
            success: false,
            error: error.message
        };
    }
}

/**
 * Pronalazi ili kreira folder strukturu
 */
async function findOrCreateFolder(storage, folderPath) {
    const folders = folderPath.split('/').filter(f => f);
    let currentFolder = storage.root;

    for (const folderName of folders) {
        // Pronađi folder u trenutnom parent-u
        // Prvo proveri children trenutnog foldera
        const children = await currentFolder.children;
        let found = children.find(child => 
            child.directory && child.name === folderName
        );

        if (!found) {
            // Kreiraj novi folder u trenutnom parent-u
            // folder.mkdir() kreira folder u tom folderu
            found = await currentFolder.mkdir(folderName);
        }

        currentFolder = found;
    }

    return currentFolder;
}

/**
 * Uploaduje fajlove i šalje metadata na backend
 */
async function uploadFilesToMegaAndSave(files, documentName, category, expiresAt = null) {
    console.log('=== uploadFilesToMegaAndSave START ===');
    console.log('Parameters:', { documentName, category, expiresAt });
    console.log('Files parameter:', files);
    console.log('Files type:', typeof files);
    console.log('Files is Array?', Array.isArray(files));
    console.log('Files length:', files?.length);
    console.log('Files instanceof FileList?', files instanceof FileList);
    
    // Konvertuj FileList u Array ako nije već array
    const filesArray = Array.isArray(files) ? files : Array.from(files || []);
    console.log('Files array:', filesArray);
    console.log('Files array length:', filesArray.length);
    
    const results = [];

    try {
        // Inicijalizuj MEGA Storage
        console.log('Initializing MEGA Storage...');
        await initMegaStorage();
        console.log('MEGA Storage initialized successfully');

        // Dobij user ID iz meta tag-a ili drugog izvora (ako je dostupan)
        // Za sada koristimo folderPath bez user ID-a, ali možemo dodati kasnije
        const userId = null; // TODO: Dobij user ID iz backend-a ili meta tag-a
        
        // Uploaduj svaki fajl
        console.log('Starting MEGA upload for', filesArray.length, 'file(s)');
        for (const file of filesArray) {
            console.log('Uploading file to MEGA:', file.name, file.size, 'bytes');
            try {
                const result = await uploadFileToMega(file, 'digital.kotor/documents', userId);
                console.log('Upload result:', result);
                if (result.success) {
                    results.push(result);
                    console.log('File uploaded successfully, added to results');
                } else {
                    console.error('Upload failed:', result.error);
                    throw new Error(`Failed to upload ${file.name}: ${result.error}`);
                }
            } catch (uploadError) {
                console.error('Error during file upload:', uploadError);
                throw uploadError;
            }
        }
        
        console.log('All files uploaded successfully. Results count:', results.length);
        console.log('Results:', results);

        // Proveri da li ima rezultata
        if (results.length === 0) {
            console.error('No files were uploaded to MEGA - results array is empty');
            throw new Error('No files were uploaded to MEGA');
        }

        console.log('Preparing to send metadata to backend. Files to send:', results.length);
        
        // Pošalji metadata na backend
        const requestBody = {
            name: documentName,
            category: category,
            expires_at: expiresAt,
            files: results.map(r => ({
                mega_node_id: r.nodeId,
                mega_link: r.megaLink,
                name: r.name,
                size: r.size,
                timestamp: r.timestamp
            }))
        };
        
        console.log('Request body:', JSON.stringify(requestBody, null, 2));
        
        const response = await fetch('/documents/store-mega', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });

        // Proveri response status
        const contentType = response.headers.get('content-type');
        if (!response.ok) {
            let errorMessage = 'Failed to save metadata';
            if (contentType && contentType.includes('application/json')) {
                try {
                    const error = await response.json();
                    errorMessage = error.message || error.error || errorMessage;
                } catch (e) {
                    // Ako ne može da parsira JSON, koristi status text
                    errorMessage = `${response.status} ${response.statusText}`;
                }
            } else {
                // Ako je HTML response, pokušaj da pročitaš tekst
                const text = await response.text();
                console.error('Non-JSON error response:', text);
                errorMessage = `Server error: ${response.status} ${response.statusText}`;
            }
            throw new Error(errorMessage);
        }

        // Parsiraj JSON response
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        console.log('Backend response:', data);
        console.log('=== uploadFilesToMegaAndSave SUCCESS ===');
        return {
            success: true,
            document_id: data.document_id,
            message: 'Files uploaded successfully to MEGA'
        };

    } catch (error) {
        console.error('=== uploadFilesToMegaAndSave ERROR ===');
        console.error('Error details:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        return {
            success: false,
            error: error.message
        };
    }
}

// Export funkcija
if (typeof window !== 'undefined') {
    window.megaUpload = {
        uploadFilesToMegaAndSave,
        uploadFileToMega,
        initMegaStorage
    };
}
