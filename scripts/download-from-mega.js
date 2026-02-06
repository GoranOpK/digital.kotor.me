#!/usr/bin/env node
/**
 * Preuzima fajl sa MEGA-e (share link) i čuva na disk.
 * Poziva se iz PHP (DocumentController::download).
 *
 * Usage: node scripts/download-from-mega.js "<mega_url>" "<output_path>"
 *
 * Output path mora biti apsolutna putanja. Fajl će biti sačuvan pod tim imenom.
 */

import { createWriteStream } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { File } from 'megajs';

const __dirname = dirname(fileURLToPath(import.meta.url));

async function main() {
    const megaUrl = process.argv[2];
    const outputPath = process.argv[3];

    if (!megaUrl || !outputPath) {
        console.error('Usage: node scripts/download-from-mega.js "<mega_url>" "<output_path>"');
        process.exit(1);
    }

    try {
        const file = File.fromURL(megaUrl);
        let target = await file.loadAttributes();

        if (target.children) {
            const firstFile = target.find((node) => !node.children, true);
            if (!firstFile) throw new Error('Folder nema fajlova');
            target = firstFile;
        }

        const stream = target.download();
        const writeStream = createWriteStream(outputPath);
        stream.pipe(writeStream);

        await new Promise((resolve, reject) => {
            writeStream.on('finish', resolve);
            writeStream.on('error', reject);
            stream.on('error', reject);
        });

        process.exit(0);
    } catch (err) {
        console.error('download-from-mega error:', err.message);
        process.exit(1);
    }
}

main();
