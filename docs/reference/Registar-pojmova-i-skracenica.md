# Digital Kotor
# Registar pojmova i skraćenica

**Oznaka dokumenta:** RG-001  
**Naziv:** Registar pojmova i skraćenica Digital Kotor  
**Status dokumenta:** Nacrt  
**Verzija:** 0.1.0  
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
| **BM** | Business Model | Poslovni model: pravila i koncepti, bez implementacije. |
| **FS** | Functional Specification | Funkcionalna specifikacija: šta sistem radi za korisnika. |
| **TS** | Technical Specification | Tehnička specifikacija: kako se usvojeni BM/FS tehnički realizuju. |
| **IS** | Implementation Strategy | Implementaciona strategija: faze, rizici, deploy i rollback plana. |
| **RG** | Registar pojmova i skraćenica | Ovaj dokument (RG-001); orijentacija kroz dokumentaciju. |
| **CR** | Change Request | Odobreni zahtjev za izmjenu implementacije (npr. CR-001). |
| **BR** | Business Rule / Business Requirement | Identifikator pravila u FS (npr. BR-102); sljedivost ka BM. |
| **FR** | Functional Requirement | Identifikator funkcionalnog zahtjeva u nekim FS dokumentima (npr. FR-001, FR-OB-001). **Nije** skraćenica za Feature Registry. |
| **FT** | Feature ID | Jedinstveni ID funkcionalnosti u Feature Registry-ju (npr. FT-001). |
| **PO** | Product Owner | Uloga koja usvaja poslovne / product odluke (npr. PO-TS9-03A). |
| **IA** | Information Architecture | Informaciono-arhitektonska odluka (npr. IA-01). |
| **TD** | Technical Decision | Tehnička odluka evidentirana u TS (npr. TD-TS9-01). |
| **TO** | Technical Overview | Pregled **trenutne** implementacije i odstupanja od BM/FS/TS. |
| **UC** | Use Case | Opis korisničkog scenarija (npr. UC-OB-001). |
| **UI** | User Interface | Korisnički interfejs; često u procjeni uticaja CR-a. |
| **API** | Application Programming Interface | Programski interfejs; u TS/CR kontekstu. |
| **URL** | Uniform Resource Locator | Adresa / putanja resursa; često u filterima i navigaciji. |
| **V1** | Verzija 1 | Prvi dogovoreni obuhvat isporuke; „Out of Scope“ za ono što nije u V1. |
| **NFR** | Non-Functional Requirement | Nefunkcionalni zahtjev (npr. poglavlje u TS). |
| **FK** | Foreign Key | Strani ključ / relacijska veza u modelu podataka. |
| **CRUD** | Create, Read, Update, Delete | Osnovne operacije nad zapisima. |
| **OFD** | Open Finding / Open Decision | Otvoreni nalaz ili neriješena stavka u dokumentaciji (npr. OFD-OB-006). |
| **M-TS** | Methodology — Technical Specification | Pravilo metodologije za TS dokumente (npr. M-TS-001). |

### Napomena o skraćenici FR

U dokumentaciji se **Feature Registry** piše punim nazivom. Skraćenica **FR** u FS označava *Functional Requirement*, ne Feature Registry. Identifikatori funkcionalnosti su **FT-***.

---

# 3. Referentni dokumenti

### RG-001-02 — Struktura (dio B)

| Dokument / tip | Svrha | Kada otvoriti |
|----------------|-------|----------------|
| **Business Model (BM)** | Poslovna pravila, entiteti, ovlašćenja — šta je poslovno tačno. | Prije svake poslovne ili domenske rasprave; izvor istine za „zašto“. |
| **Functional Specification (FS)** | Funkcionalni zahtjevi i ponašanje sistema za korisnika (BR/FR). | Kada treba znati šta sistem mora raditi, bez koda. |
| **Technical Specification (TS)** | Tehnička razrada usvojenog BM/FS za jednu funkcionalnu cjelinu. | Prije implementacije ili review-a tehničkog rješenja. |
| **Implementation Strategy (IS)** | Plan faza, rizika, testiranja, deploy-a i rollback-a. | Prije početka implementacionog rada na već stabilnoj TS. |
| **Feature Registry** | Centralni registar funkcionalnosti (FT-ID) i veza ka dokumentaciji. | Da se orijentišete koji Feature / TS postoje i koji je status. |
| **Change Request (CR)** | Registar odobrenih zahtjeva za **izmjenu implementacije**. | Prije pisanja koda koji usklađuje sistem sa FS; implementacija ide kroz CR. |
| **Technical Overview (TO)** | Opis **postojeće** implementacije i registra odstupanja od BM/FS/TS. | Kada treba „šta je danas u kodu“, ne „šta treba biti“. |
| **Use Cases (UC)** | Korisnički scenariji i tokovi. | Za razumijevanje scenarija prije FS detalja (gdje postoje). |
| **Metodologija** (`docs/METHODOLOGY.md`) | Pravila pisanja i odnosa dokumenata (npr. M-TS-*). | Kada se piše ili revidira BM/FS/TS struktura. |
| **RG-001 (ovaj dokument)** | Skraćenice i tipovi dokumenata. | Na početku rada u dokumentaciji; nije izvor poslovnih pravila. |

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

---

**Kraj dokumenta RG-001 v0.1.0 (Nacrt)**
