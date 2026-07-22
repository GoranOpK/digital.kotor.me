# Deploy, cron i Artisan komande

**Poslednje ažuriranje:** 2026-07-22
**Izvor u kodu:** root PHP skripte, `routes/console.php`, `app/Console/Commands/`, `queue-worker.php`

Detaljni Plesk vodiči: [PLESK_FINAL_INSTRUCTIONS.md](PLESK_FINAL_INSTRUCTIONS.md), [PLESK_DELETE_EXPIRED_CRON.md](PLESK_DELETE_EXPIRED_CRON.md).
Biblioteka dokumenata / queue tok: [document-library-and-mega.md](document-library-and-mega.md).

---

## Tok razvoja i deploya (važeće)

**Model:** razvoj na **lokalnim računarima** (više računara u timu) → **GitHub** (`main`) → **Plesk pull/deploy** na `digital.kotor.me`. Na serveru **nema** zasebnog staging domena; jedna instalacija služi korisnicima, a cjeline u izradi ostaju skrivene dok se ne objave.

```
Lokalni računari — razvoj i testiranje
        ↓  git status → razriješiti → push
GitHub — grana **main**
        ↓  Plesk Git pull/deploy (kad tim odluči)
digital.kotor.me
        ├── vidljivo korisnicima: platforma + objavljene cjeline
        └── na serveru ali skriveno: cjeline u izradi (bez linka na dashboardu)
```

| Faza | Gdje | Napomena |
|------|------|----------|
| Razvoj | **Lokalni računari** | Praksa isključivo: računar → GitHub → server |
| Verzionisanje | GitHub (`main`) | Prije pusha: provjera `git status` |
| Deploy | Plesk Git | Kolega ili vlasnik, **kad se ukaže potreba** |
| Objava cjeline | Server + UI | Dashboard: polje/ikonica/link; v. ispod |
| Operativa na serveru | Plesk + **Laravel Toolkit** | Artisan, Composer, Node, cron — v. ispod |

Detalji tima, grana, jezika: [project-operations.md](project-operations.md).

**Važno za AI agente i developere:**

- Na serveru **ne pretpostavljati** direktan SSH — koristiti **Laravel Toolkit**.
- Na produkciju ide **samo `main`**; feature branch-eve ne dirati bez dogovora tima.
- Cjeline u izradi **nisu** za krajnje korisnike dok tim ne objavi (obično link na dashboardu).

### Objavljivanje cjeline (razvoj → produkcija za korisnike)

| Stanje | Ko vidi | Pravilo |
|--------|---------|---------|
| **Platforma** + **objavljena cjelina** | Korisnici | Normalan rad portala |
| **Cjelina u izradi** (kod na serveru, nije objavljena) | Ne korisnici | Nema pristupa preko dashboarda / glavnog UI toka |
| **Objava** (odluka tima) | Korisnici | **Dashboard:** novo polje, ikonica ili link na cjelinu; po potrebi i admin status (npr. `published`) |

**Primjeri u kodu (dopuna, ne zamjena za dashboard objavu):**

| Cjelina | Dodatno u kodu |
|---------|----------------|
| Konkurs (žensko preduzetništvo) | `Competition.status`: `draft` → `published`; javna lista: `published` + `type=zensko` |
| Događaj (kalendar) | `CulturalEvent.status`: `draft` → `published` |
| Nova cjelina | Eksplicitna najava tima + dashboard link + mehanizam po dogovoru |

Detalji: [application-lifecycle.md](application-lifecycle.md), [project-operations.md](project-operations.md#objava-završene-cjeline).

---

## Plesk i Laravel Toolkit

Hosting je pod **Pleskom** — postoje ograničenja u odnosu na tipičan VPS sa punim shell pristupom.

### Šta je dostupno u Laravel Toolkit-u

| Modul | Namjena |
|-------|---------|
| **Dashboard** | Pregled Laravel projekta na domenu |
| **Artisan** | Polje za unos Artisan komandi (npr. `migrate --force`) |
| **Composer** | Polje za Composer komande (npr. `install --no-dev`) |
| **Node.js** | Polje za npm/Node komande (npr. `ci`, `run build`) |
| **Scheduled Tasks** | Zakazani zadaci (cron) |
| **Queue** | Upravljanje queue worker-om (`QUEUE_CONNECTION=database`) |

Artisan, Composer i Node.js imaju **tekstualno polje** u koje se upisuje komanda (bez prefiksa `php artisan` u Artisan polju — samo argumenti komande, npr. `about`, `migrate --force`).

### Tipičan deploy nakon pull-a sa GitHub-a

Redosled (u Toolkit poljima, ne u SSH):

1. **Composer:** `install --no-dev --optimize-autoloader`
2. **Node.js:** `ci` zatim `run build` (ako su mijenjani frontend assets)
3. **Artisan:** `migrate --force` (ako ima novih migracija)
4. **Artisan (opciono):** `config:cache`, `route:cache`, `view:cache` — samo ako je usklađeno sa načinom deploya; trenutno na serveru cache često **nije** uključen (v. `php artisan about` ispod)
5. Provjera: **Artisan:** `about`

Migracije i dijagnostika **ne pokretati** lokalno u ime produkcije — samo lokalno za dev ili na serveru kroz Toolkit kad vlasnik projekta to traži.

---

## Produkcijsko okruženje (snimak sa servera)

Izvor: Laravel Toolkit → Artisan → `about` (stanje na `digital.kotor.me`, jun 2026).

| Stavka | Vrijednost |
|--------|------------|
| Application Name | Digital Kotor |
| Laravel | 12.29.0 |
| PHP | 8.3.31 |
| Composer (Toolkit) | 2.10.1 |
| Node.js | 23.11.1 (+ npm) |
| APP_URL | digital.kotor.me |
| Timezone | Europe/Belgrade |
| Database | mysql |
| Cache / Session / Queue | database |
| Mail | smtp |
| `public/storage` | LINKED |
| Views | CACHED |
| Config / Routes / Events cache | NOT CACHED |

**Napomena (operativno):** u snimku je `Environment: local` i `Debug Mode: ENABLED`. To je zabilježeno stanje sa servera; promjena `.env` na produkciji zahtijeva dogovor i deploy kroz uobičajeni Plesk/Git tok — ne mijenjati bez eksplicitnog zahtjeva.

Detalji env varijabli: [environment-variables.md](environment-variables.md#produkcija-digitalkotorme).

---

## Hosting model

- **Plesk** na produkciji (`digital.kotor.me`)
- Document root: `public/`
- Cron često preko **„Run a PHP script“**, ne `php artisan schedule:run` za sve zadatke

Komentar u `routes/console.php`:

> Schedule nije korišćen za delete-expired jer Plesk Scheduled Tasks ne pokreće pouzdano `schedule:run`.

**Provjera:** document root treba biti `public/`, da root skripte (npr. `queue-worker.php`) ne budu javno dostupne kao obične URL stranice.

---

## Backup (Plesk)

- **Dnevno**, automatski: backup **baze** i **cijelog sajta**
- Tim ne pokreće ručno — Plesk konfiguracija

---

## Zakazani zadaci na produkciji (važeće)

Izvor: Plesk **Scheduled Tasks** + Laravel Toolkit (jun 2026; worker args ažurirani jul 2026 — `b3972de`).

### Laravel Toolkit → Scheduled Tasks (Artisan)

| Komanda | Interval (cron) | Napomena |
|---------|-----------------|----------|
| `cultural-calendar:send-weekly-newsletter` | `0 9 * * 1` | Ponedjeljak 09:00 |

### Plesk Scheduled Tasks (PHP skripte / ostalo)

| Zadatak | Namjena |
|---------|---------|
| `digital.kotor.me/cleanup-temp-downloads.php` | Temp fajlovi MEGA downloada |
| `digital.kotor.me/queue-worker.php` | Queue worker — v. sekciju ispod |
| `digital.kotor.me/delete-expired-documents.php` | Istekli dokumenti (`documents:delete-expired`) |
| `digital.kotor.me/find-node-path.php` | Dijagnostika/putanja (na serveru; možda nije u repou) |
| `php artisan competitions:send-candidates-list` | Email lista kandidata |

**Queue connection:** `QUEUE_CONNECTION=database`.

---

## Queue worker (`queue-worker.php`)

Plesk Scheduled Task pokreće skriptu **jednom u minuti**. Ovo je **praktično rješenje za Plesk**, ne stalni Supervisor/systemd daemon. Dugoročno se Supervisor može razmotriti ako hosting to omogući — **nije** trenutna produkcijska konfiguracija.

### Argumenti (važeće)

```text
php artisan queue:work
  --sleep=1
  --tries=3
  --timeout=300
  --max-time=55
```

- `--stop-when-empty` se **više ne koristi** (worker više ne izlazi odmah kad je red prazan)
- Worker ostaje aktivan do ~55 s; bez poslova provjerava red približno svake sekunde (`--sleep=1`)
- Nakon `max-time` uredno se gasi; sljedeći cron ciklus ga ponovo pokreće

### Lock (sprečavanje preklapanja)

| Stavka | Vrijednost |
|--------|------------|
| Lock fajl | `storage/framework/queue-worker.lock` |
| Mehanizam | `flock(LOCK_EX \| LOCK_NB)` |
| Druga instanca (lock zauzet) | mirno `exit 0` |
| Greška otvaranja lock fajla / lock dir | `exit 1` |
| Oslobađanje | u `finally` |

Samo jedna instanca smije obrađivati red.

---

## Root PHP skripte (u repou)

| Fajl | Poziva | Napomena |
|------|--------|----------|
| `delete-expired-documents.php` | `documents:delete-expired` | U Plesk Scheduled Tasks |
| `cleanup-temp-downloads.php` | `documents:cleanup-temp-downloads --minutes=5` | U Plesk Scheduled Tasks |
| `queue-worker.php` | `queue:work --sleep=1 --tries=3 --timeout=300 --max-time=55` | U Plesk Scheduled Tasks + flock |
| `recalculate-storage.php` | `storage:recalculate` | Po potrebi |

---

## Laravel scheduler (u kodu)

U `routes/console.php` definisan je `cultural-calendar:send-weekly-newsletter` (ponedjeljak 09:00). Na produkciji se izvršava kroz **Toolkit Scheduled Tasks** (v. tabela iznad), ne nužno kroz `php artisan schedule:run`.

---

## Artisan komande (`app/Console/Commands/`)

| Komanda | Namjena |
|---------|---------|
| `documents:delete-expired` | Briše istekle `UserDocument` (lokalno + MEGA) |
| `documents:cleanup-temp-downloads` | Temp fajlovi MEGA downloada |
| `documents:cleanup-orphaned` | Orphan fajlovi |
| `storage:recalculate` | Rekalkulacija kvote korisnika |
| `cultural-calendar:send-weekly-newsletter` | Sedmični newsletter |
| `competitions:send-candidates-list` | Email lista kandidata |
| `upload:check-settings` | Dijagnostika uploada |
| `imagemagick:check` | Provjera ImageMagick |
| `path:show` | Ispis `base_path()` |

---

## npm skripte

| Skripta | Namjena |
|---------|---------|
| `npm run build` | Produkcijski frontend assets |
| `npm run dev` | Vite dev server |
| `npm run delete-expired-mega` | Standalone MEGA delete (`scripts/delete-expired-mega.js`) |

---

## Node.js zahtjev

MEGA upload/delete zahtijeva `node` u PATH-u ili `NODE_BINARY` u `.env`. Cron okruženje mora imati pristup Node-u.

---

## Deploy checklist (opšti)

V. [PRE_DEPLOY_CHECKLIST_MEGA_DELETE.md](PRE_DEPLOY_CHECKLIST_MEGA_DELETE.md).

Sažetak (na **produkciji** kroz Laravel Toolkit, nakon Plesk Git deploy-a):

1. Composer: `install --no-dev --optimize-autoloader`
2. Node.js: `ci` → `run build`
3. Artisan: `migrate --force`
4. Artisan: `about` (provjera)
5. Scheduled Tasks / root PHP skripte za cron (v. ispod)
6. Provjeriti `MEGA_*` i mail u `.env` na serveru (ne u git-u)

---

## Deploy checklist — Biblioteka / Paket 2C (`b3972de` i povezano)

1. Push commitova na `origin/main`
2. Plesk Git deploy
3. Provjera `.env` (ne commitovati):
   - `EXTERNAL_ARCHIVE_PROVIDER=mega`
   - `EXTERNAL_ARCHIVE_LIBRARY_UPLOAD=true`
   - `EXTERNAL_ARCHIVE_DELETE_LOCAL_AFTER_UPLOAD=false`
4. Artisan:
   - `optimize:clear`
   - `view:clear`
   - `view:cache`
5. Potvrditi da Scheduled Task i dalje poziva isti `queue-worker.php`
6. Test single-file upload
7. Test multi-file upload
8. Statusi: `pending` → `processing` → `processed`
9. Potvrditi da worker reaguje unutar ~1 s–55 s prozora (ne čeka puni minut na prazan red)
10. Provjera iskorišćenog prostora u UI
11. Provjera dugmeta Preuzmi
12. Provjera tabela / logova: `jobs`, `failed_jobs`, `external_file_archives`, `storage/logs/laravel.log`

### Rollback

- Kod: vratiti prethodni commit na `main` + Plesk deploy
- Feature: privremeno `EXTERNAL_ARCHIVE_LIBRARY_UPLOAD=false`, zatim `php artisan optimize:clear`
- **Ne** uključivati `EXTERNAL_ARCHIVE_DELETE_LOCAL_AFTER_UPLOAD=true` u ovom deployu

---

## Povezani dokumenti

- [document-library-and-mega.md](document-library-and-mega.md)
- [environment-variables.md](environment-variables.md)
- [project-operations.md](project-operations.md)
