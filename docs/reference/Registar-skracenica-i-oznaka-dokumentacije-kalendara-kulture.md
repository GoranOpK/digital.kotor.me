# Kalendar kulture
# Registar skraćenica i oznaka dokumentacije Kalendara kulture

**Oznaka dokumenta:** RG-001
**Naziv:** Registar skraćenica i oznaka dokumentacije Kalendara kulture
**Status dokumenta:** Stable
**Verzija:** 1.1.6
**Datum:** 2026-08-11

---

# 1. Svrha i način korišćenja

### RG-001-01 — Identitet

RG-001 je **referentni i živi** dokument. Predstavlja **centralni registar skraćenica i oznaka** koje se koriste u dokumentaciji modula **Kalendar kulture**.

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

RG-001:

* **nije poslovni pojmovnik** i ne definiše poslovne ni tehničke koncepte;
* ne definiše poslovna pravila ni funkcionalnosti;
* ne zamjenjuje BM, FS, TS, IS, IR, Feature Registry ili CR;
* samo evidentira skraćenice i identifikatore koji se **stvarno koriste** u usvojenoj dokumentaciji Kalendara kulture.

**Poslovni pojmovi i statusi** (npr. Nacrt, Objavljen, Otkazan, Planiran, Odgođen) definišu se u **BM-GL**, **BM-ST** i odgovarajućim FS/TS sekcijama — vidi §2.8. RG-001 ih samo referencira.

**Kako koristiti:** pročitati §2 za skraćenice, zatim §3 da znate koji tip dokumenta otvoriti. Za sadržaj pravila ići u vlasnički dokument (BM/FS/TS/…).

**Status oznaka u ovom dokumentu:** ako nije drugačije navedeno, prefiks/skraćenica je **AKTIVNO**. **ISTORIJSKO** = još se može pojaviti u starijim zapisima; **ZASTARJELO** = supersedovano (ne brisati bez traga).

---

# 2. Skraćenice

### RG-001-02 — Struktura (dio A)

Uključene su samo **interne** skraćenice projekta koje se **stvarno koriste** u usvojenoj dokumentaciji.

## 2.1 Tipovi dokumenata i identifikatori dokumentacije

| Skraćenica | Puni naziv | Kratko objašnjenje |
|------------|------------|-------------------|
| **BM** | Poslovni model (Business Model) | Tip dokumenta: poslovni model (pravila i koncepti), bez implementacije. |
| **FS** | Funkcionalna specifikacija (Functional Specification) | Tip dokumenta: šta sistem radi za korisnika. |
| **TS** | Tehnička specifikacija (Technical Specification) | Tip dokumenta: tehnička razrada usvojenog BM/FS. Mapa TS-001…TS-012: §2.6. |
| **IS** | Implementaciona strategija (Implementation Strategy) | Tip dokumenta: faze, rizici, isporuka, rollback (npr. IS-001 Javni portal). |
| **IR** | Implementacioni roadmap (Implementation Roadmap) | Tip dokumenta: operativni redoslijed i faze realizacije. **IR-001** = Implementation Roadmap — Kalendar kulture V1 (`docs/implementation-strategies/Implementation-Roadmap_Kalendar_kulture.md`). |
| **RG** | Registar skraćenica i oznaka dokumentacije Kalendara kulture | Ovaj dokument (RG-001). |
| **CR** | Zahtjev za izmjenu (Change Request) | Odobreni zahtjev za izmjenu implementacije (npr. CR-001). |
| **BR** | Poslovno/funkcionalno pravilo (Business Rule) | Identifikator pravila u FS (npr. BR-102); sljedivost ka BM. |
| **FR** | Funkcionalni zahtjev (Functional Requirement) | Identifikator zahtjeva u nekim FS dokumentima (npr. FR-001, FR-OB-001). **Nije** Feature Registry. |
| **FT** | Feature ID | Jedinstveni ID funkcionalnosti u Feature Registry-ju (npr. FT-001). |
| **PO** | Product Owner | Uloga / prefiks product odluka (npr. PO-DG-07, PO-ORG-01, PO-AUTO-01, PO-TS9-03A). |
| **IA** | Informaciona arhitektura (Information Architecture) | Informaciono-arhitektonska odluka (npr. IA-01). |
| **TD** | Tehnička odluka (Technical Decision) | Tehnička odluka evidentirana u TS (npr. TD-TS9-01). |
| **TO** | Tehnički pregled (Technical Overview) | Pregled trenutne implementacije i odstupanja od BM/FS/TS. |
| **UC** | Use Case | Opis korisničkog scenarija (npr. UC-OB-001). |
| **UI** | Korisnički interfejs (User Interface) | Često u procjeni uticaja CR-a. |
| **API** | Application Programming Interface | Programski interfejs u dokumentacionom / CR kontekstu projekta. |
| **URL** | Uniform Resource Locator | Adresa / putanja resursa u ugovorima filtera i navigacije. |
| **V1** | Verzija 1 | Prvi dogovoreni obuhvat isporuke; „Out of Scope“ za ono što nije u V1. |
| **NFR** | Nefunkcionalni zahtjev (Non-Functional Requirement) | Npr. poglavlje u TS. |
| **FK** | Strani ključ (Foreign Key) | Relacijska veza u modelu podataka (u TS kontekstu). |
| **CRUD** | Create, Read, Update, Delete | Osnovne operacije nad zapisima (u TS / CRUD poglavljima). |
| **OFD** | Otvoreni nalaz (Open Finding) | Neriješena dokumentaciona stavka (npr. OFD-OB-006). |
| **M-TS** | Metodologija — Technical Specification | Pravilo metodologije za TS dokumente (npr. M-TS-001). |
| **PATCH** | Dokumentaciona zakrpa (Patch) | Identifikator usklađivanja dokumentacije (npr. PATCH-053 u BM). |
| **PATCH-FS** | Zakrpa FS (Patch — Functional Specification) | Identifikator zakrpe FS dokumenta (npr. PATCH-FS-053). |
| **QA** | Osiguranje kvaliteta (Quality Assurance) | Oznaka QA odluke ili korektivnog prolaza (npr. QA-TS0108-01). |
| **BP** | Poslovni proces — Plaćanja (Business Process) | Poslovna odluka / proces u BM/FS Plaćanja (npr. BP-01 … BP-09). |
| **AC** | Acceptance Criteria | Identifikator acceptance kriterijuma u TS (npr. AC-NL-01). |
| **GAP** | Gap | Dokumentacioni ili implementacioni jaz; vidi i `G-*`, `G-W*`, `G-NL-*` (§2.4). |
| **MF** | Manifestacija | Dokumentaciona skraćenica za poslovni entitet Manifestacija (BM-05 / TS-005). Prefiks pravila: **BM-MF-***. |
| **OCC** | Occurrence / Održavanje | Tehnička skraćenica za kanonski entitet `CulturalOccurrence`, koji u poslovnoj terminologiji predstavlja **Održavanje**. Ne prevoditi naziv klase. |
| **SSOT** | Single Source of Truth | Jedini mjerodavni izvor podataka/istine za dati domen (npr. public query SSOT). |
| **CTA** | Call to Action | Poziv na akciju u UI/portalskom kontekstu (npr. Hero bez CTA dugmadi). |
| **KK** | Kalendar kulture | Skraćenica naziva modula Kalendar kulture. |
| **CAT-CUTOVER** | Category cutover | Dokumentaciona/implementaciona oznaka cutover-a kategorija na kanonski katalog (PO-TS9-08E). |

**Razlika TS / IS / IR**

| Tip | Uloga |
|-----|--------|
| **TS** | Šta/kako tehnički (specifikacija usvojenog BM/FS). |
| **IS** | Kako se stabilna TS uvodi u produkciju (strategija isporuke). |
| **IR** | Operativni roadmap: redoslijed faza i realizacije (npr. IR-001). |

## 2.2 FR i Feature Registry (obavezno razlikovanje)

| Oznaka / naziv | Značenje | Primjer |
|----------------|----------|---------|
| **FR** | **Functional Requirement** — identifikator zahtjeva unutar FS | FR-001, FR-OB-001 |
| **Feature Registry** | Dokument / registar funkcionalnosti (pun naziv; **nema** skraćenice FR) | `docs/features/Feature-Registry.md` |
| **FT** | **Feature ID** — ID stavke u Feature Registry-ju | FT-001, FT-004 |

**Feature Registry se ne skraćuje kao FR.**
Ako neko kaže „FR“, u Digital Kotor dokumentaciji to znači *Functional Requirement*, ne Feature Registry.
Za registar funkcionalnosti koristi se pun naziv **Feature Registry**; za identifikatore funkcionalnosti koristi se **FT-***.

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
| **BM-MD** | Mediji | AKTIVNO | BM-MD-* |
| **BM-ST** | Statusi / lifecycle događaja | AKTIVNO | BM-ST-07 |
| **BM-PK** | Portal Kalendara (javni) | AKTIVNO | BM-PK-22 |
| **BM-EP** | Urednički portal (Editorial Portal) | AKTIVNO | BM-EP-* |
| **BM-NL** | Newsletter | AKTIVNO | BM-NL-01 |
| **BM-AL** | Evidencija aktivnosti (Audit Log) | AKTIVNO | BM-AL-* |
| **BM-GL** | Pojmovnik / glosar BM | AKTIVNO | BM-GL-19 |
| **BM-AR** | Arhitektura poslovnih cjelina | AKTIVNO | BM-AR-* |
| **BM-GR** | Opšta poslovna pravila | AKTIVNO | BM-GR-* |
| **BM-OB** | Obavještenja | AKTIVNO | BM-OB-* |

**BM-TR:** Prefiks je **AKTIVAN** za domen Održavanja (npr. BM-TR-12). Slovo „TR“ u imenu je istorijsko (raniji radni naziv „Termin“); to **ne** znači da je prefiks zastario ili neaktivan.

Brojčani ID-evi poglavlja (**BM-01** … **BM-17**) označavaju poglavlja Business Model dokumenta; baza skraćenice ostaje **BM**.

## 2.4 Prefiksi odluka, nalaza, GAP-ova i testova

Prefiksi se **ne** spajaju u jednu generičku oznaku. Svaki ima svoj namespace.

| Prefiks | Tip oznake | Značenje | Dokument / modul | Primjer |
|---------|------------|----------|------------------|---------|
| **PO-DG** | Product Owner odluka | Događaj | BM / FS / Feature Registry | PO-DG-07 |
| **PO-AUTO** | Product Owner odluka | Automatske / sistemske poslovne posljedice Kalendara kulture (npr. cascade otkazivanja, automatski završetak) | BM / FS / TS-003 / TS-004 / TS-010 | PO-AUTO-01, PO-AUTO-02 |
| **PO-ORG** | Product Owner odluka | Organizator / Moderator i model pristupa uredničkom portalu; konkretne odluke: **PO-ORG-01…** | TS-001, BM PATCH-054, FS PATCH-FS-054, Feature Registry | PO-ORG-01 |
| **PO-EV** | Product Owner / implementaciona odluka | Kanonski Event domen (Događaj / Održavanje); postoji npr. **PO-EV-01** | TS-003, IR-001, Feature Registry | PO-EV-01 |
| **PO-MF** | Product Owner odluka | Manifestacija | Feature Registry / TS-005 | PO-MF-01 |
| **PO-LOC** | Product Owner odluka | Lokacije | TS-006 | PO-LOC-01 |
| **PO-OB** | Product Owner odluka | Obavještenja | FT-004 dokumentacija | PO-OB-* |
| **PO-AL** | Product Owner odluka | Evidencija aktivnosti | TS-010.7 / TS-012 | PO-AL-01 |
| **PO-DASH** | Product Owner odluka | Dashboard | TS-010.6 | PO-DASH-01 |
| **PO-TS9** | Product Owner odluka | U okviru TS-009 | TS-009 / Feature Registry | PO-TS9-03A |
| **PO-6A** | Product Owner odluka | Faza 6A — javni portal Događaja | TS-009 / Feature Registry | PO-6A09-01, PO-6A11-01 |
| **PO-6B** | Product Owner odluka | Faza 6B — Manifestacije (domen / portal / Pretraga) | BM / FS / TS-005 / TS-009 / Feature Registry | PO-6B-01…05, PO-6B-08, PO-6B-09 |
| **PO-CR3** / **PO-CR4A** / **PO-CR4B** | Product Owner odluka | Vezane za CR-003 / CR-004A / CR-004B | TS-009 / IS-001 | PO-CR4A-01 |
| **PO-N-TR** | Product Owner odluka | Zatvara / precizira N-TR pitanje | TS-004 | PO-N-TR-02-01; **PO-N-TR-02-04** |
| **TS7-PO** | Product Owner odluka | Kategorije i oznake (TS-007) | TS-007 | TS7-PO-01 |
| **TS8-** | Product / tehnička odluka | Mediji (TS-008) | TS-008 | TS8-01 |
| **N-DG** | Otvoreno pitanje / napomena | Događaj | TS-003 / TS-010 | N-DG-02, N-DG-04 |
| **N-MF** | Otvoreno pitanje / napomena | Manifestacija | TS-005 | N-MF-01 |
| **N-TR** | Otvoreno pitanje / napomena | Održavanje | TS-004 | N-TR-02 |
| **G-** / **G-nn** | GAP / nalaz | Gap u TS-010 (urednički portal) | TS-010 | G-11 |
| **G-W** / **G-Wnn** | GAP / workflow nalaz | Workflow gap u TS-010; **G-W02** = otkazivanje Objavljenog događaja čini aktivni prijedlog izmjene neoperativnim | TS-010 §7.12 | G-W02 |
| **G-NL** | GAP / nalaz | Newsletter | TS-011 | G-NL-08 |
| **CR** | Change Request | Odobrena izmjena implementacije | Change Request Register / IS-001 | CR-001 |
| **TD** / **TD-TS9** | Tehnička odluka | Technical Decision u TS | TS-009 | TD-TS9-01 |
| **TM-** | Test | Test Matrix scenario | TS-010.8; TS-009 §18 | TM-PUB-06; TM-JP-01 |
| **T10-** | Implementacioni marker | Implementacioni zadaci / markeri zatvaranja **TS-010** (Urednički portal); nije Feature ID niti TM scenario | Feature Registry / TS-010 | T10-WF-01, T10-GEN-01 |
| **QA** / **QA-TS0108** | QA odluka | Quality Assurance prolaz | TS-010.8 | QA-TS0108-01 |
| **AC-NL** | Acceptance Criteria | Newsletter | TS-011 | AC-NL-01 |
| **V-NL** | Validation | Validaciono pravilo — Newsletter | TS-011 | V-NL-01 |
| **DM-** | Dashboard | Kategorija Moderatora — vidi §2.7 | TS-010.6 | DM-01 |
| **DU-** | Dashboard | Kategorija Urednika — vidi §2.7 | TS-010.6 | DU-01 |
| **BR-P** | Business Rule | Plaćanja | FS Plaćanja | BR-P-* |
| **P-** | Projektno načelo | Plaćanja | BM/FS Plaćanja | P-01 … P-08 |
| **F-** | Funkcionalna odluka | Plaćanja | BM/FS Plaćanja | F-01 |
| **UR-** | Uplatni računi | Plaćanja (**≠** BM-UR) | BM/FS Plaćanja | UR-01 |
| **FR-OB** / **UC-OB** / **OFD-OB** / **NC-OB** | FR / UC / OFD / negative case | Obavještenja | FS/UC Obavještenja | FR-OB-001 |
| **C-UC-OB** | Candidate use case | Obavještenja | UC Obavještenja | C-UC-OB-001 |
| **FS-OB-FLOW** | Tok u FS | Obavještenja | FS Obavještenja | FS-OB-FLOW-01 |
| **KON-LOC** | Konflikt / konzistentnost | Lokacije | TS-006 | KON-LOC-01 |

**G- ≠ G-W ≠ G-NL:** zajedničko slovo „G“, različiti namespace-i i značenja.

## 2.5 Ostale napomene o jednoznačnosti

| Skraćenica | Status / napomena |
|------------|-------------------|
| **FR** | Jednoznačno = Functional Requirement; zabuna sa Feature Registry je terminološki rizik (razriješeno §2.2). |
| **BR** | U FS se koristi kao identifikator pravila (`BR-*`). Kanonska skraćenica ostaje **BR** = Business Rule. |
| **OFD** | Kanonski: **Open Finding** (`OFD-*`). |
| **UR-** vs **BM-UR** | **UR-01** = uplatni računi (Plaćanja). **BM-UR-*** = pravila Urednika u Kalendaru kulture. Nisu ista stvar. |
| **G-** vs **G-W** vs **G-NL** | **G-nn** = gap u TS-010; **G-Wnn** = workflow gap u TS-010; **G-NL-*** = gap Newsletter. |
| **PO** | Označava Product Owner ulogu i prefiks product odluka (`PO-*`). |
| **BM, FS, TS, IS, IR, CR, TO, FT, IA, TD, UC, UI, API, URL, V1, NFR, FK, CRUD, M-TS, RG, PATCH, QA, BP, AC, GAP, MF, OCC, SSOT, CTA, KK, CAT-CUTOVER** | Nema drugog dokumentacionog značenja u pregledanom korpusu. |

Nema drugih skraćenica u ovom dokumentu sa dva **konkurentna** kanonska značenja pored navedenih napomena.

## 2.6 Mapa TS-001 … TS-012

Kanonski nazivi prema Feature Registry / odgovarajućim TS dokumentima. Globalna numeracija (M-TS-002).

| Oznaka | Modul |
|--------|--------|
| **TS-001** | Organizator, Moderator i zahtjev za kreiranje Organizatora |
| **TS-002** | Plaćanja (FT-002; nije dio KK implementacionog plana FT-001) |
| **TS-003** | Događaj |
| **TS-004** | Održavanje događaja |
| **TS-005** | Manifestacija |
| **TS-006** | Lokacije |
| **TS-007** | Kategorije i oznake |
| **TS-008** | Mediji |
| **TS-009** | Javni portal |
| **TS-010** | Urednički portal (podcjeline TS-010.1 … TS-010.8) |
| **TS-011** | Newsletter |
| **TS-012** | Evidencija aktivnosti |

## 2.7 Dashboard oznake — DU / DM

**DU** = Dashboard Urednika / kategorija uredničke radne table (TS-010.6).

| Oznaka | Naziv (TS-010) |
|--------|----------------|
| **DU-01** | Čeka pregled (događaji) |
| **DU-02** | Prijedlozi izmjene na pregledu |
| **DU-03** | Nacrti bez Organizatora |
| **DU-04** | Zahtjevi za Organizatora |
| **DU-05** | Zahtjevi za Moderatore |

**DM** = Dashboard Moderatora (TS-010.6).

| Oznaka | Naziv (TS-010) |
|--------|----------------|
| **DM-01** | Nacrti |
| **DM-02** | Na odobrenju |
| **DM-03** | Aktivni prijedlozi izmjene |

Detaljna semantika brojača i filtera ostaje u **TS-010**; RG-001 samo identifikuje oznake.

## 2.8 Referenca na poslovne pojmove i statuse

RG-001 **ne** definiše lifecycle ni poslovne pojmove.

Statusi i termini kao što su: Nacrt, Na odobrenju, Objavljen, Otkazan, Arhiviran, Planiran, Odgođen, Završen, Aktivna, Neaktivna, Deaktivirana — definišu se u:

* **BM-GL** (pojmovnik BM);
* **BM-ST** (statusi / lifecycle događaja);
* odgovarajućim **FS** / **TS** sekcijama.

---

# 3. Referentni dokumenti

### RG-001-03 — Struktura (dio B)

Samo orijentacija — **ne** prepisuje sadržaj BM/FS/TS/IS/IR.

| Dokument / tip | Svrha | Kada otvoriti |
|----------------|-------|----------------|
| **Business Model (BM)** | Šta je poslovno usvojeno (pravila). | Prije poslovne ili domenske rasprave. |
| **Functional Specification (FS)** | Šta sistem mora raditi (BR / FR). | Kada treba funkcionalno ponašanje, bez koda. |
| **Technical Specification (TS)** | Kako se usvojeni BM/FS tehnički razrađuju. | Prije implementacije ili tehničkog review-a. |
| **Implementation Strategy (IS)** | Kako se TS uvodi u produkciju (faze, rizik, isporuka). | Prije početka implementacionog rada na stabilnoj TS. |
| **Implementation Roadmap (IR)** | Operativni redoslijed i faze realizacije (npr. IR-001). | Pri planiranju / praćenju implementacije Kalendara kulture. |
| **Feature Registry** | Registar funkcionalnosti (**FT-ID**); **nije** skraćenica FR. | Orijentacija: koji Feature / TS postoje i status. |
| **Change Request (CR)** | Evidencija odobrenih izmjena implementacije. | Prije usklađivanja koda sa FS. |
| **Technical Overview (TO)** | Šta je trenutno implementirano i gdje odstupa od BM/FS/TS. | Kada treba stanje implementacije, ne ciljni model. |
| **Use Cases (UC)** | Korisnički scenariji. | Za scenarije prije FS detalja (gdje postoje). |
| **Metodologija** (`docs/METHODOLOGY.md`) | Pravila pisanja i odnosa dokumenata (npr. M-TS-*). | Pri pisanju ili reviziji BM/FS/TS. |
| **RG-001 (ovaj dokument)** | Skraćenice i oznake dokumentacije Kalendara kulture. | Na ulasku u dokumentaciju KK; nije izvor pravila. |

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

### RG-001-04 — Upravljanje

* RG-001 **nije** izvor istine za poslovna ili tehnička pravila.
* Prednost imaju vlasnički dokumenti: BM, FS, TS, IS, IR, Feature Registry, CR, TO.
* Nova skraćenica se dodaje tek kada se **stvarno koristi** u usvojenoj dokumentaciji **i** kada je specifična za projekat (vidi Pravilo obuhvata u §1).
* **Kada se u dokumentaciji Kalendara kulture uvede nova projektna skraćenica ili akronim, Katalog skraćenica (RG-001) mora se ažurirati u istom dokumentacionom paketu.**
* Skraćenice se ne mijenjaju samostalno u RG-001; prvo se usklađuje referentni dokument.
* Zastarjela skraćenica se ne briše bez traga — označava se kao **ZASTARJELO** i upućuje na važeći naziv.
* U RG-001 se **ne** unose: poslovni pojmovnik; opšte tehničke/industrijske skraćenice; poslovni termini koji nisu skraćenice.
* Ako paket eksplicitno zabranjuje izmjene dokumentacije, a uvede novu skraćenicu: prijavi `ABBREVIATION CATALOG UPDATE REQUIRED` i ne krši scope.

### RG-001-05 — Održavanje

RG-001 se ažurira samo kada:

* nastane nova **interna** standardna skraćenica projekta;
* nastane novi tip referentnog dokumenta;
* promijeni se zvanični naziv ili skraćenica;
* skraćenica postane zastarjela;
* dokumentacioni paket uvodi novu projektnu skraćenicu (vidi RG-001-04).

RG-001 se **ne** ažurira zbog: novih funkcionalnosti, poslovnih pravila, kategorija, statusa, uloga ili pojedinačnih modula — osim ako uvedu **novu internu skraćenicu**.

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

---

**Kraj dokumenta RG-001 v1.1.6 (Stable)**
