#!/usr/bin/env node
/**
 * Briše sa MEGA-e fajlove isteklih dokumenata.
 * Poziva se iz PHP (documents:delete-expired) sa putanjom do queue JSON-a.
 *
 * Queue: [ { "id": 1, "mega_file_name": "7-20260124-xxx.pdf" }, ... ]
 * Done:  [ { "id": 1 }, ... ] – samo uspešno obrisani
 *
 * Zahteva: MEGA_EMAIL, MEGA_PASSWORD u .env. Pokretati iz project root-a.
 */

import { readFileSync, writeFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import dotenv from 'dotenv';
import { Storage } from 'megajs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = join(__dirname, '..');
dotenv.config({ path: join(projectRoot, '.env') });

function env(name) {
    return process.env[name] || '';
}

async function main() {
    const queuePath = process.argv[2];
    if (!queuePath) {
        console.error('Usage: node scripts/delete-expired-mega.js <path-to-queue.json>');
        process.exit(1);
    }

    const email = env('MEGA_EMAIL');
    const password = env('MEGA_PASSWORD');
    if (!email || !password) {
        console.error('MEGA_EMAIL and MEGA_PASSWORD required in .env');
        process.exit(1);
    }

    let queue;
    try {
        const raw = readFileSync(queuePath, 'utf8');
        queue = JSON.parse(raw);
    } catch (e) {
        console.error('Failed to read queue:', e.message);
        process.exit(1);
    }

    if (!Array.isArray(queue) || queue.length === 0) {
        const donePath = join(dirname(queuePath), 'expired_mega_done.json');
        writeFileSync(donePath, '[]');
        process.exit(0);
    }

    const done = [];
    const storage = await new Storage({ email, password }).ready;

    for (const item of queue) {
        const { id, mega_file_name } = item;
        if (!mega_file_name) continue;
        try {
            const file = storage.find(mega_file_name, true);
            if (!file) {
                console.warn(`MEGA file not found: ${mega_file_name} (id ${id}), skipping`);
                continue;
            }
            await file.delete(true);
            console.log(`MEGA deleted: ${mega_file_name} (id ${id})`);
            done.push({ id });
        } catch (err) {
            console.error(`MEGA delete failed for ${mega_file_name} (id ${id}):`, err.message);
        }
    }

    const donePath = join(dirname(queuePath), 'expired_mega_done.json');
    writeFileSync(donePath, JSON.stringify(done, null, 0));
    process.exit(0);
}

main().catch((err) => {
    console.error('delete-expired-mega error:', err);
    process.exit(1);
});
