# Digital Kotor
# Functional Specification
## Modul: Plaćanja

**Feature ID:** FT-002
**Status dokumenta:** U IZRADI
**Verzija:** 1.0.1

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Functional Specification modula Plaćanja. Unesene usvojene odluke P-01–P-08 i F-01. |
| PATCH-FS-001 | 2026-07-27 | UR-01 – Uplatni računi: FS ne navodi konkretne brojeve računa; vrsta uplate ima referencu na račun iz Kataloga; Katalog ≠ šifrarnik. |
| PATCH-FS-002 | 2026-07-27 | BP-01, BP-02, BP-03 – Pronalaženje vrste uplate; način popunjavanja podataka; pregled i potvrda prije plaćanja. |
| PATCH-FS-003 | 2026-07-27 | BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja; korisnički tok nezavisan od računa i gateway implementacije. |
| PATCH-FS-004 | 2026-07-27 | BP-05 – Obrada ishoda elektronskog plaćanja; funkcionalno ponašanje po ishodima; evidencija transakcije. |
| PATCH-FS-005 | 2026-07-27 | BP-06 – Potvrda o izvršenom elektronskom plaćanju; pregled i preuzimanje; minimalni sadržaj. |
| PATCH-FS-006 | 2026-07-27 | BP-07 – Izvor obaveznih podataka za elektronsko plaćanje (BP-07.1 do BP-07.5). |
| PATCH-FS-007 | 2026-07-27 | BP-08 – Životni ciklus transakcije (BP-08.1 do BP-08.5). |
| PATCH-FS-008A | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-06/BP-08: korisnička poruka ≠ status; početni status Kreirana; potvrda izvornom sistemu ≠ knjiženje. |
| PATCH-FS-008B | 2026-07-27 | Redakcijsko usklađivanje BP-05/BP-08: evidencija bilježi trenutni status transakcije. |
| PATCH-FS-009 | 2026-07-27 | BP-09 – Istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| PATCH-FS-009A | 2026-07-27 | Redakcijsko usklađivanje: BP-06↔BP-09 (istorija); terminološko razdvajanje identifikatora transakcije. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (PATCH-FS-001, PATCH-FS-002...),
- datum,
- kratak naziv,
- kratak opis izmjene.

Naziv PATCH-a predstavlja zvanični naziv izmjene i koristi se u istoriji verzija.

---

## Svrha dokumenta

Dokument predstavlja referentnu funkcionalnu specifikaciju za planiranje, razvoj, testiranje i održavanje modula Plaćanja.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| FS-002 / 1. Svrha modula | USVOJENO (P-01) |
| FS-002 / 2. Obuhvat V1 | USVOJENO (P-02, F-01) |
| FS-002 / 3. Granice funkcionalnosti | USVOJENO (P-03, P-04, P-08) |
| FS-002 / 4. Regulatorna i dokumentaciona pravila | USVOJENO (P-05, P-06, P-07) |
| FS-002 / 5. Obavezni obuhvat vrsta uplata (F-01) | USVOJENO |
| FS-002 / 5.3 Referenca na uplatni račun (UR-01) | USVOJENO |
| FS-002 / 6. Korisnici | REZERVISANO |
| FS-002 / 7. Funkcionalni zahtjevi po tokovima (BP-01 do BP-09) | USVOJENO |
| FS-002 / 7.1 BP-01 – Pronalaženje vrste uplate | USVOJENO |
| FS-002 / 7.2 BP-02 – Način popunjavanja podataka za plaćanje | USVOJENO |
| FS-002 / 7.3 BP-03 – Pregled i potvrda prije plaćanja | USVOJENO |
| FS-002 / 7.4 BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja | USVOJENO |
| FS-002 / 7.5 BP-05 – Obrada ishoda elektronskog plaćanja | USVOJENO |
| FS-002 / 7.6 BP-06 – Potvrda o izvršenom elektronskom plaćanju | USVOJENO |
| FS-002 / 7.7 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje | USVOJENO |
| FS-002 / 7.8 BP-08 – Životni ciklus transakcije | USVOJENO |
| FS-002 / 7.9 BP-09 – Istorija transakcija i pregled plaćanja | USVOJENO |
| FS-002 / 8. Poslovna pravila | U IZRADI |
| FS-002 / 9. Prihvatni kriterijumi V1 | U IZRADI |

---

# Pravila upravljanja Functional Specification

1. Functional Specification predstavlja zvaničnu funkcionalnu specifikaciju modula Plaćanja (FT-002 / FS-002).

2. Posljednja usvojena verzija Functional Specification predstavlja jedini izvor istine (Single Source of Truth) za funkcionalne zahtjeve modula.

3. Poglavlja i tačke sa statusom USVOJENO / Approved mijenjaju se isključivo kroz PATCH koji predstavlja novu usvojenu odluku.

4. Kompletan Functional Specification generiše se isključivo na izričit zahtjev.

5. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno prepisivati, preformulisati ili reorganizovati usvojeni sadržaj.

6. Functional Specification ostaje usklađena sa Business Modelom (BM-002). Ne dokumentuje privremena tehnička ograničenja trenutne implementacije.

7. Usvojene odluke P-01 do P-08, F-01, UR-01 i BP-01 do BP-09 ne smiju se mijenjati niti proširivati bez nove projektne odluke.

---

# Upravljanje promjenama

Svaka izmjena Functional Specification mora biti rezultat usvojene odluke i evidentirana kroz odgovarajući PATCH.

---

## Sadržaj

1. Svrha modula
2. Obuhvat V1
3. Granice funkcionalnosti
4. Regulatorna i dokumentaciona pravila
5. Obavezni obuhvat vrsta uplata (F-01)
6. Korisnici
7. Funkcionalni zahtjevi po tokovima (BP-01 do BP-09)
8. Poslovna pravila
9. Prihvatni kriterijumi V1

---

# FS-002 / 1. Svrha modula

**Status:** USVOJENO (P-01)

Modul **Plaćanja** služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor.

---

# FS-002 / 2. Obuhvat V1

**Status:** USVOJENO (P-02, F-01)

## 2.1 Opšti obuhvat (P-02)

Modul omogućava online plaćanje finansijskih obaveza koje se danas mogu platiti na blagajni Opštine Kotor.

U prvoj fazi modul predstavlja isključivo elektronski kanal za izvršenje plaćanja.

## 2.2 Obuhvat vrsta uplata (F-01)

Modul u verziji V1 mora podržati sve pojedinačne vrste uplata i sve pripadajuće uplatne račune iz važeće Naredbe o načinu uplate javnih prihoda („Službeni list Crne Gore“, broj 006/25 od 29.01.2025. godine) koje su obuhvaćene ovim projektom.

Konkretan spisak vodi se u Katalogu finansijskih obaveza i popunjava se isključivo dostavljenim projektnim spiskom.

---

# FS-002 / 3. Granice funkcionalnosti

**Status:** USVOJENO (P-03, P-04, P-08)

Modul Plaćanja:

* ne obračunava finansijske obaveze;
* ne donosi upravna rješenja;
* ne kreira zaduženja;
* ne vodi izvorne evidencije finansijskih obaveza;
* ne uvodi nove finansijske obaveze;
* ne mijenja postojeće poslovne procese Opštine Kotor;
* ne mijenja sadržaj podataka izvornog informacionog sistema ili nadležnog organa.

Uloga modula je omogućavanje elektronskog plaćanja već utvrđenih finansijskih obaveza.

---

# FS-002 / 4. Regulatorna i dokumentaciona pravila

**Status:** USVOJENO (P-05, P-06, P-07)

## 4.1 Regulatorna usklađenost (P-05)

Svaka funkcionalnost modula mora imati odgovarajući pravni osnov u zakonima Crne Gore ili važećim propisima Opštine Kotor.

## 4.2 Dokumentacija (P-06)

Razvoj modula zasniva se na:

1. Pravni okvir modula Plaćanja
2. Katalog finansijskih obaveza prema Opštini Kotor
3. Business Model
4. Funkcionalna specifikacija
5. Tehnička specifikacija

## 4.3 Propis kao izvor istine (P-07)

Svaka pojedinačna vrsta finansijske obaveze mora biti povezana sa odgovarajućim pravnim osnovom.

Kada podaci budu dostupni, evidentiraju se: naziv propisa; broj i godina službenog glasila; relevantni član propisa; nadležni organ; napomene o primjeni.

Ako pravni osnov nije potvrđen, označava se kao **Potrebno pravno potvrditi.**

Ne unose se pretpostavljeni ili nepotvrđeni pravni podaci.

---

# FS-002 / 5. Obavezni obuhvat vrsta uplata (F-01)

**Status:** USVOJENO

## 5.1 Kategorije i vrste uplata

* Glavne numerisane cjeline predstavljaju **kategorije** uplata.
* Svaka podstavka sa posebnim nazivom i računom predstavlja zasebnu **vrstu uplate**.
* Sistem mora podržati svaku pojedinačnu vrstu uplate koja je obuhvaćena projektom.
* Glavne kategorije služe isključivo za logičku organizaciju i prikaz korisnicima.

## 5.2 Minimalni atributi vrste uplate

Svaka pojedinačna vrsta uplate mora imati najmanje:

| Atribut | Napomena |
|---------|----------|
| Pripadajuća kategorija | Obavezno |
| Puni naziv vrste uplate | Obavezno |
| Uplatni račun | Obavezno; referenca na uplatni račun definisan u Katalogu finansijskih obaveza (FS ne navodi konkretan broj računa) |
| Interna oznaka ili šifra | Obavezno |
| Status primjene | Obavezno |
| Pravni osnov | Kada bude utvrđen; inače „Potrebno pravno potvrditi“ |
| Napomena o ciljnoj grupi | Kada je moguće utvrditi (građani, preduzetnici, pravna lica ili više grupa) |

## 5.3 Referenca na uplatni račun (UR-01)

**Status:** USVOJENO

Funkcionalna specifikacija **ne unosi konkretne brojeve** uplatnih računa.

Svaka vrsta uplate ima **referencu** na odgovarajući uplatni račun definisan u dokumentu **Katalog finansijskih obaveza prema Opštini Kotor**.

Brojevi uplatnih računa u Katalogu predstavljaju referentne podatke preuzete iz važeće Naredbe o načinu uplate javnih prihoda i služe dokumentovanju poslovnih podataka. To ne predstavlja hardkodiranje niti projektovanje implementacije.

Katalog je poslovni referentni dokument. Nije šifrarnik i nije implementacioni artefakt. U narednim fazama iz Kataloga se izvodi aplikacioni šifrarnik (konfiguracioni izvor podataka).

Izvor liste vrsta uplata za dokumentaciju: isključivo Katalog (projektni spisak).

---

# FS-002 / 6. Korisnici

**Status:** REZERVISANO

Poglavlje će se definisati naknadnom odlukom.

---

# FS-002 / 7. Funkcionalni zahtjevi po tokovima (BP-01 do BP-09)

**Status:** USVOJENO

Poglavlje definiše funkcionalno ponašanje i korisnički tok od odabira vrste uplate do potvrde o ishodu plaćanja. Ne projektuje UI ekrane, API-je, protokole, webhooks, callback mehanizme, PDF, e-mail, digitalni potpis, fiskalizaciju, konkretan payment gateway, banku ni pružaoca platnih usluga.

## 7.0 Objedinjeni korisnički tok

1. Korisnik pronađe i odabere vrstu uplate (BP-01).
2. Sistem pribavi podatke za plaćanje automatski ili kroz ručni unos (BP-02); izvori obaveznih podataka određuju se konfiguracijom vrste uplate (BP-07).
3. Sistem izvrši validaciju.
4. Korisniku se prikaže pregled podataka za plaćanje (BP-03).
5. Korisnik potvrdi podatke (BP-03).
6. Tek nakon potvrde kreira se zapis o transakciji i korisnik se preusmjerava na payment gateway (BP-04, BP-08.1); životni ciklus statusa uređuje BP-08.
7. Nakon završetka obrade od strane sistema elektronskog plaćanja, modul ažurira status transakcije i prikaže informaciju korisniku (BP-05, BP-08).
8. Korisniku se prikaže potvrda o ishodu; za uspješne transakcije potvrda je dostupna za pregled i preuzimanje (BP-06); po potrebi se dostavlja potvrda izvornom informacionom sistemu (BP-08.5).
9. Korisnik može pregledati istoriju i detalje transakcija u skladu sa pravima pristupa (BP-09).

Korisnički tok do pokretanja plaćanja ostaje **isti** bez obzira na račun javnog prihoda ili implementaciju payment gateway-a (BP-04).

---

## 7.1 BP-01 – Pronalaženje vrste uplate

**Status:** USVOJENO

### Funkcionalno ponašanje

Korisnik može pronaći vrstu uplate na dva načina:

1. **Pregled po hijerarhiji:** kategorija → pojedinačna vrsta uplate.
2. **Pretraga po nazivu:** pretraga svih vrsta uplata po nazivu.

Oba načina koriste isti Katalog finansijskih obaveza prema Opštini Kotor kao jedini referentni izvor vrsta uplata.

### Poslovna pravila (funkcionalna)

* Kategorije služe za organizaciju i pregled.
* Pretraga se vrši nad pojedinačnim vrstama uplata.
* Pregled po kategorijama i pretraga vode do iste vrste uplate.
* Ne smije postojati posebna ili duplirana lista vrsta uplata za pretragu.
* Nazivi i klasifikacija vrsta uplata preuzimaju se iz Kataloga.
* Konkretni brojevi uplatnih računa ne navode se u ovoj specifikaciji; vrsta uplate ima referencu na račun iz Kataloga (UR-01).

---

## 7.2 BP-02 – Način popunjavanja podataka za plaćanje

**Status:** USVOJENO

### Funkcionalno ponašanje

Nakon odabira vrste uplate, podaci potrebni za plaćanje popunjavaju se kombinovanim modelom.

* **Automatsko preuzimanje:** ako za odabranu vrstu uplate postoji aktivna integracija sa izvornim informacionim sistemom, modul automatski preuzima podatke potrebne za plaćanje. Izvorni informacioni sistem ili nadležni organ ostaje mjerodavan izvor podataka (P-08).
* **Ručni unos:** ako integracija ne postoji, korisniku se prikazuje obrazac za ručni unos podataka potrebnih za plaćanje. Obavezni podaci mogu se razlikovati u zavisnosti od vrste uplate.

### Poslovna pravila (funkcionalna)

* Sistem određuje da li za konkretnu vrstu uplate postoji aktivna integracija.
* Korisnik ne bira između automatskog i ručnog načina.
* Kada postoji aktivna integracija, koristi se automatsko preuzimanje.
* Kada integracija ne postoji, koristi se ručni unos.
* Dodavanje integracije u budućnosti ne smije zahtijevati promjenu osnovnog korisničkog toka.
* Automatski preuzeti podaci ne smiju se proizvoljno mijenjati ako ih izvorni sistem označava kao mjerodavne ili neizmjenjive.
* Konkretne integracije, protokoli i izvorni sistemi ne projektuju se u ovoj specifikaciji.

---

## 7.3 BP-03 – Pregled i potvrda prije plaćanja

**Status:** USVOJENO

### Funkcionalno ponašanje

Prije pokretanja elektronskog plaćanja korisniku se mora prikazati pregled svih relevantnih podataka za plaćanje.

Plaćanje može biti pokrenuto tek nakon izričite potvrde korisnika.

### Korisnički tok

1. Korisnik odabere vrstu uplate.
2. Sistem automatski preuzme podatke ili korisnik unese potrebne podatke.
3. Sistem izvrši validaciju.
4. Korisniku se prikaže pregled podataka za plaćanje.
5. Korisnik potvrdi podatke.
6. Tek nakon potvrde može početi proces elektronskog plaćanja.

### Sadržaj pregleda (najmanje)

* kategorija;
* vrsta uplate;
* naziv primaoca;
* uplatni račun (referenca na Katalog / šifrarnik; bez navođenja konkretnog broja u FS);
* iznos;
* poziv na broj, kada postoji;
* svrha ili opis plaćanja, kada postoji;
* ostali relevantni podaci za konkretnu vrstu uplate.

Podaci koji nijesu primjenjivi na konkretnu vrstu uplate ne prikazuju se.

### Poslovna pravila (funkcionalna)

* Sve validacije moraju biti završene prije prikaza pregleda.
* Korisnik može da se vrati na prethodni korak i izmijeni podatke prije potvrde.
* Nakon potvrde i pokretanja procesa plaćanja, podaci te transakcije više se ne mogu mijenjati.
* Potvrda korisnika nije potvrda da je transakcija uspješno izvršena.
* Status uspjeha ili neuspjeha plaćanja utvrđuje se tek kroz kasnije definisan proces izvršenja plaćanja.
* Tehnički način potvrde, autentifikacija payment gateway-a i sadržaj potvrde o izvršenoj transakciji ne definišu se ovom odlukom.

---

## 7.4 BP-04 – Jedinstvena integracija sa sistemom elektronskog plaćanja

**Status:** USVOJENO

### Funkcionalno ponašanje

Modul Plaćanja koristi **jedinstvenu tehničku integraciju** sa sistemom elektronskog plaćanja (payment gateway).

Broj računa javnih prihoda ne uslovljava posebnu gateway integraciju za svaki pojedinačni račun.

Za svaku transakciju sistem, na osnovu odabrane vrste uplate i odgovarajuće konfiguracije, određuje na koji račun javnog prihoda se sredstva usmjeravaju.

### Funkcionalno pravilo – nepromjenjivost korisničkog toka

Korisnički tok ostaje isti bez obzira na:

* račun javnog prihoda pripadajući odabranoj vrsti uplate;
* implementaciju payment gateway-a;
* konkretnog pružaoca usluge elektronskog plaćanja.

Dodavanje nove vrste uplate ili promjena računa ne smije zahtijevati promjenu osnovnog korisničkog toka ni razvoj nove gateway integracije.

### Poslovna pravila (funkcionalna)

* Modul koristi jednu tehničku integraciju prema payment gateway-u.
* Jedna integracija može podržavati više računa javnih prihoda.
* Vrsta uplate određuje konfiguraciju plaćanja.
* Brojevi računa nisu dio poslovne logike; povezivanje vrste uplate sa računom vrši se kroz konfiguraciju (UR-01).
* Payment gateway je infrastrukturna komponenta, ne poslovna logika; poslovni proces je nezavisan od konkretne banke ili pružaoca.
* Zamjena payment gateway-a ne smije mijenjati poslovni tok korisnika.

### Veze

* **P-03** — Modul ne vodi izvorne finansijske evidencije.
* **P-08** — Izvorni sistemi ostaju mjerodavni.
* **UR-01** — Konfiguracioni podaci ne smiju biti hardkodirani.

### Ograničenje

Ova tačka **ne** opisuje API-je, protokole, merchant modele, terminal ID-jeve, MID-ove, banku, konkretan payment gateway ni tehnologiju.

---

## 7.5 BP-05 – Obrada ishoda elektronskog plaćanja

**Status:** USVOJENO

### Funkcionalno ponašanje

Nakon završetka procesa elektronskog plaćanja, modul Plaćanja mora:

* evidentirati ishod transakcije;
* korisniku prikazati odgovarajuću informaciju.

Modul **ne** donosi odluku o uspješnosti transakcije, već koristi status koji vrati sistem elektronskog plaćanja.

### Funkcionalno ponašanje po ishodima

Sistem mora podržati najmanje sljedeće ishode i za svaki prikazati odgovarajuću poruku, te jedinstveni identifikator transakcije kada je dostupan:

| Ishod | Funkcionalno ponašanje |
|-------|------------------------|
| Plaćanje uspješno izvršeno | Korisniku se prikaže poruka o uspješnom izvršenju i identifikator transakcije (kada je dostupan). Transakcija se evidentira sa statusom **Uspješna** (BP-08). |
| Plaćanje nije izvršeno | Korisniku se prikaže poruka da plaćanje nije izvršeno i identifikator transakcije (kada je dostupan). Transakcija se evidentira sa statusom **Neuspješna** (BP-08). |
| Plaćanje otkazano od strane korisnika | Korisniku se prikaže poruka da je plaćanje otkazano i identifikator transakcije (kada je dostupan). Transakcija se evidentira sa statusom **Otkazana** (BP-08). |
| Status trenutno nije moguće potvrditi | Korisniku se prikaže informativna poruka da status plaćanja trenutno nije moguće potvrditi (npr. privremena nedostupnost ili obrada u toku) i identifikator transakcije (kada je dostupan). Transakcija zadržava status **U toku** (BP-08). |

Napomena (usklađenje sa BP-08):

Poruka „Status trenutno nije moguće potvrditi“ predstavlja korisničku informaciju i **ne** uvodi novi status transakcije. Dok se čeka konačna potvrda od payment gateway-a, transakcija zadržava status **U toku**.

Interni statusi transakcije uređeni su životnim ciklusom (BP-08): Kreirana, U toku, Uspješna, Neuspješna, Otkazana.

### Evidencija (funkcionalni zahtjev)

Za svaku pokrenutu transakciju sistem mora omogućiti evidenciju najmanje:

* jedinstvenog identifikatora transakcije;
* vrste uplate;
* datuma i vremena pokretanja;
* datuma i vremena završetka, kada je poznato;
* trenutnog statusa transakcije (status transakcije u trenutku evidentiranja);
* identifikatora payment gateway-a, kada postoji.

Struktura baze i tehnički model evidencije ne definišu se u ovoj specifikaciji.

### Poslovna pravila (funkcionalna)

* Modul ne potvrđuje finansijsko knjiženje u izvornim sistemima.
* Modul evidentira rezultat procesa elektronskog plaćanja.
* Dalja obrada u izvornim sistemima ostaje predmet njihovih poslovnih pravila.
* Evidencija transakcija mora omogućiti naknadnu provjeru i reviziju.
* Potvrda korisnika prije plaćanja (BP-03) nije potvrda uspjeha; ishod se utvrđuje tek nakon obrade od strane sistema elektronskog plaćanja.

### Veze

* **BP-03** — Potvrda prije pokretanja plaćanja.
* **BP-04** — Jedinstvena integracija sa payment gateway-em.
* **BP-08** — Interni statusi i prelazi transakcije; korisničke poruke o ishodu nisu novi statusi.

### Ograničenje

Ova tačka **ne** opisuje API-je, webhooks, callback mehanizme, konkretan payment gateway, banku ni model baze podataka.

---

## 7.6 BP-06 – Potvrda o izvršenom elektronskom plaćanju

**Status:** USVOJENO

### Funkcionalno ponašanje

Nakon završetka procesa elektronskog plaćanja, modul Plaćanja korisniku prikazuje potvrdu o ishodu transakcije.

Ako je transakcija uspješno izvršena, potvrda mora biti dostupna za pregled i preuzimanje.

Potvrda predstavlja evidenciju izvršene elektronske transakcije u okviru modula Plaćanja i **ne predstavlja službeni finansijski dokument niti potvrdu da je uplata već proknjižena u izvornom informacionom sistemu**.

### Dostupnost i preuzimanje

Sistem mora omogućiti da korisnik:

* pregleda potvrdu odmah nakon završetka transakcije;
* ponovo otvori potvrdu iz istorije svojih transakcija, u skladu sa pravilima definisanim u BP-09;
* preuzme potvrdu u elektronskom obliku.

### Kada se generiše potvrda o uspješnom plaćanju

* Potvrda se generiše isključivo za transakcije koje imaju konačan status (**Uspješna**).
* Za transakcije u statusu **Otkazana** ili **Neuspješna**, te za transakcije u statusu **U toku** (kada se korisniku prikazuje informativna poruka da status plaćanja trenutno nije moguće potvrditi), sistem prikazuje odgovarajuću informaciju, ali **ne** generiše potvrdu o uspješnom plaćanju.

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
* broj računa na koji je izvršena uplata (referenca na konfiguraciju / Katalog; bez navođenja konkretnog broja u FS — UR-01);
* poziv na broj, kada postoji.

Napomena (terminologija identifikatora):

* **Jedinstveni identifikator transakcije** — interni identifikator koji vodi modul Plaćanja.
* **Referentni broj transakcije** — identifikator koji se prikazuje korisniku u istoriji, detaljima i potvrdi (BP-09).
* **Identifikator payment gateway-a** — eksterni identifikator koji dodjeljuje payment gateway.

Da li su jedinstveni identifikator i referentni broj isti ili različiti ostaje **implementaciona odluka**. Na potvrdi se prikazuje identifikator u skladu sa tom implementacionom odlukom, uz identifikator payment gateway-a kada postoji.

Ne prikazivati povjerljive podatke platne kartice.

### Poslovna pravila (funkcionalna)

* Potvrda ne zamjenjuje račun, rješenje ili drugi službeni dokument.
* Potvrda ne predstavlja dokaz da je uplata već evidentirana u izvornom sistemu.
* Potvrda predstavlja potvrdu da je elektronska platna transakcija završena sa prikazanim statusom.
* Naknadna potvrda ili odbijanje knjiženja u izvornom sistemu nije obuhvaćeno ovom odlukom.

### Veze

* **BP-05** — Obrada ishoda elektronskog plaćanja.
* **BP-09** — Istorija transakcija i pregled plaćanja.

### Ograničenje

Ova tačka **ne** definiše format datoteke, PDF, e-mail potvrde, digitalni potpis, fiskalizaciju, elektronski pečat, arhiviranje, računovodstveno knjiženje ni API-je.

---

## 7.7 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje

**Status:** USVOJENO

### Funkcionalno ponašanje

Za svaku vrstu uplate konfiguracijom se određuje izvor obaveznih podataka potrebnih za elektronsko plaćanje.

BP-07 definiše izvore za:

* iznos (BP-07.1);
* primaoca (BP-07.2);
* račun za uplatu (BP-07.3);
* poziv na broj (BP-07.4);
* svrhu plaćanja (BP-07.5).

### Veze

* **BP-02** — Način popunjavanja podataka (automatski / ručni); BP-07 precizira izvore po poljima.
* **BP-03** — Pregled i potvrda prije plaćanja koristi podatke pribavljene prema BP-07.
* **UR-01** — Broj računa dolazi iz konfiguracionog šifrarnika izvedenog iz Kataloga.
* **BP-04** — Brojevi računa nisu dio poslovne logike; povezivanje vrste uplate sa računom vrši se kroz konfiguraciju.

---

### BP-07.1 – Izvor iznosa

#### Funkcionalno ponašanje

Za svaku vrstu uplate konfiguracijom se određuje način određivanja iznosa.

Podržani modeli:

1. Fiksni iznos.
2. Iznos iz izvornog informacionog sistema.
3. Ručni unos.
4. Predloženi iznos koji korisnik može izmijeniti samo ako je to dozvoljeno konfiguracijom.

#### Poslovna pravila (funkcionalna)

* Način određivanja iznosa definiše se konfiguracijom vrste uplate.
* Korisnik može mijenjati iznos samo kada je to dozvoljeno konfiguracijom.
* Kada iznos dolazi iz izvornog informacionog sistema, korisnik ga ne može mijenjati.
* Modul Plaćanja ne obračunava iznos, već ga preuzima ili prihvata u skladu sa pravilima za konkretnu vrstu uplate.

---

### BP-07.2 – Izvor primaoca

#### Funkcionalno ponašanje

Za svaku vrstu uplate konfiguracijom se određuje način određivanja primaoca.

Podržani modeli:

1. Fiksni primalac.
2. Primalac iz izvornog informacionog sistema.

#### Poslovna pravila (funkcionalna)

* Način određivanja primaoca definiše se konfiguracijom vrste uplate.
* Korisnik ne može mijenjati primaoca.
* Kada primalac dolazi iz izvornog informacionog sistema, korisnik ga ne može mijenjati.
* Modul Plaćanja ne određuje primaoca već ga preuzima ili koristi iz konfiguracije.

---

### BP-07.3 – Izvor računa za uplatu

#### Funkcionalno ponašanje

Broj računa određuje se iz konfiguracionog šifrarnika koji je izveden iz Kataloga finansijskih obaveza.

Katalog ostaje referentni poslovni dokument, dok aplikacija koristi konfiguracioni šifrarnik kao operativni izvor podataka.

#### Poslovna pravila (funkcionalna)

* Katalog finansijskih obaveza predstavlja referentni izvor poslovnih podataka.
* Konfiguracioni šifrarnik predstavlja operativni izvor koji koristi aplikacija.
* Broj računa nije dio poslovne logike niti aplikacionog koda.
* Promjena broja računa vrši se kroz konfiguraciju bez izmjene poslovnog modela ili aplikacionog koda.
* Korisnik ne može mijenjati broj računa.
* FS ne navodi konkretne brojeve računa (UR-01).

---

### BP-07.4 – Izvor poziva na broj

#### Funkcionalno ponašanje

Za svaku vrstu uplate konfiguracijom se određuje način određivanja poziva na broj.

Podržani modeli:

1. Bez poziva na broj.
2. Fiksna vrijednost.
3. Vrijednost iz izvornog informacionog sistema.
4. Ručni unos.

#### Poslovna pravila (funkcionalna)

* Način određivanja poziva na broj definiše se konfiguracijom vrste uplate.
* Kada poziv na broj dolazi iz izvornog informacionog sistema, korisnik ga ne može mijenjati.
* Kada je predviđen ručni unos primjenjuju se definisana pravila validacije.
* Kada za vrstu uplate nije predviđen poziv na broj, polje se ne prikazuje korisniku.
* Modul Plaćanja ne generiše poziv na broj osim ako to bude definisano posebnom poslovnom odlukom.

---

### BP-07.5 – Izvor svrhe plaćanja

#### Funkcionalno ponašanje

Za svaku vrstu uplate konfiguracijom se određuje način određivanja svrhe plaćanja.

Podržani modeli:

1. Bez svrhe plaćanja.
2. Fiksna svrha plaćanja.
3. Svrha iz izvornog informacionog sistema.
4. Ručni unos.

#### Poslovna pravila (funkcionalna)

* Način određivanja svrhe plaćanja definiše se konfiguracijom vrste uplate.
* Kada svrha dolazi iz izvornog informacionog sistema, korisnik je ne može mijenjati.
* Kada je predviđen ručni unos primjenjuju se definisana pravila validacije.
* Kada za vrstu uplate svrha nije predviđena, polje se ne prikazuje korisniku.
* Modul Plaćanja ne generiše svrhu plaćanja osim ako to bude definisano posebnom poslovnom odlukom.

---

## 7.8 BP-08 – Životni ciklus transakcije

**Status:** USVOJENO

### Funkcionalno ponašanje

Životni ciklus transakcije uređuje kreiranje zapisa, statuse, dozvoljene prelaze i eventualnu potvrdu izvornom informacionom sistemu.

BP-08 definiše:

* evidentiranje transakcije (BP-08.1);
* statuse transakcije (BP-08.2);
* promjenu statusa transakcije (BP-08.3);
* dozvoljene prelaze između statusa (BP-08.4);
* potvrdu uspješnog plaćanja izvornom informacionom sistemu (BP-08.5).

### Veze

* **BP-03** — Transakcija se kreira nakon izričite potvrde korisnika.
* **BP-04** — Preusmjeravanje na payment gateway kroz jedinstvenu integraciju.
* **BP-05** — Obrada ishoda elektronskog plaćanja.
* **BP-06** — Potvrda korisniku zasniva se na konačnom ishodu transakcije.
* **P-08** — Izvorni sistem ostaje mjerodavan; modul ne preuzima njegovu poslovnu logiku.

---

### BP-08.1 – Evidentiranje transakcije

#### Funkcionalno ponašanje

Zapis o transakciji kreira se prije preusmjeravanja korisnika na payment gateway.

Nakon kreiranja transakcije korisnik se preusmjerava na payment gateway radi izvršenja plaćanja.

Status transakcije se naknadno ažurira na osnovu povratne informacije od payment gateway-a.

#### Poslovna pravila (funkcionalna)

* Transakcija se kreira odmah nakon što korisnik potvrdi plaćanje.
* Svaka transakcija dobija **jedinstveni identifikator transakcije** (interni identifikator koji vodi modul Plaćanja).
* Početni status svake novoformirane transakcije je **Kreirana**.
* Početni status transakcije određuje se prije preusmjeravanja korisnika na payment gateway.
* Payment gateway koristi identifikator transakcije za povezivanje povratnih informacija sa odgovarajućom transakcijom.
* Modul Plaćanja čuva zapis o svim započetim transakcijama, bez obzira na njihov konačni ishod.

Napomena (terminologija): Odnos jedinstvenog identifikatora transakcije i referentnog broja transakcije (prikaz korisniku) nije propisan ovom odlukom; to ostaje implementaciona odluka.

---

### BP-08.2 – Statusi transakcije

#### Funkcionalno ponašanje

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

#### Poslovna pravila (funkcionalna)

* Svaka transakcija u svakom trenutku ima tačno jedan status.
* Status transakcije mijenja se isključivo prema pravilima životnog ciklusa transakcije.
* Konačni statusi su Uspješna, Neuspješna i Otkazana.
* Nakon prelaska u konačni status transakcija se više ne vraća u prethodni status.

---

### BP-08.3 – Promjena statusa transakcije

#### Funkcionalno ponašanje

Status transakcije mijenja se isključivo automatski, na osnovu događaja koje modul Plaćanja primi od payment gateway-a ili internih sistemskih procesa.

Administrator i ostali korisnici nemaju mogućnost ručne izmjene statusa transakcije.

#### Poslovna pravila (funkcionalna)

* Status transakcije može mijenjati isključivo sistem.
* Ručna izmjena statusa transakcije nije dozvoljena.
* Promjena statusa mora biti zasnovana na verifikovanom sistemskom događaju.
* Svaka promjena statusa evidentira se u audit zapisu sa vremenom promjene i izvorom događaja.
* Modul Plaćanja ne dozvoljava zaobilaženje definisanog životnog ciklusa transakcije.

---

### BP-08.4 – Dozvoljeni prelazi između statusa

#### Funkcionalno ponašanje

Životni ciklus transakcije definiše se kao mašina stanja (State Machine).

Dozvoljeni su isključivo unaprijed definisani prelazi između statusa.

#### Dozvoljeni prelazi

* Kreirana → U toku
* U toku → Uspješna
* U toku → Neuspješna
* U toku → Otkazana

Nijedan drugi prelaz nije dozvoljen.

#### Poslovna pravila (funkcionalna)

* Dozvoljeni su isključivo definisani prelazi između statusa.
* Sistem mora odbiti svaki pokušaj nedozvoljenog prelaza.
* Statusi Uspješna, Neuspješna i Otkazana predstavljaju konačna stanja.
* Nakon dostizanja konačnog stanja nisu dozvoljene dalje promjene statusa.
* Svaki uspješan ili odbijen pokušaj promjene statusa evidentira se u audit zapisu.

---

### BP-08.5 – Potvrda uspješnog plaćanja izvornom informacionom sistemu

#### Funkcionalno ponašanje

Nakon što payment gateway potvrdi uspješno izvršeno plaćanje, modul Plaćanja:

1. ažurira status transakcije na Uspješna;
2. evidentira uspješno izvršeno plaćanje;
3. ako je za vrstu uplate definisana integracija, automatski dostavlja potvrdu izvornom informacionom sistemu;
4. ako integracija nije definisana, proces se završava evidentiranjem uspješne transakcije u modulu Plaćanja.

#### Poslovna pravila (funkcionalna)

* Potvrda izvornom informacionom sistemu šalje se samo kada je za konkretnu vrstu uplate definisana integracija.
* Modul Plaćanja ne preuzima poslovnu logiku izvornog informacionog sistema.
* Dostavljanje potvrde izvornom informacionom sistemu predstavlja razmjenu informacija o uspješno izvršenom plaćanju i **ne** predstavlja finansijsko knjiženje niti potvrdu knjiženja u izvornom informacionom sistemu.
* Ako potvrdu nije moguće dostaviti, transakcija zadržava status Uspješna, a neuspjela isporuka potvrde evidentira se kao poseban sistemski događaj.
* Svaki pokušaj dostavljanja potvrde evidentira se u audit zapisu.

### Ograničenje

Ova tačka **ne** projektuje API-je, webhooks, callback mehanizme, shemu audit zapisa, niti konkretne integracije sa izvornim sistemima.

---

## 7.9 BP-09 – Istorija transakcija i pregled plaćanja

**Status:** USVOJENO

### Funkcionalno ponašanje

Modul Plaćanja omogućava istoriju transakcija i pregled plaćanja, uključujući listu, pretragu, detaljan pregled i zadržavanje / arhiviranje zapisa.

BP-09 definiše:

* pravo pristupa istoriji transakcija (BP-09.1);
* sadržaj liste transakcija (BP-09.2);
* pretragu i filtriranje istorije transakcija (BP-09.3);
* detaljan pregled transakcije (BP-09.4);
* zadržavanje (retention) i arhiviranje istorije transakcija (BP-09.5).

### Veze

* **BP-06** — Potvrda o izvršenom plaćanju; istorija omogućava ponovni pristup potvrdi / detaljima.
* **BP-08** — Statusi i životni ciklus transakcije.
* **UR-01** — Broj računa na pregledu dolazi iz konfiguracionog izvora; FS ne navodi konkretne brojeve računa.

---

### BP-09.1 – Pravo pristupa istoriji transakcija

#### Funkcionalno ponašanje

Pristup istoriji transakcija određuje se na osnovu uloge korisnika.

* Korisnik može pregledati isključivo svoje transakcije.
* Administrator platforme može pregledati sve transakcije u skladu sa svojim ovlašćenjima.

#### Poslovna pravila (funkcionalna)

* Korisnik može pregledati isključivo transakcije koje je sam inicirao.
* Administrator platforme može pregledati sve transakcije.
* Pregled transakcija ne daje pravo izmjene podataka.
* Prava pristupa određuju se u skladu sa ulogama na platformi.
* Administrativni pregled transakcija evidentira se u audit logu.

---

### BP-09.2 – Sadržaj liste transakcija

#### Funkcionalno ponašanje

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

#### Poslovna pravila (funkcionalna)

* Lista prikazuje samo podatke koje korisnik ima pravo da vidi.
* Lista ne prikazuje osjetljive podatke o sredstvu plaćanja (broj kartice, CVV, puni broj računa korisnika i sl.).
* Svaka transakcija ima **referentni broj transakcije** prikazan u listi.
* Iz liste je moguće otvoriti detaljan pregled transakcije.
* Polja koja nisu primjenjiva za određenu transakciju ne prikazuju vrijednost ili se prikazuju kao prazna, u skladu sa pravilima korisničkog interfejsa.

Napomena (terminologija): Referentni broj transakcije je identifikator koji se prikazuje korisniku. Odnos prema jedinstvenom identifikatoru transakcije nije propisan ovom odlukom.

---

### BP-09.3 – Pretraga i filtriranje istorije transakcija

#### Funkcionalno ponašanje

Modul Plaćanja omogućava naprednu pretragu i filtriranje istorije transakcija.

Podržani kriterijumi:

* Period (od–do)
* Status transakcije
* Vrsta uplate
* Referentni broj transakcije
* Primalac
* Raspon iznosa (od–do)
* Tekstualna pretraga (gdje je primjenjivo)

#### Poslovna pravila (funkcionalna)

* Moguće je kombinovati više kriterijuma filtriranja.
* Primijenjeni filteri utiču isključivo na prikaz rezultata i ne mijenjaju podatke.
* Rezultati pretrage prikazuju se u skladu sa pravima pristupa korisnika.
* Ako nijedna transakcija ne odgovara zadatim kriterijumima, sistem prikazuje odgovarajuću informativnu poruku.
* Filteri ne omogućavaju pristup transakcijama za koje korisnik nema ovlašćenje.

---

### BP-09.4 – Detaljan pregled transakcije

#### Funkcionalno ponašanje

Detaljan pregled transakcije prikazuje sve relevantne podatke korišćene ili nastale tokom procesa plaćanja.

Prikazuju se:

* Datum i vrijeme kreiranja transakcije
* Datum i vrijeme posljednje promjene statusa
* Referentni broj transakcije
* Vrsta uplate
* Iznos
* Status transakcije
* Primalac
* Broj računa za uplatu (referenca na konfiguraciju / Katalog; bez navođenja konkretnog broja u FS — UR-01)
* Poziv na broj (kada postoji)
* Svrha plaćanja (kada postoji)
* Način plaćanja (ako ga payment gateway dostavlja)
* **Identifikator payment gateway-a** (ako postoji)
* Status dostavljanja potvrde izvornom informacionom sistemu (kada postoji integracija)

#### Poslovna pravila (funkcionalna)

* Detaljan pregled prikazuje samo podatke koje korisnik ima pravo da vidi.
* Osjetljivi podaci o sredstvu plaćanja nikada se ne prikazuju.
* Ako određeni podatak nije primjenjiv za konkretnu transakciju, ne prikazuje se ili se označava kao „Nije primjenjivo“, u skladu sa pravilima korisničkog interfejsa.
* Detaljan pregled je isključivo informativan i ne omogućava izmjenu podataka.

---

### BP-09.5 – Zadržavanje (Retention) i arhiviranje istorije transakcija

#### Funkcionalno ponašanje

Transakcije se trajno evidentiraju u sistemu.

Radi optimizacije performansi, starije transakcije mogu biti automatski arhivirane, pri čemu ostaju dostupne za pregled i pretragu u skladu sa pravima pristupa.

#### Poslovna pravila (funkcionalna)

* Sve transakcije trajno se evidentiraju.
* Arhiviranje ne mijenja sadržaj transakcije niti njen identitet.
* Arhivirane transakcije ostaju dostupne za pregled i pretragu u skladu sa pravima pristupa.
* Funkcionalnosti modula Plaćanja ne omogućavaju brisanje transakcija.
* Način i period arhiviranja definišu se sistemskom konfiguracijom i važećim propisima.

### Ograničenje

Ova tačka **ne** projektuje UI raspored, paginaciju, tehnologiju arhiviranja, shemu baze ni API-je.

---

# FS-002 / 8. Poslovna pravila

**Status:** U IZRADI

| ID | Pravilo | Izvor | Status |
|----|---------|-------|--------|
| BR-P-001 | Modul služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor. | P-01 | USVOJENO |
| BR-P-002 | V1 je elektronski kanal za plaćanje obaveza koje se mogu platiti na blagajni Opštine Kotor. | P-02 | USVOJENO |
| BR-P-003 | Modul ne obračunava obaveze, ne donosi upravna rješenja, ne kreira zaduženja i ne vodi izvorne evidencije. | P-03 | USVOJENO |
| BR-P-004 | Modul ne uvodi nove obaveze niti mijenja postojeće poslovne procese Opštine. | P-04 | USVOJENO |
| BR-P-005 | Svaka funkcionalnost mora imati pravni osnov. | P-05 | USVOJENO |
| BR-P-006 | Vrsta obaveze mora biti povezana sa pravnim osnovom; nepotvrđen osnov = „Potrebno pravno potvrditi“. | P-07 | USVOJENO |
| BR-P-007 | Izvorni sistem / nadležni organ ostaje mjerodavan; modul ne mijenja sadržaj tih podataka. | P-08 | USVOJENO |
| BR-P-008 | Jedinica podrške sistema je pojedinačna vrsta uplate, ne kategorija. | F-01 | USVOJENO |
| BR-P-009 | V1 podržava sve projektom obuhvaćene vrste uplata i uplatne račune. | F-01 | USVOJENO |
| BR-P-010 | Svaka vrsta uplate ima referencu na uplatni račun definisan u Katalogu; FS ne navodi konkretne brojeve računa. | UR-01 | USVOJENO |
| BR-P-011 | Lista vrsta uplata u dokumentaciji dolazi isključivo iz projektnog spiska (Katalog). | F-01 | USVOJENO |
| BR-P-012 | Katalog je poslovni referentni dokument; nije šifrarnik ni implementacioni artefakt. | UR-01 | USVOJENO |
| BR-P-013 | Uplatni računi u aplikaciji učitavaju se iz konfiguracionog izvora (šifrarnika); ne smiju biti hardkodirani. | UR-01 | USVOJENO |
| BR-P-014 | Korisnik pronalazi vrstu uplate pregledom po hijerarhiji (kategorija → vrsta) ili pretragom po nazivu. | BP-01 | USVOJENO |
| BR-P-015 | Pregled i pretraga koriste isti Katalog; nema duplirane liste za pretragu. | BP-01 | USVOJENO |
| BR-P-016 | Pretraga se vrši nad pojedinačnim vrstama uplata; kategorije služe organizaciji i pregledu. | BP-01 | USVOJENO |
| BR-P-017 | Način popunjavanja podataka (automatski / ručni) određuje sistem prema postojanju aktivne integracije; korisnik ne bira način. | BP-02 | USVOJENO |
| BR-P-018 | Kada postoji aktivna integracija koristi se automatsko preuzimanje; inače ručni unos. | BP-02 | USVOJENO |
| BR-P-019 | Dodavanje integracije ne smije zahtijevati promjenu osnovnog korisničkog toka. | BP-02 | USVOJENO |
| BR-P-020 | Automatski preuzeti podaci označeni kao mjerodavni/neizmjenjivi ne smiju se proizvoljno mijenjati. | BP-02 | USVOJENO |
| BR-P-021 | Prije pokretanja plaćanja obavezan je pregled relevantnih podataka i izričita potvrda korisnika. | BP-03 | USVOJENO |
| BR-P-022 | Sve validacije moraju biti završene prije prikaza pregleda. | BP-03 | USVOJENO |
| BR-P-023 | Prije potvrde korisnik može da se vrati i izmijeni podatke; nakon potvrde i pokretanja plaćanja podaci transakcije nisu izmjenjivi. | BP-03 | USVOJENO |
| BR-P-024 | Potvrda korisnika nije potvrda uspjeha transakcije; status uspjeha/neuspjeha utvrđuje se kasnijim procesom izvršenja. | BP-03 | USVOJENO |
| BR-P-025 | Modul koristi jednu tehničku integraciju prema payment gateway-u; broj računa ne uslovljava posebne integracije. | BP-04 | USVOJENO |
| BR-P-026 | Vrsta uplate određuje konfiguraciju plaćanja; brojevi računa nisu dio poslovne logike (konfiguracija / UR-01). | BP-04 | USVOJENO |
| BR-P-027 | Korisnički tok ostaje isti bez obzira na račun javnog prihoda ili implementaciju payment gateway-a. | BP-04 | USVOJENO |
| BR-P-028 | Dodavanje vrste uplate ili promjena računa ne smije zahtijevati novu gateway integraciju niti izmjenu osnovnog korisničkog toka. | BP-04 | USVOJENO |
| BR-P-029 | Payment gateway je infrastruktura; poslovni proces je nezavisan od konkretne banke ili pružaoca; zamjena gateway-a ne mijenja tok korisnika. | BP-04 | USVOJENO |
| BR-P-030 | Nakon završetka plaćanja modul evidentira ishod i prikazuje informaciju korisniku; uspješnost određuje status sistema elektronskog plaćanja, ne modul. | BP-05 | USVOJENO |
| BR-P-031 | Sistem podržava najmanje korisničke ishode/poruke: uspješno, nije izvršeno, otkazano od korisnika, te informativnu poruku „status trenutno nije moguće potvrditi“ (nije novi status transakcije; interni status ostaje U toku — BP-08). | BP-05 | USVOJENO |
| BR-P-032 | Za svaki ishod korisniku se prikazuje odgovarajuća poruka i jedinstveni identifikator transakcije kada je dostupan. | BP-05 | USVOJENO |
| BR-P-033 | Za svaku pokrenutu transakciju evidentiraju se najmanje: jedinstveni identifikator transakcije, vrsta uplate, vrijeme pokretanja, vrijeme završetka (kada je poznato), trenutni status transakcije, identifikator payment gateway-a (kada postoji). | BP-05 | USVOJENO |
| BR-P-034 | Modul ne potvrđuje finansijsko knjiženje u izvornim sistemima; evidencija omogućava naknadnu provjeru i reviziju. | BP-05 | USVOJENO |
| BR-P-035 | Nakon završetka plaćanja korisniku se prikazuje potvrda o ishodu; za uspješne transakcije potvrda je dostupna za pregled i preuzimanje. | BP-06 | USVOJENO |
| BR-P-036 | Potvrda o uspješnom plaćanju generiše se samo za status Uspješna; za Otkazana/Neuspješna i za U toku (informativna poruka da status trenutno nije moguće potvrditi) prikazuje se informacija bez potvrde o uspjehu. | BP-06 | USVOJENO |
| BR-P-037 | Potvrda sadrži najmanje: jedinstveni identifikator transakcije, datum/vrijeme, vrstu uplate, naziv obaveze, iznos, status, identifikator payment gateway-a (kada postoji), primaoca, račun, poziv na broj (kada postoji); bez podataka kartice. | BP-06 | USVOJENO |
| BR-P-038 | Potvrda nije službeni finansijski dokument niti dokaz knjiženja u izvornom sistemu; predstavlja evidenciju završene elektronske transakcije sa prikazanim statusom. | BP-06 | USVOJENO |
| BR-P-039 | Način određivanja iznosa definiše se konfiguracijom vrste uplate; podržani modeli: fiksni, iz izvornog sistema, ručni unos, predloženi izmjenjiv samo ako je dozvoljeno. | BP-07.1 | USVOJENO |
| BR-P-040 | Korisnik može mijenjati iznos samo kada je to dozvoljeno konfiguracijom; kada iznos dolazi iz izvornog sistema, korisnik ga ne može mijenjati. | BP-07.1 | USVOJENO |
| BR-P-041 | Modul ne obračunava iznos, već ga preuzima ili prihvata u skladu sa pravilima za konkretnu vrstu uplate. | BP-07.1 | USVOJENO |
| BR-P-042 | Način određivanja primaoca definiše se konfiguracijom vrste uplate; podržani modeli: fiksni primalac, primalac iz izvornog sistema. | BP-07.2 | USVOJENO |
| BR-P-043 | Korisnik ne može mijenjati primaoca; modul ne određuje primaoca već ga preuzima ili koristi iz konfiguracije. | BP-07.2 | USVOJENO |
| BR-P-044 | Broj računa određuje se iz konfiguracionog šifrarnika izvedenog iz Kataloga; Katalog je referentni, šifrarnik operativni izvor. | BP-07.3 | USVOJENO |
| BR-P-045 | Broj računa nije dio poslovne logike niti aplikacionog koda; promjena se vrši kroz konfiguraciju; korisnik ne može mijenjati broj računa. | BP-07.3 | USVOJENO |
| BR-P-046 | Način određivanja poziva na broj definiše se konfiguracijom; modeli: bez poziva, fiksna vrijednost, iz izvornog sistema, ručni unos. | BP-07.4 | USVOJENO |
| BR-P-047 | Kada poziv na broj dolazi iz izvornog sistema, korisnik ga ne može mijenjati; kada nije predviđen, polje se ne prikazuje; modul ga ne generiše bez posebne odluke. | BP-07.4 | USVOJENO |
| BR-P-048 | Način određivanja svrhe plaćanja definiše se konfiguracijom; modeli: bez svrhe, fiksna, iz izvornog sistema, ručni unos. | BP-07.5 | USVOJENO |
| BR-P-049 | Kada svrha dolazi iz izvornog sistema, korisnik je ne može mijenjati; kada nije predviđena, polje se ne prikazuje; modul je ne generiše bez posebne odluke. | BP-07.5 | USVOJENO |
| BR-P-050 | Zapis o transakciji kreira se prije preusmjeravanja na payment gateway, odmah nakon potvrde korisnika; svaka transakcija dobija jedinstveni identifikator transakcije (interni identifikator koji vodi modul). | BP-08.1 | USVOJENO |
| BR-P-051 | Početni status svake novoformirane transakcije je Kreirana; određuje se prije preusmjeravanja; gateway koristi identifikator za povezivanje povratnih informacija; čuvaju se svi započeti zapisi. | BP-08.1 | USVOJENO |
| BR-P-052 | Statusi transakcije: Kreirana, U toku, Uspješna, Neuspješna, Otkazana; transakcija ima tačno jedan status u svakom trenutku. | BP-08.2 | USVOJENO |
| BR-P-053 | Konačni statusi su Uspješna, Neuspješna i Otkazana; nakon konačnog statusa nema povratka u prethodni status. | BP-08.2 | USVOJENO |
| BR-P-054 | Status mijenja isključivo sistem na osnovu verifikovanog događaja; ručna izmjena nije dozvoljena; promjene se evidentišu u audit zapisu. | BP-08.3 | USVOJENO |
| BR-P-055 | Dozvoljeni prelazi: Kreirana→U toku; U toku→Uspješna; U toku→Neuspješna; U toku→Otkazana; ostali prelazi se odbijaju. | BP-08.4 | USVOJENO |
| BR-P-056 | Nakon konačnog stanja dalje promjene statusa nisu dozvoljene; uspješni i odbijeni pokušaji prelaza evidentišu se u audit zapisu. | BP-08.4 | USVOJENO |
| BR-P-057 | Nakon uspješnog plaćanja: status Uspješna, evidencija uspjeha; potvrda izvornom sistemu samo ako postoji integracija za vrstu uplate. | BP-08.5 | USVOJENO |
| BR-P-058 | Modul ne preuzima poslovnu logiku izvornog sistema; dostava potvrde nije knjiženje niti potvrda knjiženja; neuspjeh isporuke ne mijenja status Uspješna već se bilježi kao poseban sistemski događaj. | BP-08.5 | USVOJENO |
| BR-P-059 | Korisnik pregleda isključivo transakcije koje je sam inicirao; administrator platforme može pregledati sve transakcije; pregled ne daje pravo izmjene. | BP-09.1 | USVOJENO |
| BR-P-060 | Administrativni pregled transakcija evidentira se u audit logu; prava pristupa određuju se ulogama na platformi. | BP-09.1 | USVOJENO |
| BR-P-061 | Lista transakcija prikazuje najmanje: datum/vrijeme, vrstu uplate, iznos, status, referentni broj, primaoca, poziv na broj (kada postoji), način plaćanja (ako gateway dostavlja), akciju pregleda detalja. | BP-09.2 | USVOJENO |
| BR-P-062 | Lista i detalji ne prikazuju osjetljive podatke o sredstvu plaćanja; prikazuju samo podatke koje korisnik ima pravo da vidi. | BP-09.2 | USVOJENO |
| BR-P-063 | Pretraga/filtriranje podržava period, status, vrstu uplate, referentni broj, primaoca, raspon iznosa i tekstualnu pretragu; filteri se mogu kombinovati i ne mijenjaju podatke. | BP-09.3 | USVOJENO |
| BR-P-064 | Rezultati pretrage poštuju prava pristupa; bez pogodaka prikazuje se informativna poruka; filteri ne otkrivaju neovlašćene transakcije. | BP-09.3 | USVOJENO |
| BR-P-065 | Detaljan pregled je informativan (bez izmjene) i prikazuje relevantne podatke procesa plaćanja uključujući status dostave potvrde izvornom sistemu kada postoji integracija. | BP-09.4 | USVOJENO |
| BR-P-066 | Transakcije se trajno evidentiraju; brisanje nije dozvoljeno; arhiviranje ne mijenja sadržaj/identitet i zadržava dostupnost za pregled/pretragu. | BP-09.5 | USVOJENO |

---

# FS-002 / 9. Prihvatni kriterijumi V1

**Status:** U IZRADI

Okvirni kriterijumi (prošireni za BP-01–BP-09; ostatak nakon detaljnih UI odluka i tehničkog ugovaranja gateway-a):

* Sve vrste uplata iz popunjenog Kataloga dostupne su za elektronsko plaćanje u V1.
* Svaka vrsta uplate ima atribute iz F-01 i referencu na uplatni račun iz Kataloga.
* Funkcionalna specifikacija ne sadrži konkretne brojeve uplatnih računa.
* Uplatni računi u aplikaciji nisu hardkodirani; učitavaju se iz konfiguracionog izvora (šifrarnika).
* Modul ne obavlja obračun, upravna rješenja, kreiranje zaduženja ni vođenje izvornih evidencija.
* Korisnik može pronaći istu vrstu uplate hijerarhijskim pregledom i pretragom po nazivu, bez duplirane liste (BP-01).
* Nakon odabira vrste uplate podaci se popunjavaju automatski ili ručno prema postojanju integracije, bez izbora načina od strane korisnika (BP-02).
* Prije pokretanja plaćanja prikazuje se pregled i traži izričita potvrda; nakon potvrde i pokretanja podaci transakcije nisu izmjenjivi (BP-03).
* Korisnički tok plaćanja je isti za sve račune javnih prihoda i nezavisan od konkretne gateway implementacije; jedna integracija pokriva više računa (BP-04).
* Nakon završetka plaćanja korisnik vidi odgovarajuću poruku za svaki podržani ishod; transakcija je evidentirana sa statusom iz sistema elektronskog plaćanja (BP-05).
* Za uspješne transakcije korisnik može pregledati i preuzeti potvrdu; potvrda nije službeni finansijski dokument (BP-06).
* Izvori iznosa, primaoca, računa, poziva na broj i svrhe plaćanja određuju se konfiguracijom vrste uplate u skladu sa BP-07.1 do BP-07.5.
* Transakcija se kreira prije gateway-a; statusi i prelazi prate BP-08; ručna izmjena statusa nije dozvoljena; potvrda izvornom sistemu samo uz definisanu integraciju (BP-08).
* Korisnik može pregledati svoju istoriju transakcija (lista, filteri, detalji); administrator u skladu sa ovlašćenjima; bez izmjene i bez brisanja transakcija (BP-09).

---

# Veza sa dokumentacijom

| Dokument | Putanja |
|----------|---------|
| Business Model (BM-002) | `docs/business-model/Business_Model_Placanja.md` |
| Pravni okvir | `docs/pravni-okvir/Pravni_okvir_Placanja.md` |
| Katalog | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` |
| Technical Specification (TS-002) | `docs/technical-specifications/Technical-Specification_Placanja.md` |
| Feature Registry | `docs/features/Feature-Registry.md` |

### Sljedivost BP

| Oznaka | Naziv | BM | FS |
|--------|-------|----|----|
| BP-01 | Pronalaženje vrste uplate | BM-002 / 9.1 | FS-002 / 7.1 |
| BP-02 | Način popunjavanja podataka za plaćanje | BM-002 / 9.2 | FS-002 / 7.2 |
| BP-03 | Pregled i potvrda prije plaćanja | BM-002 / 9.3 | FS-002 / 7.3 |
| BP-04 | Jedinstvena integracija sa sistemom elektronskog plaćanja | BM-002 / 9.4 | FS-002 / 7.4 |
| BP-05 | Obrada ishoda elektronskog plaćanja | BM-002 / 9.5 | FS-002 / 7.5 |
| BP-06 | Potvrda o izvršenom elektronskom plaćanju | BM-002 / 9.6 | FS-002 / 7.6 |
| BP-07 | Izvor obaveznih podataka za elektronsko plaćanje | BM-002 / 9.7 | FS-002 / 7.7 |
| BP-08 | Životni ciklus transakcije | BM-002 / 9.8 | FS-002 / 7.8 |
| BP-09 | Istorija transakcija i pregled plaćanja | BM-002 / 9.9 | FS-002 / 7.9 |

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
| 2026-07-27 | Kreirana početna verzija 0.1. Unesene odluke P-01–P-08 i F-01. Rezervisana poglavlja korisnika, ekrana i detaljnih AC. |
| 2026-07-27 | PATCH-FS-001 / verzija 0.2 — UR-01: referenca na račun iz Kataloga; bez konkretnih brojeva računa u FS; BR-P-010–013. |
| 2026-07-27 | PATCH-FS-002 / verzija 0.3 — BP-01, BP-02, BP-03; BR-P-014–024; sljedivost BP. |
| 2026-07-27 | PATCH-FS-003 / verzija 0.4 — BP-04; BR-P-025–029; korisnički tok nezavisan od računa/gateway-a. |
| 2026-07-27 | PATCH-FS-004 / verzija 0.5 — BP-05; BR-P-030–034; obrada ishoda plaćanja. |
| 2026-07-27 | PATCH-FS-005 / verzija 0.6 — BP-06; BR-P-035–038; potvrda o izvršenom plaćanju. |
| 2026-07-27 | PATCH-FS-006 / verzija 0.7 — BP-07; BR-P-039–049; izvor obaveznih podataka (BP-07.1 do BP-07.5). |
| 2026-07-27 | PATCH-FS-007 / verzija 0.8 — BP-08; BR-P-050–058; životni ciklus transakcije (BP-08.1 do BP-08.5). |
| 2026-07-27 | PATCH-FS-008A / verzija 0.9 — Redakcijsko usklađivanje BP-05/BP-06/BP-08; BR-P-031/036/051/058. |
| 2026-07-27 | PATCH-FS-008B / verzija 0.9.1 — Evidencija: trenutni status transakcije; BR-P-033. |
| 2026-07-27 | PATCH-FS-009 / verzija 1.0 — BP-09; BR-P-059–066; istorija transakcija i pregled plaćanja. |
| 2026-07-27 | PATCH-FS-009A / verzija 1.0.1 — Redakcijsko: BP-06↔BP-09 (istorija); terminologija identifikatora. |
