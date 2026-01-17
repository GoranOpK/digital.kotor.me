# MEGA Browser Upload - Setup Instrukcije

## ✅ Šta je implementirano:

1. ✅ **JavaScript modul** (`resources/js/mega-upload.js`) - upload direktno na MEGA iz browser-a
2. ✅ **Backend endpoint-e**:
   - `/api/mega/session` - vraća MEGA kredencijale za browser
   - `/documents/store-mega` - čuva MEGA metadata u bazi
3. ✅ **Rute** - dodate u `routes/web.php`
4. ✅ **DocumentController metode** - `getMegaSession()` i `storeMegaMetadata()`
5. ✅ **package.json** - dodat `megajs` dependency
6. ✅ **app.js** - importovan `mega-upload.js`

## 🔧 Sledeći koraci za implementaciju:

### 1. Instaliraj npm paket na serveru:

```bash
npm install megajs
```

Ili kroz Plesk (ako postoji npm pristup).

### 2. Build JavaScript fajlove:

```bash
npm run build
```

Ili ako koristiš development mode:
```bash
npm run dev
```

### 3. Modifikuj upload form (`resources/views/documents/index.blade.php`):

Trenutno form koristi HTML submit. Treba da modifikuješ da koristi JavaScript:

**Pronađi:**
```html
<form action="{{ route('documents.store') }}" method="POST" ... onsubmit="return prepareFormSubmit(event)">
```

**Zameni `onsubmit` sa:**
```html
<form id="document-upload-form" onsubmit="return handleMegaUpload(event)">
```

**I dodaj na kraju fajla (pre `</section>`):**

```javascript
<script>
async function handleMegaUpload(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validacija
    const name = document.getElementById('name').value;
    const category = document.getElementById('category').value;
    const files = document.getElementById('file').files;
    const expiresAt = document.getElementById('expires_at').value;
    
    if (!name || !category || !files || files.length === 0) {
        alert('Molimo popunite sva obavezna polja.');
        return false;
    }
    
    // Prikaži loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Upload-ovanje na MEGA...';
    
    try {
        // Koristi window.megaUpload funkciju iz mega-upload.js
        if (!window.megaUpload) {
            throw new Error('MEGA upload modul nije učitan');
        }
        
        const result = await window.megaUpload.uploadFilesToMegaAndSave(
            files,
            name,
            category,
            expiresAt || null
        );
        
        if (result.success) {
            // Redirect na listu dokumenata
            window.location.href = '{{ route("documents.index") }}?success=1';
        } else {
            alert('Greška pri upload-u: ' + (result.error || 'Nepoznata greška'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        
    } catch (error) {
        console.error('Upload error:', error);
        alert('Greška pri upload-u: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
    
    return false;
}
</script>
```

### 4. Modifikuj download (`DocumentController::download()`) da koristi MEGA link:

Trenutno `download()` pokušava da downloaduje sa MEGA koristeći `MegaStorageService`. Treba da modifikuješ da direktno redirect-uje na MEGA link:

```php
// Ako dokument ima cloud_path (MEGA link), redirect direktno na MEGA
if ($document->cloud_path && strpos($document->cloud_path, 'mega.nz') !== false) {
    return redirect($document->cloud_path);
}
```

### 5. Testiraj:

1. Upload-uj mali test fajl
2. Proveri da li se fajl upload-uje na MEGA
3. Proveri da li se metadata čuva u bazi
4. Proveri da li download radi (redirect na MEGA link)

## ⚠️ Važne napomene:

### Bezbednost:

**TRENUTNO:** Backend vraća MEGA email/password frontend-u. **Ovo nije idealno** zbog bezbednosti.

**BOLJE REŠENJE (za budućnost):**
1. Backend se jednom uloguje na MEGA i dobije session token
2. Backend čuva session token u cache-u
3. Backend prosleđuje session token frontend-u (bez password-a)

### PDF obrada:

**TRENUTNO:** Fajlovi se upload-uju direktno na MEGA bez obrade (greyscale, 300 DPI).

**OPCIJE:**
1. **Ostavi direktno upload** - fajlovi se čuvaju kako su (bez obrade)
2. **Obradi pre upload-a** - backend preuzima fajl, obrađuje, vraća, frontend uploaduje obrađeni PDF
3. **Obradi posle upload-a** - backend kasnije preuzima sa MEGA, obrađuje, re-uploaduje

**Preporuka:** Za sada ostavi direktno upload (brže, lakše), dodaj obrada kasnije ako je potrebno.

## 📝 Checklist:

- [ ] Instaliraj `npm install megajs`
- [ ] Build JavaScript (`npm run build`)
- [ ] Modifikuj upload form da koristi `handleMegaUpload()`
- [ ] Modifikuj download da redirect-uje na MEGA link
- [ ] Testiraj upload malog fajla
- [ ] Proveri da li se fajl pojavio na MEGA
- [ ] Proveri da li se metadata čuva u bazi
- [ ] Testiraj download

## 🐛 Debug:

Ako ne radi, proveri:

1. **Browser console** - da li ima JavaScript greške?
2. **Network tab** - da li se pozivaju `/api/mega/session` i `/documents/store-mega`?
3. **Laravel logs** - da li ima backend grešaka?
4. **MEGA nalog** - da li se fajlovi upload-uju na MEGA?

## ✅ Kada sve radi:

1. Fajlovi se upload-uju direktno na MEGA iz browser-a
2. Backend čuva samo metadata (MEGA link, nodeId, size, itd.)
3. Download redirect-uje direktno na MEGA link
4. Hashcash problem je zaobiđen jer `megajs` automatski rešava u browser-u
