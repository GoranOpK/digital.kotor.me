# MEGA Browser Upload - Koraci za implementaciju

## ✅ Šta je već urađeno:

1. ✅ **JavaScript modul** (`resources/js/mega-upload.js`) - upload direktno na MEGA iz browser-a
2. ✅ **Backend endpoint-e** - `/api/mega/session` i `/documents/store-mega`
3. ✅ **DocumentController metode** - `getMegaSession()` i `storeMegaMetadata()`
4. ✅ **Rute** - dodate u `routes/web.php`
5. ✅ **package.json** - dodat `megajs` dependency
6. ✅ **app.js** - importovan `mega-upload.js`
7. ✅ **Upload form** - modifikovan da koristi `handleMegaUpload()`
8. ✅ **Download metoda** - modifikovana da redirect-uje na MEGA link

## 🔧 Sledeći koraci (šta treba da uradiš):

### Korak 1: Instaliraj npm paket

**Na lokalnoj mašini (za development):**
```bash
cd c:\temp\digital.kotor.me
npm install
```

**Ili na serveru (preko Plesk ili SSH):**
```bash
cd /path/to/your/project
npm install
```

### Korak 2: Build JavaScript fajlove

**Za production:**
```bash
npm run build
```

**Ili za development (sa watch mode):**
```bash
npm run dev
```

**Na serveru (Plesk):**
- Ako imaš SSH pristup, pokreni `npm run build`
- Ili možda postoji npm build opcija u Plesk-u

### Korak 3: Testiraj upload

1. Otvori aplikaciju u browser-u
2. Idi na `/documents` stranicu
3. Pokušaj da upload-uješ mali test fajl
4. Proveri browser console (F12) - da li ima JavaScript greške?
5. Proveri Network tab - da li se pozivaju `/api/mega/session` i `/documents/store-mega`?
6. Proveri MEGA nalog - da li se fajl pojavio?

### Korak 4: Ako ne radi - debug

**Browser console (F12):**
- Da li se učitava `mega-upload.js`?
- Da li ima greške prilikom upload-a?
- Da li `window.megaUpload` postoji?

**Network tab:**
- Da li `/api/mega/session` vraća kredencijale?
- Da li `/documents/store-mega` prima metadata?
- Da li ima HTTP greške?

**Laravel logs:**
- Proveri `storage/logs/laravel.log` za backend greške

## ⚠️ Važne napomene:

### Bezbednost:

**TRENUTNO:** Backend vraća MEGA email/password frontend-u. **Ovo nije idealno** zbog bezbednosti - kredencijali su vidljivi u browser-u i JavaScript-u.

**BOLJE REŠENJE (za budućnost):**
- Backend se jednom uloguje na MEGA i dobije session token
- Backend čuva session token u cache-u (Redis, file cache, itd.)
- Backend prosleđuje samo session token frontend-u (bez password-a)
- Frontend koristi session token za upload

### PDF obrada:

**TRENUTNO:** Fajlovi se upload-uju direktno na MEGA bez obrade (greyscale, 300 DPI, itd.).

**AKO TREBA OBRADA:**
- Možemo dodati opciju: obradi lokalno pre upload-a
- Backend preuzima fajl, obrađuje, vraća obrađeni PDF
- Frontend uploaduje obrađeni PDF na MEGA

## 📋 Checklist:

- [ ] Instaliraj `npm install` (na lokalnoj ili serveru)
- [ ] Build JavaScript (`npm run build`)
- [ ] Testiraj upload malog fajla
- [ ] Proveri browser console (greške?)
- [ ] Proveri Network tab (API pozivi?)
- [ ] Proveri MEGA nalog (fajl na MEGA?)
- [ ] Proveri bazu (metadata u `user_documents`?)
- [ ] Testiraj download (redirect na MEGA link?)

## 🎯 Očekivani rezultat:

Kada sve radi:
1. ✅ Korisnik bira fajlove u browser-u
2. ✅ JavaScript uploaduje fajlove direktno na MEGA iz browser-a
3. ✅ JavaScript dobija MEGA metadata (link, nodeId, size, itd.)
4. ✅ JavaScript šalje metadata na Laravel backend
5. ✅ Backend čuva metadata u bazi (`cloud_path` = MEGA link)
6. ✅ Download redirect-uje direktno na MEGA link
7. ✅ Hashcash problem je zaobiđen (megajs rešava u browser-u)

## 💡 Sledeći koraci (opciono):

1. **Session token caching** - za bolju bezbednost
2. **PDF obrada pre upload-a** - ako je potrebno
3. **Progress bar** - za prikaz napretka upload-a
4. **Error handling** - bolje greške i fallback opcije
