# Digital Kotor
# Registar skraćenica i oznaka dokumentacije e-Plaćanja
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-RG-001
**Naziv:** Registar skraćenica i oznaka dokumentacije e-Plaćanja
**Modul:** e-Plaćanje
**Status dokumenta:** USVOJENO
**Verzija:** 1.0
**Datum:** 2026-08-17

---

# 1. Identitet i svrha

EP-RG-001 je referentni i živi dokument. Predstavlja centralni registar skraćenica i dokumentacionih oznaka modula **e-Plaćanje**.

Nije poslovni pojmovnik. Ne definiše poslovna pravila ni tehnička rješenja. Ne zamjenjuje EP-BM-001, EP-FS-001, EP-TS-001, EP-KF-001 ili EP-PO-001.

Uključene su samo oznake koje se stvarno koriste u dokumentaciji e-Plaćanja, plus rezervisani prefiksi namespace-a.

---

# 2. Pravilo namespace-a

Sve dokumentacione oznake specifične za modul e-Plaćanje koriste prefiks `EP-`.

Registri i oznake Kalendara kulture, uključujući `RG-001`, ne koriste se kao registri e-Plaćanja.

e-Plaćanje i Kalendar kulture su potpuno odvojeni moduli. Ovaj registar ne registruje oznake Kalendara kulture.

---

# 3. Dokumentacione oznake

## 3.1 Prefiksi dokumenata

| Oznaka | Puni naziv | Značenje | Gdje se koristi |
|--------|------------|----------|-----------------|
| **EP** | e-Plaćanje | Prefiks dokumentacionog namespace-a modula e-Plaćanje. | Svi EP dokumenti |
| **EP-BM** | Poslovni model e-Plaćanja | Tip dokumenta: poslovni model. | EP-BM-001 |
| **EP-FS** | Funkcionalna specifikacija e-Plaćanja | Tip dokumenta: funkcionalna specifikacija. | EP-FS-001 |
| **EP-TS** | Tehnička specifikacija e-Plaćanja | Tip dokumenta: tehnička specifikacija. | EP-TS-001 |
| **EP-KF** | Katalog finansijskih obaveza | Tip dokumenta: katalog vrsta uplata i uplatnih računa. | EP-KF-001 |
| **EP-PO** | Pravni okvir e-Plaćanja | Tip dokumenta: pravni okvir. Nije Product Owner. | EP-PO-001 |
| **EP-RG** | Registar skraćenica i oznaka e-Plaćanja | Ovaj dokument. | EP-RG-001 |

## 3.2 Kanonski dokumenti (aktuelno)

| Oznaka | Dokument | Putanja | Status |
|--------|----------|---------|--------|
| **EP-BM-001** | Poslovni model e-Plaćanja | `docs/business-model/Business_Model_e-Placanje.md` | U IZRADI |
| **EP-FS-001** | Funkcionalna specifikacija e-Plaćanja | `docs/functional-specifications/Functional-Specification_e-Placanje.md` | U IZRADI |
| **EP-TS-001** | Tehnička specifikacija e-Plaćanja | `docs/technical-specifications/Technical-Specification_e-Placanje.md` | U IZRADI |
| **EP-KF-001** | Katalog finansijskih obaveza Opštine Kotor | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` | U IZRADI |
| **EP-PO-001** | Pravni okvir e-Plaćanja | `docs/pravni-okvir/Pravni_okvir_e-Placanje.md` | U IZRADI |
| **EP-RG-001** | Registar skraćenica i oznaka dokumentacije e-Plaćanja | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-e-Placanja.md` | USVOJENO |

Kanonski lanac sljedivosti:

`EP-BM-001 → EP-FS-001 → EP-TS-001`

## 3.3 Patch namespace

| Oznaka | Puni naziv | Značenje | Gdje se koristi |
|--------|------------|----------|-----------------|
| **EP-PATCH-BM** | Patch poslovnog modela e-Plaćanja | Identifikator izmjene EP-BM-001 (npr. EP-PATCH-BM-001 … EP-PATCH-BM-010). | EP-BM-001; navođenje u EP-TS-001 |
| **EP-PATCH-FS** | Patch funkcionalne specifikacije e-Plaćanja | Identifikator izmjene EP-FS-001 (npr. EP-PATCH-FS-001 … EP-PATCH-FS-010). | EP-FS-001 |
| **EP-PATCH-TS** | Patch tehničke specifikacije e-Plaćanja | Rezervisani prefiks za buduće izmjene EP-TS-001. Trenutno se TS vodi po broju verzije; ne postoje izdati EP-PATCH-TS-* identifikatori. | EP-TS-001 (rezervisano) |

Ovi prefiksi ne smiju se miješati sa `PATCH-FS-*`, `PATCH-BM-*` ili `PATCH-TS-*` Kalendara kulture.

## 3.4 Buduće oznake (ne kreirati dokumente sada)

Sljedeće oznake mogu se dodavati kontrolisano, kada za to postoji usvojena odluka. Dokumenti se **ne** kreiraju ovim registrom:

| Oznaka | Predviđena namjena |
|--------|--------------------|
| **EP-CR** | Change Request e-Plaćanja |
| **EP-IS** | Implementaciona strategija e-Plaćanja |
| **EP-IR** | Implementacioni roadmap e-Plaćanja |

---

# 4. Poslovne skraćenice

Registrovane su samo skraćenice koje se stvarno koriste u pet kanonskih dokumenata e-Plaćanja i u ovom registru.

| Oznaka | Puni naziv | Značenje | Gdje se koristi |
|--------|------------|----------|-----------------|
| **P** | Projektno načelo | Obavezujuća projektna odluka P-01 do P-08. | EP-BM-001, EP-FS-001, EP-TS-001, EP-PO-001, EP-KF-001 |
| **P-01** | Svrha modula | Modul služi isključivo elektronskom plaćanju finansijskih obaveza prema Opštini Kotor. | EP-BM-001 / 5; EP-FS-001; EP-TS-001; EP-PO-001 |
| **P-02** | Obuhvat V1 | Elektronski kanal za plaćanje obaveza koje se mogu platiti na blagajni. | EP-BM-001 / 5; EP-FS-001; EP-TS-001 |
| **P-03** | Granice odgovornosti | Nema obračuna, upravnih rješenja, zaduženja ni izvornih evidencija. | EP-BM-001 / 5; EP-FS-001; EP-TS-001; EP-PO-001 |
| **P-04** | Postojeći poslovni procesi | Modul ne uvodi nove obaveze niti mijenja postojeće procese Opštine. | EP-BM-001 / 5; EP-FS-001; EP-TS-001; EP-PO-001 |
| **P-05** | Regulatorna usklađenost | Svaka funkcionalnost mora imati pravni osnov. | EP-BM-001 / 5; EP-FS-001; EP-PO-001 |
| **P-06** | Dokumentacija | Razvoj se zasniva na pravnom okviru, katalogu, BM, FS i TS. | EP-BM-001 / 5; EP-FS-001; EP-KF-001 |
| **P-07** | Propis kao izvor istine | Pravni osnov se evidentira po propisanim poljima; bez pretpostavki. | EP-BM-001 / 5; EP-FS-001; EP-PO-001; EP-KF-001 |
| **P-08** | Izvorni sistem ostaje nadležan | Izvorni sistem / nadležni organ ostaje mjerodavan za podatke o obavezi. | EP-BM-001 / 5; EP-FS-001; EP-TS-001; EP-PO-001 |
| **F** | Funkcionalna odluka | Funkcionalna odluka obuhvata. | EP-BM-001, EP-FS-001, EP-KF-001, EP-PO-001 |
| **F-01** | Obavezni obuhvat V1 | Pojedinačne vrste uplata i uplatni računi iz projektnog spiska / Kataloga. | EP-BM-001 / 6; EP-FS-001 / 5; EP-KF-001; EP-PO-001 |
| **UR** | Uplatni računi | Odluka o tretmanu uplatnih računa. | EP-BM-001, EP-FS-001, EP-TS-001, EP-KF-001, EP-PO-001 |
| **UR-01** | Uplatni računi – referentni i konfiguracioni podaci | Katalog = poslovni referentni dokument (nije šifrarnik); aplikacija koristi konfiguracioni izvor; bez hardkodiranja računa. | EP-BM-001 / 6a; EP-FS-001 / 5.3; EP-TS-001 / 2.4 |
| **BP** | Poslovni proces / poslovna odluka | Usvojeni poslovni procesi BP-01 do BP-09. | EP-BM-001 / 9 i / 12; EP-FS-001 / 7; EP-TS-001 / 2 |
| **BP-01** | Pronalaženje vrste uplate | Hijerarhijski pregled i pretraga po nazivu. | EP-BM-001 / 9.1; EP-FS-001 / 7.1; EP-TS-001 / 2.5 |
| **BP-02** | Način popunjavanja podataka za plaćanje | Automatsko preuzimanje ili ručni unos prema integraciji. | EP-BM-001 / 9.2; EP-FS-001 / 7.2; EP-TS-001 / 2.5 |
| **BP-03** | Pregled i potvrda prije plaćanja | Obavezni pregled i izričita potvrda prije pokretanja. | EP-BM-001 / 9.3; EP-FS-001 / 7.3; EP-TS-001 / 2.5 |
| **BP-04** | Jedinstvena integracija sa sistemom elektronskog plaćanja | Jedna apstraktna integracija prema payment gateway sloju. | EP-BM-001 / 9.4; EP-FS-001 / 7.4; EP-TS-001 / 2.6 |
| **BP-05** | Obrada ishoda elektronskog plaćanja | Evidentiranje i prikaz ishoda transakcije. | EP-BM-001 / 9.5; EP-FS-001 / 7.5; EP-TS-001 / 2.7 |
| **BP-06** | Potvrda o izvršenom elektronskom plaćanju | Pregled i preuzimanje potvrde; nije službeni finansijski dokument. | EP-BM-001 / 9.6; EP-FS-001 / 7.6; EP-TS-001 / 2.8 |
| **BP-07** | Izvor obaveznih podataka za elektronsko plaćanje | Konfiguracija iznosa, primaoca, računa, poziva na broj i svrhe (BP-07.1 do BP-07.5). | EP-BM-001 / 9.7; EP-FS-001 / 7.7; EP-TS-001 / 2.9 |
| **BP-08** | Životni ciklus transakcije | Statusi, prelazi, audit; BP-08.1 do BP-08.5. | EP-BM-001 / 9.8; EP-FS-001 / 7.8; EP-TS-001 / 2.10 |
| **BP-09** | Istorija transakcija i pregled plaćanja | Pregled, filteri, detalji; BP-09.1 do BP-09.5. | EP-BM-001 / 9.9; EP-FS-001 / 7.9; EP-TS-001 / 2.11 |
| **BR-P** | Poslovno/funkcionalno pravilo e-Plaćanja | Identifikatori pravila u EP-FS-001 (BR-P-001 do BR-P-066). Prefiks **BR-P** razlikuje ih od BR Kalendara kulture. | EP-FS-001 / 8 |
| **AC** | Prihvatni kriterijumi (Acceptance Criteria) | Poglavlje prihvatnih kriterijuma V1; nije numerisani identifikator u ovoj fazi. | EP-FS-001 / 9; istorija verzija EP-FS-001 |
| **V1** | Prva faza / prva verzija obuhvata | Obuhvat prve faze modula. | EP-BM-001, EP-FS-001, EP-TS-001, EP-PO-001, EP-KF-001 |

---

# 5. Zabranjene legacy oznake

Sljedeće oznake su **istorijske**. Koristile su se u ranijoj dokumentaciji Plaćanja prije razdvajanja namespace-a. **Ove oznake se ne koriste u aktivnoj dokumentaciji e-Plaćanja.**

| Oznaka | Istorijska upotreba | Aktivni status |
|--------|---------------------|----------------|
| **FT-002** | Istorijski Feature ID dokumentacije Plaćanja. e-Plaćanje ne nasljeđuje FT-002. | Ne koristiti |
| **BM-002** | Istorijska oznaka poslovnog modela Plaćanja. Kanonski dokument je EP-BM-001. | Ne koristiti |
| **FS-002** | Istorijska oznaka funkcionalne specifikacije Plaćanja. Kanonski dokument je EP-FS-001. | Ne koristiti |
| **TS-002** | Istorijska oznaka tehničke specifikacije Plaćanja. Kanonski dokument je EP-TS-001. | Ne koristiti |
| **PATCH-FS-*** (bez EP-) | Istorijski FS patch-evi Plaćanja. Aktivni oblik je EP-PATCH-FS-*. | Ne koristiti u e-Plaćanju |
| **PATCH-BM-*** (bez EP-) | Istorijski BM patch-evi Plaćanja. Aktivni oblik je EP-PATCH-BM-*. | Ne koristiti u e-Plaćanju |

Napomena: ova tabela ne tvrdi da navedene oznake nikada nijesu korišćene. Evidentira da se više ne koriste u aktivnoj dokumentaciji e-Plaćanja.

`RG-001` pripada Kalendaru kulture i nije registar e-Plaćanja.

---

# 6. Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-08-17 | Verzija 1.0 — Uspostavljen EP-RG-001. Registrovan EP-* namespace, kanonski dokumenti, patch prefiksi, poslovne skraćenice iz postojećih dokumenata i zabranjene legacy oznake. |
