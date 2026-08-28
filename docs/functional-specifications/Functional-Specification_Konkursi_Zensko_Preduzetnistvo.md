# Digital Kotor
# Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu
## Modul: Konkursi

**Oznaka dokumenta:** KN-FS-003
**Naziv:** Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu
**Modul:** Konkursi
**Namespace:** KN
**Tip konkursa:** Žensko preduzetništvo
**Status dokumenta:** U IZRADI
**Verzija:** 0.1.5
**Datum:** 2026-08-28

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
| 10. Prva sjednica, administrativna provjera i Prigovor | NIJE ZAPOČETO |
| 11. Eliminatorni kriterijumi | NIJE ZAPOČETO |
| 12. Druga sjednica i usmeno obrazloženje | NIJE ZAPOČETO |
| 13. Individualno ocjenjivanje | NIJE ZAPOČETO |
| 14. Rang-lista, iznosi i treća sjednica | NIJE ZAPOČETO |
| 15. Predlog Odluke, zatvaranje, arhiva i objava | NIJE ZAPOČETO |
| 16. Funkcionalne zabrane i enforcement | NIJE ZAPOČETO |
| 17. V1 granica | NIJE ZAPOČETO |
| 18. Prihvatni kriterijumi | NIJE ZAPOČETO |
| 19. Sljedivost | NIJE ZAPOČETO |

Radna struktura Poglavlja 10–19 je odobrena. Sadržaj tih poglavlja **nije** odobren ovim korakom.

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

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 11. Eliminatorni kriterijumi

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 12. Druga sjednica i usmeno obrazloženje

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 13. Individualno ocjenjivanje

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 14. Rang-lista, iznosi i treća sjednica

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 15. Predlog Odluke, zatvaranje, arhiva i objava

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

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

**Kraj dokumenta KN-FS-003 v0.1.5**
