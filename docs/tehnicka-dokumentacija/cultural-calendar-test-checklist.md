# Kalendar kulture — checklista za testiranje prije objave

**Poslednje ažuriranje:** 2026-07-03  
**Namjena:** ručno testiranje prije objave cjeline korisnicima. Za tehnički pregled modula v. [cultural-calendar.md](cultural-calendar.md).

**Okruženje:** `https://digital.kotor.me` (ili lokalno sa istim rutama).

**Tester:** _________________ · **Datum:** _________________ · **Okruženje:** produkcija / lokalno

---

## 1. Pristup — URL i nalozi

### Korisnički pregled (prijava + potvrđen email obavezni)

| Stranica | URL |
|----------|-----|
| Početna kalendara | `/kalendar-kulture` |
| Lista događaja | `/kalendar-kulture/pregled-dogadjaja` |
| Arhiva | `/kalendar-kulture/arhiva-dogadjaja` |
| Jedan dan | `/kalendar-kulture/dan/YYYY-MM-DD` |

**Ulaz za građanina:** prijava → početna (landing) → kategorija **Kalendar kulture**, ili direktan URL.

**Napomena:** na `/dashboard` trenutno **nema** linka na kalendar — samo landing. Prije objave provjeriti da li tim želi karticu na dashboardu (v. [project-operations.md](project-operations.md#objava-završene-cjeline)).

### Admin događaja (`kk_admin` ili `superadmin`)

| Stranica | URL |
|----------|-----|
| Lista događaja | `/kalendar-kulture/dogadjaji` |
| Novi događaj | `/kalendar-kulture/dogadjaji/create` |

**Navigacija za `kk_admin`:** Kalendar kulture · Događaji.

**Test nalog** (ako postoji na okruženju — `KkAdministratorSeeder`):

| Polje | Vrijednost |
|-------|------------|
| Email | `manifestacije@kotor.me` |
| Lozinka | provjeriti na produkciji (seeder default: `Kotor123`) |

**Dva profila za test:** običan korisnik (`korisnik`) + `kk_admin` ili `superadmin` (dva browsera / incognito).

---

## 2. Checklista — admin (CRUD)

| # | Test | OK | Napomena |
|---|------|:--:|----------|
| A1 | Kreiranje događaja — naslov, opis, kategorija, lokacija, vrijeme | ☐ | |
| A2 | Status **`draft`** — ne vidi se običnom korisniku | ☐ | |
| A3 | Status **`published`** — vidi se u kalendaru i listama | ☐ | |
| A4 | Jednodnevni događaj (`datum_od` samo) | ☐ | |
| A5 | Višednevni događaj (`datum_od` + `datum_do`) | ☐ | |
| A6 | Upload slike — prikazuje se na kartici | ☐ | |
| A7 | Bez slike — default `img/kalendar-kulture-default-event.png` | ☐ | |
| A8 | Edit — zamjena slike | ☐ | |
| A9 | Edit — uklanjanje slike (`remove_image`) | ☐ | |
| A10 | **Istaknuto** — 3 aktivna `published` istaknuta OK | ☐ | |
| A11 | **Istaknuto** — 4. aktivno istaknuto vraća grešku (limit 3) | ☐ | |
| A12 | Brisanje događaja | ☐ | |
| A13 | Klik na dan u kalendaru kao `kk_admin` → create sa datumom | ☐ | |
| A14 | Validacija — datum max +1 godina od danas | ☐ | |
| A15 | Validacija — `datum_do` ≥ `datum_od` | ☐ | |

---

## 3. Checklista — korisnički pregled

| # | Test | OK | Napomena |
|---|------|:--:|----------|
| K1 | Početna: brojači (danas / sedmica / mjesec) | ☐ | |
| K2 | Istaknuti događaji (max 3 na početnoj) | ☐ | |
| K3 | Nadolazeći događaji | ☐ | |
| K4 | Navigacija mjesecima (trenutni → +1 godina) | ☐ | |
| K5 | Dani sa događajem — klikabilni | ☐ | |
| K6 | Dani bez događaja — nisu klikabilni (običan korisnik) | ☐ | |
| K7 | Klik na dan sa događajem — lista ispod kalendara | ☐ | |
| K8 | **Pregled događaja** — samo budući `published` | ☐ | |
| K9 | **Arhiva** — prošli `published` događaji | ☐ | |
| K10 | Pregled po danu `/dan/{datum}` | ☐ | |
| K11 | `draft` / `archived` / `cancelled` — **ne** u javnom prikazu | ☐ | |
| K12 | Višednevni događaj — tačan broj na svim danima u mjesecu | ☐ | |
| K13 | Mobilni prikaz / uski ekran čitljiv | ☐ | |

---

## 4. Checklista — newsletter

| # | Test | OK | Napomena |
|---|------|:--:|----------|
| N1 | Prijava emailom sa početne kalendara | ☐ | |
| N2 | Welcome mail stiže | ☐ | |
| N3 | Ponovna prijava istog emaila — jasna poruka | ☐ | |
| N4 | Odjava (checkbox unsubscribe) | ☐ | |
| N5 | Odjava nepostojećeg emaila — poruka bez greške | ☐ | |
| N6 | Sedmični mail (Toolkit Artisan): `--dry-run` prikazuje primaoce | ☐ | |
| N7 | Sedmični mail — stvarno slanje testirano (opciono) | ☐ | |

**Komanda:** `cultural-calendar:send-weekly-newsletter`  
**Scheduler:** ponedjeljak 09:00, `Europe/Podgorica` — v. [deployment-and-cron.md](deployment-and-cron.md).

---

## 5. Checklista — uloge i sigurnost

| # | Test | OK | Napomena |
|---|------|:--:|----------|
| S1 | Neprijavljen korisnik — redirect na login | ☐ | |
| S2 | Nepotvrđen email — nema pristupa modulu | ☐ | |
| S3 | Običan korisnik — **403** na `/kalendar-kulture/dogadjaji` | ☐ | |
| S4 | `kk_admin` — ne vidi konkurse / admin panel konkursa | ☐ | |
| S5 | `kk_admin` — `/dashboard` preusmjerava na kalendar | ☐ | |
| S6 | `superadmin` — može admin događaja | ☐ | |

---

## 6. Checklista — infrastruktura (produkcija)

| # | Test | OK | Napomena |
|---|------|:--:|----------|
| I1 | Slike se učitavaju (`public/storage` → `storage/app/public`) | ☐ | |
| I2 | Nove slike u `storage/app/public/cultural-events/` | ☐ | |
| I3 | SMTP radi (newsletter) | ☐ | |
| I4 | Cron za sedmični newsletter zakazan u Plesku | ☐ | |
| I5 | Hero slika i logo kalendara (`img/heroKK.jpg`, `KKLOGOC.png`) | ☐ | |

---

## 7. Priprema za objavu (odluka tima)

| # | Stavka | OK | Napomena |
|---|--------|:--:|----------|
| O1 | Dovoljno **`published`** događaja za prvi prikaz | ☐ | |
| O2 | Landing link **Kalendar kulture** radi | ☐ | |
| O3 | Link/kartica na **dashboardu** — treba li dodati? | ☐ | Trenutno nema u kodu |
| O4 | Tekstovi, kategorije i vizuelni identitet pregledani | ☐ | |
| O5 | `kk_admin` nalog aktivan, lozinka promijenjena sa defaulta | ☐ | |
| O6 | Tim zajednički odlučio: **cjelina spremna za korisnike** | ☐ | |

---

## 8. Brzi scenario (30–45 min)

1. Uloguj se kao **`kk_admin`**.
2. Kreiraj **4** događaja: 1× `draft`, 3× `published` (2× istaknuto, 1× obično).
3. Jedan **višednevni** događaj preko vikenda.
4. Uloguj se kao **običan korisnik** — vidiš samo 3 published.
5. Prođi **arhivu** (ako imaš prošli published događaj).
6. Prijavi se na **newsletter** test emailom.
7. Pokušaj **4. istaknuti** — provjeri limit.

---

## 9. Kategorije i statusi (referenca)

**Statusi:** `draft`, `published`, `archived`, `cancelled` — u javnom UI samo **`published`**.

**Kategorije** (iz `CulturalEvent::CATEGORIES`): Koncerti, Predstave, Izložbe, Sportski događaji, Književne večeri, Filmske projekcije, Radionice, Promocije publikacija, Performansi, Filmski festivali, Likovne manifestacije, Prezentacije, Paneli o kulturi, Manifestacije u organizaciji Mjesnih zajednica, Manifestacije u organizaciji NVU.

---

## Povezani dokumenti

- [cultural-calendar.md](cultural-calendar.md) — rute, model, newsletter
- [roles-and-permissions.md](roles-and-permissions.md) — uloga `kk_admin`
- [project-operations.md](project-operations.md) — objava cjeline
- [deployment-and-cron.md](deployment-and-cron.md) — cron i Toolkit
