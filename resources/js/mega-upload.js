/**
 * MEGA Browser Upload Handler
 * 
 * Uploaduje fajlove direktno na MEGA iz browser-a koristeći megajs
 * Backend samo čuva metadata (nodeId, link, size, itd.)
 */

import { Storage } from 'megajs';

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
        // Dobij MEGA kredencijale od backend-a (session token ili credentials)
        const response = await fetch('/api/mega/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (!response.ok) {
            throw new Error('Failed to get MEGA session');
        }

        const data = await response.json();
        
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
        // megajs zahteva file content (ArrayBuffer ili Blob), ne File objekat direktno
        console.log('Uploading file to MEGA:', file.name);
        
        // Pročitaj file content kao ArrayBuffer
        const fileData = await file.arrayBuffer();
        
        // Upload u target folder
        const uploadedFile = await targetFolder.upload({
            name: file.name,
            size: file.size
        }, fileData).complete;

        // Kreiraj public share link
        // link() kreira share link, vraća objekat sa url property
        // megajs vraća File objekat sa različitim property-jima
        // Proveri različite moguće property-je za node ID
        const nodeId = uploadedFile.nodeId || uploadedFile.handle || uploadedFile.id || uploadedFile.downloadId;
        const share = await uploadedFile.link();
        const megaLink = share.url || share || `https://mega.nz/file/${nodeId}`;
        
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
    const results = [];

    try {
        // Inicijalizuj MEGA Storage
        await initMegaStorage();

        // Dobij user ID iz meta tag-a ili drugog izvora (ako je dostupan)
        // Za sada koristimo folderPath bez user ID-a, ali možemo dodati kasnije
        const userId = null; // TODO: Dobij user ID iz backend-a ili meta tag-a
        
        // Uploaduj svaki fajl
        for (const file of Array.from(files)) {
            const result = await uploadFileToMega(file, 'digital.kotor/documents', userId);
            if (result.success) {
                results.push(result);
            } else {
                throw new Error(`Failed to upload ${file.name}: ${result.error}`);
            }
        }

        // Pošalji metadata na backend
        const response = await fetch('/documents/store-mega', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
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
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to save metadata');
        }

        const data = await response.json();
        return {
            success: true,
            document_id: data.document_id,
            message: 'Files uploaded successfully to MEGA'
        };

    } catch (error) {
        console.error('Upload and save error:', error);
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
