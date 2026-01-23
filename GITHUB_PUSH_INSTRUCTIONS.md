# GitHub Push Instrukcije - Browser-side MEGA Upload

## 🚀 Koraci za commit i push:

### 1. Proveri status:
```bash
cd c:\temp\digital.kotor.me
git status
```

**VAŽNO:** Proveri da `.env` NIJE u staging area. Ako vidiš `.env`, pokreni:
```bash
git reset HEAD .env
```

### 2. Dodaj sve promene:
```bash
git add .
```

### 3. Proveri šta će biti commit-ovano:
```bash
git status
```

**Trebalo bi da vidiš:**
- ✅ `database/migrations/2025_01_16_000001_add_cloud_path_to_user_documents_table.php` (novi)
- ✅ `resources/js/mega-upload.js` (novi - browser-side MEGA upload)
- ✅ `app/Models/UserDocument.php` (modifikovan - dodato `cloud_path`)
- ✅ `app/Services/DocumentProcessor.php` (modifikovan - uklonjen server-side MEGA upload)
- ✅ `app/Http/Controllers/DocumentController.php` (modifikovan - dodati `getMegaSession` i `storeMegaMetadata`, modifikovan download)
- ✅ `package.json` (modifikovan - dodat `megajs`)
- ✅ `composer.json` (modifikovan - uklonjen `tuyenlaptrinh/php-mega-nz`)
- ✅ `routes/web.php` (modifikovan - dodate rute za megajs)
- ✅ `resources/views/documents/index.blade.php` (modifikovan upload form)
- ✅ `resources/js/app.js` (modifikovan - import mega-upload)
- ✅ `config/services.php` (modifikovan - dodata MEGA sekcija)
- ✅ `.md` dokumentacija fajlovi (browser-side MEGA)

**NE bi trebalo da vidiš:**
- ❌ `.env`
- ❌ `vendor/`
- ❌ `node_modules/`
- ❌ `app/Services/MegaStorageService.php` (obrisan)

### 4. Commit sa opisom:
```bash
git commit -m "Add browser-side MEGA.nz upload integration using megajs

- Add cloud_path column to user_documents table
- Add browser-side MEGA upload using megajs library
- Add getMegaSession and storeMegaMetadata endpoints
- Modify DocumentController download to redirect to MEGA links
- Add mega-upload.js for client-side upload handling
- Update package.json with megajs dependency
- Remove server-side MEGA API implementation (MegaStorageService)
- Remove tuyenlaptrinh/php-mega-nz from composer.json
- Add MEGA configuration to config/services.php
- Add browser-side upload documentation"
```

### 5. Push na GitHub:
```bash
git push origin main
```

**ILI** ako tvoja glavna grana se zove `master`:
```bash
git push origin master
```

## ✅ Šta je uklonjeno:

- ❌ `app/Services/MegaStorageService.php` - kompletan server-side MEGA API
- ❌ `tuyenlaptrinh/php-mega-nz` iz `composer.json`
- ❌ Sve server-side MEGA dokumentacione fajlove (11 fajlova)

## ✅ Šta je dodato:

- ✅ Browser-side `megajs` upload implementacija
- ✅ `getMegaSession()` endpoint za kredencijale
- ✅ `storeMegaMetadata()` endpoint za metadata
- ✅ `mega-upload.js` JavaScript modul
- ✅ Browser-side upload dokumentacija

## 📝 Napomene:

1. **Upload na MEGA** se sada vrši **direktno iz browser-a** preko `megajs` biblioteke
2. **Backend** samo čuva **metadata** (MEGA link, node ID, size) kada frontend pošalje
3. **Download** direktno redirect-uje na MEGA link ako je `cloud_path` MEGA URL
4. **Brisanje** sa MEGA trenutno nije implementirano (zahteva browser-side implementaciju)

## 🎯 Sledeći koraci nakon push-a:

1. **Na serveru:**
   ```bash
   npm install
   npm run build
   ```

2. **Testiraj upload:**
   - Otvori aplikaciju u browser-u
   - Idi na `/documents` stranicu
   - Upload-uj test fajl
   - Proveri da li se fajl pojavio na MEGA nalogu

3. **Proveri logove:**
   - Browser console (F12)
   - Laravel logs (`storage/logs/laravel.log`)
