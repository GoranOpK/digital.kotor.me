# Poslovna pravila

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** `KotorAddress`, `HomeController`, `ProfileUpdateRequest`, `ApplicationController`, `Application` model

---

## Adresa — Opština Kotor

**Klasa:** `app\Support\KotorAddress`  
**Pravilo:** `app\Rules\KotorMunicipalityAddress`

### Važeće

- **Ulica i broj** u posebnom polju; **grad** u posebnom polju (`city` na `users` i u profilu/prijavi).
- Ulica mora imati min. 2 znaka; ne smije biti samo naziv naselja.
- Dozvoljeno `bb` / `b.b.` / „bez broja“.
- Grad ili ulica+grad moraju ukazivati na naselje Opštine Kotor ili poštanski broj **85310** / **85330**.

### Naselja (iz koda)

Kotor, Dobrota, Prčanj, Škaljari, Risan, Perast, Muo, Orahovac, Stoliv, Ljuta, Mirač, Kostanjica, Lastva, Mrčajevići, Puce, Grbalj (normalizacija bez dijakritika u validaciji).

### Gdje se provjerava

| Kontekst | Metoda / mjesto |
|----------|-----------------|
| Registracija | `HomeController::register` — rezidenti i pravna lica (ne preduzetnik bez adrese) |
| Profil | `ProfileUpdateRequest` |
| Prijava — submit | `ApplicationController::kotorAddressErrorForApplication` |
| Obrazac 1a/1b (finalno) | Adresa iz profila (`profileAddressErrorForUser`) |

---

## JMB, PIB, pasoš

| Identifikator | Pravilo | Ko |
|---------------|---------|-----|
| **JMB** | 13 cifara + kontrolna cifra (`HomeController::validateJMB`) | Rezidenti (fizička lica) |
| **PIB** | Tačno **9** cifara (`regex:/^[0-9]{9}$/`), unique u `users` | Pravna lica / preduzetnici |
| **Pasoš** | Alternativa za nerezidente | `residential_status=non-resident` |

Kolone u bazi: `users.pib` VARCHAR(9) (migracija `2026_06_26_150000_restore_pib_length_to_9.php`).

---

## Registracija — tipovi korisnika

`user_type` / `business_type` određuju Obrazac pri prijavi i obavezna polja pri registraciji.

Detalji: [authentication-and-registration.md](authentication-and-registration.md).

---

## Prijava — obavezni dokumenti

Logika: `Application::getRequiredDocuments()` i `getRequiredDocumentsForType()` — zavisi od:

- `applicant_type` (preduzetnica, doo, ostalo, fizicko_lice)
- `business_stage` (započinjanje / razvoj)
- `is_registered` (da li ima registrovanu djelatnost)

Primjer ključeva tipova: `licna_karta`, `crps_resenje`, `pib_resenje`, `predracuni_nabavka`, `potvrda_zavod_nezaposleni`, …

**Potvrda Zavoda za nezaposlene:** opciona u upload validaciji; može biti u listi obaveznih po tipu prijave.

**Važna napomena iz koda:** za DOO/ostalo u fazi razvoj/započinjanje **ne traži se** „Dokaz o broju poslovnog žiro računa društva“ (uklonjeno iz liste).

---

## Bodovanje i dodatni bodovi

| Bod | Uslov | Vrijednost |
|-----|-------|------------|
| Info dan | `bonus_info_day` | +1 |
| Novi biznis | `bonus_new_business` | +2 |
| Zavod nezaposleni | `bonus_zavod_nezaposleni` | +2 |
| Zeleno / inovativno | `bonus_green_innovative` | +3 |

**Minimum za prolaz:** 30 bodova (`meetsMinimumScore()`).

Odbijanje zbog nedostatka dokumenata: prikaz ocjene **0** (`getDisplayScore()`).

---

## Konkurs

- Tip u produkciji: `zensko` (lista na `competitions.index`)
- Statusi: `draft`, `published`, `closed`, `completed`
- Rok prijave: `deadline_days` (default 20) od `start_date` ili `published_at`
- Jedna prijava po korisniku po konkursu

---

## Biblioteka dokumenata

- Kvota: **20 MB** po korisniku (`DocumentProcessor::MAX_STORAGE_PER_USER`)
- Formati: PDF, JPG, PNG
- Opcioni `expires_at` — mora biti u budućnosti pri uploadu

---

## Član komisije

Ne može podnijeti prijavu na konkurs za koji je aktivan član komisije (`CommissionMember::activeForCommission`).

---

## Povezani dokumenti

- [application-lifecycle.md](application-lifecycle.md)
- [UPUTSTVO_ZENSKO_PREDUZETNISTVO.md](../UPUTSTVO_ZENSKO_PREDUZETNISTVO.md)
