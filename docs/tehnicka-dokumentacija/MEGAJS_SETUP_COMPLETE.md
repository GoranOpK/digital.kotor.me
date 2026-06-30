# MEGA.js Setup - Kompletno

## ✅ Šta je urađeno:

1. ✅ **package.json** - dodat `megajs: ^1.3.0`
2. ✅ **mega-upload.js** - JavaScript modul za MEGA upload
3. ✅ **app.js** - importovan `mega-upload.js`
4. ✅ **Upload form** - modifikovan da koristi `handleMegaUpload()`
5. ✅ **Backend endpoint-i** - `/api/mega/session` i `/documents/store-mega`
6. ✅ **Download** - modifikovan da redirect-uje na MEGA link

## 🔧 Ispravke u mega-upload.js:

### 1. Upload API:
```javascript
// PRE (pogrešno):
storage.upload({name, size}, file, {parent: targetFolder})

// POSLE (ispravno):
const fileData = await file.arrayBuffer();
targetFolder.upload({name, size}, fileData)
```

### 2. Folder kreiranje:
```javascript
// PRE (pogrešno):
storage.mkdir(folderName, currentFolder)

// POSLE (ispravno):
currentFolder.mkdir(folderName)
```

### 3. Share link:
```javascript
// PRE:
uploadedFile.link({ downloadId: null })

// POSLE:
uploadedFile.link()
```

## 📋 Sledeći koraci:

### 1. Build JavaScript:

**Na serveru (preko Plesk Terminal ili SSH):**
```bash
cd /path/to/your/project
npm run build
```

**Ili lokalno:**
```bash
cd c:\temp\digital.kotor.me
npm run build
# Upload public/build/ folder na server
```

### 2. Testiraj upload:

1. Otvori aplikaciju u browser-u
2. Idi na `/documents` stranicu
3. Otvori browser console (F12)
4. Upload-uj mali test fajl
5. Proveri:
   - **Console** - da li se učitava `mega-upload.js`?
   - **Console** - da li ima greške?
   - **Network tab** - da li se pozivaju `/api/mega/session` i `/documents/store-mega`?
   - **MEGA nalog** - da li se fajl pojavio?

## 🐛 Debug checklist:

### Ako ne radi, proveri:

1. **Browser Console (F12):**
   - Da li se učitava `mega-upload.js`?
   - Da li postoji `window.megaUpload`?
   - Da li ima JavaScript greške?

2. **Network Tab:**
   - `POST /api/mega/session` - da li vraća email/password?
   - `POST /documents/store-mega` - da li prima metadata?
   - Da li ima HTTP greške?

3. **Build fajlovi:**
   - Da li postoji `public/build/` folder?
   - Da li su JavaScript fajlovi build-ovani?

4. **Laravel logs:**
   - Proveri `storage/logs/laravel.log`
   - Traži "MEGA" u logovima

## 💡 Ako ima greške:

**Pošalji mi:**
1. Browser console output (F12 → Console)
2. Network tab - HTTP odgovore
3. Laravel logs - delove sa "MEGA"

Tako ću moći da pomognem sa debugovanjem!
