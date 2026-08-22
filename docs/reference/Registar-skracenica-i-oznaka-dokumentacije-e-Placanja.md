# Digital Kotor
# Registar skraćenica, oznaka i terminologije e-Plaćanja
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-RG-001
**Naziv:** Registar skraćenica, oznaka i terminologije e-Plaćanja
**Modul:** e-Plaćanje
**Status dokumenta:** USVOJENO
**Verzija:** 1.1.3
**Datum:** 2026-08-22

---

# 1. Identitet i svrha

EP-RG-001 je **SSOT** za dokumentacione oznake, poslovne prefikse i kanonsku terminologiju modula **e-Plaćanje**.

Ne definiše poslovna pravila (to je EP-BM-001). Ne definiše funkcionalne ni tehničke dizajne (EP-FS-001 / EP-TS-001). Ne zamjenjuje EP-KF-001 ni EP-PO-001.

Uključene su samo oznake koje se stvarno koriste u EP dokumentaciji, plus rezervisani prefiksi namespace-a, plus kanonski termini Koraka 6.

---

# 2. Cross-module granica

| Registar | Uloga | Odnos prema EP |
|----------|-------|----------------|
| **EP-RG-001** | SSOT za EP dokumentacione i EP poslovne oznake i EP terminologiju | ovaj dokument |
| **DK-RG-001** | Platformski registar Digital Kotor | nije EP registar; platform user-model termini se referenciraju, ne dupliraju kao novi EP pravni SSOT |
| **KK-RG-001** | Registar Kalendara kulture | nije EP registar |
| **DK-DS-001** | Platformski dokumentacioni standard | EP poštuje namespace `EP-*`; FR/BR-P ostaju module-internal |

e-Plaćanje i Kalendar kulture su potpuno odvojeni moduli.

Platformski dokumentacioni standard je `DK-DS-001`. Usvojeni namespace Konkursa je `KN-*` (dokumenti nijesu kreirani). Tenderi nemaju namespace. Ovaj registar ih ne vodi.

Ako bi EP paket trebao novu **projektnu/platformsku** oznaku koja po DK-DS-001 pripada DK-RG-001: **STOP** — ne širiti DK-RG ovim paketom.

---

# 3. Dokumentacione oznake (namespace / document IDs)

## 3.1 Prefiksi dokumenata

| Oznaka | Puni naziv | Značenje | Gdje se koristi |
|--------|------------|----------|-----------------|
| **EP** | e-Plaćanje | Prefiks dokumentacionog namespace-a modula e-Plaćanje. | Svi EP dokumenti |
| **EP-BM** | Poslovni model e-Plaćanja | Tip dokumenta: poslovni model. | EP-BM-001 |
| **EP-FS** | Funkcionalna specifikacija e-Plaćanja | Tip dokumenta: funkcionalna specifikacija. | EP-FS-001 |
| **EP-TS** | Tehnička specifikacija e-Plaćanja | Tip dokumenta: tehnička specifikacija. | EP-TS-001 |
| **EP-KF** | Katalog finansijskih obaveza | Tip dokumenta: katalog vrsta plaćanja i računa. | EP-KF-001 |
| **EP-PO** | Pravni okvir e-Plaćanja | Tip dokumenta: pravni okvir. Nije Product Owner. | EP-PO-001 |
| **EP-RG** | Registar skraćenica, oznaka i terminologije e-Plaćanja | Ovaj dokument. | EP-RG-001 |

## 3.2 Kanonski dokumenti (aktuelno)

| Oznaka | Dokument | Putanja | Status |
|--------|----------|---------|--------|
| **EP-BM-001** | Poslovni model e-Plaćanja | `docs/business-model/Business_Model_e-Placanje.md` | USVOJENO (V1 BM, Korak 6; v1.0.2; mapping USVOJENO) |
| **EP-FS-001** | Funkcionalna specifikacija e-Plaćanja | `docs/functional-specifications/Functional-Specification_e-Placanje.md` | U IZRADI (v1.1.2; mapping USVOJENO) |
| **EP-TS-001** | Tehnička specifikacija e-Plaćanja | `docs/technical-specifications/Technical-Specification_e-Placanje.md` | U IZRADI |
| **EP-KF-001** | Katalog finansijskih obaveza Opštine Kotor | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` | U IZRADI (v0.7; 17/41; mapping USVOJENO; F11 IMPLEMENTED locally; nije production complete; purpose/model/šifra/poziv OPEN; Bankart NOT IMPLEMENTED) |
| **EP-PO-001** | Pravni okvir e-Plaćanja | `docs/pravni-okvir/Pravni_okvir_e-Placanje.md` | U IZRADI |
| **EP-RG-001** | Registar skraćenica, oznaka i terminologije e-Plaćanja | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-e-Placanja.md` | USVOJENO |

Kanonski lanac sljedivosti:

`EP-BM-001 → EP-FS-001 → EP-TS-001`

Ne postoje aktivni EP dokumenti `BM-002`, `FS-002`, `TS-002`, `EP-BM-002`, `EP-FS-002`, `EP-TS-002` ili `FT-002`.

## 3.3 Patch namespace

| Oznaka | Puni naziv | Značenje | Gdje se koristi |
|--------|------------|----------|-----------------|
| **EP-PATCH-BM** | Patch poslovnog modela e-Plaćanja | Identifikator izmjene EP-BM-001 (EP-PATCH-BM-001 … EP-PATCH-BM-013). | EP-BM-001; navođenje u EP-TS-001 |
| **EP-PATCH-FS** | Patch funkcionalne specifikacije e-Plaćanja | Identifikator izmjene EP-FS-001 (EP-PATCH-FS-001 … EP-PATCH-FS-013). | EP-FS-001 |
| **EP-PATCH-TS** | Patch tehničke specifikacije e-Plaćanja | Identifikator izmjene EP-TS-001. Izdato: **EP-PATCH-TS-001**, **EP-PATCH-TS-002**, **EP-PATCH-TS-003**, **EP-PATCH-TS-004**, **EP-PATCH-TS-005**, **EP-PATCH-TS-006**, **EP-PATCH-TS-007**, **EP-PATCH-TS-008**, **EP-PATCH-TS-009**, **EP-PATCH-TS-010**, **EP-PATCH-TS-011**, **EP-PATCH-TS-012**, **EP-PATCH-TS-013**. | EP-TS-001 |

Ovi prefiksi ne smiju se miješati sa `PATCH-FS-*`, `PATCH-BM-*` ili `PATCH-TS-*` Kalendara kulture.

## 3.4 Buduće oznake (ne kreirati dokumente sada)

Sljedeće oznake mogu se dodavati kontrolisano, kada za to postoji usvojena odluka. Dokumenti se **ne** kreiraju ovim registrom:

| Oznaka | Predviđena namjena |
|--------|--------------------|
| **EP-CR** | Change Request e-Plaćanja |
| **EP-IS** | Implementaciona strategija e-Plaćanja |
| **EP-IR** | Implementacioni roadmap e-Plaćanja |

---

# 4. Poslovne skraćenice i prefiksi

Registrovane su samo skraćenice koje se stvarno koriste u kanonskim EP dokumentima i u ovom registru.

## 4.1 Projektna načela, funkcionalne i procesne odluke

| Oznaka | Puni naziv | Značenje | Gdje se koristi |
|--------|------------|----------|-----------------|
| **P** | Projektno načelo | Obavezujuća projektna odluka P-01 do P-08. | EP-BM-001, EP-FS-001, EP-TS-001, EP-PO-001, EP-KF-001 |
| **P-01** | Svrha modula | Korisnički inicirana elektronska uplata prema katalogu vrsta plaćanja i računa Opštine Kotor. | EP-BM-001; EP-FS-001; EP-TS-001; EP-PO-001 |
| **P-02** | Obuhvat V1 | Elektronski kanal za plaćanje obaveza koje se mogu platiti na blagajni; korisnik plaća u svoje ime. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **P-03** | Granice odgovornosti | Nema obračuna, upravnih rješenja, zaduženja ni izvornih evidencija. V1 ne pronalazi/preuzima rješenja. | EP-BM-001; EP-FS-001; EP-TS-001; EP-PO-001 |
| **P-04** | Postojeći poslovni procesi | Modul ne uvodi nove obaveze niti mijenja postojeće procese Opštine. | EP-BM-001; EP-FS-001; EP-TS-001; EP-PO-001 |
| **P-05** | Regulatorna usklađenost | Svaka funkcionalnost mora imati pravni osnov. | EP-BM-001; EP-FS-001; EP-PO-001 |
| **P-06** | Dokumentacija | Razvoj se zasniva na pravnom okviru, katalogu, BM, FS i TS. | EP-BM-001; EP-FS-001; EP-KF-001 |
| **P-07** | Propis kao izvor istine | Pravni osnov se evidentira po propisanim poljima; bez pretpostavki. | EP-BM-001; EP-FS-001; EP-PO-001; EP-KF-001 |
| **P-08** | Izvorni sistem ostaje nadležan | Izvorni sistem / nadležni organ ostaje mjerodavan za podatke o obavezi. V1 ne potvrđuje izmirenje. | EP-BM-001; EP-FS-001; EP-TS-001; EP-PO-001 |
| **F** | Funkcionalna odluka | Funkcionalna odluka obuhvata. | EP-BM-001, EP-FS-001, EP-KF-001, EP-PO-001 |
| **F-01** | Obavezni obuhvat V1 | 17 vrsta plaćanja i 41 račun iz Kataloga. **SUPERSEDE** stara ontologija 17 kategorija + 41 vrsta uplate. | EP-BM-001 / 6; EP-FS-001; EP-KF-001; EP-PO-001 |
| **UR** | Uplatni računi | Odluka o tretmanu uplatnih računa. | EP-BM-001, EP-FS-001, EP-TS-001, EP-KF-001, EP-PO-001 |
| **UR-01** | Uplatni računi – referentni i konfiguracioni podaci | Katalog = poslovni referentni dokument (nije šifrarnik); aplikacija koristi konfiguracioni izvor; bez hardkodiranja i bez ručnog unosa računa. | EP-BM-001; EP-FS-001; EP-TS-001 / 2.4 |
| **BP** | Poslovni proces / poslovna odluka | Usvojeni poslovni procesi BP-01 do BP-09. Aktivno značenje = EP-BM-001 (KEEP / UPDATE / SUPERSEDE). | EP-BM-001 / 9; EP-FS-001; EP-TS-001 / 2 |
| **BP-01** | Katalog vrsta plaćanja | Filterisani pregled i pretraga nad jednim katalogom. UPDATE. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-02** | Popunjavanje podataka | Profil + korisnički iznos; ne izvorni sistem. SUPERSEDE starog dual-mode preuzimanja. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-03** | Pregled i potvrda prije plaćanja | Formiranje → pregled → izričita potvrda → gateway; odustajanje = NO TRANSACTION. UPDATE. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-04** | Jedinstvena integracija sa payment gateway slojem | Jedna apstraktna integracija. KEEP. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-05** | Obrada ishoda | Server-confirmed gateway result; browser NOT AUTHORITATIVE. UPDATE. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-06** | Potvrda o uspješnoj transakciji | PDF/email samo Uspješna; nije dokaz izmirenja. UPDATE. | EP-BM-001; EP-FS-001; EP-TS-001; EP-PO-001 / 8 |
| **BP-07** | Podaci uplatnice | Iznos korisnik; svrha sistem; račun katalog. SUPERSEDE starih modela iznosa/svrhe iz izvornog sistema. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-08** | Životni ciklus transakcije | Četiri statusa; granica gateway start. SUPERSEDE Kreirana/U toku i zapisa prije gateway-a. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BP-08.5** | Potvrda izvornom sistemu | SUPERSEDE za V1 (nije dio kanonskog toka). | EP-BM-001; EP-TS-001 |
| **BP-09** | Istorija transakcija | ONLY OWN; bez brisanja kroz regularni UI. UPDATE. | EP-BM-001; EP-FS-001; EP-TS-001 |
| **BR-P** | Istorijski identifikator FR pravila | BR-P-001 do BR-P-066 u starijem EP-FS-001. **Istorijski**; aktivna FR oznaka je FR-* ispod. | EP-FS-001 / 11 |
| **AC** | Prihvatni kriterijumi (Acceptance Criteria) | Poglavlje prihvatnih kriterijuma V1; gateway AC OPEN. | EP-FS-001 |
| **V1** | Prva faza / prva verzija obuhvata | Obuhvat prve faze modula. Korak 6 = CLOSED V1 poslovni model. | Svi EP dokumenti |

## 4.2 Funkcionalni zahtjevi (FR) — EP-FS-001 v1.1.0

Prefiks **FR-** u e-Plaćanju je module-internal (DK-DS-001). Nije KK `FR-OB-*`. Nije novi 6.x poslovni ID.

| Prefiks | Namjena | Izdati ID-evi |
|---------|---------|---------------|
| **FR-CAT** | Katalog i filter | FR-CAT-01 … FR-CAT-04 |
| **FR-FLOW** | Tok plaćanja | FR-FLOW-01 … FR-FLOW-05 |
| **FR-ST** | Statusi, SSOT, idempotentnost, immutability | FR-ST-01 … FR-ST-04 |
| **FR-HIS** | Istorija | FR-HIS-01 |
| **FR-CONF** | Potvrda / PDF | FR-CONF-01 |
| **FR-MAIL** | Email | FR-MAIL-01 |
| **FR-ADM** | Administracija | FR-ADM-01 … FR-ADM-03 |

## 4.3 Nove oznake ovog corrective-a

Nisu uvedene nove kratke šifre tipa `EP-XYZ-001` za poslovna pravila.

Registrovani **novi** identifikatori u odnosu na EP-RG-001 v1.0:

* **EP-PATCH-BM-011**, **EP-PATCH-BM-012**
* **EP-PATCH-FS-011**, **EP-PATCH-FS-012**
* **EP-PATCH-TS-001**, **EP-PATCH-TS-002**
* **FR-CAT-***, **FR-FLOW-***, **FR-ST-***, **FR-HIS-***, **FR-CONF-***, **FR-MAIL-***, **FR-ADM-***

Kanonski termini Koraka 6 (poglavlje 5) nisu nove šifre.

Pravni oblici (DOO, AD, OD, KD, Nevladino udruženje, Sportska organizacija) nisu EP dokumentacione oznake; pripadaju platform user-modelu i referenciraju se, ne registruju se ovdje kao EP ID.

---

# 5. Terminološki katalog

Za svaki pojam: kanonski crnogorski naziv; eventualni tehnički engleski; značenje; gdje se koristi; šta se **ne** smije miješati.

| Kanonski naziv | Tehnički engleski | Značenje | Gdje se koristi | Ne miješati sa |
|----------------|-------------------|----------|-----------------|----------------|
| **Vrsta plaćanja** | payment type | Šta korisnik plaća. Kanonska jedinica kataloga V1 (17). | EP-BM-001, EP-KF-001, EP-FS-001, EP-TS-001 | Računom. Starom „kategorijom“ (17) ili starom „vrstom uplate“ (41). |
| **Račun** | account / credit account | Gdje se sredstva uplaćuju. 41 zapis. Pripada vrsti plaćanja. | EP-BM-001, EP-KF-001, UR-01 | Vrstom plaćanja. Ručnim unosom IBAN-a. |
| **Uplatilac** | payer | Identitet koji plaća, iz kanonskog user profila. Korisnik plaća u svoje ime. | EP-BM-001, EP-FS-001 | Drugim licem (`Plati za drugo lice` = OUT OF V1). |
| **Transakcija** | transaction | Finansijski pokušaj Digital Kotor koji postoji tek od gateway start-a. | EP-BM-001 BP-08, EP-FS-001, EP-TS-001 | Pregledom/potvrdom korisnika prije gateway-a. Izmirenom obavezom. |
| **U obradi** | in progress (gateway started) | Gateway proces pokrenut, rezultat nepoznat. Jedini početni status. | EP-BM-001, EP-FS-001, EP-TS-001 | Kreirana; U toku; pending. |
| **Uspješna** | successful | Gateway pouzdano potvrdio uspješno plaćanje. | EP-BM-001, EP-FS-001, EP-TS-001, EP-PO-001 | Potvrđenom izmirenom obavezom. Fiskalnim računom. |
| **Neuspješna** | failed | Gateway proces pokrenut i potvrđeno da plaćanje nije izvršeno. | EP-BM-001, EP-FS-001, EP-TS-001 | Odustajanjem prije gateway-a (NO TRANSACTION). |
| **Otkazana** | cancelled | Gateway proces pokrenut i gateway potvrdio otkazivanje. | EP-BM-001, EP-FS-001, EP-TS-001 | Odustajanjem prije gateway-a. |
| **Snapshot** | immutable snapshot | Nepromjenjivi presjek profila i uplatnice na nastanku transakcije. | EP-BM-001, EP-FS-001, EP-TS-001 | Current profile-om. Naknadnom izmjenom kataloga. |
| **Payment gateway** | payment gateway | Infrastruktura kartičnog plaćanja. Konačni status = server-confirmed result. | EP-BM-001 BP-04/BP-05, EP-TS-001 | Browser return-om. Digital Kotor kartičnom obradom. |
| **Potvrda o uspješnoj transakciji** | payment confirmation | Dokaz uspješne konkretne EP transakcije; PDF YES; samo Uspješna. | EP-BM-001 BP-06, EP-FS-001, EP-PO-001 / 8 | Fiskalnim računom; rješenjem; potvrdom izmirene obaveze. |
| **Declare-on-use gate** | declare-on-use gate | Existing FL/Preduzetnik bez kanonskog residential statusa izjavljuje status prije EP filtera. Bez auto-backfill-a. | EP-BM-001, EP-FS-001 | Automatskim izvođenjem iz JMB/pasoša/adrese/državljanstva. |
| **Availability** | availability | Pravilo koje vrste/račune smije koristiti data korisnička kategorija. Filter: korisnik → vrsta → račun(i). | EP-BM-001, EP-KF-001, EP-FS-001 | Legacy ciljnom grupom (`građani` / `pravna lica`) kao da je Korak 6 mapping. |
| **Aktivno / Neaktivno** | active / inactive | Status zapisa kataloga. Korišćeni račun se deaktivira, ne briše. Aktivno za korisnike samo COMPLETE + VALID. | EP-BM-001, EP-KF-001 | Brisanjem istorijskog zapisa. |
| **Pre-production dependency** | open pre-production dependency | Otvorena zavisnost prije produkcije. **Nije** ponovno otvaranje Koraka 6. | EP-BM-001 / 11; EP-TS-001; EP-PO-001 / 8; EP-KF-001 / 7 | Reopened 6.x poslovnom odlukom. |

### 5.1 Obavezne distinkcije

* `Vrsta plaćanja ≠ račun`
* `Uspješna transakcija ≠ potvrđena izmirena obaveza`
* `Browser return ≠ gateway SSOT`
* `Pravno lice ≠ resident/non-resident`
* `Rezidentnost ≠ državljanstvo`
* `Potvrda korisnika ≠ nastanak transakcije`
* `NO TRANSACTION ≠ Neuspješna / Otkazana`

### 5.2 Platform user-model termini (referenca, nije EP pravni SSOT)

Sljedeći termini pripadaju platformskom korisničkom modelu. EP ih koristi kao dependency; ne duplira kao novi EP pravni katalog.

Kanonski V1 skup (`CANONICAL USER TYPES = 8`):

* Fizička lica: Fizičko lice; Preduzetnik (fizičko lice / poslovna kategorija; nije pravno lice);
* Pravna lica: DOO; AD; OD; KD; Nevladino udruženje; Sportska organizacija;
* Rezident / Nerezident (`resident` / `non-resident`) — samo Fizičko lice i Preduzetnik.

„Javni sektor“ nije pravni oblik. Generičko `Ostalo` nije usvojeno.

Nisu aktivni V1 user types: Nevladina fondacija; Javna ustanova; Dio stranog privrednog društva; Druge organizacije.

Konkursna svojstva (Poljoprivrednik, Ribar, Marikulturista, Mladi preduzetnik, MSP veličina, Individualni sportista) nisu `user_type`.

`PLATFORM USER MODEL CORRECTIVE = COMPLETE` (application-level). `PRODUCTION LEGACY DATA CLEANUP = OPEN PRE-PRODUCTION`.

---

# 6. Legacy / superseded katalog

**LEGACY / SUPERSEDED — DO NOT USE FOR NEW EP V1 CONTENT**

Istorija se ne briše. Changelog i ovaj odjeljak smiju navoditi termine.

| Termin / oznaka | Istorijska upotreba | Aktivni status |
|-----------------|---------------------|----------------|
| **Kreirana** | Početni poslovni status transakcije prije/uz formiranje zapisa | Ne koristiti kao V1 status |
| **U toku** | Poslovni status dok se čeka ishod | Ne koristiti; kanonski: **U obradi** |
| **pending** | Stub/DB default i stari tehnički naziv | Nije kanonski V1 status |
| **Greška** | Kao poslovni status V1 | Ne koristiti |
| **17 kategorija + 41 vrsta uplate** | Stara KF ontologija; 1 račun po „vrsti uplate“ | SUPERSEDE; kanonski: 17 vrsta plaćanja → 41 račun |
| **Nevladina fondacija / Javna ustanova / Dio stranog privrednog društva** | Raniji širi EP user-model spisak | Nisu aktivni V1 user types; `LEGACY / COMPATIBILITY ONLY — NOT AVAILABLE FOR NEW REGISTRATION` |
| **kategorija** (kao 17 kanonskih jedinica) | Organizacija starog kataloga | Ne koristiti za novi V1 sadržaj |
| **vrsta uplate** (kao 41 kanonska jedinica) | Stara jedinica sa 1 računom | Ne koristiti; koristiti **račun** ili **vrsta plaćanja** prema Koraku 6 |
| **izvorni sistem** kao izvor iznosa/zaduženja u V1 toku | BP-02 / BP-07 stari model | SUPERSEDE za V1; P-08 ostaje granica nadležnosti |
| **potvrda izvornom sistemu** (BP-08.5) kao V1 tok | Outbound nakon Uspješne | SUPERSEDE za V1 |
| **ručni račun** | Korisnički unos broja računa | Zabranjeno |
| **fiksni iznos / predloženi iznos** | BP-07.1 stari konfiguracioni modeli | SUPERSEDE kao V1 default |
| **FT-002** | Istorijski Feature ID Plaćanja | Ne koristiti |
| **BM-002 / FS-002 / TS-002** | Istorijske oznake prije EP namespace-a | Ne koristiti; kanonski EP-*-001 |
| **PATCH-FS-*** / **PATCH-BM-*** (bez EP-) | Istorijski patch-evi Plaćanja | Ne koristiti u e-Plaćanju |
| **BR-P-001 … BR-P-066** | Stara numeracija FR | Istorijski; nova aktivna FR = FR-* |

Napomena: ova tabela ne tvrdi da navedeni termini nikada nijesu korišćeni. Evidentira da se **ne koriste kao aktivni V1 ugovor**.

---

# 7. Zabranjene legacy dokumentacione oznake

Isto pravilo kao v1.0, zadržano:

| Oznaka | Istorijska upotreba | Aktivni status |
|--------|---------------------|----------------|
| **FT-002** | Istorijski Feature ID dokumentacije Plaćanja. e-Plaćanje ne nasljeđuje FT-002. | Ne koristiti |
| **BM-002** | Istorijska oznaka poslovnog modela Plaćanja. Kanonski dokument je EP-BM-001. | Ne koristiti |
| **FS-002** | Istorijska oznaka funkcionalne specifikacije Plaćanja. Kanonski dokument je EP-FS-001. | Ne koristiti |
| **TS-002** | Istorijska oznaka tehničke specifikacije Plaćanja. Kanonski dokument je EP-TS-001. | Ne koristiti |

`KK-RG-001` pripada Kalendaru kulture i nije registar e-Plaćanja.

---

# 8. Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-08-17 | Verzija 1.0 — Uspostavljen EP-RG-001. Registrovan EP-* namespace, kanonski dokumenti, patch prefiksi, poslovne skraćenice iz postojećih dokumenata i zabranjene legacy oznake. |
| 2026-08-17 | Granica prema Kalendaru kulture: referentna oznaka registra KK ažurirana sa `RG-001` na `KK-RG-001`. Poslovni sadržaj e-Plaćanja neizmijenjen. |
| 2026-08-17 | Granica: pointer na `DK-DS-001`; `KN-*` rezervisan (nije EP). `EP-PO-001` KEEP. Poslovni sadržaj e-Plaćanja neizmijenjen. |
| 2026-08-20 | Verzija 1.1 — Proširenje u puni katalog skraćenica, oznaka i terminologije usklađen sa Korakom 6. Terminološki katalog; legacy/superseded termini; FR-* prefiksi; EP-PATCH-TS-001; ažurirani statusi kanonskih dokumenata. |
| 2026-08-20 | Verzija 1.1.1 — Platform user-model termini usklađeni sa kanonskih 8 tipova. EP-PATCH-BM-012 / FS-012 / TS-002. Bez novog registra/dokumenta. |
| 2026-08-22 | Verzija 1.1.2 — Aktuelni status EP-KF-001: mapping USVOJENO (F11 locally; nije production complete). Izdati EP-PATCH-TS identifikatori 001–013. Bez nove poslovne skraćenice. |
| 2026-08-22 | Verzija 1.1.3 — Aktuelni status EP-BM-001 v1.0.2 / EP-FS-001 v1.1.2: mapping USVOJENO. Izdati EP-PATCH-BM/FS kroz 013. |
