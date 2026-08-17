# Kalendar kulture
# Registar skraćenica i oznaka dokumentacije Kalendara kulture

**Oznaka dokumenta:** KK-RG-001
**Naziv:** Registar skraćenica i oznaka dokumentacije Kalendara kulture
**Status dokumenta:** Stable
**Verzija:** 1.1.13
**Datum:** 2026-08-17

---

# 1. Svrha i način korišćenja

### KK-RG-001-01 — Identitet

KK-RG-001 je **referentni i živi** dokument. Predstavlja **centralni registar skraćenica i oznaka** koje se koriste u dokumentaciji modula **Kalendar kulture**.

Nije opšti katalog oznaka za kompletnu platformu Digital Kotor.

**Pravilo obuhvata**

Registar sadrži isključivo skraćenice i identifikatore koji su specifični za dokumentaciju i poslovni model modula Kalendar kulture (uključujući povezane oznake iz usvojene dokumentacije tog modula).

Opšteprihvaćene tehničke, informatičke i industrijske skraćenice (npr. PDF, HTTP, SQL, JSON, SMTP, JPEG, PNG i sl.) ne evidentiraju se u ovom dokumentu.

Namijenjen je prvenstveno:

* novim članovima razvojnog tima;
* osobama koje održavaju aplikaciju;
* analitičarima, testerima i tehničkim saradnicima koji prvi put ulaze u projekat.

Odgovara na pitanja:

* koje se skraćenice koriste;
* šta znače;
* koje vrste referentnih dokumenata postoje;
* gdje tražiti koju vrstu informacije.

**Princip:** jedna kanonska skraćenica = jedno značenje = jedan referentni dokument ili dokumentacioni standard.

KK-RG-001:

* **nije poslovni pojmovnik** i ne definiše poslovne ni tehničke koncepte;
* ne definiše poslovna pravila ni funkcionalnosti;
* ne zamjenjuje BM, FS, TS, IS, IR, Feature Registry ili CR;
* samo evidentira skraćenice i identifikatore koji se **stvarno koriste** u usvojenoj dokumentaciji Kalendara kulture.

**Poslovni pojmovi i statusi** (npr. Nacrt, Objavljen, Otkazan, Planiran, Odgođen) definišu se u **BM-GL**, **BM-ST** i odgovarajućim FS/TS sekcijama — vidi §2.8. KK-RG-001 ih samo referencira.

**Kako koristiti:** pročitati §2 za skraćenice, zatim §3 da znate koji tip dokumenta otvoriti. Za sadržaj pravila ići u vlasnički dokument (BM/FS/TS/…).

**Status oznaka u ovom dokumentu:** ako nije drugačije navedeno, prefiks/skraćenica je **AKTIVNO**. **ISTORIJSKO** = još se može pojaviti u starijim zapisima; **ZASTARJELO** = supersedovano (ne brisati bez traga).

---

# 2. Skraćenice

### KK-RG-001-02 — Struktura (dio A)

Uključene su samo **interne** skraćenice projekta koje se **stvarno koriste** u usvojenoj dokumentaciji.

## 2.0 Dokumentacioni namespace

Dokumentacioni namespace Kalendara kulture je `KK-*`.

* `EP-*` pripada e-Plaćanju.
* `DK-*` pripada zajedničkoj/platformskoj dokumentaciji Digital Kotora.
* Lista namespace prefiksa **nije** zatvorena: novi poslovni moduli mogu uvesti sopstveni prefiks, uz lokalnu numeraciju unutar tog namespace-a.

Numeracija dokumentacionih ID-eva je lokalna unutar namespace-a. `KK-TS-002` trenutno **nije dodijeljen** i dokument nije kreiran. Broj 002 u `KK-*` namespace-u nije rezervisan za e-Plaćanje.

FT-004 / Obavještenja pripadaju platformskoj dokumentaciji i biće uređeni kroz `DK-*` namespace corrective. `TS-013` ostaje trenutna oznaka tehničke specifikacije Obavještenja.

## 2.1 Tipovi dokumenata i identifikatori dokumentacije

| Skraćenica | Puni naziv | Kratko objašnjenje |
|------------|------------|-------------------|
| **KK-BM** | Poslovni model Kalendara kulture | Dokumentacioni prefiks BM dokumenata KK. Kanonski dokument: **KK-BM-001**. |
| **KK-FS** | Funkcionalna specifikacija Kalendara kulture | Dokumentacioni prefiks FS dokumenata KK. Kanonski dokument: **KK-FS-001**. |
| **KK-TS** | Tehnička specifikacija Kalendara kulture | Dokumentacioni prefiks TS dokumenata KK. Mapa KK-TS-001, KK-TS-003…KK-TS-012: §2.6. |
| **KK-IS** | Implementaciona strategija Kalendara kulture | Dokumentacioni prefiks IS. Kanonski dokument: **KK-IS-001**. |
| **KK-IR** | Implementacioni roadmap Kalendara kulture | Dokumentacioni prefiks IR. Kanonski dokument: **KK-IR-001**. |
| **KK-RG** | Registar skraćenica i oznaka dokumentacije Kalendara kulture | Ovaj dokument. Kanonski dokument: **KK-RG-001**. |
| **KK-FR** | Feature Registry dokument Kalendara kulture | Dokumentacioni ID registra funkcionalnosti. Kanonski dokument: **KK-FR-001**. **Nije** Feature ID (`FT-*`) i **nije** Functional Requirement (`FR-*`). |
| **KK-CR-REG** | Change Request Register dokument Kalendara kulture | Dokumentacioni ID registra zahtjeva. Kanonski dokument: **KK-CR-REG-001**. Pojedinačni zahtjevi ostaju `CR-001` … `CR-004B`. |
| **BM** | Poslovni model (Business Model) | Tip dokumenta: poslovni model (pravila i koncepti), bez implementacije. Poslovne oznake ostaju `BM-01`…`BM-17`, `BM-ORG-*` itd. (bez KK prefiksa). |
| **FS** | Funkcionalna specifikacija (Functional Specification) | Tip dokumenta: šta sistem radi za korisnika. |
| **TS** | Tehnička specifikacija (Technical Specification) | Tip dokumenta: tehnička razrada usvojenog BM/FS. Dokumentacioni ID koristi `KK-TS-*`. |
| **IS** | Implementaciona strategija (Implementation Strategy) | Tip dokumenta: faze, rizici, isporuka, rollback (npr. KK-IS-001 Javni portal). |
| **IR** | Implementacioni roadmap (Implementation Roadmap) | Tip dokumenta: operativni redoslijed i faze realizacije. **KK-IR-001** = Implementation Roadmap — Kalendar kulture V1 (`docs/implementation-strategies/Implementation-Roadmap_Kalendar_kulture.md`). |
| **RG** | Registar skraćenica i oznaka dokumentacije Kalendara kulture | Ovaj dokument (KK-RG-001). |
| **CR** | Zahtjev za izmjenu (Change Request) | Odobreni zahtjev za izmjenu implementacije (npr. CR-001). **Nije** dokumentacioni ID registra. |
| **BR** | Poslovno/funkcionalno pravilo (Business Rule) | Identifikator pravila u FS (npr. BR-102); sljedivost ka BM. Bez KK prefiksa. |
| **FR** | Funkcionalni zahtjev (Functional Requirement) | Identifikator zahtjeva u nekim FS dokumentima (npr. FR-001). **Nije** Feature Registry. |
| **FT** | Feature ID | Jedinstveni ID funkcionalnosti u Feature Registry-ju (npr. FT-001, FT-003). Bez KK prefiksa. |
| **PO** | Product Owner | Uloga / prefiks product odluka (npr. PO-DG-07, PO-ORG-01, PO-AUTO-01, PO-TS9-03A). Bez KK prefiksa. |
| **IA** | Informaciona arhitektura (Information Architecture) | Informaciono-arhitektonska odluka (npr. IA-01). |
| **TD** | Tehnička odluka (Technical Decision) | Tehnička odluka evidentirana u TS (npr. TD-TS9-01). |
| **TO** | Tehnički pregled (Technical Overview) | Pregled trenutne implementacije i odstupanja od BM/FS/TS. |
| **UC** | Use Case | Opis korisničkog scenarija. |
| **UI** | Korisnički interfejs (User Interface) | Često u procjeni uticaja CR-a. |
| **API** | Application Programming Interface | Programski interfejs u dokumentacionom / CR kontekstu projekta. |
| **URL** | Uniform Resource Locator | Adresa / putanja resursa u ugovorima filtera i navigacije. |
| **V1** | Verzija 1 | Prvi dogovoreni obuhvat isporuke; „Out of Scope“ za ono što nije u V1. |
| **NFR** | Nefunkcionalni zahtjev (Non-Functional Requirement) | Npr. poglavlje u TS. |
| **FK** | Strani ključ (Foreign Key) | Relacijska veza u modelu podataka (u TS kontekstu). |
| **CRUD** | Create, Read, Update, Delete | Osnovne operacije nad zapisima (u TS / CRUD poglavljima). |
| **OFD** | Otvoreni nalaz (Open Finding) | Neriješena dokumentaciona stavka. |
| **M-TS** | Metodologija — Technical Specification | Pravilo metodologije za TS dokumente (npr. M-TS-001). |
| **PATCH** | Dokumentaciona zakrpa (Patch) | Identifikator usklađivanja dokumentacije (npr. PATCH-053 u BM). Postojeći `PATCH-001` … `PATCH-077` ostaju legacy/stabilni identifikatori Kalendara kulture; **nema** retroaktivnog `KK-PATCH-*`. |
| **HF** | Hotfix / hitna korektivna ispravka | Generička oznaka hitnog produkcionog korektiva u paketnim oznakama (npr. **MOD-UX-01-HF1**, **MOD-UX-01-HF2**). Ordinal (`HF1`, `HF2`…) nije zasebna skraćenica. |
| **PATCH-FS** | Zakrpa FS (Patch — Functional Specification) | Identifikator zakrpe FS dokumenta (npr. PATCH-FS-053, PATCH-FS-001 … PATCH-FS-077). PATCH-FS namespace ostaje nepromijenjen. |
| **QA** | Osiguranje kvaliteta (Quality Assurance) | Oznaka QA odluke ili korektivnog prolaza (npr. QA-TS0108-01). |
| **AC** | Acceptance Criteria | Identifikator acceptance kriterijuma u TS (npr. AC-NL-01). |
| **GAP** | Gap | Dokumentacioni ili implementacioni jaz; vidi i `G-*`, `G-W*`, `G-NL-*` (§2.4). |
| **MF** | Manifestacija | Dokumentaciona skraćenica za poslovni entitet Manifestacija (BM-05 / KK-TS-005). Prefiks pravila: **BM-MF-***. |
| **OCC** | Occurrence / Održavanje | Tehnička skraćenica za kanonski entitet `CulturalOccurrence`, koji u poslovnoj terminologiji predstavlja **Održavanje**. Ne prevoditi naziv klase. |
| **SSOT** | Single Source of Truth | Jedini mjerodavni izvor podataka/istine za dati domen (npr. public query SSOT). |
| **CTA** | Call to Action | Poziv na akciju u UI/portalskom kontekstu (npr. Hero bez CTA dugmadi). |
| **KK** | Kalendar kulture | Skraćenica naziva modula Kalendar kulture. |
| **MOD** | Moderator | Dokumentaciona skraćenica za **Moderator Organizatora** u paketnim oznakama (npr. **MOD-UX-01**). Poslovna definicija ostaje u BM-MOD / KK-TS-001 / KK-TS-010.3 — KK-RG-001 ne definiše ulogu. |
| **ORG** | Organizator | Dokumentaciona skraćenica za **Organizatora** u paketnim / PO oznakama (npr. **PO-ORG-***). Poslovna definicija ostaje u BM-ORG / KK-TS-001 / KK-TS-010.2. |
| **UX** | Korisničko iskustvo (User Experience) | Dokumentaciona skraćenica / prefiks UX korektiva i navigacionih paketa (npr. **MOD-UX-01**). |
| **CAT-CUTOVER** | Category cutover | Dokumentaciona/implementaciona oznaka cutover-a kategorija na kanonski katalog (PO-TS9-08E). |

**Razlika TS / IS / IR**

| Tip | Uloga |
|-----|--------|
| **TS** | Šta/kako tehnički (specifikacija usvojenog BM/FS). |
| **IS** | Kako se stabilna TS uvodi u produkciju (strategija isporuke). |
| **IR** | Operativni roadmap: redoslijed faza i realizacije (npr. KK-IR-001). |

## 2.2 FR i Feature Registry (obavezno razlikovanje)

| Oznaka / naziv | Značenje | Primjer |
|----------------|----------|---------|
| **FR** | **Functional Requirement** — identifikator zahtjeva unutar FS | FR-001 |
| **Feature Registry** | Dokument / registar funkcionalnosti Kalendara kulture; dokumentacioni ID **KK-FR-001** (**nema** skraćenice FR) | `docs/features/Feature-Registry.md` |
| **FT** | **Feature ID** — ID stavke u Feature Registry-ju Kalendara kulture | FT-001, FT-003 |

**Feature Registry se ne skraćuje kao FR.**
Ako neko kaže „FR“, u Digital Kotor dokumentaciji to znači *Functional Requirement*, ne Feature Registry.
Za registar funkcionalnosti koristi se pun naziv **Feature Registry** (dokument `KK-FR-001`); za identifikatore funkcionalnosti koristi se **FT-***. FT-004 / Obavještenja nijesu aktivni KK Feature.

## 2.3 Prefiksi identifikatora u BM (domeni)

Prefiksi ispod **nisu** poslovne definicije — samo objašnjavaju oznaku ID-a pravila. Status: **AKTIVNO**, osim ako je drugačije navedeno.

| Prefiks | Domen ID-a | Status | Primjer |
|---------|------------|--------|---------|
| **BM-ORG** | Organizator | AKTIVNO | BM-ORG-04 |
| **BM-MOD** | Moderator | AKTIVNO | BM-MOD-16 |
| **BM-UR** | Urednik | AKTIVNO | BM-UR-11 |
| **BM-DG** | Događaj | AKTIVNO | BM-DG-09 |
| **BM-MF** | Manifestacija | AKTIVNO | BM-MF-* |
| **BM-TR** | Održavanje / termini | **AKTIVNO** | BM-TR-12 |
| **BM-LK** | Lokacija | AKTIVNO | BM-LK-* |
| **BM-KO** | Kategorije i oznake | AKTIVNO | BM-KO-* |
| **BM-MD** | Naslovna fotografija (istorijski: Mediji) | **ZASTARJELO** za TS8 katalog/reuse model (BM-MD-01–17). Aktivna pravila: BM-MD-18–36 (PATCH-075 / MED). Sljedivost zadržana. | BM-MD-18 |
| **BM-ST** | Statusi / lifecycle događaja | AKTIVNO | BM-ST-07 |
| **BM-PK** | Portal Kalendara (javni) | AKTIVNO | BM-PK-22 |
| **BM-EP** | Urednički portal (Editorial Portal) | AKTIVNO | BM-EP-* |
| **BM-NL** | Newsletter | AKTIVNO | BM-NL-01 |
| **BM-AL** | Evidencija aktivnosti (Audit Log) | AKTIVNO | BM-AL-* |
| **BM-GL** | Pojmovnik / glosar BM | AKTIVNO | BM-GL-19 |
| **BM-AR** | Arhitektura poslovnih cjelina | AKTIVNO | BM-AR-* |
| **BM-GR** | Opšta poslovna pravila | AKTIVNO | BM-GR-* |

**BM-OB** nije aktivni KK prefiks. Obavještenja / FT-004 pripadaju platformskoj dokumentaciji (`DK-*` corrective pending).

**BM-TR:** Prefiks je **AKTIVAN** za domen Održavanja (npr. BM-TR-12). Slovo „TR“ u imenu je istorijsko (raniji radni naziv „Termin“); to **ne** znači da je prefiks zastario ili neaktivan.

Brojčani ID-evi poglavlja (**BM-01** … **BM-17**) označavaju poglavlja Business Model dokumenta; baza skraćenice ostaje **BM**.

## 2.4 Prefiksi odluka, nalaza, GAP-ova i testova

Prefiksi se **ne** spajaju u jednu generičku oznaku. Svaki ima svoj namespace.

| Prefiks | Tip oznake | Značenje | Dokument / modul | Primjer |
|---------|------------|----------|------------------|---------|
| **PO-DG** | Product Owner odluka | Događaj | BM / FS / Feature Registry | PO-DG-07 |
| **PO-AUTO** | Product Owner odluka | Automatske / sistemske poslovne posljedice Kalendara kulture (npr. cascade otkazivanja, automatski završetak) | BM / FS / KK-TS-003 / KK-TS-004 / KK-TS-010 | PO-AUTO-01, PO-AUTO-02 |
| **PO-ORG** | Product Owner odluka | Organizator / Moderator i model pristupa uredničkom portalu; konkretne odluke: **PO-ORG-01…** | KK-TS-001, BM PATCH-054, FS PATCH-FS-054, Feature Registry | PO-ORG-01 |
| **PO-EV** | Product Owner / implementaciona odluka | Kanonski Event domen (Događaj / Održavanje); postoji npr. **PO-EV-01** | KK-TS-003, KK-IR-001, Feature Registry | PO-EV-01 |
| **PO-MF** | Product Owner odluka | Manifestacija | Feature Registry / KK-TS-005 | PO-MF-01 |
| **PO-LOC** | Product Owner odluka | Lokacije | KK-TS-006 | PO-LOC-01 |
| **PO-AL** | Product Owner odluka | Evidencija aktivnosti | KK-TS-010.7 / KK-TS-012 | PO-AL-01 |
| **PO-DASH** | Product Owner odluka | Dashboard | KK-TS-010.6 | PO-DASH-01 |
| **PO-TS9** | Product Owner odluka | U okviru KK-TS-009 | KK-TS-009 / Feature Registry | PO-TS9-03A |
| **PO-6A** | Product Owner odluka | Faza 6A — javni portal Događaja | KK-TS-009 / Feature Registry | PO-6A09-01, PO-6A11-01 |
| **PO-6B** | Product Owner odluka | Faza 6B — Manifestacije (domen / portal / Pretraga) | BM / FS / KK-TS-005 / KK-TS-009 / Feature Registry | PO-6B-01…05, PO-6B-08, PO-6B-09, PO-6B-10 |
| **PO-CR3** / **PO-CR4A** / **PO-CR4B** | Product Owner odluka | Vezane za CR-003 / CR-004A / CR-004B | KK-TS-009 / KK-IS-001 | PO-CR4A-01 |
| **PO-N-TR** | Product Owner odluka | Zatvara / precizira N-TR pitanje | KK-TS-004 | PO-N-TR-02-01; **PO-N-TR-02-04** |
| **TS7-PO** | Product Owner odluka | Kategorije i oznake (KK-TS-007) | KK-TS-007 | TS7-PO-01 |
| **TS8-** | Product / tehnička odluka | Mediji (KK-TS-008) — **ZASTARJELO / SUPERSEDED** MED-01–MED-28 | KK-TS-008 (istorijski) | TS8-01 |
| **MED** / **MED-*** | Product Owner paket odluka | Naslovna fotografija Događaja i Manifestacije u Kalendaru kulture. **AKTIVNO** (ADOPTED / DOCS CANONICALIZED / IMPLEMENTATION COMPLETE / VERIFIED). Finalni vizuelni set dijelova fallback asseta ostaje projektni follow-up (nije nova RG skraćenica). | BM PATCH-075 / PATCH-076 / FS PATCH-FS-075 / PATCH-FS-076 / KK-TS-003/005/007/009/010 | MED-01 |
| **N-DG** | Otvoreno pitanje / napomena | Događaj | KK-TS-003 / KK-TS-010 | N-DG-02, N-DG-04 |
| **N-MF** | Otvoreno pitanje / napomena | Manifestacija | KK-TS-005 | N-MF-01 |
| **N-TR** | Otvoreno pitanje / napomena | Održavanje | KK-TS-004 | N-TR-02 |
| **G-** / **G-nn** | GAP / nalaz | Gap u KK-TS-010 (urednički portal) | KK-TS-010 | G-11 |
| **G-W** / **G-Wnn** | GAP / workflow nalaz | Workflow gap u KK-TS-010; **G-W02** = otkazivanje Objavljenog događaja čini aktivni prijedlog izmjene neoperativnim | KK-TS-010 §7.12 | G-W02 |
| **G-NL** | GAP / nalaz | Newsletter | KK-TS-011 | G-NL-08 |
| **CR** | Change Request | Odobrena izmjena implementacije | Change Request Register / KK-IS-001 | CR-001 |
| **TD** / **TD-TS9** | Tehnička odluka | Technical Decision u TS | KK-TS-009 | TD-TS9-01 |
| **TM-** | Test | Test Matrix scenario | KK-TS-010.8; KK-TS-009 §18 | TM-PUB-06; TM-JP-01 |
| **T10-** | Implementacioni marker | Implementacioni zadaci / markeri zatvaranja **KK-TS-010** (Urednički portal); nije Feature ID niti TM scenario | Feature Registry / KK-TS-010 | T10-WF-01, T10-GEN-01 |
| **QA** / **QA-TS0108** | QA odluka | Quality Assurance prolaz | KK-TS-010.8 | QA-TS0108-01 |
| **AC-NL** | Acceptance Criteria | Newsletter | KK-TS-011 | AC-NL-01 |
| **V-NL** | Validation | Validaciono pravilo — Newsletter | KK-TS-011 | V-NL-01 |
| **DM-** | Dashboard | Kategorija Moderatora — vidi §2.7 | KK-TS-010.6 | DM-01 |
| **DU-** | Dashboard | Kategorija Urednika — vidi §2.7 | KK-TS-010.6 | DU-01 |
| **KON-LOC** | Konflikt / konzistentnost | Lokacije | KK-TS-006 | KON-LOC-01 |

**G- ≠ G-W ≠ G-NL:** zajedničko slovo „G“, različiti namespace-i i značenja.

## 2.5 Ostale napomene o jednoznačnosti

| Skraćenica | Status / napomena |
|------------|-------------------|
| **FR** | Jednoznačno = Functional Requirement; zabuna sa Feature Registry je terminološki rizik (razriješeno §2.2). |
| **BR** | U FS se koristi kao identifikator pravila (`BR-*`). Kanonska skraćenica ostaje **BR** = Business Rule. |
| **OFD** | Kanonski: **Open Finding** (`OFD-*`). |
| **UR-** vs **BM-UR** | **BM-UR-*** = pravila Urednika u Kalendaru kulture. Prefiks **UR-** (bez BM-) **nije** KK prefiks i nije dio ovog registra. |
| **G-** vs **G-W** vs **G-NL** | **G-nn** = gap u KK-TS-010; **G-Wnn** = workflow gap u KK-TS-010; **G-NL-*** = gap Newsletter. |
| **PO** | Označava Product Owner ulogu i prefiks product odluka (`PO-*`). |
| **BM, FS, TS, IS, IR, CR, TO, FT, IA, TD, UC, UI, API, URL, V1, NFR, FK, CRUD, M-TS, RG, PATCH, QA, AC, GAP, MF, OCC, SSOT, CTA, KK, CAT-CUTOVER** | Nema drugog dokumentacionog značenja u pregledanom korpusu. |

Nema drugih skraćenica u ovom dokumentu sa dva **konkurentna** kanonska značenja pored navedenih napomena.

## 2.6 Mapa KK-TS dokumenata

Kanonski nazivi prema Feature Registry / odgovarajućim TS dokumentima. Lokalna numeracija unutar `KK-*` (M-TS-002). `KK-TS-002` nije dodijeljen. `KK-TS-013` ne postoji (`TS-013` = Obavještenja, platforma).

| Oznaka | Dokument |
|--------|----------|
| **KK-TS-001** | Organizator, Moderator i zahtjev za kreiranje Organizatora |
| **KK-TS-002** | Nije dodijeljen (dokument nije kreiran) |
| **KK-TS-003** | Događaj |
| **KK-TS-004** | Održavanje događaja |
| **KK-TS-005** | Manifestacija |
| **KK-TS-006** | Lokacije |
| **KK-TS-007** | Kategorije i oznake |
| **KK-TS-008** | Mediji — **historijski / SUPERSEDED** (MED-01–MED-28); nije aktivni V1 SSOT |
| **KK-TS-009** | Javni portal |
| **KK-TS-010** | Urednički portal (podcjeline KK-TS-010.1 … KK-TS-010.8) |
| **KK-TS-011** | Newsletter |
| **KK-TS-012** | Evidencija aktivnosti |

## 2.6a Runtime dual-key (`source_module`)

Dokumentacioni ID može biti `KK-TS-003`, dok postojeći runtime/persistirani ključ `source_module = TS-003` ostaje nepromijenjen radi kompatibilnosti i predstavlja tehnički runtime identifikator, ne kanonski dokumentacioni ID.

Isti princip važi za ostale postojeće runtime `source_module` vrijednosti:

| Dokumentacioni ID | Runtime `source_module` |
|-------------------|-------------------------|
| `KK-TS-001` | `TS-001` |
| `KK-TS-003` | `TS-003` |
| `KK-TS-004` | `TS-004` |
| `KK-TS-005` | `TS-005` |
| `KK-TS-011` | `TS-011` |

`TS12-*` ostaju tehnički identifikatori kataloga Evidencije aktivnosti (nisu dokumentacioni ID-evi `KK-TS-*`).

## 2.7 Dashboard oznake — DU / DM

**DU** = Dashboard Urednika / kategorija uredničke radne table (KK-TS-010.6).

| Oznaka | Naziv (KK-TS-010) |
|--------|----------------|
| **DU-01** | Čeka pregled (događaji) |
| **DU-02** | Prijedlozi izmjene na pregledu |
| **DU-03** | Nacrti bez Organizatora |
| **DU-04** | Zahtjevi za Organizatora |
| **DU-05** | Zahtjevi za Moderatore |

**DM** = Dashboard Moderatora (KK-TS-010.6).

| Oznaka | Naziv (KK-TS-010) |
|--------|----------------|
| **DM-01** | Nacrti |
| **DM-02** | Na odobrenju |
| **DM-03** | Aktivni prijedlozi izmjene |

Detaljna semantika brojača i filtera ostaje u **KK-TS-010**; KK-RG-001 samo identifikuje oznake.

## 2.8 Referenca na poslovne pojmove i statuse

KK-RG-001 **ne** definiše lifecycle ni poslovne pojmove.

Statusi i termini kao što su: Nacrt, Na odobrenju, Objavljen, Otkazan, Arhiviran, Planiran, Odgođen, Završen, Aktivna, Neaktivna, Deaktivirana — definišu se u:

* **BM-GL** (pojmovnik BM);
* **BM-ST** (statusi / lifecycle događaja);
* odgovarajućim **FS** / **TS** sekcijama.

---

# 3. Referentni dokumenti

### KK-RG-001-03 — Struktura (dio B)

Samo orijentacija — **ne** prepisuje sadržaj BM/FS/TS/IS/IR.

| Dokument / tip | Svrha | Kada otvoriti |
|----------------|-------|----------------|
| **Business Model (BM)** | Šta je poslovno usvojeno (pravila). | Prije poslovne ili domenske rasprave. |
| **Functional Specification (FS)** | Šta sistem mora raditi (BR / FR). | Kada treba funkcionalno ponašanje, bez koda. |
| **Technical Specification (TS)** | Kako se usvojeni BM/FS tehnički razrađuju. | Prije implementacije ili tehničkog review-a. |
| **Implementation Strategy (IS)** | Kako se TS uvodi u produkciju (faze, rizik, isporuka). | Prije početka implementacionog rada na stabilnoj TS. |
| **Implementation Roadmap (IR)** | Operativni redoslijed i faze realizacije (npr. KK-IR-001). | Pri planiranju / praćenju implementacije Kalendara kulture. |
| **Feature Registry (`KK-FR-001`)** | Registar funkcionalnosti (**FT-ID**); **nije** skraćenica FR. | Orijentacija: koji Feature / TS postoje i status. |
| **Change Request Register (`KK-CR-REG-001`)** | Evidencija odobrenih izmjena implementacije. Pojedinačni ID-evi ostaju `CR-*`. | Prije usklađivanja koda sa FS. |
| **Technical Overview (TO)** | Šta je trenutno implementirano i gdje odstupa od BM/FS/TS. | Kada treba stanje implementacije, ne ciljni model. |
| **Use Cases (UC)** | Korisnički scenariji. | Za scenarije prije FS detalja (gdje postoje). |
| **Metodologija** (`docs/METHODOLOGY.md`) | Pravila pisanja i odnosa dokumenata (npr. M-TS-*). | Pri pisanju ili reviziji BM/FS/TS. |
| **KK-RG-001 (ovaj dokument)** | Skraćenice i oznake dokumentacije Kalendara kulture. | Na ulasku u dokumentaciju KK; nije izvor pravila. |

**Gdje leže u repozitorijumu (orijentacija):**

| Tip | Tipična putanja |
|-----|-----------------|
| BM | `docs/business-model/` |
| FS | `docs/functional-specifications/` |
| TS | `docs/technical-specifications/` |
| IS / IR | `docs/implementation-strategies/` |
| Feature Registry | `docs/features/` |
| Change Request | `docs/change-requests/` |
| Technical Overview | `docs/tehnicka-dokumentacija/` |
| Use Cases | `docs/use-cases/` |
| RG | `docs/reference/` |

---

# 4. Pravila održavanja

### KK-RG-001-04 — Upravljanje

* KK-RG-001 **nije** izvor istine za poslovna ili tehnička pravila.
* Prednost imaju vlasnički dokumenti: BM, FS, TS, IS, IR, Feature Registry, CR, TO.
* Nova skraćenica se dodaje tek kada se **stvarno koristi** u usvojenoj dokumentaciji **i** kada je specifična za projekat (vidi Pravilo obuhvata u §1).
* **Kada se u dokumentaciji Kalendara kulture uvede nova projektna skraćenica ili akronim, Katalog skraćenica (KK-RG-001) mora se ažurirati u istom dokumentacionom paketu.**
* Skraćenice se ne mijenjaju samostalno u KK-RG-001; prvo se usklađuje referentni dokument.
* Zastarjela skraćenica se ne briše bez traga — označava se kao **ZASTARJELO** i upućuje na važeći naziv.
* U KK-RG-001 se **ne** unose: poslovni pojmovnik; opšte tehničke/industrijske skraćenice; poslovni termini koji nisu skraćenice.
* Ako paket eksplicitno zabranjuje izmjene dokumentacije, a uvede novu skraćenicu: prijavi `ABBREVIATION CATALOG UPDATE REQUIRED` i ne krši scope.

### KK-RG-001-05 — Održavanje

KK-RG-001 se ažurira samo kada:

* nastane nova **interna** standardna skraćenica projekta;
* nastane novi tip referentnog dokumenta;
* promijeni se zvanični naziv ili skraćenica;
* skraćenica postane zastarjela;
* dokumentacioni paket uvodi novu projektnu skraćenicu (vidi KK-RG-001-04).

KK-RG-001 se **ne** ažurira zbog: novih funkcionalnosti, poslovnih pravila, kategorija, statusa, uloga ili pojedinačnih modula — osim ako uvedu **novu internu skraćenicu**.

---

# 5. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Nacrt. Usvojene odluke RG-001-01 … RG-001-04. Evidentirane skraćenice i tipovi dokumenata na osnovu postojeće dokumentacije. Bez izmjene BM/FS/TS/IS/implementacije. |
| 0.5.0 | 2026-07-31 | Final Review. Pojašnjeno FR ≠ Feature Registry (FT); napomene o BR/OFD; skraćeni opisi tipova dokumenata; bez novih skraćenica i bez izmjene drugih dokumenata. |
| 1.0.0 | 2026-07-31 | Stable. Dokument je prošao Final Review i predstavlja referentni vodič za skraćenice i dokumentacionu strukturu Digital Kotora. Bez izmjene sadržaja registra, tipova dokumenata ili pravila održavanja. Bez izmjene BM/FS/TS/IS/implementacije. |
| 1.1.0 | 2026-08-07 | Završno usklađivanje sa usvojenim stanjem na `main` (uključujući TS-011 v1.0.1). Naziv usklađen sa „Dokument sa opisom skraćenica“; eksplicitno: nije pojmovnik. Dodati prefiksi i skraćenice u upotrebi. Bez izmjene BM/FS/TS/Feature Registry/CR. Bez izmjene implementacije. |
| 1.1.1 | 2026-08-07 | Administrativni PATCH: uklonjene opšte tehničke/industrijske skraćenice (PDF, SQL, HTTP, JPEG, …); uvedeno Pravilo obuhvata; zadržane/dodate samo interne oznake projekta (uklj. C-UC-OB, TS7-PO, TS8-); RG-001-03 za dio B. Bez izmjene BM/FS/TS/Feature Registry/CR. Bez izmjene implementacije. |
| 1.1.2 | 2026-08-08 | Coverage PATCH: naziv = Registar skraćenica i oznaka dokumentacije; dodati IR/IR-001 i razlika TS/IS/IR; mapa TS-001…TS-012; PO-ORG / PO-EV; G-W / G-W02; DU-01…05 i DM-01…03; BM-TR jasno AKTIVNO; referenca na BM-GL/BM-ST; razdvojeni G-/G-W/G-NL. Bez izmjene BM/FS/TS/Feature Registry/IR sadržaja. Bez izmjene implementacije. |
| 1.1.3 | 2026-08-08 | Dodata familija **PO-AUTO** (Product Owner odluke o automatskim/sistemskim procesima Kalendara kulture); primjeri PO-AUTO-01, PO-AUTO-02. Bez poslovnih definicija statusa. Bez izmjene BM/FS/TS sadržaja u ovom dokumentu. Bez izmjene implementacije. |
| 1.1.4 | 2026-08-08 | Dodata familija **T10-** (implementacioni markeri TS-010; primjeri T10-WF-01, T10-GEN-01). Usklađeno sa Feature Registry closeout / TS7-PO-07 dokumentacionim PATCH-em. Bez izmjene BM/FS sadržaja u ovom dokumentu. Bez izmjene implementacije. |
| 1.1.5 | 2026-08-09 | TM-* primjer dopunjen sa **TM-JP-*** (TS-009 §18 Faza 6A). Bez izmjene implementacije. |
| 1.1.6 | 2026-08-11 | Coverage + maintenance: dodati **MF**, **OCC**, **SSOT**, **CTA**, **KK**, **CAT-CUTOVER**, familije **PO-6A** / **PO-6B**; formalizovano pravilo da se RG-001 ažurira u istom dokumentacionom paketu kad se uvede nova projektna skraćenica. Usklađeno sa PO-6B-08/09 pre-impl docs. Bez izmjene implementacije. |
| 1.1.7 | 2026-08-11 | **PO-6B-10:** primjer familije **PO-6B** dopunjen sa PO-6B-10 (već pokriveno familijom; bez novog abbreviation row-a). Usklađeno sa 6B-04 pre-commit docs sync. Bez izmjene implementacije. |
| 1.1.8 | 2026-08-13 | **MOD-UX-01:** dodate skraćenice **UX**, **MOD**, **ORG** (dokumentacione / paketne; bez poslovnih definicija uloga). Usklađeno sa TS-010 v1.0.10 / Feature Registry status sync. Bez izmjene BM/FS. Bez izmjene implementacije. |
| 1.1.9 | 2026-08-13 | **MOD-UX-01-HF2:** dodata skraćenica **HF** (Hotfix / hitna korektivna ispravka; ordinal HF1/HF2 nije zasebna skraćenica). Bez izmjene BM/FS/TS. Bez izmjene implementacije u ovom dokumentu. |
| 1.1.10 | 2026-08-15 | **MED / MED-*:** registracija PO paketa naslovne fotografije (primjer MED-01). **TS8-** → ZASTARJELO / SUPERSEDED (sljedivost ka MED). **BM-MD** → TS8 model ZASTARJELO; BM-MD-18–36 aktivna naslovna fotografija. **TS-008** u mapi TS → historijski/supersedovan. Usklađeno sa BM PATCH-075 / FS PATCH-FS-075. Bez izmjene implementacije. |
| 1.1.11 | 2026-08-16 | **MED status hygiene (nema nove skraćenice):** AKTIVNO polje MED ažurirano na IMPLEMENTATION COMPLETE / VERIFIED. MED-I1/I2/I3/I4A/I4B/I5 **nisu** registrovani (radni nazivi implementacionih paketa). TS8- / TS-008 SUPERSEDED KEEP. |
| 1.1.12 | 2026-08-16 | **Module boundary:** uklonjene aktivne oznake drugog Digital Kotor modula (BP, BR-P, P-, F-, UR- kao Plaćanje). **TS-002** u mapi TS = numeraciona granica (nije dio KK plana; oznaka zauzeta van ovog modula). Bez opisa sadržaja drugog modula. Feature Registry ovog registra = Kalendar kulture. Bez izmjene implementacije. |
| 1.1.13 | 2026-08-17 | Administrativna migracija dokumentacionog ID-a na `KK-*` namespace. Registrovani `KK-BM` / `KK-FS` / `KK-TS` / `KK-IS` / `KK-IR` / `KK-RG` / `KK-FR` / `KK-CR-REG`. Runtime dual-key (`source_module`). `KK-TS-002` nije dodijeljen. FT-004 / Obavještenja uklonjeni iz aktivnog KK kataloga. PATCH / PATCH-FS KEEP. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |

---

**Kraj dokumenta KK-RG-001 v1.1.13 (Stable)**
