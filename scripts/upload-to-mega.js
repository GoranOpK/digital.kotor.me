#!/usr/bin/env node
/**
 * Uploaduje lokalni fajl na MEGA.
 * Poziva se iz PHP (ApplicationController::uploadDocument).
 *
 * Usage: node scripts/upload-to-mega.js "<file_path>" "<folder_path>" "<filename>"
 * Example: node scripts/upload-to-mega.js "/path/to/file.pdf" "digital.kotor/applications/user_7" "7-20260207-abc123.pdf"
 *
 * Output (stdout): JSON { "mega_link": "...", "mega_node_id": "...", "mega_file_name": "..." }
 * Exit: 0 = success, 1 = error
 */

import { createReadStream, statSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join, basename } from 'path';
import dotenv from 'dotenv';
import { Storage } from 'megajs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = join(__dirname, '..');
dotenv.config({ path: join(projectRoot, '.env') });

function env(name) {
    return process.env[name] || '';
}

async function findOrCreateFolder(storage, folderPath) {
    const folders = folderPath.split('/').filter(Boolean);
    let currentFolder = storage.root;

    for (const folderName of folders) {
        const children = await currentFolder.children;
        let found = children.find((child) => child.directory && child.name === folderName);
        if (!found) {
            found = await currentFolder.mkdir(folderName);
        }
        currentFolder = found;
    }
    return currentFolder;
}

async function main() {
    const filePath = process.argv[2];
    const folderPath = process.argv[3] || 'digital.kotor/applications';
    const filename = process.argv[4] || basename(filePath);

    if (!filePath) {
        console.error('Usage: node scripts/upload-to-mega.js "<file_path>" "<folder_path>" "<filename>"');
        process.exit(1);
    }

    const email = env('MEGA_EMAIL');
    const password = env('MEGA_PASSWORD');
    if (!email || !password) {
        console.error('MEGA_EMAIL and MEGA_PASSWORD required in .env');
        process.exit(1);
    }

    try {
        const stat = statSync(filePath);
        if (!stat.isFile()) {
            console.error('Not a file:', filePath);
            process.exit(1);
        }

        const storage = await new Storage({ email, password }).ready;
        const targetFolder = await findOrCreateFolder(storage, folderPath);

        const stream = createReadStream(filePath);
        const uploadedFile = await targetFolder.upload(
            { name: filename, size: stat.size },
            stream
        ).complete;

        const nodeId = uploadedFile.nodeId || uploadedFile.handle || uploadedFile.id || uploadedFile.downloadId;
        const share = await uploadedFile.link();
        const megaLink = typeof share === 'string' ? share : (share?.url || `https://mega.nz/file/${nodeId}`);

        const result = JSON.stringify({
            mega_link: megaLink,
            mega_node_id: nodeId || null,
            mega_file_name: filename,
        });
        process.stdout.write(result);
        process.exit(0);
    } catch (err) {
        console.error('upload-to-mega error:', err.message);
        process.exit(1);
    }
}

main();
