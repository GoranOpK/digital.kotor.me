# Digital Kotor
# Business Model
## Modul: Plaćanja

**Feature ID:** FT-002
**Status dokumenta:** U IZRADI
**Verzija:** 0.1

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Business Modela modula Plaćanja. Unesena usvojena projektna načela P-01 do P-08 i funkcionalna odluka F-01. |
| PATCH-001 | 2026-07-27 | UR-01 – Uplatni računi: referentni podaci u Katalogu; Katalog ≠ šifrarnik; aplikacija koristi konfiguracioni izvor. |
| PATCH-002 | 2026-07-27 | BP-01, BP-02, BP-03 – Pronalaženje vrste uplate; način popunjavanja podataka; pregled i potvrda prije plaćanja. |
| PATCH-003 | 2026-07-27 | BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja. |
| PATCH-004 | 2026-07-27 | BP-05 – Obrada ishoda elektronskog plaćanja. |
| PATCH-005 | 2026-07-27 | BP-06 – Potvrda o izvršenom elektronskom plaćanju. |
| PATCH-006 | 2026-07-27 | BP-07 – Izvor obaveznih podataka za elektronsko plaćanje (BP-07.1 do BP-07.5). |
| PATCH-007 | 2026-07-27 | BP-08 – Životni ciklus transakcije (BP-08.1 do BP-08.5). |
| PATCH-008A | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-06/BP-08: korisnička poruka ≠ status; početni status Kreirana; potvrda izvornom sistemu ≠ knjiženje. |
| PATCH-008B | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-08: evidencija bilježi trenutni status transakcije (ne „konačni status“ u polju evidencije). |
| PATCH-009 | 2026-07-27 | BP-09 – Istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| PATCH-009A | 2026-07-27 | Redakcijsko usklađivanje: BP-06↔BP-09 (istorija); terminološko razdvajanje identifikatora transakcije. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (PATCH-001, PATCH-002...),
- datum,
- kratak naziv,
- kratak opis izmjene.

Naziv PATCH-a predstavlja zvanični naziv izmjene i koristi se u istoriji verzija.

---

## Svrha dokumenta

Dokument predstavlja referentni poslovni model za planiranje, razvoj, testiranje i održavanje modula Plaćanja.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| BM-002 / 1. Uvod | U IZRADI |
| BM-002 / 2. Svrha | USVOJENO (P-01) |
| BM-002 / 3. Ciljevi | U IZRADI |
| BM-002 / 4. Opseg | USVOJENO (P-02, F-01) |
| BM-002 / 5. Poslovni principi (P-01 do P-08) | USVOJENO |
| BM-002 / 6. Obavezni obuhvat V1 (F-01) | USVOJENO |
| BM-002 / 6a. Uplatni računi – referentni i konfiguracioni podaci (UR-01) | USVOJENO |
| BM-002 / 7. Poslovni entiteti | REZERVISANO |
| BM-002 / 8. Korisničke uloge | REZERVISANO |
| BM-002 / 9. Poslovni procesi | USVOJENO (BP-01 do BP-09) |
| BM-002 / 9.1 BP-01 – Pronalaženje vrste uplate | USVOJENO |
| BM-002 / 9.2 BP-02 – Način popunjavanja podataka za plaćanje | USVOJENO |
| BM-002 / 9.3 BP-03 – Pregled i potvrda prije plaćanja | USVOJENO |
| BM-002 / 9.4 BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja | USVOJENO |
| BM-002 / 9.5 BP-05 – Obrada ishoda elektronskog plaćanja | USVOJENO |
| BM-002 / 9.6 BP-06 – Potvrda o izvršenom elektronskom plaćanju | USVOJENO |
| BM-002 / 9.7 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje | USVOJENO |
| BM-002 / 9.8 BP-08 – Životni ciklus transakcije | USVOJENO |
| BM-002 / 9.9 BP-09 – Istorija transakcija i pregled plaćanja | USVOJENO |
| BM-002 / 10. Veza sa dokumentacijom | U IZRADI |
| BM-002 / 11. Rječnik poslovnih pojmova | U IZRADI |
| BM-002 / 12. Registar usvojenih poslovnih odluka (BP) | USVOJENO (BP-01 do BP-09) |

---

# Pravila upravljanja Business Modelom

1. Business Model predstavlja zvaničnu poslovnu specifikaciju modula Plaćanja (FT-002 / BM-002).

2. Posljednja usvojena verzija Business Modela predstavlja jedini izvor istine (Single Source of Truth) za poslovna pravila modula.

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu ili projektnu odluku.

4. Kompletan Business Model generiše se isključivo na izričit zahtjev.

5. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno prepisivati, preformulisati ili reorganizovati usvojeni sadržaj.

6. Usvojena projektna načela P-01 do P-08, odluke F-01 i UR-01, te poslovne odluke BP-01 do BP-09 ne smiju se mijenjati niti proširivati bez nove projektne odluke.

7. Ako postoji razlika između implementacije sistema i Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se odlukom ne izmijeni sam Business Model.

---

# Upravljanje promjenama

Svaka izmjena Business Modela mora biti rezultat usvojene poslovne ili projektne odluke i evidentirana kroz odgovarajući PATCH.

---

## Sadržaj

1. Uvod
2. Svrha
3. Ciljevi
4. Opseg
5. Usvojena projektna načela (P-01 do P-08)
6. Obavezni obuhvat V1 (F-01)
6a. Uplatni računi – referentni i konfiguracioni podaci (UR-01)
7. Poslovni entiteti
8. Korisničke uloge
9. Poslovni procesi (BP-01 do BP-09)
10. Veza sa dokumentacijom
11. Rječnik poslovnih pojmova
12. Registar usvojenih poslovnih odluka (BP)

---

# 1. Uvod

Business Model definiše poslovna pravila, poslovne entitete, korisničke uloge i način funkcionisanja modula Plaćanja. Dokument predstavlja osnov za izradu funkcionalne i tehničke specifikacije.

Modul Plaćanja je funkcionalnost platforme Digital Kotor (FT-002).

---

# 2. Svrha

**Status:** USVOJENO (P-01)

Modul **Plaćanja** služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor.

---

# 3. Ciljevi

**Status:** U IZRADI

Na osnovu usvojenih načela, ciljevi modula u V1 uključuju:

* omogućiti online plaćanje finansijskih obaveza koje se danas mogu platiti na blagajni Opštine Kotor (P-02);
* predstavljati elektronski kanal za izvršenje plaćanja već utvrđenih finansijskih obaveza (P-02, P-03);
* ostati u granicama odgovornosti definisanim projektnim načelima (P-03, P-04, P-08);
* osigurati usklađenost sa pravnim okvirom (P-05, P-07).

Detaljniji poslovni ciljevi mogu se dopuniti naknadnom projektnom odlukom.

---

# 4. Opseg

**Status:** USVOJENO (P-02, F-01)

## 4.1 Obuhvat V1 (P-02)

Modul omogućava online plaćanje finansijskih obaveza koje se danas mogu platiti na blagajni Opštine Kotor.

U prvoj fazi modul predstavlja isključivo elektronski kanal za izvršenje plaćanja.

## 4.2 Granice (P-03, P-04, P-08)

Modul Plaćanja:

* ne obračunava finansijske obaveze;
* ne donosi upravna rješenja;
* ne kreira zaduženja;
* ne vodi izvorne evidencije finansijskih obaveza;
* ne uvodi nove finansijske obaveze niti mijenja postojeće poslovne procese Opštine Kotor;
* ne mijenja sadržaj podataka izvornog informacionog sistema ili nadležnog organa.

## 4.3 Obuhvat vrsta uplata (F-01)

Obuhvat pojedinačnih vrsta uplata i uplatnih računa utvrđuje se Katalogom finansijskih obaveza, na osnovu spiska dostavljenog u okviru projekta. Detalji su u poglavlju 6.

---

# 5. Usvojena projektna načela (P-01 do P-08)

**Status:** USVOJENO

Ovih osam projektnih načela predstavljaju obavezujuće projektne odluke. Ne smiju se mijenjati niti proširivati bez nove projektne odluke.

---

## P-01 – Svrha modula

Modul **Plaćanja** služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor.

---

## P-02 – Obuhvat V1

Modul omogućava online plaćanje finansijskih obaveza koje se danas mogu platiti na blagajni Opštine Kotor.

U prvoj fazi modul predstavlja isključivo elektronski kanal za izvršenje plaćanja.

---

## P-03 – Granice odgovornosti

Modul Plaćanja:

* ne obračunava finansijske obaveze;
* ne donosi upravna rješenja;
* ne kreira zaduženja;
* ne vodi izvorne evidencije finansijskih obaveza.

Njegova uloga je omogućavanje elektronskog plaćanja već utvrđenih finansijskih obaveza.

---

## P-04 – Postojeći poslovni procesi

Modul Plaćanja ne uvodi nove finansijske obaveze niti mijenja postojeće poslovne procese Opštine Kotor.

Predstavlja elektronski kanal za izvršenje plaćanja postojećih finansijskih obaveza u skladu sa važećim propisima.

---

## P-05 – Regulatorna usklađenost

Svaka funkcionalnost modula mora imati odgovarajući pravni osnov u zakonima Crne Gore ili važećim propisima Opštine Kotor.

---

## P-06 – Dokumentacija

Razvoj modula zasniva se na sljedećim dokumentima:

1. Pravni okvir modula Plaćanja.
2. Katalog finansijskih obaveza prema Opštini Kotor.
3. Business Model.
4. Funkcionalna specifikacija.
5. Tehnička specifikacija.

Dokumentacija predstavlja osnov za projektovanje, razvoj i održavanje modula.

---

## P-07 – Propis kao izvor istine

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

---

## P-08 – Izvorni sistem ostaje nadležan

Za svaku finansijsku obavezu izvorni informacioni sistem ili nadležni organ Opštine Kotor ostaje jedini mjerodavan izvor podataka.

Modul Plaćanja koristi te podatke isključivo radi omogućavanja elektronskog plaćanja i ne mijenja njihov sadržaj.

---

# 6. Obavezni obuhvat V1 (F-01)

**Status:** USVOJENO

## FUNKCIONALNA ODLUKA F-01 – OBAVEZNI OBUHVAT V1

Modul Plaćanja u verziji V1 mora podržati sve pojedinačne vrste uplata i sve pripadajuće uplatne račune definisane važećom:

**Naredbom o načinu uplate javnih prihoda**
(„Službeni list Crne Gore“, broj 006/25 od 29.01.2025. godine),

koje su obuhvaćene ovim projektom.

Važna razlika:

* glavne numerisane cjeline predstavljaju kategorije uplata;
* svaka podstavka sa posebnim nazivom i računom predstavlja zasebnu vrstu uplate;
* sistem mora podržati svaku pojedinačnu vrstu uplate koja je obuhvaćena projektom.

Glavne kategorije služe isključivo za logičku organizaciju i prikaz korisnicima.

Svaka pojedinačna vrsta uplate mora imati najmanje:

* pripadajuću kategoriju;
* puni naziv vrste uplate;
* uplatni račun;
* internu oznaku ili šifru;
* status primjene;
* pravni osnov (kada bude utvrđen);
* napomenu o ciljnoj grupi (građani, preduzetnici, pravna lica ili više grupa), kada je to moguće utvrditi iz propisa.

Brojevi računa ne smiju biti hardkodirani u aplikacionom kodu. U dokumentaciji i projektovanju treba ih tretirati kao podatke šifrarnika koji se mogu ažurirati u slučaju izmjene važećih propisa.

Prilikom izrade dokumentacije koristi se isključivo spisak vrsta uplata koji je dostavljen u okviru ovog projekta. Ne dodaju se druge vrste uplata na osnovu samostalnog tumačenja propisa ili drugih izvora.

---

# 6a. Uplatni računi – referentni i konfiguracioni podaci (UR-01)

**Status:** USVOJENO

## Projektna odluka UR-01

Brojevi uplatnih računa predstavljaju **referentne podatke** preuzete iz važeće **Naredbe o načinu uplate javnih prihoda**.

Njihovo navođenje u dokumentaciji (Katalog finansijskih obaveza) služi isključivo za dokumentovanje poslovnih podataka koji važe u trenutku izrade dokumentacije.

To ne predstavlja hardkodiranje niti projektovanje implementacije.

### Katalog

U Katalogu se evidentiraju naziv vrste uplate, pripadajuća kategorija, broj uplatnog računa i ostali podaci predviđeni strukturom Kataloga.

Brojevi računa u Katalogu predstavljaju referentne podatke preuzete iz važećeg propisa.

Katalog finansijskih obaveza predstavlja **poslovni referentni dokument**. Nije šifrarnik i ne predstavlja implementacioni artefakt.

### Funkcionalna specifikacija

Funkcionalna specifikacija ne unosi konkretne brojeve računa. Definiše da svaka vrsta uplate ima referencu na odgovarajući uplatni račun definisan u Katalogu.

### Aplikacija i šifrarnik

Uplatni računi predstavljaju konfiguracione podatke (šifrarnik) i ne smiju biti hardkodirani u aplikacionom kodu.

Aplikacija mora koristiti podatke iz konfiguracionog izvora (šifrarnika), a ne vrijednosti ugrađene u programski kod.

Iz Kataloga će u narednim fazama razvoja biti izveden odgovarajući šifrarnik koji će predstavljati izvor podataka za aplikaciju.

---

# 7. Poslovni entiteti

**Status:** REZERVISANO

Poglavlje će se dopuniti nakon popunjavanja Kataloga i usvajanja dodatnih poslovnih odluka.

Očekivani entiteti (rezervisano, bez tehničkog projektovanja):

* Kategorija uplate
* Vrsta uplate (finansijska obaveza)
* Uplatni račun (kao atribut / referentni podatak u Katalogu; u aplikaciji — konfiguracioni podatak šifrarnika izvedenog iz Kataloga)
* Plaćanje (poslovni događaj izvršenja uplate) — detalji naknadno

---

# 8. Korisničke uloge

**Status:** REZERVISANO

Poglavlje još nije definisano. Uloge će se usvojiti posebnom odlukom.

Napomena: Autentifikacija i upravljanje korisničkim nalogom pripadaju platformi Digital Kotor, a ne poslovnom domenu modula Plaćanja, osim ako se drugačije ne odluči.

---

# 9. Poslovni procesi

**Status:** USVOJENO (BP-01 do BP-09)

U skladu sa P-02 i P-04, V1 proces je elektronski kanal za izvršenje plaćanja postojećih finansijskih obaveza, bez izmjene postojećih poslovnih procesa Opštine Kotor.

## 9.0 Objedinjeni poslovni tok

1. Korisnik pronađe i odabere vrstu uplate (BP-01).
2. Sistem pribavi podatke za plaćanje automatski ili kroz ručni unos (BP-02); izvori obaveznih podataka određuju se konfiguracijom vrste uplate (BP-07).
3. Sistem izvrši validaciju.
4. Korisniku se prikaže pregled podataka za plaćanje (BP-03).
5. Korisnik potvrdi podatke (BP-03).
6. Tek nakon potvrde kreira se zapis o transakciji i korisnik se preusmjerava na payment gateway (BP-04, BP-08.1); životni ciklus statusa uređuje BP-08.
7. Nakon završetka obrade od strane sistema elektronskog plaćanja, modul ažurira status transakcije i prikaže odgovarajuću informaciju korisniku (BP-05, BP-08).
8. Korisniku se prikaže potvrda o ishodu transakcije; za uspješno izvršene transakcije potvrda je dostupna za pregled i preuzimanje (BP-06); po potrebi se dostavlja potvrda izvornom informacionom sistemu (BP-08.5).
9. Korisnik može pregledati istoriju i detalje transakcija u skladu sa pravima pristupa (BP-09).

Napomena: Korisnički proces plaćanja **nije** zavisan od broja računa javnih prihoda niti od konkretnog pružaoca usluge elektronskog plaćanja (BP-04). Konkretna banka, payment gateway, merchant model i tehnička implementacija **ne** biraju se ovim poglavljem.

---

## 9.1 BP-01 – Pronalaženje vrste uplate

**Status:** USVOJENO

### Odluka

Korisnik može pronaći vrstu uplate na dva načina:

1. pregledom po hijerarhiji:
   * kategorija;
   * pojedinačna vrsta uplate;
2. pretragom svih vrsta uplata po nazivu.

Oba načina koriste isti **Katalog finansijskih obaveza prema Opštini Kotor** kao jedini referentni izvor vrsta uplata.

### Poslovna pravila

* Kategorije služe za organizaciju i pregled.
* Pretraga se vrši nad pojedinačnim vrstama uplata.
* Pregled po kategorijama i pretraga vode do iste vrste uplate.
* Ne smije postojati posebna ili duplirana lista vrsta uplata za pretragu.
* Nazivi i klasifikacija vrsta uplata preuzimaju se iz Kataloga.

---

## 9.2 BP-02 – Način popunjavanja podataka za plaćanje

**Status:** USVOJENO

### Odluka

Nakon odabira vrste uplate, podaci potrebni za plaćanje popunjavaju se kombinovanim modelom.

#### Automatsko preuzimanje

Ako za odabranu vrstu uplate postoji integracija sa izvornim informacionim sistemom, modul automatski preuzima podatke potrebne za plaćanje.

Izvorni informacioni sistem ili nadležni organ ostaje mjerodavan izvor podataka (P-08).

#### Ručni unos

Ako integracija ne postoji, korisniku se prikazuje obrazac za ručni unos podataka potrebnih za plaćanje.

Obavezni podaci mogu se razlikovati u zavisnosti od vrste uplate.

### Poslovna pravila

* Sistem određuje da li za konkretnu vrstu uplate postoji aktivna integracija.
* Korisnik ne bira između automatskog i ručnog načina.
* Kada postoji aktivna integracija, koristi se automatsko preuzimanje.
* Kada integracija ne postoji, koristi se ručni unos.
* Dodavanje integracije u budućnosti ne smije zahtijevati promjenu osnovnog korisničkog toka.
* Automatski preuzeti podaci ne smiju se proizvoljno mijenjati ako ih izvorni sistem označava kao mjerodavne ili neizmjenjive.
* Konkretne integracije, protokoli i izvorni sistemi **ne** projektuju se ovom odlukom.

---

## 9.3 BP-03 – Pregled i potvrda prije plaćanja

**Status:** USVOJENO

### Odluka

Prije pokretanja elektronskog plaćanja korisniku se mora prikazati pregled svih relevantnih podataka za plaćanje.

Plaćanje može biti pokrenuto tek nakon izričite potvrde korisnika.

### Poslovni tok

1. Korisnik odabere vrstu uplate.
2. Sistem automatski preuzme podatke ili korisnik unese potrebne podatke.
3. Sistem izvrši validaciju.
4. Korisniku se prikaže pregled podataka za plaćanje.
5. Korisnik potvrdi podatke.
6. Tek nakon potvrde može početi proces elektronskog plaćanja.

### Pregled treba da sadrži najmanje

* kategoriju;
* vrstu uplate;
* naziv primaoca;
* uplatni račun (referenca na Katalog / šifrarnik; bez hardkodiranja);
* iznos;
* poziv na broj, kada postoji;
* svrhu ili opis plaćanja, kada postoji;
* ostale relevantne podatke za konkretnu vrstu uplate.

Podaci koji nijesu primjenjivi na konkretnu vrstu uplate ne prikazuju se.

### Poslovna pravila

* Sve validacije moraju biti završene prije prikaza pregleda.
* Korisnik može da se vrati na prethodni korak i izmijeni podatke prije potvrde.
* Nakon potvrde i pokretanja procesa plaćanja, podaci te transakcije više se ne mogu mijenjati.
* Potvrda korisnika nije potvrda da je transakcija uspješno izvršena.
* Status uspjeha ili neuspjeha plaćanja utvrđuje se tek kroz kasnije definisan proces izvršenja plaćanja.
* Tehnički način potvrde, autentifikacija payment gateway-a i sadržaj potvrde o izvršenoj transakciji **ne** definišu se ovom odlukom.

---

## 9.4 BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja

**Status:** USVOJENO

### Odluka

Modul Plaćanja koristi **jedinstvenu tehničku integraciju** sa sistemom elektronskog plaćanja (payment gateway).

Broj računa javnih prihoda ne smije uslovljavati razvoj posebne gateway integracije za svaki pojedinačni račun.

Za svaku transakciju sistem, na osnovu odabrane vrste uplate i odgovarajuće konfiguracije, određuje na koji račun javnog prihoda se sredstva usmjeravaju.

Korisnički proces plaćanja nije zavisan od broja računa javnih prihoda niti od konkretnog pružaoca usluge elektronskog plaćanja.

### Poslovna pravila

* Modul koristi jednu tehničku integraciju prema payment gateway-u.
* Jedna integracija može podržavati više računa javnih prihoda.
* Vrsta uplate određuje konfiguraciju plaćanja.
* Brojevi računa nisu dio poslovne logike.
* Povezivanje vrste uplate sa računom vrši se kroz konfiguraciju sistema (UR-01).
* Dodavanje nove vrste uplate ili promjena računa ne smije zahtijevati razvoj nove gateway integracije.

### Arhitektonski principi (poslovni nivo)

* Payment gateway predstavlja infrastrukturnu komponentu, a ne poslovnu logiku.
* Poslovni proces plaćanja mora biti nezavisan od konkretne banke ili pružaoca usluge elektronskog plaćanja.
* Modul mora omogućiti zamjenu payment gateway-a bez izmjene poslovnog toka korisnika.
* Konfiguracija računa, merchant profila, terminala ili drugih parametara integracije mora biti odvojena od aplikacionog koda.

### Veze sa drugim odlukama

* **P-03** — Modul ne vodi izvorne finansijske evidencije; gateway integracija ne mijenja tu granicu odgovornosti.
* **P-08** — Izvorni sistemi / nadležni organi ostaju mjerodavni za podatke o obavezi.
* **UR-01** — Konfiguracioni podaci (uključujući račune) ne smiju biti hardkodirani.

### Ograničenje

Odluka **ne** bira i **ne** pretpostavlja konkretnog merchant-a, marketplace model, terminal ID, MID, banku, payment gateway, API ni tehnologiju. To ostaje predmet buduće tehničke analize i ugovaranja.

---

## 9.5 BP-05 – Obrada ishoda elektronskog plaćanja

**Status:** USVOJENO

### Odluka

Nakon završetka procesa elektronskog plaćanja, modul Plaćanja mora evidentirati ishod transakcije i korisniku prikazati odgovarajuću informaciju.

Modul ne donosi odluku o uspješnosti transakcije, već koristi status koji vrati sistem elektronskog plaćanja.

### Poslovni tok nakon završetka plaćanja

1. Korisnik je potvrdio podatke i pokrenuo elektronsko plaćanje (BP-03, BP-04).
2. Sistem elektronskog plaćanja završi obradu transakcije.
3. Modul primi / preuzme status ishoda iz sistema elektronskog plaćanja.
4. Modul evidentira ishod transakcije.
5. Korisniku se prikaže odgovarajuća poruka i jedinstveni identifikator transakcije, kada je dostupan.

### Poslovna pravila – ishodi

Sistem mora podržati najmanje sljedeće ishode:

* Plaćanje uspješno izvršeno.
* Plaćanje nije izvršeno.
* Plaćanje otkazano od strane korisnika.
* Status transakcije trenutno nije moguće potvrditi (npr. privremena nedostupnost ili obrada u toku).

Za svaki ishod korisniku mora biti prikazana odgovarajuća poruka i jedinstveni identifikator transakcije, kada je dostupan.

Napomena (usklađenje sa BP-08):

Poruka „Status trenutno nije moguće potvrditi“ predstavlja korisničku informaciju i **ne** uvodi novi status transakcije. Dok se čeka konačna potvrda od payment gateway-a, transakcija zadržava status **U toku**.

Interni statusi transakcije uređeni su životnim ciklusom (BP-08): Kreirana, U toku, Uspješna, Neuspješna, Otkazana.

### Evidencija

Za svaku pokrenutu transakciju evidentirati najmanje:

* jedinstveni identifikator transakcije;
* vrstu uplate;
* datum i vrijeme pokretanja;
* datum i vrijeme završetka, kada je poznato;
* trenutni status transakcije (status transakcije u trenutku evidentiranja);
* identifikator payment gateway-a, kada postoji.

Struktura baze podataka i tehnički model evidencije **ne** definišu se ovom odlukom.

### Poslovni principi

* Modul ne potvrđuje finansijsko knjiženje u izvornim sistemima.
* Modul evidentira rezultat procesa elektronskog plaćanja.
* Dalja obrada u izvornim sistemima ostaje predmet njihovih poslovnih pravila.
* Evidencija transakcija mora omogućiti naknadnu provjeru i reviziju.

### Veze sa drugim odlukama

* **BP-03** — Potvrda korisnika nije potvrda uspjeha transakcije; ishod se utvrđuje nakon obrade od strane sistema elektronskog plaćanja.
* **BP-04** — Ishod se prima kroz jedinstvenu integraciju sa payment gateway slojem.
* **BP-08** — Interni statusi i prelazi transakcije; korisničke poruke o ishodu nisu novi statusi.

### Ograničenje

Odluka **ne** opisuje API-je, webhooks, callback mehanizme, konkretan payment gateway, banku ni model baze podataka.

---

## 9.6 BP-06 – Potvrda o izvršenom elektronskom plaćanju

**Status:** USVOJENO

### Odluka

Nakon završetka procesa elektronskog plaćanja, modul Plaćanja korisniku prikazuje potvrdu o ishodu transakcije.

Ako je transakcija uspješno izvršena, potvrda mora biti dostupna za pregled i preuzimanje.

Potvrda predstavlja evidenciju izvršene elektronske transakcije u okviru modula Plaćanja i **ne predstavlja službeni finansijski dokument niti potvrdu da je uplata već proknjižena u izvornom informacionom sistemu**.

### Završni korak korisničkog procesa

BP-06 predstavlja završni korak korisničkog procesa plaćanja nakon obrade ishoda (BP-05).

### Poslovna pravila

Sistem mora omogućiti da korisnik:

* pregleda potvrdu odmah nakon završetka transakcije;
* ponovo otvori potvrdu iz istorije svojih transakcija, u skladu sa pravilima definisanim u BP-09;
* preuzme potvrdu u elektronskom obliku.

Potvrda se generiše isključivo za transakcije koje imaju konačan status (**Uspješna**).

Za transakcije u statusu **Otkazana** ili **Neuspješna**, te za transakcije u statusu **U toku** (kada se korisniku prikazuje informativna poruka da status plaćanja trenutno nije moguće potvrditi), sistem prikazuje odgovarajuću informaciju, ali ne generiše potvrdu o uspješnom plaćanju.

### Minimalni sadržaj potvrde

Potvrda treba da sadrži najmanje:

* jedinstveni identifikator transakcije;
* datum i vrijeme transakcije;
* vrstu uplate;
* naziv finansijske obaveze;
* iznos;
* status transakcije;
* identifikator payment gateway-a, kada postoji;
* naziv primaoca;
* broj računa na koji je izvršena uplata (referenca na konfiguraciju / Katalog; bez hardkodiranja u kodu — UR-01);
* poziv na broj, kada postoji.

Napomena (terminologija identifikatora):

* **Jedinstveni identifikator transakcije** — interni identifikator koji vodi modul Plaćanja.
* **Referentni broj transakcije** — identifikator koji se prikazuje korisniku u istoriji, detaljima i potvrdi (BP-09).
* **Identifikator payment gateway-a** — eksterni identifikator koji dodjeljuje payment gateway.

Da li su jedinstveni identifikator i referentni broj isti ili različiti ostaje **implementaciona odluka** i nije predmet ove dokumentacije. Na potvrdi se prikazuje identifikator u skladu sa tom implementacionom odlukom, uz identifikator payment gateway-a kada postoji.

Ne prikazivati povjerljive podatke platne kartice.

### Poslovni principi

Potvrda:

* ne zamjenjuje račun, rješenje ili drugi službeni dokument;
* ne predstavlja dokaz da je uplata već evidentirana u izvornom sistemu;
* predstavlja potvrdu da je elektronska platna transakcija završena sa prikazanim statusom.

Ako izvorni informacioni sistem naknadno potvrdi ili odbije knjiženje, to predstavlja zaseban poslovni proces koji nije obuhvaćen ovom odlukom.

### Veze sa drugim odlukama

* **BP-05** — Obrada ishoda elektronskog plaćanja; potvrda se zasniva na evidentiranom ishodu.
* **BP-09** — Istorija transakcija i pregled plaćanja.

### Ograničenje

Odluka **ne** definiše format datoteke, PDF, e-mail potvrde, digitalni potpis, fiskalizaciju, elektronski pečat, arhiviranje, računovodstveno knjiženje ni API-je.

---

## 9.7 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje

**Status:** USVOJENO

### Odluka

Za svaku vrstu uplate konfiguracijom se određuje izvor obaveznih podataka potrebnih za elektronsko plaćanje.

BP-07 definiše izvore za:

* iznos (BP-07.1);
* primaoca (BP-07.2);
* račun za uplatu (BP-07.3);
* poziv na broj (BP-07.4);
* svrhu plaćanja (BP-07.5).

### Veze sa drugim odlukama

* **BP-02** — Način popunjavanja podataka (automatski / ručni); BP-07 precizira izvore po poljima.
* **BP-03** — Pregled i potvrda prije plaćanja koristi podatke pribavljene prema BP-07.
* **UR-01** — Broj računa dolazi iz konfiguracionog šifrarnika izvedenog iz Kataloga.
* **BP-04** — Brojevi računa nisu dio poslovne logike; povezivanje vrste uplate sa računom vrši se kroz konfiguraciju.

---

### BP-07.1 – Izvor iznosa

#### Odluka

Za svaku vrstu uplate konfiguracijom se određuje način određivanja iznosa.

Podržani modeli:

1. Fiksni iznos.
2. Iznos iz izvornog informacionog sistema.
3. Ručni unos.
4. Predloženi iznos koji korisnik može izmijeniti samo ako je to dozvoljeno konfiguracijom.

#### Poslovna pravila

* Način određivanja iznosa definiše se konfiguracijom vrste uplate.
* Korisnik može mijenjati iznos samo kada je to dozvoljeno konfiguracijom.
* Kada iznos dolazi iz izvornog informacionog sistema, korisnik ga ne može mijenjati.
* Modul Plaćanja ne obračunava iznos, već ga preuzima ili prihvata u skladu sa pravilima za konkretnu vrstu uplate.

---

### BP-07.2 – Izvor primaoca

#### Odluka

Za svaku vrstu uplate konfiguracijom se određuje način određivanja primaoca.

Podržani modeli:

1. Fiksni primalac.
2. Primalac iz izvornog informacionog sistema.

#### Poslovna pravila

* Način određivanja primaoca definiše se konfiguracijom vrste uplate.
* Korisnik ne može mijenjati primaoca.
* Kada primalac dolazi iz izvornog informacionog sistema, korisnik ga ne može mijenjati.
* Modul Plaćanja ne određuje primaoca već ga preuzima ili koristi iz konfiguracije.

---

### BP-07.3 – Izvor računa za uplatu

#### Odluka

Broj računa određuje se iz konfiguracionog šifrarnika koji je izveden iz Kataloga finansijskih obaveza.

Katalog ostaje referentni poslovni dokument, dok aplikacija koristi konfiguracioni šifrarnik kao operativni izvor podataka.

#### Poslovna pravila

* Katalog finansijskih obaveza predstavlja referentni izvor poslovnih podataka.
* Konfiguracioni šifrarnik predstavlja operativni izvor koji koristi aplikacija.
* Broj računa nije dio poslovne logike niti aplikacionog koda.
* Promjena broja računa vrši se kroz konfiguraciju bez izmjene poslovnog modela ili aplikacionog koda.
* Korisnik ne može mijenjati broj računa.

---

### BP-07.4 – Izvor poziva na broj

#### Odluka

Za svaku vrstu uplate konfiguracijom se određuje način određivanja poziva na broj.

Podržani modeli:

1. Bez poziva na broj.
2. Fiksna vrijednost.
3. Vrijednost iz izvornog informacionog sistema.
4. Ručni unos.

#### Poslovna pravila

* Način određivanja poziva na broj definiše se konfiguracijom vrste uplate.
* Kada poziv na broj dolazi iz izvornog informacionog sistema, korisnik ga ne može mijenjati.
* Kada je predviđen ručni unos primjenjuju se definisana pravila validacije.
* Kada za vrstu uplate nije predviđen poziv na broj, polje se ne prikazuje korisniku.
* Modul Plaćanja ne generiše poziv na broj osim ako to bude definisano posebnom poslovnom odlukom.

---

### BP-07.5 – Izvor svrhe plaćanja

#### Odluka

Za svaku vrstu uplate konfiguracijom se određuje način određivanja svrhe plaćanja.

Podržani modeli:

1. Bez svrhe plaćanja.
2. Fiksna svrha plaćanja.
3. Svrha iz izvornog informacionog sistema.
4. Ručni unos.

#### Poslovna pravila

* Način određivanja svrhe plaćanja definiše se konfiguracijom vrste uplate.
* Kada svrha dolazi iz izvornog informacionog sistema, korisnik je ne može mijenjati.
* Kada je predviđen ručni unos primjenjuju se definisana pravila validacije.
* Kada za vrstu uplate svrha nije predviđena, polje se ne prikazuje korisniku.
* Modul Plaćanja ne generiše svrhu plaćanja osim ako to bude definisano posebnom poslovnom odlukom.

---

## 9.8 BP-08 – Životni ciklus transakcije

**Status:** USVOJENO

### Odluka

Životni ciklus transakcije uređuje kreiranje zapisa, statuse, dozvoljene prelaze i eventualnu potvrdu izvornom informacionom sistemu.

BP-08 definiše:

* evidentiranje transakcije (BP-08.1);
* statuse transakcije (BP-08.2);
* promjenu statusa transakcije (BP-08.3);
* dozvoljene prelaze između statusa (BP-08.4);
* potvrdu uspješnog plaćanja izvornom informacionom sistemu (BP-08.5).

### Veze sa drugim odlukama

* **BP-03** — Transakcija se kreira nakon izričite potvrde korisnika.
* **BP-04** — Preusmjeravanje na payment gateway kroz jedinstvenu integraciju.
* **BP-05** — Obrada ishoda elektronskog plaćanja; statusi i prelazi preciziraju životni ciklus.
* **BP-06** — Potvrda korisniku zasniva se na konačnom ishodu transakcije.
* **P-08** — Izvorni sistem ostaje mjerodavan; modul ne preuzima njegovu poslovnu logiku.

---

### BP-08.1 – Evidentiranje transakcije

#### Odluka

Zapis o transakciji kreira se prije preusmjeravanja korisnika na payment gateway.

Nakon kreiranja transakcije korisnik se preusmjerava na payment gateway radi izvršenja plaćanja.

Status transakcije se naknadno ažurira na osnovu povratne informacije od payment gateway-a.

#### Poslovna pravila

* Transakcija se kreira odmah nakon što korisnik potvrdi plaćanje.
* Svaka transakcija dobija **jedinstveni identifikator transakcije** (interni identifikator koji vodi modul Plaćanja).
* Početni status svake novoformirane transakcije je **Kreirana**.
* Početni status transakcije određuje se prije preusmjeravanja korisnika na payment gateway.
* Payment gateway koristi identifikator transakcije za povezivanje povratnih informacija sa odgovarajućom transakcijom.
* Modul Plaćanja čuva zapis o svim započetim transakcijama, bez obzira na njihov konačni ishod.

Napomena (terminologija): Odnos jedinstvenog identifikatora transakcije i referentnog broja transakcije (prikaz korisniku) nije propisan ovom odlukom; to ostaje implementaciona odluka.

---

### BP-08.2 – Statusi transakcije

#### Odluka

Modul Plaćanja podržava sljedeće statuse transakcije:

* Kreirana
* U toku
* Uspješna
* Neuspješna
* Otkazana

#### Opis statusa

* **Kreirana** – transakcija je evidentirana i spremna za slanje prema payment gateway-u.
* **U toku** – korisnik je preusmjeren na payment gateway ili se čeka konačna potvrda.
* **Uspješna** – payment gateway je potvrdio uspješno izvršeno plaćanje.
* **Neuspješna** – plaćanje nije izvršeno ili je odbijeno.
* **Otkazana** – korisnik je odustao od plaćanja ili je proces prekinut prije završetka.

#### Poslovna pravila

* Svaka transakcija u svakom trenutku ima tačno jedan status.
* Status transakcije mijenja se isključivo prema pravilima životnog ciklusa transakcije.
* Konačni statusi su Uspješna, Neuspješna i Otkazana.
* Nakon prelaska u konačni status transakcija se više ne vraća u prethodni status.

---

### BP-08.3 – Promjena statusa transakcije

#### Odluka

Status transakcije mijenja se isključivo automatski, na osnovu događaja koje modul Plaćanja primi od payment gateway-a ili internih sistemskih procesa.

Administrator i ostali korisnici nemaju mogućnost ručne izmjene statusa transakcije.

#### Poslovna pravila

* Status transakcije može mijenjati isključivo sistem.
* Ručna izmjena statusa transakcije nije dozvoljena.
* Promjena statusa mora biti zasnovana na verifikovanom sistemskom događaju.
* Svaka promjena statusa evidentira se u audit zapisu sa vremenom promjene i izvorom događaja.
* Modul Plaćanja ne dozvoljava zaobilaženje definisanog životnog ciklusa transakcije.

---

### BP-08.4 – Dozvoljeni prelazi između statusa

#### Odluka

Životni ciklus transakcije definiše se kao mašina stanja (State Machine).

Dozvoljeni su isključivo unaprijed definisani prelazi između statusa.

#### Dozvoljeni prelazi

* Kreirana → U toku
* U toku → Uspješna
* U toku → Neuspješna
* U toku → Otkazana

Nijedan drugi prelaz nije dozvoljen.

#### Poslovna pravila

* Dozvoljeni su isključivo definisani prelazi između statusa.
* Sistem mora odbiti svaki pokušaj nedozvoljenog prelaza.
* Statusi Uspješna, Neuspješna i Otkazana predstavljaju konačna stanja.
* Nakon dostizanja konačnog stanja nisu dozvoljene dalje promjene statusa.
* Svaki uspješan ili odbijen pokušaj promjene statusa evidentira se u audit zapisu.

---

### BP-08.5 – Potvrda uspješnog plaćanja izvornom informacionom sistemu

#### Odluka

Nakon što payment gateway potvrdi uspješno izvršeno plaćanje, modul Plaćanja:

1. ažurira status transakcije na Uspješna;
2. evidentira uspješno izvršeno plaćanje;
3. ako je za vrstu uplate definisana integracija, automatski dostavlja potvrdu izvornom informacionom sistemu;
4. ako integracija nije definisana, proces se završava evidentiranjem uspješne transakcije u modulu Plaćanja.

#### Poslovna pravila

* Potvrda izvornom informacionom sistemu šalje se samo kada je za konkretnu vrstu uplate definisana integracija.
* Modul Plaćanja ne preuzima poslovnu logiku izvornog informacionog sistema.
* Dostavljanje potvrde izvornom informacionom sistemu predstavlja razmjenu informacija o uspješno izvršenom plaćanju i **ne** predstavlja finansijsko knjiženje niti potvrdu knjiženja u izvornom informacionom sistemu.
* Ako potvrdu nije moguće dostaviti, transakcija zadržava status Uspješna, a neuspjela isporuka potvrde evidentira se kao poseban sistemski događaj.
* Svaki pokušaj dostavljanja potvrde evidentira se u audit zapisu.

---

## 9.9 BP-09 – Istorija transakcija i pregled plaćanja

**Status:** USVOJENO

### Odluka

Modul Plaćanja omogućava istoriju transakcija i pregled plaćanja, uključujući listu, pretragu, detaljan pregled i zadržavanje / arhiviranje zapisa.

BP-09 definiše:

* pravo pristupa istoriji transakcija (BP-09.1);
* sadržaj liste transakcija (BP-09.2);
* pretragu i filtriranje istorije transakcija (BP-09.3);
* detaljan pregled transakcije (BP-09.4);
* zadržavanje (retention) i arhiviranje istorije transakcija (BP-09.5).

### Veze sa drugim odlukama

* **BP-06** — Potvrda o izvršenom plaćanju; istorija omogućava ponovni pristup potvrdi / detaljima.
* **BP-08** — Statusi i životni ciklus transakcije; lista i detalji prikazuju status iz BP-08.
* **UR-01** — Broj računa na pregledu dolazi iz konfiguracionog izvora, bez hardkodiranja u kodu.

---

### BP-09.1 – Pravo pristupa istoriji transakcija

#### Odluka

Pristup istoriji transakcija određuje se na osnovu uloge korisnika.

* Korisnik može pregledati isključivo svoje transakcije.
* Administrator platforme može pregledati sve transakcije u skladu sa svojim ovlašćenjima.

#### Poslovna pravila

* Korisnik može pregledati isključivo transakcije koje je sam inicirao.
* Administrator platforme može pregledati sve transakcije.
* Pregled transakcija ne daje pravo izmjene podataka.
* Prava pristupa određuju se u skladu sa ulogama na platformi.
* Administrativni pregled transakcija evidentira se u audit logu.

---

### BP-09.2 – Sadržaj liste transakcija

#### Odluka

Lista transakcija prikazuje:

* Datum i vrijeme transakcije
* Vrstu uplate
* Iznos
* Status transakcije
* Referentni broj transakcije
* Primaoca
* Poziv na broj (kada postoji)
* Način plaćanja (ako ga payment gateway dostavlja)
* Akciju „Pregled detalja“

#### Poslovna pravila

* Lista prikazuje samo podatke koje korisnik ima pravo da vidi.
* Lista ne prikazuje osjetljive podatke o sredstvu plaćanja (broj kartice, CVV, puni broj računa korisnika i sl.).
* Svaka transakcija ima **referentni broj transakcije** prikazan u listi.
* Iz liste je moguće otvoriti detaljan pregled transakcije.
* Polja koja nisu primjenjiva za određenu transakciju ne prikazuju vrijednost ili se prikazuju kao prazna, u skladu sa pravilima korisničkog interfejsa.

Napomena (terminologija): Referentni broj transakcije je identifikator koji se prikazuje korisniku. Odnos prema jedinstvenom identifikatoru transakcije nije propisan ovom odlukom.

---

### BP-09.3 – Pretraga i filtriranje istorije transakcija

#### Odluka

Modul Plaćanja omogućava naprednu pretragu i filtriranje istorije transakcija.

Podržani kriterijumi:

* Period (od–do)
* Status transakcije
* Vrsta uplate
* Referentni broj transakcije
* Primalac
* Raspon iznosa (od–do)
* Tekstualna pretraga (gdje je primjenjivo)

#### Poslovna pravila

* Moguće je kombinovati više kriterijuma filtriranja.
* Primijenjeni filteri utiču isključivo na prikaz rezultata i ne mijenjaju podatke.
* Rezultati pretrage prikazuju se u skladu sa pravima pristupa korisnika.
* Ako nijedna transakcija ne odgovara zadatim kriterijumima, sistem prikazuje odgovarajuću informativnu poruku.
* Filteri ne omogućavaju pristup transakcijama za koje korisnik nema ovlašćenje.

---

### BP-09.4 – Detaljan pregled transakcije

#### Odluka

Detaljan pregled transakcije prikazuje sve relevantne podatke korišćene ili nastale tokom procesa plaćanja.

Prikazuju se:

* Datum i vrijeme kreiranja transakcije
* Datum i vrijeme posljednje promjene statusa
* Referentni broj transakcije
* Vrsta uplate
* Iznos
* Status transakcije
* Primalac
* Broj računa za uplatu
* Poziv na broj (kada postoji)
* Svrha plaćanja (kada postoji)
* Način plaćanja (ako ga payment gateway dostavlja)
* **Identifikator payment gateway-a** (ako postoji)
* Status dostavljanja potvrde izvornom informacionom sistemu (kada postoji integracija)

#### Poslovna pravila

* Detaljan pregled prikazuje samo podatke koje korisnik ima pravo da vidi.
* Osjetljivi podaci o sredstvu plaćanja nikada se ne prikazuju.
* Ako određeni podatak nije primjenjiv za konkretnu transakciju, ne prikazuje se ili se označava kao „Nije primjenjivo“, u skladu sa pravilima korisničkog interfejsa.
* Detaljan pregled je isključivo informativan i ne omogućava izmjenu podataka.

---

### BP-09.5 – Zadržavanje (Retention) i arhiviranje istorije transakcija

#### Odluka

Transakcije se trajno evidentiraju u sistemu.

Radi optimizacije performansi, starije transakcije mogu biti automatski arhivirane, pri čemu ostaju dostupne za pregled i pretragu u skladu sa pravima pristupa.

#### Poslovna pravila

* Sve transakcije trajno se evidentiraju.
* Arhiviranje ne mijenja sadržaj transakcije niti njen identitet.
* Arhivirane transakcije ostaju dostupne za pregled i pretragu u skladu sa pravima pristupa.
* Funkcionalnosti modula Plaćanja ne omogućavaju brisanje transakcija.
* Način i period arhiviranja definišu se sistemskom konfiguracijom i važećim propisima.

---

# 10. Veza sa dokumentacijom

U skladu sa P-06, razvoj modula zasniva se na:

| # | Dokument | Putanja |
|---|----------|---------|
| 1 | Pravni okvir modula Plaćanja | `docs/pravni-okvir/Pravni_okvir_Placanja.md` |
| 2 | Katalog finansijskih obaveza prema Opštini Kotor | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` |
| 3 | Business Model | `docs/business-model/Business_Model_Placanja.md` |
| 4 | Funkcionalna specifikacija | `docs/functional-specification/Functional-Specification_Placanja.md` |
| 5 | Tehnička specifikacija | `docs/technical-specification/Technical-Specification_Placanja.md` |

Feature Registry: FT-002 — `docs/features/Feature-Registry.md`

---

# 11. Rječnik poslovnih pojmova

**Status:** U IZRADI

| Pojam | Definicija | Izvor |
|-------|------------|-------|
| Modul Plaćanja | Modul platforme Digital Kotor za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor | P-01 |
| Finansijska obaveza | Obaveza plaćanja prema Opštini Kotor koja je već utvrđena izvan modula | P-03 |
| Kategorija uplate | Logička grupa za organizaciju i prikaz | F-01 |
| Vrsta uplate | Pojedinačna obaveza sa nazivom i uplatnim računom; jedinica podrške sistema | F-01 |
| Uplatni račun | Broj računa za uplatu; u Katalogu referentni podatak iz Naredbe; u aplikaciji konfiguracioni podatak šifrarnika | F-01, UR-01 |
| Katalog | Poslovni referentni dokument vrsta uplata i računa; nije šifrarnik ni implementacioni artefakt | P-06, F-01, UR-01 |
| Šifrarnik (aplikacioni) | Konfiguracioni izvor podataka za aplikaciju; izvodi se iz Kataloga u narednim fazama | UR-01 |
| Izvorni sistem | Informacioni sistem ili nadležni organ mjerodavan za podatke o obavezi | P-08 |
| Pregled po hijerarhiji | Pronalaženje vrste uplate preko kategorije, zatim pojedinačne vrste | BP-01 |
| Pretraga po nazivu | Pronalaženje vrste uplate pretragom nad pojedinačnim vrstama uplata | BP-01 |
| Automatsko preuzimanje podataka | Popunjavanje podataka za plaćanje iz izvornog sistema kada postoji aktivna integracija | BP-02 |
| Ručni unos podataka | Popunjavanje podataka za plaćanje obrazcem kada integracija ne postoji | BP-02 |
| Pregled i potvrda prije plaćanja | Obavezni prikaz i izričita potvrda podataka prije pokretanja elektronskog plaćanja | BP-03 |
| Jedinstvena gateway integracija | Jedna tehnička integracija prema payment gateway sloju; računi i parametri u konfiguraciji; poslovni tok nezavisan od pružaoca | BP-04 |
| Ishod elektronskog plaćanja | Evidentiranje i prikaz rezultata transakcije na osnovu statusa sistema elektronskog plaćanja | BP-05 |
| Potvrda o izvršenom plaćanju | Pregled i preuzimanje potvrde o ishodu transakcije; nije službeni finansijski dokument | BP-06 |
| Izvor obaveznih podataka | Konfiguracijom vrste uplate određen način pribavljanja iznosa, primaoca, računa, poziva na broj i svrhe plaćanja | BP-07 |
| Životni ciklus transakcije | Kreiranje zapisa, statusi, automatski prelazi i eventualna potvrda izvornom sistemu | BP-08 |
| Status transakcije | Jedan od: Kreirana, U toku, Uspješna, Neuspješna, Otkazana | BP-08.2 |
| Istorija transakcija | Pregled, pretraga i detalji transakcija u skladu sa pravima pristupa | BP-09 |
| Jedinstveni identifikator transakcije | Interni identifikator koji vodi modul Plaćanja | BP-08.1 |
| Referentni broj transakcije | Identifikator koji se prikazuje korisniku u istoriji, detaljima i potvrdi | BP-09.2 |
| Identifikator payment gateway-a | Eksterni identifikator koji dodjeljuje payment gateway | BP-05, BP-09.4 |

Napomena (terminologija identifikatora): Dokumentacija razlikuje tri pojma navedena iznad. Da li su jedinstveni identifikator i referentni broj isti ili različiti **nije** propisano i ostaje implementaciona odluka.

---

# 12. Registar usvojenih poslovnih odluka (BP)

| Oznaka | Naziv | Status | Sljedivost |
|--------|-------|--------|------------|
| BP-01 | Pronalaženje vrste uplate | USVOJENO | BM-002 / 9.1 → FS-002 / 7.1 |
| BP-02 | Način popunjavanja podataka za plaćanje | USVOJENO | BM-002 / 9.2 → FS-002 / 7.2 |
| BP-03 | Pregled i potvrda prije plaćanja | USVOJENO | BM-002 / 9.3 → FS-002 / 7.3 |
| BP-04 | Jedinstvena integracija sa sistemom elektronskog plaćanja | USVOJENO | BM-002 / 9.4 → FS-002 / 7.4 → TS-002 / 2.6 |
| BP-05 | Obrada ishoda elektronskog plaćanja | USVOJENO | BM-002 / 9.5 → FS-002 / 7.5 → TS-002 / 2.7 |
| BP-06 | Potvrda o izvršenom elektronskom plaćanju | USVOJENO | BM-002 / 9.6 → FS-002 / 7.6 → TS-002 / 2.8 |
| BP-07 | Izvor obaveznih podataka za elektronsko plaćanje | USVOJENO | BM-002 / 9.7 → FS-002 / 7.7 → TS-002 / 2.9 |
| BP-08 | Životni ciklus transakcije | USVOJENO | BM-002 / 9.8 → FS-002 / 7.8 → TS-002 / 2.10 |
| BP-09 | Istorija transakcija i pregled plaćanja | USVOJENO | BM-002 / 9.9 → FS-002 / 7.9 → TS-002 / 2.11 |

Veze BP-04: P-03, P-08, UR-01.

Veze BP-05: BP-03, BP-04.

Veze BP-06: BP-05, BP-09.

Veze BP-07: BP-02, BP-03, UR-01, BP-04.

Veze BP-08: BP-03, BP-04, BP-05, BP-06, P-08.

Veze BP-09: BP-06, BP-08, UR-01.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1. Unesena načela P-01–P-08 i odluka F-01. Rezervisana poglavlja entiteta, uloga i procesa. |
| 2026-07-27 | PATCH-001 — UR-01: uplatni računi kao referentni / konfiguracioni podaci; Katalog ≠ šifrarnik. |
| 2026-07-27 | PATCH-002 — BP-01, BP-02, BP-03 usvojene; poslovni procesi i registar BP. |
| 2026-07-27 | PATCH-003 — BP-04: jedinstvena integracija sa sistemom elektronskog plaćanja. |
| 2026-07-27 | PATCH-004 — BP-05: obrada ishoda elektronskog plaćanja. |
| 2026-07-27 | PATCH-005 — BP-06: potvrda o izvršenom elektronskom plaćanju. |
| 2026-07-27 | PATCH-006 — BP-07: izvor obaveznih podataka za elektronsko plaćanje (BP-07.1 do BP-07.5). |
| 2026-07-27 | PATCH-007 — BP-08: životni ciklus transakcije (BP-08.1 do BP-08.5). |
| 2026-07-27 | PATCH-008A — Redakcijsko usklađivanje BP-05/BP-06/BP-08 (poruka ≠ status; Kreirana; potvrda ≠ knjiženje). |
| 2026-07-27 | PATCH-008B — Redakcijsko usklađivanje: evidencija bilježi trenutni status transakcije. |
| 2026-07-27 | PATCH-009 — BP-09: istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| 2026-07-27 | PATCH-009A — Redakcijsko: BP-06↔BP-09 (istorija); terminologija identifikatora. |
