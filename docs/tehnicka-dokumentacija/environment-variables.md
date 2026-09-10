# Environment varijable

**Posljednje ažuriranje:** 2026-09-10
**Izvor u kodu:** `.env.example`, `config/*.php`, direktni `env()` pozivi

---

## Osnovno aplikacije

| Varijabla | Default (example) | Namjena |
|-----------|-------------------|---------|
| `APP_NAME` | Digital Kotor | Naziv aplikacije |
| `APP_ENV` | local | Okruženje |
| `APP_KEY` | — | Laravel enkripcija (obavezno) |
| `APP_DEBUG` | true | Debug (false na produkciji) |
| `APP_URL` | http://localhost | Bazni URL |

---

## Identitet (DK-TS-002 / D15)

Izvor u kodu: `config/identity.php`. Default svih četiri ključa = **false**. Promjena na produkciji zahtijeva recycle persistent FastCGI PHP workers (Plesk PHP Settings Apply), zatim HTTP potvrdu. CLI `artisan` **nije** HTTP dokaz.

| Varijabla | Produkcija 2026-09-06 | Namjena |
|-----------|----------------------|---------|
| `IDENTITY_CANONICAL_READ` | `true` | Kanonski identitet je read SSOT |
| `IDENTITY_CANONICAL_WRITE` | `true` | Kanonski HTTP writer authority |
| `IDENTITY_WRITE_FREEZE` | `false` | Targeted identity-write freeze; trenutno isključen nakon GATE 2 |
| `IDENTITY_EP_IDENTITY_FLOWS` | `false` | Durable disable live EP identity tokova; admin DB toggle ne može bypass |

**R1 ACTIVE.** Ne vraćati `canonical_read`/`canonical_write` na `false` kao recovery shortcut. EP hard gate ostaje OPEN; live EP identity tokovi ostaju disabled. Detalj: `DK-TS-002` v1.0.1 §14.1.

---

## JMB/JMBG enkripcija (Faza B1 / B2 / C / D)

Kanonski implementacioni i produkcijski zapis: [jmb-encryption.md](jmb-encryption.md). Ovaj odjeljak ostaje env ugovor. Kanonski redoslijed: A → B1 → B2 → C → D. Faza C2 ne postoji.

Izvor u kodu: `config/jmb.php`, `App\Security\JmbEncryptionService`, `App\Security\JmbDualWrite`, `App\Security\JmbEncryptedReadService`, `jmb:backfill-encrypted`. **Nije** `APP_KEY` / `APP_PREVIOUS_KEYS`. Boot aplikacije **ne** zahtijeva ključ. Faza C dual-write zahtijeva ključ kada se persistuje ne-prazan JMB/JMBG.

**Stanje 2026-09-10:** A/B1/B2/C/D = produkcija. B2 apply + verifikacija = 71 `already_valid`, 0 grešaka. Faza C dual-write deployovana; kontrolisana `users.jmb` verifikacija = PASS. Faza D encrypted-first VALUE read deployovana i produkcijski prihvaćena (profil / Obrazac 1A / Obrazac 2 = PASS). SQL uniqueness ostaje plaintext. Plaintext kolone i dalje postoje.

| Varijabla | Default (example) | Namjena |
|-----------|-------------------|---------|
| `JMB_ENCRYPTION_KEY` | prazno | Aktivni 32-bajtni ključ za AES-256-GCM, format `base64:...`. Samo ovaj ključ se koristi za `encrypt()`. Ne koristiti `APP_KEY`. |
| `JMB_ENCRYPTION_KEY_ID` | `v1` | Aktivni `key_id` u zapisu `jmb:<key_id>:<payload>`. |
| `JMB_ENCRYPTION_PREVIOUS_KEYS` | prazno | Decrypt-only keyring. JSON objekat `{"<key_id>":"base64:..."}`. Prazno dok nema rotacije. |

Rotacija v1 → v2 (samo config; B2 ne radi re-encrypt spremljenih redova):

1. Novi aktivni ključ: `JMB_ENCRYPTION_KEY` + `JMB_ENCRYPTION_KEY_ID=v2`.
2. Stari ključ ostaje decrypt-only: `JMB_ENCRYPTION_PREVIOUS_KEYS={"v1":"<stari JMB_ENCRYPTION_KEY>"}`.
3. `decrypt()` bira tačno `key_id` iz envelope-a. Nema fallback na drugi ključ.

Generisanje ključa:

```text
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

### Faza B2 — `jmb:backfill-encrypted`

Kopira postojeći plaintext JMB/JMBG u paralelne `*_encrypted` kolone. **Ne** mijenja plaintext. **Ne** uvodi dual-write ni encrypted-first read. Pokretanje na produkciji je **odvojena PO-odobrena akcija**, nije dio deploya ni crona.

```text
php artisan jmb:backfill-encrypted --dry-run
php artisan jmb:backfill-encrypted --dry-run --scope=users --chunk=100
php artisan jmb:backfill-encrypted --scope=applications.physical_person_jmbg
```

| Opcija | Ponašanje |
|--------|-----------|
| (bez opcija) | Svih 7 mappinga, upis u encrypted kolone |
| `--dry-run` | Čitanje + encrypt/decrypt validacija u memoriji; **nula** DB upisa |
| `--scope=` | Jedan mapping. Dozvoljeno: `users`, `physical_person_identities`, `legal_entity_authorized_persons`, `foreign_branch_representatives`, `applications.physical_person_jmbg`, `applications.applicant_jmbg`, `business_plans`. Nepoznat scope = greška. |
| `--chunk=` | Broj redova po batch-u. Default `100`, min `1`, max `500`. |

Algoritam po redu: `null`/prazan plaintext → `skipped_no_plaintext` (bez upisa); plaintext + NULL target → encrypt + in-memory decrypt, upis samo ako je round-trip tačan (`encrypted` / `would_encrypt`); postojeći target koji se dekriptuje na isti plaintext → `already_valid` (bez rewrite); mismatch ili nečitljiv target → **stop na prvoj grešci**, target se ne prepisuje, exit ≠ 0.

Izlaz (samo agregati): `scope`, `table`, `scanned`, `encrypted` ili `would_encrypt`, `already_valid`, `skipped_no_plaintext`, `errors`. Na grešci: `table` + `id` + `reason`. Nikad plaintext, ciphertext ili ključ.

Idempotentnost: drugi apply ne mijenja already-valid ciphertext.

Transakcije: nema jedne velike transakcije preko 7 tabela. Svaki uspješan encrypted upis se commit-uje zasebno. Retry nastavlja preko `already_valid`.

Konkurentnost: B2 je bio prije Phase C dual-write. Produkcijski B2 run je završen 2026-09-10 (evidencija: [jmb-encryption.md](jmb-encryption.md#6-produkcijsko-izvršenje-b2--2026-09-10)). Direct DBA izmjene i dalje zaobilaze aplikacioni dual-write.

Exit: `0` uspjeh; ≠ `0` za neispravan key/scope/chunk, mismatch, decrypt failure.

### Faza C — application dual-write

Faza C je na `origin/main` (`3d71cbf`) i **deployovana na produkciju**. Kontrolisana `users.jmb` verifikacija 2026-09-10 = PASS. Evidencija: [jmb-encryption.md](jmb-encryption.md#81-produkcijska-verifikacija-faze-c--2026-09-10-usersjmb).

Faza C piše plaintext i odgovarajuću `*_encrypted` kolonu na podržanim Eloquent persist putanjama (`saving` na modelima koji vlasniče kolonama). **Uniqueness/equality ostaju plaintext.** Encrypted-first VALUE read je Faza D (`JmbEncryptedReadService`), nije Faza C. Faza C dual-write ostaje aktivan. Plaintext se ne uklanja.

- Create/update ne-praznog JMB/JMBG: `plaintext = original`, `encrypted = JmbEncryptionService::encrypt(original)`.
- Namjerno brisanje: obje kolone `null` (prazan string prati B1: encrypted = null).
- Ažuriranje nepovezanih polja **ne** regeneriše ciphertext ako se JMB nije promijenio.
- Neuspjeh enkripcije **sprečava** persist novog/izmijenjenog plaintext JMB-a (isti `save()` / ista transakcija).
- Ručni/direct DB upisi (phpMyAdmin, raw SQL) **zaobilaze** dual-write i operativno su zabranjeni za JMB/JMBG kolone.
- `users.jmb` UNIQUE, `CanonicalIdentifierUniqueness::jmbTaken()` i ostali equality query ostaju na plaintextu. Encrypted kolone nisu unique/index.

### Faza D — encrypted-first VALUE read

Faza D je na `origin/main` (`077a01c`) i **deployovana / PRODUCTION ACCEPTED**. Aplikacioni VALUE read ide encrypted-first (`JmbEncryptedReadService`). Dual-write Faze C i plaintext uniqueness ostaju. Plaintext kolone i dalje postoje. Evidencija: [jmb-encryption.md](jmb-encryption.md#9-faza-d--encrypted-first-value-read).

### JMB lookup digest — application dual-write

Izvor u kodu: `config/jmb.php`, `App\Security\JmbLookupService`, `App\Security\JmbDualWrite`. **Nije** `APP_KEY` i **nije** `JMB_ENCRYPTION_KEY`. Boot aplikacije **ne** zahtijeva ključ. Eloquent persist ne-praznog `users.jmb` / `physical_person_identities.jmb` zahtijeva ključ. Ostali JMB/JMBG modeli (ovlašćeno lice, predstavnik, application/business-plan snapshot) **ne** pišu lookup.

Ovo je samo sinhronizacija kolone. **Uniqueness/equality ostaju plaintext.** `CanonicalIdentifierUniqueness::jmbTaken()` i `users.jmb` UNIQUE se ne mijenjaju. Encrypted-first VALUE read se ne mijenja. Plaintext se ne uklanja.

| Varijabla | Default (example) | Namjena |
|-----------|-------------------|---------|
| `JMB_LOOKUP_KEY` | prazno | Aktivni 32-bajtni HMAC ključ, format `base64:...`. Ne koristiti `APP_KEY` ni `JMB_ENCRYPTION_KEY`. |
| `JMB_LOOKUP_KEY_ID` | `v1` | Operativna verzija ključa. **Nije** dio spremljenog digest-a. |

- Ne-prazan validan 13-cifreni JMB: `jmb_lookup = JmbLookupService::digest(jmb)` u istom `saving` ciklusu kao `jmb_encrypted`.
- `null` / prazan JMB: `jmb_lookup = null` i `jmb_encrypted = null`.
- Neispravan ne-prazan JMB, ili nedostajući/neispravan lookup ključ: persist se **ne** izvršava (nema djelimičnog plaintext/encrypted/lookup upisa).
- Ažuriranje nepovezanih polja **ne** regeneriše digest ako se JMB nije promijenio.
- `users.jmb_lookup` ima DB UNIQUE; kolizija je native DB greška, bez novog business validation sloja.

---

## Baza

| Varijabla | Napomena |
|-----------|----------|
| `DB_CONNECTION` | sqlite u example; MySQL na produkciji |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL parametri |

---

## Superadmin provisioning

Izvor u kodu: `config/provisioning.php` → `SuperAdminSeeder`.

| Varijabla | Obavezno | Namjena |
|-----------|----------|---------|
| `SUPERADMIN_EMAIL` | da (za seed) | Email jedinog superadmin naloga |
| `SUPERADMIN_PASSWORD` | da (za seed) | Početna lozinka; ukloniti poslije seeda |

### Poslovno pravilo

Sistem podržava **tačno jednog** superadmina. Seeder ne kreira drugi nalog ako već postoji superadmin sa drugim emailom.
Ako u budućnosti bude potrebno više superadmina, to treba uvesti eksplicitnom dozvolom (npr. `SUPERADMIN_ALLOW_MULTIPLE`) i odvojenim odobrenjem — ne tihim ponašanjem seedera.

### Ponašanje seedera

- Bez `SUPERADMIN_EMAIL` / `SUPERADMIN_PASSWORD` eksplicitni seed **završava greškom** (ne uspješnim skipom).
- Lozinka mora imati najmanje 12 karaktera.
- Novi nalog: `activation_status = active`, `email_verified_at` postavljen.
- Postojeći aktivan superadmin sa istim emailom: **ne mijenja** lozinku ni `activation_status`.
- Postojeći deaktivirani nalog: **ne reaktivira** — greška.
- Lozinka se **nikada** ne ispisuje u konzolu.

### Produkcijski tok (isključivo)

Pokretati **samo**:

```text
php artisan db:seed --class=SuperAdminSeeder
```

**Ne** pokretati `php artisan db:seed` na produkciji: `DatabaseSeeder` poziva demo seedere (`UserSeeder`, `KkAdministratorSeeder`) i **ne** uključuje `SuperAdminSeeder`.

Koraci:

1. Postaviti `SUPERADMIN_EMAIL` i `SUPERADMIN_PASSWORD` u produkcijski `.env`.
2. Ako je aktivan `config:cache`, pokrenuti `config:cache` da se nove vrijednosti učitaju.
3. Pokrenuti `php artisan db:seed --class=SuperAdminSeeder`.
4. **Ukloniti `SUPERADMIN_PASSWORD`** iz produkcijskog `.env` (po želji i email ostaviti dokumentovan interno).
5. Ponovo pokrenuti `config:cache` da lozinka ne ostane u `bootstrap/cache/config.php`.

Stvarne vrijednosti se ne commit-uju.

---

## Testno okruženje

Testovi koriste zasebnu MySQL bazu definisanu u `.env.testing` (nije u repou). Priprema:

```text
cp .env.testing.example .env.testing
php artisan key:generate --env=testing
```

Guard u `tests/TestCase.php` prekida testove ako `APP_ENV` nije `testing`, ako driver nije MySQL ili ako naziv baze ne sadrži `test`/`testing`.

---

## Session, queue, cache

| Varijabla | Default | Namjena |
|-----------|---------|---------|
| `SESSION_DRIVER` | database | |
| `QUEUE_CONNECTION` | database | `ProcessDocumentJob` |
| `CACHE_STORE` | database | |

---

## Mail

| Varijabla | Default | Namjena |
|-----------|---------|---------|
| `MAIL_MAILER` | log | Produkcija: smtp |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | | SMTP |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | | Pošiljalac |

---

## MEGA.nz (korišćeno u kodu, **nije u `.env.example`**)

| Varijabla | Default | Gdje |
|-----------|---------|------|
| `MEGA_EMAIL` | — | `config/services.php`, Node skripte |
| `MEGA_PASSWORD` | — | isto |
| `MEGA_BASE_FOLDER` | `digital.kotor` | `config/services.php` |
| `NODE_BINARY` | `node` | `DocumentController`, `ApplicationController`, `DeleteExpiredDocuments` |

**TODO:** dopuniti `.env.example` kad se odobri izmjena koda ([project-todo.md](project-todo.md)).

---

## External archive (Biblioteka dokumenata)

Izvor: `.env.example`, `config/external_archive.php`. Detalji toka: [document-library-and-mega.md](document-library-and-mega.md).

| Varijabla | Default (example) | Namjena |
|-----------|-------------------|---------|
| `EXTERNAL_ARCHIVE_PROVIDER` | `mega` | Provider arhive |
| `EXTERNAL_ARCHIVE_LIBRARY_UPLOAD` | `false` | `true` = server-side upload Biblioteke (produkcija može biti `true`) |
| `EXTERNAL_ARCHIVE_DELETE_LOCAL_AFTER_UPLOAD` | `false` | Brisanje lokalnog fajla nakon uspješne arhive — **ostaviti `false`** |

Stvarne MEGA lozinke i produkcijski `.env` se ne commit-uju.

---

## Document Library limits / smart PDF (Paket 2D)

Izvor: `.env.example`, `config/document_library.php`. Detalji: [document-library-and-mega.md](document-library-and-mega.md).

| Varijabla | Default | Namjena |
|-----------|---------|---------|
| `DOCUMENT_LIBRARY_IMAGE_MAX_KB` | `2048` | Max po slici (KB) |
| `DOCUMENT_LIBRARY_PDF_MAX_KB` | `20480` | Max po PDF-u (KB) |
| `DOCUMENT_LIBRARY_USER_QUOTA_BYTES` | `20971520` | Korisnička kvota (20 MB) |
| `DOCUMENT_LIBRARY_PDF_OPTIMIZATION_THRESHOLD_BYTES` | `3145728` | Ispod: pass-through; iznad: Imagick optimize |
| `DOCUMENT_LIBRARY_PDF_TARGET_DPI` | `200` | DPI za velike PDF-ove |
| `DOCUMENT_LIBRARY_PDF_GRAYSCALE` | `true` | Greyscale pri optimizaciji |
| `DOCUMENT_LIBRARY_PDF_JPEG_QUALITY` | `82` | JPEG quality u PDF-u |

Produkcijski `.env` se ne mijenja zbog PHP upload limita. Shared hosting (provjereno): `upload_max_filesize=2M`, `post_max_size=8M` — **Default, neizmjenjivo iz Pleska**. Aplikacioni PDF limit ostaje 20 MB; efektivno na produkciji 2 MB dok hosting provajder ne postavi ~25M/~32M (v. [deployment-and-cron.md](deployment-and-cron.md)).

---

## Vite

| Varijabla | Namjena |
|-----------|---------|
| `VITE_APP_NAME` | Frontend build |

---

## AWS (Laravel default)

U `config/services.php` / `config/filesystems.php` — trenutno **nije** primarna integracija projekta (storage je local + MEGA).

---

## Produkcija (digital.kotor.me)

Snimak iz Laravel Toolkit → Artisan → `about` (jun 2026). Puni deploy tok: [deployment-and-cron.md](deployment-and-cron.md).

| Parametar | Vrijednost na serveru |
|-----------|------------------------|
| PHP | 8.3.31 |
| Laravel | 12.29.0 |
| `APP_NAME` | Digital Kotor |
| `APP_URL` | digital.kotor.me |
| `APP_ENV` (snimak) | `local` |
| `APP_DEBUG` (snimak) | `true` (ENABLED) |
| Timezone | Europe/Belgrade |
| DB | mysql |
| Session / Queue / Cache | database |
| Mail | smtp |
| Node (Toolkit) | 23.11.1 |
| Composer (Toolkit) | 2.10.1 |

**Lokalni razvoj** koristi `.env.example` kao polaznu tačku (često sqlite); produkcija ima zaseban `.env` na Plesku — **ne commitovati**.

**Operativa:** Artisan / Composer / Node na serveru samo kroz **Laravel Toolkit** (polja za komande), ne direktan shell.

---
