# Digital Kotor
# Pravni okvir
## Modul: Plaćanja

**Feature ID:** FT-002
**Status dokumenta:** U IZRADI
**Verzija:** 0.2

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Pravnog okvira modula Plaćanja. Unesene usvojene projektne odluke P-05, P-07 i povezane odluke P-01–P-08 i F-01. |
| 0.2 | 2026-07-27 | Usklađivanje sa UR-01: Katalog kao poslovni referentni dokument; uplatni računi kao referentni / konfiguracioni podaci. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument definiše pravni okvir modula Plaćanja na platformi Digital Kotor.

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

---

# Pravila upravljanja dokumentom

1. Pravni okvir predstavlja zvanični dokument pravne usklađenosti modula Plaćanja (FT-002).

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

---

# 1. Uvod

Modul Plaćanja (FT-002) služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor (P-01).

Modul ne obračunava finansijske obaveze, ne donosi upravna rješenja, ne kreira zaduženja i ne vodi izvorne evidencije finansijskih obaveza (P-03).

Za svaku finansijsku obavezu izvorni informacioni sistem ili nadležni organ Opštine Kotor ostaje jedini mjerodavan izvor podataka (P-08).

---

# 2. Projektna načela relevantna za pravni okvir

Sljedeća projektna načela (P-01 do P-08) predstavljaju obavezujuće projektne odluke i moraju se poštovati u cijeloj dokumentaciji i razvoju modula. Tekst načela ne smije se mijenjati niti proširivati bez nove projektne odluke.

Puna formulacija načela nalazi se u Business Modelu (BM-002), poglavlje „Usvojena projektna načela (P-01 do P-08)“.

Za Pravni okvir posebno su mjerodavna:

| Oznaka | Naziv | Relevancija |
|--------|-------|-------------|
| P-01 | Svrha modula | Predmet pravnog okvira je elektronsko plaćanje finansijskih obaveza prema Opštini Kotor. |
| P-03 | Granice odgovornosti | Modul nije izvor upravnih rješenja ni evidencija obaveza. |
| P-04 | Postojeći poslovni procesi | Modul ne uvodi nove obaveze niti mijenja postojeće procese. |
| P-05 | Regulatorna usklađenost | Svaka funkcionalnost mora imati pravni osnov. |
| P-07 | Propis kao izvor istine | Pravni osnov se evidentira po propisanim poljima; bez pretpostavki. |
| P-08 | Izvorni sistem ostaje nadležan | Izvorni sistem / nadležni organ ostaje mjerodavan. |
| F-01 | Obavezni obuhvat V1 | Vrste uplata i računi iz projekta; računi u Katalogu kao referentni podaci. |
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
| Katalog finansijskih obaveza | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` | Poslovni referentni dokument vrsta uplata i uplatnih računa (nije šifrarnik) |
| Business Model | `docs/business-model/Business_Model_Placanja.md` | Poslovna pravila i načela P-01–P-08 |
| Functional Specification | `docs/functional-specifications/Functional-Specification_Placanja.md` | Funkcionalni zahtjevi, uključujući F-01 |
| Technical Specification | `docs/technical-specifications/Technical-Specification_Placanja.md` | Tehnička specifikacija (nakon usvajanja tehničkih odluka) |

U skladu sa F-01 i UR-01, brojevi uplatnih računa u Katalogu tretiraju se kao **referentni podaci** iz važeće Naredbe. Katalog nije šifrarnik. Aplikacija koristi konfiguracioni izvor (šifrarnik izveden iz Kataloga); računi se ne hardkodiraju u kodu.

Referentni propis naveden u F-01 za obuhvat V1:

**Naredba o načinu uplate javnih prihoda** („Službeni list Crne Gore“, broj 006/25 od 29.01.2025. godine), u dijelu koji je obuhvaćen ovim projektom.

Konkretan spisak vrsta uplata unosi se isključivo iz Kataloga nakon dostave projektnog spiska. Ovaj dokument ne dopunjava spisak samostalnim tumačenjem propisa.

---

# 7. Završne odredbe

1. Ovaj dokument važi za modul Plaćanja (FT-002).
2. Izmjene se unose isključivo kroz usvojene projektne ili pravne odluke i PATCH evidenciju.
3. U slučaju neslaganja između pretpostavki i potvrđenih propisa, primjenjuje se potvrđeni propis, uz ažuriranje dokumentacije kroz PATCH.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1 Pravnog okvira. Unesene odluke P-05, P-07 i veze na P-01–P-08 i F-01. Registar pravnih osnova ostavljen prazan. |
| 2026-07-27 | Verzija 0.2 — Usklađeno sa UR-01 (Katalog ≠ šifrarnik; računi = referentni / konfiguracioni podaci). |
