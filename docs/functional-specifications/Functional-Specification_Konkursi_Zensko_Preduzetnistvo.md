# Digital Kotor
# Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu
## Modul: Konkursi

**Oznaka dokumenta:** KN-FS-003
**Naziv:** Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu
**Modul:** Konkursi
**Namespace:** KN
**Tip konkursa:** Žensko preduzetništvo
**Status dokumenta:** U IZRADI
**Verzija:** 0.1.2
**Datum:** 2026-08-27

Povezani dokumenti:

* Registar oznaka: **KN-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md`
* Zajednički poslovni model modula Konkursi: **KN-BM-001** — `docs/business-model/Business_Model_Konkursi.md` (USVOJEN v1.0.0)
* Poslovni profil: **KN-BM-003** — `docs/business-model/Business_Model_Konkursi_Zensko_Preduzetnistvo.md` (USVOJEN v1.0.2)
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
| 7. Prijava Podnositeljke | NIJE ZAPOČETO |
| 8. Privatnost Prijava | NIJE ZAPOČETO |
| 9. Istek roka i pristup Komisije | NIJE ZAPOČETO |
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

Radna struktura Poglavlja 7–19 je odobrena. Sadržaj tih poglavlja **nije** odobren ovim korakom.

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
* može se povući samo do isteka roka.

**Povučena:**

* ne nastavlja postupak;
* ne može se uređivati;
* ne može se vratiti u Podnesena.

Gdje usvojeni tok to predviđa, Podnositeljka podnosi Prigovor Komisiji putem digitalnog servisa (`KN-BM-003` §9.2). Detalj: Poglavlja 4 i 10.

Polja, validacije i forme nisu predmet ovog poglavlja. Detalj: Poglavlja 6–8 i 10.

### Ograničenja

* nema pristup kompletnoj Prijavi druge Podnositeljke;
* ograničenje važi tokom aktivnog Konkursa, evaluacije, nakon zaključenja i nakon arhiviranja;
* arhiviranje samo po sebi ne otvara tuđe Prijave;
* javna rang-lista / konačna Odluka / javni rezultat nisu obrazac Prijave, biznis plan, prilozi ni drugi nejavni dijelovi tuđeg dosijea;
* Podnesena Prijava **nije** izmjenjiva, ni dok rok još traje;
* nakon isteka roka nema uređivanja, podnošenja, povlačenja ni brisanja Prijave.

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

Podnesena Prijava **nije** izmjenjiva i **nije** brisiva. Može se samo povući do isteka roka. Povučena Prijava se ne vraća u Podnesena.

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

* nema novih izmjena, podnošenja, brisanja ni povlačenja Prijave;
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
* **Podnesena**;
* **Povučena**.

Istek roka **nije** dodatno stanje Prijave.

**U pripremi**, samo dok rok za prijavu traje:

* uređivanje: DA;
* brisanje: DA;
* podnošenje: DA.

**Podnesena** nastaje eksplicitnim podnošenjem. Nakon podnošenja sadržaj je nepromjenjiv.

* uređivanje: NE;
* brisanje: NE;
* povlačenje: DA, samo dok rok traje.

**Povučena** više ne učestvuje u postupku.

* uređivanje: NE;
* ponovno aktiviranje: NE;
* povratak u Podnesena: NE.

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

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 8. Privatnost Prijava

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

---

# 9. Istek roka i pristup Komisije

Status poglavlja: NIJE ZAPOČETO

Sadržaj ovog poglavlja biće definisan u narednom odobrenom dokumentacionom koraku.

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

**Kraj dokumenta KN-FS-003 v0.1.2**
