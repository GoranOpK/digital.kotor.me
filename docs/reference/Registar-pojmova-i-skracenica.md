# Digital Kotor
# Dokument sa opisom skraćenica

**Oznaka dokumenta:** RG-001  
**Naziv:** Dokument sa opisom skraćenica Digital Kotor  
**Status dokumenta:** Stable  
**Verzija:** 1.1.1  
**Datum:** 2026-08-07

---

# 1. Svrha i način korišćenja

### RG-001-01 — Identitet

RG-001 je **referentni i živi** dokument. Služi kao kratki orijentacioni vodič kroz **skraćenice** i dokumentacione identifikatore Digital Kotora.

**Pravilo obuhvata**

Dokument sa opisom skraćenica sadrži isključivo skraćenice i identifikatore koji su specifični za dokumentaciju i poslovni model projekta Digital Kotor.

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

* **nije pojmovnik** i ne definiše poslovne ni tehničke koncepte;
* ne definiše poslovna pravila ni funkcionalnosti;
* ne zamjenjuje BM, FS, TS, IS ili Feature Registry;
* samo evidentira skraćenice i identifikatore koji se **stvarno koriste** u usvojenoj dokumentaciji projekta.

**Kako koristiti:** pročitati §2 za skraćenice, zatim §3 da znate koji tip dokumenta otvoriti. Za sadržaj pravila ići u vlasnički dokument (BM/FS/TS/…).

---

# 2. Skraćenice

### RG-001-02 — Struktura (dio A)

Uključene su samo **interne** skraćenice projekta koje se **stvarno koriste** u usvojenoj dokumentaciji.

## 2.1 Tipovi dokumenata i identifikatori dokumentacije

| Skraćenica | Puni naziv | Kratko objašnjenje |
|------------|------------|-------------------|
| **BM** | Business Model | Tip dokumenta: poslovni model (pravila i koncepti), bez implementacije. |
| **FS** | Functional Specification | Tip dokumenta: funkcionalna specifikacija — šta sistem radi za korisnika. |
| **TS** | Technical Specification | Tip dokumenta: tehnička specifikacija usvojenog BM/FS. |
| **IS** | Implementation Strategy | Tip dokumenta: implementaciona strategija (faze, rizici, isporuka, rollback). |
| **RG** | Dokument sa opisom skraćenica | Ovaj dokument (RG-001); orijentacija kroz skraćenice i tipove dokumenata. |
| **CR** | Change Request | Odobreni zahtjev za izmjenu implementacije (npr. CR-001). |
| **BR** | Business Rule | Identifikator pravila u FS (npr. BR-102); sljedivost ka BM. |
| **FR** | Functional Requirement | Identifikator funkcionalnog zahtjeva u nekim FS dokumentima (npr. FR-001, FR-OB-001). |
| **FT** | Feature ID | Jedinstveni ID funkcionalnosti u Feature Registry-ju (npr. FT-001). |
| **PO** | Product Owner | Uloga / prefiks product odluka (npr. PO-DG-07, PO-TS9-03A). |
| **IA** | Information Architecture | Informaciono-arhitektonska odluka (npr. IA-01). |
| **TD** | Technical Decision | Tehnička odluka evidentirana u TS (npr. TD-TS9-01). |
| **TO** | Technical Overview | Pregled trenutne implementacije i odstupanja od BM/FS/TS. |
| **UC** | Use Case | Opis korisničkog scenarija (npr. UC-OB-001). |
| **UI** | User Interface | Korisnički interfejs; često u procjeni uticaja CR-a. |
| **API** | Application Programming Interface | Programski interfejs u dokumentacionom / CR kontekstu projekta. |
| **URL** | Uniform Resource Locator | Adresa / putanja resursa u ugovorima filtera i navigacije projekta. |
| **V1** | Verzija 1 | Prvi dogovoreni obuhvat isporuke; „Out of Scope“ za ono što nije u V1. |
| **NFR** | Non-Functional Requirement | Nefunkcionalni zahtjev (npr. poglavlje u TS). |
| **FK** | Foreign Key | Strani ključ / relacijska veza u modelu podataka (u TS kontekstu). |
| **CRUD** | Create, Read, Update, Delete | Osnovne operacije nad zapisima (u TS / CRUD poglavljima). |
| **OFD** | Open Finding | Otvoreni nalaz / neriješena dokumentaciona stavka (npr. OFD-OB-006). |
| **M-TS** | Methodology — Technical Specification | Pravilo metodologije za TS dokumente (npr. M-TS-001). |
| **PATCH** | Patch | Identifikator dokumentacione zakrpe / usklađivanja (npr. PATCH-053). |
| **PATCH-FS** | Patch — Functional Specification | Identifikator zakrpe FS dokumenta (npr. PATCH-FS-053). |
| **QA** | Quality Assurance | Oznaka QA odluke ili korektivnog prolaza (npr. QA-TS0108-01). |
| **BP** | Business Process (Plaćanja) | Poslovna odluka / proces u BM/FS Plaćanja (npr. BP-01 … BP-09). |
| **AC** | Acceptance Criteria | Identifikator acceptance kriterijuma u TS (npr. AC-NL-01). |
| **GAP** | Gap | Dokumentacioni ili implementacioni jaz; u TS-010 i kao `G-*` / `G-NL-*`. |

## 2.2 FR i Feature Registry (obavezno razlikovanje)

| Oznaka / naziv | Značenje | Primjer |
|----------------|----------|---------|
| **FR** | **Functional Requirement** — identifikator zahtjeva unutar FS | FR-001, FR-OB-001 |
| **Feature Registry** | Dokument / registar funkcionalnosti | `docs/features/Feature-Registry.md` |
| **FT** | **Feature ID** — ID stavke u Feature Registry-ju | FT-001, FT-004 |

**Feature Registry se ne skraćuje kao FR.**  
Ako neko kaže „FR“, u Digital Kotor dokumentaciji to znači *Functional Requirement*, ne Feature Registry.  
Za registar funkcionalnosti koristi se pun naziv **Feature Registry**; za identifikatore funkcionalnosti koristi se **FT-***.

## 2.3 Prefiksi identifikatora u BM (domeni)

Prefiksi ispod **nisu** poslovne definicije — samo objašnjavaju oznaku ID-a pravila.

| Prefiks | Domen ID-a | Primjer |
|---------|------------|---------|
| **BM-ORG** | Organizator | BM-ORG-04 |
| **BM-MOD** | Moderator | BM-MOD-16 |
| **BM-UR** | Urednik | BM-UR-11 |
| **BM-DG** | Događaj | BM-DG-09 |
| **BM-MF** | Manifestacija | BM-MF-* |
| **BM-TR** | Održavanje (istorijski prefiks TR) | BM-TR-12 |
| **BM-LK** | Lokacija | BM-LK-* |
| **BM-KO** | Kategorije i oznake | BM-KO-* |
| **BM-MD** | Mediji | BM-MD-* |
| **BM-ST** | Statusi / lifecycle događaja | BM-ST-07 |
| **BM-PK** | Portal Kalendara (javni) | BM-PK-22 |
| **BM-EP** | Urednički portal (Editorial Portal) | BM-EP-* |
| **BM-NL** | Newsletter | BM-NL-01 |
| **BM-AL** | Evidencija aktivnosti (Audit Log) | BM-AL-* |
| **BM-GL** | Pojmovnik / glosar BM | BM-GL-19 |
| **BM-AR** | Arhitektura poslovnih cjelina | BM-AR-* |
| **BM-GR** | Opšta poslovna pravila | BM-GR-* |
| **BM-OB** | Obavještenja | BM-OB-* |

Brojčani ID-evi poglavlja (**BM-01** … **BM-17**) označavaju poglavlja Business Model dokumenta; baza skraćenice ostaje **BM**.

## 2.4 Prefiksi odluka, nalaza i testova

| Prefiks | Značenje | Primjer |
|---------|----------|---------|
| **PO-DG** | Product odluka — Događaj | PO-DG-07 |
| **PO-MF** | Product odluka — Manifestacija | PO-MF-01 |
| **PO-LOC** | Product odluka — Lokacije | PO-LOC-01 |
| **PO-OB** | Product odluka — Obavještenja | PO-OB-* |
| **PO-AL** | Product odluka — Evidencija aktivnosti | PO-AL-01 |
| **PO-DASH** | Product odluka — Dashboard | PO-DASH-01 |
| **PO-TS9** | Product odluka u okviru TS-009 | PO-TS9-03A |
| **PO-CR3** / **PO-CR4A** / **PO-CR4B** | Product odluke vezane za CR-003 / CR-004A / CR-004B | PO-CR4A-01 |
| **PO-N-TR** | Product odluka koja zatvara N-TR pitanje | PO-N-TR-02-01 |
| **TS7-PO** | Product odluka — Kategorije i oznake (TS-007) | TS7-PO-01 |
| **TS8-** | Product / tehnička odluka — Mediji (TS-008) | TS8-01 |
| **N-DG** | Otvoreno pitanje / napomena — Događaj | N-DG-02 |
| **N-MF** | Otvoreno pitanje — Manifestacija | N-MF-01 |
| **N-TR** | Otvoreno pitanje — Održavanje | N-TR-02 |
| **G-** / **G-nn** | Gap / nalaz u TS-010 | G-11 |
| **G-NL** | Gap / nalaz — Newsletter | G-NL-08 |
| **AC-NL** | Acceptance Criteria — Newsletter | AC-NL-01 |
| **V-NL** | Validation (validaciono pravilo) — Newsletter | V-NL-01 |
| **TM-** | Test Matrix scenario (TS-010) | TM-PUB-06 |
| **DM-** | Dashboard kategorija — Moderator | DM-01 |
| **DU-** | Dashboard kategorija — Urednik | DU-01 |
| **BR-P** | Business Rule — Plaćanja | BR-P-* |
| **P-** | Projektno načelo — Plaćanja | P-01 … P-08 |
| **F-** | Funkcionalna odluka — Plaćanja | F-01 |
| **UR-** | Uplatni računi (Plaćanja) | UR-01 |
| **FR-OB** / **UC-OB** / **OFD-OB** / **NC-OB** | Obavještenja — FR / UC / OFD / negative case | FR-OB-001 |
| **C-UC-OB** | Candidate / otvoreni use case — Obavještenja | C-UC-OB-001 |
| **FS-OB-FLOW** | Tok u FS Obavještenja | FS-OB-FLOW-01 |
| **KON-LOC** | Konflikt / konzistentnost — Lokacije | KON-LOC-01 |
| **QA-TS0108** | QA odluka za TS-010.8 | QA-TS0108-01 |
| **TD-TS9** | Technical Decision u okviru TS-009 | TD-TS9-01 |

## 2.5 Ostale napomene o jednoznačnosti

| Skraćenica | Status |
|------------|--------|
| **FR** | Jednoznačno = Functional Requirement; zabuna sa Feature Registry je terminološki rizik (razriješeno §2.2). |
| **BR** | U FS se koristi kao identifikator pravila (`BR-*`). Kanonska skraćenica ostaje **BR** = Business Rule. |
| **OFD** | Kanonski: **Open Finding** (`OFD-*`). |
| **UR-** vs **BM-UR** | **UR-01** = uplatni računi (Plaćanja). **BM-UR-*** = pravila Urednika u Kalendaru kulture. Nisu ista stvar. |
| **G-** vs **G-NL** | **G-nn** = gap u TS-010. **G-NL-*** = gap Newsletter. Zajedničko slovo „G“, različiti namespace-i. |
| **PO** | Označava Product Owner ulogu i prefiks product odluka (`PO-*`). |
| **BM, FS, TS, IS, CR, TO, FT, IA, TD, UC, UI, API, URL, V1, NFR, FK, CRUD, M-TS, RG, PATCH, QA, BP, AC, GAP** | Nema drugog dokumentacionog značenja u pregledanom korpusu. |

Nema drugih skraćenica u ovom dokumentu sa dva **konkurentna** kanonska značenja pored navedenih napomena.

---

# 3. Referentni dokumenti

### RG-001-03 — Struktura (dio B)

Samo orijentacija — **ne** prepisuje sadržaj BM/FS/TS/IS.

| Dokument / tip | Svrha | Kada otvoriti |
|----------------|-------|----------------|
| **Business Model (BM)** | Šta je poslovno usvojeno (pravila). | Prije poslovne ili domenske rasprave. |
| **Functional Specification (FS)** | Šta sistem mora raditi (BR / FR). | Kada treba funkcionalno ponašanje, bez koda. |
| **Technical Specification (TS)** | Kako se usvojeni BM/FS tehnički razrađuju. | Prije implementacije ili tehničkog review-a. |
| **Implementation Strategy (IS)** | Kako se TS uvodi u produkciju (faze, rizik, isporuka). | Prije početka implementacionog rada na stabilnoj TS. |
| **Feature Registry** | Registar funkcionalnosti (**FT-ID**); **nije** skraćenica FR. | Orijentacija: koji Feature / TS postoje i status. |
| **Change Request (CR)** | Evidencija odobrenih izmjena implementacije. | Prije usklađivanja koda sa FS. |
| **Technical Overview (TO)** | Šta je trenutno implementirano i gdje odstupa od BM/FS/TS. | Kada treba stanje implementacije, ne ciljni model. |
| **Use Cases (UC)** | Korisnički scenariji. | Za scenarije prije FS detalja (gdje postoje). |
| **Metodologija** (`docs/METHODOLOGY.md`) | Pravila pisanja i odnosa dokumenata (npr. M-TS-*). | Pri pisanju ili reviziji BM/FS/TS. |
| **RG-001 (ovaj dokument)** | Skraćenice i tipovi dokumenata. | Na ulasku u dokumentaciju; nije izvor pravila. |

**Gdje leže u repozitorijumu (orijentacija):**

| Tip | Tipična putanja |
|-----|-----------------|
| BM | `docs/business-model/` |
| FS | `docs/functional-specifications/` |
| TS | `docs/technical-specifications/` |
| IS | `docs/implementation-strategies/` |
| Feature Registry | `docs/features/` |
| Change Request | `docs/change-requests/` |
| Technical Overview | `docs/tehnicka-dokumentacija/` |
| Use Cases | `docs/use-cases/` |
| RG | `docs/reference/` |

---

# 4. Pravila održavanja

### RG-001-04 — Upravljanje

* RG-001 **nije** izvor istine za poslovna ili tehnička pravila.
* Prednost imaju vlasnički dokumenti: BM, FS, TS, IS, Feature Registry, CR, TO.
* Nova skraćenica se dodaje tek kada se **stvarno koristi** u usvojenoj dokumentaciji **i** kada je specifična za projekat (vidi Pravilo obuhvata u §1).
* Skraćenice se ne mijenjaju samostalno u RG-001; prvo se usklađuje referentni dokument.
* Zastarjela skraćenica se ne briše bez traga — označava se kao zastarjela i upućuje na važeći naziv.
* U RG-001 se **ne** unose: pojmovnik; opšte tehničke/industrijske skraćenice; poslovni termini koji nisu skraćenice.

### RG-001-05 — Održavanje

RG-001 se ažurira samo kada:

* nastane nova **interna** standardna skraćenica projekta;
* nastane novi tip referentnog dokumenta;
* promijeni se zvanični naziv ili skraćenica;
* skraćenica postane zastarjela.

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

---

**Kraj dokumenta RG-001 v1.1.1 (Stable)**
