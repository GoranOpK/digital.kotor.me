# Environment varijable

**Poslednje ažuriranje:** 2026-06-30  
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
