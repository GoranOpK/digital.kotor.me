# MEGA Upload Debug - Problem sa Browser-side Upload

## Problem:
Upload se dešava lokalno (preko PHP Imagick), a ne na MEGA preko browser-a.

## Uzrok:
JavaScript fajlovi nisu build-ovani na serveru, pa se `mega-upload.js` ne učitava.

## Rešenje:

### 1. Build JavaScript fajlove na serveru:

**Preko Plesk Terminal ili SSH:**
```bash
cd /path/to/your/project  # npr. /var/www/vhosts/digital.kotor.me/httpdocs
npm install
npm run build
```

**Ili lokalno i upload:**
```bash
cd c:\temp\digital.kotor.me
npm install
npm run build
# Upload public/build/ folder na server
```

### 2. Proveri da li su fajlovi build-ovani:

Proveri da li postoji `public/build/` folder sa fajlovima:
- `public/build/assets/app-*.js`
- `public/build/assets/app-*.css`
- `public/build/manifest.json`

### 3. Proveri browser console:

Otvori browser console (F12) i proveri:
- **Console tab:** Da li ima greške?
- **Network tab:** Da li se učitava `app.js`?
- **Console:** Pokreni `window.megaUpload` - da li postoji?

### 4. Debug u browser-u:

U browser console pokreni:
```javascript
console.log('megaUpload:', window.megaUpload);
console.log('uploadFilesToMegaAndSave:', window.megaUpload?.uploadFilesToMegaAndSave);
```

Ako je `undefined`, znači da se `mega-upload.js` ne učitava.

## Šta se dešava trenutno:

1. Form se submit-uje
2. `handleMegaUpload()` se poziva
3. Proverava `window.megaUpload` - **ne postoji** (jer nije build-ovano)
4. Fallback na `form.submit()` - standardni Laravel upload
5. Dokument se obrađuje lokalno (PHP Imagick)
6. Fajl ostaje na serveru, ne ide na MEGA

## Nakon build-a:

1. `npm run build` kreira `public/build/` folder
2. Vite učitava `app.js` koji importuje `mega-upload.js`
3. `window.megaUpload` postaje dostupan
4. `handleMegaUpload()` koristi MEGA upload umesto fallback-a

## Provera na serveru:

```bash
# Proveri da li postoji build folder
ls -la public/build/

# Proveri da li su fajlovi tu
ls -la public/build/assets/
```

## Ako build ne radi:

1. Proveri da li je `node` i `npm` instaliran na serveru
2. Proveri da li je `package.json` ispravan
3. Proveri da li je `vite.config.js` ispravan
4. Proveri logove build procesa
