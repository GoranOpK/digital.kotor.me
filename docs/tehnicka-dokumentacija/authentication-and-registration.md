# Autentikacija i registracija

**Poslednje ažuriranje:** 2026-09-10
**Izvor u kodu:** `HomeController`, `ProfileController`, `ProfileUpdateRequest`, `CanonicalHttpIdentityService`, `ProfileIdentityMapper`, `CanonicalIdentityWriter`, `app/Support/UserType`, `app/Support/ResidentialStatusDeclaration`, `routes/auth.php`, `app/Models/User.php`

**Produkcija 2026-09-06 (DK-TS-002 v1.0.1 / D15 Step 8 CLOSED / PRODUCTION PASS):** kanonski identitet je produkcioni autoritet (`IDENTITY_CANONICAL_READ=true`, `IDENTITY_CANONICAL_WRITE=true`, `IDENTITY_WRITE_FREEZE=false`, `IDENTITY_EP_IDENTITY_FLOWS=false`). **R1 ACTIVE.** `users.user_type` je izvedeni compatibility mirror, **nije** current identity SSOT. Istorijski opis ispod ostaje AS-IS compatibility / account sloj; ne pretvara se u pre-cutover stanje. Detalj: `DK-TS-002` §14.1.

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

SSOT u kodu: `App\Support\UserType`. `isNaturalPerson(Preduzetnik) = true`, `isLegalEntity(Preduzetnik) = false`. Preduzetnik **nije** posebna pravna priroda; ostaje Fizičko lice. Trenutni klasifikator kanonskog grafa: `physical_person_identities.is_entrepreneur`. `users.user_type` = `Preduzetnik` je izvedeni mirror, ne druga Vrsta subjekta.

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
| PIB | 8 cifara (`App\Support\Pib`), unique; obavezan za pravna lica i kada je Preduzetnik = Da (uz Poslovno ime i CRPS; `DK-BM-002` §7) |
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

Ovo je **izmjena postojećeg** korisničkog profila / kanonskog identiteta. **Nije** početna registracija. **Nije** declare-on-use dopuna (`DK-BM-002` §11.3) — ta ostaje odvojen tok kad postojećem nalogu nedostaje podatak tek prije korišćenja funkcije.

SSOT lifecycle-a Fizičko lice ↔ Preduzetnik: `DK-BM-002` §11.4, `DK-FS-002` §16.4, `DK-TS-002` §7.3 i §7.19. Ovaj odjeljak ih ne redefiniše.

Preduzetnik **nije** posebna pravna priroda i **nije** četvrta Vrsta subjekta. Ostaje Fizičko lice.

Postojeći registrovani korisnik čiji je kanonski identitet Fizičko lice smije na Izmjena korisničkog profila promijeniti poslovnu kategoriju u **oba** smjera:

1. Fizičko lice → Preduzetnik
2. Preduzetnik → Fizičko lice

Write path: `ProfileController::update` → `CanonicalHttpIdentityService::updateProfileIdentity` → `ProfileIdentityMapper` → `CanonicalIdentityWriter::updateLiveGraph` → `DerivedUserTypeMirror::sync`.

**Fizičko lice → Preduzetnik.** Rezultujuće stanje = Preduzetnik (`is_entrepreneur = true`). Obavezni su Poslovno ime, PIB i Broj registracije u CRPS, istom kanonskom validacijom kao pri registraciji Preduzetnika. Polja nisu zaključana nakon prvog unosa.

**Preduzetnik → Fizičko lice.** Isti nalog, isti korijen kanonskog identiteta, isti zapis Fizičkog lica. Ne kreira se novi nalog. Ne kreira se drugi identitet Fizičkog lica. Nije prelaz u Pravno lice niti u Dio stranog privrednog društva. Trenutni klasifikator: `is_entrepreneur = false`. Već unijeti Poslovno ime, PIB i CRPS **ostaju sačuvani** kao neaktivni/istorijski podaci profila; **nisu** aktivni atributi trenutnog običnog Fizičkog lica. Prisustvo tih polja samo po sebi **ne** čini korisnika trenutnim Preduzetnikom.

**Reaktivacija** (ponovo Fizičko lice → Preduzetnik): zadržane vrijednosti smiju se prikazati (prefill) radi pregleda i izmjene i **moraju** se ponovo validirati. Postojanje zadržanih podataka **nije** dokaz da su i dalje validni.

**JMB.** Promjena Fizičko lice ↔ Preduzetnik **sama po sebi** ne kreira novi JMB, ne zamjenjuje postojeći JMB i ne kreira novi identitet Fizičkog lica. JMB ostaje atribut istog identiteta Fizičkog lica. Ako profil odvojeno dozvoli izmjenu JMB kroz sopstveno polje, to je izmjena podatka profila, **nije** posljedica entrepreneur toggle-a.

**Rezidentnost.** Ista promjena poslovne kategorije **sama po sebi** ne mijenja `residential_status`. Rezidentnost pripada Fizičkom licu, uključujući kada to lice ima status Preduzetnika. Nova pravila rezidentnosti se ovim odjeljkom **ne** uvode.

- Pravna lica: legal-entity grana (naziv + PIB). Bez `residential_status` na write path-u. Postojeći legacy `resident` redovi se ovim paketom **ne** čiste. Ova grana **nije** tok Fizičko lice ↔ Preduzetnik.
- Staff/system nalozi bez poslovnog identiteta (`user_type` NULL, npr. `kk_admin`) ne biraju `user_type` ni rezidentnost.
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
- `DK-BM-002` §11.4 — `docs/business-model/Business_Model_Registracija_korisnickog_identiteta.md`
- `DK-FS-002` §16.4 — `docs/functional-specifications/Functional_Specification_Registracija_korisnickog_identiteta.md`
- `DK-TS-002` §7.3 / §7.19 — `docs/technical-specifications/Technical_Specification_Registracija_korisnickog_identiteta.md`
