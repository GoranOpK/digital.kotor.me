# Uloge i dozvole

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** `database/seeders/RoleSeeder.php`, `RoleMiddleware.php`, `RestrictRoleModuleAccess.php`, `routes/web.php`

---

## Uloge u bazi

| ID | `name` | Prikaz |
|----|--------|--------|
| 1 | `admin` | Administrator |
| 2 | `komisija` | Komisija |
| 3 | `korisnik` | Korisnik (default pri registraciji) |
| 4 | `superadmin` | Super administrator |
| 5 | `konkurs_admin` | Administrator konkursa |
| 6 | `kk_admin` | Administrator kalendara kulture |

**Nije u seederu:** `evaluator` — postoji u rutama (`role:evaluator`) ali uloga se ne kreira automatski. Tretirati legacy rutama.

---

## RoleMiddleware (`role:*`)

- Registrovan u `bootstrap/app.php` kao alias `role`.
- `superadmin` **uvijek prolazi** bez provjere liste uloga.
- Inače korisnik mora imati jednu od navedenih uloga na ruti, inače **403**.

Primjer: `Route::middleware('role:admin,konkurs_admin')`.

---

## RestrictRoleModuleAccess

Primjenjuje se na **sve** autentifikovane rute (grupa u `web.php`).

Zajednički dozvoljeni route name-ovi za sve: `dashboard`, `logout`, `profile.edit`, `profile.update`, `profile.password.update`.

### `kk_admin`

Dozvoljeno: kalendar kulture (`cultural-calendar.*`, `cultural-events.*`, URL `kalendar-kulture*`), profil.  
Sve ostalo → redirect na `cultural-calendar.index`.

### `konkurs_admin`

Dozvoljeno: konkursi (javni i admin), komisije, admin dashboard, pregled prijave.  
Sve ostalo → redirect na `admin.dashboard`.

### `komisija`

Dozvoljeno: dashboard, evaluacija, arhiva konkursa, admin pregled prijave i konkursa (index/show/ranking/decision).  
Sve ostalo → redirect na `dashboard`.

### Ostale uloge

Nema dodatnih ograničenja ovim middleware-om (ali `role:*` na pojedinim rutama i dalje važi).

---

## Matrica sposobnosti (sažetak)

| Sposobnost | superadmin | admin | konkurs_admin | komisija | korisnik | kk_admin |
|------------|:----------:|:-----:|:-------------:|:--------:|:--------:|:--------:|
| Upravljanje korisnicima | ✓ | ✓ | | | | |
| CRUD konkursa / publish | ✓ | ✓ | ✓ | | | |
| Upravljanje komisijom | ✓ | ✓ | ✓ | | | |
| Evaluacija prijava | ✓ | | | ✓ | | |
| Prijava na konkurs | ✓* | ✓* | | ✓** | ✓ | |
| Biblioteka dokumenata | ✓* | ✓* | | ✓* | ✓ | |
| Admin kalendar događaja | | | | | | ✓ |
| Odobravanje ugovora / izvještaja | ✓ | ✓ | | | | |

\* Osim ako `module_access_restrict` preusmjeri specijalizovanu ulogu.  
\** Član komisije **ne može** podnijeti prijavu na konkurs za koji je član te komisije (`ApplicationController@store`).

---

## Redirecti nakon prijave (dashboard)

`HomeController@dashboard`:

- `kk_admin` → kalendar kulture
- `konkurs_admin` → admin dashboard
- `komisija` → evaluacioni panel

---

## Aktivacija naloga

Login zahtijeva `activation_status = active` na korisniku. Deaktivacija preko admin panela.

---

## Povezani dokumenti

- [modules-and-routes.md](modules-and-routes.md)
- [authentication-and-registration.md](authentication-and-registration.md)
