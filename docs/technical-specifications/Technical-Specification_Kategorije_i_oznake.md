# Digital Kotor
# Technical Specification
## Kategorije i oznake

**Feature ID:** FT-001
**Oznaka dokumenta:** TS-007
**Funkcionalna cjelina:** Kategorije i oznake
**Modul:** Kalendar kulture
**Status dokumenta:** Usvojen
**Verzija:** 0.1.2
**Datum:** 2026-08-09

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-30 | Prva kompletna tehnička specifikacija za Kategorije i oznake. Ugrađene usvojene Product Owner odluke TS7-PO-01–TS7-PO-06 i usklađene sa BM-08, FS §5.10 i TS pravilima projekta. Bez SQL, API ugovora, Laravel koda i migracija. |
| 0.1.1 | 2026-08-08 | **TS7-PO-07** / BM PATCH-059 / FS PATCH-FS-059: konačni početni V1 katalog (14 kategorija); razdvajanje kanonskog DB kataloga od PO početnog sadržaja; semantičko mapiranje legacy→kanonski; cutover = TS-009. Bez implementacije seed/migracije. |
| 0.1.2 | 2026-08-09 | **Faza 6A / PO-TS9-08E:** javni CAT-CUTOVER bez migracije legacy sadržaja i bez alias mape (PO-EV-01); preduslov 14 kategorija u `cultural_categories`; tehnički ugovor u TS-009. Bez implementacije seed/migracije. |

---

# Svrha dokumenta

Ovaj dokument opisuje tehničku realizaciju funkcionalne cjeline **Kategorije i oznake** u okviru FT-001 – Kalendar kulture.

TS-007:

* ne uvodi nova poslovna pravila;
* ne mijenja Business Model ni Functional Specification;
* ne predstavlja implementaciju;
* ne definiše SQL, migracije, Laravel kod ni konkretne API ugovore.

Izvori istine:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-08, BM-GL-14, BM-GL-23, relevantni BM-02/BM-03/BM-04)
* `docs/functional-specifications/Functional-Specification.md` (§5.10, BR-081–BR-085, BR-224–BR-236)
* usvojene PO odluke: TS7-PO-01 .. TS7-PO-07
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

1. TS-007 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz TS-007.
4. Izmjene usvojenog sadržaja evidentiraju se novom verzijom dokumenta i odgovarajućim PATCH-om BM/FS, gdje je primjenjivo.

---

# 1. Pregled funkcionalne cjeline

## 1.1 Svrha funkcionalne cjeline

Kategorija je zapis **poslovnog kataloga** koji predstavlja osnovnu klasifikaciju Događaja. Kategorija nije tehnička ENUM vrijednost. Katalog kategorija je proširiv.

Oznaka je zapis poslovnog kataloga koji predstavlja dodatnu klasifikaciju Događaja. Oznake ulaze u V1. Jedan Događaj može imati više oznaka. Oznake nisu zamjena za primarnu kategoriju.

## 1.2 Obuhvat dokumenta

TS-007 obuhvata:

* poslovni katalog Kategorija;
* poslovni katalog Oznaka;
* lifecycle (Aktivna / Neaktivna), kreiranje kao Aktivna, reaktivacija, bez redovnog fizičkog brisanja;
* ovlašćenja Urednik / Moderator / Organizator / Administrator platforme;
* vezu Događaja na primarnu kategoriju i na oznake;
* zabranu kategorije „Nešto drugo“;
* granicu: novi katalog bez migracije test podataka i bez tranzicionog ENUM modela;
* V1 uključuje oznake.

## 1.3 Zavisnosti

| Zavisnost | Uloga u odnosu na TS-007 |
|-----------|---------------------------|
| TS-001 Organizator / Moderator | Moderator radi u ime Organizatora; Organizator nije operativna uloga |
| TS-003 Događaj | Događaj referencira primarnu kategoriju i opciono oznake |
| TS-005 Manifestacija | Manifestacija nema sopstvene kategorije ni oznake; eventualni portalni prikaz je izveden iz Događaja |
| TS-010 Urednički portal | Operativni UI za upravljanje katalogom (Urednik) i izbor pri događaju (Moderator/Urednik) |
| TS-012 Evidencija aktivnosti | Centralna evidencija poslovno značajnih radnji nad katalogom (gdje je u opsegu) |

## 1.4 Veza sa BM / FS / PO

```
TS7-PO-01..07
  -> BM-08 Kategorije i oznake, BM-GL-14, BM-GL-23
  -> FS §5.10 (BR-081..085, BR-224..236, BR-277..279)
  -> TS-007 (ovaj dokument)
```

---

# 2. Arhitektonski principi

## 2.1 Poslovni katalog (ne ENUM)

Katalog kategorija i katalog oznaka su jedini izvor istine za dostupne vrijednosti klasifikacije.

Tehnički model ne tretira kategorije kao fiksnu ENUM listu ni kao PHP konstantu kao poslovni izvor istine.

Katalog je proširiv dodavanjem novih zapisa od strane Urednika.

### 2.1.1 Kanonski katalog vs početni V1 sadržaj (TS7-PO-07)

| Pojam | Značenje |
|-------|----------|
| **Kanonski katalog** | Zapisi u poslovnom/tehničkom katalogu kategorija (npr. `cultural_categories`); SSOT za runtime izbor i validaciju |
| **Početni V1 sadržaj (PO)** | Usvojeni skup od **14** naziva (BM-KO-09 / BR-277) sa kojim V1 ulazi u produkcijski/cutover režim |
| **Legacy lista** | `CulturalEvent::CATEGORIES` / ENUM string — **nije** kanonski izvor istine |

Početni V1 sadržaj **nije** ENUM. Urednik i dalje može proširiti katalog. Obezbjeđivanje početnog sadržaja u bazi prije cutover-a je preduslov za TS-009; **način** (seed, ručni unos, migracija) **nije** propisan ovim dokumentom i ne implementira se ovdje.

## 2.2 Razdvajanje pojmova i uloga

* Organizator je poslovni entitet i nije operativna uloga.
* Moderator je poslovno ovlašćenje koje bira postojeće Aktivne kategorije i oznake pri uređivanju Događaja.
* Urednik isključivo upravlja katalogom kategorija i katalogom oznaka.
* Administrator platforme nema redovnu poslovnu ulogu u ovom workflow-u.
* Kategorija = vrsta Događaja; **nije** Manifestacija; **nije** tip Organizatora (BM-KO-10 / BR-278).

## 2.3 Oznake u V1

Oznake su dio V1. Nisu zamjena za primarnu kategoriju.

## 2.4 Bez migracije test podataka

Ne radi se migracija postojećih test podataka kao referentni katalog. Ne uvodi se kompatibilnost sa starim modelom kao trajni dual model. Ne pravi se tranzicioni ENUM+FK model. Postojeće test kategorije nisu referentni poslovni podaci. Katalog se definiše kao novi poslovni katalog.

**Napomena (TS-009 Faza 6A):** javni portal nakon cutover-a koristi isključivo kanonski `CulturalCategory`. Legacy `CulturalEvent` sadržaj je testni (**PO-EV-01**): **ne** migrira se; **ne** uvodi se URL/legacy alias mapa. Semantičko mapiranje: BM-KO-11 / BR-279 (referentno, ne runtime adapter). Preduslov: 14 usvojenih kategorija u `cultural_categories` (TS-009 §9.4).

## 2.5 Bez kategorije „Nešto drugo“

Kategorija „Nešto drugo“ ne postoji. Ako nijedna postojeća kategorija nije odgovarajuća, Urednik proširuje katalog novom kategorijom. Oznake ne zamjenjuju kategoriju. Automatski fallback za legacy „Nešto drugo“ **nije** usvojen.

## 2.6 Bez workflow-a predlaganja

Ne uvodi se workflow za predlaganje kategorija ili oznaka, dodatni statusi odobravanja ni dodatna ovlašćenja.

## 2.7 Početni V1 katalog (TS7-PO-07)

Usvojeni nazivi i redoslijed (1–14): Koncerti; Predstave; Sportski događaji; Izložbe; Književni programi; Filmske projekcije; Dječiji programi; Konferencije; Radionice; Publikacije; Performansi; Prezentacije i predavanja; Paneli i tribine; Sajmovi.

Odbačene kao kanonske kategorije: Filmski festivali; Likovne manifestacije; Manifestacije u organizaciji Mjesnih zajednica; Manifestacije u organizaciji NVU; Nešto drugo.

Detaljna značenja: BM-KO-09.

---

# 3. Tehnički model

## 3.1 Kategorija

Samostalan zapis kataloga sa najmanje:

* stabilnim identifikatorom;
* nazivom;
* statusom (Aktivna / Neaktivna).

Nova kategorija nastaje sa statusom **Aktivna**.

## 3.2 Oznaka

Samostalan zapis kataloga sa najmanje:

* stabilnim identifikatorom;
* nazivom;
* statusom (Aktivna / Neaktivna).

Nova oznaka nastaje sa statusom **Aktivna**.

## 3.3 Veza Događaj → Kategorija

* 0..1 primarna kategorija dok je Nacrt;
* tačno 1 primarna kategorija za slanje na odobrenje i za objavu;
* referenca na katalogski zapis, ne na slobodni ENUM string kao izvor istine.

## 3.4 Veza Događaj → Oznake

* 0..N oznaka;
* reference na katalogske zapise oznaka.

## 3.5 Lifecycle

```
Aktivna <--> Neaktivna
```

* Nova → Aktivna.
* Deaktivacija: Aktivna → Neaktivna.
* Reaktivacija: Neaktivna → Aktivna.
* Fizičko brisanje nije dio redovnog poslovnog procesa.
* Deaktivacija ne mijenja postojeće veze ni istorijske podatke.

---

# 4. Tokovi

## 4.1 Upravljanje katalogom (Urednik)

1. Urednik kreira kategoriju ili oznaku → status Aktivna.
2. Urednik uređuje naziv i druga dozvoljena svojstva.
3. Urednik deaktivira zapis → Neaktivna; postojeće veze Događaja ostaju.
4. Urednik ponovo aktivira zapis → Aktivna.

## 4.2 Korišćenje pri uređivanju Događaja (Moderator / Urednik)

1. Pri izboru prikazuju se isključivo **Aktivne** kategorije, odnosno **Aktivne** oznake.
2. Moderator bira jednu primarnu kategoriju (obavezno za slanje/objavu) i opciono više oznaka.
3. Moderator ne kreira ni ne mijenja katalog.

## 4.3 Proširenje umjesto „Nešto drugo“

1. Ako odgovarajuća kategorija ne postoji, Urednik dodaje novu kategoriju u katalog.
2. Tek nakon toga Moderator/Urednik može dodijeliti tu kategoriju Događaju.

---

# 5. Autorizacija i ovlašćenja

| Radnja | Organizator | Moderator | Urednik | Administrator platforme |
|--------|-------------|-----------|---------|-------------------------|
| Kreirati kategoriju u katalogu | Ne | Ne | Da | Ne |
| Urediti kategoriju u katalogu | Ne | Ne | Da | Ne |
| Deaktivirati / reaktivirati kategoriju | Ne | Ne | Da | Ne |
| Kreirati oznaku u katalogu | Ne | Ne | Da | Ne |
| Urediti oznaku u katalogu | Ne | Ne | Da | Ne |
| Deaktivirati / reaktivirati oznaku | Ne | Ne | Da | Ne |
| Izabrati Aktivnu kategoriju na Događaju | Ne | Da (kontekst Org. / po pravilima događaja) | Da | Ne |
| Izabrati Aktivne oznake na Događaju | Ne | Da | Da | Ne |
| Predložiti novu kategoriju / oznaku (workflow) | Ne | Ne | Ne — nema takvog workflow-a | Ne |
| Sistemska administracija | Ne | Ne | Ne | Da |

Napomena: zabranjeno tretirati Organizatora kao operativnog korisnika kataloga. Moderator ne upravlja katalogom.

---

# 6. Model podataka

Konceptualni model (bez SQL tipova):

## 6.1 Kategorija

Obavezno:

* stabilni identifikator;
* naziv;
* status (Aktivna / Neaktivna).

## 6.2 Oznaka

Obavezno:

* stabilni identifikator;
* naziv;
* status (Aktivna / Neaktivna).

## 6.3 Relacije

* Kategorija 1 : N Događaj (primarna kategorija; Događaj 0..1 u Nacrtu, 1 za slanje/objavu)
* Oznaka N : M Događaj

## 6.4 Integritet

* nove veze samo na Aktivne zapise;
* postojeće veze ostaju validne nakon deaktivacije;
* nema kategorije „Nešto drugo“;
* nema ENUM liste kao poslovnog izvora istine.

---

# 7. Validacije

## 7.1 Statusne validacije

* samo Aktivna kategorija dostupna za nove veze;
* samo Aktivna oznaka dostupna za nove veze;
* Neaktivna ostaje validna za istorijske veze.

## 7.2 Kardinalnost

* najviše jedna primarna kategorija po Događaju;
* nula ili više oznaka po Događaju;
* primarna kategorija obavezna pri slanju na odobrenje i objavi.

## 7.3 Zabrane

* zabranjen unos / postojanje kategorije „Nešto drugo“;
* zabranjeno tretirati oznaku kao zamjenu za primarnu kategoriju;
* zabranjeno Moderatoru upravljanje katalogom.

---

# 8. Evidencija aktivnosti (Audit)

Poslovno značajne radnje nad katalogom (kreiranje, izmjena, deaktivacija, reaktivacija) evidentiraju se u skladu sa BM-14 / FS §5.16 i budućim TS-012, bez uvođenja novog modela ovlašćenja.

Lokalni tragovi na entitetima Događaja (promjena kategorije/oznaka) ostaju predmet TS-003 / Evidencije događaja.

---

# 9. Integracije

| TS | Ugovor granice |
|----|-----------------|
| TS-001 | Moderatorski kontekst; Organizator nije operativna uloga |
| TS-003 | Primarna kategorija i oznake na Događaju; uslovi slanja/objave |
| TS-005 | Manifestacija bez sopstvenih kategorija/oznaka |
| TS-010 | UI kataloga (Urednik) i izbor na obrascu događaja |
| TS-012 | Centralna Evidencija aktivnosti |

---

# 10. Nefunkcionalni zahtjevi

## 10.1 Integritet

Sistem mora garantovati da nove veze koriste isključivo Aktivne katalogske zapise i da deaktivacija ne kida postojeće reference.

## 10.2 Proširivost

Dodavanje nove kategorije ili oznake ne smije zahtijevati izmjenu ENUM šeme kao poslovnog modela.

## 10.3 Minimalne kontrolisane izmjene

Implementacija uvodi novi katalogski model; ne uvodi tranzicioni dual-write sa starim ENUM modelom i ne migrira test podatke kao referentne.

---

# 11. Granice V1 (Out of Scope)

Van V1 / van ovog dokumenta:

* migracija postojećih test kategorija kao poslovnih podataka;
* kompatibilnost i tranzicioni period sa ENUM/string modelom;
* workflow predlaganja kategorija/oznaka od strane Moderatora;
* kategorija „Nešto drugo“;
* fizičko brisanje kao redovni poslovni proces;
* sopstvene kategorije/oznake Manifestacije.

U V1: oznake jesu u opsegu.

---

# 12. Otvorena pitanja

Za TS-007 trenutno nema otvorenih pitanja koja blokiraju usvajanje ovog dokumenta.

Napomena: konkretan početni sadržaj kataloga usvojen je odlukom **TS7-PO-07** (BM-KO-09 / BR-277) kao **14** naziva sa redoslijedom. To **nije** fiksna ENUM lista; katalog ostaje proširiv. Javni CAT-CUTOVER = **TS-009 Faza 6A** (bez migracije legacy sadržaja).

---

# 13. Matrica sljedivosti

| PO | BM | FS | TS-007 |
|----|----|----|--------|
| TS7-PO-01 | BM-KO-01 | BR-081, BR-224, BR-235 | §2.1, §3.1, §10.2 |
| TS7-PO-02 | BM-KO-03, BM-GL-23 | BR-083, BR-236 | §2.3, §3.2, §3.4, §11 |
| TS7-PO-03 | BM-KO-05, BM-KO-06 | BR-085, BR-230–BR-233 | §3.5, §4.1, §7.1 |
| TS7-PO-04 | BM-KO-08 | BR-224 | §2.4, §10.3, §11 |
| TS7-PO-05 | BM-KO-07 | BR-225 | §2.5, §4.3, §7.3 |
| TS7-PO-06 | BM-KO-04 | BR-084, BR-226–BR-229 | §2.2, §5 |
| TS7-PO-07 | BM-KO-09–BM-KO-11, BM-GL-14 | BR-277–BR-279 | §2.1.1, §2.7, §14 |

---

# 14. Napomene za implementaciju

1. Ne koristiti `CulturalEvent::CATEGORIES` / DB ENUM kao trajni poslovni izvor istine.
2. Ne zadržavati kategoriju „Nešto drugo“.
3. Ne migrirati test podatke kao referentni katalog.
4. Ne uvoditi dual-write / tranzicioni ENUM+FK model u ovom dokumentu.
5. Implementacija mora ostati usklađena sa: Organizator = entitet; Moderator = ovlašćenje; Urednik = upravlja katalogom; Administrator platforme = sistemska administracija.
6. Podrazumijevane fotografije po kategoriji (BM-MD-06) ostaju predmet medija / Događaja; mapiranje se veže na katalogski zapis, ne na ENUM string kao izvor istine.
7. **TS7-PO-07:** početni V1 sadržaj = 14 usvojenih naziva (BM-KO-09). Obezbijediti ih u kanonskom katalogu prije TS-009 Faza 6A cutover-a; **način** (seed/ručno/…) nije propisan ovdje.
8. Semantičko mapiranje legacy→kanonski: BM-KO-11 / BR-279 (referentno). **Faza 6A:** bez migracije legacy sadržaja; bez alias mape; javni portal = `CulturalCategory` (PO-TS9-08E / PO-EV-01).
