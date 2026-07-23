# Handoff — novi chat / novi agent

**Poslednje ažuriranje:** 2026-07-22

Kratko uputstvo da nastaviš rad na **digital.kotor.me** bez čitanja cijelog repoa.

---

## 1. Šta je ovo

Laravel 12 (PHP 8.2+) portal Opštine Kotor. **Konceptualni model** (v. [architecture-overview.md](architecture-overview.md#konceptualni-model-projekta-važeće)):

1. **Platforma** — login/registracija, profil, **biblioteka dokumenata** (MEGA); dokumenti se koriste u cjelinama
2. **Cjelina: Kalendar kulturnih događaja** — javni pregled + admin (`kk_admin`)
3. **Cjelina: Podrška ženskom preduzetništvu** — modul konkursa (`type=zensko`): prijave, evaluacija, ugovori

**Nove cjeline** na platformu dodaju se samo uz eksplicitno obavještenje — ne pretpostavljati ih u radu.

**Stubovi (vidljivi u UI, nisu gotovi):** plaćanja, tenderi, obavještenja — namjerno ostaju da se vidi da se radi na projektu.

**Prioritet cjelina:** mladi konkurs (`omladinsko`) → plaćanja → tenderi. Kalendar i žensko preduzetništvo su završeni.

**Odluka o konkursu:** zvaničan izvor je katalog propisa / Službeni list; nema pravila mimo Odluke.

---

## 2. Gdje početi

1. [project-conventions.md](project-conventions.md) — pravila dokumentacije
2. [project-status-next-steps.md](project-status-next-steps.md) — indeks svih tema
3. Tematski fajl za modul koji mijenjaš (npr. `application-lifecycle.md`)

**Kod je presudan** kad doc i kod nisu usklađeni — provjeri `routes/web.php`, odgovarajući kontroler i model.

---

## 3. Ključne putanje u kodu

| Oblast | Putanja |
|--------|---------|
| Rute | `routes/web.php`, `routes/auth.php` |
| Middleware | `app/Http/Middleware/RoleMiddleware.php`, `RestrictRoleModuleAccess.php` |
| Prijave | `app/Http/Controllers/ApplicationController.php`, `app/Models/Application.php` |
| Biznis plan | `app/Http/Controllers/BusinessPlanController.php` |
| Dokumenti / MEGA | `app/Http/Controllers/DocumentController.php`, `app/Services/DocumentProcessor.php` |
| Konkursi | `app/Http/Controllers/CompetitionsController.php`, `AdminController.php` |
| Adresa Kotor | `app/Support/KotorAddress.php`, `app/Rules/KotorMunicipalityAddress.php` |
| Kalendar | `app/Http/Controllers/CulturalCalendarController.php` |
| Seed uloga | `database/seeders/RoleSeeder.php` |

---

## 4. Uloge (kratko)

`superadmin` → sve · `admin` → admin panel · `konkurs_admin` → samo konkursi · `komisija` → evaluacija · `kk_admin` → kalendar · `korisnik` → podnosilac.

Detalji: [roles-and-permissions.md](roles-and-permissions.md).

---

## 5. Pravila pri izmjenama

- **Ne mijenjaj kod** ako korisnik eksplicitno traži samo dokumentaciju.
- Poslije izmjene koda — ažuriraj odgovarajući `.md` u `docs/tehnicka-dokumentacija/`.
- Ne commituj bez zahtjeva korisnika.
- `.env` tajne ne idu u git.

---

## 6. Deploy / cron

**Tok:** lokalno → GitHub (`main`) → Plesk deploy (ti ili kolega, po potrebi). **Laravel Toolkit** na serveru (nema SSH). **Queue:** `queue-worker.php` u Scheduled Tasks. **PDF:** aplikacija do 20 MB; shared hosting trenutno efektivno **2 MB** PHP Default — v. [deployment-and-cron.md](deployment-and-cron.md). Dijagnostika: `pdf:check`, `document:fingerprint-check` (Toolkit Artisan).

**Objava cjeline:** zadovoljstvo tima → link/polje na **Dashboard-u** (+ po slučaju admin status).

Detalji: [project-operations.md](project-operations.md), [deployment-and-cron.md](deployment-and-cron.md).

---

## 7. Otvoreno

[project-todo.md](project-todo.md)
