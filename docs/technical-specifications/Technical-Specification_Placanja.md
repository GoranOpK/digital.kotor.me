# Digital Kotor
# Technical Specification
## Modul: Plaćanja

**Feature ID:** FT-002
**Status dokumenta:** U IZRADI
**Verzija:** 1.0.1

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-27 | Uspostavljena početna struktura Technical Specification. Unesena obavezujuća ograničenja iz P-01–P-08 i F-01. Tehnička rješenja nisu projektovana. |
| 0.2 | 2026-07-27 | UR-01 – Uplatni računi kao konfiguracioni podaci; implementaciona zabrana hardkodiranja; Katalog ≠ šifrarnik; buduća arhitektura izvoda šifrarnika. |
| 0.3 | 2026-07-27 | BP-01/BP-02/BP-03 – Neutralne napomene: jedinstveni izvor vrsta uplata; podrška različitim izvorima podataka; nepromjenjivost potvrđene transakcije. |
| 0.4 | 2026-07-27 | BP-04 – Jedinstvena apstraktna integracija prema payment gateway sloju; konfiguracija odvojena od koda; bez pretpostavki o konkretnoj implementaciji. |
| 0.5 | 2026-07-27 | BP-05 – Prihvatanje i obrada statusa ishoda transakcije iz payment gateway sloja; bez vezivanja za konkretnu implementaciju. |
| 0.6 | 2026-07-27 | BP-06 – Generisanje potvrde nezavisno od konkretnog payment gateway-a; bez formata/dostave/PDF pretpostavki. |
| 0.7 | 2026-07-27 | BP-07 – Izvor obaveznih podataka (iznos, primalac, račun, poziv na broj, svrha) kao konfiguracija vrste uplate; bez implementacionog dizajna. |
| 0.8 | 2026-07-27 | BP-08 – Životni ciklus transakcije (statusi, state machine, audit, potvrda izvornom sistemu); bez implementacionog dizajna. |
| 0.9 | 2026-07-27 | PATCH-008A – Redakcijsko usklađivanje BP-05/BP-06/BP-08: korisnička poruka ≠ status; početni status Kreirana; potvrda ≠ knjiženje. |
| 0.9.1 | 2026-07-27 | PATCH-008B – Redakcijsko usklađivanje: evidencija bilježi trenutni status transakcije. |
| 1.0 | 2026-07-27 | BP-09 – Istorija transakcija i pregled plaćanja (pristup, lista, filteri, detalji, retention); bez implementacionog dizajna. |
| 1.0.1 | 2026-07-27 | PATCH-009A – Redakcijsko: BP-06↔BP-09 (istorija); terminologija identifikatora. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument će predstavljati tehničku specifikaciju modula Plaćanja nakon usvajanja tehničkih odluka.

U verziji 1.0.1 dokument:

* uspostavlja strukturu tehničke specifikacije;
* bilježi obavezujuća projektna ograničenja iz usvojenih odluka;
* definiše usvojeno projektno pravilo o uplatnim računima (UR-01);
* bilježi neutralna tehnička ograničenja iz BP-01 do BP-09;
* **ne** definiše arhitekturu rješenja, bazu podataka, API-je, webhooks, PDF, e-mail, digitalni potpis, fiskalizaciju, konkretan payment gateway ni ostale implementacione detalje koji još nisu usvojeni.

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| TS-002 / 1. Uvod i veza sa dokumentacijom | U IZRADI |
| TS-002 / 2. Obavezujuća projektna ograničenja | USVOJENO |
| TS-002 / 2.4 Uplatni računi – konfiguracioni podaci (UR-01) | USVOJENO |
| TS-002 / 2.5 Ograničenja iz BP-01, BP-02 i BP-03 | USVOJENO |
| TS-002 / 2.6 BP-04 – Jedinstvena integracija sa payment gateway slojem | USVOJENO |
| TS-002 / 2.7 BP-05 – Obrada ishoda transakcije | USVOJENO |
| TS-002 / 2.8 BP-06 – Potvrda o izvršenom plaćanju | USVOJENO |
| TS-002 / 2.9 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje | USVOJENO |
| TS-002 / 2.10 BP-08 – Životni ciklus transakcije | USVOJENO |
| TS-002 / 2.11 BP-09 – Istorija transakcija i pregled plaćanja | USVOJENO |
| TS-002 / 3. Arhitektura rješenja | NIJE USVOJENO |
| TS-002 / 4. Model podataka | NIJE USVOJENO |
| TS-002 / 5. Integracije | NIJE USVOJENO |
| TS-002 / 6. Sigurnost | NIJE USVOJENO |
| TS-002 / 7. Interfejsi i API | NIJE USVOJENO |
| TS-002 / 8. Ne-funkcionalni zahtjevi | NIJE USVOJENO |
| TS-002 / 9. Plan implementacije | NIJE USVOJENO |

---

# Pravila upravljanja Technical Specification

1. Technical Specification pripada modulu Plaćanja (FT-002 / TS-002).

2. Tehnička rješenja unose se isključivo nakon usvojene tehničke ili projektne odluke i evidentiraju kroz PATCH.

3. Cursor ne smije samostalno projektovati bazu podataka, API-je, integracije, arhitekturu ni druga tehnička rješenja.

4. Technical Specification mora ostati usklađena sa Business Modelom (BM-002) i Functional Specification (FS-002).

5. Odluke P-01 do P-08, F-01, UR-01 i BP-01 do BP-09 su obavezujuća ograničenja i ne smiju se mijenjati bez nove projektne odluke.

---

## Sadržaj

1. Uvod i veza sa dokumentacijom
2. Obavezujuća projektna ograničenja
3. Arhitektura rješenja
4. Model podataka
5. Integracije
6. Sigurnost
7. Interfejsi i API
8. Ne-funkcionalni zahtjevi
9. Plan implementacije

---

# TS-002 / 1. Uvod i veza sa dokumentacijom

Modul Plaćanja (FT-002) služi isključivo za elektronsko plaćanje finansijskih obaveza prema Opštini Kotor putem platforme Digital Kotor (P-01).

Tehnička specifikacija razvija se u okviru dokumentacije propisane odlukom P-06:

| # | Dokument | Putanja |
|---|----------|---------|
| 1 | Pravni okvir | `docs/pravni-okvir/Pravni_okvir_Placanja.md` |
| 2 | Katalog finansijskih obaveza | `docs/katalog/Katalog_finansijskih_obaveza_Opstina_Kotor.md` |
| 3 | Business Model | `docs/business-model/Business_Model_Placanja.md` |
| 4 | Functional Specification | `docs/functional-specifications/Functional-Specification_Placanja.md` |
| 5 | Technical Specification | `docs/technical-specifications/Technical-Specification_Placanja.md` |

Sljedivost: FT-002 → BM-002 → FS-002 → TS-002

---

# TS-002 / 2. Obavezujuća projektna ograničenja

**Status:** USVOJENO

Ova ograničenja važe za sva buduća tehnička rješenja. Ne predstavljaju tehnički dizajn.

## 2.1 Poslovne i funkcionalne granice

| Oznaka | Ograničenje |
|--------|-------------|
| P-01 | Modul služi isključivo elektronskom plaćanju finansijskih obaveza prema Opštini Kotor. |
| P-02 | V1 je elektronski kanal za plaćanje obaveza koje se mogu platiti na blagajni. |
| P-03 | Modul ne obračunava obaveze, ne donosi upravna rješenja, ne kreira zaduženja i ne vodi izvorne evidencije. |
| P-04 | Modul ne uvodi nove obaveze niti mijenja postojeće poslovne procese Opštine. |
| P-05 | Svaka funkcionalnost mora imati pravni osnov. |
| P-07 | Pravni osnov se evidentira po propisanim poljima; bez pretpostavljenih podataka. |
| P-08 | Izvorni sistem / nadležni organ ostaje mjerodavan; modul ne mijenja sadržaj tih podataka. |

## 2.2 Obuhvat podataka o vrstama uplata (F-01)

| Ograničenje | Opis |
|-------------|------|
| Jedinica podrške | Pojedinačna vrsta uplate (ne kategorija). |
| Kategorije | Isključivo za logičku organizaciju i prikaz. |
| Minimalni atributi | Kategorija, puni naziv, uplatni račun, interna oznaka/šifra, status primjene, pravni osnov (kada je utvrđen), ciljna grupa (kada je moguće). |
| Izvor liste | Isključivo projektni spisak u Katalogu; bez samostalnog dopunjavanja iz propisa. |

## 2.3 Dokumentacioni preduslov za tehnički dizajn

Prije usvajanja tehničkih rješenja potrebno je:

1. imati popunjen Katalog finansijskih obaveza (konačan spisak vrsta uplata);
2. imati usvojene relevantne dijelove Business Modela i Functional Specification;
3. imati usvojene posebne tehničke odluke za arhitekturu, podatke i integracije.

## 2.4 Uplatni računi – konfiguracioni podaci (UR-01)

**Status:** USVOJENO

### Projektno pravilo

> Uplatni računi predstavljaju konfiguracione podatke (šifrarnik) i ne smiju biti hardkodirani u aplikacionom kodu.

Aplikacija mora koristiti podatke iz konfiguracionog izvora (šifrarnika), a ne vrijednosti ugrađene u programski kod.

### Referentni podaci u dokumentaciji

Brojevi uplatnih računa u **Katalogu finansijskih obaveza** predstavljaju referentne podatke preuzete iz važeće Naredbe o načinu uplate javnih prihoda.

Njihovo navođenje u dokumentaciji služi isključivo za dokumentovanje poslovnih podataka koji važe u trenutku izrade dokumentacije.

To **ne predstavlja hardkodiranje** niti projektovanje implementacije.

### Implementaciono pravilo

Tokom razvoja **nije dozvoljeno**:

* hardkodirati brojeve računa u kontrolerima;
* hardkodirati brojeve računa u servisima;
* hardkodirati brojeve računa u modelima;
* hardkodirati brojeve računa u konfiguracionim klasama koje predstavljaju poslovnu logiku.

Brojevi računa moraju biti učitavani iz konfiguracionog izvora podataka.

### Buduća arhitektura (ogrančenje, ne dizajn)

Katalog finansijskih obaveza predstavlja **poslovni referentni dokument**.

Iz njega će u narednim fazama razvoja biti izveden odgovarajući **šifrarnik** koji će predstavljati izvor podataka za aplikaciju.

**Katalog nije šifrarnik i ne predstavlja implementacioni artefakt.**

Način tehničke realizacije šifrarnika (npr. tabela, seed, admin UI) **nije** predmet ove odluke i usvaja se posebnom tehničkom odlukom.

## 2.5 Ograničenja iz BP-01, BP-02 i BP-03

**Status:** USVOJENO

Ova podpoglavlja bilježe **neutralna** tehnička ograničenja. Ne predstavljaju dizajn integracija, payment gateway-a, protokola ni izvornih sistema.

### BP-01 – Jedinstveni izvor vrsta uplata

Buduća implementacija mora omogućiti pronalaženje vrste uplate hijerarhijskim pregledom i pretragom po nazivu nad **istim** skupom vrsta uplata izvedenim iz Kataloga / šifrarnika.

Ne smije postojati posebna ili duplirana lista vrsta uplata za pretragu.

Konkretni brojevi uplatnih računa ne hardkodiraju se; koristi se referenca na konfiguracioni izvor (UR-01).

### BP-02 – Različiti izvori podataka za plaćanje

Buduća implementacija mora podržati dva načina pribavljanja podataka za plaćanje, u zavisnosti od toga da li za vrstu uplate postoji aktivna integracija:

* automatsko preuzimanje iz izvornog informacionog sistema;
* ručni unos putem obrasca.

Izbor načina određuje sistem; korisnik ne bira način. Dodavanje nove integracije ne smije zahtijevati promjenu osnovnog korisničkog toka.

Konkretne integracije, protokoli i izvorni sistemi **ne** projektuju se ovom napomenom.

### BP-03 – Nepromjenjivost potvrđene transakcije

Nakon izričite potvrde korisnika i pokretanja procesa elektronskog plaćanja, podaci te transakcije više se ne smiju mijenjati.

Potvrda korisnika nije potvrda uspjeha transakcije. Status uspjeha ili neuspjeha utvrđuje se tek kroz kasnije definisan proces izvršenja plaćanja (nije predmet BP-03).

Tehnički način potvrde, autentifikacija payment gateway-a i sadržaj potvrde o izvršenoj transakciji **ne** definišu se ovom napomenom.

## 2.6 BP-04 – Jedinstvena integracija sa payment gateway slojem

**Status:** USVOJENO

### Arhitektonski zahtjev

Aplikacija koristi **jednu apstraktnu integraciju** prema payment gateway sloju.

Broj računa javnih prihoda ne smije uslovljavati razvoj posebne gateway integracije za svaki pojedinačni račun.

Za svaku transakciju sistem, na osnovu odabrane vrste uplate i odgovarajuće konfiguracije, određuje na koji račun javnog prihoda se sredstva usmjeravaju.

### Konfiguracija odvojena od koda

Konfiguracija gateway-a, računa, merchant profila, terminala i drugih parametara integracije mora biti **izdvojena** iz poslovne logike i aplikacionog koda (usklađeno sa UR-01).

Brojevi računa nisu dio poslovne logike.

### Arhitektonski principi

* Payment gateway predstavlja infrastrukturnu komponentu, a ne poslovnu logiku.
* Poslovni proces plaćanja mora biti nezavisan od konkretne banke ili pružaoca usluge elektronskog plaćanja.
* Modul mora omogućiti zamjenu payment gateway-a bez izmjene poslovnog toka korisnika.
* Dodavanje nove vrste uplate ili promjena računa ne smije zahtijevati razvoj nove gateway integracije.

### Veze

* **P-03** — Modul ne vodi izvorne finansijske evidencije.
* **P-08** — Izvorni sistemi ostaju mjerodavni.
* **UR-01** — Konfiguracioni podaci ne smiju biti hardkodirani.

### Ograničenje

Ova odluka **ne** projektuje konkretnu implementaciju i **ne** pretpostavlja: jednog ili više merchant-a, marketplace / master-sub model, terminal ID, MID, određenu banku, određeni payment gateway, određeni API ni tehnologiju. Sve navedeno ostaje predmet buduće tehničke analize i ugovaranja.

## 2.7 BP-05 – Obrada ishoda transakcije

**Status:** USVOJENO

### Arhitektonski zahtjev

Modul mora biti sposoban da **prihvati i obradi status transakcije** koji vraća payment gateway sloj, bez vezivanja za konkretnu implementaciju, banku, API, webhook ili callback mehanizam.

Modul ne donosi odluku o uspješnosti transakcije; koristi status koji vrati sistem elektronskog plaćanja.

### Minimalni ishodi koje treba podržati

* Plaćanje uspješno izvršeno.
* Plaćanje nije izvršeno.
* Plaćanje otkazano od strane korisnika.
* Status transakcije trenutno nije moguće potvrditi.

Napomena (usklađenje sa BP-08):

Poruka „Status trenutno nije moguće potvrditi“ predstavlja korisničku informaciju i **ne** uvodi novi status transakcije. Dok se čeka konačna potvrda od payment gateway-a, transakcija zadržava status **U toku**.

Interni statusi: Kreirana, U toku, Uspješna, Neuspješna, Otkazana (BP-08).

### Evidencija (arhitektonsko ograničenje)

Buduća implementacija mora omogućiti evidenciju najmanje: jedinstvenog identifikatora transakcije (interni identifikator koji vodi modul), vrste uplate, vremena pokretanja, vremena završetka (kada je poznato), trenutnog statusa transakcije (statusa u trenutku evidentiranja) i identifikatora payment gateway-a (kada postoji).

Napomena (terminologija): Dokumentacija razlikuje jedinstveni identifikator transakcije, referentni broj transakcije (prikaz korisniku) i identifikator payment gateway-a. Odnos jedinstvenog identifikatora i referentnog broja nije propisan i ostaje implementaciona odluka.

Struktura baze podataka i tehnički model evidencije **ne** projektuju se ovom odlukom.

### Veze

* **BP-03** — Potvrda prije pokretanja nije potvrda uspjeha.
* **BP-04** — Ishod se prima kroz jedinstvenu apstraktnu integraciju sa payment gateway slojem.
* **BP-08** — Interni statusi i prelazi; korisničke poruke o ishodu nisu novi statusi.

### Ograničenje

Odluka **ne** opisuje API-je, webhooks, callback mehanizme, konkretan payment gateway, banku ni shemu baze.

## 2.8 BP-06 – Potvrda o izvršenom plaćanju

**Status:** USVOJENO

### Arhitektonski zahtjev

Sistem mora biti sposoban da **generiše potvrdu** o ishodu elektronskog plaćanja **nezavisno od konkretnog payment gateway-a**.

Potvrda se zasniva na evidentiranom ishodu transakcije (BP-05) i mora biti dostupna za pregled i preuzimanje kada je transakcija uspješno izvršena.

### Ograničenja sadržaja i dostupnosti

* Potvrda o uspješnom plaćanju generiše se isključivo za transakcije sa statusom **Uspješna**.
* Za statuse **Otkazana** i **Neuspješna**, te za status **U toku** (informativna poruka da status plaćanja trenutno nije moguće potvrditi), sistem prikazuje informaciju, ali ne generiše potvrdu o uspješnom plaćanju.
* Potvrda ne smije sadržati povjerljive podatke platne kartice.
* Broj računa na potvrdi dolazi iz konfiguracionog izvora (UR-01), ne iz hardkodiranih vrijednosti.
* Terminologija identifikatora: jedinstveni identifikator transakcije (interni), referentni broj (prikaz korisniku), identifikator payment gateway-a (eksterni); odnos prva dva nije propisan.

### Veze

* **BP-05** — Obrada ishoda elektronskog plaćanja.
* **BP-09** — Istorija transakcija.

### Ograničenje

Odluka **ne** definiše format datoteke, tehnologiju generisanja, način dostave, PDF, e-mail, digitalni potpis, fiskalizaciju, elektronski pečat, arhiviranje ni API-je.

## 2.9 BP-07 – Izvor obaveznih podataka za elektronsko plaćanje

**Status:** USVOJENO

### Arhitektonski zahtjev

Izvori obaveznih podataka za elektronsko plaćanje (iznos, primalac, račun za uplatu, poziv na broj, svrha plaćanja) moraju biti određeni **konfiguracijom vrste uplate**, a ne hardkodirani u aplikacionom kodu.

### Ograničenja po pododlukama

#### BP-07.1 – Izvor iznosa

Implementacija mora podržati konfiguracione modele: fiksni iznos; iznos iz izvornog informacionog sistema; ručni unos; predloženi iznos izmjenjiv samo ako je dozvoljeno konfiguracijom. Modul ne obračunava iznos.

#### BP-07.2 – Izvor primaoca

Implementacija mora podržati konfiguracione modele: fiksni primalac; primalac iz izvornog informacionog sistema. Korisnik ne smije mijenjati primaoca. Modul ne određuje primaoca.

#### BP-07.3 – Izvor računa za uplatu

Broj računa dolazi iz konfiguracionog šifrarnika izvedenog iz Kataloga (UR-01). Nije dio poslovne logike niti aplikacionog koda. Korisnik ne smije mijenjati broj računa.

#### BP-07.4 – Izvor poziva na broj

Implementacija mora podržati konfiguracione modele: bez poziva na broj; fiksna vrijednost; vrijednost iz izvornog sistema; ručni unos. Modul ne generiše poziv na broj osim ako to bude definisano posebnom poslovnom odlukom.

#### BP-07.5 – Izvor svrhe plaćanja

Implementacija mora podržati konfiguracione modele: bez svrhe; fiksna svrha; svrha iz izvornog sistema; ručni unos. Modul ne generiše svrhu plaćanja osim ako to bude definisano posebnom poslovnom odlukom.

### Veze

* **BP-02** — Način popunjavanja podataka.
* **UR-01** — Računi iz konfiguracionog šifrarnika.
* **BP-04** — Brojevi računa nisu dio poslovne logike.

### Ograničenje

Odluka **ne** projektuje shemu konfiguracije, API-je izvornih sistema, pravila validacije ni UI kontrole.

## 2.10 BP-08 – Životni ciklus transakcije

**Status:** USVOJENO

### Arhitektonski zahtjev

Implementacija mora podržati životni ciklus transakcije kao mašinu stanja sa unaprijed definisanim statusima i prelazima, bez ručne izmjene statusa od strane administratora ili korisnika.

### Ograničenja po pododlukama

#### BP-08.1 – Evidentiranje transakcije

Zapis o transakciji mora se kreirati **prije** preusmjeravanja korisnika na payment gateway. Početni status svake novoformirane transakcije je **Kreirana**. Svaka transakcija mora imati **jedinstveni identifikator transakcije** (interni identifikator koji vodi modul) koji se koristi za povezivanje povratnih informacija. Čuvaju se svi započeti zapisi. Odnos jedinstvenog identifikatora i referentnog broja (prikaz korisniku) nije propisan.

#### BP-08.2 – Statusi transakcije

Podržani statusi: Kreirana, U toku, Uspješna, Neuspješna, Otkazana. Transakcija ima tačno jedan status. Konačni statusi: Uspješna, Neuspješna, Otkazana.

#### BP-08.3 – Promjena statusa transakcije

Status mijenja isključivo sistem na osnovu verifikovanog događaja (gateway ili interni proces). Ručna izmjena nije dozvoljena. Svaka promjena se evidentiše u audit zapisu (vrijeme, izvor događaja).

#### BP-08.4 – Dozvoljeni prelazi

Dozvoljeni prelazi: Kreirana → U toku; U toku → Uspješna; U toku → Neuspješna; U toku → Otkazana. Ostali prelazi se odbijaju. Nakon konačnog stanja nema daljih promjena statusa.

#### BP-08.5 – Potvrda izvornom informacionom sistemu

Nakon uspjeha: status Uspješna; ako postoji integracija za vrstu uplate, dostavlja se potvrda izvornom sistemu; neuspjeh isporuke ne mijenja status Uspješna već se bilježi kao poseban sistemski događaj. Modul ne preuzima poslovnu logiku izvornog sistema.

Dostavljanje potvrde izvornom informacionom sistemu predstavlja razmjenu informacija o uspješno izvršenom plaćanju i **ne** predstavlja finansijsko knjiženje niti potvrdu knjiženja u izvornom informacionom sistemu.

### Veze

* **BP-03** — Kreiranje nakon potvrde korisnika.
* **BP-04** — Jedinstvena gateway integracija.
* **BP-05** — Obrada ishoda.
* **BP-06** — Potvrda korisniku.
* **P-08** — Izvorni sistem ostaje mjerodavan.

### Ograničenje

Odluka **ne** projektuje shemu baze, API-je, webhooks, callback mehanizme, format audit zapisa ni protokole prema izvornim sistemima.

## 2.11 BP-09 – Istorija transakcija i pregled plaćanja

**Status:** USVOJENO

### Arhitektonski zahtjev

Implementacija mora omogućiti istoriju transakcija i pregled plaćanja u skladu sa pravima pristupa, bez mogućnosti izmjene ili brisanja transakcija kroz funkcionalnosti modula.

### Ograničenja po pododlukama

#### BP-09.1 – Pravo pristupa

Pristup se ograničava ulogom: korisnik vidi samo svoje transakcije; administrator platforme može vidjeti sve u skladu sa ovlašćenjima. Administrativni pregled se evidentiše u audit logu.

#### BP-09.2 – Lista transakcija

Lista prikazuje definisana polja (datum/vrijeme, vrsta uplate, iznos, status, **referentni broj transakcije**, primalac, poziv na broj kada postoji, način plaćanja ako gateway dostavlja, akcija detalja). Osjetljivi podaci o sredstvu plaćanja se ne prikazuju.

#### BP-09.3 – Pretraga i filtriranje

Podržani kriterijumi: period, status, vrsta uplate, referentni broj, primalac, raspon iznosa, tekstualna pretraga. Filteri se kombinuju; rezultati poštuju prava pristupa.

#### BP-09.4 – Detaljan pregled

Detalji su informativni (bez izmjene). Broj računa dolazi iz konfiguracionog izvora (UR-01). Prikazuje se i status dostave potvrde izvornom sistemu kada postoji integracija, te **identifikator payment gateway-a** kada postoji.

#### BP-09.5 – Retention i arhiviranje

Transakcije se trajno čuvaju; brisanje nije dozvoljeno. Arhiviranje (konfiguracija / propisi) ne mijenja sadržaj ni identitet i zadržava dostupnost za pregled/pretragu.

### Veze

* **BP-06** — Potvrda i istorija.
* **BP-08** — Statusi transakcije.
* **UR-01** — Računi iz konfiguracije.

### Ograničenje

Odluka **ne** projektuje UI, paginaciju, tehnologiju arhiviranja, shemu baze ni API-je.

---

# TS-002 / 3. Arhitektura rješenja

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. Arhitektura se ne projektuje u ovoj fazi.

Obavezujuća ograničenja:

* UR-01 — uplatni računi iz konfiguracionog izvora (šifrarnika), ne hardkod.
* BP-04 — jedna apstraktna integracija prema payment gateway sloju; konfiguracija odvojena od koda; bez izbora konkretnog pružaoca u ovoj fazi.
* BP-05 — sposobnost prihvatanja i obrade statusa ishoda transakcije iz payment gateway sloja, bez vezivanja za konkretnu implementaciju.
* BP-06 — sposobnost generisanja potvrde nezavisno od konkretnog payment gateway-a; bez definisanja formata/dostave.
* BP-07 — izvori obaveznih podataka (iznos, primalac, račun, poziv na broj, svrha) kao konfiguracija vrste uplate.
* BP-08 — životni ciklus transakcije kao state machine; audit promjena statusa; opcioni outbound potvrde izvornom sistemu.
* BP-09 — istorija transakcija (pristup po ulozi, lista, filteri, detalji, retention) bez brisanja i bez izmjene podataka.

---

# TS-002 / 4. Model podataka

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. Model podataka, tabele i migracije se ne projektuju u ovoj fazi.

Obavezujuće ograničenje (UR-01): kada model bude usvajan, uplatni računi moraju biti modelovani kao konfiguracioni podaci šifrarnika izvedenog iz Kataloga, a ne kao hardkodirane konstante u kodu. Katalog ostaje poslovni referentni dokument, ne implementacioni artefakt.

---

# TS-002 / 5. Integracije

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. Integracije sa payment gateway-em, izvornim sistemima Opštine ili drugim servisima se ne projektuju u ovoj fazi.

Napomena (P-08, BP-02, BP-04, BP-07, BP-08):

* Izvorni sistem / nadležni organ ostaje mjerodavan za podatke o obavezi.
* Podrška za automatsko i ručno pribavljanje podataka ostaje u istom osnovnom korisničkom toku (BP-02).
* Izvori iznosa, primaoca, računa, poziva na broj i svrhe određuju se konfiguracijom vrste uplate (BP-07).
* Prema payment gateway sloju koristi se jedna apstraktna integracija; konfiguracija je odvojena od koda (BP-04, UR-01).
* Modul mora moći prihvatiti i obraditi status ishoda transakcije iz payment gateway sloja, bez vezivanja za konkretnu implementaciju (BP-05).
* Životni ciklus transakcije, statusi i prelazi uređeni su BP-08; eventualna potvrda izvornom sistemu šalje se samo uz definisanu integraciju (BP-08.5).
* Konkretan gateway, banka, merchant model, API, webhooks, callback mehanizmi i protokoli **ne** biraju se ovom specifikacijom.

---

# TS-002 / 6. Sigurnost

**Status:** NIJE USVOJENO

Poglavlje je rezervisano.

---

# TS-002 / 7. Interfejsi i API

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. API-ji, rute i interfejsi se ne projektuju u ovoj fazi.

---

# TS-002 / 8. Ne-funkcionalni zahtjevi

**Status:** NIJE USVOJENO

Poglavlje je rezervisano.

---

# TS-002 / 9. Plan implementacije

**Status:** NIJE USVOJENO

Poglavlje je rezervisano. Implementacija aplikacionog koda nije predmet ove faze dokumentacije, ali implementaciono pravilo UR-01 važi čim razvoj započne.

---

# Change Log

| Datum | Izmjena |
|-------|---------|
| 2026-07-27 | Kreirana početna verzija 0.1. Unesena ograničenja P-01–P-08 i F-01. Tehnička poglavlja ostavljena kao NIJE USVOJENO. |
| 2026-07-27 | Verzija 0.2 — UR-01: uplatni računi = konfiguracioni podaci; zabrana hardkodiranja; Katalog ≠ šifrarnik. |
| 2026-07-27 | Verzija 0.3 — BP-01/BP-02/BP-03: jedinstveni izvor vrsta uplata; različiti izvori podataka; nepromjenjivost potvrđene transakcije. |
| 2026-07-27 | Verzija 0.4 — BP-04: jedna apstraktna gateway integracija; konfiguracija odvojena od koda; bez pretpostavki o konkretnoj implementaciji. |
| 2026-07-27 | Verzija 0.5 — BP-05: prihvatanje i obrada statusa ishoda transakcije; bez API/webhook/DB pretpostavki. |
| 2026-07-27 | Verzija 0.6 — BP-06: generisanje potvrde nezavisno od gateway-a; bez PDF/e-mail/formata. |
| 2026-07-27 | Verzija 0.7 — BP-07: izvor obaveznih podataka (BP-07.1 do BP-07.5) kao konfiguracija vrste uplate. |
| 2026-07-27 | Verzija 0.8 — BP-08: životni ciklus transakcije (BP-08.1 do BP-08.5); state machine; audit; potvrda izvornom sistemu. |
| 2026-07-27 | Verzija 0.9 — PATCH-008A: redakcijsko usklađivanje BP-05/BP-06/BP-08. |
| 2026-07-27 | Verzija 0.9.1 — PATCH-008B: evidencija bilježi trenutni status transakcije. |
| 2026-07-27 | Verzija 1.0 — BP-09: istorija transakcija i pregled plaćanja (BP-09.1 do BP-09.5). |
| 2026-07-27 | Verzija 1.0.1 — PATCH-009A: BP-06↔BP-09 (istorija); terminologija identifikatora. |
