# Project TODO — digital.kotor.me

**Poslednje ažuriranje:** 2026-07-22

Otvoreni zadaci (dokumentacija i proizvod). Završeno → [project-done.md](project-done.md).

**Prioritet cjelina (tim):** mladi konkurs → plaćanja → tenderi. V. [project-operations.md](project-operations.md#prioritet-budućih-cjelina).

---

## Produkt — sljedeće (prioritet)

- [ ] **Konkurs za mlade u preduzetništvu** (`type=omladinsko`) — aktivacija u UI i poslovna logika po Odluci
- [ ] **Online plaćanja** — implementacija nakon mladih konkursa
- [ ] **Tenderi** — implementacija nakon plaćanja

## Dokumentacija

- [ ] Ažurirati `UPUTSTVO_ZENSKO_PREDUZETNISTVO.md` ako se promijeni tok prijave ili polja u formama
- [ ] **Uputstva za Predsjednika komisije i ostale članove** — predloženo; manje razlike u odnosu na podnosioca; sve po Odluci
- [ ] Arhitekturni dijagram (mermaid) u `architecture-overview.md` po potrebi
- [ ] Formalni security doc (MEGA kredencijali u browser flow-u) — sažetak u `document-library-and-mega.md`, treba proširiti po potrebi

## Operativa / infrastruktura

- [ ] **Feature branch strategija** — uskladiti sa **drugim administratorom**; tim želi bolju organizaciju grana (projekat će biti velik; samo `main` nije dugoročno održivo)
- [ ] Konsolidovati višestruke `PLESK_FIND_PATH*.md` u jedan vodič (opciono)
- [ ] Po potrebi formalizovati **rezervni plan MEGA/mail** (trenutno: konsultacija u timu, u najgorem slučaju ručno — v. [project-operations.md](project-operations.md))
- [ ] Razmotriti **Supervisor/systemd** za queue worker ako Plesk/hosting to omogući (trenutno: cron + `queue-worker.php` 55 s — v. [deployment-and-cron.md](deployment-and-cron.md))

## Kod / tehnički dug (zahtijeva dozvolu prije izmjene koda)

- [ ] **Uloga `evaluator` i rute `/evaluations`** — namjerno zadržano do kraja tekućeg konkursa; očistiti **nakon** završetka konkursa
- [ ] **Duplikat auth ruta:** `routes/web.php` i `routes/auth.php` (Breeze) registruju `login`/`register` — provjeriti koja verzija je aktivna i ukloniti duplikat
- [ ] **`.env.example`:** nedostaju `MEGA_EMAIL`, `MEGA_PASSWORD`, `MEGA_BASE_FOLDER`, `NODE_BINARY` (dokumentovano u `environment-variables.md`)
- [ ] Modul **obavještenja** — `NotificationController` prazan (stub ostaje vidljiv u UI)
- [ ] **Backfill `file_size`** za stare cloud/MEGA dokumente sa `null`/`0` (kvota Biblioteke — v. [document-library-and-mega.md](document-library-and-mega.md))
- [ ] **Paralelni upload race** na korisničku kvotu (nije riješen u `b3972de`)
- [ ] **`post_max_size=8M`** na produkciji ograničava jedan HTTP upload ispod poslovne kvote 20 MB (hosting/PHP, ne aplikacioni commit)
- [ ] Provjeriti da je **document root** `public/` (zaštita root skripti npr. `queue-worker.php` — v. [deployment-and-cron.md](deployment-and-cron.md))
- [ ] **Paket 2D (PDF optimizacija)** — **blokiran** dok produkcijski `pdf:check` (Laravel Toolkit → Artisan) ne vrati `READY FOR PDF OPTIMIZATION` (v. [document-library-and-mega.md](document-library-and-mega.md), [deployment-and-cron.md](deployment-and-cron.md))

## Testiranje

- [ ] Proširiti automatizovane testove (trenutno minimalno PHPUnit pokrivanje poslovnih pravila)

---

## Riješeno / zatvoreno (ne raditi ponovo)

- ~~Zvanična Odluka~~ — izvor: katalog propisa / Službeni list
- ~~PDF uputstvo~~ — `public/pdf/uputstvo-zensko-preduzetnistvo.pdf` potvrđeno
- ~~Prioritet cjelina~~ — dokumentovano u `project-operations.md`
- ~~Stub moduli u UI~~ — namjerno ostaju vidljivi
- ~~`APP_ENV` / `APP_DEBUG` na produkciji~~ — prilagođeno na serveru (2026-06-30)
