# Digital Kotor
# Registar pojmova i skraćenica

**Oznaka dokumenta:** RG-001  
**Naziv:** Registar pojmova i skraćenica Digital Kotor  
**Status dokumenta:** Stable  
**Verzija:** 1.0.0  
**Datum:** 2026-07-31

---

# 1. Svrha i način korišćenja

### RG-001-01 — Identitet

RG-001 je **referentni i živi** dokument. Služi kao kratki orijentacioni vodič kroz dokumentaciju Digital Kotora.

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

* ne definiše poslovna pravila ni funkcionalnosti;
* nije poslovni rječnik;
* ne zamjenjuje BM, FS, TS, IS ili Feature Registry;
* samo evidentira postojeću dokumentacionu terminologiju.

**Kako koristiti:** pročitati §2 za skraćenice, zatim §3 da znate koji tip dokumenta otvoriti. Za sadržaj pravila ići u vlasnički dokument (BM/FS/TS/…).

---

# 2. Skraćenice

### RG-001-02 — Struktura (dio A)

Uključene su samo skraćenice koje se **stvarno koriste** u usvojenoj dokumentaciji.

| Skraćenica | Puni naziv | Kratko objašnjenje |
|------------|------------|-------------------|
| **BM** | Business Model | Tip dokumenta: poslovni model (pravila i koncepti), bez implementacije. |
| **FS** | Functional Specification | Tip dokumenta: funkcionalna specifikacija — šta sistem radi za korisnika. |
| **TS** | Technical Specification | Tip dokumenta: tehnička specifikacija usvojenog BM/FS. |
| **IS** | Implementation Strategy | Tip dokumenta: implementaciona strategija (faze, rizici, isporuka, rollback). |
| **RG** | Registar pojmova i skraćenica | Ovaj dokument (RG-001); orijentacija kroz dokumentaciju. |
| **CR** | Change Request | Odobreni zahtjev za izmjenu implementacije (npr. CR-001). |
| **BR** | Business Rule | Identifikator pravila u FS (npr. BR-102); sljedivost ka BM. |
| **FR** | Functional Requirement | Identifikator funkcionalnog zahtjeva u nekim FS dokumentima (npr. FR-001, FR-OB-001). |
| **FT** | Feature ID | Jedinstveni ID funkcionalnosti u Feature Registry-ju (npr. FT-001). |
| **PO** | Product Owner | Uloga koja usvaja product / poslovne odluke (npr. PO-TS9-03A). |
| **IA** | Information Architecture | Informaciono-arhitektonska odluka (npr. IA-01). |
| **TD** | Technical Decision | Tehnička odluka evidentirana u TS (npr. TD-TS9-01). |
| **TO** | Technical Overview | Pregled trenutne implementacije i odstupanja od BM/FS/TS. |
| **UC** | Use Case | Opis korisničkog scenarija (npr. UC-OB-001). |
| **UI** | User Interface | Korisnički interfejs; često u procjeni uticaja CR-a. |
| **API** | Application Programming Interface | Programski interfejs; u TS/CR kontekstu. |
| **URL** | Uniform Resource Locator | Adresa / putanja resursa; često u filterima i navigaciji. |
| **V1** | Verzija 1 | Prvi dogovoreni obuhvat isporuke; „Out of Scope“ za ono što nije u V1. |
| **NFR** | Non-Functional Requirement | Nefunkcionalni zahtjev (npr. poglavlje u TS). |
| **FK** | Foreign Key | Strani ključ / relacijska veza u modelu podataka. |
| **CRUD** | Create, Read, Update, Delete | Osnovne operacije nad zapisima. |
| **OFD** | Open Finding | Otvoreni nalaz / neriješena dokumentaciona stavka (npr. OFD-OB-006). |
| **M-TS** | Methodology — Technical Specification | Pravilo metodologije za TS dokumente (npr. M-TS-001). |

### 2.1 FR i Feature Registry (obavezno razlikovanje)

| Oznaka / naziv | Značenje | Primjer |
|----------------|----------|---------|
| **FR** | **Functional Requirement** — identifikator zahtjeva unutar FS | FR-001, FR-OB-001 |
| **Feature Registry** | Dokument / registar funkcionalnosti | `docs/features/Feature-Registry.md` |
| **FT** | **Feature ID** — ID stavke u Feature Registry-ju | FT-001, FT-004 |

**Feature Registry se ne skraćuje kao FR.**  
Ako neko kaže „FR“, u Digital Kotor dokumentaciji to znači *Functional Requirement*, ne Feature Registry.  
Za registar funkcionalnosti koristi se pun naziv **Feature Registry**; za identifikatore funkcionalnosti koristi se **FT-***.

### 2.2 Ostale napomene o jednoznačnosti

| Skraćenica | Status |
|------------|--------|
| **FR** | Jednoznačno u dokumentaciji = Functional Requirement; zabuna sa Feature Registry je **terminološki rizik za nove članove** (razriješeno §2.1). |
| **BR** | U FS se koristi kao identifikator pravila (`BR-*`). Ponekad se u prozi kaže „requirement“; kanonska skraćenica ostaje **BR** = Business Rule. |
| **OFD** | U upotrebi kao `OFD-*` za otvorene nalaze. Pun engleski oblik u prozi nije svuda jednak („finding“ / „decision“); u RG-001 kanonski: **Open Finding**. |
| **BM, FS, TS, IS, CR, TO, FT, PO, IA, TD, UC, UI, API, URL, V1, NFR, FK, CRUD, M-TS, RG** | Nema drugog dokumentacionog značenja u pregledanom korpusu. |

Nema drugih skraćenica u ovom registru sa dva **konkurentna** kanonska značenja pored navedenih napomena.

---

# 3. Referentni dokumenti

### RG-001-02 — Struktura (dio B)

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

### RG-001-03 — Upravljanje

* RG-001 **nije** izvor istine za poslovna ili tehnička pravila.
* Prednost imaju vlasnički dokumenti: BM, FS, TS, IS, Feature Registry, CR, TO.
* Nova skraćenica se dodaje tek kada se **stvarno koristi** u usvojenoj dokumentaciji.
* Skraćenice se ne mijenjaju samostalno u RG-001; prvo se usklađuje referentni dokument.
* Zastarjela skraćenica se ne briše bez traga — označava se kao zastarjela i upućuje na važeći naziv.

### RG-001-04 — Održavanje

RG-001 se ažurira samo kada:

* nastane nova standardna skraćenica;
* nastane novi tip referentnog dokumenta;
* promijeni se zvanični naziv ili skraćenica;
* skraćenica postane zastarjela.

RG-001 se **ne** ažurira zbog: novih funkcionalnosti, poslovnih pravila, kategorija, statusa, uloga ili pojedinačnih modula.

---

# 5. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Nacrt. Usvojene odluke RG-001-01 … RG-001-04. Evidentirane skraćenice i tipovi dokumenata na osnovu postojeće dokumentacije. Bez izmjene BM/FS/TS/IS/implementacije. |
| 0.5.0 | 2026-07-31 | Final Review. Pojašnjeno FR ≠ Feature Registry (FT); napomene o BR/OFD; skraćeni opisi tipova dokumenata; bez novih skraćenica i bez izmjene drugih dokumenata. |
| 1.0.0 | 2026-07-31 | Stable. Dokument je prošao Final Review i predstavlja referentni vodič za skraćenice i dokumentacionu strukturu Digital Kotora. Bez izmjene sadržaja registra, tipova dokumenata ili pravila održavanja. Bez izmjene BM/FS/TS/IS/implementacije. |

---

**Kraj dokumenta RG-001 v1.0.0 (Stable)**
