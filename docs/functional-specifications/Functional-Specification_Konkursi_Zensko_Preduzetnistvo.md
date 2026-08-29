# Digital Kotor
# Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu
## Modul: Konkursi

**Oznaka dokumenta:** KN-FS-003
**Naziv:** Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu
**Modul:** Konkursi
**Namespace:** KN
**Tip konkursa:** Žensko preduzetništvo
**Status dokumenta:** U IZRADI
**Verzija:** 0.1.11
**Datum:** 2026-08-29

Povezani dokumenti:

* Registar oznaka: **KN-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md`
* Zajednički poslovni model modula Konkursi: **KN-BM-001** — `docs/business-model/Business_Model_Konkursi.md` (USVOJEN v1.0.0)
* Poslovni profil: **KN-BM-003** — `docs/business-model/Business_Model_Konkursi_Zensko_Preduzetnistvo.md` (USVOJEN v1.0.3)
* Zajedničke funkcionalnosti modula Konkursi: **KN-FS-001** — `docs/functional-specifications/Functional-Specification_Konkursi.md` (planiran; fajl nije kreiran)
* Zajednička tehnička specifikacija modula Konkursi: **KN-TS-001** — `docs/technical-specifications/Technical-Specification_Konkursi.md` (planiran; fajl nije kreiran)

Ovaj dokument **ne** mijenja `KN-BM-001` niti `KN-BM-003`.

Ovaj dokument **ne** tvrdi da je opisano ponašanje već implementirano na Platformi. Implementacija se usklađuje sa usvojenim BM i ovom specifikacijom, a ne obrnuto (`BM-KN-012`; `docs/METHODOLOGY.md` §3.3).

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-26 | Uspostavljen kostur `KN-FS-003`. Napisana i usvojena Poglavlja 1–4. Poglavlje 3 usklađeno: Sekretarijat donosi konačnu Odluku van Platforme; Administrator Konkursa objavljuje već donesenu Odluku na Platformi; Podnesena Prijava nije izmjenjiva; nacrt ocjena vs završavanje. Poglavlje 4 konsoliduje usvojena funkcionalna stanja. Referenca na `KN-BM-003` v1.0.2. Poglavlja 5–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.1 | 2026-08-27 | Napisano Poglavlje 5: Komisija u konfiguraciji Konkursa, nalog člana, mandat / zamjena / smjena, dodjela Komisije, konfiguracija instance, čuvanje i validnost. Mehanika objave i toka roka pripada Poglavlju 6. Poglavlja 6–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.2 | 2026-08-27 | Napisano i usvojeno Poglavlje 6: objava Konkursa, rok za Prijave od 20 kalendarskih dana, automatski prestanak podnošenja, prikaz ISTEKLO; Konkurs ostaje Objavljen. Poglavlja 7–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.3 | 2026-08-28 | Napisano Poglavlje 7 — Prijava Podnositeljke. Usklađeno: nakon uspješnog podnošenja Prijava je Podnesena i zaključana; Podnositeljka je ne može mijenjati, povući, obrisati ni ponovo podnijeti. Stanja Prijave: U pripremi / Podnesena. Poglavlja 8–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.4 | 2026-08-28 | Napisano Poglavlje 8 — Privatnost Prijava. Dok rok za prijavu traje, pojedinačna Prijava je privatna u stanjima U pripremi i Podnesena. Administrator konkursa vidi samo zbirni broj Prijava konkretnog Konkursa. Komisija nema pristup informacijama o Prijavama. Poglavlja 9–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.5 | 2026-08-28 | Napisano Poglavlje 9 — Istek roka i pristup Komisije. Rok ističe automatski bez promjene stanja. Komisiji postaju dostupne samo Podnesene Prijave konkretnog Konkursa, aktivnim članovima dodijeljene Komisije. Pregled prateće dokumentacije DA, preuzimanje NE. Poglavlja 10–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.6 | 2026-08-28 | Napisano Poglavlje 10 — Prva sjednica, administrativna provjera i Prigovor. Komisija vrši administrativnu provjeru Podnesenih Prijava. Predsjednik evidentira Potpuna / Nepotpuna. Nema dopune nakon isteka roka. Prigovor ide isključivo preko Platforme. Poglavlja 11–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.7 | 2026-08-28 | Poglavlje 11 — Eliminatorni kriterijumi — privremeno obustavljeno (`OBUSTAVLJENO`) do pribavljanja i analize autoritativnog izvora Odluke 027/26. Poglavlja 12–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.8 | 2026-08-28 | Napisano Poglavlje 12 — Druga sjednica i usmeno obrazloženje. Druga sjednica i usmeno obrazloženje su van Platforme. Platforma ne zakazuje, ne poziva i ne evidentira prisustvo. Kriterijum 10 se ocjenjuje nakon usmenog obrazloženja kao poslovno pravilo, bez tehničkog otključavanja. Poglavlja 13–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.9 | 2026-08-29 | Napisano Poglavlje 13 — Individualno ocjenjivanje. Pet članova ocjenjuje 10 pozitivnih kriterijuma skalom 1–5. Nacrt do eksplicitnog Završi ocjenjivanje. Tajnost do završetka ciklusa. Ostale napomene opcione. Dodatni bodovi, prosjeci, prag 30 i rang-lista pripadaju Poglavlju 14. Poglavlja 14–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.10 | 2026-08-29 | Napisano Poglavlje 14 — Rang-lista, iznosi i treća sjednica. Zbirni rezultati i preliminarna rang-lista nastaju tek po završetku cjelokupnog ciklusa. Dodatni bodovi, konačna ocjena, prag 30, treća sjednica van Platforme, predloženi iznosi, tie-break čl. 21 i zaključana konačna rang-lista. Predlog Odluke, zatvaranje, arhiva i objava pripadaju Poglavlju 15. Poglavlja 15–19 ostaju za naredne odobrene dokumentacione korake. |
| 0.1.11 | 2026-08-29 | Napisano Poglavlje 15 — Predlog Odluke, zvanična Odluka, zaključivanje, arhiva i objava. Predlog se generiše iz zaključane konačne rang-liste. Zvanična Odluka nastaje fizičkim potpisom sekretara van Platforme. Zaključivanje nije donošenje. Ciljno: Administrator konkursa postavlja i objavljuje potpisani primjerak. Poglavlja 16–19 ostaju za naredne odobrene dokumentacione korake. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Dokument ima status `U IZRADI` i nije formalno usvojen. Dok dokument ima status `U IZRADI`, redakcijske korekcije koje ne mijenjaju značenje mogu se unositi u okviru iste radne verzije. Kada se odobrenim dokumentacionim korakom doda ili promijeni sadržaj, obuhvat ili usvojeno pravilo dokumenta, povećava se radna verzija i dodaje novi red u istoriju verzija. Postojeći redovi istorije verzija ne mijenjaju se. PATCH oznaka se ne izdaje dok dokument nije formalno usvojen.

---

## Svrha dokumenta

Dokument je funkcionalna specifikacija tipa konkursa **Žensko preduzetništvo**. Prevodi usvojena poslovna pravila tog profila u funkcionalno ponašanje Platforme. Nije Business Model i nije Technical Specification.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Identitet, svrha i granice | USVOJENO |
| 2. Odnos prema BM / FS / TS | USVOJENO |
| 3. Akteri i ovlašćenja na Platformi | USVOJENO |
| 4. Funkcionalna stanja | USVOJENO |
| 5. Kreiranje i konfiguracija Konkursa | USVOJENO |
| 6. Objavljivanje i rok za Prijave | USVOJENO |
| 7. Prijava Podnositeljke | USVOJENO |
| 8. Privatnost Prijava | USVOJENO |
| 9. Istek roka i pristup Komisije | USVOJENO |
| 10. Prva sjednica, administrativna provjera i Prigovor | USVOJENO |
| 11. Eliminatorni kriterijumi | OBUSTAVLJENO |
| 12. Druga sjednica i usmeno obrazloženje | USVOJENO |
| 13. Individualno ocjenjivanje | USVOJENO |
| 14. Rang-lista, iznosi i treća sjednica | USVOJENO |
| 15. Predlog Odluke, zatvaranje, arhiva i objava | USVOJENO |
| 16. Funkcionalne zabrane i enforcement | NIJE ZAPOČETO |
| 17. V1 granica | NIJE ZAPOČETO |
| 18. Prihvatni kriterijumi | NIJE ZAPOČETO |
| 19. Sljedivost | NIJE ZAPOČETO |

Radna struktura Poglavlja 16–19 je odobrena. Sadržaj tih poglavlja **nije** odobren ovim korakom. Poglavlje 11 je **OBUSTAVLJENO** do pribavljanja autoritativnog izvora.

---

# Pravila upravljanja dokumentom

1. `KN-FS-003` specificira funkcionalno ponašanje Platforme za tip konkursa Žensko preduzetništvo.
2. Poslovna pravila ostaju u `KN-BM-003` (profil) i `KN-BM-001` (zajednički sloj).
3. Ovaj dokument ne mijenja i ne reinterpretira usvojena poslovna pravila.
4. Postojeća implementacija nije izvor funkcionalnog pravila.
5. Odstupanja implementacije od BM/FS dokumentuju se u tehničkoj/gap analizi, ne u ovom dokumentu kao nova pravila (`docs/METHODOLOGY.md` §3.3).
6. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno uvoditi poslovna ni funkcionalna pravila van odobrenog dokumentacionog koraka.

---

## Sadržaj

1. Identitet, svrha i granice
2. Odnos prema BM / FS / TS
3. Akteri i ovlašćenja na Platformi
4. Funkcionalna stanja
5. Kreiranje i konfiguracija Konkursa
6. Objavljivanje i rok za Prijave
7. Prijava Podnositeljke
8. Privatnost Prijava
9. Istek roka i pristup Komisije
10. Prva sjednica, administrativna provjera i Prigovor
11. Eliminatorni kriterijumi
12. Druga sjednica i usmeno obrazloženje
13. Individualno ocjenjivanje
14. Rang-lista, iznosi i treća sjednica
15. Predlog Odluke, zatvaranje, arhiva i objava
16. Funkcionalne zabrane i enforcement
17. V1 granica
18. Prihvatni kriterijumi
19. Sljedivost

---

# 1. Identitet, svrha i granice

Status poglavlja: USVOJENO

`KN-FS-003` je funkcionalna specifikacija tipa konkursa **Žensko preduzetništvo** u modulu **Konkursi**.

Dokument određuje kako Platforma ostvaruje usvojena poslovna pravila tog profila: koje akcije dozvoljava, kome, u kojoj fazi, sa kojom vidljivošću, kojim validacijama i kojim posljedicama.

Žensko preduzetništvo **nije** zaseban dokumentacioni modul. Pripada namespace-u `KN` (`DK-DS-001` §1).

## 1.1. Izvor istine

Primarni poslovni SSOT ovog profila je `KN-BM-003` v1.0.2.

Zajednički poslovni SSOT modula Konkursi je `KN-BM-001` v1.0.0.

`KN-FS-003` **ne** mijenja poslovna pravila. **Ne** izvodi pravila iz trenutnog koda. **Ne** usklađuje poslovni model sa postojećom implementacijom.

Postojeća implementacija može se naknadno auditovati prema ovoj specifikaciji. Odstupanja implementacije pripadaju tehničkoj i gap analizi, ne reinterpretaciji poslovnih pravila.

## 1.2. Granica V1

V1 funkcionalni obuhvat na Platformi završava se **objavljivanjem konačne Odluke**, u skladu sa `KN-BM-003` Poglavljem 15.

Van Platforme u V1 ostaju, osim gdje `KN-BM-003` već eksplicitno drugačije odredi:

* pojedinačna Rješenja;
* pravni lijekovi;
* Ugovori;
* isplata sredstava;
* praćenje realizacije;
* kontrola namjenskog korišćenja;
* naknadne izmjene Odluke zbog odustanka.

Ovo poglavlje **ne** proširuje V1.

## 1.3. Šta ovaj dokument nije

`KN-FS-003` nije:

* poslovni profil;
* tehnička specifikacija;
* opis trenutnog koda;
* UI pixel-level specifikacija;
* izvor pravnih pravila Odluke 027/26.

Tehnička realizacija (modeli, tabele, klase, rute, policies, servisi, storage) ne pripada ovom dokumentu.

---

# 2. Odnos prema BM / FS / TS

Status poglavlja: USVOJENO

Hijerarhija dokumentacije za ovaj profil:

poslovni sloj  
→ `KN-BM-001` (zajednička pravila modula Konkursi)  
→ `KN-BM-003` (poslovna pravila profila Žensko preduzetništvo)

funkcionalni sloj  
→ `KN-FS-003` (ovo dokument)

tehnički sloj  
→ prema kanonskoj KN arhitekturi; trenutno je planiran zajednički `KN-TS-001`. Novi TS Document ID se ovim dokumentom **ne** uvodi.

## 2.1. Autoritet

* `KN-BM-003` je autoritativan za poslovna pravila specifična za Žensko preduzetništvo.
* `KN-BM-001` je autoritativan za zajednička poslovna pravila modula Konkursi.
* `KN-FS-003` specificira funkcionalno ponašanje Platforme kojim se ta pravila ostvaruju.
* FS može referencirati BM pravila bez nepotrebnog ponavljanja pravnog i poslovnog obrazloženja.
* FS **ne može** preglasati BM.
* TS **ne može** preglasati FS ni BM.
* Postojeći kod **nije** normativni izvor.

Sljedivost: BM → FS → TS → implementacija → testovi, gdje je primjenjivo (`DK-DS-001` §11; `docs/METHODOLOGY.md`).

## 2.2. Odnos prema KN-FS-001

`KN-FS-001` je planirani zajednički i konfigurabilni funkcionalni sloj modula Konkursi.

`KN-FS-003` je profilni funkcionalni sloj tipa konkursa Žensko preduzetništvo.

`KN-FS-003` **ne** pretvara pravila ovog profila u univerzalno ponašanje svih konkursa (`KN-BM-001`, `KN-FS-001` / `KN-FS-00x`).

Zajedničke konfigurabilne sposobnosti ne treba nepotrebno duplirati ovdje ako pripadaju `KN-FS-001`. Nepostojanje fajla `KN-FS-001` **ne** ovlašćuje ovaj dokument da usvoji zajednička pravila umjesto zajedničkog FS-a, niti da ih izvede iz koda.

`KN-FS-002` ostaje planirani funkcionalni profil konkursa za podršku preduzetništvu mladih. Ovaj dokument ga **ne** mijenja.

---

# 3. Akteri i ovlašćenja na Platformi

Status poglavlja: USVOJENO

Ovo poglavlje određuje **ko** na Platformi koristi funkcije ovog profila. Ne određuje u potpunosti **kada** i **kako**; to pripada Poglavljima 4–18.

Poslovni akter iz `KN-BM-003` **nije** automatski funkcionalni akter ovog dokumenta.

## 3.1. Princip funkcionalnog aktera

Funkcionalni akter je akter koji stvarno obavlja, prima ili kontroliše profilnu funkciju **preko Platforme**.

Poslovni akter iz BM-a postaje funkcionalni akter `KN-FS-003` samo ako ima takvu interakciju u usvojenom V1 profilu.

Ovlašćenja su ograničena fazom Konkursa i usvojenim pravilima `KN-BM-003`. Vidljivost je sama po sebi funkcionalno ovlašćenje.

Odsustvo ovlašćenja znači da akcija ili sadržaj **ne** smiju biti dostupni kroz redovnu interakciju sa Platformom.

Ovo poglavlje ne određuje rute, middleware, policies ni bazu.

## 3.2. Podnositeljka

Funkcionalni akter: **DA**. Osnov: `KN-BM-003` §4.1, §7, §7.1, §9.2.

### Vidljivost

* javni podaci Konkursa;
* sopstvena Prijava, uključujući sopstveno stanje Prijave;
* javna rang-lista, konačna Odluka i drugi javno objavljeni rezultati, kada su objavljeni.

Javni rezultat **nije** kompletna Prijava druge Podnositeljke.

### Radnje

Ovlašćenja zavise od osnovnog funkcionalnog stanja Prijave. Detalj stanja: Poglavlje 4.

**U pripremi**, samo dok rok za prijavu traje:

* uređivati sopstvenu Prijavu;
* obrisati sopstvenu Prijavu;
* podnijeti sopstvenu Prijavu.

**Podnesena:**

* sadržaj više nije izmjenjiv;
* ne može se obrisati;
* ne može se povući;
* ne može se ponovo podnijeti.

Gdje usvojeni tok to predviđa, Podnositeljka podnosi Prigovor Komisiji putem digitalnog servisa (`KN-BM-003` §9.2). Detalj: Poglavlja 4 i 10.

Polja, validacije i forme nisu predmet ovog poglavlja. Detalj: Poglavlja 6–8 i 10.

### Ograničenja

* nema pristup kompletnoj Prijavi druge Podnositeljke;
* ograničenje važi tokom aktivnog Konkursa, evaluacije, nakon zaključenja i nakon arhiviranja;
* arhiviranje samo po sebi ne otvara tuđe Prijave;
* javna rang-lista / konačna Odluka / javni rezultat nisu obrazac Prijave, biznis plan, prilozi ni drugi nejavni dijelovi tuđeg dosijea;
* Podnesena Prijava **nije** izmjenjiva, ni dok rok još traje;
* nakon isteka roka nema uređivanja, podnošenja ni brisanja Prijave.

## 3.3. Administrator Konkursa

Funkcionalni akter: **DA**. Osnov: `KN-BM-003` §4.2, §5, §6, §15.2.

Na Platformi upravlja konkursnim administrativnim radnjama u okviru usvojenih ovlašćenja, uključujući:

* kreiranje i konfiguraciju Konkursa;
* unos zavodnog broja sa pisarnice;
* objavljivanje Konkursa;
* povezivanje Komisije sa Konkursom, gdje to usvojeni tok predviđa;
* objavljivanje na Platformi konačne Odluke koju je prethodno donio Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj.

**Objava na Platformi nije donošenje Odluke.**

Administrator Konkursa objavljuje već donesenu konačnu Odluku. Ne donosi je, ne odobrava je, ne mijenja je, ne zamjenjuje je i ne uređuje njen sadržaj. Ne djeluje u ime tog Sekretarijata.

Detalj kreiranja i objave Konkursa: Poglavlja 5 i 6. Detalj objave konačne Odluke: Poglavlje 15.

Ostali kanali objave koje zahtijeva poslovni/pravni tok ostaju van profilne Platform radnje koja se ovdje specificira.

Administrator Konkursa **ne može** biti član Komisije (`BM-KN-014`).

Administrator Konkursa **nema** ovlašćenje da:

* ocjenjuje biznis planove;
* utvrđuje rang-listu;
* generiše predlog Odluke u ime Komisije niti završava Platform radnju kojom predlog postaje pripremljen za dostavu Sekretarijatu;
* donosi konačnu Odluku;
* odobrava, mijenja, zamjenjuje ili sadržinski uređuje konačnu Odluku;
* obavlja radnje koje pripadaju predsjedniku Komisije ili Administratoru platforme.

V1 ne uvodi funkcije nakon objave konačne Odluke van `KN-BM-003` Poglavlja 15.

## 3.4. Član Komisije

Funkcionalni akter: **DA**. Osnov: `KN-BM-003` §4.3, §8, §9, §11, §12.

Komisija je kolektivni poslovni akter. Na Platformi pojedinačno djeluju njeni članovi.

Član Komisije:

* **nema** pristup podnesenim Prijavama, biznis planu ni prilozima **prije isteka roka** za prijavljivanje;
* pristupa Prijavama tek kada usvojeni tok to dozvoli, nakon isteka roka;
* učestvuje u pregledu i ocjenjivanju prema `KN-BM-003`;
* unosi sopstvene individualne ocjene, uključujući izmjenjiv **nacrt**;
* vidi samo sopstvene individualne ocjene dok svih pet članova ne završi individualno ocjenjivanje svih biznis planova koji su ušli u pozitivno ocjenjivanje;
* nakon **završavanja** individualnog ocjenjivanja ne mijenja, ne briše, ne poništava i ne zamjenjuje te ocjene;
* **nema** ovlašćenje za operativne radnje koje BM dodjeljuje predsjedniku Komisije.

Mehanika ocjenjivanja, tajnost i nepromjenjivost razrađuju se u Poglavljima 4 i 13.

Zamjena člana Komisije nakon već **završenih** individualnih ocjena ostaje non-blocking normativna praznina iz `KN-BM-003` §12.7. Ovo poglavlje **ne** uvodi ovlašćenja zamjene. Nepromjenjivost završene ocjene ostaje.

## 3.5. Predsjednik Komisije

Funkcionalni akter: **DA**. Osnov: `KN-BM-003` §4.3, §8, §9, §12.6, §13.2, §14.

Predsjednik Komisije **jeste** član Komisije.

Pri individualnom ocjenjivanju predsjednik ima:

* ista pravila ocjenjivanja;
* istu težinu;
* ista ograničenja tajnosti;
* **nema** privilegovan uvid u ocjene drugih članova dok svih pet ne završi individualno ocjenjivanje svih biznis planova koji su ušli u pozitivno ocjenjivanje;
* **nema** ovlašćenje da mijenja ocjenu drugog člana;
* **nema** posebnu ocjenjivačku moć.

Sva ograničenja iz §3.4 važe i za predsjednika.

Dodatne Platform radnje predsjednik izvršava **u ime Komisije**, prema `KN-BM-003` §4.3:

* evidentira rezultat administrativne provjere dokumentacije;
* elektronski unosi dodatne bodove ukoliko ih ima;
* evidentira zaključke, iznose i obrazloženja sa treće sjednice;
* tehnički generiše predlog Odluke u ime Komisije;
* završava Platform radnju kojom predlog postaje pripremljen i evidentiran za dostavu Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj;
* zatvara Konkurs **nakon** te Platform radnje.

Poslovni generator predloga je **Komisija**. Predsjednik **nije** samostalni donosilac predloga niti konačne Odluke.

Ovaj dokument **ne** uvodi nalog, prijemno sanduče, workflow ni elektronsko odobrenje tog Sekretarijata na Platformi. Dostava predloga Sekretarijatu ostaje poslovna granica. Donošenje konačne Odluke ostaje van Platforme.

Detalj redoslijeda generate → propose → close → archive: Poglavlje 15.

## 3.6. Akteri van profilnog funkcionalnog obuhvata Platforme

### A. Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj

Poslovni akter: **DA** (`KN-BM-003` §4.5, §14, §15).

Profilni funkcionalni akter Platforme: **NE**.

Usvojene poslovne radnje tog Sekretarijata u V1 **ne** obavljaju se kroz profilnu interakciju sa Platformom: imenovanje Komisije, donošenje konačne Odluke, ostali kanali objave van Platforme, Rješenja, ugovori i praćenje.

Ovaj dokument **ne** uvodi ulogu, nalog, ovlašćenja, ekrane, prijemno sanduče ni akcije tog Sekretarijata na Platformi.

Predlog se poslovno dostavlja tom Sekretarijatu van profilnog Platform workflow-a. Sekretarijat donosi konačnu Odluku van Platforme. Objavu već donesene konačne Odluke **na Platformi** vrši Administrator Konkursa (§3.3). Ostali kanali objave ostaju van ove profilne Platform radnje.

Sekretarijat se ne briše iz poslovnog toka.

### B. Administrator platforme

Poslovni / platformski akter: **DA**, prema `KN-BM-001` i `KN-BM-003` §4.4.

Profilni funkcionalni akter `KN-FS-003`: **NE**.

`KN-BM-003` ne dodjeljuje Administratoru platforme nijednu profilnu Platform radnju. Samim postojanjem te uloge ne postaje Administrator Konkursa niti član Komisije.

Opšta platformska administracija ostaje van profilnog funkcionalnog obuhvata. Ovaj dokument **ne** dodjeljuje mu konkursna ovlašćenja.

## 3.7. Matrica funkcionalnih ovlašćenja

Vrijednosti se odnose na redovnu interakciju sa Platformom u ovom profilu. `USLOVNO` znači da važi samo u fazi i pod uslovom koje određuje `KN-BM-003`; detalj faza: Poglavlje 4.

| Akter | Vidi javni Konkurs | Vidi sopstvenu Prijavu | Vidi tuđu kompletnu Prijavu | Kreira / uređuje Prijavu | Administracija Konkursa | Pregled Komisije | Individualno ocjenjivanje | Operativne radnje predsjednika | Objava konačne Odluke na Platformi |
|------|--------------------|------------------------|-----------------------------|--------------------------|-------------------------|------------------|---------------------------|--------------------------------|------------------------------------|
| Podnositeljka | DA | DA | NE | USLOVNO — U pripremi, dok rok traje | NE | NE | NE | NE | NE |
| Administrator Konkursa | DA | NE | NE | NE | DA | NE | NE | NE | DA |
| Član Komisije | DA | NE | USLOVNO — tek nakon isteka roka | NE | NE | USLOVNO — nakon isteka roka | USLOVNO — nacrt od prve sjednice; završavanje tek kada su ocijenjena svih 10 | NE | NE |
| Predsjednik Komisije | DA | NE | USLOVNO — tek nakon isteka roka | NE | NE | USLOVNO — nakon isteka roka | USLOVNO — nacrt od prve sjednice; završavanje tek kada su ocijenjena svih 10 | DA — u ime Komisije | NE |

Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj i Administrator platforme **nijesu** profilni funkcionalni akteri. Nisu u ovoj matrici.

Podnesena Prijava **nije** izmjenjiva, **nije** brisiva i **ne** povlači se. Ne može se ponovo podnijeti.

Konačnu Odluku **donosi** taj Sekretarijat van Platforme. Objavu već donesene konačne Odluke **na Platformi** vrši Administrator Konkursa. Objava nije donošenje.

---

# 4. Funkcionalna stanja

Status poglavlja: USVOJENO

Ovo poglavlje određuje **kada** koje funkcije na Platformi jesu ili nijesu dostupne. Ne razrađuje forme, validacije polja ni tehničku realizaciju.

## 4.1. Princip funkcionalnih stanja

Razlikuju se:

* **poslovni status** — poslovno stanje elementa, kada ga BM ili Odluka prepoznaju;
* **funkcionalno stanje** — stanje potrebno da Platforma kontroliše dostupne funkcije;
* **događaj / međaš** — nešto što se dogodi, a ne mora biti trajno stanje;
* **izvedeni uslov** — utvrđuje se iz podataka, vremena ili završetka radnje, a ne kao zasebno trajno stanje.

Faza procesa **nije** automatski status.

Ovaj dokument **ne** uvodi tehnički katalog statusa. Ne pretvara svaki korak Odluke 027/26 u trajno stanje.

Tehničko čuvanje i enforcement usvojenih pravila pripadaju TS sloju.

## 4.2. Vremenski uslovi Konkursa

Istek roka za prijavu je **izvedeni uslov**. Nije zasebno trajno stanje Konkursa.

Platforma ga utvrđuje na osnovu roka Konkursa (`KN-BM-003` §6, §6.1). Ne uvodi se zakazana promjena statusa kao poslovno pravilo.

Dok rok traje, dozvoljene su samo radnje koje odgovaraju osnovnom stanju Prijave (§4.4).

Nakon isteka roka:

* nema novih izmjena, podnošenja ni brisanja Prijave;
* Komisiji postaje dostupan konkursni tok prema Odluci 027/26 (`KN-BM-003` §8).

## 4.3. Zatvaranje i arhiviranje Konkursa

Zatvaranje Konkursa je **događaj / radnja** predsjednika Komisije, u ime Komisije.

**Arhiviran** je funkcionalno stanje Konkursa.

Kada predsjednik zatvori Konkurs, Platforma ga **odmah i automatski** arhivira.

Nema:

* posebnog dugmeta Arhiviraj;
* ručnog arhiviranja;
* redovnog međustanja „zatvoren, ali nije arhiviran“.

Redoslijed predlaganja i zatvaranja: §4.13 i §4.14.

## 4.4. Osnovna funkcionalna stanja Prijave

Kanonska osnovna stanja Prijave su:

* **U pripremi**;
* **Podnesena**.

Istek roka **nije** dodatno stanje Prijave.

**U pripremi**, samo dok rok za prijavu traje:

* uređivanje: DA;
* brisanje: DA;
* podnošenje: DA.

**Podnesena** nastaje eksplicitnim podnošenjem. Nakon podnošenja sadržaj je nepromjenjiv.

* uređivanje: NE;
* brisanje: NE;
* povlačenje: NE;
* ponovno podnošenje: NE.

## 4.5. Administrativna provjera i Prigovor

Administrativna provjera i Prigovor **nijesu** osnovno stanje Prijave.

Rezultat administrativne provjere (čl. 17; `KN-BM-003` §9):

* **Potpuna**;
* **Nepotpuna**.

Predsjednik Komisije na Platformi, u ime Komisije, evidentira da li Podnositeljka ima svu potrebnu dokumentaciju.

Ako je Prijava nepotpuna, Komisija je neće dalje razmatrati, osim ako Prigovor bude prihvaćen.

Prigovor je zaseban tok u kontekstu čl. 17:

* **Podnesen**;
* **Prihvaćen**;
* **Odbijen**.

Prihvaćen: Prijava nastavlja dalji tok prema pravilima ovog profila.

Odbijen: Prijava ostaje nepotpuna i ne razmatra se dalje.

## 4.6. Eliminatorni kriterijumi

Eliminatorni kriterijumi su prema čl. 19 Odluke 027/26.

**Kriterijum 1** — nedostatak formalnih uslova / nepotpuna dokumentacija — funkcionalno se obrađuje kroz administrativnu provjeru potpunosti iz čl. 17 i odgovarajući tok Prigovora (§4.5).

Ne uvodi se druga elektronska provjera istog razloga.

**Kriterijumi 2 i 3** Komisija utvrđuje **usmeno i kolektivno**:

* **2** — nije dostavljen Izvještaj o realizaciji ranije finansiranog biznis plana sa Finansijskim izvještajem (Obrasci 4 i 4a) i propisanom pratećom dokumentacijom;
* **3** — biznis plan nije vezan za prioritetne oblasti iz čl. 10 Odluke 027/26.

Kada Komisija utvrdi eliminatorni razlog 2 ili 3:

* predsjednik Komisije u ime Komisije evidentira zaključak;
* konkretan eliminatorni razlog evidentira u postojećoj Napomeni;
* takav biznis plan **ne ulazi** u ocjenjivanje po pozitivnim kriterijumima.

Osnovno stanje Prijave ostaje **Podnesena**.

Ne uvodi se:

* novo osnovno stanje Eliminisana / Odbijena;
* novo vidljivo normativno polje;
* novi checkbox;
* nova kolona Obrasca 3;
* novi Prigovor za kriterijume 2 i 3.

Funkcionalna posljedica: Platforma ne omogućava dalje pozitivno ocjenjivanje plana sa utvrđenim eliminatornim razlogom 2 ili 3.

Kako se ta zabrana tehnički čuva: TS.

## 4.7. Obrazac 3 i radni nacrt ocjena

Obrazac 3 / lista za ocjenjivanje dostupna je Komisiji već od prve sjednice.

Dostupnost Obrasca 3 **nije** isto što i završeno ocjenjivanje.

Član Komisije može unositi i čuvati **nacrt** sopstvenih ocjena za pozitivne kriterijume za koje raspolaže potrebnim osnovom.

Nacrt:

* može se ponovo otvoriti;
* može se mijenjati;
* **nije** konačno završena individualna ocjena.

Kriterijum 10 — usmeno obrazloženje — ocjenjuje se nakon sprovedenog usmenog obrazloženja biznis plana.

Prije mogućnosti ocjenjivanja kriterijuma 10, individualno ocjenjivanje tog biznis plana **ne može biti završeno**.

## 4.8. Završavanje individualnog ocjenjivanja

Član može završiti individualno ocjenjivanje tek kada su unesene ocjene za svih 10 pozitivnih kriterijuma.

Završavanje je posebna eksplicitna radnja. Traži jasnu potvrdu člana.

Nakon potvrđenog završavanja:

* njegove ocjene postaju nepromjenjive;
* ne mogu se uređivati;
* ne mogu se brisati, resetovati ni zamijeniti.

Obično čuvanje nacrta **nije** okidač nepromjenjivosti.

Zamjena člana Komisije nakon već završenih individualnih ocjena ostaje non-blocking normativna praznina (`KN-BM-003` §12.7). Ovo poglavlje je **ne** rješava.

## 4.9. Završetak ukupnog ocjenjivanja i vidljivost rezultata

Dok ukupno ocjenjivanje traje:

* svaki član vidi samo svoje individualne ocjene;
* predsjednik **nema** privilegovan uvid;
* činjenica da je član završio svoje ocjenjivanje ne otkriva rezultate drugih.

Ukupno ocjenjivanje je završeno kada **svi** članovi Komisije završe individualno ocjenjivanje **svih** biznis planova koji su ušli u pozitivno ocjenjivanje.

Tada istovremeno:

* članovima Komisije postaju dostupni kompletni Obrasci 3 za sve ocijenjene biznis planove;
* postaju vidljive konačne individualne ocjene svih članova, prosjeci, dodatni bodovi i konačna ocjena;
* završene ocjene ostaju nepromjenjive;
* automatski se formira preliminarna rang-lista.

Otkrivanje je **globalno** za završeni ciklus ocjenjivanja, ne plan po plan.

Predsjednik elektronski evidentira dodatne bodove.

Konačna ocjena je zbir prosječnih ocjena pozitivnih kriterijuma i dodatnih bodova.

## 4.10. Preliminarna rang-lista

Preliminarna rang-lista automatski nastaje završetkom ukupnog ocjenjivanja.

Svi bodovani biznis planovi ulaze na preliminarnu rang-listu. Nema odobrenih iznosa.

**30 bodova** je prag koji omogućava dalje odlučivanje o dodjeli:

* **≥ 30** — iznad crte;
* **< 30** — ostaje vidljiv ispod crte.

Planovima ispod 30 sredstva se **ne mogu** dodijeliti. Ne uklanjaju se iz preliminarne rang-liste.

Ne uvodi se sopstveni tie-break. Pravila jednakih bodova primjenjuju se prema čl. 21 kada nastupe propisani uslovi (§4.11).

## 4.11. Treća sjednica i odlučivanje Komisije

Treća sjednica se zakazuje u roku od **7 dana** od održavanja druge sjednice i usmenih intervjua (čl. 20; `KN-BM-003` §13.2).

Planovi **< 30**:

* ostaju na rang-listi ispod crte;
* nad njima nema daljeg postupanja za dodjelu sredstava;
* nema unosa Podržava / Odbija;
* nema dodjele iznosa;
* nema posebnog obrazloženja u ovom koraku.

Planovi **≥ 30**: Komisija utvrđuje **Podržava** ili **Odbija**.

Broj bodova iznad 30 **ne garantuje** podršku. Komisija može odbiti i plan sa ocjenom ≥ 30.

Ako Komisija utvrdi **Odbija**, detaljno obrazloženje je **obavezno**. Platforma ne omogućava završavanje Odbija bez detaljnog obrazloženja.

Predsjednik ne odlučuje kao pojedinac. Na Platformi, u ime Komisije, evidentira zaključak i obrazloženje.

Ako **Podržava**, odobreni iznos je obavezan. Odobreni iznos ne može preći:

* traženi iznos;
* primjenjivu maksimalnu granicu čl. 18, uključujući usvojeno pravilo preklapanja 20% / 10% / 5% (`KN-BM-003` §13.4: pri preklapanju 20% ima prioritet; procenti se ne sabiraju; 20% nije automatski dodijeljeni iznos);
* raspoloživa sredstva.

Ako za sljedeći plan na rang-listi nema dovoljno za puni traženi iznos, Komisija mu može dodijeliti **preostali** raspoloživi iznos. Raspodjela tada ide do utroška sredstava.

Jednaki bodovi — tačno prema čl. 21:

* ako je samo jedan od izjednačenih planova plan za otpočinjanje biznisa, prednost tom planu;
* ako nijedan nije, ili su svi, Komisija odlučuje **većinom glasova od ukupnog broja članova**.

Glasanje se sprovodi na sjednici. Ne uvodi se zaseban elektronski tok glasanja. Predsjednik evidentira zaključak Komisije.

## 4.12. Konačna rang-lista

Nakon što predsjednik završi unos svih obaveznih podataka utvrđenih na trećoj sjednici, Platforma **automatski** formira konačnu rang-listu.

Planovi < 30 ne zahtijevaju dodatni unos radi njenog završavanja.

Sadržaj najmanje poštuje čl. 21:

* ime i prezime preduzetnice / naziv društva;
* naziv biznis plana;
* broj bodova;
* iznos traženih sredstava;
* iznos odobrenih sredstava;
* potpise svih članova Komisije.

Usvojeni V1 prikaz konačne rang-liste ostaje prezentacioni zahtjev (`KN-BM-003` §13.7): pozicija; naziv; Podnositeljka; tip; ocjena /58; traženi iznos; odobreni iznos; status / ishod; Zaključak Komisije i obrazloženje; lice / datum unosa; obrazloženje.

**Potpisi.** Platforma generiše dokument sa imenima svih članova Komisije i mjestima za njihove potpise. Potpisivanje je **fizički, van Platforme**. Platforma ne simulira elektronski potpis. Ne uvodi se obavezni upload skeniranog potpisanog dokumenta.

## 4.13. Predlog konačne Odluke

Završena konačna rang-lista je osnov za predlog konačne Odluke.

Poslovno predlog utvrđuje **Komisija**. Na Platformi predsjednik Komisije, u ime Komisije, generiše predlog.

Predlog se zasniva na završenom toku i konačnoj rang-listi. Ne smije mijenjati bodove, rang, zaključke ni odobrene iznose.

Predsjednik zatim evidentira predlaganje Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj.

Taj Sekretarijat **nije** profilni Platform akter. Nema prijemnog sandučeta, approval toka ni elektronskog usvajanja Odluke na Platformi.

Obavezan redoslijed:

konačna rang-lista  
→ generisanje predloga  
→ evidentirano predlaganje Sekretarijatu  
→ tek onda zatvaranje.

## 4.14. Zatvaranje Konkursa i automatsko arhiviranje

Predsjednik može zatvoriti Konkurs tek kada je predlaganje Sekretarijatu evidentirano.

Radnju zatvaranja vrši predsjednik Komisije. Kada zatvori, Platforma Konkurs **odmah automatski** arhivira.

Nema posebne radnje Arhiviraj.

Arhiviranje:

* zaključava konkursni / evaluacioni tok;
* ne otvara privatne Prijave;
* **ne** znači da je Sekretarijat već donio konačnu Odluku.

## 4.15. Konačna Odluka i završetak V1

Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj donosi konačnu Odluku **van Platforme**.

Nakon donošenja, Administrator Konkursa objavljuje **već donesenu** Odluku na Platformi.

Administrator Konkursa ne donosi, ne odobrava i ne mijenja sadržaj Odluke.

Objava je dozvoljena nad **arhiviranim** Konkursom. To **nije** ponovno otvaranje Konkursa.

Objavljena Odluka je javni rezultat. Ne otvara kompletne Prijave, biznis planove ni nejavne priloge drugih Podnositeljki.

Objavom konačne Odluke završava se V1 funkcionalni tok.

Van V1 ostaju: pojedinačna Rješenja; pravni lijekovi poslije konačne Odluke; Ugovori; isplata; praćenje; kontrola namjene; naknadne izmjene zbog odustanka.

## 4.16. Normativna granica FS-a

`KN-FS-003` digitalizuje važeću Odluku 027/26 i njene sastavne obrasce.

FS **ne** smije „popravljati“ normativni akt dodavanjem novih poslovnih ili normativnih polja.

Ne uvodi se:

* novi normativni kriterijum;
* nova skala;
* novi pravni lijek;
* novo polje Obrasca 3

samo zato što bi tehnički model bio jednostavniji.

Interni tehnički mehanizmi kojima Platforma sprovodi usvojena pravila pripadaju TS sloju.

---

# 5. Kreiranje i konfiguracija Konkursa

Status poglavlja: USVOJENO

Ovo poglavlje određuje Komisiju u mjeri potrebnoj za konfiguraciju Konkursa, kreiranje konkretne instance i čuvanje / validnost / izmjenu / brisanje te konfiguracije.

Ne određuje mehaniku objave, kanale objave, pravni ili funkcionalni početak roka od 20 dana, ranu javnu vidljivost, automatsko otvaranje ili zatvaranje podnošenja, niti životni ciklus Prijave. To pripada Poglavlju 6, odnosno kasnijim poglavljima.

## 5.1. Komisija u konfiguraciji Konkursa

Komisija postoji **prije** dodjele konkretnom Konkursu. Pri kreiranju / konfiguraciji Konkursa bira se već postojeća odgovarajuća Komisija.

Komisiju formalno imenuje sekretar Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj, Rješenjem (`KN-BM-003` §4.3, §4.5). Administrator Konkursa **ne** imenuje Komisiju putem Platforme. Platforma podržava administrativno evidentiranje / konfiguraciju da bi već imenovana Komisija mogla biti korišćena u konkursnom postupku. Pravni postupak imenovanja se ovim ne redefiniše.

Identifikacioni / konfiguracioni podaci Komisije, bez razrade polje-po-polje, obuhvataju najmanje naziv, godinu i podatke o mandatu.

Mandat Komisije traje **godinu dana** (čl. 6; `KN-BM-003` §4.3). Početak mandata se evidentira. Kraj slijedi iz usvojenog jednogodišnjeg trajanja.

Za ovaj profil Komisija ima **pet članova** u propisanom sastavu prema čl. 6 Odluke 027/26 i `KN-BM-003` §4.3:

1. predsjednik Komisije — predstavnik Opštine Kotor;
2. predstavnik Opštine Kotor iz redova zaposlenih u Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj, koji je sekretar Komisije;
3. drugi predstavnik Opštine Kotor iz redova zaposlenih u tom Sekretarijatu;
4. predstavnica Udruženja preduzetnica Crne Gore ili strukovnih udruženja, ili biznisa, ili akademske zajednice;
5. predstavnica Ženske političke mreže.

Ovih pet pozicija **nijesu** pet novih platformskih uloga. Funkcionalni akteri ostaju oni iz Poglavlja 3.

Pravno konstituisana Komisija ima pet članova. Čl. 6 ostaje autoritativan.

Administrator Konkursa može sačuvati **nepotpun** zapis Komisije prije nego što su poznati svi podaci o članovima i naknadno dopuniti ostale članove. To je samo nepotpun Platform zapis. **Ne** tumači se kao pravno konstituisana Komisija sa manje od pet članova.

Čuvanje koristi običnu radnju **Sačuvaj komisiju**. Ne uvodi se zasebna radnja ni stanje „Završi evidentiranje“. Platforma utvrđuje potpunost / valjanost iz evidentiranih podataka kada kasnija radnja zahtijeva potpunu Komisiju.

Konkursi se planiraju tako da se njihov postupak završi prije isteka mandata dodijeljene Komisije. Ne uvodi se V1 tok oporavka zbog isteka mandata cijele Komisije tokom aktivnog Konkursa.

## 5.2. Članovi Komisije i pristup

Jedan od dva predstavnika tog Sekretarijata je **sekretar Komisije**. To je funkcija / pozicija unutar sastava Komisije. Ne stvara se zasebna platformska uloga niti dodatno Platform ovlašćenje samo zbog te oznake.

Član Komisije za komisijske funkcije koristi **namjenski nalog člana Komisije**.

Administrator Konkursa uspostavlja taj nalog prema usvojenom toku, uključujući početnu lozinku. Član završava primjenjivi postupak verifikacije / aktivacije emaila i može naknadno izmijeniti lozinku.

Ako isto lice već koristi Platformu u drugoj ulozi / svojstvu, taj drugi nalog se **ne** koristi automatski za rad Komisije. Komisijske funkcije se koriste kroz namjenski nalog člana Komisije.

Isti nalog / lice člana Komisije može učestvovati u uzastopnim Komisijama i u Komisijama različitih tipova konkursa. Ne duplicira se lice zbog novog članstva. Članstvo u konkretnoj Komisiji i nalog / identitet lica **funkcionalno su odvojeni**.

Tehnička realizacija autentikacije nije predmet ovog dokumenta.

Primjenjuje se već usvojeno `BM-KN-014`: Administrator Konkursa ne može biti član Komisije istog Konkursa. Namjenski nalog to ne ukida.

Komisija se može evidentirati, dopuniti i dodijeliti **prije** nego što svaki član verifikuje email. Verifikacija emaila **nije** uslov samo za čuvanje / formiranje zapisa Komisije.

Pojedinačni član mora ispuniti potrebnu verifikaciju / aktivaciju **prije** sopstvenih elektronskih radnji Komisije.

## 5.3. Promjene sastava Komisije

### Zamjenski član

Sekretar Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj može, posebnim aktom, imenovati zamjenskog člana zbog odsustva člana Komisije (čl. 6).

Zamjenski član:

* veže se za člana / poziciju koja se zamjenjuje;
* **nije** šesti redovni član Komisije;
* evidentira se na Platformi nakon što relevantno vanjsko imenovanje već postoji.

Imenovanje zamjene prethodi sjednici / radu u kojem zamjena učestvuje. Administrator Konkursa **ne** imenuje zamjenu tokom sjednice Komisije.

Za funkcionalnu istoriju:

* već završene radnje ostaju pripisane članu koji ih je izvršio;
* zamjena obavlja primjenjive **naknadne** radnje zamijenjene pozicije;
* istorijske radnje se ne prepisuju, ne ponavljaju i ne pripisuju zamjeni.

### Prestanak mandata člana i novi član

Ako mandat pojedinačnog člana prestane prije isteka mandata Komisije:

* bivši član i njegove istorijske radnje ostaju sačuvani;
* bivši član se ne prepisuje;
* novi imenovani član zauzima relevantnu poziciju za **naknadni** rad;
* mandat novog člana traje do isteka mandata Komisije.

Izmjena pojedinačnog člana **ne** stvara novu Komisiju.

Platforma može evidentirati primjenjivi prestanak članstva prema čl. 7–10. Pravni postupak razrješenja / imenovanja odvija se van Platforme. Ne uvode se dodatne automatske pravne posljedice. Ne uvodi se novo automatsko zabranjujuće pravilo mimo onoga što je već kanonski uspostavljeno.

Ovo poglavlje **ne** popunjava normativnu prazninu o sudbini već završenih individualnih ocjena (`KN-BM-003` §12.7; `KN-FS-003` §3.4, §4.8).

Sastav i istorija Komisije moraju omogućiti utvrđivanje ko je u relevantnom trenutku zauzimao koju poziciju i izvršio relevantne radnje Komisije.

### Uređivanje i brisanje Komisije

Dok se podaci Komisije još pripremaju, Administrator Konkursa može dopuniti nedostajuće podatke o članovima i ispraviti pogrešne podatke. Ispravka koja ne mijenja identitet člana niti stvarno članstvo ostaje obična korekcija.

Komisija koja još nije istorijski korišćena može se i dalje uređivati. Ako izmjena učini Komisiju nepotpunom, nije dostupna za radnju koja zahtijeva potpunu Komisiju dok se ponovo ne dopuni.

Kada istorijsko učešće Komisije već postoji, stvarna izmjena sastava **ne** vrši se prostim prepisivanjem jednog lica drugim. Koristi se usvojeni tok zamjenskog člana / prestanka mandata / novog člana.

Neiskorišćena Komisija može se obrisati.

Komisija koja je učestvovala u postupku Konkursa **ne** briše se.

Istek mandata **nije** brisanje.

Brisanje neiskorišćene Komisije **ne** briše ponovo upotrebljiv identitet / nalog člana Komisije.

## 5.4. Dodjela Komisije Konkursu

Ista Komisija koristi se za isti tip konkursa dok njen mandat traje. Ista konkretna Komisija može služiti više konkretnih Konkursa odgovarajućeg tipa tokom mandata, tamo gdje profil to dozvoljava, uključujući drugi Konkurs tog tipa (`KN-BM-003` §13.8).

Lice može zasebno učestvovati u različitim Komisijama, uključujući Komisije različitih tipova konkursa.

Pri konfiguraciji Konkursa Administrator Konkursa bira **postojeću** odgovarajuću Komisiju. Dodjela ne stvara novu Komisiju i ne stvara nova članstva.

Komisija mora ispuniti usvojene uslove potpunosti / valjanosti potrebne za stvarno sprovođenje Konkursa.

Član koji nije završio potrebnu verifikaciju naloga ne može obavljati sopstvene elektronske radnje Komisije. To samo po sebi **ne** znači da zapis Komisije ne može postojati ili biti dodijeljen.

Dodijeljena Komisija može se izmijeniti dok Konkurs **još nije** objavljen. Nakon objave, cijela dodijeljena Komisija se **ne** zamjenjuje običnim uređivanjem Konkursa.

Naknadne izmjene pojedinih lica idu usvojenim tokovima zamjenskog člana / prestanka mandata / novog člana. To **nije** zamjena same Komisije.

Objava se ovdje navodi **samo** kao granica poslije koje obična zamjena cijele dodijeljene Komisije više nije dozvoljena. Mehanika objave nije predmet ovog poglavlja.

## 5.5. Kreiranje i konfiguracija instance Konkursa

Administrator Konkursa kreira konkretnu godišnju instancu Konkursa za izabrani tip. Tip određuje primjenjivi poslovni / funkcionalni profil. Za Žensko preduzetništvo to je `KN-BM-003` / `KN-FS-003`.

Gdje pravila profila dozvoljavaju, u istoj godini može postojati više konkretnih Konkursa istog tipa. Drugi Javni konkurs je **zasebna instanca**. Nije ponovno otvaranje prvog Konkursa.

Obični konfiguracioni podaci, bez razrade polje-po-polje, obuhvataju najmanje:

* naziv;
* opis;
* tip Konkursa;
* godinu;
* broj Konkursa;
* ukupan budžet;
* datum početka;
* dodijeljenu Komisiju.

**Broj Konkursa nije automatski redni broj.** To je zavodni broj koji Administrator Konkursa dobija sa pisarnice i unosi na Platformi (`KN-BM-003` §5).

Postojeća sirova HTML implementacija opisa **nije** normativna. Informativni tekst smije se zahtijevati bez propisivanja sirovog HTML uređivanja.

Svaka konkretna instanca ima sopstveni ukupan raspoloživi budžet. Ako je drugi Javni konkurs dozvoljen jer sredstva ostaju, to je zasebna instanca sa sopstvenim raspoloživim iznosom. Nije ponovno otvaranje prvog Konkursa.

Za ovaj profil usvojeno trajanje roka za prijavu je **20 dana** (`KN-BM-003` §6; čl. 5 i čl. 13). Na nivou konfiguracije ovog poglavlja:

* Administrator evidentira relevantnu konfiguraciju datuma početka;
* odgovarajuća vrijednost kraja roka može se izvesti iz propisanog trajanja od 20 dana;
* trajanje od 20 dana **nije** proizvoljno trajanje koje Administrator konfigurira za ovaj profil.

Ovo poglavlje **ne** određuje događaj koji pravno ili funkcionalno pokreće rok od 20 dana, niti učinak isteka roka na Prijave. To pripada Poglavlju 6.

## 5.6. Čuvanje i validnost konfiguracije

**Sačuvaj konkurs** čuva konfiguraciju Konkursa. Čuvanje **nije** objava.

Sačuvani neobjavljeni Konkurs može se i dalje uređivati.

Ne uvodi se zasebna radnja ni stanje „Završi konfiguraciju“.

Model konfiguracije: **Sačuvaj** → uređivanje dok je dozvoljeno → konfiguracija mora biti valjana / potpuna prije nego što smije preći u objavu.

Ovo poglavlje ne određuje kako objava funkcioniše. Mehanika objave pripada Poglavlju 6.

## 5.7. Izmjene i brisanje

Dok Konkurs nije objavljen, konfiguracija se može uređivati u skladu sa usvojenim pravilima ovog poglavlja.

Nakon objave, obično uređivanje **ne smije** mijenjati suštinsku konfiguraciju / uslove Konkursa. To obuhvata zaštićene dimenzije:

* tip Konkursa;
* godinu;
* raspoloživi budžet;
* dodijeljenu Komisiju;
* konfiguraciju vezanu za rok.

Čisto informativne korekcije ostaju moguće ako ne mijenjaju uslove Konkursa. Ne uvodi se novi pravni tok izmjene Javnog konkursa.

Objava se ovdje koristi **samo** kao granica izmjenjivosti konfiguracije.

Neobjavljeni Konkurs bez relevantnog istorijskog učešća može se obrisati.

Nakon objave obično brisanje **nije** dozvoljeno.

Arhiviranje **nije** brisanje. Kasniji tok arhiviranja nije predmet ovog poglavlja, osim te razlike.

---


# 6. Objavljivanje i rok za Prijave

Status poglavlja: USVOJENO

Ovo poglavlje određuje objavljivanje Konkursa na Platformi i rok za podnošenje Prijava.

## 6.1. Uslovi i radnja objavljivanja

Konkurs se može objaviti samo kada je njegova konfiguracija potpuna i valjana prema pravilima profila Žensko preduzetništvo.

Čuvanje i objavljivanje su **zasebne** radnje.

**Sačuvaj konkurs** **ne** objavljuje Konkurs.

Objavljivanje je zasebna radnja **Administratora Konkursa**.

Prije objave Platforma provjerava obavezne uslove objave. Ako uslovi nijesu ispunjeni, objava se **odbija** i Administratoru Konkursa se prikazuje šta nedostaje.

Objavom Konkurs prelazi:

**Nacrt → Objavljen.**

## 6.2. Početak i trajanje roka za Prijave

Objavljivanje **pokreće** rok za podnošenje Prijava.

Datum objave je istovremeno datum početka roka za prijavu.

Za Žensko preduzetništvo rok za prijavu traje **20 kalendarskih dana**, računajući od datuma objave.

20 dana **ne** tumači se kao 480 sati od tačnog časa objave.

Rok ističe **23:59:59** posljednjeg kalendarskog dana.

Administrator Konkursa **ne** može običnim upravljanjem Konkursom proizvoljno skratiti niti produžiti propisanih 20 dana.

Nema zasebnog razdoblja u kojem je Konkurs objavljen, a podnošenje Prijava još nije počelo.

## 6.3. Kanali objavljivanja

Platforma omogućava objavu Konkursa na Digital Kotoru.

Objava / oglašavanje putem ostalih kanala koje propisuju relevantni akti odvija se **van Platforme**.

Platforma te vanjske kanale **ne** vodi i **ne** automatizuje.

Objava na Platformi **sama po sebi ne potvrđuje** da je objava kroz sve ostale propisane kanale izvršena.

## 6.4. Prikaz objavljenog Konkursa i roka

Nakon objave status Konkursa je **Objavljen**.

Prikaz objavljenog Konkursa sadrži:

* Status;
* Budžet;
* Komisija;
* Rok za prijave;
* Datum objave;
* Datum početka;
* Datum isteka roka za prijavu;
* Preostalo vremena;
* Opis konkursa;
* informativni blok o roku za prijavu, sa preostalim danima i datumom / vremenom isteka.

U odnosu na Nacrt, objava uspostavlja i prikazuje konkretne vremenske podatke o početku i isteku roka za prijavu.

Tokom cijelog roka za prijavu Konkurs ostaje **Objavljen**.

## 6.5. Istek roka za Prijave

U **23:59:59** posljednjeg dana roka za prijavu, rok za podnošenje Prijava prestaje.

Od tog trenutka Platforma **automatski** sprečava podnošenje novih Prijava.

Za prestanak podnošenja **nije** potrebna radnja Administratora Konkursa.

Platforma više **ne** prikazuje aktivnu vrijednost preostalog vremena za podnošenje Prijava. Prikazuje:

**ISTEKLO**

Istek roka za prijavu **ne** mijenja status Konkursa. Konkurs ostaje **Objavljen**.

Pristup Komisije nakon isteka roka za prijavu: Poglavlje 9 — Istek roka i pristup Komisije.

Kasniji tok Konkursa, uključujući rok za odlučivanje / zatvaranje, zatvaranje i Arhivu: Poglavlje 15 — Predlog Odluke, zatvaranje, arhiva i objava.

---

# 7. Prijava Podnositeljke

Status poglavlja: USVOJENO

Ovo poglavlje određuje tok Prijave sa strane Podnositeljke: nastanak, popunjavanje Obrazaca 1a / 1b i Obrasca 2, prateću dokumentaciju, podnošenje, zaključavanje i brisanje.

Ne određuje pristup Komisije, administrativnu provjeru, Prigovor, ocjenjivanje, rangiranje ni Odluku. Ti tokovi pripadaju Poglavljima 8–15.

Osnov: `KN-BM-003` §4.1, §7; čl. 13 i čl. 18 Odluke 027/26.

## 7.1. Nastanak Prijave

Podnositeljka može započeti Prijavu samo za Konkurs u stanju **Objavljen**, dok rok za prijavu traje.

Prijava pripada jednoj konkretnoj Podnositeljki i jednom konkretnom Konkursu. Ne može se prenijeti na drugu Podnositeljku niti na drugi Konkurs.

Nova Prijava se ne može započeti nakon isteka roka za prijavu.

Nakon izbora faze biznisa i radnje **Prijavi se na konkurs** počinje tok Prijave Podnositeljke.

Ovo poglavlje ne propisuje trenutak tehničkog nastanka zapisa niti ponašanje baze podataka.

## 7.2. Jedna Prijava po konkretnom Konkursu

Za jedan konkretni Konkurs jedna Podnositeljka može imati najviše jednu postojeću Prijavu (`KN-BM-003` §7; čl. 18).

Ako Prijava već postoji, Platforma nastavlja tu Prijavu. Ne stvara drugu Prijavu za isti konkretni Konkurs.

Pravilo važi nezavisno za svaku konkretnu instancu Konkursa.

Izuzetak: ako se Prijava u stanju **U pripremi** obriše dok je brisanje još dozvoljeno (§7.18), Prijava tada ne postoji. Podnositeljka može započeti novu Prijavu za isti konkretni Konkurs dok rok za prijavu još traje.

## 7.3. Čuvanje tokom popunjavanja

Prijava se može popunjavati kroz više sesija.

Čuvanje nepotpunog sadržaja:

* **nije** konačno podnošenje;
* čuva unesene podatke Podnositeljke;
* omogućava nastavak dok rok za prijavu traje.

Ne uvodi se dodatna opšta kapija „podobnosti za podnošenje“ mimo uslova iz §7.14.

Razlikuju se:

* stanje Prijave: **U pripremi** / **Podnesena**;
* poslovno stanje popunjenosti pojedinačnog Obrasca: **Nacrt** / **Popunjen**.

Prijava u toku popunjavanja, prije uspješnog podnošenja, jeste **U pripremi**. Prijava **nije** Nacrt.

## 7.4. Tip Podnositeljke i faza biznisa

Tip Podnositeljke dolazi sa Platforme. Podnositeljka **ne** bira ručno tip Podnositeljke unutar Prijave.

Mapiranje:

* fizičko lice bez registrovane djelatnosti i preduzetnica → **Obrazac 1a**;
* društvo (DOO) → **Obrazac 1b**.

Podnositeljka bira fazu biznisa.

Tip Podnositeljke i faza biznisa određuju:

* primjenjivi tok Prijave;
* primjenjivi skup prateće dokumentacije prema čl. 13 (§7.13).

Nakon izbora, faza biznisa je zaključana za tu Prijavu.

Tip Podnositeljke je zaključan za tu Prijavu.

Nalog društva (DOO) na Platformi **ne** znači nužno da formalno registrovano društvo već postoji.

Žena koja namjerava da osnuje društvo može koristiti tok Podnositeljke društva i prije formalne registracije društva.

Postojanje registrovanog biznisa je zaseban uslov. Razlikuje se od tipa naloga / tipa Podnositeljke.

Ne uvodi se tip Podnositeljke van navedenog mapiranja.

## 7.5. Zajedničko ponašanje Obrasca 1a i Obrasca 1b

Platforma otvara Obrazac 1a ili Obrazac 1b prema tipu naloga.

Poznati podaci profila se automatski popunjavaju.

Faza biznisa se prenosi automatski.

Podatke koji nijesu dostupni iz profila unosi Podnositeljka.

Automatski popunjeni podaci su **početne** vrijednosti.

**JMBG:**

* automatski se popunjava;
* zaključan je.

Ostali usvojeni automatski popunjeni podaci Obrasca su izmjenjivi za konkretnu Prijavu, osim ako su izričito zaključani.

Izmjena podataka Obrasca:

* mijenja konkretnu Prijavu;
* **ne** mijenja profil na Platformi.

Tip Podnositeljke: zaključan.

Faza biznisa: zaključana.

Sačuvane vrijednosti Obrasca postaju podaci konkretne Prijave.

Naknadne izmjene profila **ne** mijenjaju automatski već sačuvane podatke Prijave.

## 7.6. Stanje popunjenosti Obrasca 1a / 1b

Svaki Obrazac 1a / 1b ima poslovna stanja popunjenosti:

* **Nacrt**;
* **Popunjen**.

Ako obavezna polja ili obavezna izjava o istinitosti nijesu potpuni, Obrazac je **Nacrt**.

Kada su sva obavezna / primjenjiva polja i obavezna izjava potpuni, Obrazac je **Popunjen**.

**Popunjen** Obrazac **ne** znači da je Prijava **Podnesena**.

Ne zahtijeva se posebna implementacija statusa u bazi podataka za popunjenost Obrasca.

Obavezna izjava:

*Kao podnositeljka prijave pod punom materijalnom i krivičnom odgovornošću izjavljujem da su gore navedeni podaci istiniti.*

## 7.7. Obrazac 1a

Obrazac 1a primjenjuje se na:

* fizičko lice bez registrovane djelatnosti;
* preduzetnicu.

Uvijek obavezno:

* Naziv biznis plana;
* Ime i prezime;
* JMBG;
* Kontakt telefon;
* Adresa;
* E-mail;
* Oblast u kojoj planira realizaciju biznis plana;
* Faza biznisa;
* izjava o istinitosti (§7.6).

Izmjenjivost:

* Naziv biznis plana: izmjenjiv;
* Ime i prezime: automatski popunjeno, izmjenjivo;
* JMBG: automatski popunjen, zaključan;
* Kontakt telefon: automatski popunjen, izmjenjiv;
* Adresa: automatski popunjena, izmjenjiva;
* E-mail: automatski popunjen, izmjenjiv;
* Oblast realizacije: izmjenjiva;
* Faza biznisa: prenesena iz prethodnog izbora, zaključana.

Nijesu bezuslovno obavezni:

* Oblik registracije;
* CRPS;
* PIB.

Ova polja zavise od toga da li registrovana djelatnost postoji.

Opcioni podaci trenutnog digitalnog Obrasca:

* Broj računa;
* PDV broj;
* Website.

Tip Podnositeljke: izveden iz naloga; ne bira se ručno.

## 7.8. Obrazac 1b

Obrazac 1b primjenjuje se na tok Podnositeljke društva (DOO).

Nalog društva **ne** dokazuje formalnu registraciju društva.

Uvijek obavezno i izmjenjivo:

* Naziv biznis plana;
* Ime i prezime nositeljke biznisa;
* Kontakt telefon;
* Adresa;
* E-mail;
* Oblast realizacije biznis plana.

Automatsko popunjavanje gdje podaci Platforme postoje.

Uvijek obavezno i zaključano:

* JMBG;
* Tip Podnositeljke;
* Faza biznisa.

Obavezna je izjava o istinitosti (§7.6).

Ako registrovani biznis postoji, postaju primjenjiva / obavezna:

* Oblik registracije;
* Broj registracije u CRPS;
* Osnivač/ica;
* Izvršni direktor/ica;
* Sjedište društva;
* PIB.

Vrijednosti izvedene iz zaključanog identiteta / tipa Podnositeljke ostaju zaključane gdje je to primjenjivo. Ostali konkretni podaci Prijave uređuju se prema §7.5.

Opciono:

* Broj računa;
* PDV broj;
* Website.

Ako registrovani biznis ne postoji, odsustvo polja registracije **ne** sprečava da Obrazac 1b postane **Popunjen**.

## 7.9. Prelazak na Obrazac 2

Redovni tok Podnositeljke dozvoljava prelazak na Obrazac 2 tek kada je odgovarajući Obrazac 1a / 1b **Popunjen**.

Obrazac 1a / 1b u stanju **Nacrt**:

* može se sačuvati;
* ne dozvoljava redovni prelazak na Obrazac 2.

Otvaranje Obrasca 2:

* **nije** podnošenje Prijave;
* stvara ili nastavlja Obrazac 2 koji pripada istoj konkretnoj Prijavi.

Jedna Prijava ima jedan Obrazac 2.

## 7.10. Obrazac 2 — tehnička kompletnost

Obrazac 2 ima poslovna stanja popunjenosti:

* **Nacrt**;
* **Popunjen**.

**Q1 — Naziv biznis ideje**

* obavezno;
* početno se preuzima iz sačuvanog Obrasca 1a / 1b, polje *Naziv biznis plana*;
* izmjenjivo unutar Obrasca 2;
* izmjena vrijednosti u Obrascu 2 ne mijenja Obrazac 1a / 1b.

**Q2 — Podaci Podnositeljke**

Obavezno:

* ime;
* JMBG;
* adresa;
* telefon;
* e-mail.

Izvor: sačuvana konkretna Prijava, **ne** živi profil.

Izmjenjivo u Obrascu 2:

* ime;
* adresa;
* telefon;
* e-mail.

Zaključano:

* JMBG.

Izmjene utiču samo na Obrazac 2.

**Q3 — Da li imate registrovan biznis?**

* obavezno;
* automatski se izvodi iz konkretne Prijave;
* zaključano.

Ako je odgovor DA, blok registrovanog biznisa postaje primjenjiv.

Ako je odgovor NE, polja registrovanog biznisa nijesu obavezna.

**Q4 — Podaci registrovanog biznisa**

* prenose se iz Obrasca 1a / 1b;
* obaveznost prati primjenjiva pravila Obrasca 1a / 1b;
* izmjenjivo, osim vrijednosti koje su već određene kao zaključane;
* izmjene u Obrascu 2 ne mijenjaju Obrazac 1a / 1b niti profil.

**Q5 — Rezime**

* obavezno;
* unosi ga Podnositeljka;
* izmjenjivo do konačnog podnošenja.

**Napomena o finansijama**

Obavezna potvrda:

*Potvrđujem da sam pročitala napomenu*

Ta potvrda je dio tehničke kompletnosti Obrasca 2.

Tehničko polje implementacije, samo radi sljedivosti: `finances_notice_confirmed`.

**Ostali sadržaj biznis plana**

Ostala suštinska / bodovana pitanja **nijesu** pojedinačno obavezna za tehničku kompletnost Obrasca 2.

Neodgovorena suštinska / bodovana pitanja sama po sebi **ne** sprečavaju da Obrazac 2 postane **Popunjen**.

Prije konačnog podnošenja Platforma može upozoriti Podnositeljku da suštinski sadržaj biznis plana nije potpun i da to može uticati na ocjenjivanje.

Ovo poglavlje **ne** određuje posljedice ocjenjivanja ni ponašanje Komisije.

Tačno:

* nedostatak tehnički obaveznog sadržaja → Obrazac 2 ostaje **Nacrt**;
* nedostatak neobaveznog suštinskog / bodovanog sadržaja → **ne** čini Obrazac 2 tehnički nepotpunim.

## 7.11. Čuvanje i uređivanje Obrasca 2

Nepotpun Obrazac 2 može se sačuvati kao **Nacrt**.

Čuvanje Obrasca 2 **nije** podnošenje Prijave.

Kada je tehnička kompletnost ispunjena, Obrazac 2 je **Popunjen**.

**Popunjen** Obrazac 2 ostaje izmjenjiv dok:

* Prijava nije podnesena;
* rok za prijavu još traje.

Ponovno čuvanje ažurira isti Obrazac 2. Ne stvara se drugi Obrazac 2.

Nakon konačnog podnošenja Podnositeljka ne može mijenjati Obrazac 2 (§7.16).

## 7.12. Prateća dokumentacija — opšte pravilo

Prateća dokumentacija pripada istoj konkretnoj Prijavi.

Platforma prikazuje primjenjivi skup dokumenata na osnovu:

* tipa Podnositeljke;
* faze biznisa;
* primjenjivih uslovnih činjenica.

Dokumenti mogu biti:

* obavezni;
* uslovno obavezni;
* opcioni / dokaz za dodatne bodove.

**Obavezan dokument** znači: propisan Konkursom.

**Ne** znači: tehnička prepreka konačnog podnošenja.

Ako nedostaju obavezni ili primjenjivi uslovni dokumenti:

* Platforma upozorava Podnositeljku prije konačnog podnošenja;
* nedostajuće stavke se nabrajaju;
* Podnositeljka i dalje može nastaviti konačno podnošenje.

Opcioni dokumenti / dokazi za dodatne bodove nijesu tehnički uslov podnošenja.

Ovo poglavlje **ne** određuje bodovanje opcionog dokaza.

Upravljanje dokumentima prije podnošenja:

* jedan dokument po tipu dokumenta;
* više fajlova za isti dokaz može se spojiti u jedan dokument;
* Podnositeljka može dodati dokumente;
* Podnositeljka može ukloniti dokumente;
* zamjena = uklanjanje postojećeg dokumenta + učitavanje zamjene.

Nakon konačnog podnošenja nema dodavanja, uklanjanja ni zamjene (§7.16).

## 7.13. Katalog prateće dokumentacije (čl. 13)

Za sva četiri skupa važi opšte pravilo upozorenja i dozvole iz §7.12. Nedostajući dokumenti se **ne** pretvaraju u prepreku konačnog podnošenja.

Obrazac 1a, Obrazac 1b i Obrazac 2 su obavezni digitalni Obrasci Prijave, nisu prilozi za učitavanje.

### A. Preduzetnica koja započinje biznis

1. Obrazac 1a — obavezni digitalni Obrazac
2. Obrazac 2 — obavezni digitalni Obrazac
3. Ovjerena kopija lične karte — obavezno
4. CRPS — uslovno ako postoji registrovana djelatnost
5. Registracija kod Poreske uprave — uslovno ako postoji registrovana djelatnost
6. Dokaz o PDV statusu — uslovno ako postoji registrovana djelatnost; odgovarajuća varijanta prema PDV statusu
7. Potvrda Osnovnog suda da se ne vodi krivični postupak — obavezno
8. Uvjerenje o lokalnim obavezama, ne starije od 30 dana — obavezno
9. Uvjerenje o porezu na nepokretnost, ne starije od 30 dana — obavezno
10. Dokaz o poslovnom žiro-računu — uslovno ako postoji registrovana djelatnost
11. Dokaz Zavoda za zapošljavanje o evidenciji dužoj od 12 mjeseci — opciono / dokaz za dodatne bodove
12. Predračuni — obavezno

### B. Preduzetnica koja planira razvoj poslovanja

1. Obrazac 1a
2. Obrazac 2
3. Ovjerena kopija lične karte — obavezno
4. CRPS — obavezno
5. Registracija kod Poreske uprave — obavezno
6. Dokaz o PDV statusu — obavezno; odgovarajuća alternativa prema PDV statusu
7. Potvrda Osnovnog suda — obavezno
8. Uvjerenje o lokalnim obavezama, ne starije od 30 dana — obavezno
9. Uvjerenje o porezu na nepokretnost, ne starije od 30 dana — obavezno
10. Potvrda Poreske uprave o porezima i doprinosima, ne starija od 30 dana — obavezno
11. IOPPD ili potvrda Poreske uprave da nema zaposlenih — obavezna alternativa
12. Dokaz o poslovnom žiro-računu — obavezno
13. Dokaz Zavoda za zapošljavanje o evidenciji dužoj od 12 mjeseci — opciono / dokaz za dodatne bodove
14. Predračuni — obavezno

### C. Društvo koje započinje biznis

1. Obrazac 1b
2. Obrazac 2
3. Ovjerena kopija lične karte nositeljke biznisa — obavezno
4. CRPS — uslovno ako postoji registrovana djelatnost
5. Registracija kod Poreske uprave — uslovno ako postoji registrovana djelatnost
6. Dokaz o PDV statusu — uslovno ako postoji registrovana djelatnost; odgovarajuća varijanta prema PDV statusu
7. Važeći Statut — uslovno ako postoji registrovana djelatnost
8. Važeći karton deponovanih potpisa — uslovno ako postoji registrovana djelatnost
9. Potvrda Osnovnog suda za Podnositeljku / nositeljku biznisa — obavezno
10. Uvjerenje o lokalnim poreskim obavezama, ne starije od 30 dana — obavezno
11. Uvjerenje o porezu na nepokretnost, ne starije od 30 dana — obavezno
12. Dokaz Zavoda za zapošljavanje o evidenciji dužoj od 12 mjeseci — opciono / dokaz za dodatne bodove
13. Predračuni — obavezno

### D. Društvo koje planira razvoj poslovanja

1. Obrazac 1b
2. Obrazac 2
3. Ovjerena kopija lične karte nositeljke biznisa — obavezno
4. CRPS — obavezno
5. Registracija kod Poreske uprave — obavezno
6. Dokaz o PDV statusu — obavezno; odgovarajuća alternativa prema PDV statusu
7. Važeći Statut — obavezno
8. Važeći karton deponovanih potpisa — obavezno
9. Paket godišnjih računa za prethodnu godinu — obavezno:
   * Bilans stanja;
   * Bilans uspjeha;
   * analitika kupaca;
   * analitika dobavljača.

   Ako analitika kupaca ne postoji zato što su kupci isključivo fizička lica / neposredna kasa, periodični izvještaj kase prihvata se kao alternativni dokaz unutar ove stavke.

10. Dokaz da se ne vodi krivični postupak za nositeljku **i** društvo — obavezno
11. Dokaz o lokalnim porezima, ne stariji od 30 dana, za nositeljku **i** društvo — obavezno
12. Dokaz o porezu na nepokretnost, ne stariji od 30 dana, za nositeljku **i** društvo — obavezno
13. Potvrda Poreske uprave o porezima i doprinosima, ne starija od 30 dana, za nositeljku **i** društvo — obavezno
14. IOPPD za posljednji mjesec — obavezno
15. Dokaz Zavoda za zapošljavanje o evidenciji dužoj od 12 mjeseci — opciono / dokaz za dodatne bodove
16. Predračuni — obavezno

## 7.14. Podnošenje Prijave

Konačno podnošenje je dozvoljeno samo kada su ispunjeni svi sljedeći uslovi:

1. odgovarajući Obrazac 1a / 1b je **Popunjen**;
2. Obrazac 2 je **Popunjen**;
3. tehnička kompletnost Obrasca 2 uključuje potvrdu napomene o finansijama (§7.10);
4. Konkurs je **Objavljen** i rok za prijavu još traje;
5. Podnositeljka izričito potvrđuje konačno podnošenje.

Nedostajuća prateća dokumentacija **ne** blokira podnošenje.

Ako nedostaju obavezni ili primjenjivi uslovni dokumenti, prije konačne potvrde prikazuje se upozorenje i spisak nedostajućih stavki (§7.12).

Konačna potvrda mora jasno obavijestiti Podnositeljku da nakon uspješnog konačnog podnošenja:

* Prijava postaje **Podnesena**;
* Prijava postaje zaključana;
* ne može se mijenjati;
* ne može se povući;
* ne može se obrisati.

Podnositeljka mora izričito potvrditi.

Ako Podnositeljka odustane, Prijava ostaje **U pripremi**.

Ne propisuje se da li je potvrda modal ili stranica.

## 7.15. Uspješno podnošenje

Nakon uspješnog podnošenja na strani servera:

* Prijava prelazi: **U pripremi → Podnesena**;
* evidentira se datum / vrijeme uspješnog podnošenja;
* dodjeljuje se `redni_broj` unutar konkretnog Konkursa.

`redni_broj` se **ne** dodjeljuje:

* pri otvaranju Obrasca 1a / 1b;
* pri početku popunjavanja;
* pri čuvanju Obrasca 1a / 1b;
* samo zato što Obrazac postane **Popunjen**;
* pri otvaranju ili čuvanju Obrasca 2.

Jednom dodijeljen, `redni_broj` ostaje nepromijenjen.

Podnositeljka prima potvrdu da je podnošenje uspjelo.

Ne zahtijeva se prikaz `redni_broj` u toj potvrdi.

Prijava se smatra **Podnesenom** tek nakon uspješne serverske operacije.

## 7.16. Zaključavanje nakon podnošenja

Nakon što Prijava postane **Podnesena**, Podnositeljka je može pregledati.

Podnositeljka ne može:

* uređivati Obrazac 1a / 1b;
* uređivati Obrazac 2;
* mijenjati tip Podnositeljke;
* mijenjati fazu biznisa;
* dodati prateću dokumentaciju;
* ukloniti prateću dokumentaciju;
* zamijeniti prateću dokumentaciju;
* obrisati Prijavu;
* povući Prijavu;
* ponovo podnijeti Prijavu.

Naknadne izmjene profila na Platformi **ne** mijenjaju snimak podnesene Prijave.

U V1 nema povlačenja Podnesene Prijave sa strane Podnositeljke.

## 7.17. Prijava U pripremi nakon isteka roka

Ako je Prijava još **U pripremi** kada rok za prijavu istekne:

* ostaje evidentirana kao **U pripremi**;
* ne prelazi automatski u drugo stanje;
* **nije** podnesena;
* Podnositeljka je može pregledati.

Podnositeljka više ne može:

* uređivati Obrazac 1a / 1b;
* uređivati Obrazac 2;
* mijenjati tip Podnositeljke;
* mijenjati fazu biznisa;
* dodati, ukloniti ili zamijeniti prateću dokumentaciju;
* podnijeti Prijavu;
* obrisati Prijavu.

Ovo poglavlje ne određuje trajanje čuvanja ni arhiviranja.

## 7.18. Brisanje prije podnošenja

Podnositeljka može obrisati Prijavu samo kada su istovremeno ispunjena oba uslova:

* Prijava je **U pripremi**;
* rok za prijavu još traje.

Brisanje uklanja tu konkretnu nepodnesenu Prijavu.

Nakon brisanja, dok rok za prijavu ostaje otvoren, Podnositeljka može započeti novu Prijavu za isti konkretni Konkurs.

**Podnesena** Prijava se ne može obrisati.

Prijava **U pripremi** nakon isteka roka ne može se obrisati.

---

# 8. Privatnost Prijava

Status poglavlja: USVOJENO

Ovo poglavlje određuje privatnost i pristup Prijavama **dok rok za prijavu traje**.

Granica poglavlja je istek roka za prijavu.

Pravila pristupa nakon isteka roka za prijavu određuju se u narednom poglavlju.

Osnov: `KN-BM-003` §4.1, §4.4, §7.1, §8.

## 8.1. Opšte pravilo

Dok rok za prijavu traje, pojedinačna Prijava je privatna, bez obzira na to da li je njeno stanje:

* **U pripremi**;
* **Podnesena**.

Prelazak **U pripremi → Podnesena** sam po sebi **ne** otvara pristup Prijavi.

Podnesena Prijava ostaje privatna do isteka roka za prijavu.

## 8.2. Pristup po akterima

### Podnositeljka

Podnositeljka ima pristup **samo sopstvenoj** Prijavi, uključujući podatke Prijave, Obrazac 1a / Obrazac 1b, Obrazac 2 / Biznis plan i prateću dokumentaciju.

Ne može pristupiti Prijavi druge Podnositeljke, njenom identitetu kroz Prijavu, stanju tuđe Prijave, tuđim Obrascima, tuđem Biznis planu ni tuđoj pratećoj dokumentaciji.

Ovo poglavlje ne mijenja pravila uređivanja iz Poglavlja 7. Predmet je samo pristup / privatnost.

### Administrator konkursa

Dok rok za prijavu traje, Administrator konkursa može vidjeti **samo zbirni ukupan broj evidentiranih Prijava** konkretnog Konkursa.

Taj zbir:

* obuhvata evidentirane Prijave tog Konkursa;
* **ne** dijeli se na **U pripremi** i **Podnesena**;
* **ne** otkriva identitet Podnositeljke;
* **ne** prikazuje listu pojedinačnih Prijava;
* **ne** prikazuje pojedinačne metapodatke Prijave;
* **ne** omogućava prelazak na pojedinačnu Prijavu.

Dok rok traje, Administrator konkursa **nema** pristup pojedinačnoj Prijavi, identitetu Podnositeljke kroz tu Prijavu, pojedinačnom stanju, datumu / vremenu podnošenja, tipu Podnositeljke ili fazi biznisa kroz pojedinačnu Prijavu, pojedinačnim iznosima ili drugim metapodacima Prijave, Obrascu 1a / 1b, Obrascu 2 / Biznis planu, metapodacima prateće dokumentacije ni fajlovima prateće dokumentacije.

### Komisija

Dok rok za prijavu traje, članovi Komisije **nemaju** pristup informacijama o Prijavama.

Ne vide zbirni broj Prijava, listu Prijava, identitet Podnositeljki, pojedinačna stanja, pojedinačne metapodatke, Obrazac 1a / 1b, Obrazac 2 / Biznis plan, metapodatke prateće dokumentacije ni fajlove prateće dokumentacije.

Činjenica da je Prijava podnesena prije isteka roka **ne** čini je vidljivom Komisiji dok rok traje.

### Administrator platforme i Super administrator

Administrator platforme i Super administrator **ne** stiču poslovno pravo uvida u sadržaj pojedinačnih Prijava dok rok za prijavu traje samo zato što imaju platformsku administratorsku ulogu.

Platformska tehnička privilegija sama po sebi **nije** ovlašćenje za uvid u sadržaj Prijava Konkursa.

## 8.3. Obuhvat zaštićenog sadržaja

Privatnost važi za Prijavu kao cjelinu.

Zaštićeni sadržaj obuhvata najmanje:

* identitet Podnositeljke i pojedinačne podatke Podnositeljke izložene kroz Prijavu;
* stanje Prijave na nivou pojedinačne Prijave;
* pojedinačne metapodatke Prijave;
* Obrazac 1a / Obrazac 1b;
* Obrazac 2 / Biznis plan;
* metapodatke prateće dokumentacije;
* učitane fajlove prateće dokumentacije.

Prateća dokumentacija prati isto pravilo privatnosti kao Prijava kojoj pripada.

## 8.4. Sprovođenje na strani servera

Ograničenja pristupa sprovode se na strani servera.

Nije dovoljno sakriti stavku navigacije, dugme, tabelu ili vezu u korisničkom interfejsu.

Poznavanje ili pogađanje identifikatora Prijave, identifikatora dokumenta, rute ili URL-a **ne** smije zaobići usvojena pravila pristupa.

Ovo poglavlje određuje obavezno ponašanje, ne tehnički mehanizam sprovođenja.

---

# 9. Istek roka i pristup Komisije

Status poglavlja: USVOJENO

Ovo poglavlje određuje istek roka za podnošenje Prijava i pristup Komisije Prijavama **nakon** isteka tog roka.

Pravila privatnosti iz Poglavlja 8 ostaju na snazi dok rok konkretnog Konkursa ne istekne.

Ovo poglavlje **ne** određuje prvu sjednicu, administrativnu provjeru, Prigovor ni kasniji rad Komisije. Ti tokovi pripadaju narednim poglavljima.

Osnov: `KN-BM-003` §6, §8; Poglavlja 6 i 8 ovog dokumenta.

## 9.1. Istek roka

Rok za podnošenje Prijava ističe automatski prema roku utvrđenom za konkretni Konkurs.

Za istek **nije** potrebna radnja Administratora konkursa, Komisije, Administratora platforme ni drugog aktera.

Istek sam po sebi **ne**:

* mijenja stanje Konkursa;
* mijenja stanje Prijave;
* pretvara Prijavu **U pripremi** u drugo stanje;
* pretvara Prijavu **Podnesena** u drugo stanje.

Konkurs zato može ostati u postojećem stanju, uključujući **Objavljen**, i nakon što je rok za prijavu istekao.

Promjena stanja Konkursa **nije** preduslov isteka roka.

Podnošenje Prijave nakon isteka roka uređeno je Poglavljem 7. Ovo poglavlje to ne ponavlja.

## 9.2. Prijave dostupne Komisiji

Nakon isteka roka za prijavu konkretnog Konkursa, pristup Komisije se otključava **automatski**.

Nema zasebne ručne radnje otključavanja. Takva radnja **nije** dozvoljena.

Komisiji postaju dostupne **samo Prijave u stanju Podnesena** tog konkretnog Konkursa.

Prijave u stanju **U pripremi** ostaju nevidljive Komisiji i nakon isteka roka.

Za Prijavu **U pripremi** Komisija ne smije dobiti:

* vidljivost na listi;
* pristup pojedinačnoj Prijavi;
* identitet Podnositeljke kroz tu Prijavu;
* metapodatke Prijave;
* Obrazac 1a / Obrazac 1b;
* Obrazac 2 / Biznis plan;
* metapodatke prateće dokumentacije;
* pregled fajla prateće dokumentacije.

Poznavanje ili pogađanje identifikatora Prijave, identifikatora dokumenta, rute ili URL-a **ne** smije zaobići ovo pravilo.

Dok rok traje, Komisija **nema** pristup informacijama o Prijavama, uključujući zbirni broj Prijava, u skladu sa Poglavljem 8.

Nakon isteka roka Komisija može vidjeti **broj Podnesenih Prijava** koje su joj dostupne za konkretni Konkurs. Taj broj **ne** obuhvata Prijave **U pripremi**.

## 9.3. Ovlašćenje člana Komisije

Pristup nakon isteka roka imaju **samo aktivni članovi Komisije** dodijeljene konkretnom Konkursu.

Član:

* druge Komisije;
* sa neaktivnim članstvom;
* bez dodjele kroz Komisiju odgovornu za taj Konkurs

**ne** stječe pristup Prijavama.

Ako je jedna Komisija dodijeljena više Konkursima, rok i pristup za svaki Konkurs utvrđuju se **zasebno**.

Istek roka Konkursa A **ne** otključava Prijave Konkursa B čiji rok još nije istekao.

Pristup je zato:

* po konkretnom Konkursu;
* zavisan od isteka roka tog Konkursa;
* zavisan od aktivnog članstva u Komisiji tog Konkursa.

## 9.4. Obuhvat pristupa Komisije

Za ovlašćenu **Podnesenu** Prijavu, nakon isteka roka, Komisija može pristupiti cjelovitom sadržaju Prijave potrebnom za njen kasniji rad, uključujući:

* identitet Podnositeljke i podatke Podnositeljke izložene kroz Prijavu;
* pojedinačne podatke i metapodatke Prijave;
* Obrazac 1a / Obrazac 1b;
* Obrazac 2 / Biznis plan;
* metapodatke prateće dokumentacije;
* **pregled** fajlova prateće dokumentacije.

Prateća dokumentacija je Komisiji **preglediva** kroz Platformu.

Članovi Komisije **ne** smiju preuzimati fajlove prateće dokumentacije.

Ograničenje pregleda i zabrana preuzimanja sprovode se na strani servera.

## 9.5. Administrator konkursa i zabrana ranog otključavanja

Istek roka za prijavu **sam po sebi ne** daje Administratoru konkursa novo pravo pristupa sadržaju pojedinačne Prijave.

Prava i odgovornosti Administratora konkursa u vezi sa administrativnom provjerom određuju se u Poglavlju 10.

Pristup Komisije pokreće **istek roka za prijavu konkretnog Konkursa**.

Nema zasebnog ručnog otključavanja.

Promjena stanja Konkursa **nije** alternativni mehanizam za obilaženje roka.

Zatvaranje, arhiviranje ili druga sadašnja ili buduća oznaka stanja Konkursa **ne** otključava Komisiji pristup Prijavama prije stvarnog isteka roka za prijavu tog Konkursa.

Pravila privatnosti iz Poglavlja 8 ostaju na snazi do stvarnog isteka roka.

---

# 10. Prva sjednica, administrativna provjera i Prigovor

Status poglavlja: USVOJENO

Ovo poglavlje određuje prvu sjednicu Komisije, administrativnu provjeru potpunosti Podnesenih Prijava i Prigovor na rezultat te provjere.

Ne određuje eliminatorne kriterijume 2 i 3, usmeno obrazloženje, ocjenjivanje, rangiranje ni Odluku. Ti tokovi pripadaju narednim poglavljima.

Nedostatak obavezne dokumentacije (eliminatorni kriterijum 1 iz čl. 19) funkcionalno se obrađuje **samo** kroz administrativnu provjeru i Prigovor ovog poglavlja. Ne uvodi se druga elektronska provjera istog razloga.

Osnov: `KN-BM-003` §4.3, §9, §10; Poglavlja 4.5, 7 i 9 ovog dokumenta.

## 10.1. Prva sjednica Komisije

Komisija održava prvu sjednicu najkasnije u roku od **7 dana** od isteka roka za podnošenje Prijava.

Predmet ove faze je **administrativna provjera** Podnesenih Prijava konkretnog Konkursa.

Prva sjednica je poslovni / postupovni događaj.

V1 **ne** zahtijeva zaseban entitet, model ni tok sjednice na Platformi. Ne uvodi se otvaranje / zatvaranje sjednice, evidencija prisustva, zapisnik ni ručna Platform radnja kojom se otključava administrativna provjera.

Funkcionalnost Platforme u ovom poglavlju usmjerena je na **evidentiranje rezultata administrativne provjere po Prijavi**.

## 10.2. Administrativna provjera

Administrativnu provjeru vrši **Komisija**.

Predsjednik Komisije na Platformi, **u ime Komisije**, evidentira rezultat administrativne provjere.

Administrator konkursa **nije** akter koji vrši ili evidentira administrativnu provjeru.

U administrativnu provjeru ulaze **samo Prijave u stanju Podnesena**.

Provjera utvrđuje da li Prijava sadrži **cjelokupan obavezni paket** koji važi za tu Prijavu / kategoriju Podnositeljke:

* primjenjivi Obrazac 1a ili Obrazac 1b;
* Obrazac 2 / Biznis plan;
* obaveznu prateću dokumentaciju koja važi za tu Podnositeljku i fazu biznisa.

Ovo poglavlje **ne** mijenja pravila podnošenja iz Poglavlja 7. Uspješno konačno podnošenje već zahtijeva primjenjivi Obrazac 1a / 1b i popunjen Obrazac 2 / Biznis plan. Nedostatak prateće dokumentacije **ne** sprečava konačno podnošenje.

Administrativna provjera je:

* po Prijavi;
* jedan konačni administrativni rezultat;
* **ne** zaseban rezultat / stanje za svaki pojedinačni dokument.

Platforma može identificirati i prikazati nedostajuću obaveznu prateću dokumentaciju kao podršku pregledu.

Konačni administrativni rezultat evidentira Predsjednik Komisije.

Dozvoljeni rezultati administrativne provjere:

* **Potpuna**;
* **Nepotpuna**.

To su **rezultati postupka**, ne osnovna stanja Prijave. Osnovno stanje Prijave ostaje **Podnesena**.

Administrativna provjera je funkcionalno **nezavisna** od kasnijeg ocjenjivanja. Ovo poglavlje ne određuje kriterijume ocjenjivanja ni tok ocjenjivanja.

## 10.3. Nepotpuna Prijava i dopuna

Nakon isteka roka za prijavu **nema dopune** podnesene Prijave.

Podnositeljka u ovoj fazi **ne** smije:

* uređivati Obrazac 1a / Obrazac 1b;
* uređivati Obrazac 2 / Biznis plan;
* dodati novu prateću dokumentaciju;
* zamijeniti već podnesenu prateću dokumentaciju;
* na drugi način izmijeniti snimak podnesene Prijave.

Ako je rezultat administrativne provjere **Potpuna**, Prijava može ići ka narednim fazama Konkursa.

Ako je rezultat **Nepotpuna**, Prijava **ne** ide dalje dok je pravo na Prigovor otvoreno ili dok je podneseni Prigovor u postupku.

Ne uvodi se tok zahtjeva za dopunu, rok za dopunu, broj pokušaja dopune ni pravo učitavanja dokumenata nakon isteka roka.

## 10.4. Prigovor

Podnositeljka čija je Prijava utvrđena kao **Nepotpuna** prima obavještenje **preko Platforme** o:

* rezultatu administrativne provjere;
* pravu na podnošenje Prigovora;
* primjenjivom roku.

Prigovor se podnosi:

* **isključivo preko Platforme** / digitalnog servisa;
* za konkretnu Prijavu;
* od strane Podnositeljke;
* u roku od **3 dana** od **slanja** obavještenja o administrativnoj nepotpunosti.

Koristi se formulacija **3 dana**. Ne tumači se kao 3 radna dana.

Prigovor omogućava Podnositeljki da unese obrazloženje.

Prigovor **nije** dopuna Prijave.

Prigovor **ne** smije omogućiti:

* nove prateće dokumente;
* zamjenu pratećih dokumenata;
* izmjenu Obrasca 1a / Obrasca 1b;
* izmjenu Obrasca 2 / Biznis plana;
* bilo koju drugu izmjenu snimka podnesene Prijave.

Komisija odlučuje o Prigovoru u roku od **7 dana** od prijema.

Stanja / rezultati postupka Prigovora:

* **Podnesen**;
* **Prihvaćen**;
* **Odbijen**.

To su stanja postupka Prigovora, ne osnovna stanja Prijave.

Ako je Prigovor **Prihvaćen**:

* administrativni rezultat postaje / tretira se kao **Potpuna**;
* Prijava nastavlja naredne faze Konkursa.

Ako je Prigovor **Odbijen**:

* administrativni rezultat ostaje **Nepotpuna**;
* Prijava se **ne** razmatra dalje.

Ako Prigovor nije podnesen u roku od 3 dana:

* **Nepotpuna** postaje konačna za ovu administrativnu fazu;
* Prijava se **ne** razmatra dalje.

V1 kanal Prigovora je **Platforma**. Vanjski e-mail **nije** važeći kanal podnošenja Prigovora.

## 10.5. Završetak administrativne provjere

**Potpuna** / **Nepotpuna** ostaju rezultati administrativne provjere. **Ne** uvode se kao nova osnovna stanja Prijave.

**Podnesen** / **Prihvaćen** / **Odbijen** ostaju stanja postupka Prigovora. **Ne** uvode se kao osnovna stanja Prijave.

Prijava može izaći iz ove administrativne faze i ići dalje **samo** ako je njen konačni administrativni rezultat **Potpuna**.

To može nastati:

* neposredno administrativnom provjerom;
* ili nakon **Prihvaćenog** Prigovora.

Za Prijavu koja je prvobitno **Nepotpuna**:

* kasniji rad Komisije je blokiran dok traje rok od 3 dana za Prigovor;
* ako je Prigovor podnesen, kasniji rad Komisije je blokiran dok Komisija o njemu ne odluči;
* Prihvaćen Prigovor dozvoljava nastavak;
* Odbijen Prigovor sprečava dalje razmatranje;
* istek roka za Prigovor bez podnošenja sprečava dalje razmatranje.

To je izlazna granica Poglavlja 10.

Ovo poglavlje **ne** određuje koju suštinsku provjeru Komisija obavlja nakon te granice.

---

# 11. Eliminatorni kriterijumi

Status poglavlja: OBUSTAVLJENO

Ovo poglavlje **nije** završeno.

Poglavlje je **privremeno obustavljeno** dok se ne pribavi i ne analizira autoritativni izvor Odluke 027/26.

Eliminatorni kriterijumi 2 i 3 zavise od odredbi Odluke 027/26. Odluka 027/26 trenutno nije dostupna u repozitorijumu / skupu izvora. Bez tog izvora tačno funkcionalno ponašanje ne može se specificirati bez pretpostavki.

**OBUSTAVLJENO** znači privremenu obustavu do pribavljanja autoritativnog izvora. **Ne** znači odbijeno, otkazano, uklonjeno iz V1 niti trajno van obuhvata.

Poznati kontekst, bez razrade:

* eliminatorni kriterijum 1 / administrativna nepotpunost već je obrađen u Poglavlju 10;
* kriterijumi 2 i 3 se ovdje **ne** razrađuju;
* ne uvodi se privremeno ciljno ponašanje;
* ne uvode se tehničke pretpostavke.

---

# 12. Druga sjednica i usmeno obrazloženje

Status poglavlja: USVOJENO

Ovo poglavlje određuje drugu sjednicu Komisije i usmeno obrazloženje Biznis plana.

Ne određuje eliminatorne kriterijume 2 i 3, individualno ocjenjivanje, rangiranje ni Odluku. Ti tokovi pripadaju drugim poglavljima.

Poglavlje **ne** rješava Poglavlje 11. Koristi rezultat prethodnih faza, bez njihovog ponovnog definisanja.

Osnov: `KN-BM-003` §4.3, §11, §12; Poglavlja 7, 9 i 10 ovog dokumenta.

## 12.1. Druga sjednica Komisije

Komisija zakazuje drugu sjednicu i usmeno obrazloženje Biznis planova u roku od **15 dana** od održavanja prve sjednice.

Koristi se formulacija **15 dana**. Ne tumači se kao 15 radnih dana.

Na usmeno obrazloženje dolaze Podnositeljke čije su Prijave do tog trenutka ispunile sve prethodno propisane uslove za nastavak postupka.

Ovo poglavlje **ne** nabraja te uslove i **ne** uvodi model prolaska Poglavlja 11.

Druga sjednica i njeno zakazivanje obavljaju se **van Platforme**.

Platforma **ne** upravlja zakazivanjem druge sjednice. Ne uvodi se entitet sjednice, stanje sjednice, raspored, datum sjednice, termin po Prijavi, lokacija, onlajn veza, pomjeranje ni otkazivanje na Platformi.

## 12.2. Usmeno obrazloženje Biznis plana

Podnositeljka usmeno obrazlaže sopstveni podneseni Biznis plan pred Komisijom.

Usmeno obrazloženje se sprovodi **van Platforme**.

Platforma **ne** upravlja:

* pozivom Podnositeljki;
* terminima;
* prisustvom Podnositeljke;
* nedolaskom;
* pomjeranjem termina;
* lokacijom;
* onlajn sastankom;
* trajanjem;
* tokom / sadržajem usmenog obrazloženja.

Podnesena Prijava, Biznis plan i pripadajuća dokumentacija ostaju zaključani za izmjene, ali su dostupni Komisiji za pregled u skladu sa njenim pravima pristupa.

Usmeno obrazloženje **ne** predstavlja izmjenu Prijave, Biznis plana niti dopunu dokumentacije.

Zaključanost **nije** sakrivanje od Komisije. Ne uvodi se izuzetak od pravila snimka podnesene Prijave.

## 12.3. Uloga Komisije

Usmeno obrazloženje sprovodi se pred Komisijom.

Prilikom usmenog obrazloženja obavezno je prisustvo **svih** članova Komisije.

Nakon usmenog obrazloženja članovi Komisije nastavljaju ocjenjivanje na Platformi.

Ne uvodi se evidencija prisustva na Platformi, zapisnik usmenog obrazloženja, rezultat položio / nije položio, ni posebna Platform radnja kojom se potvrđuje da je usmeno obrazloženje održano.

## 12.4. Granica prema ocjenjivanju

Kriterijum 10 odnosi se na usmeno obrazloženje Biznis plana.

Prema poslovnom postupku, članovi Komisije ocjenjuju kriterijum 10 **nakon** što je usmeno obrazloženje sprovedeno.

To je **poslovno pravilo postupka**. **Nije** tehnička Platform kapija.

Platforma **ne** treba da zna, evidentira, potvrđuje, zaključava niti otključava da li je usmeno obrazloženje održano prije unosa ocjene kriterijuma 10.

Ne uvodi se oznaka, potvrda, server-side kapija, zaključavanje / otključavanje kriterijuma 10, ni tehnička zavisnost između dokaza o usmenom obrazloženju i unosa te ocjene.

Kada je Prijava ispunila prethodne uslove i dostupna je članovima Komisije za ocjenjivanje, članovi Komisije mogu koristiti funkcionalnost ocjenjivanja. Odgovornost da kriterijum 10 ocjene tek nakon usmenog obrazloženja pripada poslovnom postupku Komisije. Platforma taj redoslijed **ne** tehnički nameće.

Detalj individualnog ocjenjivanja, skala, formula i završavanje pripadaju Poglavlju 13.

Platforma u ovoj fazi:

* omogućava ovlašćeni pristup Komisije podnesenoj Prijavi, Biznis planu i dokumentaciji prema već usvojenim pravilima pristupa;
* omogućava funkcionalnost ocjenjivanja čija se pravila razrađuju u Poglavlju 13.

Platforma **ne** zakazuje drugu sjednicu, ne poziva Podnositeljke, ne vodi termine, ne evidentira prisustvo / nedolazak, ne sprovodi usmeno obrazloženje, ne evidentira njegov završetak i **ne** otključava tehnički kriterijum 10.

---

# 13. Individualno ocjenjivanje

Status poglavlja: USVOJENO

Ovo poglavlje određuje individualno ocjenjivanje članova Komisije na Platformi.

Ne određuje dodatne bodove, prosjeke, konačnu ocjenu Prijave, prag od 30 bodova, rang-listu, iznose ni treću sjednicu. Ti tokovi pripadaju Poglavlju 14 ili kasnijim poglavljima.

Poglavlje **ne** rješava Poglavlje 11. Koristi rezultat prethodnih faza, bez njihovog ponovnog definisanja.

Poglavlje 12 ostaje usvojeno: usmeno obrazloženje je van Platforme; kriterijum 10 **nije** tehnička Platform kapija.

Osnov: `KN-BM-003` §4.3, §12; Poglavlja 3.4, 3.5, 4.7, 4.8, 9, 10 i 12 ovog dokumenta.

## 13.1. Individualno ocjenjivanje članova Komisije

Svaki od **pet** članova Komisije individualno ocjenjuje svaku Prijavu koja je do tog trenutka ispunila sve prethodno propisane uslove za nastavak do individualnog ocjenjivanja.

Predsjednik Komisije učestvuje kao **jedan od pet** ocjenjivača.

Svih pet članova ima **istu težinu** pri individualnom ocjenjivanju.

Svaki član vrši **sopstveno** individualno ocjenjivanje.

Ovo poglavlje **ne** nabraja prethodne uslove i **ne** uvodi model prolaska Poglavlja 11.

Ne uvodi se rangiranje, prosjek, dodatni bodovi, iznos ni kolektivni rezultat Komisije.

## 13.2. Kriterijumi i skala ocjenjivanja

Individualno ocjenjivanje sadrži tačno **10** pozitivnih kriterijuma.

Svaki kriterijum ocjenjuje se **cijeli broj** od **1 do 5**.

* **1** — najniži stepen odgovaranja navedenom kriterijumu;
* **5** — potpuno odgovara navedenom kriterijumu.

Kriterijumi **nijesu** ponderisani.

Svih 10 kriterijuma je **obavezno** za završavanje individualnog ocjenjivanja.

Pozitivni kriterijumi (`KN-BM-003` §12.1; čl. 19):

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

Kriterijum 10 je kriterijum usmenog obrazloženja. Poslovni redoslijed unosa uređuje Poglavlje 12. Ovo poglavlje **ne** mijenja tu granicu.

Ne uvode se dodatni bodovi, prag od 30 bodova, prosjeci, konačna ocjena Prijave ni rang-lista.

## 13.3. Nacrt individualne ocjene

Dok član **nije** odabrao **Završi ocjenjivanje**, njegovo individualno ocjenjivanje ostaje **nacrt**.

Nacrt se odnosi na **cjelokupno** individualno ocjenjivanje člana, ne samo na kriterijume 1–9.

Dok ocjena ostaje nacrt, član može unositi i mijenjati sopstvene ocjene kriterijuma.

Ocjene unesene kao nacrt **nijesu** konačne.

Član može sačuvati nacrt i kasnije nastaviti uređivanje.

Granica izmjenjivosti je eksplicitna radnja **Završi ocjenjivanje**, **ne** kriterijum 10.

Kriterijum 10, prema poslovnom postupku Poglavlja 12, ocjenjuje se nakon što je usmeno obrazloženje sprovedeno.

Platforma **ne** kontroliše tehnički kada se unosi ocjena kriterijuma 10. Ne uvodi se posebno zaključavanje / otključavanje kriterijuma 10. Kriterijum 10 **nema** zasebno tehničko stanje. Ne uvodi se oznaka da je usmeno obrazloženje održano.

## 13.4. Završavanje individualnog ocjenjivanja

Individualno ocjenjivanje može se završiti **samo** kada su unesene ocjene za svih 10 kriterijuma.

Član eksplicitno bira **Završi ocjenjivanje**.

Platforma zahtijeva **potvrdu** te radnje.

Nakon potvrde, individualno ocjenjivanje postaje **konačno**.

Ocjene kriterijuma se nakon završavanja **ne** mogu mijenjati.

Ne uvodi se automatsko završavanje samo zato što su sva polja popunjena. Ne uvodi se ponovno otvaranje, ovlašćenje predsjednika da mijenja tuđu ocjenu, ovlašćenje Administratora konkursa da mijenja ocjene, ni uređivanje završenih ocjena.

## 13.5. Tajnost individualnog ocjenjivanja

Dok je cjelokupan ciklus individualnog ocjenjivanja **otvoren**, svaki član Komisije vidi **samo sopstvene** individualne ocjene.

Predsjednik Komisije **nema** privilegovan uvid u individualne ocjene drugih članova dok taj ciklus traje.

Administrator konkursa **ne** dobija pristup individualnim ocjenama članova Komisije dok je ciklus individualnog ocjenjivanja otvoren.

Podnositeljka **ne** dobija pristup individualnim ocjenama članova Komisije u ovom poglavlju.

Individualne ocjene drugih članova postaju dostupne Komisiji **tek** kada svi članovi Komisije završe individualno ocjenjivanje **svih** Prijava koje su ušle u ciklus individualnog ocjenjivanja.

Ovo poglavlje **ne** određuje kasniju javnu objavu ni pristup Podnositeljke rezultatima.

## 13.6. Ostale napomene

Svaki član Komisije može, za konkretnu Prijavu koju individualno ocjenjuje, unijeti **Ostale napomene**.

Polje je **opciono**.

Napomena pripada konkretnom članu Komisije i konkretnoj Prijavi. Odnosi se na individualno ocjenjivanje Prijave **kao cjeline**.

**Nije** zaseban kriterijum ocjenjivanja. **Ne** dodaje i **ne** oduzima bodove.

Ista tajnost kao za individualne ocjene:

* dok je ciklus individualnog ocjenjivanja otvoren, član vidi sopstvene Ostale napomene;
* napomene drugih članova **ne** otkrivaju se ranije samo zato što su svi članovi ocjenili jednu konkretnu Prijavu;
* napomene drugih članova mogu se otkriti zajedno sa njihovim individualnim ocjenama nakon završetka **cjelokupnog** ciklusa individualnog ocjenjivanja.

Ostale napomene **nijesu** Napomena iz Poglavlja 11 o eliminatornim kriterijumima.

Ne uvode se napomene po pojedinačnom kriterijumu.

## 13.7. Granica prema Poglavlju 14

Poglavlje 13 obuhvata:

* individualne ocjene kriterijuma;
* nacrt;
* izmjenjivost prije završavanja;
* eksplicitno završavanje;
* nepromjenjivost nakon završavanja;
* tajnost;
* opcione Ostale napomene.

Poglavlje 13 **ne** određuje:

* dodatne bodove;
* prosjek po kriterijumu;
* ukupni prosjek;
* konačnu ocjenu Prijave;
* prag od 30 bodova;
* preliminarnu rang-listu;
* pravila jednakih bodova;
* predloženi iznos sredstava;
* treću sjednicu;
* kolektivnu odluku Komisije.

To pripada Poglavlju 14 ili kasnijim poglavljima.

---

# 14. Rang-lista, iznosi i treća sjednica

Status poglavlja: USVOJENO

Ovo poglavlje određuje zbirne rezultate, dodatne bodove, konačnu ocjenu, prag od 30 bodova, preliminarnu i konačnu rang-listu, treću sjednicu Komisije, zaključke Podržava / Odbija i predložene iznose podrške.

Poglavlje **ne** rješava Poglavlje 11. Koristi rezultat prethodnih faza, bez njihovog ponovnog definisanja.

Poglavlja 12 i 13 ostaju usvojena. Ovo poglavlje **ne** mijenja individualno ocjenjivanje, nacrt, završavanje, nepromjenjivost individualnih ocjena ni tajnost dok ciklus traje.

Ne određuje predlog Odluke, predlaganje Sekretarijatu, zatvaranje Konkursa, arhivu, konačnu Odluku ni objavu. Ti tokovi pripadaju Poglavlju 15.

Osnov: `KN-BM-003` §4.3, §12.5, §12.6, §13; Poglavlja 3.5, 4.9–4.12, 12 i 13 ovog dokumenta.

## 14.1. Završetak individualnog ocjenjivanja i formiranje zbirnih rezultata

Poglavlje 14 počinje kada svih **pet** članova Komisije završi individualno ocjenjivanje **svih** Prijava koje su do tog trenutka ispunile sve prethodno propisane uslove za nastavak postupka i ušle u ocjenjivanje.

Završetak je **globalan** za cjelokupan ciklus individualnog ocjenjivanja, **ne** po pojedinačnoj Prijavi.

Ovo poglavlje **ne** nabraja prethodne uslove i **ne** uvodi model prolaska Poglavlja 11.

Kada je cjelokupan ciklus individualnog ocjenjivanja završen, Platforma **automatski** prelazi na formiranje zbirnih rezultata.

Za početak zbirnog proračuna **nije** potrebna radnja Predsjednika Komisije niti Administratora konkursa.

Tek u tom globalnom trenutku prestaje granica tajnosti iz Poglavlja 13. Članovi Komisije tada mogu vidjeti konačne individualne ocjene drugih članova i zbirne rezultate.

Ponašanje preliminarne rang-liste određuje §14.6.

## 14.2. Prosječne ocjene

Za svaki od 10 kriterijuma ocjenjivanja:

prosječna ocjena kriterijuma = zbir konačnih individualnih ocjena svih pet članova Komisije / 5

U proračun ulaze ocjene **svih pet** članova.

Kriterijumi imaju **istu težinu**. Ne uvodi se ponderisanje kriterijuma.

Svaka prosječna ocjena kriterijuma zaokružuje se na **dvije decimale**. Ta zaokružena vrijednost koristi se u daljem proračunu.

Ovo poglavlje **ne** propisuje programsku funkciju zaokruživanja.

## 14.3. Dodatni bodovi

Predsjednik Komisije može, u ime Komisije, evidentirati osnov za dodatne bodove konkretne Prijave čim raspolaže potrebnim podacima za utvrđivanje tog osnova.

**Ne** mora čekati završetak cjelokupnog ciklusa individualnog ocjenjivanja.

Predsjednik evidentira ostvareni osnov. Platforma primjenjuje **fiksan** broj bodova za taj osnov. Platforma **ne** odlučuje samostalno da li je činjenični osnov ispunjen.

Kanonski dodatni bodovi (`KN-BM-003` §12.6; čl. 19):

* **+1** — prisustvo Info danu i obuci za izradu Biznis plana, za tekuću godinu;
* **+2** — fizičko lice koje planira registrovanje biznisa;
* **+2** — evidencija Zavoda za zapošljavanje duže od 12 mjeseci;
* **+3** — inovativna i/ili zelena biznis ideja.

Bodovi su **kumulativni**. Maksimum dodatnih bodova = **8**.

Dodatni bodovi **nijesu** jedanaesti kriterijum ocjenjivanja i **nijesu** individualno ocjenjivanje člana Komisije.

Rano evidentiranje osnova **ne** smije otkriti niti na drugi način uticati na tajne individualne ocjene iz Poglavlja 13.

Ne uvodi se novi tok dokaza, novo stanje Prijave, automatski motor podobnosti ni drugi nepodržani poslovni postupak.

Razlikuje se:

* **unos** — Predsjednik može evidentirati osnov i prije globalnog završetka ocjenjivanja;
* **konačna vidljivost zbirnih rezultata i rang-liste** — ostaje uređena granicom tajnosti Poglavlja 13 i §14.1 / §14.6.

## 14.4. Konačna ocjena

konačna ocjena Prijave = zbir 10 prosječnih ocjena kriterijuma + dodatni bodovi

Maksimum = **58** bodova.

Platforma izračunava konačnu ocjenu. **Nije** ručno izmjenjivo polje ocjene.

U proračun ulaze prosječne ocjene kriterijuma zaokružene na dvije decimale iz §14.2. Konačna ocjena se prikazuje na **dvije decimale**.

Matematički, konačna ocjena konkretne Prijave može biti izračunljiva kada su svih pet članova završili individualno ocjenjivanje te Prijave i kada su evidentirani podaci o dodatnim bodovima.

To **ne** završava globalni ciklus ocjenjivanja, **ne** prestaje tajnost ciklusa i **ne** formira preliminarnu rang-listu ranije.

Dok preliminarna rang-lista **nije** formirana, ako Predsjednik ispravi evidentirani osnov dodatnih bodova, Platforma **automatski** preračunava izvedenu konačnu ocjenu.

Individualne ocjene ostaju zaključane prema Poglavlju 13.

Ne uvodi se trajna zaključanost konačne ocjene već pri prvom matematičkom izračunu. Granicu rang-liste određuje §14.6. Zaključanost rezultata Poglavlja 14 određuje §14.10.

## 14.5. Prag od 30 bodova

Prag od **30 bodova** odnosi se na **konačnu ocjenu**, uključujući dodatne bodove.

Ako je konačna ocjena **< 30**:

* Prijava ostaje na preliminarnoj rang-listi;
* prikazuje se ispod praga od 30 bodova;
* **ne** može dobiti finansijsku podršku;
* **ne** dodjeljuje se iznos podrške;
* **ne** uvodi se posebno osnovno stanje Prijave „Odbijena“ samo zbog ocjene ispod 30;
* na trećoj sjednici se **ne** odbija dodatno samo zbog praga.

Ako je konačna ocjena **≥ 30**:

* Prijava je iznad praga;
* može se razmatrati za finansijsku podršku;
* ocjena ≥ 30 **ne garantuje** podršku.

Platforma automatski utvrđuje da li je ocjena ispod ili iznad praga. To je matematička posljedica, **ne** diskrecija Komisije.

## 14.6. Preliminarna rang-lista

Preliminarna rang-lista formira se **automatski** tek kada je završen **cjelokupan globalni** ciklus individualnog ocjenjivanja.

Redoslijed je po konačnoj ocjeni, od najviše ka nižoj.

Ne uvodi se ručno premještanje od strane Predsjednika Komisije, člana Komisije niti Administratora konkursa.

Postoji **jedna** poslovna rang-lista. Prag od 30 bodova može se vizuelno označiti. To **ne** stvara dvije odvojene poslovne liste.

Sve Prijave koje su ušle u pozitivno ocjenjivanje ostaju na listi, uključujući one ispod 30 bodova.

Prijave sa istom konačnom ocjenom imaju **isti rang**. Koriste se **dijeljene** pozicije. Primjer: 1, 2, 2, 4.

Tehnički identifikator Prijave, `redni_broj`, vrijeme podnošenja, identifikator u bazi niti druga skrivena tehnička vrijednost **nije** poslovni tie-break za rang.

Pravilo čl. 21 o prioritetu finansiranja pri jednakim bodovima pripada §14.9. **Ne** mijenja dijeljene pozicije na rang-listi.

Vidljivost preliminarne rang-liste:

* svih pet članova Komisije može je vidjeti;
* Predsjednik Komisije ima istu vidljivost rezultata, uz funkcije evidentiranja zaključaka Komisije koje mu pripadaju;
* Administrator konkursa može **vidjeti** preliminarnu rang-listu, ali **ne** može mijenjati ocjene, dodatne bodove, pozicije niti zaključke Komisije;
* Podnositeljka **ne** dobija pristup preliminarnoj rang-listi u ovom poglavlju;
* preliminarna rang-lista se u ovom poglavlju **ne** objavljuje javno.

## 14.7. Treća sjednica Komisije

Komisija zakazuje treću sjednicu u roku od **7 dana** od održavanja **druge sjednice i usmenih obrazloženja / intervjua** (čl. 20; `KN-BM-003` §13.2).

Koristi se formulacija **7 dana**. Ne tumači se kao 7 radnih dana.

Rok **ne** teče od završetka ocjenjivanja niti od formiranja preliminarne rang-liste.

Poslovni tok:

* usmeno obrazloženje;
* individualno ocjenjivanje;
* završetak cjelokupnog ciklusa ocjenjivanja;
* preliminarna rang-lista;
* treća sjednica.

Na trećoj sjednici Komisija razmatra preliminarnu rang-listu.

Za Prijave sa konačnom ocjenom **≥ 30** utvrđuje **Podržava** ili **Odbija**. Gdje utvrdi Podržava, utvrđuje predloženi iznos podrške.

Gdje je potrebno, primjenjuje se prioritet finansiranja pri jednakim bodovima prema §14.9.

Prijave ispod 30 bodova **ne** odbijaju se dodatno samo zbog ocjene.

Obavezno je prisustvo **svih pet** članova Komisije.

Organizacija i održavanje treće sjednice su **van Platforme**.

Platforma **ne** upravlja:

* zakazivanjem;
* pozivima;
* lokacijom;
* prisustvom;
* elektronskim prisustvom;
* otvaranjem / zatvaranjem sjednice;
* opštim elektronskim glasanjem.

Predsjednik Komisije na Platformi, u ime Komisije, evidentira zaključke Komisije.

Ne uvodi se opšti tok elektronskog glasanja. Ako je potrebno glasanje za prioritet finansiranja, glasa se na poslovnoj sjednici. Platforma evidentira **samo ishod**, prema §14.9.

## 14.8. Podržava / Odbija, iznosi i raspodjela

### 14.8.1. Podržava / Odbija

Za svaku Prijavu sa konačnom ocjenom **≥ 30** Komisija na trećoj sjednici utvrđuje **Podržava** ili **Odbija**.

Predsjednik Komisije evidentira zaključak na Platformi, u ime Komisije.

Prijave sa ocjenom **< 30** **ne** dobijaju dodatni zaključak Odbija samo zbog ocjene.

### 14.8.2. Ručni unos iznosa

Ako je zaključak **Podržava**, Predsjednik ručno unosi **predloženi iznos podrške**.

Platforma **ne** popunjava taj iznos automatski i **ne** postavlja ga kao podrazumijevanu vrijednost.

Poslovni naziv je **predloženi iznos podrške**. Postojeći tehnički identifikator polja, ako postoji u implementaciji (npr. `approved_amount`), **nije** poslovni naziv.

### 14.8.3. Maksimum 20% / 10% / 5%

Platforma određuje primjenjivi maksimum prema čl. 18 (`KN-BM-003` §13.4):

* **20%** — biznis plan za inovativne djelatnosti i/ili zeleno preduzetništvo;
* **10%** — fizička lica / preduzetnice / društva kojima ranije **nijesu** dodjeljivana budžetska sredstva Opštine Kotor za ovu podršku;
* **5%** — preduzetnice / društva kojima su ranije dodjeljivana budžetska sredstva za podršku ženskom preduzetništvu.

Osnovica je ukupni iznos definisan Javnim konkursom za tekuću godinu, prema usvojenom tumačenju čl. 18.

Usvojeno pravilo preklapanja. **Ne** otvara se ponovo:

* ako istovremeno postoji osnov za **5%** i **20%**, primjenjuje se **20%**;
* ako istovremeno postoji osnov za **10%** i **20%**, primjenjuje se **20%**;
* procenti se **ne** sabiraju.

Predsjednik i dalje ručno unosi predloženi iznos. Platforma **ne dozvoljava** iznos iznad primjenjivog maksimuma.

20% je **maksimalna granica**, ne automatski dodijeljeni iznos.

### 14.8.4. Ograničenje traženim iznosom

Maksimalna podrška za konkretnu Prijavu ne može preći:

* iznos koji je Podnositeljka zatražila; i
* primjenjivi maksimum 20% / 10% / 5%.

Gornja granica u ovoj tački je **manja** od te dvije vrijednosti.

### 14.8.5. Preostala sredstva

Platforma stalno izračunava:

preostala sredstva = ukupan iznos Konkursa − zbir već predloženih iznosa podrške

Predloženi iznos **ne** može preći preostala raspoloživa sredstva.

Stvarni maksimum za unos je stoga ograničen:

1. traženim iznosom;
2. primjenjivim maksimumom 20% / 10% / 5%;
3. preostalim sredstvima Konkursa.

Platforma **ne** dodjeljuje preostali iznos automatski. Komisija odlučuje. Predsjednik evidentira.

### 14.8.6. Redoslijed raspodjele

Komisija razmatra Prijave za finansiranje prema redoslijedu preliminarne rang-liste, od najviše rangirane naniže.

Za svaku Prijavu sa ocjenom **≥ 30**:

* Komisija utvrđuje Podržava ili Odbija;
* ako je Podržava, Predsjednik evidentira predloženi iznos uz sva usvojena ograničenja;
* ako je Odbija, sredstva ostaju raspoloživa i raspodjela se nastavlja na sljedeću Prijavu.

Odbija **ne** prekida raspodjelu i **ne** sprečava niže rangiranu Prijavu da dobije podršku.

Prijave sa jednakim bodovima uređuje §14.9.

### 14.8.7. Djelimična podrška zbog preostalog budžeta

Ako Prijava dođe na red, a preostala sredstva Konkursa su manja od iznosa koji bi inače mogla dobiti:

* Komisija odlučuje da li će je podržati preostalim raspoloživim iznosom;
* Platforma prikazuje i nameće preostali iznos kao maksimum;
* Platforma ga **ne** dodjeljuje automatski.

Ako Komisija **ne** podrži Prijavu tim smanjenim iznosom, zaključak je **Odbija**.

Kada preostala sredstva padnu na nulu, nijedna kasnija Prijava **ne** može dobiti finansijsku podršku.

### 14.8.8. Odbija — obavezno obrazloženje

Za Prijavu sa ocjenom **≥ 30** kod koje Komisija utvrdi **Odbija**:

* detaljno tekstualno obrazloženje je **obavezno**;
* Predsjednik ga evidentira na Platformi, u ime Komisije;
* Platforma **ne** dozvoljava završavanje konačnog zaključka Odbija bez tog obrazloženja.

Ne uvodi se katalog razloga, šifra razloga, proizvoljan minimalni broj karaktera ni nove kategorije odbijanja.

Prijave sa ocjenom **< 30** **ne** zahtijevaju ovo obrazloženje Odbija samo zato što su ispod praga.

### 14.8.9. Podržava — obrazloženje

Ako je zaključak **Podržava**:

* predloženi iznos podrške je **obavezan**;
* zasebno tekstualno obrazloženje **nije** obavezno;
* Platforma može dozvoliti opcionu napomenu / obrazloženje Komisije;
* taj tekst **nije** uslov završavanja.

Postojeći prikaz ili podatak može već sadržati obrazloženje za podržane Prijave. To ga **ne** čini obaveznim.

### 14.8.10. Završenost za konkretnu Prijavu

Za Prijavu sa ocjenom **≥ 30**:

**Podržava** je završena kada su evidentirani zaključak Podržava i predloženi iznos podrške, i kada iznos zadovoljava sva ograničenja iz §14.8.

**Odbija** je završena kada su evidentirani zaključak Odbija i obavezno detaljno obrazloženje, i kada **nije** dodijeljen predloženi iznos podrške.

Prijave sa ocjenom **< 30** **ne** zahtijevaju unos Podržava / Odbija samo da bi ova faza bila završena.

Završenost jedne Prijave **sama po sebi ne** formira konačnu rang-listu. Globalnu završenost određuje §14.10.

## 14.9. Jednaki bodovi

Ovo je pravilo **prioriteta finansiranja** prema čl. 21. **Ne** mijenja dijeljene pozicije na rang-listi iz §14.6.

Primjenjuje se kada jednake konačne ocjene zahtijevaju utvrđivanje prioriteta finansiranja, odnosno kada raspoloživa sredstva nijesu dovoljna za finansiranje svih Prijava sa tom ocjenom.

### 14.9.1. Prednost — otpočinjanje biznisa

Kada jednake konačne ocjene zahtijevaju utvrđivanje prioriteta finansiranja, prvo se primjenjuje pravilo čl. 21:

prednost ima biznis plan koji se odnosi na **otpočinjanje biznisa**.

To određuje samo prioritet finansiranja. **Ne** mijenja konačne ocjene niti dijeljene pozicije.

Ako to pravilo jednoznačno odredi prioritet, taj ishod se evidentira prema §14.9.3.

### 14.9.2. Odluka Komisije ako prednost nije riješena

Ako se prioritet **ne** može riješiti pravilom o otpočinjanju biznisa, Komisija odlučuje koja od izjednačenih Prijava ima prioritet finansiranja.

Odluka se donosi **većinom od ukupnog broja članova Komisije**.

Komisija ima pet članova. Potrebna većina = **najmanje 3 od 5**.

Odluka se donosi na trećoj poslovnoj sjednici. Ne uvodi se tok elektronskog individualnog glasanja.

### 14.9.3. Evidentiranje ishoda

Predsjednik Komisije na Platformi evidentira:

* koja Prijava je dobila prioritet finansiranja;
* da li je osnov **otpočinjanje biznisa** ili odluka Komisije.

Platforma **ne** evidentira pojedinačne elektronske glasove članova.

Ne zahtijeva se evidencija odnosa glasova 3:2, 4:1 ili 5:0, osim ako kasniji autoritativni izvor to izričito zatraži.

Ishod:

* **ne** mijenja konačnu ocjenu;
* **ne** mijenja dijeljenu poziciju na rang-listi;
* koristi se **samo** za raspodjelu sredstava.

## 14.10. Konačna rang-lista

### 14.10.1. Automatsko formiranje

Platforma **automatski** formira konačnu rang-listu kada su završeni svi potrebni rezultati treće sjednice.

Za Prijave sa ocjenom **≥ 30** mora biti završena Podržava prema §14.8 ili Odbija prema §14.8.

Gdje je §14.9 bio potreban, mora biti evidentiran i ishod prioriteta finansiranja.

Prijave sa ocjenom **< 30** **ne** zahtijevaju dodatni zaključak Komisije samo radi formiranja konačne rang-liste.

Ne uvodi se zasebno dugme „Formiraj konačnu rang-listu“, ručni početak od strane Predsjednika, niti ručni početak od strane Administratora konkursa.

### 14.10.2. Sadržaj

Preliminarna i konačna rang-lista su **dva stanja iste** poslovne rang-liste, ne dva odvojena poslovna objekta.

Konačna rang-lista zadržava:

* redoslijed po konačnoj ocjeni;
* dijeljene pozicije za jednake ocjene.

Odluke o finansiranju **ne** prepisuju ocjene niti pozicije.

Konačna rang-lista prikazuje najmanje poslovne podatke već usvojene za ovaj model, uključujući:

* poziciju;
* konačnu ocjenu;
* identifikaciju Podnositeljke / Biznis plana;
* traženi iznos;
* ishod finansiranja;
* predloženi iznos podrške gdje je Prijava podržana;
* relevantne podatke o zaključku Komisije.

Za Prijave sa ocjenom **≥ 30** koje su Odbija, detaljno obrazloženje ostaje dio evidentiranog zaključka Komisije.

Za Prijave sa ocjenom **< 30** prikazuje se činjenica da prag **nije** ostvaren, bez uvođenja novog osnovnog stanja Prijave.

Usvojeni V1 prikaz ostaje prezentacioni zahtjev (`KN-BM-003` §13.7; Poglavlje 4.12 ovog dokumenta). Potpisivanje ostaje fizički, van Platforme, prema već usvojenom pravilu.

### 14.10.3. Zaključavanje

Kada Platforma automatski formira konačnu rang-listu, rezultat Poglavlja 14 je **zaključan**.

Nakon toga, u redovnom toku Konkursa, niko **ne** smije mijenjati podatke koji određuju tu konačnu rang-listu, uključujući:

* konačne individualne ocjene;
* dodatne bodove;
* konačnu ocjenu;
* pozicije na rang-listi;
* zaključak Podržava / Odbija;
* predloženi iznos podrške;
* ishod prioriteta finansiranja;
* evidentirano obrazloženje / napomenu Komisije.

Ne uvodi se ovlašćenje Administratora konkursa, poslovno ovlašćenje Super administratora, „otključaj rang-listu“, ponovno otvaranje niti tok uređivanja nakon konačne rang-liste.

Ako budući pravni zahtjev bude zahtijevao postupak ispravke, to se određuje posebno. Ovo poglavlje ga **ne** uvodi.

### 14.10.4. Vidljivost i nepromjenjivost

Nakon formiranja:

* svih pet članova Komisije može vidjeti cjelokupnu konačnu rang-listu;
* Administrator konkursa može je vidjeti;
* niko je **ne** smije mijenjati.

Izričito **ne** mijenjaju je: Predsjednik Komisije, drugi član Komisije, Administrator konkursa, Administrator platforme, niti Super administrator samo zbog platformske uloge.

Podnositeljka **ne** dobija pristup samo zato što je konačna rang-lista formirana.

Konačna rang-lista se u ovom poglavlju **ne** objavljuje javno.

Javna / konačna objava Odluke pripada Poglavlju 15.

## 14.11. Granica prema Poglavlju 15

Poglavlje 14 završava se kada su:

* završeno individualno ocjenjivanje;
* izračunati zbirni rezultati i konačne ocjene;
* formirana preliminarna rang-lista;
* održana treća sjednica;
* evidentirani potrebni zaključci Komisije;
* raspodijeljeni predloženi iznosi podrške prema usvojenim ograničenjima;
* riješen potrebni prioritet finansiranja pri jednakim bodovima;
* Platforma automatski formirala konačnu rang-listu;
* konačna rang-lista zaključana i nepromjenjiva.

Konačna rang-lista je **izlaz** Poglavlja 14 i **ulaz** u Poglavlje 15.

Ovo poglavlje **ne** ulazi u Poglavlje 15.

Izričito **ne** obuhvata:

* generisanje / izradu predloga Odluke;
* predlaganje / dostavu Sekretarijatu za razvoj preduzetništva, komunalne poslove i saobraćaj;
* zatvaranje Konkursa;
* arhiviranje;
* konačnu Odluku tog Sekretarijata;
* objavu konačne Odluke;
* tok obavještavanja / objave prema Podnositeljki;
* drugi / ponovljeni Konkurs.

---

# 15. Predlog Odluke, zatvaranje, arhiva i objava

Status poglavlja: USVOJENO

Ovo poglavlje određuje tok od zaključane konačne rang-liste do Predloga Odluke, nastanka zvanične Odluke, zaključivanja Konkursa, arhive i objavljivanja zvanične Odluke na Platformi.

Poglavlje **ne** mijenja Poglavlje 14. Konačna rang-lista ostaje zaključana. Nema novog ocjenjivanja ni nove selekcije.

Kanonski redoslijed:

* zaključana konačna rang-lista;
* Platforma generiše Predlog Odluke;
* Komisija provjerava Predlog;
* Predlog se štampa;
* van Platforme se dostavlja sekretaru nadležnog Sekretarijata;
* sekretar pregleda i fizički potpisuje;
* fizičkim potpisom Predlog postaje zvanična Odluka;
* sekretar van Platforme obavještava Komisiju;
* Predsjednik Komisije zaključuje Konkurs;
* Konkurs se istovremeno smatra arhiviranim;
* Administrator konkursa postavlja elektronski primjerak fizički potpisane zvanične Odluke;
* Administrator konkursa objavljuje taj zvanični primjerak na Platformi.

**Predlog Odluke nije zvanična Odluka.** Generisanje na Platformi **ne** predstavlja donošenje Odluke. Zvanična Odluka nastaje fizičkim potpisom sekretara **van Platforme**. Zaključivanje Konkursa **nije** donošenje Odluke.

Osnov: `KN-BM-003` §4.2, §4.3, §4.5, §13.8, §14, §15; Poglavlja 3.3, 3.5, 4.12 i 14 ovog dokumenta.

## 15.1. Polazna tačka — zaključana konačna rang-lista

Poglavlje 15 počinje kada Platforma formira konačnu rang-listu prema Poglavlju 14.

Konačna rang-lista je zaključani rezultat rada Komisije i **ulaz** u postupak Predloga Odluke i zvanične Odluke.

U Poglavlju 15 **nema**:

* ponovnog ocjenjivanja;
* nove selekcije;
* izmjene individualnih ocjena;
* izmjene dodatnih bodova;
* izmjene konačnih ocjena;
* izmjene pozicija;
* izmjene zaključka Podržava / Odbija;
* izmjene predloženih iznosa podrške;
* izmjene ishoda prioriteta finansiranja pri jednakim bodovima;
* izmjene obrazloženja / napomena Komisije;
* izmjene drugih zaključanih podataka konačne rang-liste.

Predlog Odluke i svi naredni koraci koriste zaključanu konačnu rang-listu kao izvor rezultata. Ovo poglavlje **ne** razrađuje ponovo pravila Poglavlja 14.

## 15.2. Predlog Odluke

### 15.2.1. Generisanje Predloga Odluke

Predlog Odluke je šablonski dokument. Platforma ga generiše iz zaključane konačne rang-liste i drugih već evidentiranih podataka Konkursa potrebnih za dokument.

Predsjednik Komisije pokreće generisanje postojećom funkcijom **„Generiši Odluku“**, u ime Komisije. Predsjednik **nije** samostalni donosilac Predloga.

Predsjednik **ne** sastavlja Predlog ručno i **ne** odlučuje ponovo o rezultatima.

Platforma popunjava unaprijed definisani šablon. Šablon može obuhvatiti:

* podržane Biznis planove;
* Podnositeljke;
* konačne ocjene;
* predložene iznose podrške;
* ukupna raspoloživa i dodijeljena sredstva;
* druge već evidentirane podatke Konkursa potrebne za šablon.

Šablon može sadržati unaprijed definisani pravni osnov, dispozitiv, članove, obrazloženje i mjesto za potpis sekretara.

Ovo poglavlje **ne** zamrzava konkretan tekst šablona jedne godišnje instance. Ne pretvara postojeći prikaz na Platformi u kanonski tekst ovog dokumenta.

Postojeći UI ili kod može koristiti naziv „Odluka“. To **ne** određuje poslovno značenje. Dokument nastao ovom funkcijom je **Predlog Odluke**.

### 15.2.2. Izvor podataka i nepromjenljivost rezultata

Predlog koristi zaključane podatke konačne rang-liste i druge već evidentirane podatke Konkursa potrebne za šablon.

Generisanje **ne može** mijenjati:

* podržane Prijave;
* konačne ocjene;
* pozicije / rang;
* predložene iznose podrške;
* druge zaključane rezultate.

Funkcija „Generiši Odluku“ **nije** editor rezultata i **nije** slobodni editor dokumenta.

### 15.2.3. Status generisanog dokumenta

Dokument nastao funkcijom „Generiši Odluku“ je **Predlog Odluke**.

Generisanjem:

* **ne** nastaje zvanična Odluka Sekretarijata;
* Konkurs se **ne** zaključuje;
* dokument se **ne** objavljuje kao zvanična Odluka;
* **ne** vrši se dostavljanje zvanične Odluke.

Samo nadležni sekretar fizičkim potpisom pretvara Predlog u zvaničnu Odluku, prema §15.3.2.

### 15.2.4. Potpisi i Predlog Odluke

Generisanje Predloga na Platformi **ne** uvodi novi elektronski postupak potpisivanja.

Potpisi članova Komisije koji se odnose na konačnu rang-listu ostaju vezani za konačnu rang-listu, prema već usvojenom pravilu Poglavlja 14. **Ne** prenose se kao elektronski potpisi Predloga.

Ne uvodi se:

* pet elektronskih potpisa Predloga;
* status „čeka potpis“, „djelimično potpisan“ ili „potpisan“ za Predlog;
* elektronsko potpisivanje Predloga;
* novi signing workflow.

Ako je fizički potpis na Predlogu potreban prema formalnom postupku, odvija se **van Platforme**.

## 15.3. Postupanje sa Predlogom i nastanak zvanične Odluke

### 15.3.1. Provjera Predloga Odluke od strane Komisije

Nakon generisanja Komisija provjerava Predlog prije upućivanja Sekretarijatu.

Provjera potvrđuje da dokument odgovara zaključanoj konačnoj rang-listi i rezultatima Komisije.

Provjera **nije** novi krug odlučivanja. Komisija kroz provjeru **ne** mijenja zaključane rezultate.

Ako je Predlog ispravan:

* štampa se;
* fizički se dostavlja sekretaru Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj.

Platforma u V1 **ne** upravlja fizičkim slanjem dokumenta sekretaru.

### 15.3.2. Potpis sekretara i nastanak zvanične Odluke

Sekretar **van Platforme** prima i pregleda Predlog.

Ako ga prihvata, fizički ga potpisuje.

**Fizičkim potpisom sekretara Predlog Odluke postaje zvanična Odluka** Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj.

Prije fizičkog potpisa dokument je Predlog.

Generisanje, provjera, štampanje ili fizička dostava sekretaru **sami po sebi** ne čine dokument zvaničnom Odlukom.

Platforma **nema**:

* elektronsko prihvatanje sekretara;
* elektronski potpis sekretara;
* poseban workflow Sekretarijata;
* checkbox „sekretar prihvatio“;
* status prihvatanja sekretara;
* poslovni korak `proposed_at` ili sličan zapis samo radi ovog toka.

### 15.3.3. Obavještavanje Komisije

Nakon fizičkog potpisa sekretar **van Platforme** obavještava Komisiju da je Predlog prihvaćen i da je nastala zvanična Odluka.

Platforma **ne** evidentira posebno ovo obavještenje.

Nakon tog obavještenja Predsjednik Komisije može koristiti funkciju „Zaključi Konkurs“ (§15.4).

To je poslovna odgovornost Predsjednika. Platforma **ne** provjerava elektronski da je fizički potpis izvršen niti da je obavještenje stiglo.

## 15.4. Zatvaranje Konkursa

### 15.4.1. Ko i kada zaključuje Konkurs

Konkurs zaključuje **isključivo Predsjednik Komisije**, kao poslovnu radnju, postojećom funkcijom **„Zaključi Konkurs“**.

Predsjednik to radi **tek nakon** što ga sekretar van Platforme obavijesti da je Predlog fizički potpisan i postao zvanična Odluka.

Zaključivanje:

* **nije** automatsko;
* **ne** izvršava ga Sekretarijat;
* **ne** izvršava ga Administrator konkursa;
* **ne** izvršava ga Administrator platforme / Super administrator kao poslovnu radnju.

Platforma **ne** provjerava elektronski fizički potpis sekretara.

Postojeći UI ili kod može koristiti drugi natpis za istu funkciju. Poslovni naziv radnje je **„Zaključi Konkurs“**.

### 15.4.2. Posljedice zaključivanja

Klikom na „Zaključi Konkurs“ konkretni Konkurs prelazi u završeno stanje. Postupak Konkursa na Platformi smatra se zaključenim.

Zaključivanje **ne** mijenja:

* konačnu rang-listu;
* rezultate Komisije;
* konačne ocjene;
* predložene iznose podrške;
* zvaničnu Odluku.

Zaključivanje **nije** donošenje Odluke. Zvanična Odluka već postoji fizičkim potpisom sekretara.

Zaključivanje **nije** objavljivanje Odluke.

Detaljne funkcionalne zabrane, autorizacija i server-side enforcement nakon zaključivanja pripadaju Poglavlju 16.

## 15.5. Arhiva

### 15.5.1. Automatsko arhiviranje zaključenog Konkursa

Zaključivanjem se Konkurs **istovremeno** tretira kao arhiviran.

Nema posebnog poslovnog koraka niti dugmeta „Arhiviraj“. Nije potrebna dodatna radnja Predsjednika Komisije, Administratora konkursa ili Administratora platforme.

Arhiviranje **ne** mijenja rezultate. Arhivirani Konkurs ostaje sačuvan kao istorijski zapis.

Arhiviranje **nije** objavljivanje Odluke.

## 15.6. Konačna Odluka Sekretarijata

### 15.6.1. Zvanična Odluka kao dokument za objavljivanje

Zvanična Odluka je dokument nastao fizičkim potpisom sekretara na prethodno generisanom Predlogu.

Platforma nakon zaključivanja **ne** generiše novu konačnu Odluku.

Zaključivanje samo po sebi **ne** pretvara Predlog u zvaničnu Odluku.

Za dalje postupanje relevantan je fizički potpisani primjerak zvanične Odluke.

### 15.6.2. Čuvanje zvanične Odluke na Platformi

Uvodi se **ciljna** funkcionalnost čuvanja elektronskog primjerka fizički potpisane zvanične Odluke na Platformi, povezanog sa konkretnim Konkursom.

Ovo poglavlje **ne** tvrdi da je ta funkcionalnost već implementirana. Definiše je kao cilj.

### 15.6.3. Postavljanje potpisane Odluke

Administrator konkursa postavlja na Platformu elektronski primjerak fizički potpisane zvanične Odluke.

Postavljanje se vrši **nakon** što je Predsjednik zaključio Konkurs.

Postavljanjem:

* Odluka se **ne** donosi;
* Administrator konkursa **ne** potvrđuje rezultate;
* **ne** mijenja se rang-lista;
* **ne** mijenjaju se ocjene;
* **ne** mijenja se zaključak Podržava / Odbija;
* **ne** mijenjaju se predloženi iznosi podrške.

Radnja samo evidentira zvanični dokument uz konkretni Konkurs.

### 15.6.4. Integritet zvanične Odluke

Administrator konkursa postavlja elektronski primjerak fizički potpisane Odluke **u cjelini**.

Dokument predstavlja zvanični primjerak Odluke za konkretni Konkurs.

Dokument se **ne** uređuje kroz Platformu. Platforma iz dokumenta **ne** izračunava ponovo rezultate. Postavljanje dokumenta **ne** mijenja zaključanu konačnu rang-listu.

Ovo poglavlje **ne** određuje format fajla, maksimalnu veličinu, naziv fajla, storage lokaciju ni tehničke validacije. To pripada tehničkoj specifikaciji.

Postupak kontrolisane zamjene pogrešno postavljenog dokumenta **nije** definisan. Ovo poglavlje ga **ne** uvodi.

## 15.7. Objavljivanje i dostavljanje

### 15.7.1. Objavljivanje zvanične Odluke na Platformi

Nakon postavljanja potpisane zvanične Odluke Administrator konkursa objavljuje Odluku na Platformi.

Javni objekat je **elektronski primjerak fizički potpisane zvanične Odluke**.

**Ne** objavljuje se:

* živi, ponovo generisani Predlog kao zamjena za zvanični dokument;
* nepotpisani Predlog;
* ponovo generisana rang-lista kao zamjena za zvaničnu Odluku.

Objavljivanje je **posebna radnja** nakon postavljanja. Objavljivanje **ne** mijenja rezultate niti rang-listu.

Objava na Platformi **nije** donošenje Odluke. Administrator konkursa objavljuje već donesenu zvaničnu Odluku, u skladu sa Poglavljem 3.3.

### 15.7.2. Rok za objavljivanje Odluke

Zvanična Odluka mora biti objavljena najkasnije u roku od **45 dana od isteka roka za podnošenje Prijava**.

Rok se **ne** računa od:

* generisanja Predloga;
* fizičkog potpisa sekretara;
* zaključivanja Konkursa.

Ovo je poslovni rok. Ne uvodi se automatska objava 45. dana niti novo ponašanje za prekoračenje roka bez posebne poslovne odluke.

### 15.7.3. Dostavljanje Odluke podržanim Podnositeljkama

Zvanična Odluka se **van Platforme** dostavlja samo Podnositeljkama čiji su Biznis planovi Odlukom **podržani** za dodjelu sredstava.

Ne dostavlja se kroz Platformu. Ne uvodi se email, Platform notification, PDF prilog ni link kao obavezni sistemski workflow dostave.

Dostava **ne** obuhvata sve Podnositeljke samo zato što imaju Podnesenu Prijavu.

Predmet dostavljanja je **zvanična Odluka**, ne Predlog.

### 15.7.4. Ostali kanali javnog objavljivanja

Platforma je odgovorna za objavljivanje zvanične Odluke na **digital.kotor.me**.

Ostali propisani kanali objavljivanja, uključujući internet stranicu Opštine Kotor, oglasnu tablu Opštine i medije (uključujući lokalni javni emiter), odvijaju se **van Platforme**.

Platforma njima **ne** upravlja i **ne** evidentira njihovo izvršenje.

## 15.8. Mogućnost raspisivanja drugog Konkursa

Ako sredstva namijenjena podršci ženskom preduzetništvu nijesu utrošena, odnosno nijesu u potpunosti raspoređena do kraja trećeg kvartala, može se raspisati drugi Konkurs.

Poglavlje 15 evidentira **isključivo** ovu poslovnu mogućnost. Kanonska razrada ostaje u `KN-BM-003` §13.8.

Ovo poglavlje **ne** određuje kreiranje drugog Konkursa, novu instancu, kopiranje konfiguracije, rokove, iznose ni workflow drugog Konkursa.

## 15.9. Granica prema Poglavlju 16

Poglavlje 15 završava poslovni tok:

* zaključana konačna rang-lista;
* Predlog Odluke;
* zvanična Odluka;
* zaključivanje / arhiva;
* objavljivanje zvanične Odluke na Platformi.

Detaljne funkcionalne zabrane, authorization pravila i server-side enforcement kojima se štite zaključani rezultati i završeni Konkurs pripadaju **Poglavlju 16**.

Ovo poglavlje ih **ne** razrađuje.

---


# 16. Funkcionalne zabrane i enforcement

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 17. V1 granica

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 18. Prihvatni kriterijumi

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 19. Sljedivost

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

**Kraj dokumenta KN-FS-003 v0.1.11**
