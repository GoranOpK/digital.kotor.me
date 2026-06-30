# Project TODO — digital.kotor.me

**Poslednje ažuriranje:** 2026-06-30

Otvoreni zadaci (dokumentacija i proizvod). Završeno → [project-done.md](project-done.md).

---

## Dokumentacija

- [ ] Ažurirati `UPUTSTVO_ZENSKO_PREDUZETNISTVO.md` ako se promijeni tok prijave ili polja u formama
- [ ] Dodati admin/komisija korisnička uputstva (trenutno samo podnosioci)
- [ ] **Zvanična Odluka o konkursu / pravilnici (PDF)** — unijeti u docs kad kolega dostavi izvor
- [ ] Arhitekturni dijagram (mermaid) u `architecture-overview.md` po potrebi
- [ ] Formalni security doc (MEGA kredencijali u browser flow-u) — sažetak u `document-library-and-mega.md`, treba proširiti

## Operativa / infrastruktura

- [ ] **Rezervni plan za MEGA i mail** — procedura ako servis padne (vlasnik: Opština Kotor; admini imaju pristup; plan još nije definisan)
- [ ] Provjeriti da PDF postoji na produkciji: `public/pdf/uputstvo-zensko-preduzetnistvo.pdf`
- [ ] Konsolidovati višestruke `PLESK_FIND_PATH*.md` u jedan vodič (opciono)
- [ ] Pregled `APP_ENV` / `APP_DEBUG` na produkciji (trenutno snimak: local + debug ON) — samo uz eksplicitni zahtjev za izmjenu
- [ ] **Prioritet budućih cjelina** (plaćanja, tenderi, konkursi…) — definisati redoslijed kad tim odluči
- [ ] **Feature branch strategija** — uskladiti sa kolegom; do tada: samo `main` na server, ne mijenjati branch-eve u repou bez dogovora

## Kod / tehnički dug (zahtijeva dozvolu prije izmjene koda)

- [ ] **Duplikat auth ruta:** `routes/web.php` i `routes/auth.php` (Breeze) registruju `login`/`register` — provjeriti koja verzija je aktivna i ukloniti duplikat
- [ ] **Uloga `evaluator`:** rute u `web.php` (`role:evaluator`) ali nije u `RoleSeeder` — ukloniti ili dodati ulogu
- [ ] **`.env.example`:** nedostaju `MEGA_EMAIL`, `MEGA_PASSWORD`, `MEGA_BASE_FOLDER`, `NODE_BINARY` (dokumentovano u `environment-variables.md`)
- [ ] Modul **plaćanja** — implementacija ili uklanjanje stub ruta
- [ ] Modul **tenderi** — implementacija ili uklanjanje stub ruta
- [ ] Modul **obavještenja** — `NotificationController` prazan

## Testiranje

- [ ] Proširiti automatizovane testove (trenutno minimalno PHPUnit pokrivanje poslovnih pravila)
