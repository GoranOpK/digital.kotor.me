# Digital Kotor
# Technical Specification
## Lokacije

**Feature ID:** FT-001  
**Oznaka dokumenta:** KK-TS-006  
**Funkcionalna cjelina:** Lokacije  
**Modul:** Kalendar kulture  
**Status dokumenta:** Usvojen  
**Verzija:** 0.1.1  
**Datum:** 2026-07-30

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-30 | Prva kompletna tehnička specifikacija za Lokacije. Ugrađene usvojene Product Owner odluke PO-LOC-01–PO-LOC-07 i usklađene sa BM-07, FS §5.9 i TS pravilima projekta. Bez SQL, API ugovora, Laravel koda i migracija. |
| 0.1.1 | 2026-07-30 | Korekcija prema novim PO odlukama za razrješenje KON-LOC-01 i KON-LOC-02: katalog Lokacija je opcioni za ponovno korišćenje, ručni unos naziva Lokacije je dozvoljen, kataloška referenca je opciona, merge i referencijalni integritet važe za postojeće kataloške veze. Bez promjene opsega V1 (fizičke Lokacije). |
| — | 2026-08-17 | Administrativna migracija dokumentacionog ID-a na `KK-*` namespace. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku realizaciju funkcionalne cjeline **Lokacije** u okviru FT-001 – Kalendar kulture.

KK-TS-006:

* ne uvodi nova poslovna pravila;
* ne mijenja Business Model ni Functional Specification;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-07, BM-GL-13, relevantni BM-02/BM-03/BM-06/BM-14)
* `docs/functional-specifications/Functional-Specification.md` (§5.9, BR-074–BR-080, BR-206–BR-223)
* usvojene PO odluke: PO-LOC-01 .. PO-LOC-07
* `docs/features/Feature-Registry.md`
* `docs/METHODOLOGY.md`

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Usvojeno |
| 2. Arhitektonski principi | Usvojeno |
| 3. Tehnički model | Usvojeno |
| 4. Tokovi | Usvojeno |
| 5. Autorizacija i ovlašćenja | Usvojeno |
| 6. Model podataka | Usvojeno |
| 7. Validacije | Usvojeno |
| 8. Evidencija aktivnosti (Audit) | Usvojeno |
| 9. Integracije | Usvojeno |
| 10. Nefunkcionalni zahtjevi | Usvojeno |
| 11. Granice V1 (Out of Scope) | Usvojeno |
| 12. Otvorena pitanja | Usvojeno |
| 13. Matrica sljedivosti | Usvojeno |
| 14. Napomene za implementaciju | Usvojeno |

---

# Pravila upravljanja ovim dokumentom

1. KK-TS-006 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz KK-TS-006.
4. Izmjene usvojenog sadržaja evidentiraju se novom verzijom dokumenta i odgovarajućim PATCH-om BM/FS, gdje je primjenjivo.

---

# 1. Pregled funkcionalne cjeline

## 1.1 Svrha funkcionalne cjeline

Kataloška Lokacija je samostalan poslovni entitet centralnog kataloga za ponovno korišćenje Lokacija.

Moderator može koristiti Lokaciju kroz Događaje i Održavanja na tri načina: izbor iz kataloga, ručni unos naziva Lokacije ili bez definisane Lokacije kada poslovna pravila to dozvoljavaju.

## 1.2 Obuhvat dokumenta

KK-TS-006 obuhvata:

* centralni katalog kataloških Lokacija;
* jedinstvenost i obradu mogućih duplikata;
* lifecycle Lokacije (Aktivna/Deaktivirana);
* ovlašćenja Moderator/Urednik/Administrator platforme;
* referencijalni integritet kataloških veza i atomski merge kataloških Lokacija;
* audit za poslovno značajne radnje nad Lokacijom;
* V1 granicu: isključivo fizičke Lokacije.

## 1.3 Zavisnosti

| Zavisnost | Uloga u odnosu na KK-TS-006 |
|-----------|---------------------------|
| KK-TS-001 Organizator / Moderator | Moderator radi u ime Organizatora; Organizator nije operativna uloga |
| KK-TS-003 Događaj | Događaj može biti bez Lokacije; kada je definisana Lokacija može biti kataloška ili ručno unesena preko Održavanja |
| KK-TS-004 Održavanje događaja | Lokacija pripada Održavanju |
| KK-TS-010 Urednički portal | Operativni UI za workflow Lokacija |
| KK-TS-012 Evidencija aktivnosti | Centralna evidencija poslovno značajnih lokacijskih radnji |

## 1.4 Veza sa BM / FS / PO

```
PO-LOC-01..07
  -> BM-07 Lokacija, BM-GL-13
  -> FS §5.9 (BR-074..080, BR-206..223)
  -> KK-TS-006 (ovaj dokument)
```

---

# 2. Arhitektonski principi

## 2.1 Opcioni katalog za ponovno korišćenje

Centralni katalog Lokacija je opcioni katalog za ponovno korišćenje Lokacija.

Ručni unos naziva Lokacije je dozvoljen i ne zahtijeva obaveznu katalošku referencu.

## 2.2 Razdvajanje pojmova i uloga

* Organizator je poslovni entitet i nije operativna uloga.
* Moderator je operativni korisnik koji predlaže Lokacije u ime Organizatora.
* Urednik donosi uredničke odluke i upravlja katalogom.
* Administrator platforme nema redovnu poslovnu ulogu u ovom workflow-u.

## 2.3 Integritet referenci

Kada zapis koristi katalošku Lokaciju, veza mora biti stabilna i validna.

Odsustvo kataloške reference nije povreda integriteta kod ručnog unosa ili odsustva Lokacije.

## 2.4 Atomsko spajanje

Operacija merge odnosi se samo na kataloške Lokacije i mora biti atomska.

## 2.5 Auditabilnost

Sve poslovno značajne radnje nad Lokacijom ostavljaju nepromjenjiv trag.

---

# 3. Tehnički model

## 3.1 Entitet: Lokacija

Kataloška Lokacija je samostalan poslovni entitet centralnog kataloga.

## 3.2 Lifecycle

Dozvoljeni statusi:

* Aktivna
* Deaktivirana

Deaktivirana Lokacija ostaje povezana sa istorijskim zapisima; fizičko brisanje nije redovan poslovni tok.

## 3.3 Korišćenje kroz module

* Održavanje može imati katalošku Lokaciju, ručno unijeti naziv Lokacije ili biti bez definisane Lokacije.
* Događaj može biti bez Lokacije.
* Kada se koristi kataloška Lokacija, više Događaja/Održavanja može dijeliti istu Lokaciju.

## 3.4 Duplikati i merge

* Identične Lokacije nijesu dozvoljene.
* Mogući duplikati prijavljuju se Uredniku.
* Urednik odlučuje o merge/odbijanju/uređivanju.

---

# 4. Tokovi

## 4.1 Predlaganje Lokacije

1. Moderator predlaže novu katalošku Lokaciju u ime Organizatora.
2. Sistem provjerava jedinstvenost i potencijalne duplikate.
3. Prijedlog ulazi u urednički workflow.

Moderator nije obavezan da svaku ručno unesenu Lokaciju prethodno doda u katalog. Ručno uneseni naziv može naknadno biti predložen za katalog radi ponovne upotrebe.

## 4.2 Urednička odluka

Urednik može:

* odobriti;
* odbiti;
* vratiti na doradu.

Samo odobrena kataloška Lokacija ulazi u aktivni katalog.

## 4.3 Uređivanje kataloga

Urednik uređuje postojeće Lokacije i rješava moguće duplikate.

## 4.4 Deaktivacija / aktivacija

Urednik deaktivira i ponovo aktivira Lokacije.

Deaktivirana kataloška Lokacija:

* ne može biti izabrana za nove zapise;
* ostaje na postojećim zapisima.

## 4.5 Merge Lokacija

1. Urednik inicira merge izvorne u ciljnu katalošku Lokaciju.
2. Sistem atomski preusmjerava sve postojeće kataloške reference.
3. Ručno uneseni tekst Lokacije ne mijenja se automatski merge operacijom.

---

# 5. Autorizacija i ovlašćenja

| Radnja | Organizator | Moderator | Urednik | Administrator platforme |
|--------|-------------|-----------|---------|-------------------------|
| Predložiti katalošku Lokaciju | Ne | Da | Da (po uredničkim ovlašćenjima) | Ne |
| Odobriti/Odbiti/Vratiti na doradu | Ne | Ne | Da | Ne |
| Uređivati katalog Lokacija | Ne | Ne | Da | Ne |
| Rješavati duplikate | Ne | Ne | Da | Ne |
| Deaktivirati/Aktivirati | Ne | Ne | Da | Ne |
| Merge Lokacija | Ne | Ne | Da | Ne |
| Sistemska administracija | Ne | Ne | Ne | Da |

Napomena: zabranjeno koristiti formulacije “Organizator kreira/predlaže/uređuje Lokaciju”.

---

# 6. Model podataka

Konceptualni model (bez SQL tipova):

## 6.1 Lokacija

Obavezno:

* stabilni identifikator;
* naziv;
* status (Aktivna/Deaktivirana).

Opciono:

* dodatni opisni podaci Lokacije u skladu sa FS.

## 6.2 Relacije

* Kataloška Lokacija 1 : N Održavanje (kada postoji kataloška veza)
* Održavanje N : 1 Događaj

## 6.3 Integritet

* referenca na centralni katalog Lokacija je opciona;
* kada zapis koristi katalošku Lokaciju, referenca mora biti validna;
* odsustvo kataloške reference nije greška za ručni unos ili zapis bez Lokacije;
* promjena podataka kataloške Lokacije je vidljiva svim zapisima koji referenciraju tu katalošku Lokaciju.

---

# 7. Validacije

## 7.1 Jedinstvenost

* blokirati unos identične kataloške Lokacije;
* potencijalne duplikate eskalirati Uredniku.

## 7.2 Statusne validacije

* samo Aktivna kataloška Lokacija dostupna je za nove kataloške veze;
* Deaktivirana kataloška Lokacija ostaje validna za istorijske veze.

## 7.3 Merge validacije

* ciljna kataloška Lokacija mora postojati;
* preusmjeravanje postojećih kataloških referenci mora biti kompletno i atomsko;
* ručno uneseni tekst Lokacije ne mijenja se automatski kroz merge.

---

# 8. Evidencija aktivnosti (Audit)

Audit događaji:

* kreiranje kataloške Lokacije;
* izmjena kataloške Lokacije;
* odobrenje;
* odbijanje;
* vraćanje na doradu;
* deaktivacija;
* aktivacija;
* merge.

Svaki audit zapis sadrži najmanje:

* datum i vrijeme;
* korisnika;
* vrstu radnje;
* staru vrijednost;
* novu vrijednost.

Audit je nepromjenjiv i ne predstavlja rollback mehanizam.

---

# 9. Integracije

| TS | Ugovor granice |
|----|-----------------|
| KK-TS-001 | Moderatorski kontekst i razdvajanje Organizator/Moderator |
| KK-TS-003 | Događaj koristi Lokacije kroz Održavanja |
| KK-TS-004 | Lokacija pripada Održavanju |
| KK-TS-010 | Urednički workflow Lokacija |
| KK-TS-012 | Centralna Evidencija aktivnosti za lokacijske radnje |

---

# 10. Nefunkcionalni zahtjevi

## 10.1 Integritet i konzistentnost

Sistem mora garantovati konzistentne kataloške reference prije i nakon merge operacije.

## 10.2 Auditabilnost

Audit zapis ne smije biti ručno izmjenjiv kroz redovno korišćenje.

## 10.3 Pouzdanost merge operacije

Merge mora biti atomski i idempotentan u pogledu konačnog stanja kataloških referenci.

---

# 11. Granice V1 (Out of Scope)

V1 obuhvata samo fizičke Lokacije.

Van opsega V1:

* online Lokacije;
* hibridne Lokacije;
* nova tipologija Lokacija bez nove PO odluke.

---

# 12. Otvorena pitanja

Nema otvorenih Product Owner pitanja za KK-TS-006 na osnovu usvojenih odluka PO-LOC-01 .. PO-LOC-07.

---

# 13. Matrica sljedivosti

| PO | BM | FS | KK-TS-006 | Feature Registry |
|----|----|----|--------|------------------|
| PO-LOC-01 (korekcija) | BM-LK-01, BM-LK-02 | BR-074, BR-075, BR-077 | §1, §2, §3, §6 | FT-001 / KK-TS-006 veza |
| PO-LOC-02 | BM-LK-06 | BR-206, BR-207 | §3, §4, §7 | FT-001 / KK-TS-006 veza |
| PO-LOC-03 | BM-LK-07 | BR-208..BR-211 | §2, §4, §5 | FT-001 / KK-TS-006 veza |
| PO-LOC-04 | BM-LK-05, BM-LK-08 | BR-078, BR-212..BR-215 | §3, §4, §7 | FT-001 / KK-TS-006 veza |
| PO-LOC-05 (korekcija) | BM-LK-09, BM-LK-10 | BR-216..BR-219 | §2, §3, §6, §7 | FT-001 / KK-TS-006 veza |
| PO-LOC-06 | BM-LK-11 | BR-220..BR-222 | §8 | FT-001 / KK-TS-006 veza |
| PO-LOC-07 | BM-LK-12, BM-GL-13 | BR-223 | §11 | FT-001 / KK-TS-006 veza |

---

# 14. Napomene za implementaciju

1. KK-TS-006 je normativni cilj; trenutno implementaciono stanje može odstupati i vodi se u Technical Overview dokumentaciji.
2. Implementacija mora ostati usklađena sa pravilom da Organizator nije operativna uloga.
3. Uvođenje online/hibridnih Lokacija zahtijeva novu Product Owner odluku prije izmjene BM/FS/TS.
4. Merge utiče samo na kataloške reference; ručno uneseni tekst Lokacije ne mijenja se automatski.
