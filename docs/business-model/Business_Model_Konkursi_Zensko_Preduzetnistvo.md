# Digital Kotor
# Poslovni profil: Konkurs za podršku ženskom preduzetništvu
## Modul: Konkursi

**Oznaka dokumenta:** KN-BM-003
**Naziv:** Poslovni profil: Konkurs za podršku ženskom preduzetništvu
**Modul:** Konkursi
**Namespace:** KN
**Tip konkursa:** Žensko preduzetništvo
**Status dokumenta:** USVOJEN
**Verzija:** 1.0.2
**Datum:** 2026-08-26

Povezani dokumenti:

* Registar oznaka: **KN-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md`
* Zajednički poslovni model modula Konkursi: **KN-BM-001** — `docs/business-model/Business_Model_Konkursi.md`
* Zajedničke funkcionalnosti modula Konkursi: **KN-FS-001** — `docs/functional-specifications/Functional-Specification_Konkursi.md` (planiran; fajl nije kreiran)
* Zajednička tehnička specifikacija modula Konkursi: **KN-TS-001** — `docs/technical-specifications/Technical-Specification_Konkursi.md` (planiran; fajl nije kreiran)

Ovaj dokument **ne** mijenja `KN-BM-001`. Zajednička pravila se ne ponavljaju osim koliko je potrebno da profil bude samodovoljan (`BM-KN-008`).

Ovaj dokument **ne** tvrdi da je opisano ponašanje već implementirano na Platformi. Implementacija se usklađuje sa ovim profilom kroz FS/TS, a ne obrnuto (`BM-KN-012`).

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 1.0.0 | 2026-08-26 | Usvojen poslovni profil Konkursa za podršku ženskom preduzetništvu prema Odluci 027/26 i završenom poslovnom modelovanju. Evidentirana dva otvorena pravna pitanja. |
| 1.0.1 / KN-PATCH-BM-001 | 2026-08-26 | Zatvorena OPEN LEGAL ISSUE #1 i OPEN LEGAL ISSUE #2. Usvojeno: (1) pri preklapanju 20% sa 10% ili 5% primjenjuje se maksimalna granica od 20%; procenti se ne sabiraju; 20% nije automatski dodijeljeni iznos; (2) Komisija može raspisati drugi Javni konkurs; raspisivanje nije automatsko; ako Komisija odluči da raspiše drugi Konkurs, mora biti raspisan najkasnije do isteka trećeg kvartala tekuće godine. |
| 1.0.2 / KN-PATCH-BM-002 | 2026-08-26 | Usklađenje sa Odlukom 027/26 i usvojenim FS tokom: (1) U pripremi / Podnesena / Povučena — Podnesena nije izmjenjiva; (2) eliminatorni kriterijum 1 iz čl. 19 obrađuje se kroz provjeru potpunosti i Prigovor iz čl. 17; (3) Obrazac 3 i nacrt ocjena od prve sjednice; kriterijum 10 tek nakon usmenog; (4) nepromjenjivost tek nakon završavanja individualnog ocjenjivanja, ne pri čuvanju nacrta. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Dokument ima status `USVOJEN`. Nakon formalnog usvajanja, kontrolisane izmjene označavaju se prema `KN-PATCH-BM-{NNN}` i evidentiraju se u `KN-RG-001` tek pri prvoj stvarnoj upotrebi.

Formalna otvorena pravna pitanja OPEN LEGAL ISSUE #1 i OPEN LEGAL ISSUE #2 zatvorena su poslovnom odlukom u verziji 1.0.1 / `KN-PATCH-BM-001`. Kanonska pravila su u Poglavlju 13.

---

## Svrha dokumenta

Dokument je kanonski poslovni profil tipa konkursa **Žensko preduzetništvo**. Definiše poslovna pravila tog tipa koja utiču na ponašanje Platforme. Nije Functional Specification i nije Technical Specification.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| 1. Svrha i granice profila | USVOJENO |
| 2. Normativni osnov | USVOJENO |
| 3. Odnos prema KN-BM-001 | USVOJENO |
| 4. Akteri i odgovornosti | USVOJENO |
| 5. Kreiranje Konkursa | USVOJENO |
| 6. Objavljivanje i rok za Prijave | USVOJENO |
| 7. Podnositeljka i Prijava | USVOJENO |
| 8. Istek roka i pristup Komisije | USVOJENO |
| 9. Prva sjednica / administrativna provjera / Prigovor | USVOJENO |
| 10. Eliminatorni kriterijumi | USVOJENO |
| 11. Druga sjednica i usmeno obrazloženje | USVOJENO |
| 12. Ocjenjivanje | USVOJENO |
| 13. Preliminarna i konačna rang-lista | USVOJENO |
| 14. Predlog Odluke i završetak rada Komisije | USVOJENO |
| 15. Konačna Odluka, objava i granica V1 | USVOJENO |

Normativna praznina o sudbini završenih individualnih ocjena pri zamjeni člana Komisije dokumentovana je u Poglavlju 12. Ne blokira status `USVOJEN`.

---

# Pravila upravljanja dokumentom

1. KN-BM-003 je izvor istine za poslovna pravila tipa konkursa Žensko preduzetništvo.
2. Zajednička apstraktna pravila ostaju u `KN-BM-001`.
3. Primarni normativni izvor je Odluka 027/26. Starija Odluka 011/24 i nacrt iz javne rasprave nijesu autoritet ovog profila.
4. Otvoreno pravno pitanje se ne zatvara pretpostavkom (`BM-KN-009`).
5. Postojeća implementacija nije izvor poslovnog pravila.
6. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno rješavati OPEN pravna pitanja.

---

## Sadržaj

1. Svrha i granice profila
2. Normativni osnov
3. Odnos prema KN-BM-001
4. Akteri i odgovornosti
5. Kreiranje Konkursa
6. Objavljivanje i rok za Prijave
7. Podnositeljka i Prijava
8. Istek roka i pristup Komisije
9. Prva sjednica / administrativna provjera / Prigovor
10. Eliminatorni kriterijumi
11. Druga sjednica i usmeno obrazloženje
12. Ocjenjivanje
13. Preliminarna i konačna rang-lista
14. Predlog Odluke i završetak rada Komisije
15. Konačna Odluka, objava i granica V1

---

# 1. Svrha i granice profila

Status poglavlja: USVOJENO

Ovaj profil opisuje tip konkursa **Žensko preduzetništvo**: raspodjelu bespovratnih sredstava iz Budžeta Opštine Kotor namijenjenih za podršku ženskom preduzetništvu, putem Javnog konkursa na Platformi digital.kotor.me.

Profil određuje poslovna pravila koja utiču na:

* kreiranje i objavljivanje Konkursa;
* podnošenje, izmjenu, povlačenje i brisanje Prijave;
* rad Komisije;
* ocjenjivanje, rangiranje i predlaganje Odluke;
* zatvaranje i arhiviranje Konkursa;
* objavljivanje konačne Odluke na Platformi.

Profil **ne** određuje:

* konkretan godišnji budžet jedne instance;
* konkretne datume jednog Konkursa, osim pravila računanja rokova;
* tehničku realizaciju;
* da je opisano ponašanje već implementirano.

V1 granica ovog profila na Platformi je objavljivanje konačne Odluke. Van Platforme u V1 ostaju radnje navedene u Poglavlju 15.

---

# 2. Normativni osnov

Status poglavlja: USVOJENO

Primarni i jedini autoritativni normativni izvor ovog profila je:

**Odluka o podršci ženskom preduzetništvu**, „Sl. list CG — opštinski propisi“, br. **027/26** od 22.06.2026. (Skupština Opštine Kotor, V sjednica 17.06.2026.).

Nijesu normativni autoritet za ovaj profil:

* Odluka 011/24;
* nacrt iz javne rasprave;
* postojeći aplikacioni kod;
* `docs/UPUTSTVO_ZENSKO_PREDUZETNISTVO.md` kao zamjena za Odluku.

Gdje je pravni osnov već potvrđen, profil navodi član Odluke 027/26. Gdje Odluka ne daje jednoznačan odgovor, pitanje se evidentira kao OPEN i ne zatvara se tumačenjem.

---

# 3. Odnos prema KN-BM-001

Status poglavlja: USVOJENO

`KN-BM-001` je zajednički apstraktni poslovni model modula Konkursi.

Ovaj profil:

* pripada tačno jednom tipu konkursa — Žensko preduzetništvo;
* koristi zajedničke aktere, sposobnosti i pojmove iz `KN-BM-001`;
* određuje koje opcione sposobnosti koristi (`BM-KN-004`, `BM-KN-005`): prijavu, ocjenjivanje, rangiranje, predlog Odluke, konačnu Odluku i arhiviranje.

Ovaj profil **ne** uvodi Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj kao petog zajedničkog KN aktera. Njegove pravne nadležnosti opisane su u ovom profilu (`KN-BM-001` §17.3).

Zajednička nespojivost `BM-KN-014` ostaje na snazi: Administrator Konkursa ne može biti član Komisije.

---

# 4. Akteri i odgovornosti

Status poglavlja: USVOJENO

Zajednički model od četiri apstraktna aktera ostaje:

1. Podnosilac — u ovom profilu: **Podnositeljka**;
2. Administrator Konkursa;
3. Komisija;
4. Administrator platforme.

## 4.1. Podnositeljka

Podnositeljka je žensko lice koje učestvuje na Konkursu podnošenjem Prijave, u skladu sa čl. 3 i čl. 4 Odluke 027/26:

* preduzetnica sa prebivalištem na teritoriji opštine Kotor; ili
* fizičko lice koje tek planira registraciju djelatnosti, pod uslovima Odluke; ili
* nositeljka biznisa u privrednom društvu (osnivačica ili jedna od osnivača i izvršna direktorica) čije sjedište je na teritoriji opštine Kotor.

Podnositeljka na Platformi:

* **U pripremi**, samo dok rok traje: uređuje, briše i podnosi sopstvenu Prijavu;
* **Podnesena**: sadržaj je zaključan; ne uređuje se i ne briše se; može se povući samo do isteka roka;
* **Povučena**: ne nastavlja postupak; nema reaktivacije;
* ima pristup samo sopstvenim Prijavama; javna rang-lista, konačna Odluka i drugi javno objavljeni rezultati Konkursa zaseban su javni sadržaj, a nijesu kompletna Prijava.

## 4.2. Administrator Konkursa

Administrator Konkursa upravlja konkursnim administrativnim radnjama u okviru svojih ovlašćenja, uključujući kreiranje i objavljivanje Konkursa i unos zavodnog broja sa pisarnice.

Administrator Konkursa **ne može** biti član Komisije (`BM-KN-014`).

Administrator Konkursa **nema** ovlašćenje da:

* ocjenjuje biznis planove;
* utvrđuje rang-listu;
* generiše ili predlaže predlog Odluke u ime Komisije;
* donosi konačnu Odluku.

## 4.3. Komisija

Komisija je kolektivni poslovni akter. Sastav je prema čl. 6 Odluke 027/26:

* pet članova, od kojih je jedan predsjednik Komisije;
* 3 člana — predstavnici Opštine Kotor (1 član — predsjednik Komisije; 2 člana iz redova zaposlenih u Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj, od kojih je jedan sekretar Komisije);
* 1 član — predstavnica Udruženja preduzetnica Crne Gore ili strukovnih udruženja, ili biznisa, ili akademske zajednice;
* 1 član — predstavnica Ženske političke mreže.

Komisiju imenuje sekretar Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj Rješenjem. Mandat Komisije je godinu dana (čl. 6).

Komisija je nadležna za raspisivanje Konkursa, pregled i ocjenu validnosti dokumentacije, sprovođenje usmenog obrazloženja, ocjenjivanje, formiranje konačne rang-liste i pripremu predloga Odluke (čl. 6).

Kvorum za rad je prisustvo većine od ukupnog broja članova. Prilikom sprovođenja intervjua i donošenja punovažnih odluka obavezno je prisustvo svih članova Komisije (čl. 6).

Predsjednik Komisije ima istu težinu kao ostali članovi pri individualnom ocjenjivanju. Pored toga, na Platformi u ime Komisije evidentira:

* rezultat administrativne provjere dokumentacije;
* dodatne bodove;
* zaključke, iznose i obrazloženja sa treće sjednice;
* tehničku radnju generisanja predloga Odluke i predlaganja Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj;
* zatvaranje Konkursa nakon predlaganja.

Predsjednik **nije** samostalni donosilac predloga niti konačne Odluke.

## 4.4. Administrator platforme

Administrator platforme ostaje platformski akter prema `KN-BM-001`. Samim postojanjem te uloge ne postaje Administrator Konkursa niti član Komisije.

## 4.5. Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj

Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj **nije** novi zajednički KN akter.

U ovom profilu:

* sekretar Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj imenuje Komisiju i, prema čl. 6 i čl. 10, zamjenskog odnosno novog člana;
* Komisija predlog Odluke predlaže Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj;
* Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj donosi i objavljuje konačnu Odluku (čl. 22–24);
* pojedinačna Rješenja, ugovori i praćenje realizacije pripadaju Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj i u V1 ostaju van Platforme, osim gdje ovaj profil izričito drugačije odredi.

Puni naziv se koristi svuda u ovom dokumentu. Ne uvodi se ad hoc skraćenica.

## 4.6. Izjave Komisije

Članovi Komisije potpisuju Izjavu o tajnosti podataka i Izjavu o sprečavanju sukoba interesa (čl. 6).

Te izjave se **fizički potpisuju van Platforme**. Profil ne uvodi elektronsko potpisivanje tih izjava.

Na Platformi se, ako se uopšte vodi, evidentira samo činjenica da su izjave potpisane. Sadržaj izjava ostaje van Platforme.

---

# 5. Kreiranje Konkursa

Status poglavlja: USVOJENO

Administrator Konkursa kreira Konkurs na Platformi.

Obavezni poslovni sadržaj forme/profila Konkursa:

* naziv Konkursa;
* opis;
* tip Konkursa = Žensko preduzetništvo;
* godina;
* broj Konkursa;
* ukupan budžet;
* datum početka;
* Komisija;
* informacije o roku.

**Broj Konkursa nije automatski redni broj.** Predstavlja zavodni broj koji Administrator Konkursa dobija sa pisarnice i ručno unosi na Platformi.

Prvi Konkurs se raspisuje u drugom kvartalu tekuće godine (čl. 4).

Konkurs mora sadržati podatke propisane čl. 4 Odluke 027/26, uključujući:

* ukupan iznos sredstava koja se raspodjeljuju;
* najviši iznos sredstava koji se može dodijeliti za finansiranje svake pojedinačne biznis ideje;
* uslove za podnošenje Prijave;
* podatke o dokumentaciji;
* kriterijume za ocjenjivanje;
* rok i način podnošenja Prijave i prateće dokumentacije;
* informacije o informativnim sastancima tokom trajanja Konkursa;
* druge podatke od značaja za sprovođenje Konkursa.

Ukupan iznos sredstava koja se raspodjeljuju definiše se Javnim konkursom (čl. 3).

Drugi Konkurs **nije** automatski. Komisija **može raspisati** drugi Javni konkurs. Ako Komisija odluči da ga raspiše, drugi Konkurs mora biti raspisan najkasnije do isteka trećeg kvartala tekuće godine. Kanonsko pravilo: Poglavlje 13.8.

---

# 6. Objavljivanje i rok za Prijave

Status poglavlja: USVOJENO

Konkurs se objavljuje u jednom dnevnom listu, na vebsajtu Opštine Kotor, na digitalnom servisu Opštine Kotor, putem lokalnog javnog emitera i na oglasnoj tabli Opštine (čl. 5).

Konkurs za raspodjelu sredstava je otvoren **20 dana** od dana njegovog objavljivanja (čl. 5 i čl. 13).

Objavljivanjem na Platformi počinje rok za prijavljivanje.

## 6.1. Računanje roka

Rok se računa u kalendarskim danima. Krajnji dan traje do kraja tog kalendarskog dana (23:59:59). Rok **nije** 20 × 24 sata od časa objave.

Primjer:

Ako je Konkurs objavljen 01.01.2027. u 09:00 i rok je 20 dana, krajnji rok je **21.01.2027. u 23:59:59**.

---

# 7. Podnositeljka i Prijava

Status poglavlja: USVOJENO

Prijava i prateća dokumentacija podnose se Komisiji **elektronski** preko digitalnog servisa Opštine Kotor — digital.kotor.me (čl. 13).

Jedno fizičko lice / preduzetnica / društvo može konkurisati sa **jednim biznis planom** po Javnom konkursu (čl. 18).

Osnovna poslovna stanja Prijave:

**U pripremi** — samo dok rok za prijavu traje:

* uređivanje: DA;
* brisanje: DA;
* podnošenje: DA.

**Podnesena** nastaje eksplicitnim podnošenjem. Nakon podnošenja sadržaj je zaključan.

* uređivanje: NE;
* brisanje: NE;
* povlačenje: DA, samo dok rok traje.

**Povučena** više ne učestvuje u postupku.

* uređivanje: NE;
* reaktivacija: NE;
* povratak u Podnesena: NE.

Istek roka **nije** dodatno stanje Prijave. Nakon isteka roka nema uređivanja, podnošenja, brisanja ni povlačenja.

## 7.1. Privatnost Prijava

Podnositeljka nema pravo pristupa kompletnoj Prijavi druge Podnositeljke.

Ovo ograničenje važi tokom aktivnog Konkursa, evaluacionog toka, nakon zaključenja i nakon arhiviranja.

Zaključenje ili arhiviranje Konkursa samo po sebi ne otvara pristup tuđim Prijavama.

Javna rang-lista, konačna Odluka i drugi javno objavljeni rezultati Konkursa nijesu isto što i kompletna Prijava.

Objavljivanje javnog rezultata ne daje pravo pristupa:

* obrascu Prijave;
* biznis planu;
* priloženoj dokumentaciji;
* drugim nejavnim djelovima Prijave i prateće dokumentacije druge Podnositeljke.

---

# 8. Istek roka i pristup Komisije

Status poglavlja: USVOJENO

Nijedan član Komisije, uključujući predsjednika Komisije, ne može pristupiti podnesenim Prijavama **prije isteka roka** za prijavljivanje.

Zabrana obuhvata:

* sadržaj Prijave;
* biznis plan;
* priloženu dokumentaciju.

Pristup Komisiji počinje tek nakon isteka roka.

Ovo pravilo pripada ovoj fazi. Ne ponavlja se u Poglavlju 12.

---

# 9. Prva sjednica / administrativna provjera / Prigovor

Status poglavlja: USVOJENO

Komisija zakazuje prvu sjednicu u roku od najkasnije **7 dana** od isteka roka za prijavu na Konkurs (čl. 17).

Na prvoj sjednici Komisija pregleda elektronski zaprimljene Prijave.

Predsjednik Komisije na Platformi, u ime Komisije, evidentira da li Podnositeljka ima svu potrebnu dokumentaciju.

## 9.1. Nepotpuna Prijava

Ako Komisija utvrdi da je Prijava nepotpuna, ista se označava kao takva u listi za ocjenjivanje iz čl. 19 i Komisija je **neće dalje razmatrati** (čl. 17).

Čl. 17 propisuje funkcionalni postupak za nepotpunu dokumentaciju. Čl. 19 tu istu nepotpunu dokumentaciju navodi kao eliminatorni kriterijum 1 (nedostatak formalnih uslova za kandidovanje biznis plana). Zato se kriterijum 1 funkcionalno obrađuje kroz administrativnu provjeru potpunosti i Prigovor iz čl. 17. Ne uvodi se druga elektronska provjera istog razloga.

Ovo **nije** isto što i konačna ocjena ispod 30 bodova.

## 9.2. Prigovor

Komisija, putem registrovanog mail-a Podnositeljke na digitalnom servisu Opštine Kotor, obavještava Podnositeljku o mogućnosti podnošenja Prigovora Komisiji putem digitalnog servisa u roku od **3 dana** od dana slanja obavještenja (čl. 17).

Koristi se formulacija Odluke: **3 dana**. Ne dodaje se „radna“.

Komisija donosi odluku o prihvatanju ili odbijanju Prigovora u roku od **7 dana** od prijema istoga (čl. 17).

Ako se Prigovor prihvati, Prijava se dalje razmatra prema pravilima ovog profila.

Ako se Prigovor odbije, Prijava ostaje nepotpuna i Komisija je ne razmatra dalje.

---

# 10. Eliminatorni kriterijumi

Status poglavlja: USVOJENO

Komisija vrši dodjelu sredstava na osnovu pozitivnih i eliminatornih kriterijuma (čl. 19).

Eliminatorni kriterijumi prema čl. 19 Odluke 027/26 su:

1. nedostatak formalnih uslova za kandidovanje biznis plana (nepotpuna dokumentacija);
2. preduzetnica/društvo nije dostavila/lo Izvještaj o realizaciji biznis plana sa Finansijskim izvještajem (Obrasci 4 i 4a) i pratećom dokumentacijom (fakture i izvodi sa banke) za biznis plan koji je u prethodnom periodu finansiran ili djelimično finansiran iz budžeta Opštine;
3. biznis plan nije vezan za prioritetne oblasti navedene u članu 10 Odluke 027/26.

**Kriterijum 1** se funkcionalno obrađuje kroz administrativnu provjeru potpunosti i Prigovor iz čl. 17 (Poglavlje 9). Ne uvodi se druga elektronska provjera istog razloga.

**Kriterijumi 2 i 3** ostaju zasebni. Komisija ih utvrđuje usmeno i kolektivno. Kada utvrdi razlog 2 ili 3, predsjednik Komisije u ime Komisije evidentira zaključak, a konkretan razlog evidentira u postojećoj Napomeni. Takav biznis plan ne ulazi u ocjenjivanje po pozitivnim kriterijumima. Osnovno stanje Prijave ostaje Podnesena. Ne uvodi se novo polje Obrasca 3, novi checkbox, nova kolona ni novi Prigovor za kriterijume 2 i 3.

Ovaj profil strogo razdvaja:

| | Šta | Gdje |
|--|-----|------|
| A | nepotpuna dokumentacija / eliminatorni kriterijum 1 | Poglavlje 9; čl. 17 i čl. 19 |
| B | eliminatorni kriterijumi 2 i 3 | ovo poglavlje; čl. 19 |
| C | konačna ocjena ispod 30 bodova | Poglavlje 13; čl. 21 |

Prag ispod 30 bodova **nije** administrativni eliminatorni kriterijum i **nije** dio ovog poglavlja.

---

# 11. Druga sjednica i usmeno obrazloženje

Status poglavlja: USVOJENO

Drugu sjednicu Komisije i usmeno obrazloženje biznis planova Komisija zakazuje u roku od **15 dana** od održavanja prve sjednice (čl. 17).

Na drugoj sjednici sprovodi se usmeno obrazloženje biznis planova. Relevantno učešće Podnositeljke je usmeno obrazloženje sopstvenog biznis plana.

Nakon sprovedenog usmenog obrazloženja može se ocijeniti kriterijum 10. Nacrt ocjena za ostale pozitivne kriterijume može postojati i prije toga. Individualno ocjenjivanje se **ne** može završiti prije ocjene svih 10 kriterijuma.

Ovo poglavlje **ne** sadrži pravila o tajnosti ocjena niti o nepromjenjivosti. Ta pravila pripadaju Poglavlju 12.

---

# 12. Ocjenjivanje

Status poglavlja: USVOJENO

Obrazac 3 / lista za ocjenjivanje koristi se **od prve sjednice**.

Član Komisije može unositi i čuvati **nacrt** sopstvenih ocjena za pozitivne kriterijume za koje već raspolaže potrebnim osnovom. Nacrt nije obavezan prije usmenog obrazloženja. Član **nije** dužan ocjenjivati prije usmenog.

Kriterijum 10 (usmeno obrazloženje) ocjenjuje se **tek nakon** sprovedenog usmenog obrazloženja biznis plana (čl. 20).

Individualno ocjenjivanje **ne može biti završeno** prije nego što su ocijenjena svih 10 pozitivnih kriterijuma.

Svaki od pet članova Komisije, uključujući predsjednika, ocjenjuje svaki od deset pozitivnih kriterijuma za svaki relevantni biznis plan.

Predsjednik ima **istu težinu** kao ostali članovi pri individualnom ocjenjivanju.

## 12.1. Pozitivni kriterijumi

Pozitivni kriterijumi prema čl. 19 Odluke 027/26:

1. Obrazac biznis plana je detaljno popunjen sa svim neophodnim informacijama i jasno su precizirani proizvodi/usluge koje će se ponuditi na tržištu.
2. Jasno su identifikovani potencijalni kupci i njihove karakteristike.
3. Biznis plan će omogućiti samozapošljavanje i/ili zapošljavanje (stalno ili sezonsko) lica sa teritorije opštine Kotor.
4. Prepoznata je i navedena konkurencija kao i slabosti i snage iste.
5. Jasno su navedeni potrebni resursi i identifikovani dobavljači.
6. Biznis ideja je finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja).
7. Podaci o preduzetnici (fizičko lice/preduzetnica posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti).
8. Preduzetnica planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.
9. Razvijena matrica rizika je jasna i logična.
10. Usmeno obrazloženje biznis plana (preduzetnica je uvjerljiva i sigurna u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovara na sva pitanja).

## 12.2. Skala

Za ocjenu biznis plana prema pozitivnim kriterijumima koristi se skala od **1 do 5** bodova za svaki kriterijum pojedinačno, pri čemu 1 bod znači „uopšte ne odgovara navedenom“, a 5 bodova znači „u potpunosti odgovara navedenom“ (čl. 20).

## 12.3. Tajnost individualnih ocjena

Dok ocjenjivanje traje, članovi Komisije imaju uvid samo u svoje ocjene (čl. 20).

Dok svih 5 članova ne završi individualno ocjenjivanje **svih** biznis planova koji su ušli u pozitivno ocjenjivanje, član vidi samo svoje individualne ocjene. Isto važi za predsjednika Komisije.

Naknadni uvid u ocjene drugih članova **ne** otključava sopstvene ocjene.

## 12.4. Nepromjenjivost nakon završavanja

Čuvanje **nacrta** omogućava nastavak rada i izmjene. Nacrt **nije** konačna individualna ocjena. Obično čuvanje nacrta **nije** okidač nepromjenjivosti.

Član eksplicitno **završava** individualno ocjenjivanje. Završavanje zahtijeva potvrdu i moguće je tek kada su unesene ocjene za svih 10 pozitivnih kriterijuma.

Tek nakon potvrđenog završavanja ocjene postaju nepromjenjive. Ne mogu se izmijeniti, obrisati, poništiti niti zamijeniti drugom ocjenom.

Nema, nakon završavanja:

* izmjene;
* brisanja;
* poništavanja;
* ponovnog ocjenjivanja;
* zamjene drugom ocjenom.

Ovo je usvojeno poslovno pravilo profila. Odluka 027/26 nema odredbu u direktnom konfliktu sa njim.

## 12.5. Završetak ocjenjivanja i proračun

Ocjenjivanje je završeno kada svih 5 članova završi individualno ocjenjivanje svih biznis planova koji su ušli u pozitivno ocjenjivanje.

Prosječna ocjena po svakom kriterijumu predstavlja zbir bodova svih članova Komisije podijeljen brojem članova Komisije (čl. 20).

Na zbir prosječnih ocjena dodaju se dodatni bodovi (čl. 19 i čl. 20).

Konačna ocjena biznis plana predstavlja zbir prosječnih ocjena po svih 10 kriterijuma i dodatnih bodova (čl. 20).

## 12.6. Dodatni bodovi

Predsjednik Komisije elektronski unosi dodatne bodove ukoliko ih ima (čl. 20).

Dodatni bodovi prema čl. 19:

* **+1** — Podnositeljka je prisustvovala Info danu i obuci za pisanje biznis plana koju organizuje Opština Kotor u tekućoj godini;
* **+2** — Podnositeljka je fizičko lice koje tek planira da registruje biznis;
* **+2** — Podnositeljka se nalazi na evidenciji Zavoda za zapošljavanje duže od 12 mjeseci;
* **+3** — biznis ideja je inovativna i/ili „zelena“, prema definicijama čl. 19.

Maksimalno dodatnih bodova = **8**.  
Maksimalna konačna ocjena = **58**.

## 12.7. Zamjena člana Komisije — normativna praznina

Odluka 027/26 uređuje:

* zamjenskog člana zbog odsustva (čl. 6);
* prestanak mandata (čl. 7);
* razrješenje (čl. 8–9);
* imenovanje novog člana u roku od **15 dana** od prestanka mandata (čl. 10);
* kvorum i obavezno prisustvo svih članova pri intervjuu i punovažnim odlukama (čl. 6).

Odluka 027/26 **ne** uređuje sudbinu individualnih ocjena koje je član već **završio** prije prestanka mandata ili zamjene.

To je **normativna praznina**, ne konflikt sa usvojenim pravilom nepromjenjivosti nakon završavanja. Nepromjenjivost završene ocjene se ovim ne mijenja.

Ova tačka **ne blokira** status `USVOJEN` profila. Ne donosi se novo poslovno pravilo kojim bi se praznina popunila.

---

# 13. Preliminarna i konačna rang-lista

Status poglavlja: USVOJENO

Preliminarna i konačna rang-lista **nijesu** dva fizički odvojena poslovna objekta. To su **dva stanja iste rang-liste**.

## 13.1. Preliminarna rang-lista

Kada svi članovi ocjene sve biznis planove, automatski se formira preliminarna rang-lista sa bodovima, bez utvrđenih iznosa koji se dodjeljuju (čl. 20).

Preliminarna rang-lista **ne** smije nastajati samo otvaranjem stranice. Nastaje završetkom ocjenjivanja.

## 13.2. Treća sjednica

Komisija zakazuje treću sjednicu u roku od **7 dana** od održavanja **druge sjednice i usmenih intervjua** (čl. 20).

Koristi se formulacija Odluke: **7 dana**. Ne dodaje se „radnih“.

Rok **ne** teče od završetka ocjenjivanja, niti od formiranja preliminarne rang-liste.

Poslovni tok ipak ostaje:

druga sjednica / usmeno obrazloženje  
→ individualno ocjenjivanje  
→ svi članovi završili  
→ preliminarna rang-lista  
→ treća sjednica.

Na trećoj sjednici Komisija za svaki biznis plan koji se nalazi u preliminarnoj rang-listi utvrđuje (čl. 21):

* da li se biznis plan podržava ili odbija;
* iznos sredstava koji se dodjeljuje.

Predsjednik Komisije na Platformi, u ime Komisije, evidentira:

* zaključak;
* odobreni iznos;
* relevantne napomene;
* obrazloženje.

Za odbijeni plan detaljno obrazloženje je obavezno (čl. 21).

## 13.3. Prag ispod 30 bodova

Biznis planovi sa konačnom ocjenom ispod 30 bodova se neće podržati (čl. 21).

To **nije** administrativni eliminatorni kriterijum.

## 13.4. Maksimalni iznosi — 20% / 10% / 5%

Maksimalan iznos dodijeljenih sredstava za **jedan biznis plan** ne može iznositi više od (čl. 18):

1. **20%** od ukupnog iznosa definisanog u Javnom konkursu za tekuću godinu — za inovativne djelatnosti i/ili zeleno preduzetništvo, prema definicijama čl. 18;
2. **10%** od istog ukupnog iznosa — za fizička lica / preduzetnice / društva kojima ranije nijesu dodjeljivana budžetska sredstva Opštine Kotor za podršku ženskom preduzetništvu;
3. **5%** od istog ukupnog iznosa — za preduzetnice / društva kojima su ranije dodjeljivana budžetska sredstva ženskom preduzetništvu.

Osnovica je formulacija Odluke, bez prevođenja u drugi pojam:

**ukupni iznos definisan u Javnom konkursu za tekuću godinu.**

Uz čl. 3: ukupan iznos sredstava koja se raspodjeljuju definiše se Javnim konkursom.

Ograničenje se odnosi na **jedan biznis plan**.

### Preklapanje 20% sa 10% / 5%

Ako isti biznis plan ispunjava uslove za maksimalni iznos od **20%** po osnovu inovativne djelatnosti i/ili zelenog preduzetništva i istovremeno pripada kategoriji za koju bi po drugom osnovu važila granica od **10%**, primjenjuje se maksimalna granica od **20%**.

Ako isti biznis plan ispunjava uslove za maksimalni iznos od **20%** po osnovu inovativne djelatnosti i/ili zelenog preduzetništva i istovremeno pripada kategoriji za koju bi po drugom osnovu važila granica od **5%**, primjenjuje se maksimalna granica od **20%**.

20% ima prioritet u ova dva slučaja preklapanja.

Ovo je **maksimalna granica**, ne automatski dodijeljeni iznos. Podnositeljka ne dobija automatski 20% ukupnog iznosa. Stvarno odobreni iznos i dalje zavisi od traženog iznosa, raspoloživih sredstava, rangiranja, odluke Komisije i ostalih usvojenih ograničenja raspodjele.

Ovaj profil **ne** sabira procente. Ne primjenjuje se 20% + 10% = 30%, niti 20% + 5% = 25%. Ne primjenjuje se niža granica 10% ili 5% kada je istovremeno ispunjen uslov za 20%.

Ovo pravilo **nije** OPEN. Status: USVOJENO.

## 13.5. Raspodjela sredstava

Sredstva se raspodjeljuju u skladu sa konačnom rang-listom do utroška raspoloživih sredstava (čl. 18 i čl. 21).

Odobreni iznos:

* ne može biti veći od traženog;
* ne može biti veći od primjenjivog maksimalnog iznosa iz čl. 18, uključujući usvojeno pravilo preklapanja iz §13.4;
* ne može biti veći od preostalih raspoloživih sredstava.

U slučaju preklapanja 20% sa 10% ili 5% primjenjuje se maksimalna granica od 20%, prema §13.4.

## 13.6. Tie-break

Ako dva ili više biznis planova dobije isti broj bodova, a raspoloživa sredstva nisu dovoljna za njihovo finansiranje (čl. 21):

* ako je samo jedan od njih plan za otpočinjanje biznisa, sredstva se dodjeljuju tom planu;
* ako nijedan nije plan za otpočinjanje biznisa, ili su svi planovi za otpočinjanje biznisa, sredstva se dodjeljuju na osnovu odluke donijete **većinom glasova od ukupnog broja članova Komisije**.

Tehnički identifikator Prijave **nije** poslovni tie-break.

## 13.7. Konačna rang-lista

Nakon što Komisija na trećoj sjednici završi potrebne radnje i predsjednik Komisije evidentira potrebne podatke, rang-lista prelazi u **konačno stanje** (čl. 21).

Konačna rang-lista se automatski generiše i sadrži, prema čl. 21:

* ime i prezime preduzetnice / naziv društva;
* naziv biznis plana;
* broj bodova;
* iznos traženih sredstava;
* iznos odobrenih sredstava;
* potpise svih članova Komisije.

Usvojeni V1 **izgled i sadržaj** konačne rang-liste ostaju. To se odnosi isključivo na poslovni prikaz/sadržaj, ne na backend ponašanje i ne tvrdi da je implementacija već usklađena.

Zadržani sadržaj prikaza:

* zaglavlje Opštine;
* puni naziv Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj;
* identifikacija Konkursa;
* pozicija;
* naziv biznis plana;
* Podnositeljka;
* tip;
* ocjena /58;
* traženi iznos;
* odobreni iznos;
* status;
* „Zaključak komisije i obrazloženje“;
* podaci o zaključku;
* iznosi;
* lice/datum unosa;
* obrazloženje.

## 13.8. Drugi Konkurs

Ako sredstva planirana budžetom Opštine Kotor za podršku ženskom preduzetništvu nijesu raspodijeljena ili nijesu u cjelosti raspodijeljena po prvom Konkursu, Komisija **može raspisati** drugi Javni konkurs.

Raspisivanje drugog Konkursa **nije** automatsko. Činjenica da nakon prvog Konkursa nijesu raspodijeljena sva sredstva sama po sebi **ne** proizvodi automatsko raspisivanje drugog Konkursa.

Komisija donosi odluku da li će koristiti mogućnost raspisivanja drugog Konkursa.

Ako Komisija odluči da raspiše drugi Konkurs, drugi Konkurs mora biti raspisan najkasnije do isteka trećeg kvartala tekuće godine.

Ovo pravilo **nije** OPEN. Status: USVOJENO.

Normativna sljedivost:

Član 4 koristi formulaciju „raspisuje se“. Član 18 koristi formulaciju „Komisija može raspisati drugi Javni konkurs“. Za kanonski poslovni model usvojeno je pravilo da Komisija **može raspisati** drugi Konkurs.

Prvi Konkurs se raspisuje u drugom kvartalu tekuće godine.

---

# 14. Predlog Odluke i završetak rada Komisije

Status poglavlja: USVOJENO

Kanonski tok nakon konačne rang-liste (čl. 22):

konačna rang-lista  
→ Komisija putem Platforme generiše predlog Odluke  
→ Komisija predlog predlaže Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj  
→ predsjednik Komisije zatvara Konkurs  
→ Konkurs se pohranjuje u Arhivu Konkursa  
→ Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj donosi/objavljuje konačnu Odluku.

Poslovni generator predloga je **Komisija**.

U V1 tehničku radnju na Platformi izvršava **predsjednik Komisije u ime Komisije**. Predsjednik se ne predstavlja kao samostalni donosilac predloga.

Predlog se zasniva na konačnoj rang-listi.

Između konačne rang-liste i predloga **nema**:

* novog ocjenjivanja;
* novog rangiranja;
* nove selekcije.

Podržani planovi iz konačne rang-liste prenose se u predlog Odluke.

## 14.1. Redoslijed zatvaranja i arhive

Redoslijed čl. 22 mora biti očuvan:

1. Komisija generiše predlog;
2. Komisija predlaže predlog Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj;
3. **tek onda** predsjednik Komisije zatvara Konkurs;
4. zatim se Konkurs pohranjuje u Arhivu Konkursa.

Ispravno: generate → propose/send → close → archive.

Nije dozvoljeno: generate → close → send.

Arhiviranje ne mijenja poslovni rezultat i ne briše istoriju (`KN-BM-001` Poglavlje 18).

---

# 15. Konačna Odluka, objava i granica V1

Status poglavlja: USVOJENO

Konačnu Odluku donosi **Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj**.

Komisija predlaže predlog. Komisija **ne** donosi konačnu Odluku.

## 15.1. Rok objave

Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj dužan je da objavi Odluku u roku od **45 dana od dana isteka roka za prijavu na Konkurs** (čl. 22).

Sve prethodne faze potrebne za donošenje i objavljivanje moraju stati unutar tog krajnjeg roka.

## 15.2. Dostavljanje i objava

Odluka se dostavlja učesnicama Konkursa i objavljuje (čl. 24):

* na vebsajtu Opštine Kotor;
* na digitalnom servisu Opštine Kotor;
* putem lokalnog radio emitera;
* na oglasnoj tabli Opštine Kotor.

Objavljivanje na digital.kotor.me predstavlja objavljivanje **već donesene** konačne Odluke. To **nije** generisanje predloga Komisije.

## 15.3. Granica V1

V1 konkursni tok na Platformi završava se objavljivanjem konačne Odluke.

Van Platforme u V1 ostaju, osim gdje je ovaj profil već eksplicitno drugačije odredio:

* pojedinačna Rješenja;
* pravni lijekovi;
* Ugovori;
* isplata sredstava;
* praćenje realizacije;
* kontrola namjenskog korišćenja;
* naknadne izmjene Odluke zbog odustanka.

---

# Otvorena pravna pitanja ovog profila

Trenutno nema formalnih otvorenih pravnih pitanja ovog profila.

OPEN LEGAL ISSUE #1 i OPEN LEGAL ISSUE #2 zatvoreni su poslovnom odlukom u verziji 1.0.1 / `KN-PATCH-BM-001`. Kanonska pravila su u Poglavlju 13.

Normativna praznina — zamjena člana Komisije tokom ocjenjivanja:

Odluka uređuje imenovanje zamjene, ali ne sudbinu već **završenih** individualnih ocjena. To **nije** blocking OPEN. Usvojena nepromjenjivost nakon završavanja ostaje. Pitanje ne blokira status `USVOJEN`.

---

**Kraj dokumenta KN-BM-003 v1.0.2**
