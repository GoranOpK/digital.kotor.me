import sharp from 'sharp';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const logoPath = path.join(root, 'public/img/logo.png');
const svgPath = path.join(root, 'public/favicon.svg');

const brandBlue = { r: 11, g: 61, b: 145, a: 255 };
const cropWidth = 175;
const cropHeight = 241;

async function makeFromLogo(size, outputPath) {
    const resized = await sharp(logoPath)
        .extract({ left: 0, top: 0, width: cropWidth, height: cropHeight })
        .resize(Math.round(size * 0.9), Math.round(size * 0.9), { fit: 'inside' })
        .greyscale()
        .raw()
        .toBuffer({ resolveWithObject: true });

    const { data, info } = resized;
    const pixels = Buffer.alloc(info.width * info.height * 4);

    for (let i = 0; i < info.width * info.height; i++) {
        const offset = i * 4;
        if (data[i] > 80) {
            pixels[offset] = brandBlue.r;
            pixels[offset + 1] = brandBlue.g;
            pixels[offset + 2] = brandBlue.b;
            pixels[offset + 3] = 255;
        } else {
            pixels[offset + 3] = 0;
        }
    }

    const padTop = Math.floor((size - info.height) / 2);
    const padLeft = Math.floor((size - info.width) / 2);

    await sharp(pixels, {
        raw: { width: info.width, height: info.height, channels: 4 },
    })
        .extend({
            top: padTop,
            bottom: size - info.height - padTop,
            left: padLeft,
            right: size - info.width - padLeft,
            background: { r: 0, g: 0, b: 0, alpha: 0 },
        })
        .png()
        .toFile(outputPath);
}

async function makeFromSvg(size, outputPath) {
    await sharp(svgPath, { density: Math.max(192, size * 8) })
        .resize(size, size)
        .png()
        .toFile(outputPath);
}

await Promise.all([
    makeFromLogo(32, path.join(root, 'public/favicon-32.png')),
    makeFromLogo(48, path.join(root, 'public/favicon-48.png')),
    makeFromLogo(64, path.join(root, 'public/favicon-64.png')),
    makeFromLogo(32, path.join(root, 'public/favicon.ico')),
    makeFromSvg(180, path.join(root, 'public/apple-touch-icon.png')),
]);

const stats = fs.statSync(path.join(root, 'public/favicon-32.png'));
console.log(`Favicons generated: blue lines only, transparent background (${stats.size} bytes).`);
