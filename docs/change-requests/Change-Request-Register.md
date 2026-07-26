# Digital Kotor
# Change Request Register
## Modul: Kalendar kulture

**Status dokumenta:** AKTIVAN
**Verzija:** 0.1

---

# Svrha

Change Request Register predstavlja centralni registar svih odobrenih poslovnih zahtjeva koji zahtijevaju izmjenu implementacije.

Functional Specification definiše željeno ponašanje sistema.

Change Request Register evidentira razlike između trenutne implementacije i usvojene Functional Specification.

Implementacija se ne mijenja direktno na osnovu Functional Specification, već isključivo kroz odobreni Change Request.

---

# Pravila

Svaki Change Request dobija jedinstveni identifikator:

* CR-001
* CR-002
* CR-003
* ...

Svaki zapis sadrži najmanje:

* ID
* Naziv
* Referencu na Functional Specification
* Opis poslovnog zahtjeva
* Razlog izmjene
* Prioritet
* Procjena uticaja
* Status

Dozvoljeni statusi:

* Planned
* Approved
* In Progress
* Implemented
* Rejected
* Cancelled

Procjena uticaja (Impact) predstavlja pregled sistema koje Change Request zahvata.

Dozvoljene vrijednosti su jedna ili više od sljedećih oznaka:

* UI
* Backend
* Database
* API
* Security
* Permissions
* Documentation
* Performance

Svaki odobreni poslovni zahtjev koji zahtijeva izmjenu implementacije mora biti evidentiran u Change Request Register-u i dobiti jedinstveni CR identifikator prije početka razvoja.

---

# Registar

| ID | Naziv | FS Referenca | Prioritet | Procjena uticaja | Status |
| -- | ----- | ------------ | --------- | ---------------- | ------ |
| CR-001 | Usklađivanje statističkih pokazatelja sa Functional Specification. | FS-001 → 5.2 Statistički pokazatelji. | Medium | UI, Backend | Planned |

---

# CR-001

### Naziv

Usklađivanje statističkih pokazatelja sa Functional Specification.

### Referenca

FS-001 → 5.2 Statistički pokazatelji.

### Opis

Postojeću implementaciju statističkih pokazatelja potrebno je uskladiti sa usvojenom Functional Specification.

Potrebno je implementirati:

* četiri statističke kartice;
* ispravan obračun pokazatelja "Ove sedmice";
* ispravan obračun pokazatelja "Ovog mjeseca";
* novu karticu "Predstojeći događaji".

### Razlog

Trenutna implementacija nije u potpunosti usklađena sa usvojenim poslovnim pravilima.

### Prioritet

Medium

### Procjena uticaja

* UI
* Backend

### Status

Planned

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
