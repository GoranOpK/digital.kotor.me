# Digital Kotor
# Metodologija dokumentacije

**Status dokumenta:** AKTIVAN
**Verzija:** 0.1

---

# Odnos postojeće i nove dokumentacije

Digital Kotor već posjeduje značajnu količinu tehničke i operativne dokumentacije nastale tokom razvoja projekta.

Nova metodologija **ne uvodi retroaktivnu reorganizaciju postojeće dokumentacije**.

Postojeći dokumenti ostaju važeći i predstavljaju opis trenutnog stanja sistema, osim ako se za određeni dokument ne donese posebna odluka o izmjeni.

Nova metodologija se primjenjuje prvenstveno:

* na nove funkcionalnosti,
* na velike rekonstrukcije postojećih modula,
* na nove poslovne zahtjeve koji zahtijevaju Business Model, Functional Specification, Change Request i Technical Specification.

---

## Technical Overview dokumenti

Dokumenti koji opisuju postojeću implementaciju modula mogu ostati u tehničkoj dokumentaciji kao **Technical Overview** dokumenti.

Njihova svrha je:

* pregled postojeće implementacije,
* pregled arhitekture modula,
* pregled ruta, kontrolera, modela i integracija,
* pomoć pri razumijevanju postojećeg sistema.

Technical Overview dokumenti **nisu izvor istine** za:

* poslovna pravila,
* funkcionalne zahtjeve,
* planirane izmjene.

Za te oblasti koriste se:

* Business Model,
* Functional Specification,
* Change Request Register,
* Technical Specification.

---

## Odnos Business Modela, Functional Specification i implementacije

Za projekat Kalendar kulture usvaja se sljedeće pravilo:

### Business Model

Business Model opisuje ciljni poslovni model sistema.

Business Model se NE prilagođava trenutnoj implementaciji.

### Functional Specification

Functional Specification opisuje funkcionalnosti koje proizvod treba da ima nakon implementacije usvojenog poslovnog modela.

Functional Specification ne dokumentuje privremena tehnička ograničenja.

Ako implementacija kasni za usvojenim poslovnim modelom, Functional Specification ostaje usklađena sa Business Modelom.

### Technical Overview

Technical Overview opisuje isključivo trenutno stanje implementacije.

Technical Overview je jedino mjesto gdje se dokumentuju odstupanja između:

* Business Modela,
* Functional Specification-a,
* trenutne implementacije.

Registar odstupanja za Kalendar kulture vodi se u poglavlju „Odstupanja trenutne implementacije od usvojenog funkcionalnog modela“ dokumenta `docs/tehnicka-dokumentacija/cultural-calendar.md`.

---

## Kalendar kulture

Dokument:

`docs/tehnicka-dokumentacija/cultural-calendar.md`

zadržava postojeću ulogu tehničkog pregleda (Technical Overview) trenutne implementacije.

Ne mijenja se u okviru uvođenja nove metodologije.

Njegov sadržaj služi kao referenca za razumijevanje postojećeg sistema i buduću izradu Technical Specification.

---

## Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-26 | Definisan odnos između postojeće tehničke dokumentacije i nove metodologije. Uveden pojam Technical Overview dokumenta. Potvrđeno da se postojeća dokumentacija ne reorganizuje retroaktivno. |
| 2026-07-26 | Usvojeno pravilo odnosa Business Model / Functional Specification / Technical Overview; registar odstupanja vodi se isključivo u Technical Overview dokumentu modula. |
