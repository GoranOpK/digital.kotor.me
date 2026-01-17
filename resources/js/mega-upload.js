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
async function uploadFileToMega(file, folderPath = 'digital.kotor/documents') {
    try {
        // Inicijalizuj MEGA Storage ako nije već inicijalizovan
        const storage = await initMegaStorage();

        // Pronađi ili kreiraj folder strukturu
        let targetFolder = await findOrCreateFolder(storage, folderPath);
        
        if (!targetFolder) {
            // Ako nema folder strukture, uploaduj u root
            targetFolder = storage.root;
        }

        // Upload fajla
        console.log('Uploading file to MEGA:', file.name);
        const uploadedFile = await storage.upload({
            name: file.name,
            size: file.size
        }, file, {
            parent: targetFolder
        }).complete;

        // Kreiraj public share link
        const share = await uploadedFile.link({ downloadId: null });
        const megaLink = share.url || `https://mega.nz/file/${uploadedFile.nodeId}`;

        console.log('File uploaded successfully:', {
            nodeId: uploadedFile.nodeId,
            name: uploadedFile.name,
            size: uploadedFile.size,
            link: megaLink
        });

        return {
            success: true,
            nodeId: uploadedFile.nodeId,
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
        const children = await currentFolder.children;
        let found = children.find(child => 
            child.directory && child.name === folderName
        );

        if (!found) {
            // Kreiraj novi folder
            found = await storage.mkdir(folderName, currentFolder);
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

        // Uploaduj svaki fajl
        for (const file of Array.from(files)) {
            const result = await uploadFileToMega(file);
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
