# Digital Kotor
# Implementation Strategy
## IS-001 — Implementaciona strategija javnog portala

**Oznaka dokumenta:** IS-001  
**Naziv:** Implementaciona strategija javnog portala  
**Feature ID:** FT-001  
**Modul:** Kalendar kulture  
**Referentna specifikacija:** TS-009 v1.0.19 Stable
**Status dokumenta:** Stable
**Verzija:** 1.0.8
**Datum:** 2026-08-13

---

# 1. Identitet dokumenta

| Stavka | Vrijednost |
|--------|------------|
| Oznaka | IS-001 |
| Naziv | Implementaciona strategija javnog portala |
| Tip | Operativni planski dokument |
| Referenca | TS-009 v1.0.19 Stable |
| Usvojene odluke | IS-001-01 … IS-001-08 |

### IS-001-01 — Identitet dokumenta

IS-001 je operativni planski dokument koji definiše:

* faze implementacije;
* međuzavisnosti;
* procjenu rizika;
* strategiju testiranja;
* strategiju implementacije;
* strategiju deploy-a;
* strategiju rollback-a;

za implementaciju **TS-009 v1.0.5**, bez mijenjanja usvojenih poslovnih, funkcionalnih i tehničkih pravila.

IS-001:

* ne definiše nove funkcionalnosti;
* ne predstavlja zamjenu za BM, FS ili TS;
* ne mijenja usvojena BM/FS/TS pravila;
* mora ostati potpuno sljediv prema TS-009.

---

# 2. Svrha i status

**Svrha:** omogućiti kontrolisanu, evolutivnu implementaciju javnog portala u skladu sa TS-009, uz najmanji rizik za postojeću produkciju (princip IA-01).

**Status dokumenta:** Stable (v1.0.8).

**Van svrhe:** SQL, Laravel kod, konačni dizajn klasa/metoda, nove Product Owner odluke, zamjena TS-003…TS-008.

## 2.1 CURRENT STATE — FAZA 6A CLOSURE (2026-08-13)

**FAZA 6A = CLOSED**

| Stavka | Status |
|--------|--------|
| Public Event read SSOT | **CANONICAL ONLY** (`CulturalEventEntry` + `CulturalOccurrence`) |
| Active public legacy dependency | **0** |
| Dual-read | **NO** |
| Dual-write | **NO** |
| `CULTURAL_PUBLIC_READ_SOURCE` / XOR flag | **REMOVED** (nije current-state mehanizam) |
| Package A (`cultural-calendar.day`) | **CLOSED** — PRODUCTION VERIFIED (empty-date) |
| Phase **B1** (flag/config removal) | **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** |
| Phase **B2** (canonical-only public + legacy CRUD runtime removal) | **IMPLEMENTED / TESTED / PRODUCTION VERIFIED / CLOSED** |
| Phase **B3** (`cultural_events` table DROP) | **DEFERRED** — non-runtime / **non-blocking for 6A** |
| Production canonical categories | **14/14 PASS** (TS-007 §2.7 set) |
| PATCH-063 | Opcioni javni `cancellation_reason` **dozvoljen** (superseduje PATCH-060 apsolutnu zabranu) |
| Implementation remaining (6A) | **NONE** |
| Production verification (6A / B1 / B2) | **PASS** (PO-confirmed) |
| P0 / P1 | **0 / 0** |
| 6B | **OUT OF SCOPE** for this closure; **does not block 6A** |

Historical plan rows u §9.6 (privremeni flag; „bez javnog cancellation_reason“) ostaju istorijski; CURRENT STATE = ova tabela + TS-009 v1.0.19.

---

# 3. Referentni dokumenti

| Dokument | Uloga |
|----------|--------|
| `docs/technical-specifications/Technical-Specification_Javni_portal.md` (TS-009 v1.0.19) | Referentna specifikacija javnog portala |
| `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-11, BM-05, …) | Poslovna pravila (ne mijenjaju se ovim dokumentom) |
| `docs/functional-specifications/Functional-Specification.md` (§5.1–§5.4, §5.13) | Funkcionalni zahtjevi |
| TS-003 Događaj | Domen Događaja; zavisnost Faze 4/6 |
| TS-004 Održavanje | Domen Održavanja; zavisnost Faze 4/5/6 |
| TS-005 Manifestacija | Domen Manifestacije; zavisnost Faze 4/5 |
| TS-007 Kategorije i oznake | Oznake događaja; zavisnost Faze 4/6 |
| TS-008 Mediji | Mediji / fallback; zavisnost Faze 4/6 (po obuhvatu) |
| `docs/features/Feature-Registry.md` | Registar FT-001 / plan TS |

**Napomena o putanji:** Dokument je smješten u `docs/implementation-strategies/`, analogno `docs/technical-specifications/` i `docs/change-requests/`.

**Napomena o oznaci TS-010:** Feature Registry rezerviše **TS-010** za *Urednički portal*. IS-001 nije taj dokument i ne zamjenjuje ga.

---

# 4. Granice i načela

### Granice

IS-001 **ne smije**:

* mijenjati TS-009, BM ili FS;
* uvoditi nova poslovna pravila ili nove PO odluke;
* davati SQL, Laravel kod ili konačnu arhitekturu klasa;
* zamijeniti tehničke specifikacije domena (TS-003…TS-008);
* rješavati otvorena pitanja bez Product Owner-a.

IS-001 **smije** navoditi: pogođene tehničke slojeve, tip migracije, nivo rizika, zavisnosti, redoslijed, test/deploy/rollback obuhvat.

### Terminologija (usklađeno sa BM / FS / TS-009)

Kanonski nazivi:

| Pojam | Značenje |
|-------|----------|
| Kalendar kulture | Modul / portal |
| Početna | Početna stranica |
| Pretraga i pregled | Centralna lista + filteri |
| Detalji događaja | Stranica jednog događaja |
| Manifestacije / Detalji manifestacije | Zasebna cjelina / stranica Manifestacije |
| Arhiva događaja | Stranica arhive |
| Održavanje | Poslovni entitet (TS-004) |
| Termin | Isključivo vremenski atributi Održavanja; nije entitet |
| Kategorija | Primarna klasifikacija događaja |
| **Oznake** | Dodatna klasifikacija događaja (BM-08 / TS-007). **Jedini** kanonski termin za tu klasifikaciju. |
| **Tagovi** | Metapodaci medija (BM-09 / TS-008). **Nisu** sinonim za Oznake; nisu V1 UI (TS-009). |
| Statusne oznake | Javni prikaz poslovnog statusa (npr. Otkazano). **Nisu** isto što i Oznake (BM-08). |

U IS-001 se **ne** koristi riječ „Tagovi“ za klasifikaciju događaja.

---

# 5. Implementacioni principi

### IS-001-02 — Implementacioni principi

1. Minimalne izmjene postojeće produkcione aplikacije.
2. Evolutivni razvoj bez nepotrebnog redizajna (usklađeno sa IA-01 / BM-PK-16 / BR-255).
3. Male i funkcionalno zaokružene implementacione cjeline.
4. Kontrolisan rizik.
5. Očuvanje kompatibilnosti postojećih funkcionalnosti.
6. Samostalno testiranje svake faze.
7. Mogućnost sigurnog rollback-a.
8. Potpuna sljedivost prema BM, FS i TS-009.

Implementacija **ne smije** uvoditi funkcionalnosti koje nijesu prethodno usvojene kroz dokumentaciju.

**Hibridna strategija isporuke (usklađena sa principima):**

* Faze 1–3: tanke, portalne cjeline (jedna logička cjelina po isporuci).
* Faza 4: domenski modul(i) — ne djeliti na „pola“ entiteta bez konzistentnog modela.
* Faze 5–6: portalni potrošač nakon stabilnog domena.

---

# 6. Pregled trenutnog stanja

Na osnovu analize postojeće implementacije (bez izmjene koda u okviru IS-001):

| Područje | Stanje (sažetak) |
|----------|------------------|
| Početna | Hero statički; istaknuti / statistike / lista ispod kalendara postoje, djelimično odstupaju od TS-009 (npr. limiti, klikabilnost, dugme „Prikaži sve“) |
| Pretraga i pregled | Stranica postoji (UI naziv još „Pregled događaja“); filteri nepotpuni u odnosu na PO-TS9-04A |
| Detalji događaja | Postoje; datum/vrijeme/lokacija na događaju (bez zasebnog Održavanja); bez bloka Manifestacije; statusne oznake ograničene |
| Arhiva događaja | Postoji lista (objavljeni + završen period po datumu); bez punih statusnih oznaka po BM-PK-13 |
| Manifestacije (portal) | Nisu implementirane (nema entiteta / ruta / UI) |
| Održavanja | Nisu zaseban model; podaci na događaju (TO odstupanje) |
| Oznake / Mediji (domen) | Nisu u skladu sa punim TS-007/TS-008 modelom na portalu |

Detaljna matrica odstupanja: §7.

---

# 7. Matrica funkcionalnosti i odstupanja

| Funkcionalnost (TS-009) | Postoji | Potrebna izmjena | Nova implementacija | Tipična faza |
|-------------------------|:------:|:----------------:|:-------------------:|:------------:|
| IA-01 evolutivni okvir | djelimično | — | — | sve |
| Rename → Pretraga i pregled | ne (UI) | da | ne | 1 |
| Filteri (datum, kategorija, lokacija) | djelimično | da | djelimično | 2 |
| Mjesečni filter `month` + klik treće statistike (CR-002) | da (CR-002) | — | ne | **2** |
| Filteri `q` / `category` / `location` + filter UI (CR-003) | da (CR-003) | — | ne | **2** |
| Filter Manifestacija | ne | — | da | 5 (nakon 4) |
| Hero | da | provjera | ne | 1 |
| Istaknuti (max 3, prazno) | da (CR-001) | — | ne | 1 |
| Statistike Danas / Ove sedmice (klik) | da (CR-001) | — | ne | 1 |
| Statistike Izabrani mjesec (klik + `month`) | da (CR-002) | — | ne | **2** |
| Lista ispod kalendara (max 3, „Prikaži sve“) | da (CR-001) | — | ne | 1 |
| Detalji događaja — baseline (postojeći model) | djelimično | da | ne | **3** |
| Detalji događaja — puni domen (Održavanja, Oznake, …) | ne | da | djelimično | **6** (nakon 4) |
| Arhiva događaja — baseline (postojeći model) | da | da | ne | **3** |
| Arhiva događaja — puno usklađenje (status / domen) | djelimično | da | ne | **6** |
| Manifestacije UI (lista/detalj/program/blok) | ne | — | da | 5 |
| Domen MF / Održavanja / Oznake / Mediji | ne / djelimično | — | da | 4 |

---

# 8. Matrica zavisnosti

```text
Faza 1 (UI usklađenje)
    └─► nije uslovljen domenom
    └─► preporučeni preduslov za Fazu 2 (rename)

Faza 2 (Pretraga i pregled)
    └─► preferira završenu Fazu 1
    └─► filter MF → tek nakon Faze 4 + 5

Faza 3 (Detalji + Arhiva — **samo postojeći model**)
    └─► ne uključuje više Održavanja, Oznake (BM-08), niti pun BM-DG-04 kriterijum arhive
    └─► puno usklađenje Detalja/Arhive sa domenom → **isključivo Faza 6**

Faza 4 (Domenski model)
    └─► preduslov za Fazu 5
    └─► preduslov za Fazu 6 (više Održavanja, Oznake, mediji po obuhvatu)

Faza 5 (Manifestacije na portalu)
    └─► zahtijeva Fazu 4 (Manifestacija + Održavanja za program)
    └─► ne zatvara puno usklađenje Detalja/Arhive (to je Faza 6)

Faza 6A (IR-001 — javni portal Događaja / kanonski cutover)
    └─► **ne blokira** TS-005 / Manifestacije (PO-TS9-08F)
    └─► više Održavanja, CAT-CUTOVER, public query SSOT (TS-009 §7.3 / §9–§12)

Faza 6B (IR-001 — Manifestacije na portalu)
    └─► zahtijeva TS-005 spreman; mapira se na IS-001 Fazu 5 + dio ranije „Faze 6“

Faza 6 (IS-001 istorijski naziv — završno usklađenje)
    └─► tumači se kao **6A + 6B** po IR-001 v1.0.5; 6A nije uslovljena MF portalom
```

**Granica Faza 3 vs Faza 6 (obavezna):**

| | Faza 3 | Faza 6A (IR) / završno usklađenje Događaja |
|--|--------|--------|
| Model podataka | Postojeći (bez novih tabela/relacija) | Kanonski `CulturalEventEntry` + `CulturalOccurrence` |
| Detalji / Arhiva | UI i ponašanje u granicama trenutne šeme | Prikaz usklađen sa punim BM-PK-05/09–14 / BM-PK-29–33 |
| Više Održavanja | Zabranjeno uvoditi | Obavezno uskladiti prikaz (kartica + detalj) |
| Oznake (BM-08) | Van obuhvata | Po potrebi / naredni korak |
| Manifestacije | Van | **Faza 6B** (ne blokira 6A) |
| Kriterijum Arhive | Bez izmjene ka BM-DG-04 osim ako poseban CR i PO odobre ranije | Usklađenje sa BM-PK-13 / BR-114 u punom smislu |

**Obavezujući redoslijed** (IS-001-03): 1 → 2 → 3 → 4 → 5 → 6, uz izuzetak: **IR Faza 6A** (kanonski cutover Događaja) **nije** blokirana IS Fazom 5 / TS-005 (PO-TS9-08F / IR-001). Ograničeni paralelizam Faze 2 i Faze 3 dozvoljen je samo uz odobrenje i bez dijeljenih konflikata.

---

# 9. Implementacione faze

### IS-001-03 — Implementacione faze

## 9.1 Faza 1 — Usklađenje postojećeg korisničkog interfejsa

| Stavka | Opis |
|--------|------|
| **Cilj** | Uskladiti postojeći javni UI sa usvojenim TS-009 odlukama za početnu i terminologiju (PO-TS9-03A label; PO-TS9-06A–06D), bez novog domena |
| **Obuhvat** | Terminologija (Pretraga i pregled); Početna; Hero (provjera usklađenosti); istaknuti (max 3, prazno stanje); statistike (klikabilnost, naziv mjeseca); lista ispod kalendara (max 3, „Prikaži sve“); očuvanje postojećih javnih tokova; TD-TS9-01 (interni dan-view nije javni ekran IA) |
| **Zavisnosti** | Nema domenskih preduslova |
| **Rizik** | **Nizak** |
| **Uticaj na kod** | Sloj kontrolera (početna / limiti upita); model (eventualno validacija isticanja); prikazi (početna, navigacija); rute: bez novih; stilovi: malo; klijentska logika kalendara: eventualno label; baza: ne; testovi: da |
| **Ulaz** | TS-009 usvojen; rizik/test/rollback plan; Faza 1 definisana |
| **Izlaz** | UI usklađen sa PO-TS9-03A (label), 06A–06D, BM-PK-15/21–23; testovi OK; PO potvrda |
| **Test** | Početna: Hero, ≤3 istaknuta, 3 statistike + navigacija na Pretragu i pregled, lista ≤3 / dan, dugme; navigacioni naziv |
| **Deploy** | Bez migracije; bez maintenance window; backup preporučen kao uobičajena praksa; feature flag nije neophodan; smoke: početna + Pretraga i pregled + Detalji događaja + Arhiva događaja |
| **Rollback** | **Potpuni rollback** (revert isporuke) — jednostavan |

**Minimalni skup izmjena:** proširenje postojeće početne i navigacije; bez novih ekrana.

---

## 9.2 Faza 2 — Pretraga i pregled

| Stavka | Opis |
|--------|------|
| **Cilj** | Centralna Pretraga i pregled sa filterima i URL stanjem (PO-TS9-04A), uključujući CR-002 mjesečni filter i CR-003 ne-datumske filtere; bez filtera Manifestacije |
| **Obuhvat** | Pretraga; filteri (datum, kategorija, lokacija — u granicama postojećeg modela); URL stanje; očuvanje konteksta; paginacija; sortiranje po usvojenim pravilima; „Poništi filtere“; **CR-002 (Implemented):** `month` + treća kartica; **CR-003 (Implemented):** `q` / `category` / `location`, filter zona, aktivni filteri, reset (PO-CR3-01…08 / TS-009 §3.3). **Faza 2 je završena u granicama postojećeg modela.** Filter Manifestacije ostaje **Faza 5**; Oznake ostaju **Faza 4/6**. |
| **Zavisnosti** | Preferira Fazu 1 (CR-001 završen). CR-002 i CR-003 završeni. Filter Manifestacije **nije** u ovoj fazi |
| **Rizik** | **Srednji** |
| **Uticaj na kod** | Sloj kontrolera (`events` upiti); prikaz Pretrage i pregleda (filter zona / aktivni filteri); početna (linkovi statistika — CR-002); rute: bez novih; stilovi: malo; baza: ne; testovi: da |
| **Ulaz** | Faza 1, CR-002 i CR-003 završeni; TS-009 v1.0.2 (§3.2–§3.3) |
| **Izlaz** | Filteri + URL u skladu sa BM-PK-18 / BR-257 / TS-009 §3.2–§3.3 (postojeći model); regresija CR-001/CR-002/CR-003 OK |
| **Test** | Prioritet `date` / `week_*` / `month`; AND `q`/`category`/`location`; prazni rezultati; paginacija + query string; aktivni filteri (×); „Poništi sve filtere“; ulazi sa statistika; state persistence / `back` |
| **Deploy** | Bez migracije; bez MW; feature flag opciono; smoke: Pretraga i pregled + linkovi sa početne |
| **Rollback** | **Potpuni** ili **djelimični** (UI filtera) — bez migracije |

### 9.2.1 CR-003 — Filteri `q` / `category` / `location` (implementacioni paket)

| Stavka | Opis |
|--------|------|
| **Status** | **Implemented** |
| **Dokumentacija** | `fc35132` (`docs: prepare CR-003 filter documentation`) |
| **Implementacija** | `595045a` (`feat(cultural-calendar): implement CR-003 event filters`) |
| **Testiranje** | `41 passed (194 assertions)` (`CulturalCalendarCr001Phase1Test` + `CulturalCalendarCr002MonthFilterTest` + `CulturalCalendarCr003FiltersTest`) |
| **Cilj** | Isporuka ne-datumskih filtera na „Pretrazi i pregledu“ prema TS-009 §3.3 i PO-CR3-01…08 |
| **Obuhvat** | GET filter zona (tekst, dropdown kategorije, dropdown lokacije, Pretraži); query `q`/`category`/`location`; AND sa aktivnim datumskim mehanizmom; aktivni filteri sa ×; „Poništi sve filtere“ → čist URL; state persistence kroz paginaciju i `back` |
| **Controller** | `CulturalCalendarController::events` — parsiranje/validacija `q`/`category`/`location`; AND filteri; dropdown lokacija = distinct objavljenih `lokacija`; kategorije = `CulturalEvent::CATEGORIES`; bez novih ruta |
| **View** | `resources/views/cultural-calendar/events.blade.php` — filter zona; aktivni filteri; lista/paginacija/prazno stanje; datumski filteri **ne** u filter zoni |
| **URL** | Postojeća ruta `cultural-calendar.events`; parametri §3.3; paginacija `withQueryString()` |
| **Van obuhvata** | Manifestacija (Faza 5); Oznake (Faza 4/6); migracije; izmjene modela; nove rute; AJAX |
| **Test** | `q` po naslov/opis/lokacija; category/location; AND sa `date`/`week_*`/`month`; ×; Poništi sve; paginacija; `back`; regresija CR-001/CR-002 |
| **Regresija** | Ulazi sa početne (Danas / Ove sedmice / mjesec / Prikaži sve); prioritet datumskih mehanizama; naslov/podnaslov; Arhiva i Detalji neregresirani |
| **Rizik** | **Srednji** |
| **Deploy / Rollback** | Bez migracije; potpuni ili djelimični revert UI/query — bez undo baze |

---

## 9.3 Faza 3 — Detalji događaja i Arhiva

| Stavka | Opis |
|--------|------|
| **Cilj** | Uskladiti Detalje događaja i Arhivu događaja **isključivo u granicama postojećeg modela** (baseline TS-009 §7–§8), bez uvođenja domena Održavanja, Oznaka ili Manifestacije |
| **Obuhvat** | Postojeći detalj; **CR-004A (Implemented):** javni status badge (TS-009 §7.1; PO-CR4A-01…05); **CR-004B (Planned):** javni prikaz otkazanih događaja (TS-009 §7.2; PO-CR4B-01…10); navigacija i povratak; Arhiva; kartice Arhive. **Van obuhvata:** više Održavanja; Oznake (BM-08); blok Manifestacije; izmjena kriterijuma ulaska u Arhivu ka BM-DG-04 (to je Faza 6 ili zaseban odobreni CR); novi statusi baze; Odgođen kao status Događaja; izmjena BR-065 / BM-DG-04 |
| **Zavisnosti** | Faze 1–2 nisu strogi preduslov za početak; **ne zamjenjuje** Fazu 6 |
| **Rizik** | **Srednji** |
| **Uticaj na kod** | Sloj kontrolera / helper za izračun javnog stanja (bez novih tabela); prikazi kartica i Detalja; rute: bez novih; baza: ne |
| **Ulaz** | Plan test/rollback; granica „postojeći model“ dokumentovana u CR; TS-009 §7.1 |
| **Izlaz** | Prikaz i navigacija usklađeni u dogovorenom baseline obimu; status badge konzistentan na svim javnim prikazima; nema regresije dostupnosti detalja / povratka; PO potvrda |
| **Test** | Detalji za javno dostupne događaje; povratak na Arhivu / Pretragu i pregled sa kontekstom; lista Arhive; status badge (Otkazan prioritet; Predstoji / U toku / Završen po pravilima) |
| **Deploy** | Bez migracije; bez MW; feature flag nije neophodan; smoke: Detalji + Arhiva + Pretraga i pregled + Početna |
| **Rollback** | **Potpuni rollback** |

**Ograničenje:** Ne uvoditi nova polja/tabele/relacije u ovoj fazi. Ne simulirati puni TS-004/TS-007/TS-005 prikaz.

### 9.3.1 CR-004A — Javni status badge (implementacioni paket)

| Stavka | Opis |
|--------|------|
| **Status** | **Implemented** |
| **Dokumentacija** | `614706c` (`docs: prepare CR-004A public event status badges`); TS-009 v1.0.4 §7.1; IS-001 v1.0.5 §9.3 / §9.3.1; PO-CR4A-01…05 |
| **Implementacija** | `0f73240` (`feat(cultural-calendar): implement CR-004A public status badges`) |
| **Testiranje** | `65 passed (266 assertions)` (`CulturalEventPublicStatusTest` + `CulturalCalendarCr004APublicStatusTest` + regresija CR-001/CR-002/CR-003) |
| **Cilj** | Jedinstveni javni status badge na svim javnim prikazima događaja |
| **Obuhvat** | Izračunata stanja Predstoji / U toku / Završen; `cancelled` → Otkazan (apsolutni prioritet); badge na karticama (gornji desni ugao fotografije) i Detaljima (ispod naslova); isti tekst/logika/izgled; vremenska zona aplikacije; polja `datum_od` / `datum_do` / `vrijeme` / `vrijeme_do`; bez badge-a kada status nije pouzdan (PO-CR4A-05) |
| **Van obuhvata** | Novi statusi baze; migracije; Odgođen kao status Događaja; Održavanja / Oznake / Manifestacije UI; izmjena kriterijuma Arhive (BM-DG-04); redizajn van PO-CR4A-04; proširenje javne dostupnosti statusa `cancelled` (to je CR-004B); javna dostupnost internog statusa `archived` nije dio CR-004A ni CR-004B |
| **Model / View** | Bez migracije i bez izmjene šeme baze; implementacija uključuje metodu `publicStatus()` na postojećem modelu `CulturalEvent`; zajednički Blade partial; prikazi index / events / archive / show; bez novih ruta; bez izmjene javnih query-ja dostupnosti |
| **Test** | Otkazan prioritet; višednevni / jednodnevni / bez vremena; PO-CR4A-05; konzistentnost Početna / Pretraga / Arhiva / Detalji; regresija CR-001…CR-003 |
| **Rizik** | **Srednji** |
| **Deploy / Rollback** | Bez migracije; potpuni revert UI/metode — bez undo baze |

### 9.3.2 CR-004B — Javni prikaz otkazanih događaja (implementacioni paket)

| Stavka | Opis |
|--------|------|
| **Status** | **Planned** |
| **Dokumentacija** | TS-009 v1.0.5 §7.2; IS-001 v1.0.6 §9.3 / §9.3.2; BM-PK-13 (PATCH-051); FS PATCH-FS-051 (BR-270–BR-274); PO-CR4B-01…10 |
| **Cilj** | Uskladiti javnu dostupnost i portalni prikaz otkazanih događaja bez migracije i bez izmjene internog lifecycle-a |
| **Obuhvat** | Aktivne površine: javni skup `published` \| `cancelled` uz vremenske uslove; Istaknuti isključuju `cancelled` (bez izmjene flaga); sistemsko obavještenje na Detaljima; portalna Arhiva = query po datumu (`published` ili `cancelled` + prošao termin); status otkazanog ostaje `cancelled`; javni status uvijek **Otkazan**; statistike/datumski skupovi uključuju otkazane; bez novih filtera/URL/search moda |
| **Van obuhvata** | Odgođen; Faza 4 / Faza 5; migracije / izmjena šeme / novi modeli / tabele; izmjena BR-065 / BM-DG-04; javna dostupnost internog statusa `archived`; prelaz `cancelled → archived`; izmjena prava otkazivanja (BR-063 / BM-DG-05); izmjena flaga Istaknut |
| **Controller / View** | Proširenje query dostupnosti (bez migracije); Istaknuti filter; `show` dozvoljava `cancelled`; Blade obavještenje na Detaljima; Arhiva uključuje otkazane nakon termina (date query); bez novih ruta |
| **Test** | Dostupnost cancelled na aktivnim površinama; Istaknuti bez cancelled; show URL; obavještenje; portalna Arhiva po datumu uz badge Otkazan; Pretraga bez novog moda; statistike uključuju cancelled; regresija CR-001…CR-004A |
| **Rizik** | **Srednji**. Napomena: buduća implementacija BR-065 (`cancelled → archived`) može zahtijevati novi CR ili migraciju radi očuvanja istorije otkazivanja — nije dio CR-004B. |
| **Deploy / Rollback** | Bez migracije / bez promjene šeme; rollback = vraćanje query/UI promjena — bez undo baze |

---

## 9.4 Faza 4 — Razvoj domenskog modela

| Stavka | Opis |
|--------|------|
| **Cilj** | Uvesti / uskladiti domenske entitete potrebne za puni TS-009 potrošački sloj, prema TS-003/004/005/007/008 — **bez** portalskih MF ekrana (to je Faza 5) |
| **Obuhvat** | Manifestacija; Održavanja; Oznake; Mediji (ako su dio potvrđenog implementacionog obuhvata CR-a); migracije; modeli i relacije; urednički tokovi kao **zavisnost** (planirani TS-010 / postojeći admin — van IS-001 detalja) |
| **Zavisnosti** | Faze 1–3 završene ili odobren izuzetak. Zasebni CR za domen. |
| **Rizik** | **Visok** |
| **Uticaj na kod** | Novi/prošireni modeli; migracije; admin/urednički tokovi; javni portal u ovoj fazi **minimalno** (samo kompatibilnost čitanja); testovi: obavezni domen + regresija portala |
| **Ulaz** | Usvojeni TS domena; migracioni plan; backup plan; test plan; rollback plan; procjena rizika potvrđena |
| **Izlaz** | Domen konzistentan sa BM/FS/TS; migracije uspješne na staging; javni portal nije regresiran; PO potvrda |
| **Test** | Integritet relacija; lifecycle; migracija podataka (dry-run); smoke javnog portala bez MF UI |
| **Deploy** | **Zahtijeva migraciju**; **prethodni backup obavezan**; maintenance window preporučen; posebna deploy provjera; feature flag samo ako smanjuje rizik ekspozicije nedovršenog UI (portal MF još nije Faza 5) |
| **Rollback** | **Rollback uz migraciju podataka** — složeno; plan unaprijed; u nekim scenarijima **rollback nije preporučljiv** bez restore-a |

**Migracioni tipovi (bez SQL-a):** nove tabele (npr. Manifestacija, Održavanja); proširenje postojeće tabele događaja (veza ka MF); eventualno katalog Oznaka / Medija. Najveći rizik: migracija flat datuma/vremena → Održavanja.

---

## 9.5 Faza 5 — Manifestacije u javnom portalu

| Stavka | Opis |
|--------|------|
| **Cilj** | PO-TS9-07A–07E na javnom portalu |
| **Obuhvat** | Navigacija „Manifestacije“; lista; Detalji manifestacije; program; blok Manifestacije na Detaljima događaja; filter po Manifestaciji na Pretrazi i pregledu |
| **Zavisnosti** | **Faza 4** (Manifestacija + Održavanja za program) |
| **Rizik** | **Srednji** (nakon stabilne Faze 4); **Visok** ako se radi prije Faze 4 |
| **Uticaj na kod** | Nove rute/prikazi; proširenje navigacije i Detalja događaja; filter na Pretrazi i pregledu; stilovi za nove stranice; baza: već Faza 4 |
| **Ulaz** | Faza 4 izlazni kriterijumi ispunjeni |
| **Izlaz** | Lista/detalj/program/blok/filter u skladu sa BM-PK-24–28 / BR-265–269; dvosmjerna navigacija; PO potvrda |
| **Test** | Paginacija liste MF; prazna lista; program (statusna oznaka Otkazano; Vrijeme nije definisano); blok na Detaljima događaja; filter MF; regresija Početne / Pretrage i pregleda / Arhive |
| **Deploy** | Bez nove migracije ako je Faza 4 gotova; bez MW tipično; feature flag **preporučen** za navigaciju/rute MF dok se ne potvrdi stabilnost; smoke: MF lista/detalj + Detalji događaja + filter Pretrage i pregleda |
| **Rollback** | **Djelimični** (sakrivanje nav/ruta) ili **potpuni** revert UI — bez undo Faze 4 |

---

## 9.6 Faza 6 — Završno usklađenje (mapiranje IR-001: 6A + 6B)

| Stavka | Opis |
|--------|------|
| **Cilj** | Završiti usklađenost javnog portala sa TS-009; **IR-001 Faza 6A** = kanonski cutover Događaja (bez MF); **IR-001 Faza 6B** = Manifestacije |
| **Obuhvat 6A (plan — historical)** | Cutover `CulturalEvent` → `CulturalEventEntry`+`CulturalOccurrence`; kartica multi-OCC; sortiranje; Odgođen; CAT-CUTOVER; public query SSOT; privremeni flag; očuvanje UI (TS-009 §1.7, §7.3, §9–§12). **Van:** Manifestacije; slug; migracija legacy; dual-read/write |
| **CURRENT STATE 6A** | **CLOSED** — canonical-only SSOT; flag **uklonjen**; dual-read/write **0**; active public legacy dependency **0**; CAT-CUTOVER **14/14** produkcija; PATCH-063 opcioni javni `cancellation_reason` **dozvoljen**; B1+B2 **PRODUCTION VERIFIED / CLOSED**; B3 DROP **DEFERRED / non-blocking**. Vidi §2.1 |
| **Obuhvat 6B** | MF lista/detalj/program; filter MF (TS-009 §6) — **zasebno CLOSED**; ne blokira 6A |
| **Zavisnosti** | 6A: kanonski domen Događaj/Održavanje + katalozi; **TS-005 ne blokira 6A**. 6B: TS-005 spreman |
| **Rizik** | **Srednji do Visok** (široka regresija) — historical plan risk |
| **Uticaj na kod** | Sloj kontrolera/upita/prikaza; testovi: TM-JP (TS-009 §18) + široka regresija |
| **Ulaz** | TS-009 (current Stable); IR-001 (current); checklist usklađenosti |
| **Izlaz** | 6A: **CLOSED** (implementation + production verification); 6B: MF portal CLOSED zasebno |
| **Test** | End-to-end: Početna → Pretraga → Detalji → (6B: Manifestacije) → Arhiva; TM-JP-* |
| **Deploy (plan — historical)** | Privremeni feature flag `legacy` XOR `canonical` (TS-009 §10.2) — **supersedovano** Phase B1+B2 (flag removed) |
| **Rollback (plan — historical)** | Flag → `legacy` — **nije** current-state put; B3 shell nije rollback mehanizam |

---

# 10. Upravljanje rizikom

### IS-001-04 — Upravljanje implementacionim rizikom

| Nivo | Kriterijumi |
|------|-------------|
| **Nizak** | Bez izmjena baze; male UI izmjene; jednostavan rollback |
| **Srednji** | Izmjene kontrolera ili query logike; pretraga/filteri/postojeći javni prikazi; obavezna regresija |
| **Visok** | Nove tabele; novi poslovni entiteti; migracije podataka; nove relacije; izmjene životnog ciklusa |

**Prije svake faze obavezno:** procjena rizika; plan testiranja; plan rollback-a.

**Eskalacija:** ako se rizik tokom rada poveća, rad na pogođenom dijelu se **zaustavlja** do nove procjene i odobrenja.

| Faza | Rizik | Ključni regresioni rizici |
|------|-------|---------------------------|
| 1 | Nizak | Pogrešan filter link; manje istaknutih; admin limit |
| 2 | Srednji | Paginacija/URL; prazni rezultati; ulaz sa početne |
| 3 | Srednji | 404 na show; pogrešan back; sadržaj arhive |
| 4 | Visok | Kalendar/liste/newsletter/admin; gubitak/korupcija termina |
| 5 | Srednji | Nav/layout; show; filter events |
| 6 | Srednji–Visok | Široka regresija svih javnih prikaza |

---

# 11. Ulazni i izlazni kriterijumi

### IS-001-05 — Ulazni i izlazni kriterijumi

**Ulazni (svaka faza):**

* BM, FS i TS zahtjevi usvojeni;
* faza definisana u IS-001;
* potrebne prethodne faze završene (ili odobren izuzetak);
* procijenjen rizik;
* postoji plan testiranja;
* postoji plan rollback-a.

**Tok:**

* izmjene unutar obuhvata faze;
* nema neodobrenih funkcionalnosti;
* izmjene sljedive prema dokumentaciji.

**Izlazni:**

* implementacija odgovara specifikaciji;
* planirani testovi uspješni;
* nema neprihvatljive regresije;
* potrebna dokumentacija usklađena (ako je bilo CR/TO ažuriranje — van izmjene TS-009 pravila);
* faza spremna za isporuku;
* Product Owner formalno potvrdio završetak.

**Odstupanje od specifikacije:** zaustaviti pogođeni dio → analiza → nova PO odluka → ažurirati dokumentaciju → tek onda nastavak.

---

# 12. Strategija testiranja

| Faza | Obavezno testirati | Ne smije biti narušeno |
|------|--------------------|------------------------|
| 1 | Početna (Hero, istaknuti, statistike, lista, dugme); navigacioni naziv | Arhiva, Detalji događaja, admin CRUD, ostali moduli platforme |
| 2 | Filteri, URL, poništi, paginacija, ulazi sa početne | Početna (izuzev linkova), Arhiva |
| 3 | Detalji, povratak, Arhiva kartice/paginacija, statusne oznake (baseline obuhvat) | Admin, newsletter; **ne** očekivati Oznake / više Održavanja |
| 4 | Migracije (staging), relacije, lifecycle; smoke portala | Produkcijski podaci (backup); javni MF UI (Faza 5) |
| 5 | MF lista/detalj/program/blok/filter; regresija Početne / Pretrage / Detalja / Arhive | Podaci Faze 4 |
| 6 | E2E portal + regresija (Održavanja, Oznake, Arhiva po BM) | Stabilnost lista |

**Zajednički smoke nakon svake isporuke:** Početna, Pretraga i pregled, Detalji događaja, Arhiva događaja; nakon Faze 5 i Manifestacije.

---

# 13. Strategija isporuke

### IS-001-06 — Strategija isporuke (Deploy)

Opšta pravila:

* svaka faza samostalno isporučiva;
* jedna isporuka = jedna odobrena logička cjelina;
* naredna faza ne ide u produkciju prije stabilizacije prethodne;
* migracije se testiraju unaprijed;
* faze sa migracijama imaju posebnu deploy provjeru;
* nakon deploy-a — postimplementaciona provjera;
* vodi se evidencija isporuke.

| Faza | Bez MW? | Migracija? | Backup? | Feature flag? | Smoke odmah nakon deploy-a |
|------|:-------:|:----------:|:-------:|:-------------:|----------------------------|
| 1 | Da | Ne | Preporučen | Ne neophodan | Početna, Pretraga i pregled, Detalji, Arhiva |
| 2 | Da | Ne | Preporučen | Opciono | Pretraga i pregled + linkovi sa početne |
| 3 | Da | Ne | Preporučen | Ne neophodan | Detalji, Arhiva, povratak |
| 4 | Ne (preporučen MW) | **Da** | **Obavezan** | Opciono (ekspozicija) | Domen + smoke portala |
| 5 | Da (tipično) | Ne (ako 4 gotova) | Preporučen | **Preporučen** (MF nav/rute) | MF + Detalji događaja + Pretraga i pregled |
| 6 | Po obimu | Po obimu | Obavezan ako ima migracija | Po potrebi | Pun portal E2E |

---

# 14. Strategija rollback-a

### IS-001-07 — Strategija povratka (Rollback)

Klasifikacije: Potpuni; Djelimični; Uz migraciju podataka; Nije preporučljiv.

| Faza | Klasifikacija |
|------|----------------|
| 1 | Potpuni |
| 2 | Potpuni / djelimični |
| 3 | Potpuni |
| 4 | Uz migraciju podataka; u nekim slučajevima nije preporučljiv (restore) |
| 5 | Djelimični (UI/flag) ili potpuni revert UI |
| 6 | Djelimični po paketima |

**Aktivacija rollback-a:** ugrožena stabilnost; kritična greška; regresija onemogućava rad; odluka tehničke odgovorne osobe i/ili Product Owner-a.

Rollback se ograničava na pogođenu fazu kada je moguće.

**Evidencija:** datum/vrijeme; faza; razlog; aktivnosti; rezultat.  
**Nakon rollback-a:** analiza uzroka prije ponovne implementacije.

Plan rollback-a mora postojati **prije** isporuke.

---

# 15. Upravljanje promjenama specifikacije

### IS-001-08 — Upravljanje promjenama specifikacije

**TS-009 v1.0.1** je referentna specifikacija.

Proces promjene:

1. analiza;
2. prijedlog;
3. usaglašavanje;
4. Product Owner odluka;
5. dokumentovanje;
6. verzionisanje;
7. sljedivost.

Implementacioni problem **ne mijenja automatski** specifikaciju.

Ako je potrebna promjena poslovnog ili funkcionalnog pravila: prvo ažurirati i usvojiti dokumentaciju (BM/FS/TS), zatim implementacija.

Ne dozvoljavaju se nevidljive ili nedokumentovane izmjene stabilne specifikacije.

---

# 16. Matrica sljedivosti prema TS-009

| IS-001 | TS-009 / odluke | BM / FS (referenca) |
|--------|-----------------|---------------------|
| Faza 1 | IA-01; PO-TS9-03A (label); PO-TS9-05A/05B (provjera); PO-TS9-06A–06D; TD-TS9-01 | BM-PK-15, BM-PK-16, BM-PK-19–23; BR-117, BR-255, BR-258–264; §5.1–§5.3 |
| Faza 2 | PO-TS9-03A; PO-TS9-04A; **CR-002** (`month`); **CR-003** (`q`/`category`/`location`, PO-CR3-01…08) | BM-PK-17–18, BM-PK-22 (takođe BM-PK-06–07); BR-256–257, BR-263 (takođe BR-107–108); TS-009 §3.2–§3.3 |
| Faza 3 | TS-009 §7–§8 **baseline**; **CR-004A** §7.1 (PO-CR4A-01…05); **CR-004B** §7.2 (PO-CR4B-01…10) | BM-PK-05 (djelimično), BM-PK-13; BR-106, BR-114, BR-270–BR-274. **Ne:** BM-PK-09/11 (Održavanja/Oznake); bez izmjene BM-DG-04 |
| Faza 4 | Granice domena (nije portal UI) | TS-003/004/005/007/008; BM-04/05/06/08/09; odgovarajući BR u FS §5.5–5.12 |
| Faza 5 | PO-TS9-07A–07E; TS-009 §6 | BM-PK-24–28; BM-MF-13; BR-265–269, BR-192 |
| Faza 6 | TS-009 §7–§8 **puni** obuhvat nakon domena | BM-PK-05, BM-PK-09–14; BR-106, BR-110–115; §5.4 |
| IS-001-02 | IA-01 | BM-PK-16; BR-255 |
| IS-001-08 | TS-009 v1.0.5 Stable | — |

| Usvojena odluka | Primarne sekcije IS-001 |
|-----------------|-------------------------|
| IS-001-01 | §1, §2 |
| IS-001-02 | §5 |
| IS-001-03 | §9 |
| IS-001-04 | §10 |
| IS-001-05 | §11 |
| IS-001-06 | §13 |
| IS-001-07 | §14 |
| IS-001-08 | §15 |

---

# 17. Otvorena pitanja i pretpostavke

### Pretpostavke

* Implementacija prati IS-001 redoslijed faza osim uz odobreni izuzetak.
* Faza 4 obuhvata uredničke tokove samo kao zavisnost; detalj uredničkog portala ostaje u okviru planiranog TS-010 / zasebnih CR.
* Mediji u Fazi 4 ulaze samo ako CR potvrdi obuhvat (TS-008).

### Otvorena pitanja (bez PO odluke u IS-001)

1. Tačan CR raspored unutar Faze 4 (redoslijed Održavanja vs Manifestacija vs Oznake vs Mediji).
2. Kada se kriterijum Arhive usklađuje sa BM-DG-04 / statusom Arhiviran — **podrazumijevano Faza 6**; ranije samo uz zaseban CR i PO odobrenje (Faza 3 to ne radi).
3. Feature-flag mehanizam — postoji li već na platformi ili se uvodi samo po potrebi Faze 5.
4. Obim Medija (TS-008) u prvom CR-u Faze 4 (samo hijerarhija prikaza / fallback vs širi obuhvat).

**Uklonjeno kao „otvoreno“:** miješanje pojmova Oznake/Tagovi — razriješeno kanonskom terminologijom (§4). Preklapanje Faze 3/6 — razriješeno eksplicitnom granicom (§8, §9.3, §9.6).

Ova pitanja **ne rješava** IS-001; zahtijevaju analizu i Product Owner / tehničko odobrenje prije pogođene faze.

---

# 18. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Nacrt. Formalizovane usvojene odluke IS-001-01 … IS-001-08. Ugrađeni relevantni zaključci radne implementacione analize TS-009 v1.0.0. Bez izmjene BM/FS/TS/implementacije. |
| 0.5.0 | 2026-07-31 | Final Review. Terminologija Oznake ≠ Tagovi; razgraničenje Faze 3 / Faze 6; precizirana sljedivost BM/FS/TS; usklađeni test/deploy nazivi; smanjena otvorena pitanja. Bez novih PO odluka. Bez izmjene BM/FS/TS/implementacije. |
| 1.0.0 | 2026-07-31 | Stable. Dokument je prošao Final Review i predstavlja referentnu implementacionu strategiju za implementaciju TS-009 v1.0.0. Bez izmjene sadržaja faza, rizika, deploy/rollback strategije, sljedivosti ili otvorenih pitanja. Bez izmjene BM/FS/TS/implementacije. |
| 1.0.1 | 2026-08-01 | CR-002: Faza 2 dopunjena mjesečnim filterom (`month=YYYY-MM`), klikom treće statističke kartice, prioritetom filtera, usklađenošću broja/liste i testovima. Referenca TS-009 v1.0.1. Faza 1 neizmijenjena. Bez izmjene implementacije. |
| 1.0.2 | 2026-08-01 | CR-003: Faza 2 dopunjena implementacionim paketom §9.2.1 (`q`/`category`/`location`, filter zona, AND, aktivni filteri, reset; PO-CR3-01…08). Referenca TS-009 v1.0.2. Bez izmjene implementacije. |
| 1.0.3 | 2026-08-01 | Statusno usklađenje nakon CR-003 implementacije: CR-003 = Implemented (`fc35132` / `595045a`; testovi 41/194). Faza 2 završena u granicama postojećeg modela; MF → Faza 5; Oznake → Faza 4/6. Bez izmjene obuhvata Faze 2. Bez izmjene BM/FS/TS/implementacije. |
| 1.0.4 | 2026-08-01 | CR-004A Planned (IS-001 Faza 3): javni status badge; §9.3 / §9.3.1; referenca TS-009 v1.0.3 §7.1; PO-CR4A-01…04. Bez izmjene implementacije. Bez širenja Faze 3 van badge obuhvata. |
| 1.0.5 | 2026-08-01 | CR-004A Implemented (dokumentacija `614706c`; implementacija `0f73240`; testovi 65/266). §9.3 / §9.3.1; referenca TS-009 v1.0.4. Precizirano: bez migracije/izmjene šeme; metoda na postojećem modelu CulturalEvent. Bez širenja Faze 3; Faza 3 nije označena kao završena. |
| 1.0.6 | 2026-08-06 | CR-004B Planned (IS-001 Faza 3): korektivni prolaz — bez migracija/šeme; cancelled ostaje; portalna Arhiva = date query; archived nije javno; §9.3.2; TS-009 v1.0.5 §7.2; PO-CR4B-01…10. Faza 3 nije zatvorena. Bez izmjene implementacije. |
| 1.0.7 | 2026-08-09 | **Faza 6A/6B (IR-001):** usklađenje sa TS-009 v1.0.6 — 6A kanonski cutover Događaja nije blokiran TS-005; §8 dijagram / §9.6 ažurirani. Bez izmjene implementacije. |
| 1.0.8 | 2026-08-13 | **FAZA 6A DOCUMENTATION CLOSURE:** §2.1 CURRENT STATE — 6A **CLOSED**; canonical-only; B1+B2 PRODUCTION VERIFIED / CLOSED; categories 14/14; PATCH-063 supersede; B3 DEFERRED non-blocking; referenca TS-009 v1.0.19. Bez izmjene implementacije. |

---

**Kraj dokumenta IS-001 v1.0.8 (Stable)**
