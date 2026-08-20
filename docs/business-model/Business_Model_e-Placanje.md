# Digital Kotor
# Poslovni model e-Plaćanja
## Modul: e-Plaćanje

**Oznaka dokumenta:** EP-BM-001
**Modul:** e-Plaćanje
**Status dokumenta:** USVOJENO
**Verzija:** 1.0.1
**Datum usvajanja poslovnog modela V1:** 2026-08-20 (Korak 6 — CLOSED)

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Business Modela modula e-Plaćanja. Unesena usvojena projektna načela P-01 do P-08 i funkcionalna odluka F-01. |
| EP-PATCH-BM-001 | 2026-07-27 | UR-01 – Uplatni računi: referentni podaci u Katalogu; Katalog ≠ šifrarnik; aplikacija koristi konfiguracioni izvor. |
| EP-PATCH-BM-002 | 2026-07-27 | BP-01, BP-02, BP-03 – Pronalaženje vrste uplate; način popunjavanja podataka; pregled i potvrda prije plaćanja. |
| EP-PATCH-BM-003 | 2026-07-27 | BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja. |
| EP-PATCH-BM-004 | 2026-07-27 | BP-05 – Obrada ishoda elektronskog plaćanja. |
| EP-PATCH-BM-005 | 2026-07-27 | BP-06 – Potvrda o izvršenom elektronskom plaćanju. |
| EP-PATCH-BM-006 | 2026-07-27 | BP-07 – Izvor obaveznih podataka za elektronsko plaćanje (BP-07.1 do BP-07.5). |
| EP-PATCH-BM-007 | 2026-07-27 | BP-08 – Životni ciklus transakcije (BP-08.1 do BP-08.5). |
| EP-PATCH-BM-008A | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-06/BP-08: korisnička poruka ≠ status; početni status Kreirana; potvrda izvornom sistemu ≠ knjiženje. |
| EP-PATCH-BM-008B | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-08: evidencija bilježi trenutni status transakcije (ne „konačni status“ u polju evidencije). |
| EP-PATCH-BM-009 | 2026-07-27 | BP-09 – Istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| EP-PATCH-BM-009A | 2026-07-27 | Redakcijsko usklađivanje: BP-06↔BP-09 (istorija); terminološko razdvajanje identifikatora transakcije. |
| EP-PATCH-BM-010 | 2026-08-17 | Dokumentacioni corrective: oznaka EP-BM-001; namespace EP-*; naziv modula e-Plaćanje. Bez izmjene poslovnih odluka. |
| EP-PATCH-BM-011 | 2026-08-20 | Usklađivanje sa zatvorenim Korakom 6 (V1 poslovni model CLOSED). SUPERSEDE: ontologija 17 kategorija + 41 vrsta uplate; statusi Kreirana/U toku; preuzimanje obaveze/iznosa iz izvornog sistema; zapis transakcije prije gateway-a. KEEP: P-01–P-08 u suštini granica; UR-01; BP-04. Bez application implementation-a. |
| EP-PATCH-BM-012 | 2026-08-20 | Usklađivanje EP korisničkog modela sa kanonskim platform V1 user modelom (8 kategorija). Identity vs eligibility granica. Platform application corrective COMPLETE; production data cleanup OPEN. Bez application implementation-a. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument je **poslovni SSOT** zatvorenog V1 modela e-Plaćanja (Korak 6, 2026-08-20).

Korak 6 se ne reinterpretira. Pre-production zavisnosti (poglavlje 11) **nisu** ponovno otvaranje Koraka 6.

Terminologija: EP-RG-001.

---

# Status razvoja Business Modela

| Poglavlje | Status | Korak 6 |
|-----------|--------|---------|
| 1. Uvod | USVOJENO | KEEP |
| 2. Svrha V1 | USVOJENO | UPDATE (P-01) |
| 3. Ciljevi | USVOJENO | UPDATE |
| 4. Opseg i granice | USVOJENO | UPDATE (P-02, P-03, P-04, P-08) |
| 5. Projektna načela P-01 do P-08 | USVOJENO | KEEP suštine; UPDATE primjene |
| 6. Obavezni obuhvat V1 (F-01) | USVOJENO | SUPERSEDE ontologije |
| 6a. Uplatni računi (UR-01) | USVOJENO | KEEP + UPDATE filtera |
| 7. Poslovni entiteti | USVOJENO | UPDATE |
| 8. Korisnički model relevantan za EP | USVOJENO | NOVO (Korak 6) |
| 9. Poslovni procesi BP-01 do BP-09 | USVOJENO | UPDATE / SUPERSEDE |
| 10. Administracija kataloga i transakcija | USVOJENO | NOVO (Korak 6) |
| 11. Pre-production zavisnosti | USVOJENO kao OPEN lista | NOVO |
| 12. Veza sa dokumentacijom | USVOJENO | KEEP |
| 13. Registar usvojenih odluka | USVOJENO | UPDATE |

---

# Pravila upravljanja Business Modelom

1. Poslovni model predstavlja zvaničnu poslovnu specifikaciju modula e-Plaćanja (EP-BM-001).
2. Zatvoreni Korak 6 (2026-08-20) je jedini izvor istine za V1 poslovna pravila.
3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu ili projektnu odluku.
4. Ako postoji razlika između starijeg BP teksta i Koraka 6: **KORAK 6 WINS**.
5. Ako postoji razlika između implementacije i ovog dokumenta, implementacija se usklađuje sa Business Modelom.
6. Application implementation **nije** predmet ovog dokumenta. Stub ostaje stub dok PO ne odobri implementaciju.

---

## Sadržaj

1. Uvod
2. Svrha V1
3. Ciljevi
4. Opseg i granice
5. Projektna načela (P-01 do P-08)
6. Obavezni obuhvat V1 (F-01)
6a. Uplatni računi (UR-01)
7. Poslovni entiteti
8. Korisnički model relevantan za EP
9. Poslovni procesi (BP-01 do BP-09)
10. Administracija kataloga i transakcija
11. Pre-production zavisnosti
12. Veza sa dokumentacijom
13. Registar usvojenih odluka

---

# 1. Uvod

Modul e-Plaćanje je funkcionalnost platforme Digital Kotor.

Ovaj dokument definiše zatvoreni V1 poslovni model. Ne projektuje UI, bazu, API, gateway protokol ni application kod.

---

# 2. Svrha V1

**Status:** USVOJENO (P-01, Korak 6)

V1 e-Plaćanja je:

> servis za korisnički iniciranu elektronsku uplatu prema kontrolisanom katalogu vrsta plaćanja i računa Opštine Kotor.

Korisnik plaća **u svoje ime**.

`Plati za drugo lice` = **OUT OF V1**

---

# 3. Ciljevi

**Status:** USVOJENO

* omogućiti korisniku da elektronski uplati prema katalogu Opštine Kotor;
* ostati u granicama P-03 / P-04 / P-08 i Koraka 6 (modul ne utvrđuje obavezu);
* osigurati usklađenost sa pravnim okvirom (P-05, P-07);
* evidentirati ishod konkretne EP transakcije, bez tvrdnje da je obaveza izmirena.

---

# 4. Opseg i granice

**Status:** USVOJENO (P-02, P-03, P-04, P-08, Korak 6)

## 4.1 Šta V1 jeste

Elektronski kanal za korisnički iniciranu uplatu iz kontrolisanog kataloga (17 vrsta plaćanja, 41 račun — EP-KF-001).

## 4.2 Šta V1 NE radi

V1 **NE**:

* pronalazi korisnikova rješenja;
* preuzima rješenja;
* preuzima zaduženja;
* utvrđuje postojanje obaveze;
* izračunava visinu obaveze;
* predlaže iznos obaveze;
* vodi saldo obaveze;
* potvrđuje da je konkretnom uplatom obaveza izmirena.

## 4.3 Out of V1

* `Plati za drugo lice`
* refund kroz user portal
* ručni unos računa primaoca
* ručna izmjena statusa transakcije od strane administratora

---

# 5. Projektna načela (P-01 do P-08)

**Status:** USVOJENO

Suština načela ostaje. Primjena je usklađena sa Korakom 6 (EP-PATCH-BM-011). Tekst se ne proširuje novim P-ID-evima.

## P-01 – Svrha modula — KEEP / UPDATE

Modul **e-Plaćanje** služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor.

**Korak 6 primjena:** isključivo korisnički inicirana uplata prema kontrolisanom katalogu; ne servis za pronalaženje ili izmirenje utvrđene obaveze u izvornom sistemu.

## P-02 – Obuhvat V1 — KEEP / UPDATE

Modul omogućava online plaćanje prema katalogu koji odgovara prihodima koji se mogu platiti na blagajni Opštine Kotor, u obuhvatu projektnog spiska (F-01 / EP-KF-001).

**Korak 6 primjena:** V1 je elektronski kanal za uplatu, ne kanal koji preuzima zaduženje sa blagajne.

## P-03 – Granice odgovornosti — KEEP

Modul e-Plaćanje:

* ne obračunava finansijske obaveze;
* ne donosi upravna rješenja;
* ne kreira zaduženja;
* ne vodi izvorne evidencije finansijskih obaveza.

**Korak 6:** ranija rečenica da je uloga „plaćanje već utvrđenih obaveza“ ne smije se čitati kao da Digital Kotor utvrđuje ili preuzima konkretnu obavezu.

## P-04 – Postojeći poslovni procesi — KEEP

Modul e-Plaćanje ne uvodi nove finansijske obaveze niti mijenja postojeće poslovne procese Opštine Kotor.

## P-05 – Regulatorna usklađenost — KEEP

Svaka funkcionalnost modula mora imati odgovarajući pravni osnov u zakonima Crne Gore ili važećim propisima Opštine Kotor.

## P-06 – Dokumentacija — KEEP

Razvoj modula zasniva se na: EP-PO-001, EP-KF-001, EP-BM-001, EP-FS-001, EP-TS-001. Oznake: EP-RG-001.

## P-07 – Propis kao izvor istine — KEEP

Svaka vrsta plaćanja mora biti povezana sa pravnim osnovom kada je potvrđen. Ako nije: **Potrebno pravno potvrditi.** Ne unose se pretpostavljeni pravni podaci.

## P-08 – Izvorni sistem ostaje nadležan — KEEP / UPDATE

Za stvarnu finansijsku obavezu izvorni informacioni sistem ili nadležni organ Opštine Kotor ostaje jedini mjerodavan izvor.

**Korak 6 SUPERSEDE primjene:** V1 **ne** preuzima podatke obaveze iz izvornog sistema radi popunjavanja uplatnice. V1 **ne** potvrđuje izvornom sistemu da je obaveza izmirena. Izvorni sistem ostaje mjerodavan; Digital Kotor evidentira samo ishod sopstvene EP transakcije.

---

# 6. Obavezni obuhvat V1 (F-01)

**Status:** USVOJENO — ontologija SUPERSEDE (Korak 6)

## F-01 — aktivna formulacija

V1 podržava katalog iz projektnog spiska (Naredba o načinu uplate javnih prihoda, „Službeni list Crne Gore“, br. 006/25 od 29.01.2025.), u dijelu obuhvaćenom projektom:

* **17 vrsta plaćanja** — *šta* korisnik plaća;
* **41 račun** — *gdje* se sredstva uplaćuju.

Jedna vrsta plaćanja može imati jedan ili više računa.

Spisak se ne dopunjava samostalnim tumačenjem propisa.

Konačno mapiranje 17/41 na korisničke kategorije i payment rules = **OPEN PRE-PRODUCTION DEPENDENCY** (poglavlje 11, stavka 13). Ne izmišlja se u ovom dokumentu.

## F-01 — SUPERSEDE

Sljedeća historijska ontologija **nije** kanonska za V1:

> 17 kategorija + 41 vrsta uplate; svaka podstavka = zasebna vrsta uplate; kategorije samo za prikaz.

To je legacy KF model. Aktivno: `17 vrsta plaćanja → 41 račun`.

---

# 6a. Uplatni računi (UR-01)

**Status:** USVOJENO — KEEP + UPDATE

Brojevi računa u EP-KF-001 su **referentni podaci** iz Naredbe. Navođenje u dokumentaciji **nije** hardkodiranje.

Katalog je **poslovni referentni dokument**, nije aplikacioni šifrarnik.

Aplikacija (kada bude implementirana) koristi konfiguracioni izvor izveden iz Kataloga. Računi se **ne** hardkodiraju.

### Korak 6 pravila računa — UPDATE

* Korisnik **ne može** ručno unijeti proizvoljan račun primaoca.
* Račun uvijek dolazi iz kontrolisanog kataloga.
* Kanonski filter: `korisnik → dozvoljena vrsta plaćanja → dozvoljeni račun(i)`.
* Račun **ne može** proširiti pravo koje korisnik nema na nivou vrste.
* 1 dozvoljeni račun: sistem ga može automatski odabrati.
* 2+ dozvoljenih: korisnik bira između dozvoljenih.
* Vrsta se korisniku prikazuje samo ako postoji najmanje jedan **aktivan**, **validno konfigurisan** i **dozvoljen** račun za njegovu kategoriju.
* Korišćeni račun se **ne briše**; deaktivira se.
* Promjena broja računa = `deactivate` starog + **novi** catalog record. Opisni podaci se mogu uređivati.
* Promjena kataloga ne mijenja istorijske transakcije.

---

# 7. Poslovni entiteti

**Status:** USVOJENO (konceptualno; bez SQL šeme)

| Entitet | Značenje |
|---------|----------|
| Vrsta plaćanja | Šta korisnik plaća (17 u V1 katalogu) |
| Račun | Gdje se sredstva uplaćuju (41 u V1 katalogu) |
| Uplatilac | Kanonski Digital Kotor user profile; na transakciji: immutable snapshot |
| Transakcija | Poslovni zapis pokušaja plaćanja koji je prihvaćen/pokrenut prema gateway-u |
| Potvrda o uspješnoj transakciji | Dokaz konkretne uspješne EP transakcije; nije fiskalni račun ni izmirenje obaveze |
| Payment gateway | Infrastruktura kartičnog plaćanja; SSOT konačnog statusa |

**SUPERSEDE entiteta:** „Kategorija uplate“ kao jedinica V1 kataloga (17). Kategorija u starom KF smislu više nije kanonski entitet.

---

# 8. Korisnički model relevantan za EP

**Status:** USVOJENO (Korak 6; usklađeno sa kanonskim platform V1 user modelom)

EP **ne** definiše sopstvene user types. Koristi kanonski platformski `user_type`.

Osnovna kategorija korisnika određuje identitet/oblik korisnika.

Svojstva potrebna za pravo učešća na konkretnom konkursu predstavljaju zaseban eligibility sloj i ne postaju automatski `user_type` Digital Kotora.

Nisu `user_type`: Poljoprivrednik; Registrovani poljoprivredni proizvođač; Ribar; Marikulturista; Mladi preduzetnik; Mikro / Malo / Srednje preduzeće; Individualni sportista.

`CANONICAL USER TYPES = 8`

| Canonical type | Legal nature | Storage value |
|---|---|---|
| Fizičko lice | fizičko lice | `Fizičko lice` |
| Preduzetnik | fizičko lice (poslovna kategorija) | `Preduzetnik` |
| DOO | pravno lice | `Društvo sa ograničenom odgovornošću` |
| AD | pravno lice | `Akcionarsko društvo` |
| OD | pravno lice | `Ortačko društvo` |
| KD | pravno lice | `Komanditno društvo` |
| Nevladino udruženje | pravno lice | `Nevladino udruženje` |
| Sportska organizacija | pravno lice | `Sportska organizacija` |

`STORAGE VALUE = full legal name`. `DOO` / `AD` / `OD` / `KD` su kanonske labele/kodovi, ne drugi user model.

Lista se ne proširuje bez PO odluke. Nisu aktivni V1 user types: Nevladina fondacija; Javna ustanova; Privatna ustanova; Dio stranog privrednog društva; Politička partija; Vjerska zajednica; Komora; Sindikat; Druge organizacije; generičko `Ostalo`.

Legacy ENUM vrijednosti mogu privremeno ostati u storage-u radi production safety. To je `LEGACY / COMPATIBILITY ONLY — NOT AVAILABLE FOR NEW REGISTRATION`. Nisu kanonske V1 kategorije i ne nabrajaju se u aktivnoj listi.

`PLATFORM USER MODEL CORRECTIVE = COMPLETE`

`PRODUCTION LEGACY DATA CLEANUP = OPEN PRE-PRODUCTION`

`FINAL 17/41 USER CATEGORY MAPPING = OPEN`

## 8.1 Fizičko lice i Preduzetnik — rezidentnost

`residential_status` se primjenjuje **samo** na:

* Fizičko lice
* Preduzetnik

Kanonske vrijednosti:

* `resident` — Rezident
* `non-resident` — Nerezident

`ex-non-resident` **nije** kanonska kategorija (uklonjen iz aktivnog modela).

Rezidentnost **nije** državljanstvo. **Ne izvodi se** iz JMB-a, pasoša, adrese, državljanstva niti broja dana. Digital Kotor je **ne izračunava**. Korisnik status **izjavljuje**.

## 8.2 Existing user bez kanonskog statusa

`DECLARE-ON-USE GATE`

Tek kada Fizičko lice / Preduzetnik (NULL / unknown status) pristupi funkciji kojoj je status potreban, mora izabrati Rezident ili Nerezident.

Nema auto-backfill-a, auto-mapiranja ni ponovne registracije.

Pravno lice **ne** prolazi declare-on-use.

## 8.3 Preduzetnik

Preduzetnik je **fizičko lice** i posebna poslovna kategorija fizičkog lica. Zadržava identitet fizičkog lica. Eligible je za `residential_status`. **Nije** pravno lice.

## 8.4 Pravno lice

Pravno lice **NEMA** `residential_status` u kanonskom poslovnom modelu.

Postojeći production-like `resident` na pravnim licima = **PRE-PRODUCTION PLATFORM DATA DEPENDENCY**. Ne koristi se za EP filter. Application write path više ne postavlja taj fallback.

Kanonska pravna lica V1: DOO, AD, OD, KD, Nevladino udruženje, Sportska organizacija.

Generičko `Ostalo` **nije** usvojeno. „Javni sektor“ **nije** pravni oblik.

---

# 9. Poslovni procesi (BP-01 do BP-09)

**Status:** USVOJENO — UPDATE / SUPERSEDE prema Koraku 6

## 9.0 Kanonski tok

`formiranje naloga` → `pregled` → `izričita potvrda korisnika` → `gateway`

Prije gateway-a korisnik može: nazad; izmijeniti vlastite unose/izbore; odustati.

Odustajanje prije gateway-a: **NO TRANSACTION**. Nije `Otkazana`. Nije `Neuspješna`.

Neposredno prije pokušaja kreiranja transakcije sistem ponovo provjerava: korisnika, profil, prava, dostupnost vrste, dostupnost računa, validnost konfiguracije.

## 9.1 BP-01 – Pronalaženje vrste plaćanja — UPDATE

Korisnik vidi samo vrste plaćanja dozvoljene njegovoj kategoriji za koje postoji najmanje jedan aktivan, validan i dozvoljen račun.

Pregled i pretraga rade nad **istim** katalogom (EP-KF-001 / izvedeni šifrarnik). Nema paralelne liste.

Ako nema nijedne dostupne vrste: modul ostaje dostupan sa **empty state**. Istorija i potvrde ostaju dostupne.

## 9.2 BP-02 – Popunjavanje podataka — SUPERSEDE + nova formulacija

**SUPERSEDE:** automatsko preuzimanje podataka obaveze iz izvornog informacionog sistema; korisnik bira ručni unos vs integraciju.

**Aktivno:**

* uplatilac = current canonical Digital Kotor profile; korisnik ne unosi drugi identitet;
* iznos = **korisnik unosi** (BP-07.1);
* račun = iz kataloga (UR-01 / 6a);
* svrha = osnovni tekst formira sistem prema vrsti plaćanja;
* poziv na broj = per vrsta/račun (system / user input / optional / N/A);
* model i šifra plaćanja = sistemski; korisnik ih ne određuje proizvoljno.

## 9.3 BP-03 – Pregled i potvrda — UPDATE

Obavezni pregled i **izričita** potvrda prije gateway-a.

Sama potvrda korisnika **nije** dovoljna za nastanak transakcije (BP-08.1).

Potvrda korisnika **nije** potvrda uspjeha plaćanja.

## 9.4 BP-04 – Jedinstvena integracija — KEEP

Jedna tehnička integracija prema payment gateway sloju. Broj računa ne uslovljava posebne integracije. Gateway je infrastruktura. Zamjena gateway-a ne mijenja poslovni tok korisnika.

Konkretna banka, protokol, webhook/callback/status API = **OPEN PRE-PRODUCTION DEPENDENCY**.

## 9.5 BP-05 – Ishod plaćanja — UPDATE

Konačni status = **server-confirmed gateway result**.

Browser return = **NOT AUTHORITATIVE**.

Digital Kotor **ne nagađa** status.

Korisničke poruke nisu novi statusi.

Ako gateway dostavi kontradiktorne rezultate: **ne** važi `last response wins`. Čuvaju se rezultati; status se razrješava prema autoritativnom gateway stanju. Tačan mehanizam = pre-production dependency.

## 9.6 BP-06 – Potvrda o uspješnoj transakciji — UPDATE

Samo za status **Uspješna**.

Potvrda **jeste** dokaz uspješno izvršene konkretne EP transakcije.

Potvrda **nije**: fiskalni račun; rješenje; dokaz da je obaveza izmirena.

**PDF = YES.**

Minimum sadržaja: snapshot uplatioca; vrsta plaćanja; račun; iznos; svrha; poziv/model/šifra kada se koriste; datum/vrijeme; Digital Kotor transaction ID; gateway reference ako postoji; status Uspješna; jasno objašnjenje prirode potvrde.

**Email:** automatski samo za Uspješna, na email važeći u trenutku transakcije (snapshot ga čuva). Neuspjelo slanje **ne** mijenja finansijski status. Korisnik iz detalja Uspješne može zatražiti ponovno slanje na **trenutni validni** email naloga. Resend ne mijenja snapshot.

## 9.7 BP-07 – Podaci uplatnice — SUPERSEDE djelimično

### BP-07.1 Iznos — SUPERSEDE

**SUPERSEDE:** fiksni iznos; iznos iz izvornog sistema; predloženi iznos.

**Aktivno:** korisnik unosi iznos. Valuta **EUR**. Iznos **> 0**. Najviše **2 decimale**. Nema univerzalnog min/max dok gateway ili konkretno plaćanje to ne zahtijeva.

Digital Kotor ne izračunava, ne predlaže i ne potvrđuje visinu obaveze.

**Provizija:** snosi Opština Kotor. Korisnik: **NO ADDITIONAL FEE**. Iznos terećenja kartice = iznos koji je korisnik potvrdio.

### BP-07.2 Primalac — KEEP / UPDATE

Primalac dolazi iz konfiguracije kataloga. Korisnik ga ne mijenja.

### BP-07.3 Račun — UPDATE

Vidi 6a. Korisnik ne unosi proizvoljan račun. Biraju se samo dozvoljeni računi (1 auto / 2+ izbor).

### BP-07.4 Poziv na broj — UPDATE

Definiše se per vrsta plaćanja i račun. Može biti: sistemski; korisnički unos; optional; not applicable.

### BP-07.5 Svrha — SUPERSEDE ručnog/izvornog modela kao default

**Aktivno:** osnovnu svrhu formira sistem prema vrsti plaćanja.

**SUPERSEDE kao V1 default:** „bez svrhe“, svrha iz izvornog sistema, slobodan ručni unos svrhe kao opšti model.

## 9.8 BP-08 – Životni ciklus transakcije — SUPERSEDE statusnog modela

### BP-08.1 Granica nastanka — SUPERSEDE

**SUPERSEDE:** zapis se kreira odmah nakon potvrde korisnika, prije gateway-a, sa statusom **Kreirana**.

**Aktivno:** sama potvrda korisnika nije dovoljna. Transakcija dobija status **U obradi** tek kada Digital Kotor pouzdano utvrdi da je konkretan pokušaj plaćanja **prihvaćen/pokrenut** prema payment gateway-u.

Ako gateway proces nije pokrenut: **NO TRANSACTION**. Nema `Neuspješna`. Nema `Otkazana`.

Na nastanku: **immutable historical snapshot** (profil, vrsta, račun, iznos, svrha, email tada, konfiguracija).

### BP-08.2 Statusi — SUPERSEDE

V1 ima **tačno četiri** statusa:

1. **U obradi** — gateway proces pokrenut, konačni rezultat nepoznat.
2. **Uspješna** — gateway pouzdano potvrdio uspješno plaćanje. Konačni status osnovne transakcije.
3. **Neuspješna** — gateway proces pokrenut i gateway pouzdano potvrdio da plaćanje nije izvršeno.
4. **Otkazana** — gateway proces pokrenut i gateway pouzdano potvrdio otkazivanje.

**REMOVE / SUPERSEDE kao V1 poslovni statusi:** `Kreirana`, `U toku`, `pending`, `Greška`.

Odustajanje prije gateway-a **nije** `Otkazana`.

### BP-08.3 Promjena statusa — UPDATE

Status mijenja isključivo autoritativni gateway rezultat (ili ugovoreni status-check mehanizam). Administrator **ne bira** rezultat.

Samo vrijeme **ne** mijenja `U obradi`.

Admin može pokrenuti **Provjeri status** ako gateway to podržava. Ako ne podržava: ostaje `U obradi` dok se pouzdano ne razriješi drugim ugovorenim mehanizmom.

### BP-08.4 Prelazi — UPDATE

Dozvoljeno:

* U obradi → Uspješna
* U obradi → Neuspješna
* U obradi → Otkazana

Konačni statusi se ne vraćaju unazad. Originalna **Uspješna** ostaje Uspješna i ako kasnije postoji reversal/refund/chargeback kao **odvojen linked event** (OUT OF V1 za user portal refund; event model nije definisan).

### BP-08.5 Potvrda izvornom sistemu — SUPERSEDE za V1

**SUPERSEDE:** obavezna/opciona dostava potvrde izvornom sistemu kao dio V1 životnog ciklusa.

**Aktivno:** V1 ne potvrđuje izvornom sistemu da je obaveza izmirena. P-08 ostaje: izvorni sistem je mjerodavan za obavezu.

### BP-08.6 Idempotentnost — NOVO u okviru BP-08

Jedna korisnička potvrda: **max 1 transakcija**, **max 1 gateway attempt**.

Dupli klik / refresh / retry istog zahtjeva: **NO NEW TRANSACTION**.

Ponovljeni isti gateway callback: **NO DUPLICATE EFFECT**.

Svjesni novi pokušaj nakon Neuspješna ili Otkazana: **NEW TRANSACTION + NEW SNAPSHOT**.

Sistem ne zaključuje da su dvije nove uplatnice ista obaveza samo zbog istog iznosa, računa, svrhe ili poziva na broj. V1 ne poznaje stvarnu obavezu. Blokira se ponovno pokretanje iste postojeće transakcije `U obradi`.

Tehnički ključevi/tokeni se **ne** definišu u BM.

### BP-08.7 Immutability — UPDATE (bivši BP-03 nastavak)

Nakon gateway start-a transakcija je immutable. Korisnik ne uređuje U obradi / Uspješnu / Neuspješnu / Otkazanu.

Naknadna promjena profila, kataloga, računa, naziva, svrhe ili pravila **ne** mijenja istorijsku transakciju.

Administrator smije mijenjati katalog i dok postoje `U obradi` transakcije. Promjena važi samo za **nova** plaćanja.

## 9.9 BP-09 – Istorija — UPDATE

Korisnik vidi **ONLY OWN TRANSACTIONS**, sve četiri statusa.

Minimum liste: datum/vrijeme; vrsta plaćanja; iznos; status; Digital Kotor transaction ID.

Detalj može prikazati snapshot.

Korisnik **ne može** brisati. Administrator **ne može** brisati kroz regularni admin UI.

User account lifecycle **ne briše** automatski finansijsku istoriju.

Retention / brisanje / anonimizacija: **PRE-PRODUCTION LEGAL / REGULATORY REVIEW REQUIRED**. BM ne određuje proizvoljan rok.

---

# 10. Administracija kataloga i transakcija

**Status:** USVOJENO (Korak 6)

## 10.1 Katalog

Vlasnik: **Administrator platforme**.

Obični korisnik ne mijenja katalog.

Administrator može: kreirati/uređivati vrstu plaćanja; dodavati račun; aktivirati/deaktivirati; definisati availability; definisati sistemske parametre plaćanja.

Aktivacija za korisnike samo ako je konfiguracija **COMPLETE + VALID**.

Sve poslovno značajne izmjene kataloga imaju audit: **ko**, **kada**, **šta**. Tehnički action kodovi nisu usvojeni.

## 10.2 Modul offline / disabled

Administrator može privremeno zaustaviti **nova plaćanja**.

Tada korisnik ne može pokrenuti novu transakciju. Istorija, detalji i potvrde ostaju dostupni. Existing `U obradi` nastavljaju gateway lifecycle.

## 10.3 Administracija transakcija

Administrator može: pregledati sve transakcije; snapshot; status; status history; relevantne gateway reference; inicirati provjeru `U obradi` ako gateway to omogućava.

Administrator **NE može:** ručno proglasiti Uspješna / Neuspješna / Otkazana; brisati finansijsku transakciju kroz regularni UI.

## 10.4 Kartični podaci

Digital Kotor **ne** prikuplja, **ne** obrađuje i **ne** čuva osjetljive kartične podatke. Gateway obrađuje karticu.

Digital Kotor čuva samo minimalno potrebne gateway podatke. Maskirani card data samo ako ih gateway standardno vraća **i** opravdano su potrebni. Ako nijesu: **ne čuvati**.

## 10.5 Refund

Refund kroz user portal = **OUT OF V1**. Originalna Uspješna ostaje Uspješna. Naknadni reversal/refund/chargeback, ako ga protokol zahtijeva: **separate linked financial event**. Event model nije definisan.

---

# 11. Pre-production zavisnosti

**Status:** OPEN PRE-PRODUCTION DEPENDENCY

Ovo **nije** ponovno otvaranje Koraka 6.

1. stvarni bank/gateway protokol;
2. callback / webhook / status mechanism;
3. tačan gateway data contract;
4. tačan skup gateway podataka za čuvanje;
5. eventualni min/max iznosi;
6. dugotrajni `U obradi` resolution;
7. reversal/refund/chargeback contract ako postoji;
8. retention / legal / privacy review;
9. production audit existing `residential_status` (platform data);
10. production legacy `ex-non-resident` COUNT/mapping ako postoji;
11. legal-entity `resident` legacy data cleanup (platform);
12. NULL residential status na Fizičkom licu i Preduzetniku (platform);
13. konačno mapiranje 17 vrsta / 41 računa na kanonske korisničke kategorije i pravila.

`PLATFORM USER MODEL CORRECTIVE = COMPLETE` (application-level; commit `9c63e36`).

Stavke 9–12 ostaju **PRODUCTION LEGACY DATA CLEANUP = OPEN PRE-PRODUCTION**. Ovaj paket ih ne čisti.

Stavka 13: `FINAL 17/41 USER CATEGORY MAPPING = OPEN`. Ne izmišlja se ovdje.

---

# 12. Veza sa dokumentacijom

| # | Dokument | Putanja |
|---|----------|---------|
| 1 | Pravni okvir e-Plaćanja (EP-PO-001) | `docs/pravni-okvir/Pravni_okvir_e-Placanje.md` |
| 2 | Katalog finansijskih obaveza (EP-KF-001) | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` |
| 3 | Poslovni model e-Plaćanja (EP-BM-001) | `docs/business-model/Business_Model_e-Placanje.md` |
| 4 | Funkcionalna specifikacija e-Plaćanja (EP-FS-001) | `docs/functional-specifications/Functional-Specification_e-Placanje.md` |
| 5 | Tehnička specifikacija e-Plaćanja (EP-TS-001) | `docs/technical-specifications/Technical-Specification_e-Placanje.md` |
| 6 | Registar skraćenica i oznaka e-Plaćanja (EP-RG-001) | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-e-Placanja.md` |

Lanac: `EP-BM-001 → EP-FS-001 → EP-TS-001`

`KK-RG-001` i `DK-RG-001` nisu EP poslovni SSOT.

---

# 13. Registar usvojenih odluka

| Oznaka | Aktivno značenje | Korak 6 |
|--------|------------------|---------|
| P-01 … P-08 | Projektna načela | KEEP / UPDATE primjene |
| F-01 | 17 vrsta plaćanja, 41 račun | SUPERSEDE ontologije |
| UR-01 | Računi = katalog/šifrarnik, ne hardkod | KEEP + UPDATE filtera |
| BP-01 | Filterisani katalog vrsta | UPDATE |
| BP-02 | Profil + korisnički iznos; ne izvorni sistem | SUPERSEDE |
| BP-03 | Pregled i potvrda; odustajanje ≠ transakcija | UPDATE |
| BP-04 | Jedna gateway integracija | KEEP |
| BP-05 | Gateway SSOT; browser not authoritative | UPDATE |
| BP-06 | Potvrda/PDF/email samo Uspješna | UPDATE |
| BP-07 | Podaci uplatnice | SUPERSEDE 07.1/07.5 default |
| BP-08 | 4 statusa; granica gateway start | SUPERSEDE |
| BP-08.5 | Potvrda izvornom sistemu | SUPERSEDE za V1 |
| BP-09 | Istorija only-own; no delete | UPDATE |

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1. Unesena načela P-01–P-08 i odluka F-01. Rezervisana poglavlja entiteta, uloga i procesa. |
| 2026-07-27 | EP-PATCH-BM-001 — UR-01. |
| 2026-07-27 | EP-PATCH-BM-002 — BP-01, BP-02, BP-03. |
| 2026-07-27 | EP-PATCH-BM-003 — BP-04. |
| 2026-07-27 | EP-PATCH-BM-004 — BP-05. |
| 2026-07-27 | EP-PATCH-BM-005 — BP-06. |
| 2026-07-27 | EP-PATCH-BM-006 — BP-07. |
| 2026-07-27 | EP-PATCH-BM-007 — BP-08. |
| 2026-07-27 | EP-PATCH-BM-008A — Redakcijsko usklađivanje BP-05/BP-06/BP-08 (poruka ≠ status; Kreirana; potvrda ≠ knjiženje). |
| 2026-07-27 | EP-PATCH-BM-008B — evidencija trenutnog statusa. |
| 2026-07-27 | EP-PATCH-BM-009 — BP-09. |
| 2026-07-27 | EP-PATCH-BM-009A — BP-06↔BP-09; identifikatori. |
| 2026-08-17 | EP-PATCH-BM-010 — Dokumentacioni corrective: EP-BM-001; namespace EP-*. |
| 2026-08-20 | EP-PATCH-BM-011 / verzija 1.0.0 — Usklađivanje sa zatvorenim Korakom 6. Status dokumenta USVOJENO za V1 poslovni model. Application implementation nije pokrenuta. |
| 2026-08-20 | EP-PATCH-BM-012 / verzija 1.0.1 — EP korisnički model usklađen sa kanonskih 8 platform user types. Identity vs eligibility granica. Platform application corrective COMPLETE; production data cleanup OPEN. |
