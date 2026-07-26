# Digital Kotor
# Feature Registry

**Status dokumenta:** AKTIVAN
**Verzija:** 0.1

---

# Feature Registry

## Svrha

Feature Registry predstavlja centralni registar svih funkcionalnosti sistema Digital Kotor.

Svaka funkcionalnost dobija jedinstveni identifikator (Feature ID) koji ostaje nepromijenjen tokom cijelog životnog ciklusa projekta.

Feature ID omogućava povezivanje poslovne dokumentacije, funkcionalnih specifikacija, tehničkih specifikacija, Change Request-ova, implementacije i testiranja.

---

# Pravila

Svaka funkcionalnost dobija jedinstveni identifikator u formatu:

* FT-001
* FT-002
* FT-003
* ...

Feature ID se dodjeljuje samo jednom i nikada se ne koristi ponovo.

Feature Registry predstavlja polaznu tačku za sljedivost kroz cijeli projekat.

---

# Pregled funkcionalnosti

| Feature ID | Naziv            | Status | Napomena                      |
| ---------- | ---------------- | ------ | ----------------------------- |
| FT-001     | Kalendar kulture | Active | Prva funkcionalnost u razvoju |

Dozvoljeni statusi:

* Planned
* Active
* Deprecated
* Removed

---

# Veza sa ostalom dokumentacijom

Svaka funkcionalnost može biti povezana sa:

* Business Model dokumentacijom
* Functional Specification
* Technical Specification
* Change Request Register
* Test Case dokumentacijom
* Implementacijom

Primjer:

FT-001
→ BM-001
→ FS-001
→ CR-001
→ TS-001
→ Test Cases
→ Produkcija

---

# Pravila sljedivosti

Svaki novi dokument koji opisuje određenu funkcionalnost treba da sadrži referencu na odgovarajući Feature ID.

Svaki Change Request mora sadržati referencu na Feature ID kojem pripada.

Technical Specification mora biti vezana za odgovarajući Feature ID.

Na ovaj način svaka funkcionalnost može biti praćena od poslovne ideje do produkcije.

---

# Prvi zapis

## FT-001

Naziv:

Kalendar kulture

Status:

Active

Napomena:

Funkcionalnost je u fazi detaljne funkcionalne specifikacije i predstavlja referentni model za razvoj metodologije Digital Kotor.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-26 | Kreiran Feature Registry. Registrovana funkcionalnost FT-001 – Kalendar kulture. |
