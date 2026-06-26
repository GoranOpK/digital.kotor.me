import sharp from 'sharp';
import { fileURLToPath } from 'url';
import path from 'path';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const logoPath = path.join(root, 'public/img/logo.png');

const brandBlue = { r: 11, g: 61, b: 145 };
const white = { r: 255, g: 255, b: 255 };
const cropWidth = 175;
const cropHeight = 241;

async function makeFavicon(size, outputPath) {
    const resized = await sharp(logoPath)
        .extract({ left: 0, top: 0, width: cropWidth, height: cropHeight })
        .resize(Math.round(size * 0.95), Math.round(size * 0.95), { fit: 'inside' })
        .greyscale()
        .raw()
        .toBuffer({ resolveWithObject: true });

    const { data, info } = resized;
    const pixels = Buffer.alloc(info.width * info.height * 3);

    for (let i = 0; i < info.width * info.height; i++) {
        const isLogo = data[i] > 100;
        const offset = i * 3;

        if (isLogo) {
            pixels[offset] = brandBlue.r;
            pixels[offset + 1] = brandBlue.g;
            pixels[offset + 2] = brandBlue.b;
        } else {
            pixels[offset] = white.r;
            pixels[offset + 1] = white.g;
            pixels[offset + 2] = white.b;
        }
    }

    const padTop = Math.floor((size - info.height) / 2);
    const padLeft = Math.floor((size - info.width) / 2);

    await sharp(pixels, {
        raw: { width: info.width, height: info.height, channels: 3 },
    })
        .extend({
            top: padTop,
            bottom: size - info.height - padTop,
            left: padLeft,
            right: size - info.width - padLeft,
            background: white,
        })
        .png()
        .toFile(outputPath);
}

await Promise.all([
    makeFavicon(32, path.join(root, 'public/favicon-32.png')),
    makeFavicon(48, path.join(root, 'public/favicon-48.png')),
    makeFavicon(64, path.join(root, 'public/favicon-64.png')),
    makeFavicon(32, path.join(root, 'public/favicon.ico')),
]);

console.log('Favicons generated (white background, blue logo).');
