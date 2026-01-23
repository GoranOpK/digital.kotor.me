# MEGA Browser Upload - Sledeći koraci

## ✅ Šta je implementirano i commit-ovano:

1. ✅ **JavaScript modul** (`resources/js/mega-upload.js`) - upload direktno na MEGA iz browser-a
2. ✅ **Backend endpoint-e**:
   - `POST /api/mega/session` - vraća MEGA kredencijale za browser
   - `POST /documents/store-mega` - čuva MEGA metadata u bazi
3. ✅ **DocumentController metode**:
   - `getMegaSession()` - vraća MEGA kredencijale
   - `storeMegaMetadata()` - čuva metadata
   - `download()` - redirect na MEGA link
4. ✅ **Upload form** - modifikovan da koristi `handleMegaUpload()` JavaScript funkciju
5. ✅ **package.json** - dodat `megajs` dependency
6. ✅ **app.js** - importovan `mega-upload.js`

**Sve promene su commit-ovane i push-ovane na GitHub.**

## 🔧 Šta treba da uradiš sada:

### Korak 1: Instaliraj npm paket na serveru

**Ako imaš SSH pristup:**
```bash
cd /path/to/your/project
npm install
```

**Ili kroz Plesk:**
- Proveri da li postoji npm opcija u Plesk-u
- Ili koristi Terminal opciju (ako je dostupna)

**Ili na lokalnoj mašini (pa upload build fajlova):**
```bash
cd c:\temp\digital.kotor.me
npm install
npm run build
# Upload build fajlova na server
```

### Korak 2: Build JavaScript fajlove

**Za production:**
```bash
npm run build
```

Ovo će generisati build fajlove u `public/build/` folderu.

### Korak 3: Testiraj upload

1. Otvori aplikaciju u browser-u
2. Idi na `/documents` stranicu
3. Otvori browser console (F12)
4. Upload-uj mali test fajl
5. Proveri:
   - **Console** - da li ima JavaScript greške?
   - **Network tab** - da li se pozivaju `/api/mega/session` i `/documents/store-mega`?
   - **MEGA nalog** - da li se fajl pojavio na MEGA-u?
   - **Baza** - da li se metadata čuva u `user_documents` tabeli?

### Korak 4: Ako ne radi - debug koraci

**Browser Console (F12):**
```
- Da li se učitava mega-upload.js?
- Da li postoji window.megaUpload?
- Da li ima greške prilikom upload-a?
```

**Network Tab:**
```
- POST /api/mega/session - da li vraća email/password?
- POST /documents/store-mega - da li prima metadata?
- Da li ima HTTP greške?
```

**Laravel Logs:**
```
- Proveri storage/logs/laravel.log
- Traži "MEGA" u logovima
```

## ⚠️ Važne napomene:

### Bezbednost:

**TRENUTNO:** Backend vraća MEGA email/password frontend-u. **Ovo nije idealno** zbog bezbednosti - kredencijali su vidljivi u browser-u.

**ZA BUDUĆNOST (opciono):**
- Backend se jednom uloguje na MEGA i dobije session token
- Backend čuva session token u cache-u
- Backend prosleđuje samo session token frontend-u (bez password-a)

### PDF obrada:

**TRENUTNO:** Fajlovi se upload-uju direktno na MEGA **bez obrade** (bez greyscale, 300 DPI, itd.).

**AKO TREBA OBRADA:**
- Možemo dodati opciju: obradi lokalno pre upload-a
- Backend preuzima fajl, obrađuje, vraća obrađeni PDF
- Frontend uploaduje obrađeni PDF na MEGA

**Ili:**
- Ostavi direktno upload (brže, lakše)
- Obrada se preskače za MEGA upload-ove

## 📋 Checklist za testiranje:

- [ ] Instaliraj `npm install` (na serveru)
- [ ] Build JavaScript (`npm run build`)
- [ ] Proveri da li se `public/build/` folder kreira
- [ ] Testiraj upload malog fajla
- [ ] Proveri browser console (greške?)
- [ ] Proveri Network tab (API pozivi?)
- [ ] Proveri MEGA nalog (fajl na MEGA?)
- [ ] Proveri bazu (`user_documents` tabela - `cloud_path` kolona?)
- [ ] Testiraj download (redirect na MEGA link?)

## 🎯 Očekivani flow:

1. ✅ Korisnik bira fajlove u browser-u
2. ✅ JavaScript (`megajs`) uploaduje fajlove direktno na MEGA iz browser-a
3. ✅ `megajs` automatski rešava Hashcash challenge (u browser-u)
4. ✅ JavaScript dobija MEGA metadata (link, nodeId, size, itd.)
5. ✅ JavaScript šalje metadata na Laravel backend (`/documents/store-mega`)
6. ✅ Backend čuva metadata u bazi (`cloud_path` = MEGA link)
7. ✅ Download redirect-uje direktno na MEGA link

## 🐛 Ako ne radi:

**Pošalji mi:**
1. Browser console output (F12 → Console)
2. Network tab - HTTP odgovore za `/api/mega/session` i `/documents/store-mega`
3. Laravel logs - delove sa "MEGA" u `storage/logs/laravel.log`

Tako ću moći da pomognem sa debugovanjem!
