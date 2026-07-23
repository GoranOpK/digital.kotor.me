# Project done — digital.kotor.me

**Poslednje ažuriranje:** 2026-07-23

Kratka historija značajnih završetaka. Detalji u tematskim `.md` fajlovima ili git istoriji.

---

## 2026-07-23 — Zaštita od duplikata + fingerprint dijagnostika

- Backend: binarni SHA-256 + (za ≥2 slike) normalizovani Imagick pixel fingerprint (`DocumentImageFingerprint`)
- Frontend: `name` + `size` + `lastModified` (UX)
- Artisan: `document:fingerprint-check` / `--compare` (Toolkit, bez SSH)
- Produkcijska verifikacija: PHP 8.3, Imagick, peak ~26 MB; PASS (multi-frame UNSUPPORTED očekivano)
- Commiti: `22e1116` (binarni duplikati), `ad85fce` (dijagnostika); v. [document-library-and-mega.md](document-library-and-mega.md)

## 2026-07-22 — Biblioteka dokumenata (Paket 2D — Smart PDF)

- Limiti: slike 2 MB; PDF **aplikaciono** 20 MB; kvota 20 MB
- Shared hosting: efektivni PHP upload trenutno **2M** (Default) — 20 MB proradi nakon povećanja limita kod provajdera
- `PdfOptimizer` (PHP Imagick): mali PDF pass-through; veliki PDF ~200 DPI grayscale; ako veći → original
- Config: `config/document_library.php` + `.env.example` (`DOCUMENT_LIBRARY_*`)
- Produkcijski `pdf:check` READY; CLI ImageMagick nije potreban za novi PDF tok
- Produkcijski `.env` / DELETE_LOCAL nedirani u ovom paketu
- Dokumentacija: [document-library-and-mega.md](document-library-and-mega.md), [environment-variables.md](environment-variables.md), [deployment-and-cron.md](deployment-and-cron.md)

## 2026-07-22 — Biblioteka dokumenata (Paket 2C)

Commit (lokalno; u trenutku pisanja bez push/deploy): `b3972de` — `fix: improve document upload processing and storage limits`

- Queue worker: `--sleep=1 --tries=3 --timeout=300 --max-time=55`, bez `--stop-when-empty`; flock lock u `storage/framework/queue-worker.lock`
- UX: „Dokument je uspješno otpremljen. Obrada je u toku.“ / „Dokument je uspješno sačuvan.“
- Kvota: izvor `user_documents.file_size`; cloud dokumenti više se ne preskaču; archive tabela se ne sabira
- Limiti: 2 MB po ulaznom fajlu; finalni merge smije > 2 MB; cleanup pri prekoračenju kvote
- Dokumentacija: [document-library-and-mega.md](document-library-and-mega.md), [deployment-and-cron.md](deployment-and-cron.md), [environment-variables.md](environment-variables.md)

## 2026-07-08 — Kalendar kulture: vidljivost input polja

- U formi `Kalendar kulture → Novi događaj` i `Uredi događaj` dodati diskretni stilovi za polja (`border`, blagi `bg`, focus ring) da inputi budu jasno vidljivi korisniku.
- U kategorije događaja dodata stavka `Nešto drugo`.
- Polje vremena preimenovano u `Sa početkom u:` i dodato opciono polje `Do:` uz validaciju da je početak prije završetka.
- U Kalendaru kulture kartice događaja u **Pregledu**, **Arhivi**, **Istaknutim događajima** i **Narednim događajima** otvaraju novu stranicu detalja (`cultural-calendar.show`).
- U detalju događaja dugme `Nazad` vraća korisnika na prethodnu sekciju (`back` parametar), umjesto uvijek na pregled.
- Newsletter: status poruke pomjerene na vrh stranice; e-mail polje učinjeno čitljivim; welcome mail se više ne šalje pri ponovnoj prijavi već aktivne adrese.
- Newsletter e-mail polje više se ne popunjava automatski prijavljenom adresom nakon osvježavanja stranice.
- Dodata checklista testiranja prije objave: `cultural-calendar-test-checklist.md`.
- Ažurirani indeksi dokumentacije (`README.md`, `project-status-next-steps.md`, `cultural-calendar.md`) sa referencom na checklistu.

## 2026-06-30 — Odgovori tima (dokumentacija)

- Zvaničan izvor Odluke: **katalog propisa** / **Službeni list**
- PDF uputstvo: potvrđena putanja `public/pdf/uputstvo-zensko-preduzetnistvo.pdf`
- Prioritet cjelina: mladi konkurs → plaćanja → tenderi
- `APP_ENV` / `APP_DEBUG` na produkciji prilagođeno
- `evaluator` / stare rute — zadržano do kraja tekućeg konkursa
- Stub moduli — namjerno vidljivi u UI

## 2026-06-30 — Dokumentacioni sistem

- Kreiran `docs/tehnicka-dokumentacija/project-conventions.md` sa pravilima održavanja dokumentacije.
- Kreirani meta-fajlovi u `docs/tehnicka-dokumentacija/`: `project-status-next-steps.md`, `project-todo.md`, `project-done.md`, `handoff-new-chat.md`.
- Kreirana tematska dokumentacija u istom folderu (arhitektura, moduli, uloge, lifecycle prijave, MEGA, kalendar, baza, env, deploy, stubovi).
- Meta-fajlovi i README indeks konsolidovani u `docs/tehnicka-dokumentacija/README.md`.
- Meta-fajlovi (`project-*.md`, `handoff-new-chat.md`) premješteni iz `docs/` u `docs/tehnicka-dokumentacija/`.
- Obrisan suvišan `docs/README.md` — ulaz je root `README.md` + `docs/tehnicka-dokumentacija/README.md`.
- U dokumentaciju unesen konceptualni model: platforma + cjeline (kalendar, žensko preduzetništvo); pravilo za buduće cjeline.
- Dokumentovan tok razvoja (lokalno → GitHub → Plesk deploy) i ograničenja Laravel Toolkit-a na produkciji.
- Pojašnjen praktični model: razvoj na lokalnim računarima → GitHub `main` → Plesk; objava cjeline preko dashboard linka.
- Kreiran [project-operations.md](project-operations.md) — tim, Git, jezik, MEGA/mail, backup, scheduled tasks.
- Ranije: premještena operativna dokumentacija iz root-a u `docs/tehnicka-dokumentacija/`.
- Ranije: korisničko uputstvo `UPUTSTVO_ZENSKO_PREDUZETNISTVO.md`, PDF dugme na stranici konkursa (`competitions.guide.pdf`).

## Ranije (v. git / tematski fajlovi)

- MEGA browser upload integracija — v. `MEGA_BROWSER_UPLOAD_SETUP.md`, `MEGAJS_SETUP_COMPLETE.md`
- Adresa + grad (Kotor validacija), PIB 9 cifara — v. `business-rules.md`
- Finansijski prikaz na statusu prijave (biznis plan / budžet konkursa)
