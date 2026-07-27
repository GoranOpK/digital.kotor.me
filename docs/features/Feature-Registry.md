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

| Feature ID | Naziv            | Status  | Napomena                                      |
| ---------- | ---------------- | ------- | --------------------------------------------- |
| FT-001     | Kalendar kulture | Active  | Prva funkcionalnost u razvoju                 |
| FT-002     | Plaćanja         | Planned | Nova funkcionalnost; dokumentacija u pripremi |

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

## FT-002

Naziv:

Plaćanja

Status:

Planned

Napomena:

Modul za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor. U ovoj fazi u toku je priprema dokumentacije. Implementacija nije započeta.

Povezana dokumentacija:

* Pravni okvir: `docs/pravni-okvir/Pravni_okvir_Placanja.md`
* Katalog finansijskih obaveza: `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md`
* Business Model: `docs/business-model/Business_Model_Placanja.md`
* Functional Specification: `docs/functional-specification/Functional-Specification_Placanja.md`
* Technical Specification: `docs/technical-specification/Technical-Specification_Placanja.md`

Sljedivost:

FT-002
→ BM-002
→ FS-002
→ TS-002

Usvojene projektne odluke:

* P-01 do P-08 — Projektna načela modula Plaćanja
* F-01 — Obavezni obuhvat V1
* UR-01 — Uplatni računi (referentni / konfiguracioni podaci)
* BP-01 — Pronalaženje vrste uplate
* BP-02 — Način popunjavanja podataka za plaćanje
* BP-03 — Pregled i potvrda prije plaćanja
* BP-04 — Jedinstvena integracija sa sistemom elektronskog plaćanja
* BP-05 — Obrada ishoda elektronskog plaćanja
* BP-06 — Potvrda o izvršenom elektronskom plaćanju
* BP-07 — Izvor obaveznih podataka za elektronsko plaćanje
* BP-08 — Životni ciklus transakcije
* BP-09 — Istorija transakcija i pregled plaćanja

Sljedivost poslovnih odluka:

| Oznaka | Naziv | BM | FS | TS |
|--------|-------|----|----|----|
| BP-01 | Pronalaženje vrste uplate | BM-002 / 9.1 | FS-002 / 7.1 | TS-002 / 2.5 |
| BP-02 | Način popunjavanja podataka za plaćanje | BM-002 / 9.2 | FS-002 / 7.2 | TS-002 / 2.5 |
| BP-03 | Pregled i potvrda prije plaćanja | BM-002 / 9.3 | FS-002 / 7.3 | TS-002 / 2.5 |
| BP-04 | Jedinstvena integracija sa sistemom elektronskog plaćanja | BM-002 / 9.4 | FS-002 / 7.4 | TS-002 / 2.6 |
| BP-05 | Obrada ishoda elektronskog plaćanja | BM-002 / 9.5 | FS-002 / 7.5 | TS-002 / 2.7 |
| BP-06 | Potvrda o izvršenom elektronskom plaćanju | BM-002 / 9.6 | FS-002 / 7.6 | TS-002 / 2.8 |
| BP-07 | Izvor obaveznih podataka za elektronsko plaćanje | BM-002 / 9.7 | FS-002 / 7.7 | TS-002 / 2.9 |
| BP-08 | Životni ciklus transakcije | BM-002 / 9.8 | FS-002 / 7.8 | TS-002 / 2.10 |
| BP-09 | Istorija transakcija i pregled plaćanja | BM-002 / 9.9 | FS-002 / 7.9 | TS-002 / 2.11 |

Veze BP-04: P-03, P-08, UR-01.

Veze BP-05: BP-03, BP-04.

Veze BP-06: BP-05, BP-09.

Veze BP-07: BP-02, BP-03, UR-01, BP-04.

Veze BP-08: BP-03, BP-04, BP-05, BP-06, P-08.

Veze BP-09: BP-06, BP-08, UR-01.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-26 | Kreiran Feature Registry. Registrovana funkcionalnost FT-001 – Kalendar kulture. |
| 2026-07-27 | Registrovana funkcionalnost FT-002 – Plaćanja. Status: Planned. |
| 2026-07-27 | FT-002 – Dodate usvojene odluke BP-01, BP-02, BP-03 i matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-04 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-05 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-06 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-07 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-08 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – PATCH-008A: redakcijsko usklađivanje BP-05/BP-06/BP-08 (bez nove poslovne odluke). |
| 2026-07-27 | FT-002 – PATCH-008B: evidencija bilježi trenutni status transakcije (bez nove poslovne odluke). |
| 2026-07-27 | FT-002 – Dodata usvojena odluka BP-09 i ažurirana matrica sljedivosti. |
| 2026-07-27 | FT-002 – PATCH-009A: redakcijsko usklađivanje BP-06↔BP-09 i terminologija identifikatora (bez nove poslovne odluke). |
