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
| FT-003     | Evidencija aktivnosti (Kalendar kulture) | Planned | FS §5.16; BM-14; van opsega: TS, pregled/filteri, retention, izvoz |

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
* jednim ili više Technical Specification dokumenata
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

Jedan Feature može biti povezan sa jednim ili više TS dokumenata.

TS dokumenti koriste jedinstvenu globalnu numeraciju (TS-001, TS-002, TS-003...), nezavisno od Feature ID-a.

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

**Newsletter (u okviru FT-001):** model zasnovan na novoobjavljenim događajima i poslovno značajnim promjenama — javno objavljivanje je okidač za prvo uključivanje; otkazivanje, odlaganje i promjena datuma/vremena/lokacije su prioritetni okidači (samo pretplatnicima kojima je događaj već poslat). Višestruke promjene prije slanja daju jedinstveno obavještenje sa posljednjim važećim stanjem. Bez fiksnog sedmičnog rasporeda.

**Usvojene poslovne odluke (Događaj — otkazivanje / ponovna objava):** Dok je Organizator aktivan, Moderator može otkazati objavljeni događaj u aktivnom kontekstu; deaktivacijom Organizatora moderatorski kontekst prestaje i Moderator više ne izvršava poslovne radnje nad njegovim događajima — otkazivanje tada isključivo Urednik. Urednik može otkazati bilo koji objavljeni događaj; isključivo Urednik može ponovo objaviti otkazani događaj (BM PATCH-035/036: BM-ORG-12, BM-DG-05, BM-DG-09, BM-ST-07, BM-MOD-16, BM-UR-11; FS PATCH-FS-037/038: BR-007, BR-049, BR-050, BR-063, BR-064). Relevantno za budući TS-003.

Povezana dokumentacija (Organizator):

* Technical Specification — `docs/technical-specification/Technical-Specification_Organizator.md` (TS-001; funkcionalna cjelina Organizator / Moderator / Zahtjev za kreiranje Organizatora u okviru FT-001)

Povezana dokumentacija (Newsletter):

* Business Model — BM-13 (BM-NL-01–BM-NL-25), PATCH-031–PATCH-033
* Functional Specification — §5.15 (BR-138–BR-169), PATCH-FS-032–PATCH-FS-034

**Planirani Technical Specification dokumenti (modul Kalendar kulture):**

Plan koristi globalnu numeraciju (M-TS-002). Oznaka TS-002 pripada modulu Plaćanja (FT-002) i nije dio ovog plana.

| TS | Naziv | Feature | Modul | Status |
| -- | ----- | ------- | ----- | ------ |
| TS-001 | Organizator, Moderator i zahtjev za kreiranje Organizatora | FT-001 | Kalendar kulture | U izradi (postoji dokument) |
| TS-003 | Događaj | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-004 | Održavanje događaja | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-005 | Manifestacija | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-006 | Lokacije | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-007 | Kategorije i oznake | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-008 | Mediji | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-009 | Javni portal | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-010 | Urednički portal | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-011 | Newsletter | FT-001 | Kalendar kulture | Planiran — nacrt nije započet |
| TS-012 | Evidencija aktivnosti | FT-003 | Kalendar kulture | Planiran — nacrt nije započet |

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

## FT-003

Naziv:

Evidencija aktivnosti (Kalendar kulture)

Status:

Planned

Napomena:

Centralna Evidencija aktivnosti modula Kalendar kulture — dokumentovanje poslovno značajnih radnji radi odgovornosti, kontrole i revizije. Direktan pristup: Administrator platforme. Razlikuje se od lokalnih audit tragova na entitetima.

V1 katalog (FS): Moderator ovlašćenja; Organizatori; događaji; Newsletter. Van opsega ovog feature zapisa do posebnog PATCH-a: autentikacija/platformski nalozi i uloge, detaljni Admin pregled/filteri, struktura polja zapisa, retention, izvoz, Technical Specification.

Povezana dokumentacija:

* Business Model — BM-14 (BM-AL-01–BM-AL-08), BM-EP-09, BM-GL-09, BM-GL-20
* Functional Specification — §5.16 (BR-170–BR-188), PATCH-FS-035
* Technical Specification — TS-012 Evidencija aktivnosti (planiran — nacrt nije započet; modul Kalendar kulture)

Matrica sljedivosti (sažetak):

| BM | FS | FT | TS |
|----|----|----|-----|
| BM-AL-01–BM-AL-08 | BR-170–BR-188 / §5.16 | FT-003 | TS-012 (planiran) |
| BM-EP-09 | §5.16 | FT-003 | TS-012 (planiran) |
| BM-GL-09, BM-GL-20 | BR-170, BR-174 | FT-003 | TS-012 (planiran) |

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
| 2026-07-27 | FT-001 – Newsletter: usklađeno sa BM PATCH-031 / FS PATCH-FS-032 (novoobjavljeni događaji; bez fiksnog sedmičnog modela). |
| 2026-07-27 | Registrovana funkcionalnost FT-003 – Evidencija aktivnosti (Kalendar kulture). Status: Planned. Povezano sa BM-14 i FS §5.16 (PATCH-FS-035). |
| 2026-07-28 | Usklađivanje pravila sljedivosti: Feature može imati jedan ili više TS dokumenata; TS dokumenti koriste globalnu numeraciju. Za FT-001 evidentiran postojeći TS-001 za funkcionalnu cjelinu Organizator / Moderator / Zahtjev za kreiranje Organizatora. |
| 2026-07-28 | Evidentiran planski raspored TS dokumenata za modul Kalendar kulture (TS-001, TS-003–TS-012); TS-002 ostaje rezervisan za Plaćanja (FT-002). TS-012 rezervisan za FT-003. Status planiranih: nacrt nije započet. |
| 2026-07-28 | FT-001 — Evidentirana usvojena odluka o ovlašćenjima za otkazivanje i ponovnu objavu događaja (BM PATCH-035 / FS PATCH-FS-037); relevantno za TS-003. |
| 2026-07-28 | FT-001 — Korekcija odluke: nakon deaktivacije Organizatora Moderator nema pravo otkazivanja (BM PATCH-036 / FS PATCH-FS-038); nalaz B4 zatvoren. |
