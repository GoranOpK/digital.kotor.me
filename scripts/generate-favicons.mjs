import sharp from 'sharp';
import { fileURLToPath } from 'url';
import path from 'path';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const logoPath = path.join(root, 'public/img/logo.png');
const brandBlue = { r: 11, g: 61, b: 145 };
const cropWidth = 190;
const cropHeight = 241;

const base = sharp(logoPath)
    .extract({ left: 0, top: 0, width: cropWidth, height: cropHeight })
    .flatten({ background: brandBlue });

await Promise.all([
    base.clone().resize(32, 32).png().toFile(path.join(root, 'public/favicon-32.png')),
    base.clone().resize(48, 48).png().toFile(path.join(root, 'public/favicon-48.png')),
    base.clone().resize(32, 32).toFile(path.join(root, 'public/favicon.ico')),
]);

console.log('Favicons generated in public/');
