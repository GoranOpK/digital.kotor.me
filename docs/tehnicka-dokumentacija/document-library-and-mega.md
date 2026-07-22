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
7. Smart PDF (Imagick): mali PDF pass-through / veliki PDF optimizacija; zatim status → processed
8. ExternalFileArchiveService → eksterni provider (MEGA); zapis u `external_file_archives`
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
| Slika > 2 MB | Svaka pojedinačna slika može imati najviše 2 MB. |
| PDF > 20 MB | PDF dokument može imati najviše 20 MB. |
| Prekoračena kvota | Dokument nije moguće sačuvati jer bi bila prekoračena ukupna kvota od 20 MB. |

Processing poruka potvrđuje da je **HTTP upload završen**; asinhrona obrada/arhiva može još trajati. Korisničke poruke **ne spominju MEGA**.

Legacy MEGA poruke (flag OFF) ostaju nepromijenjene.

---

## Ograničenja veličine (Paket 2D)

### Slike (JPEG/PNG)

- Maksimalno **2 MB** po ulaznoj slici (`DOCUMENT_LIBRARY_IMAGE_MAX_KB`)
- Više slika → merge u jedan PDF; finalni PDF smije biti > 2 MB ako staje u kvotu

### PDF

- Maksimalno **20 MB** po ulaznom PDF-u (`DOCUMENT_LIBRARY_PDF_MAX_KB`)
- Višestranični PDF = **jedan** fajl
- **Smart tok (PHP Imagick, bez CLI convert):**
  - **&lt; threshold** (default 3 MB): pass-through, `optimized=false`, tool `pass-through`
  - **≥ threshold**: Imagick ~200 DPI, grayscale, JPEG quality ~82
  - ako optimizovani fajl **≥** original: zadržava se original (`optimized=false`, tool `imagick-reverted` — Imagick je pokušao, rezultat odbačen)

### Korisnička kvota

- Ukupno **20 MB** (`DOCUMENT_LIBRARY_USER_QUOTA_BYTES`)
- Provjera u **bajtovima** prema **finalnom** PDF-u
- Pri prekoračenju: brišu se finalni i privremeni fajlovi, DB red se uklanja, archive job se **ne** dispatchuje

### Infrastrukturno (Plesk/PHP) — shared hosting, provjereno stanje

Produkcija `digital.kotor.me` je na **shared hostingu** (Plesk). PHP Settings prikazuju (Default, **neizmjenjivo** iz Plesk panela korisnika — mijenja se samo PHP verzija / FastCGI vs PHP-FPM):

| Parametar | Trenutno | Napomena |
|-----------|----------|----------|
| PHP | **8.3.31** | FastCGI / PHP-FPM |
| `memory_limit` | **128M** (Default) | smoke test velikog skena |
| `max_execution_time` | **30** (Default) | |
| `max_input_time` | **60** (Default) | |
| `upload_max_filesize` | **2M** (Default) | **efektivni max jednog fajla na produkciji** |
| `post_max_size` | **8M** (Default) | **efektivni max HTTP POST-a** |
| `file_uploads` | on | |

**Razlika limita (važno):**

| Sloj | Vrijednost | Značenje |
|------|------------|----------|
| Aplikacija (Laravel / Biblioteka) | PDF do **20 MB** | konfiguracija u kodu — **ne** smanjivati |
| Trenutna produkcija (PHP) | upload **2M**, post **8M** | shared hosting Default |
| Cilj nakon intervencije provajdera | upload **25M**, post **32M** | tada proradi aplikacioni 20 MB |

Zbog `upload_max_filesize=2M` PHP **odbija** upload prije Laravel validacije. Aplikacija **podržava** PDF do 20 MB, ali na trenutnoj produkciji **efektivni** limit ostaje **2 MB** dok hosting provajder ne poveća PHP limite. To **nije** ograničenje Laravel aplikacije.

**Memory (`memory_limit=128M`):** `PdfOptimizer` čita stranice jednu po jednu, ali drži obrađene stranice u Imagick output objektu do `writeImages`. Za 10–15 skeniranih stranica @ 200 DPI postoji rizik OOM — nije dokazano da je 128M dovoljno. Smoke test nakon povećanja upload limita; 256M samo ako OOM.

ImageMagick CLI nije potreban za novi PDF tok (`pdf:check` READY).

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

## PDF podrška (`pdf:check` + Paket 2D)

Produkcijski `pdf:check` je **READY FOR PDF OPTIMIZATION** (Imagick + Ghostscript PASS; ImageMagick CLI WARN nije blokacija).

Novi PDF tok koristi **PHP Imagick** (`PdfOptimizer`) — ne zavisi od CLI `convert`/`magick`.

**Plesk → Laravel Toolkit → Artisan → `pdf:check`** za ponovnu dijagnostiku nakon PHP/Imagick promjena.

**Lokal vs produkcija:** lokalni BLOCKED **ne** znači da je produkcija blokirana.

Privremeni probe fajlovi: `storage/app/pdf-diagnostics/` (komanda briše nakon rada).

---

## Poznata ograničenja (Biblioteka)

1. Stari cloud dokumenti sa `file_size` null/0 — zaseban backfill (novi uploadi imaju ispravan `file_size`; recalculate može podcijeniti kvotu za stare redove)
2. Shared hosting: PHP Default `upload_max_filesize=2M` / `post_max_size=8M` (**neizmjenjivo** iz Plesk panela) — efektivni produkcijski upload ostaje 2 MB dok provajder ne poveća limite; aplikacioni PDF limit ostaje 20 MB
3. `memory_limit=128M` — smoke test velikog skeniranog PDF-a @ 200 DPI nakon povećanja upload limita; ne podizati na 256M bez OOM dokaza
4. Paralelni upload race na kvotu nije riješen
5. Plesk cron + 55s worker nije zamjena za Supervisor
6. Provjeriti da je document root `public/` (zaštita root PHP skripti)

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
