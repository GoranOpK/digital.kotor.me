# Životni ciklus prijave na konkurs

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** `ApplicationController`, `BusinessPlanController`, `EvaluationController`, `AdminController`, `Application` model

---

## Pregled koraka (važeće)

```
Registracija + verifikacija emaila
        ↓
Izbor konkursa (status=published, type=zensko, rok nije istekao)
        ↓
Obrazac 1a ili 1b (draft ili finalno) — applications.create/store
        ↓
Obrazac 2 — biznis plan (obavezan prije submita)
        ↓
Upload dokumentacije (može i prije submita; nije blokirajući za submit)
        ↓
Konačno podnošenje — applications.final-submit → status=submitted
        ↓
Evaluacija komisije (nakon isteka roka)
        ↓
approved / rejected
        ↓
Ugovor → izvještaji o realizaciji
```

---

## Statusi prijave (`applications.status`)

| Status | Značenje |
|--------|----------|
| `draft` | U izradi; može se mijenjati i brisati |
| `submitted` | Podnesena; dodijeljen `redni_broj` |
| `evaluated` | Ocjenjena od strane komisije |
| `approved` | Odobrena |
| `rejected` | Odbijena |

---

## Obrazac 1a / 1b

**Kontroler:** `ApplicationController@create/store`  
**Ruta:** `GET/POST /competitions/{competition}/apply`

### Tipovi podnosioca (`applicant_type`)

| Vrijednost | Obrazac | Značenje |
|------------|---------|----------|
| `preduzetnica` | 1a | Fizičko lice **sa** registrovanom djelatnošću |
| `fizicko_lice` | 1a | Fizičko lice **bez** registrovane djelatnosti |
| `doo` | 1b | DOO |
| `ostalo` | 1b | Ostala društva |

### Faza biznisa (`business_stage`)

- `započinjanje` — novi posao
- `razvoj` — razvoj postojećeg

### Draft

Parametar `save_as_draft=1` — relaksirana validacija; prijava ostaje `draft`.

### Kompletnost obrasca

`Application::isObrazacComplete()` — obavezna polja po tipu + `accuracy_declaration`.  
**Finansijski iznosi** više nisu u Obrazcu 1a/1b; idu u biznis plan.

### Nakon uspješnog čuvanja (nije draft)

Redirect na biznis plan ako je obrazac kompletan.

---

## Obrazac 2 — biznis plan

**Kontroler:** `BusinessPlanController`  
**Ruta:** `GET/POST /applications/{application}/business-plan`

- Pristup samo vlasniku prijave.
- Zahtijeva `isObrazacComplete()`.
- Polja uključuju traženi iznos, projekcije troškova, opis poslovanja itd. (`BusinessPlan` model).

Prikaz na statusu prijave: `displayRequestedAmount()`, `displayRequiredFunds()`, `displayCompetitionBudget()` na `Application` modelu.

---

## Dokumentacija prijave

**Upload:** `POST /applications/{application}/upload`

- Direktan upload (PDF/JPG/PNG, max 20 MB po fajlu) ili izbor iz **biblioteke dokumenata** (`user_document_id`).
- Tipovi dokumenta: lista u validaciji kontrolera + `Application::getRequiredDocuments()` po Odluci (čl. 12–13).
- Jedan dokument po `document_type` po prijavi.

### Submit **ne zahtijeva** kompletnu dokumentaciju

**Važeće** (`ApplicationController@submit`, komentar u kodu):

> Korisnici mogu podnijeti prijavu i bez svih dokumenata. Predsjednik komisije može odbiti prijavu zbog nedostatka dokumenata kroz formu za ocjenjivanje.

Odbijanje zbog dokumenata: `isRejectedForMissingDocuments()` — `rejection_reason` sadrži „Nedostaju potrebna dokumenta“.

---

## Konačno podnošenje

**Ruta:** `POST /applications/{application}/submit` (`applications.final-submit`)

Uslovi:

1. `status === draft`
2. Postoji `businessPlan`
3. Adresa podnosioca prolazi Kotor validaciju (`kotorAddressErrorForApplication`)
4. Dokumenti **nisu** obavezni

Rezultat: `status=submitted`, `submitted_at=now()`, `redni_broj` inkrement po konkursu.

---

## Evaluacija

**Middleware:** `role:komisija`  
**Kontroler:** `EvaluationController`

- 10 kriterijuma (Obrazac 3) po članu komisije → `EvaluationScore`
- Dodatni bodovi na prijavi: info dan (+1), novi biznis (+2), Zavod nezaposleni (+2), zeleno/inovativno (+3)
- **Minimum za prolaz:** 30 bodova (`Application::meetsMinimumScore()`)
- Predsjednik komisije: odluka, potpis, odbijanje zbog dokumenata

Komisija se vezuje za godinu konkursa (`Commission::where('year', $competition->year)`).

---

## Nakon odobrenja

| Faza | Kontroler | Model |
|------|-----------|-------|
| Ugovor | `ContractController` | `Contract` — statusi: `draft`, `signed`, `approved` |
| Izvještaj realizacije | `ReportController` | `Report` |

---

## Konkurs — rokovi

`Competition::getDeadlineAttribute()` — baza: `start_date` ili `published_at` + `deadline_days` (default **20**).

Jedna prijava po korisniku po konkursu.

---

## PDF uputstvo na stranici konkursa

`GET /competitions/guide/pdf` — servira `uputstvo-zensko-preduzetnistvo.pdf` iz `public/pdf/` (ili fallback putanje u closure ruti).

Korisničko uputstvo (tekst): `docs/UPUTSTVO_ZENSKO_PREDUZETNISTVO.md`.

---

## Povezani dokumenti

- [business-rules.md](business-rules.md) — lista dokumenata, bodovi, adresa
- [document-library-and-mega.md](document-library-and-mega.md)
- [roles-and-permissions.md](roles-and-permissions.md)
