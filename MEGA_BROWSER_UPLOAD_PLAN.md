# MEGA Browser Upload Plan

## Arhitektura:

### Trenutni flow (Server-side):
1. Korisnik bira fajlove u browser-u
2. Fajlovi se šalju na Laravel backend (`/documents`)
3. Backend čuva fajlove lokalno
4. Backend obrađuje fajlove (PDF conversion)
5. Backend pokušava upload na MEGA (ne radi zbog Hashcash)

### Novi flow (Browser-side):
1. Korisnik bira fajlove u browser-u
2. **JavaScript (`megajs`) direktno uploaduje fajlove na MEGA** iz browser-a
3. JavaScript dobija MEGA metadata (nodeId, link, size, itd.)
4. JavaScript šalje metadata na Laravel backend
5. Backend čuva samo metadata u bazi
6. Backend može da obrađuje fajlove (ako je potrebno) ili koristi direktno sa MEGA

## Prednosti:

✅ **Zaobiđe Hashcash problem** - `megajs` automatski rešava Hashcash u browser-u
✅ **Smanjuje opterećenje servera** - fajlovi idu direktno na MEGA
✅ **Brže upload** - direktno sa korisničkog računara na MEGA
✅ **Backend samo čuva metadata** - lakše održavanje

## Izazovi:

⚠️ **Autentifikacija** - MEGA kredencijali moraju biti u browser-u (bezbednosni rizik)
⚠️ **Obrada fajlova** - PDF conversion mora ići pre upload-a ili posle (možda lokalno pa upload)
⚠️ **Download** - Treba da vratimo fajlove sa MEGA (možda public link)

## Rešenje za autentifikaciju:

### Opcija A: Korisnik se uloguje na MEGA u browser-u (preporučeno)
- Korisnik uloguje MEGA nalog jednom u browser-u
- `megajs` koristi session token
- Nema potrebe za čuvanje password-a u browser-u

### Opcija B: Backend šalje session token frontendu
- Backend se jednom uloguje na MEGA i dobije session
- Backend šalje session token frontendu za korisnje
- Frontend koristi session token za upload

### Opcija C: Koristiti server MEGA kredencijale (NE preporučujem)
- Server MEGA kredencijali se šalju u frontend (bezbednosni rizik)

## Implementacija:

### Frontend (`resources/views/documents/index.blade.php`):
```javascript
import { Storage } from 'megajs';

// 1. Inicijalizuj MEGA Storage sa kredencijalima
const storage = await new Storage({
    email: 'informatika@kotor.me',
    password: 'password' // Ili session token
}).ready;

// 2. Upload fajla direktno na MEGA
const file = fileInput.files[0];
const uploadedFile = await storage.upload({
    name: file.name,
    size: file.size
}, file).complete;

// 3. Kreiraj public share link
const share = await uploadedFile.link({ downloadId: null });
const megaLink = share.url;

// 4. Pošalji metadata na backend
await fetch('/documents/store-mega', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        mega_node_id: uploadedFile.nodeId,
        mega_link: megaLink,
        name: uploadedFile.name,
        size: uploadedFile.size,
        // ... ostali metadata
    })
});
```

### Backend (`app/Http/Controllers/DocumentController.php`):
```php
public function storeMegaMetadata(Request $request)
{
    // Validacija
    $validated = $request->validate([
        'mega_node_id' => 'required|string',
        'mega_link' => 'required|url',
        'name' => 'required|string',
        'size' => 'required|integer',
    ]);

    // Kreiraj UserDocument sa MEGA metadata
    $document = UserDocument::create([
        'user_id' => Auth::id(),
        'name' => $validated['name'],
        'file_path' => null, // Nema lokalni fajl
        'cloud_path' => $validated['mega_node_id'], // MEGA node ID
        'file_size' => $validated['size'],
        'status' => 'processed',
        'processed_at' => now(),
    ]);

    // Storage se ne računa jer je na cloud-u
    // Ili možda čuvamo `mega_link` umesto `cloud_path`

    return response()->json([
        'success' => true,
        'document_id' => $document->id
    ]);
}
```

## Izazov: PDF obrada

Ako treba da obradimo fajlove (greyscale, 300 DPI, itd.):

### Opcija 1: Obradi lokalno pre upload-a
- JavaScript preuzima fajl
- Šalje na backend za obradu
- Backend vraća obrađeni PDF
- JavaScript uploaduje obrađeni PDF na MEGA

### Opcija 2: Upload originala, obrada kasnije
- Upload originala na MEGA
- Backend kasnije preuzima, obrađuje, i re-uploaduje

### Opcija 3: Obradi na client-side (ako je moguće)
- Koristi JavaScript biblioteku za PDF obradu (npr. PDF.js + Canvas)
- Upload obrađenog PDF-a direktno

## Preporuka:

**Koristiti Opciju 1 (Obradi lokalno pre upload-a)** jer:
- Već imamo obradu na backend-u
- Kontrolisano i pouzdano
- Jednostavno za implementaciju

## Sledeći koraci:

1. ✅ Instaliraj `megajs` u frontend (`npm install megajs`)
2. ✅ Modifikuj upload form da koristi JavaScript umesto HTML submit
3. ✅ Dodaj novi backend endpoint za čuvanje MEGA metadata
4. ✅ Modifikuj download da koristi MEGA link
5. ✅ Testiraj end-to-end flow
