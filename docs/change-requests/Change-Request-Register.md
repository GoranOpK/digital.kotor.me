# Digital Kotor
# Change Request Register
## Modul: Kalendar kulture

**Oznaka dokumenta:** KK-CR-REG-001
**Status dokumenta:** AKTIVAN
**Verzija:** 0.8

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
| CR-001 | KK-IS-001 Faza 1 — Usklađenje postojećeg javnog UI | KK-FS-001 → §5.1–§5.3, BR-261–BR-264 | Medium | UI, Backend | Implemented |
| CR-002 | KK-IS-001 Faza 2 — Mjesečni filter i klik treće statistike | KK-FS-001 → §5.2 / BR-263; KK-TS-009 §3.2 | Medium | UI, Backend | Implemented |
| CR-003 | KK-IS-001 Faza 2 — Filteri Pretrage i pregleda (`q` / `category` / `location`) | KK-FS-001 → BR-257 / BR-107–108; KK-TS-009 §3.3 | Medium | UI, Backend | Implemented |
| CR-004A | KK-IS-001 Faza 3 — Javni status badge (Predstoji / U toku / Završen / Otkazan) | KK-FS-001 → BR-114; KK-TS-009 §7.1; PO-CR4A-01…05 | Medium | UI, Backend | Implemented |
| CR-004B | KK-IS-001 Faza 3 — Javni prikaz otkazanih događaja | KK-FS-001 → BR-270–BR-274; BR-001/114/116; KK-TS-009 §7.2; PO-CR4B-01…10 | Medium | UI, Backend | Planned |

---

# CR-001

### Naziv

KK-IS-001 Faza 1 — Usklađenje postojećeg javnog korisničkog interfejsa.

### Referenca

KK-FS-001 → §5.1–§5.3, BR-261–BR-264; KK-TS-009; KK-IS-001 Faza 1.

### Opis

Usklađivanje postojećeg javnog UI sa usvojenim odlukama (terminologija „Pretraga i pregled“, Hero, istaknuti max 3 + neutralno prazno stanje, klikabilne kartice Danas/Ove sedmice, label izabranog mjeseca, lista ispod kalendara max 3, „Prikaži sve događaje“).

**Van obuhvata CR-001 (Scope Freeze):** mjesečni filter i klik treće kartice — prebačeno na CR-002.

### Razlog

Implementacija nije bila u potpunosti usklađena sa usvojenim BM/FS/KK-TS-009 za početnu stranicu.

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

KK-IS-001 Faza 2 — Mjesečni filter i klik treće statističke kartice.

### Referenca

KK-FS-001 → §5.2 / BR-263; BM-PK-22; KK-TS-009 §3.2 / §5.3; KK-IS-001 Faza 2.

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

CR-001 je namjerno odgodio mjesečni filter. BM-PK-22 / BR-263 / KK-TS-009 zahtijevaju klikabilnost treće kartice; CR-002 zatvara taj jaz u okviru KK-IS-001 Faze 2.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Implemented** (2026-08-01).

### Implementacija

* Dokumentacioni commit: `d01c6d0` (KK-TS-009 / KK-IS-001 v1.0.1; ugovor za `month`).
* Implementacioni commit: `c5d396f` (`feat(cultural-calendar): implement CR-002 month filter`).
* Isporuka: klik treće statističke kartice → `month=YYYY-MM`; mjesečni overlap filter; prioritet `date` → `week_start`/`week_end` → `month` (bez kombinovanja); nevalidan `month` se bezbjedno ignoriše; broj na kartici i lista koriste isti skup događaja; podnaslov „Izabrani mjesec: …“.
* Testiranje: `29 passed (121 assertions)` (`CulturalCalendarCr001Phase1Test` + `CulturalCalendarCr002MonthFilterTest`).
* Bez migracija; bez novih ruta; bez izmjena modela.

---

# CR-003

### Naziv

KK-IS-001 Faza 2 — Filteri na stranici „Pretraga i pregled“ (`q` / `category` / `location`).

### Referenca

KK-FS-001 → BR-257, BR-107, BR-108; BM-PK-06, BM-PK-07, BM-PK-18; KK-TS-009 §3.3; KK-IS-001 §9.2.1; PO-CR3-01 … PO-CR3-08.

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

CR-002 zatvorio je mjesečni datumski ulaz. Preostali dio KK-IS-001 Faze 2 / PO-TS9-04A (pretraga, kategorija, lokacija, filter UI, reset) zahtijeva usklađivanje implementacije sa usvojenim BM/FS/TS.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Implemented** (2026-08-01).

### Implementacija

* Dokumentacioni commit: `fc35132` (`docs: prepare CR-003 filter documentation`; KK-TS-009 / KK-IS-001 v1.0.2; PO-CR3-01…08).
* Implementacioni commit: `595045a` (`feat(cultural-calendar): implement CR-003 event filters`).
* Isporuka:
  * tekstualna pretraga `q` (naslov, opis, lokacija);
  * filter `category` (dropdown `CulturalEvent::CATEGORIES`);
  * filter `location` (dropdown jedinstvenih objavljenih lokacija);
  * AND logika ne-datumskih filtera;
  * kombinovanje sa jednim aktivnim datumskim mehanizmom (`date` → `week_*` → `month` → default);
  * GET filter forma (Pretraži; bez AJAX/auto-submit);
  * aktivni filteri; uklanjanje pojedinačnog filtera (×); „Poništi sve filtere“;
  * očuvanje URL stanja, paginacije (`withQueryString`) i `back` konteksta.
* Testiranje: `41 passed (194 assertions)` (`CulturalCalendarCr001Phase1Test` + `CulturalCalendarCr002MonthFilterTest` + `CulturalCalendarCr003FiltersTest`).
* Bez migracija; bez novih ruta; bez izmjena modela.

---

# CR-004A

### Naziv

KK-IS-001 Faza 3 — Javni status badge (Predstoji / U toku / Završen / Otkazan).

### Referenca

KK-FS-001 → BR-114; BM-PK-13; KK-TS-009 §7.1; KK-IS-001 §9.3 / §9.3.1; PO-CR4A-01 … PO-CR4A-05.

### Opis

Na javnom portalu uvesti jedinstveni status badge događaja:

* javna stanja: **Predstoji**, **U toku**, **Završen**, **Otkazan** (PO-CR4A-01);
* interni statusi (`draft` / Na odobrenju / `published` / `archived`) ne prikazuju se građanima;
* `cancelled` → **Otkazan** (apsolutni prioritet);
* **Odgođen** ostaje isključivo status Održavanja — nije javni status Događaja;
* Predstoji / U toku / Završen = izračunata stanja (ne statusi baze), prema pravilima PO-CR4A-03 (`datum_od` / `datum_do` / `vrijeme` / `vrijeme_do`; vremenska zona aplikacije);
* prikaz na: Početna, Pretraga i pregled, Arhiva događaja, Detalji događaja (PO-CR4A-02);
* kartice: badge u gornjem desnom uglu fotografije; Detalji: ispod naslova (PO-CR4A-04);
* jedinstven tekst, badge i logika na svim prikazima;
* ako se status ne može pouzdano odrediti, badge se ne prikazuje (PO-CR4A-05).

**Van obuhvata:** novi statusi baze; migracije; Odgođen kao status Događaja; Održavanja / Oznake / Manifestacije UI; izmjena kriterijuma Arhive (BM-DG-04); redizajn van PO-CR4A-04; proširenje javne dostupnosti statusa `cancelled` (to je CR-004B); javna dostupnost internog statusa `archived` nije dio CR-004A ni CR-004B.

### Razlog

BM-PK-13 / BR-114 zahtijevaju jasan prikaz statusa na javnom portalu. CR-004A formalizuje izračunata javna stanja i jedinstveni badge u okviru KK-IS-001 Faze 3, bez izmjene lifecycle Događaja.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Implemented** (2026-08-01).

### Implementacija

* Dokumentaciona priprema: `614706c` (`docs: prepare CR-004A public event status badges`).
* Implementacija: `0f73240` (`feat(cultural-calendar): implement CR-004A public status badges`).
* Testovi: `65 passed (266 assertions)` (`CulturalEventPublicStatusTest` + `CulturalCalendarCr004APublicStatusTest` + regresija CR-001/CR-002/CR-003).
* Datum statusnog usklađenja: 2026-08-01.
* Isporuka:
  * metoda `CulturalEvent::publicStatus()` (ključ / labela / CSS klasa; `null` kada status nije pouzdan — PO-CR4A-05);
  * zajednički Blade partial `cultural-calendar/partials/public-status-badge.blade.php`;
  * badge na Početnoj (istaknuti / naredni / događaji dana), Pretrazi i pregledu, Arhivi i Detaljima;
  * Unit i Feature testovi;
  * bez migracija i bez izmjene šeme baze;
  * bez izmjene javne dostupnosti statusa kroz query-je i rute (`published` ostaje ulazni skup).

---

# CR-004B

### Naziv

KK-IS-001 Faza 3 — Javni prikaz otkazanih događaja.

### Referenca

KK-FS-001 → BR-270–BR-274; BR-001, BR-114, BR-116; BM-PK-13; BM-DG-05 / BR-063 (prava — bez izmjene); KK-TS-009 §7.2 / §8; KK-IS-001 §9.3 / §9.3.2; PO-CR4B-01 … PO-CR4B-10.

### Opis

Proširiti javnu dostupnost otkazanih događaja na portalu (CR-004A je uveo badge, ali nije mijenjao query dostupnosti):

* otkazani događaj ostaje javno dostupan; interni status ostaje `cancelled` (PO-CR4B-01);
* do planiranog termina prikaz na aktivnim javnim površinama: početnoj, kalendaru, događajima dana, narednim događajima, Pretrazi i pregledu, Detaljima i direktnom URL-u (PO-CR4B-02);
* ne prikazuje se među Istaknutim; flag „Istaknut“ se ne mijenja — samo isključenje iz javnog prikaza (PO-CR4B-03);
* nakon isteka planiranog termina otkazani događaj zadržava interni status `cancelled`, prikazuje se u portalnoj Arhivi na osnovu datuma i u javnom prikazu ostaje označen statusom „Otkazan“ (PO-CR4B-04);
* portalna Arhiva je javna vremenska površina i ne podrazumijeva promjenu internog statusa u `archived`;
* na Detaljima fiksno sistemsko obavještenje (PO-CR4B-05); badge ostaje (CR-004A);
* bez novih filtera, URL parametara ili search moda (PO-CR4B-06);
* **Odgođen** nije dio CR-004B (PO-CR4B-07);
* prava otkazivanja: postojeća BR-063 / BM-DG-05 — bez novih pravila (PO-CR4B-08);
* CR-004B ne mijenja BR-065 ni BM-DG-04 i ne uvodi javnu dostupnost internog statusa `archived`. Buduća implementacija lifecycle prelaza `cancelled → archived` zahtijeva zasebno rješenje za trajno očuvanje informacije o otkazivanju (PO-CR4B-09).

**Van obuhvata:** Odgođen; promjena termina; održavanja; manifestacije; novi modeli/migracije/tabele; Faza 4 / Faza 5; izmjena BR-065 / BM-DG-04; javna dostupnost internog statusa `archived`; prelaz `cancelled → archived`; izmjena flaga Istaknut; novi filteri / URL parametri.

### Razlog

BM-PK-13 / BR-114 zahtijevaju prikaz otkazanih na portalu. CR-004A isporučuje badge; CR-004B usklađuje javni skup dostupnosti i portalni prikaz, bez izmjene internog lifecycle-a.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

**Planned**

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
| 2026-08-01 | CR-003 status → Implemented (dokumentacija `fc35132`; implementacija `595045a`). Testovi 41/194. Bez migracija/ruta/modela. Verzija registra 0.5. |
| 2026-08-01 | Evidentiran CR-004A (Planned): javni status badge Predstoji / U toku / Završen / Otkazan; PO-CR4A-01…04; TS-009 §7.1; IS-001 §9.3.1. Verzija registra 0.6. |
| 2026-08-01 | CR-004A status → Implemented (dokumentacija `614706c`; implementacija `0f73240`). Testovi 65/266. Bez migracija/ruta/izmjene šeme. Verzija registra 0.7. |
| 2026-08-06 | Evidentiran CR-004B (Planned): javni prikaz otkazanih; korektivni prolaz (portalna Arhiva ≠ archived; status ostaje cancelled); PO-CR4B-01…10; BR-270–BR-274; TS-009 §7.2; IS-001 §9.3.2. Bez izmjene BR-065 / BM-DG-04. Verzija registra 0.8. |
| 2026-08-17 | Administrativna migracija dokumentacionog ID-a na `KK-*` namespace. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |
