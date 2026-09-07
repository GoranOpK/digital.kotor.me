# Digital Kotor
# Tehnička specifikacija Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-TS-001
**Naziv:** Tehnička specifikacija Konkursa
**Funkcionalna cjelina:** Konkursi — kanonski paket (početni TS)
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** NACRT
**Verzija:** 0.1.0
**Datum:** 2026-08-18

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreirana početna struktura KN-TS-001 prema M-TS-005 (poglavlja 1–14). Tehnički model, tokovi, autorizacija, podaci, validacije, integracije i NFR nijesu projektovani. Čeka usvajanje KN-BM-001 i KN-FS-001 nakon analize Odluke. Bez izmišljenih modela, tabela, ruta, kontrolera, uloga ili servisa. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

PATCH model, kada bude izdat: `KN-PATCH-TS-*` (KN-RG-001 / DK-DS-001 §8).

---

# Svrha dokumenta

Ovaj dokument opisuje kako će se usvojeni Business Model i Functional Specification cjeline **Konkursi** tehnički realizovati.

U verziji 0.1.0 dokument:

* uspostavlja obaveznu M-TS-005 strukturu;
* uspostavlja dokumentacionu sljedivost prema KN-BM-001 i KN-FS-001;
* **ne** definiše arhitekturu rješenja, model podataka, API, rute, Laravel komponente ni runtime identifikatore;
* **ne** uvodi poslovna pravila kojih nema u KN-BM-001 / KN-FS-001.

Dokument:

* nije Technical Overview trenutne implementacije;
* nije opis postojećeg koda ženskog preduzetništva;
* nije Change Request.

Izvori istine za buduća poslovna i funkcionalna pravila:

* `docs/business-model/Business_Model_Konkursi.md` (KN-BM-001)
* `docs/functional-specifications/Functional-Specification_Konkursi.md` (KN-FS-001)
* `docs/pravni-okvir/Pravni_okvir_Konkursi.md` (KN-PRO-001) — preko BM, ne zaobilazeći lanac
* `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` (KN-RG-001)
* `docs/METHODOLOGY.md` (M-TS-001 … M-TS-005)
* `docs/reference/Digital-Kotor-Documentation-Standard.md` (DK-DS-001)

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | NACRT — identitet; bez tehničkog dizajna |
| 2. Arhitektonski principi | NACRT — samo metodološka ograničenja |
| 3. Tehnički model | PENDING — čeka usvajanje BM/FS |
| 4. Tokovi | PENDING — čeka usvajanje BM/FS |
| 5. Autorizacija i ovlašćenja | PENDING — čeka usvajanje BM/FS |
| 6. Model podataka | PENDING — čeka usvajanje BM/FS |
| 7. Validacije | PENDING — čeka usvajanje BM/FS |
| 8. Evidencija aktivnosti (Audit) | PENDING — čeka usvajanje BM/FS |
| 9. Integracije | PENDING — čeka usvajanje BM/FS |
| 10. Nefunkcionalni zahtjevi | PENDING — čeka usvajanje BM/FS |
| 11. Granice V1 (Out of Scope) | NACRT — nema usvojenih isključenja |
| 12. Otvorena pitanja | NACRT — čeka analizu Odluke / BM / FS |
| 13. Matrica sljedivosti | NACRT — hijerarhija dokumenata; bez rule-level mapa |
| 14. Napomene za implementaciju | NACRT — nenormativno; bez implementacionog dizajna |

---

# Pravila upravljanja ovim dokumentom

1. KN-TS-001 pripada cjelini Konkursi.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz Technical Specification.
4. Sve što nije definisano u BM ili FS evidentira se kao **Otvoreno pitanje**.
5. Product Owner donosi poslovne odluke; ovaj dokument ih ne pretpostavlja.
6. TS ne projektuje stvarnu aplikacionu strukturu dok BM i FS nijesu sadržajno usvojeni.
7. Izmjene usvojenog sadržaja evidentiraju se novim redom u istoriji verzija.
8. Poglavlje 14 je strogo nenormativno (M-TS-005).

---

# 1. Pregled funkcionalne cjeline

Izvori

Business Model:

* KN-BM-001 — NACRT; nema usvojenih poslovnih pravila konkursa
* KN-BM-001 §5 — dokumentaciona načela `KN-DOC-01` … `KN-DOC-07` (nisu poslovna pravila konkursa)

Functional Specification:

* KN-FS-001 — NACRT; nema unesenih BR / FR

## 1.1 Svrha funkcionalne cjeline

Cjelina **Konkursi** (`KN`) obuhvata podršku preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.

KN-TS-001 je prvi tehnički dokument tog paketa. Ne predstavlja kompletan tehnički dizajn dok BM/FS ne budu usvojeni.

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

## 1.2 Obuhvat dokumenta

Obuhvat ovog nacrta:

* uspostavljanje M-TS-005 strukture;
* dokumentaciona hijerarhija i zabrana izmišljanja tehničkog dizajna.

Nije obuhvat ovog nacrta:

* modeli, tabele, kolone;
* nazivi ruta;
* Laravel kontroleri, servisne klase, mail klase, queue poslovi;
* role i middleware;
* statusi;
* storage struktura.

## 1.3 Identifikovani pravni izvor

Naslov (bez analize teksta): **Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.**

Pravni sadržaj ulazi u TS samo preko usvojenog KN-BM-001 / KN-FS-001.

---

# 2. Arhitektonski principi

Izvori

Business Model: KN-BM-001 §5 (`KN-DOC-03`, `KN-DOC-07`)

Functional Specification: KN-FS-001 §4

Ovo poglavlje u verziji 0.1.0 navodi samo metodološka ograničenja. **Ne** opisuje mjesto cjeline u aplikacionom kodu, framework-specifičnu realizaciju niti zavisnosti među klasama.

Principi ovog nacrta:

* TS razrađuje isključivo usvojena BM/FS pravila (M-TS-005).
* TS ne uvodi poslovno pravilo koje ne postoji u BM/FS.
* TS ne preuzima arhitekturu iz postojeće implementacije ženskog preduzetništva kao kanonski dizajn.
* Runtime / stable ključ se ne mijenja implicitno promjenom document ID-a (DK-DS-001 §9; KN-RG-001 §7).
* Filename nije SSOT; Document ID jeste (DK-DS-001 §2, §14).

Konkretno mjesto cjeline u sistemu, odgovornosti komponenti i principi dizajna rješenja: **PENDING** — čeka usvajanje BM/FS.

---

# 3. Tehnički model

Izvori

Business Model: nema usvojenih entiteta / lifecycle pravila

Functional Specification: nema usvojenih tokova / statusa

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se tehnički entiteti, odnosi, statusi, lifecycle ni odgovornosti komponenti.

---

# 4. Tokovi

Izvori

Business Model: nema usvojenih poslovnih procesa

Functional Specification: nema usvojenih funkcionalnih tokova

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se koraci postupka, dijagrami ni mapiranje na rute ili kontrolere.

---

# 5. Autorizacija i ovlašćenja

Izvori

Business Model: nema usvojenih uloga

Functional Specification: nema usvojene poslovne autorizacije

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se role, middleware, gate-ovi ni ACL. Postojeće runtime uloge **nisu** kanonski TS ugovor.

---

# 6. Model podataka

Izvori

Business Model: nema usvojenih poslovnih entiteta

Functional Specification: nema usvojenih atributa / validacija

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se tabele, kolone, SQL šema, migracije ni fizički dizajn baze. Ne unosi se konceptualni model iz postojeće implementacije.

---

# 7. Validacije

Izvori

Business Model: nema usvojenih poslovnih validacija

Functional Specification: nema usvojenih BR validacija

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se poslovne, tehničke, integritetske niti statusne validacije.

---

# 8. Evidencija aktivnosti (Audit)

Izvori

Business Model: nema usvojenih audit pravila

Functional Specification: nema usvojenih audit zahtjeva

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

---

# 9. Integracije

Izvori

Business Model: nema usvojenih integracionih pravila

Functional Specification: nema usvojenih integracionih zahtjeva

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se vanjski sistemi, API ugovori, storage provajderi, mail ni queue mehanizmi.

---

# 10. Nefunkcionalni zahtjevi

Izvori

Business Model: nema usvojenih NFR

Functional Specification: nema usvojenih NFR

**Status:** PENDING — čeka usvajanje BM/FS

Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva.

Ne unose se performanse, kapacitet, RPO/RTO, ni tehnički limiti.

---

# 11. Granice V1 (Out of Scope)

Izvori

Business Model: KN-BM-001 §6 — PENDING BUSINESS ANALYSIS; nema usvojenog obuhvata V1

Functional Specification: KN-FS-001 §3 — PENDING BUSINESS ANALYSIS; nema usvojenih isključenja

U ovom nacrtu **nema** usvojenih isključenja van V1.

Otvorena pitanja, nedonesene poslovne odluke i nedovršena analiza Odluke **ne** ulaze u ovo poglavlje (M-TS-003). Vidi poglavlje 12.

---

# 12. Otvorena pitanja

1. Zvanični tekst identifikovane Odluke nije analiziran; KN-PRO-001 ostaje NACRT (PENDING LEGAL SOURCE ANALYSIS).
2. KN-BM-001 nema usvojena poslovna pravila konkursa (PENDING BUSINESS ANALYSIS).
3. KN-FS-001 nema unesene BR / FR / tokove (PENDING BUSINESS ANALYSIS).
4. Tehnički dizajn (poglavlja 3–10) ne može se usvojiti prije BM/FS.
5. Odnos budućeg kanonskog KN paketa prema postojećoj implementaciji ženskog preduzetništva nije predmet ovog dokumenta; rješava se posebnim kontrolisanim zadatkom.

Ova pitanja **nisu** poslovna pravila i **nisu** tehnička rješenja.

---

# 13. Matrica sljedivosti

Dokumentaciona hijerarhija (nije prenos pravnih pravila):

```text
DK-DS-001 / METHODOLOGY
        ↓
KN-RG-001
        ↓
KN-PRO-001
        ↓
KN-BM-001
        ↓
KN-FS-001
        ↓
KN-TS-001
```

Sekcijska sljedivost ovog nacrta:

| TS poglavlje | KN-BM-001 | KN-FS-001 | Napomena |
|--------------|-----------|-----------|----------|
| 1. Pregled | §1, §5 | §1, §4 | Identitet paketa |
| 2. Arhitektonski principi | §5 | §4 | Metodološka ograničenja |
| 3–10 | §6–§9 | §5–§10 | PENDING — nema usvojenih pravila |
| 11. Granice V1 | §6 | §3 | Nema usvojenih isključenja |
| 12. Otvorena pitanja | — | — | M-TS-003 / M-TS-005 |
| 13. Matrica sljedivosti | §10 | §12 | Hijerarhija dokumenata |
| 14. Napomene | — | — | Nenormativno |

Rule-level sljedivost `pravna odredba → poslovno pravilo → BR → TS` **nije** uspostavljena. Nema usvojenih pravila za mapiranje.

---

# 14. Napomene za implementaciju

Ovo poglavlje je strogo nenormativno (M-TS-005).

Ne uvodi poslovna ni funkcionalna pravila. Ne mijenja BM, FS niti normativni sadržaj poglavlja 1–13.

Napomene ovog nacrta:

* Ne implementirati aplikacioni kod, bazu ni migracije na osnovu ovog dokumenta dok BM/FS ne budu usvojeni.
* Ne tretirati postojeći kod ženskog preduzetništva kao tehničku specifikaciju KN-TS-001.
* Sljedeći sadržajni korak je analiza zvaničnog teksta Odluke u KN-PRO-001, zatim KN-BM-001, zatim KN-FS-001; tek onda tehnički dizajn u ovom TS-u.

---

**Kraj dokumenta KN-TS-001 v0.1.0**
