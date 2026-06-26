import sharp from 'sharp';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const svgPath = path.join(root, 'public/favicon.svg');

async function renderPng(size, outputPath) {
    await sharp(svgPath, { density: Math.max(192, size * 8) })
        .resize(size, size)
        .png()
        .toFile(outputPath);
}

const outputs = [
    [32, path.join(root, 'public/favicon-32.png')],
    [48, path.join(root, 'public/favicon-48.png')],
    [64, path.join(root, 'public/favicon-64.png')],
    [32, path.join(root, 'public/favicon.ico')],
    [180, path.join(root, 'public/apple-touch-icon.png')],
];

await Promise.all(outputs.map(([size, file]) => renderPng(size, file)));

const stats = fs.statSync(path.join(root, 'public/favicon-32.png'));
console.log(`Favicons generated from SVG (${stats.size} bytes for 32px).`);
