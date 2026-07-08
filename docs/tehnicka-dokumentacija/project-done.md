# Project done — digital.kotor.me

**Poslednje ažuriranje:** 2026-07-08

Kratka historija značajnih završetaka. Detalji u tematskim `.md` fajlovima ili git istoriji.

---

## 2026-07-08 — Kalendar kulture: vidljivost input polja

- U formi `Kalendar kulture → Novi događaj` i `Uredi događaj` dodati diskretni stilovi za polja (`border`, blagi `bg`, focus ring) da inputi budu jasno vidljivi korisniku.
- U kategorije događaja dodata stavka `Nešto drugo`.
- Polje vremena preimenovano u `Sa početkom u:` i dodato opciono polje `Do:` uz validaciju da je početak prije završetka.
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
