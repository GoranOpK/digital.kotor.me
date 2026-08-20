# Digital Kotor
# Pravni okvir e-Plaćanja
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-PO-001
**Modul:** e-Plaćanje
**Status dokumenta:** U IZRADI
**Verzija:** 0.4

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Pravnog okvira modula e-Plaćanja. Unesene usvojene projektne odluke P-05, P-07 i povezane odluke P-01–P-08 i F-01. |
| 0.2 | 2026-07-27 | Usklađivanje sa UR-01: Katalog kao poslovni referentni dokument; uplatni računi kao referentni / konfiguracioni podaci. |
| 0.3 | 2026-08-17 | Dokumentacioni corrective: oznaka EP-PO-001; namespace EP-*; naziv modula e-Plaćanje. Bez izmjene pravnih zaključaka. |
| 0.4 | 2026-08-20 | Minimalni pointer usklađenosti sa Korakom 6: priroda potvrde; retention OPEN PRE-PRODUCTION; kartični podaci. Pravni osnovi po vrstama nijesu reinterpretirani. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument definiše pravni okvir modula e-Plaćanja na platformi Digital Kotor.

Predstavlja osnov za usklađenost funkcionalnosti modula sa važećim propisima Crne Gore i Opštine Kotor.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | U IZRADI |
| 2. Projektna načela relevantna za pravni okvir | USVOJENO |
| 3. Regulatorna usklađenost | USVOJENO (P-05) |
| 4. Propis kao izvor istine | USVOJENO (P-07) |
| 5. Registar pravnih osnova po vrstama obaveza | REZERVISANO |
| 6. Veza sa Katalogom i ostalom dokumentacijom | U IZRADI |
| 7. Završne odredbe | U IZRADI |
| 8. Poslovno-pravni pointeri Koraka 6 | USVOJENO kao pointer; nije nova pravna osnova |

---

# Pravila upravljanja dokumentom

1. Pravni okvir predstavlja zvanični dokument pravne usklađenosti modula e-Plaćanja (EP-PO-001).

2. Posljednja usvojena verzija predstavlja jedini izvor istine za pravni okvir u okviru projekta.

3. Dokument se mijenja isključivo kroz PATCH koji predstavlja usvojenu projektnu ili pravnu odluku.

4. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno unositi, pretpostavljati ili dopunjavati pravne podatke.

5. Ne potvrđeni pravni podaci ne smiju se unositi. Ako pravni osnov nije potvrđen, označava se kao **Potrebno pravno potvrditi** (P-07).

---

## Sadržaj

1. Uvod
2. Projektna načela relevantna za pravni okvir
3. Regulatorna usklađenost (P-05)
4. Propis kao izvor istine (P-07)
5. Registar pravnih osnova po vrstama obaveza
6. Veza sa Katalogom i ostalom dokumentacijom
7. Završne odredbe
8. Poslovno-pravni pointeri Koraka 6

---

# 1. Uvod

Modul e-Plaćanje služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor (P-01).

Modul ne obračunava finansijske obaveze, ne donosi upravna rješenja, ne kreira zaduženja i ne vodi izvorne evidencije finansijskih obaveza (P-03).

Za svaku finansijsku obavezu izvorni informacioni sistem ili nadležni organ Opštine Kotor ostaje jedini mjerodavan izvor podataka (P-08).

---

# 2. Projektna načela relevantna za pravni okvir

Sljedeća projektna načela (P-01 do P-08) predstavljaju obavezujuće projektne odluke i moraju se poštovati u cijeloj dokumentaciji i razvoju modula. Tekst načela ne smije se mijenjati niti proširivati bez nove projektne odluke.

Puna formulacija načela nalazi se u poslovnom modelu (EP-BM-001), poglavlje „Usvojena projektna načela (P-01 do P-08)“.

Za Pravni okvir posebno su mjerodavna:

| Oznaka | Naziv | Relevancija |
|--------|-------|-------------|
| P-01 | Svrha modula | Predmet pravnog okvira je elektronsko plaćanje finansijskih obaveza prema Opštini Kotor. |
| P-03 | Granice odgovornosti | Modul nije izvor upravnih rješenja ni evidencija obaveza. |
| P-04 | Postojeći poslovni procesi | Modul ne uvodi nove obaveze niti mijenja postojeće procese. |
| P-05 | Regulatorna usklađenost | Svaka funkcionalnost mora imati pravni osnov. |
| P-07 | Propis kao izvor istine | Pravni osnov se evidentira po propisanim poljima; bez pretpostavki. |
| P-08 | Izvorni sistem ostaje nadležan | Izvorni sistem / nadležni organ ostaje mjerodavan. |
| F-01 | Obavezni obuhvat V1 | 17 vrsta plaćanja i 41 račun iz Kataloga (Korak 6 ontologija). Računi u Katalogu kao referentni podaci. |
| UR-01 | Uplatni računi | Katalog = referentni dokument; aplikacija koristi šifrarnik (konfiguracioni izvor), bez hardkodiranja. |

---

# 3. Regulatorna usklađenost (P-05)

**Status:** USVOJENO

**Odluka P-05 – Regulatorna usklađenost**

Svaka funkcionalnost modula mora imati odgovarajući pravni osnov u zakonima Crne Gore ili važećim propisima Opštine Kotor.

Primjena:

* Funkcionalnosti se ne projektuju niti dokumentuju kao obavezne dok nije utvrđen ili označen status pravnog osnova u skladu sa P-07.
* Pravni osnov se ne izmišlja i ne preuzima iz spoljnih izvora mimo usvojenog projektnog postupka.

---

# 4. Propis kao izvor istine (P-07)

**Status:** USVOJENO

**Odluka P-07 – Propis kao izvor istine**

Svaka pojedinačna vrsta finansijske obaveze mora biti povezana sa odgovarajućim pravnim osnovom.

Kada podaci budu dostupni, potrebno je evidentirati:

* naziv propisa;
* broj i godinu službenog glasila;
* relevantni član propisa;
* nadležni organ;
* napomene o primjeni.

Ako pravni osnov nije potvrđen, potrebno ga je označiti kao:

**Potrebno pravno potvrditi.**

Ne smiju se unositi pretpostavljeni ili nepotvrđeni pravni podaci.

## 4.1 Obrazac evidencije pravnog osnova

Za svaku vrstu finansijske obaveze koristi se sljedeći obrazac. Polja se popunjavaju isključivo kada su podaci potvrđeni.

| Polje | Opis | Obavezno |
|-------|------|----------|
| Interna oznaka / šifra vrste obaveze | Veza na Katalog | Da |
| Naziv vrste obaveze | Pun naziv iz Kataloga | Da |
| Naziv propisa | Zvanični naziv | Kada je potvrđen |
| Broj i godina službenog glasila | Npr. broj / godina | Kada je potvrđen |
| Relevantni član propisa | Član / stav | Kada je potvrđen |
| Nadležni organ | Organ nadležan za obavezu | Kada je potvrđen |
| Napomene o primjeni | Dodatne napomene | Kada postoje |
| Status pravnog osnova | Potvrđen / Potrebno pravno potvrditi | Da |

---

# 5. Registar pravnih osnova po vrstama obaveza

**Status:** REZERVISANO

Registar se popunjava nakon dostave konačnog spiska vrsta uplata u Katalogu finansijskih obaveza i nakon potvrde pravnih osnova.

Do tada se u registar ne unose vrste obaveza niti pravni podaci.

| Interna oznaka | Naziv vrste obaveze | Naziv propisa | Broj i godina službenog glasila | Relevantni član | Nadležni organ | Napomene o primjeni | Status pravnog osnova |
|----------------|---------------------|---------------|---------------------------------|-----------------|----------------|---------------------|------------------------|
| — | — | — | — | — | — | — | — |

Napomena: Tabela je namjerno prazna. Popunjavanje slijedi u narednom koraku projekta.

---

# 6. Veza sa Katalogom i ostalom dokumentacijom

| Dokument | Putanja | Uloga |
|----------|---------|-------|
| Katalog finansijskih obaveza | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` | Poslovni referentni dokument 17 vrsta plaćanja i 41 računa (nije šifrarnik) |
| Poslovni model e-Plaćanja (EP-BM-001) | `docs/business-model/Business_Model_e-Placanje.md` | Poslovna pravila i načela P-01–P-08 |
| Funkcionalna specifikacija e-Plaćanja (EP-FS-001) | `docs/functional-specifications/Functional-Specification_e-Placanje.md` | Funkcionalni zahtjevi, uključujući F-01 |
| Tehnička specifikacija e-Plaćanja (EP-TS-001) | `docs/technical-specifications/Technical-Specification_e-Placanje.md` | Tehnička specifikacija (nakon usvajanja tehničkih odluka) |
| Registar skraćenica e-Plaćanja (EP-RG-001) | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-e-Placanja.md` | Dokumentacione oznake e-Plaćanja |

U skladu sa F-01 i UR-01, brojevi uplatnih računa u Katalogu tretiraju se kao **referentni podaci** iz važeće Naredbe. Katalog nije šifrarnik. Aplikacija koristi konfiguracioni izvor (šifrarnik izveden iz Kataloga); računi se ne hardkodiraju u kodu.

Referentni propis naveden u F-01 za obuhvat V1:

**Naredba o načinu uplate javnih prihoda** („Službeni list Crne Gore“, broj 006/25 od 29.01.2025. godine), u dijelu koji je obuhvaćen ovim projektom.

Konkretan spisak vrsta plaćanja i računa unosi se isključivo iz Kataloga. Ovaj dokument ne dopunjava spisak samostalnim tumačenjem propisa.

Kanonska ontologija Kataloga (Korak 6): **17 vrsta plaćanja → 41 račun**. Stara formulacija „17 kategorija + 41 vrsta uplate“ je superseded u EP-KF-001; pravni osnovi po stavkama ostaju **Potrebno pravno potvrditi**.

---

# 7. Završne odredbe

1. Ovaj dokument važi za modul e-Plaćanje (EP-PO-001).
2. Izmjene se unose isključivo kroz usvojene projektne ili pravne odluke i PATCH evidenciju.
3. U slučaju neslaganja između pretpostavki i potvrđenih propisa, primjenjuje se potvrđeni propis, uz ažuriranje dokumentacije kroz PATCH.

---

# 8. Poslovno-pravni pointeri Koraka 6

**Status:** USVOJENO kao dokumentacioni pointer. **Nije** nova pravna osnova, **nije** reinterpretacija P-05/P-07/P-08 i **nije** popunjavanje registra u poglavlju 5.

Ove stavke usklađuju Pravni okvir sa zatvorenim poslovnim modelom (EP-BM-001 v1.0.0, 2026-08-20) tamo gdje je to direktno potrebno zbog prirode potvrde, retention otvorenog pitanja i kartičnih podataka.

## 8.1 Priroda potvrde o EP transakciji

Potvrda o uspješnoj e-Plaćanje transakciji (uključujući PDF):

* jeste dokaz da je konkretna EP transakcija uspješno izvršena prema server-confirmed gateway rezultatu;
* **nije** fiskalni račun;
* **nije** upravno rješenje;
* **nije** dokaz da je konkretna finansijska obaveza izmirena.

Izvorni sistem / nadležni organ ostaje mjerodavan za utvrđivanje izmirenja (P-08).

## 8.2 Retention / deletion / anonymization

Rok čuvanja, brisanje i anonimizacija finansijske istorije e-Plaćanja:

**OPEN PRE-PRODUCTION DEPENDENCY**

**PRE-PRODUCTION LEGAL / REGULATORY REVIEW REQUIRED**

Ovaj dokument **ne** određuje rok. User account lifecycle ne briše automatski finansijsku istoriju (EP-BM-001). To nije zatvoreno pravno pravilo retention-a.

## 8.3 Kartični podaci

Prema poslovnom modelu, Digital Kotor **ne** prikuplja, **ne** obrađuje i **ne** čuva osjetljive kartične podatke. Gateway obrađuje karticu.

Tehničko-pravni review konkretnog gateway ugovora (PCI/DSS obaveze pružaoca, data-retention set, maskirani podaci) ostaje **OPEN PRE-PRODUCTION**. Ovaj pointer ne bira gateway i ne popunjava pravni osnov.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1 Pravnog okvira. Unesene odluke P-05, P-07 i veze na P-01–P-08 i F-01. Registar pravnih osnova ostavljen prazan. |
| 2026-07-27 | Verzija 0.2 — Usklađeno sa UR-01 (Katalog ≠ šifrarnik; računi = referentni / konfiguracioni podaci). |
| 2026-08-17 | Verzija 0.3 — Dokumentacioni corrective: oznaka EP-PO-001; namespace EP-*; naziv modula e-Plaćanje. Bez izmjene pravnih zaključaka i statusa pravnih osnova. |
| 2026-08-20 | Verzija 0.4 — Minimalni pointeri Koraka 6 (priroda potvrde; retention OPEN; kartični podaci). Registar pravnih osnova ostaje prazan. Pravni osnovi nijesu reinterpretirani. |
