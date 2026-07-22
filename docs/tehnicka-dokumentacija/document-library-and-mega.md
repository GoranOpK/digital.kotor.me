# Biblioteka dokumenata i MEGA

**Poslednje ažuriranje:** 2026-07-22
**Izvor u kodu:** `DocumentController`, `DocumentProcessor`, `ProcessDocumentJob`, `ExternalFileArchiveService`, `queue-worker.php`, `resources/views/documents/index.blade.php`, `scripts/*.js`
**Relevantni commit (još bez push/deploy u trenutku pisanja):** `b3972de` — `fix: improve document upload processing and storage limits`

Operativni setup (legacy browser MEGA): [MEGA_BROWSER_UPLOAD_SETUP.md](MEGA_BROWSER_UPLOAD_SETUP.md), [MEGAJS_SETUP_COMPLETE.md](MEGAJS_SETUP_COMPLETE.md), [APPLICATION_DOCUMENTS_MEGA.md](APPLICATION_DOCUMENTS_MEGA.md).
Queue / cron: [deployment-and-cron.md](deployment-and-cron.md). Env: [environment-variables.md](environment-variables.md).

---

## Namjena

Svaki prijavljeni korisnik (osim ograničenih uloga) ima **biblioteku dokumenata** — upload, kategorizacija, preuzimanje, vezivanje za prijavu na konkurs.

Dokument se obrađuje lokalno (PDF/slike). Eksterno arhiviranje (MEGA) može ići:

- **server-side** (trenutni produkcijski tok kad je feature flag uključen), ili
- **legacy browser megajs** (kad je feature flag isključen).

---

## Feature flagovi

| Varijabla | Tipična produkcija | Namjena |
|-----------|--------------------|---------|
| `EXTERNAL_ARCHIVE_PROVIDER` | `mega` | Provider arhive |
| `EXTERNAL_ARCHIVE_LIBRARY_UPLOAD` | `true` | `true` = server-side Document Library upload |
| `EXTERNAL_ARCHIVE_DELETE_LOCAL_AFTER_UPLOAD` | `false` | Automatsko brisanje lokalnog fajla nakon arhive — **ne uključivati** u ovom deployu |

U `.env.example` default za `EXTERNAL_ARCHIVE_LIBRARY_UPLOAD` je `false` (siguran default za novi clone). Produkcijski `.env` se ne commit-uje.

---

## Tok uploada — server-side (važeće kad je flag ON)

```
1. Browser: POST /documents (FormData, Accept: application/json)
2. Lokalno čuvanje originala u private storage
3. UserDocument.status = pending; file_size = veličina sačuvanog fajla
4. Dispatch ProcessDocumentJob
5. queue-worker.php preuzima job (database queue)
6. status → processing
7. PDF obrada; status → processed (dokument spreman u Biblioteci / download)
8. U istom ProcessDocumentJob: ExternalFileArchiveService → eksterni provider (MEGA); zapis u `external_file_archives`
9. Frontend polling (documents.status) ažurira listu
10. Dugme Preuzmi dostupno kad je status processed
```

Napomena: `pending → processing → processed` prati lokalnu obradu dokumenta. `processed` znači da je PDF u Biblioteci; **ne garantuje** da je MEGA upload već završen — arhiviranje ide nakon lokalnog `processed` u istom jobu, a `ExternalFileArchive` prati eksterni zapis odvojeno. Greška arhive ne demotira dokument u `failed` ako lokalni PDF postoji. `queued` nije glavni UX signal.

### Statusi (`user_documents.status`)

```text
pending → processing → processed
```

(ili `failed` pri grešci obrade)

### Frontend klasifikacija success odgovora

Nakon JSON success-a (`success`, `document_id`):

- glavni signal: **`data.status`**
- `pending` / `processing` → processing UX + postojeći polling nakon reload-a
- `processed` → processed UX (i kad `queued === true` — archive u pozadini, dokument već dostupan)
- **`queued` nije glavni UX signal**

---

## Tok uploada — legacy browser MEGA (flag OFF)

```
1. POST /documents/process-for-mega — priprema PDF
2. Browser: megajs (POST /api/mega/session)
3. POST /documents/store-mega — metadata (cloud_path, mega_node_id)
```

Isti MEGA pattern i za **dokumente prijava** (`ApplicationController`) — van Biblioteke.

---

## UX poruke (server-side)

| Situacija | Poruka |
|-----------|--------|
| Pending / processing | Dokument je uspješno otpremljen. Obrada je u toku. |
| Processed | Dokument je uspješno sačuvan. |
| Fajl > 2 MB | Svaki pojedinačni fajl može imati najviše 2 MB. |
| Prekoračena kvota | Dokument nije moguće sačuvati jer bi bila prekoračena ukupna kvota od 20 MB. |

Processing poruka potvrđuje da je **HTTP upload završen**; asinhrona obrada/arhiva može još trajati. Korisničke poruke **ne spominju MEGA**.

Legacy MEGA poruke (flag OFF) ostaju nepromijenjene.

---

## Ograničenja veličine

### Pojedinačni ulazni fajl

- Maksimalno **2 MB** po fajlu (`max:2048` u Laravel validaciji; JS UX pomoć)
- Višestranični PDF = **jedan** fajl; broj stranica nije kriterijum

### Multi-file

- Svaki ulazni fajl ≤ 2 MB
- Finalni spojeni PDF **smije** biti > 2 MB
- Mora stati u preostalu korisničku kvotu (izvor: veličina **finalnog** PDF-a)

### Korisnička kvota

- Ukupno **20 MB** (`DocumentProcessor::MAX_STORAGE_PER_USER`)
- Provjera u **bajtovima**
- Pri prekoračenju na merge-u: brišu se finalni i privremeni fajlovi, DB red se uklanja, archive job se **ne** dispatchuje

### Infrastrukturno ograničenje

Produkcijski PHP `post_max_size=8M` može ograničiti **jedan HTTP zahtjev** prije Laravel validacije. Poslovna kvota 20 MB **nije** isto što i max veličina jednog requesta.

---

## Obračun prostora (kvota)

| Stavka | Vrijednost |
|--------|------------|
| Izvor istine | `user_documents.file_size` (integer, bajtovi) |
| Zbir na korisniku | `users.used_storage_bytes` |
| Prikaz | MB (formatiranje iz bajtova) |
| U zbir | aktivni dokumenti (`status != failed`) |
| Van zbira | `failed`; fizički obrisani redovi (nema SoftDeletes) |
| Cloud/MEGA | ulaze preko `file_size` (ne preskaču se zbog odsustva `file_path`) |
| `external_file_archives` | **ne** sabira se dodatno |
| Multi-file | samo veličina finalnog PDF-a |
| Pending | ulazi u kvotu čim je lokalni fajl sačuvan (`file_size` postavljen) |

Nakon brisanja dokumenta radi se `recalculateUserStorage`.

### Poznato ograničenje (stari podaci)

Stariji cloud dokumenti sa `file_size = null` ili `0` mogu ostati van tačnog zbira. Lokalni fajl može poslužiti kao fallback ako još postoji. Za cloud-only stare redove potreban je **zaseban backfill** — **nije** dio `b3972de`. Ne tvrdi se da je historijski produkcijski zbir potpuno ispravljen dok backfill nije urađen.

---

## Statusi dokumenta (`user_documents.status`)

`pending` → `processing` → `processed` / `failed` (te `active` gdje se koristi)

---

## Kategorije biblioteke

- Lični dokumenti
- Finansijski dokumenti
- Poslovni dokumenti
- Ostali dokumenti

---

## Polja MEGA / arhive u bazi

| Kolona / tabela | Namjena |
|-----------------|---------|
| `cloud_path` | Putanja / link (legacy browser tok) |
| `mega_node_id` | ID čvora |
| `mega_file_name` | Ime na MEGA |
| `external_file_archives` | Server-side archive zapisi (ne ulaze u kvotu) |

Objašnjenje cloud_path: [CLOUD_PATH_EXPLANATION.md](CLOUD_PATH_EXPLANATION.md).

---

## Istek dokumenata (`expires_at`)

- Opciono pri uploadu; mora biti **poslije** današnjeg datuma
- Dnevno brisanje: `documents:delete-expired` (v. [deployment-and-cron.md](deployment-and-cron.md))

---

## Env varijable (sažetak)

MEGA kredencijali: `MEGA_EMAIL`, `MEGA_PASSWORD`, `MEGA_BASE_FOLDER`, `NODE_BINARY` — v. [environment-variables.md](environment-variables.md).
Archive flagovi: `EXTERNAL_ARCHIVE_*` — ista stranica.

---

## Sigurnosna napomena

Legacy MEGA sesija za browser upload koristi credentials sa servera (`getMegaSession`). Svjesni trade-off; formalni security pregled — v. [project-todo.md](project-todo.md).

`queue-worker.php` je u rootu projekta; document root treba biti `public/` (v. [deployment-and-cron.md](deployment-and-cron.md)).

---

## PDF podrška (dijagnostika prije Paketa 2D)

Obrada dokumenata koristi **PHP Imagick** / **ImageMagick CLI** (Ghostscript obično kao PDF delegate). Nema SSH na Plesku — provjera:

**Plesk → Laravel Toolkit → Artisan → `pdf:check`**

| Rezultat | Značenje |
|----------|----------|
| `READY FOR PDF OPTIMIZATION` | PDF read, write, PDF → PDF i multi-page PASS |
| `PDF OPTIMIZATION BLOCKED` | Paket 2D **ne** implementirati dok **produkcija** ne prođe |

**Lokal vs produkcija:** lokalni `pdf:check` može biti BLOCKED jer na razvojnom PHP-u nedostaju Imagick/CLI/Ghostscript. To **ne** znači da je produkcija blokirana — odluka za Paket 2D ide isključivo po rezultatu na Plesku (Laravel Toolkit → Artisan).

Privremeni probe fajlovi: `storage/app/pdf-diagnostics/` (komanda briše nakon rada). Ne koristi korisničke dokumente. Detalji: [deployment-and-cron.md](deployment-and-cron.md).

---

## Poznata ograničenja (Biblioteka / Paket 2C)

1. Stari cloud dokumenti sa `file_size` null/0 — zaseban backfill
2. `post_max_size=8M` ograničava jedan HTTP zahtjev
3. Paralelni upload race na kvotu nije riješen
4. Plesk cron + 55s worker nije zamjena za Supervisor
5. Provjeriti da je document root `public/` (zaštita root PHP skripti)
6. Paket 2D (PDF optimizacija) blokiran dok `pdf:check` na produkciji ne bude READY

---

## Rute (sažetak)

| Ruta | Namjena |
|------|---------|
| `documents.index` | Lista |
| `documents.store` | Upload (server-side ili legacy ovisno o flagu) |
| `documents.download` | Preuzimanje |
| `documents.destroy` | Brisanje |
| `documents.status` | Polling statusa |
| `mega.session` | API sesija za megajs (legacy) |

**Ne koristiti** URL `/documents/ime.pdf` za statičke fajlove — Laravel tretira `/documents/{document}` kao rutu.

---

## Povezani dokumenti

- [deployment-and-cron.md](deployment-and-cron.md)
- [environment-variables.md](environment-variables.md)
- [application-lifecycle.md](application-lifecycle.md)
- [project-todo.md](project-todo.md)
