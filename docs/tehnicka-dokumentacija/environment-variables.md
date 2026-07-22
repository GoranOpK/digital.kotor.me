# Environment varijable

**Posljednje ažuriranje:** 2026-07-22
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
