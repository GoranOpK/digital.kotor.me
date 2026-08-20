# Autentikacija i registracija

**Poslednje ažuriranje:** 2026-08-20
**Izvor u kodu:** `HomeController`, `ProfileController`, `ProfileUpdateRequest`, `app/Support/UserType`, `app/Support/ResidentialStatusDeclaration`, `routes/auth.php`, `app/Models/User.php`

---

## Tok prijave (login)

1. `GET /login` → forma
2. `POST /login` — validacija email/lozinka
3. Zahtjev: `activation_status = active`
4. Email mora biti verifikovan (`verified` middleware na zaštićenim rutama)
5. Redirect na `dashboard` (role-specific redirect u `HomeController@dashboard`)

---

## Kanonski V1 korisnički model

Osnovna kategorija korisnika (`users.user_type`) određuje **identitet/oblik** korisnika: ko je korisnik.

Svojstva i uslovi potrebni za pravo učešća na konkretnom konkursu predstavljaju **zaseban konkursni/eligibility sloj** i ne postaju automatski osnovne kategorije korisnika Digital Kotora. Konkursni pojmovi poput poljoprivrednika, ribara, MSP veličine ili individualnog sportiste nisu `user_type`.

Podržanih **8** kategorija (CLOSED):

| Canonical type | Legal nature | Storage value |
|---|---|---|
| Fizičko lice | fizičko lice | `Fizičko lice` |
| Preduzetnik | fizičko lice (poslovna kategorija) | `Preduzetnik` |
| DOO | pravno lice | `Društvo sa ograničenom odgovornošću` |
| AD | pravno lice | `Akcionarsko društvo` |
| OD | pravno lice | `Ortačko društvo` |
| KD | pravno lice | `Komanditno društvo` |
| Nevladino udruženje | pravno lice | `Nevladino udruženje` |
| Sportska organizacija | pravno lice | `Sportska organizacija` |

SSOT u kodu: `App\Support\UserType`. `isNaturalPerson(Preduzetnik) = true`, `isLegalEntity(Preduzetnik) = false`.

UI registracije i dalje nudi grupu `Registrovan privredni subjekt`; to **nije** storage vrijednost. `business_type` je polje forme koje se mapira u `users.user_type`. Nema kolone `users.business_type`.

Legacy storage vrijednosti (dio stranog društva, zbirno udruženje, ustanova, druge organizacije) ostaju čitljive zbog postojećih redova. **Ne nude se** novoj registraciji i **ne auto-mapiraju** se na kanonske kategorije.

## Registracija

**Ruta:** `GET/POST /register` — `HomeController@registerForm` / `register`

### Koraci za korisnika

1. Izbor grupe: Fizičko lice ili Registrovan privredni subjekt
2. Ako je privredni subjekt: izbor jedne od 7 podržanih kategorija (Preduzetnik + 6 pravnih oblika)
3. Popuna: ime, email, telefon, ulica, **grad**, identifikatori, lozinka
4. Verifikacija emaila (link u poruci — `VerifyEmailNotification`)

### Validacija identiteta

| Polje | Pravilo |
|-------|---------|
| JMB | 13 cifara + algoritam kontrole; obavezan za rezidentna fizička lica i Preduzetnike |
| PIB | 8 cifara (`App\Support\Pib`), unique; obavezan za pravna lica; **nije** automatski obavezan za Preduzetnika |
| Adresa | Kotor opština za rezidente i pravna lica (v. [business-rules.md](business-rules.md)) |

### Rezidentnost

Primjenjuje se **samo** na Fizičko lice i Preduzetnika:

- `resident` — Rezident
- `non-resident` — Nerezident

Pravno lice: polje se ne prikazuje, nije required, backend ga ne prima kao poslovni input, storage = `NULL`. Nema fallback-a na `resident`.

`ex-non-resident` („Bivši nerezident“) je **legacy** vrijednost uklonjena iz aktivnog modela. Nije kanonska kategorija; profil i registracija je ne nude i ne prihvataju.

Digital Kotor status **ne izračunava** iz JMB, pasoša, državljanstva, adrese ni broja dana.

Za postojeća fizička lica / Preduzetnike sa `residential_status IS NULL` važi declare-on-use ugovor (`App\Support\ResidentialStatusDeclaration`). UI aktivacija je odložena do prvog module consumera. EP payment stub **nije** povezan.

### Default uloga

`korisnik` (`role_id` iz `RoleSeeder`).

---

## Profil

**Rute:** `profile.edit`, `profile.update`, `profile.password.update`

- Fizičko lice i Preduzetnik: natural-person grana (JMB / rezidentnost). Preduzetnik može imati opciono poslovno ime; PIB nije automatski obavezan.
- Pravna lica: legal-entity grana (naziv + PIB). Bez `residential_status` na write path-u. Postojeći legacy `resident` redovi se ovim paketom **ne** čiste.
- Staff/system nalozi bez poslovnog identiteta (`user_type` NULL, npr. `kk_admin`) ne biraju `user_type` ni rezidentnost.
- `ProfileUpdateRequest` — ista Kotor pravila; PIB pravila prema pravnom licu, ne prema Preduzetniku
- `User::formattedAddress()` — prikaz „ulica, grad“

---

## Breeze vs custom auth

**Važeće stanje:**

- `routes/web.php` registruje custom login/register preko `HomeController`
- `routes/auth.php` (Laravel Breeze) takođe učitava auth rute

**Poznati rizik:** duplikat imena ruta `login` i `register`. Pri debug-u provjeriti red registracije i `php artisan route:list`.

---

## Middleware na zaštićenim rutama

```
auth → verified → module_access_restrict
```

Neulogovan → login. Nеверifikovan email → stranica za verifikaciju (Breeze).

---

## Deaktivacija

Admin može deaktivirati korisnika (`activation_status`). Deaktivirani ne mogu login.

---

## Povezani dokumenti

- [roles-and-permissions.md](roles-and-permissions.md)
- [business-rules.md](business-rules.md)
