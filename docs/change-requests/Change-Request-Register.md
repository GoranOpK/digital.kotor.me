# Digital Kotor
# Change Request Register
## Modul: Kalendar kulture

**Status dokumenta:** AKTIVAN
**Verzija:** 0.4

---

# Svrha

Change Request Register predstavlja centralni registar svih odobrenih poslovnih zahtjeva koji zahtijevaju izmjenu implementacije.

Functional Specification definiše željeno ponašanje sistema.

Change Request Register evidentira odobrene zahtjeve za usklađivanje implementacije sa usvojenom specifikacijom.

---

# Pravila

Svaki Change Request dobija jedinstveni identifikator:

* CR-001
* CR-002
* …

Identifikator se ne mijenja i ne ponovo koristi.

Statusi:

* Planned
* In Progress
* Implemented
* Verified
* Closed
* Cancelled

Procjena uticaja može uključivati:

* UI
* Backend
* Database
* Permissions
* Documentation
* Performance

Svaki odobreni poslovni zahtjev koji zahtijeva izmjenu implementacije mora biti evidentiran u Change Request Register-u i dobiti jedinstveni CR identifikator prije početka razvoja.

---

# Registar

| ID | Naziv | FS Referenca | Prioritet | Procjena uticaja | Status |
| -- | ----- | ------------ | --------- | ---------------- | ------ |
| CR-001 | IS-001 Faza 1 — Usklađenje postojećeg javnog UI | FS-001 → §5.1–§5.3, BR-261–BR-264 | Medium | UI, Backend | Implemented |
| CR-002 | IS-001 Faza 2 — Mjesečni filter i klik treće statistike | FS-001 → §5.2 / BR-263; TS-009 §3.2 | Medium | UI, Backend | Implemented |
| CR-003 | IS-001 Faza 2 — Filteri Pretrage i pregleda (`q` / `category` / `location`) | FS-001 → BR-257 / BR-107–108; TS-009 §3.3 | Medium | UI, Backend | Planned |

---

# CR-001

### Naziv

IS-001 Faza 1 — Usklađenje postojećeg javnog korisničkog interfejsa.

### Referenca

FS-001 → §5.1–§5.3, BR-261–BR-264; TS-009; IS-001 Faza 1.

### Opis

Usklađivanje postojećeg javnog UI sa usvojenim odlukama (terminologija „Pretraga i pregled“, Hero, istaknuti max 3 + neutralno prazno stanje, klikabilne kartice Danas/Ove sedmice, label izabranog mjeseca, lista ispod kalendara max 3, „Prikaži sve događaje“).

**Van obuhvata CR-001 (Scope Freeze):** mjesečni filter i klik treće kartice — prebačeno na CR-002.

### Razlog

Implementacija nije bila u potpunosti usklađena sa usvojenim BM/FS/TS-009 za početnu stranicu.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Implemented** (commit `613dc00`, 2026-08-01).

---

# CR-002

### Naziv

IS-001 Faza 2 — Mjesečni filter i klik treće statističke kartice.

### Referenca

FS-001 → §5.2 / BR-263; BM-PK-22; TS-009 §3.2 / §5.3; IS-001 Faza 2.

### Opis

Treća statistička kartica (Izabrani mjesec) postaje klikabilna i otvara „Pretragu i pregled“ sa query parametrom:

`month=YYYY-MM`

(npr. `/kalendar-kulture/pregled-dogadjaja?month=2026-08`).

Usvojena pravila:

* broj na kartici i lista = **isti skup** (objavljeni događaji koji se preklapaju sa mjesecom; **bez** ograničenja „samo od danas“);
* prioritet filtera (bez kombinovanja): `date` → `week_start`+`week_end` → `month`;
* naslov stranice ostaje „Pretraga i pregled“; podnaslov/aktivni filter: „Izabrani mjesec: …“;
* nevalidan `month` se ignoriše (bez greške; standardna stranica).

### Razlog

CR-001 je namjerno odgodio mjesečni filter. BM-PK-22 / BR-263 / TS-009 zahtijevaju klikabilnost treće kartice; CR-002 zatvara taj jaz u okviru IS-001 Faze 2.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Implemented** (2026-08-01).

### Implementacija

* Dokumentacioni commit: `d01c6d0` (TS-009 / IS-001 v1.0.1; ugovor za `month`).
* Implementacioni commit: `c5d396f` (`feat(cultural-calendar): implement CR-002 month filter`).
* Isporuka: klik treće statističke kartice → `month=YYYY-MM`; mjesečni overlap filter; prioritet `date` → `week_start`/`week_end` → `month` (bez kombinovanja); nevalidan `month` se bezbjedno ignoriše; broj na kartici i lista koriste isti skup događaja; podnaslov „Izabrani mjesec: …“.
* Testiranje: `29 passed (121 assertions)` (`CulturalCalendarCr001Phase1Test` + `CulturalCalendarCr002MonthFilterTest`).
* Bez migracija; bez novih ruta; bez izmjena modela.

---

# CR-003

### Naziv

IS-001 Faza 2 — Filteri na stranici „Pretraga i pregled“ (`q` / `category` / `location`).

### Referenca

FS-001 → BR-257, BR-107, BR-108; BM-PK-06, BM-PK-07, BM-PK-18; TS-009 §3.3; IS-001 §9.2.1; PO-CR3-01 … PO-CR3-08.

### Opis

Na postojećoj stranici „Pretraga i pregled“ (`cultural-calendar.events`) uvesti ne-datumske filtere u granicama postojećeg modela:

* URL parametri: `q`, `category`, `location` (PO-CR3-01);
* tekstualna pretraga `q` po `naslov`, `opis`, `lokacija` — ne po kategoriji/statusu/datumima/… (PO-CR3-02);
* kategorija: dropdown iz `CulturalEvent::CATEGORIES` (PO-CR3-03);
* lokacija: dropdown jedinstvenih objavljenih lokacija, A–Z, bez praznih (PO-CR3-04);
* datumski prioritet neizmijenjen (`date` → `week_*` → `month` → default); ne-datumski AND sa aktivnim datumskim mehanizmom (PO-CR3-05);
* aktivni filteri sa ×; „Poništi sve filtere“ → `/kalendar-kulture/pregled-dogadjaja` (PO-CR3-06);
* filter zona: tekst, kategorija, lokacija, Pretraži — GET forma, bez AJAX/auto-submit; datumski filteri van zone (PO-CR3-07);
* forma odražava URL; persistence kroz pretragu, paginaciju i `back` (PO-CR3-08).

**Van obuhvata:** Manifestacija; Oznake; migracije; izmjene modela; nove rute; AJAX.

### Razlog

CR-002 zatvorio je mjesečni datumski ulaz. Preostali dio IS-001 Faze 2 / PO-TS9-04A (pretraga, kategorija, lokacija, filter UI, reset) zahtijeva usklađivanje implementacije sa usvojenim BM/FS/TS.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Planned** (dokumentacija usvojena; implementacija nije započeta).

---

# Sljedivost (Traceability)

Za svaki Change Request treba biti moguće pratiti njegov životni ciklus kroz projektnu dokumentaciju.

Preporučeni lanac sljedivosti je:

Business Model
↓
Functional Specification
↓
Change Request
↓
Technical Specification
↓
Implementation Strategy
↓
Implementacija
↓
Testiranje
↓
Produkcija

Ovaj model omogućava da se za svaku funkcionalnost može utvrditi:

* poslovni razlog uvođenja,
* funkcionalni zahtjevi,
* razlog izmjene,
* tehnička implementacija,
* način testiranja,
* status u produkciji.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-26 | Kreiran Change Request Register. Evidentiran CR-001. |
| 2026-07-26 | Proširen Change Request Register novim kolonama. Dodata procjena uticaja (Impact). Uvedeno pravilo obavezne registracije Change Request-ova prije razvoja. Dodato poglavlje Sljedivost (Traceability). |
| 2026-08-01 | CR-001 status → Implemented (IS-001 Faza 1). Evidentiran CR-002 (mjesečni filter / treća kartica; IS-001 Faza 2). Verzija registra 0.2. |
| 2026-08-01 | CR-002 status → Implemented (commit `c5d396f`; dokumentacija `d01c6d0`). Mjesečni filter `month=YYYY-MM`; prioritet date → week → month; testovi 29/121. Bez migracija/ruta/modela. Verzija registra 0.3. |
| 2026-08-01 | Evidentiran CR-003 (Planned): filteri `q`/`category`/`location` na Pretrazi i pregledu; PO-CR3-01…08; TS-009 §3.3; IS-001 §9.2.1. Verzija registra 0.4. |
