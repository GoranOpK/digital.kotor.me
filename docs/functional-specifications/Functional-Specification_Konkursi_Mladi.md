# Digital Kotor
# Funkcionalna specifikacija konkursa za podršku preduzetništvu mladih
## Modul: Konkursi

**Oznaka dokumenta:** KN-FS-002
**Naziv:** Funkcionalna specifikacija konkursa za podršku preduzetništvu mladih
**Modul:** Konkursi
**Namespace:** KN
**Tip konkursa:** Konkurs za podršku preduzetništvu mladih
**Status dokumenta:** USVOJEN
**Verzija:** 1.0.4
**Datum:** 2026-09-11

Povezani dokumenti:

* Registar oznaka: **KN-RG-001 v1.0.14** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` (USVOJENO)
* Zajednički poslovni model modula Konkursi: **KN-BM-001 v1.0.1** — `docs/business-model/Business_Model_Konkursi.md` (USVOJEN)
* Poslovni profil mladih: **KN-BM-002 v1.0.5** — `docs/business-model/Business_Model_Konkursi_Mladi.md` (USVOJEN)
* Zajedničke funkcionalnosti modula Konkursi: **KN-FS-001** — `docs/functional-specifications/Functional-Specification_Konkursi.md` (postoji; USVOJENO)
* Funkcionalna specifikacija ženskog preduzetništva: **KN-FS-003 v0.1.22** — `docs/functional-specifications/Functional-Specification_Konkursi_Zensko_Preduzetnistvo.md` (U IZRADI) — **samo strukturni obrazac i uporedni izvor**; nije poslovni izvor pravila mladih
* Zajednička tehnička specifikacija modula Konkursi: **KN-TS-001** — `docs/technical-specifications/Technical-Specification_Konkursi.md` (postoji; NACRT)

Ovaj dokument **ne** mijenja `KN-BM-001` niti `KN-BM-002`.

Ovaj dokument **ne** tvrdi da je opisano ponašanje već implementirano na Platformi. Implementacija se usklađuje sa usvojenim BM i ovom specifikacijom, a ne obrnuto (`BM-KN-012`; `docs/METHODOLOGY.md` §3.3).

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|-------|------|
| 0.1.0 | 2026-09-03 | Kreiran KN-FS-002 i popunjena Poglavlja 1–4: identitet i granica V1, dokumentaciona hijerarhija, akteri i ovlašćenja, funkcionalna stanja i odvojeni rezultati postupka. |
| 0.1.1 | 2026-09-03 | Popunjena Poglavlja 5 i 6 funkcionalnom razradom godišnje instance, konfiguracije Poziva, ručnog čuvanja i objavljivanja, zavodnog broja, prvog Poziva u drugom kvartalu i roka za prijave; primijenjena odobrena odluka F-02 o naknadnom povezivanju Komisije. |
| 0.1.2 | 2026-09-03 | Popunjena Poglavlja 7–10 funkcionalnom razradom kreiranja prijave, izbora obrazaca M1a/M1b, obrasca M2, kataloga prateće dokumentacije, kontrola prije podnošenja i konačnog zaključavanja prijave. |
| 0.1.3 | 2026-09-04 | Popunjena Poglavlja 11–14 funkcionalnom razradom privatnosti i pregleda dokumentacije, isteka roka i pristupa Komisije, prve sjednice, M3 i administrativne provjere, te obavještenja i prigovora. |
| 0.1.4 | 2026-09-04 | Popunjena Poglavlja 15–17 funkcionalnom razradom usmenog obrazloženja, individualnog ocjenjivanja i dodatnih bodova. |
| 0.1.5 | 2026-09-07 | Popunjena Poglavlja 18–21 funkcionalnom razradom eliminatornih kriterijuma, obračuna i praga, rangiranja i izjednačenja, te budžeta i raspodjele. |
| 0.1.6 | 2026-09-07 | Popunjena Poglavlja 22 i 23 funkcionalnom razradom drugog Poziva i zaključivanja odnosno arhiviranja Poziva. |
| 0.1.7 | 2026-09-07 | Popunjena Poglavlja 24–28 objedinjenim zabranama, granicom V1, indeksom prihvatnih kriterijuma, matricom sljedivosti i odloženim temama; dokument ostaje U IZRADI. |
| 1.0.0 | 2026-09-07 | USVOJEN — Završena i odobrena prva funkcionalna specifikacija profila konkursa za podršku preduzetništvu mladih. Funkcionalno su razrađena Poglavlja 1–28, poslovna pravila BM-ML-001–BM-ML-058 i granica V1. Detalji de minimis dokumentacije i službenih akata ostaju izričito odloženi van granice verzije 1.0.0. |
| 1.0.1 | 2026-09-08 | KN-PATCH-FS-007 — Funkcionalni tok prijave mladih usklađen sa zajedničkim katalogom statusa draft, submitted, evaluated, approved i rejected. Potpunost, prigovor, eliminatorni razlozi, ocjene, bodovi, rang, raspodjela i arhiviranje ostaju odvojene funkcionalne činjenice; rejected zbog nepotpunosti nastaje tek nakon odbijenog prigovora ili isteka roka bez prigovora. |
| 1.0.2 | 2026-09-08 | KN-PATCH-FS-008 — Usklađeni izbor namjere, pravnog oblika, poslovne faze, obrazaca M1a/M1b i četiri dokumentaciona paketa. Privredno društvo obuhvata DOO, AD, OD i KD. Neregistrovano fizičko lice bira namjeru; registrovani preduzetnik ili društvo bira fazu koju Komisija provjerava. Nepodržani identitet se ne svrstava u `ostalo`. |
| 1.0.3 | 2026-09-09 | KN-PATCH-FS-009 — Elektronski M3 prikazuje sva tri eliminatorna kriterijuma kao odvojene stavke. Jedno objedinjeno obavještenje, jedan prigovor na aktivirane kriterijume, odluka po kriterijumu i `rejected` tek nakon konačnosti makar jednog razloga. |
| 1.0.4 | 2026-09-11 | Administrativni closeout pokazivača: `KN-FS-001` i `KN-TS-001` označeni kao postojeći dokumenti (`USVOJENO` / `NACRT`) u povezanim dokumentima, hijerarhiji, §2.2 i tabeli izvora. Funkcionalna pravila nijesu mijenjana. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Dokument ima status `USVOJEN`. Naknadne izmjene zahtijevaju novu verziju i odgovarajući PATCH. Funkcionalnosti se identifikuju numerisanim poglavljima, naslovima, izvorima `BM-ML-*` i prihvatnim kriterijumima. Poseban namespace `FR-ML-*` ili `FS-ML-*` **nije** uveden (odluka F-01).

---

## Svrha dokumenta

Dokument je funkcionalna specifikacija tipa konkursa **Konkurs za podršku preduzetništvu mladih**. Prevodi usvojena poslovna pravila profila `KN-BM-002` u testabilno ponašanje Platforme. Nije Business Model i nije Technical Specification.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Identitet, svrha i granice | USVOJENO — započeto u v0.1.0 |
| 2. Odnos prema BM, FS i TS dokumentaciji | USVOJENO — započeto u v0.1.0 |
| 3. Akteri i funkcionalna ovlašćenja | USVOJENO — započeto u v0.1.0 |
| 4. Funkcionalna stanja i odvojeni rezultati postupka | USVOJENO — započeto u v0.1.0 |
| 5. Godišnja instanca i konfiguracija Poziva | USVOJENO — uneseno u v0.1.1 |
| 6. Objavljivanje Poziva i rok za prijave | USVOJENO — uneseno u v0.1.1 |
| 7. Prijava podnosioca | USVOJENO — uneseno u v0.1.2 |
| 8. Obrasci M1a/M1b i M2 | USVOJENO — uneseno u v0.1.2 |
| 9. Prateća dokumentacija | USVOJENO — uneseno u v0.1.2 |
| 10. Podnošenje i zaključavanje | USVOJENO — uneseno u v0.1.2 |
| 11. Privatnost i pregled dokumenata | USVOJENO — uneseno u v0.1.3 |
| 12. Istek roka i pristup Komisije | USVOJENO — uneseno u v0.1.3 |
| 13. Prva sjednica, M3 i tri eliminatorna kriterijuma | USVOJENO — uneseno u v0.1.3 |
| 14. Obavještenja i prigovori | USVOJENO — uneseno u v0.1.3 |
| 15. Usmeno obrazloženje | USVOJENO — uneseno u v0.1.4 |
| 16. Individualno ocjenjivanje | USVOJENO — uneseno u v0.1.4 |
| 17. Dodatni bodovi | USVOJENO — uneseno u v0.1.4 |
| 18. Eliminatorni kriterijumi | USVOJENO — uneseno u v0.1.5 |
| 19. Obračun i prag | USVOJENO — uneseno u v0.1.5 |
| 20. Rangiranje i izjednačenje | USVOJENO — uneseno u v0.1.5 |
| 21. Budžet i raspodjela | USVOJENO — uneseno u v0.1.5 |
| 22. Drugi Poziv | USVOJENO — uneseno u v0.1.6 |
| 23. Zaključivanje i arhiviranje Poziva | USVOJENO — uneseno u v0.1.6 |
| 24. Funkcionalne zabrane i zaštita poslovnih pravila | USVOJENO — uneseno u v0.1.7 |
| 25. Granica V1 | USVOJENO — uneseno u v0.1.7 |
| 26. Objedinjeni prihvatni kriterijumi | USVOJENO — uneseno u v0.1.7 |
| 27. Matrica sljedivosti | USVOJENO — uneseno u v0.1.7 |
| 28. Odložene teme i teme van V1 | USVOJENO — uneseno u v0.1.7 |

Poglavlja 1–4 započeta su u verziji 0.1.0 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 5 i 6 unesena su u verziji 0.1.1 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 7–10 unesena su u verziji 0.1.2 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 11–14 unesena su u verziji 0.1.3 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 15–17 unesena su u verziji 0.1.4 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 18–21 unesena su u verziji 0.1.5 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 22 i 23 unesena su u verziji 0.1.6 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0.

Poglavlja 24–28 unesena su u verziji 0.1.7 i imaju status `USVOJENO`. Formalno su usvojena u v1.0.0. Nijedno poglavlje ovog dokumenta nije `PLANIRANO`. Dokument i sva poglavlja imaju status `USVOJENO`.

Matrica sljedivosti `KN-BM-002` → `KN-FS-002` nalazi se u Poglavlju 27.

---

# Pravila upravljanja dokumentom

1. `KN-FS-002` specificira funkcionalno ponašanje Platforme za tip konkursa Konkurs za podršku preduzetništvu mladih.
2. Poslovna pravila ostaju u `KN-BM-002` (profil) i `KN-BM-001` (zajednički sloj).
3. Ovaj dokument ne mijenja i ne reinterpretira usvojena poslovna pravila.
4. Postojeća implementacija nije izvor funkcionalnog pravila.
5. Odstupanja implementacije od BM/FS dokumentuju se u tehničkoj/gap analizi, ne u ovom dokumentu kao nova pravila (`docs/METHODOLOGY.md` §3.3).
6. `KN-FS-003` služi samo kao strukturni obrazac i uporedni izvor. Nije poslovni izvor pravila mladih i ne smije se prepisivati.
7. Funkcionalnosti se identifikuju numerisanim poglavljima, naslovima, izvorima `BM-ML-*` i prihvatnim kriterijumima. Oznake `FR-ML-*` i `FS-ML-*` se ne uvode (odluka F-01).
8. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno uvoditi poslovna ni funkcionalna pravila van odobrenog dokumentacionog koraka.

---

## Sadržaj

1. Identitet, svrha i granice
2. Odnos prema BM, FS i TS dokumentaciji
3. Akteri i funkcionalna ovlašćenja
4. Funkcionalna stanja i odvojeni rezultati postupka
5. Godišnja instanca i konfiguracija Poziva
6. Objavljivanje Poziva i rok za prijave
7. Prijava podnosioca
8. Obrasci M1a/M1b i M2
9. Prateća dokumentacija
10. Podnošenje i zaključavanje
11. Privatnost i pregled dokumenata
12. Istek roka i pristup Komisije
13. Prva sjednica, M3 i tri eliminatorna kriterijuma
14. Obavještenja i prigovori
15. Usmeno obrazloženje
16. Individualno ocjenjivanje
17. Dodatni bodovi
18. Eliminatorni kriterijumi
19. Obračun i prag
20. Rangiranje i izjednačenje
21. Budžet i raspodjela
22. Drugi Poziv
23. Zaključivanje i arhiviranje Poziva
24. Funkcionalne zabrane i zaštita poslovnih pravila
25. Granica V1
26. Objedinjeni prihvatni kriterijumi
27. Matrica sljedivosti
28. Odložene teme i teme van V1

---

# 1. Identitet, svrha i granice

Status poglavlja: USVOJENO

`KN-FS-002` je funkcionalna specifikacija tipa konkursa **Konkurs za podršku preduzetništvu mladih** u modulu **Konkursi**.

Dokument određuje kako Platforma ostvaruje usvojena poslovna pravila tog profila: koje akcije dozvoljava, kome, u kojoj fazi, sa kojom vidljivošću, kojim validacijama i kojim posljedicama.

Konkurs za podršku preduzetništvu mladih **nije** zaseban dokumentacioni modul. Pripada namespace-u `KN` (`DK-DS-001` §1).

Dokument se odnosi na **V1**.

## 1.1. Izvor istine

Primarni poslovni SSOT ovog profila je `KN-BM-002` v1.0.5.

Zajednički poslovni SSOT modula Konkursi je `KN-BM-001` v1.0.1.

`KN-FS-002` razrađuje usvojena poslovna pravila `KN-BM-002` u testabilno ponašanje Platforme.

`KN-FS-002`:

* **ne** mijenja poslovna pravila;
* **ne** donosi pravna tumačenja Odluke o podršci preduzetništvu mladih;
* **ne** određuje tehničku implementaciju;
* **ne** prepisuje `KN-FS-003`;
* **ne** izvodi pravila iz trenutnog koda;
* **ne** usklađuje poslovni model sa postojećom implementacijom.

Postojeća implementacija može se naknadno auditovati prema ovoj specifikaciji. Odstupanja implementacije pripadaju tehničkoj i gap analizi, ne reinterpretaciji poslovnih pravila.

## 1.2. Granica V1

V1 funkcionalni obuhvat na Platformi završava se **konačnim rezultatom konkursa, evidentiranjem raspodjele sredstava i arhiviranjem konkursnog postupka**, u skladu sa `KN-BM-002` §2.6 i `KN-PATCH-BM-010`.

V1 obuhvata:

* godišnju instancu;
* prvi Poziv;
* eventualni drugi Poziv, kada se ispune uslovi iz `BM-ML-049`;
* pripremu, čuvanje i objavljivanje Poziva;
* podnošenje prijava;
* administrativnu provjeru;
* prigovore;
* usmeno obrazloženje u potvrđenoj granici `KN-BM-002` (`BM-ML-034`; kriterijum 10 u `BM-ML-038`);
* ocjenjivanje;
* dodatne bodove;
* eliminatorne kriterijume;
* rangiranje;
* evidentiranje raspodjele;
* arhiviranje konkursnog postupka.

Van V1 ostaju:

* generisanje ugovora;
* dostavljanje i potpisivanje ugovora;
* upravljanje ugovorom;
* isplata sredstava;
* realizacija projekta;
* praćenje ugovornih obaveza;
* elektronsko popunjavanje ili upload obrazaca M4 i M4a;
* obrada faktura i bankovnih izvoda nakon realizacije;
* kontrola i odobravanje izvještaja;
* povraćaj sredstava;
* de minimis dokumentacija i njena obrada;
* detaljno generisanje, dostavljanje i objavljivanje službenih akata, jer je pitanje 10 iz `KN-BM-002` Poglavlja 4 odloženo.

Ovo poglavlje **ne** uvodi ekrane, tokove ni API-je za procese van V1.

## 1.3. Sačuvane poslovne granice

Sljedeća pravila ostaju važeća poslovna pravila. Njihovo navođenje **ne** uvodi V1 funkcionalni proces.

| Pravilo | Šta FS zadržava u V1 | Šta FS ne uvodi u V1 |
|---------|----------------------|----------------------|
| `BM-ML-011` | Neregistrovano fizičko lice smije podnijeti prijavu. Registracija, poreska evidencija i žiro obezbjeđuju se najkasnije do dana potpisivanja Ugovora iz člana 26. | Operativnu provjeru pred ugovor. |
| `BM-ML-018` | Neizvršeno ranije izvještavanje M4/M4a ostaje eliminatorni uslov u konkursnom postupku. | Modul izvještavanja, upload ili obradu M4/M4a. |
| `BM-ML-032` | Dokaz o žiro računu nije obavezan za početno podnošenje. | Blokadu podnošenja zbog žira; provjeru pred ugovor. |
| `BM-ML-056` | Prihvatljivost troškova počinje stvarnim datumom zaključenja ugovora iz člana 26, ne datumom rezultata ni objave. | Generisanje, dostavu, potpis ili upravljanje ugovorom; isplatu. |
| `BM-ML-057` | M4 je narativni, M4a finansijski izvještaj; nijesu dio početne prijave. | Popunjavanje, upload ili obradu izvještaja. |

## 1.4. Šta ovaj dokument nije

`KN-FS-002` nije:

* poslovni profil;
* tehnička specifikacija;
* opis trenutnog koda;
* UI pixel-level specifikacija;
* izvor pravnih pravila Odluke o podršci preduzetništvu mladih;
* prepis `KN-FS-003`.

Tehnička realizacija (modeli, tabele, klase, rute, policies, servisi, storage) ne pripada ovom dokumentu.

## 1.5. Prihvatni kriterijumi — granica V1

### 1.5.1 — Procesi van V1 nijesu V1 funkcije

**Ako:** korisnik pokuša da kroz profilnu funkciju `KN-FS-002` pokrene ugovor, dostavu ili potpis ugovora, isplatu, realizaciju, praćenje ugovornih obaveza, popunjavanje ili upload M4/M4a, obradu faktura i izvoda, povraćaj sredstava ili de minimis dokumentaciju.

**Kada:** Platforma ocjenjuje da li je radnja dio V1 obuhvata.

**Onda:** Platforma **ne** nudi tu radnju kao V1 funkciju ovog profila. Navođenje `BM-ML-011`, `BM-ML-018`, `BM-ML-032`, `BM-ML-056` i `BM-ML-057` ne uvodi te procese.

Izvor: `KN-BM-002` §2.6; `BM-ML-011`; `BM-ML-018`; `BM-ML-032`; `BM-ML-056`; `BM-ML-057`; `KN-PATCH-BM-010`.

---

# 2. Odnos prema BM, FS i TS dokumentaciji

Status poglavlja: USVOJENO

Hijerarhija dokumentacije za ovaj profil:

* `KN-RG-001`
* → `KN-BM-001` (zajednička pravila modula Konkursi)
* → `KN-BM-002` (poslovna pravila profila mladih)
* → `KN-FS-002` (ovo dokument)
* → `KN-TS-001` (zajednička tehnička specifikacija; postoji; NACRT).

## 2.1. Autoritet

* `KN-BM-002` je SSOT za poslovna pravila mladih.
* `KN-BM-001` je autoritativan za zajednička poslovna pravila modula Konkursi.
* `KN-FS-002` razrađuje samo potvrđena pravila iz tih izvora u funkcionalno ponašanje Platforme.
* FS može referencirati BM pravila bez nepotrebnog ponavljanja pravnog i poslovnog obrazloženja.
* FS **ne može** preglasati BM.
* TS **ne može** preglasati FS ni BM.
* Implementacija i postojeći kod **nijesu** izvor poslovne istine.
* Tehnički detalji pripadaju TS-u.

Sljedivost: BM → FS → TS → implementacija → testovi, gdje je primjenjivo (`DK-DS-001` §11; `docs/METHODOLOGY.md`).

## 2.2. Odnos prema KN-FS-001

`KN-FS-001` je usvojeni zajednički i konfigurabilni funkcionalni sloj modula Konkursi (`docs/functional-specifications/Functional-Specification_Konkursi.md`; status USVOJENO).

`KN-FS-002` je profilni funkcionalni sloj tipa konkursa za podršku preduzetništvu mladih.

Postojanje `KN-FS-001` **ne** daje `KN-FS-002` pravo da izmišlja zajednička pravila umjesto zajedničkog FS-a, niti da ih izvede iz koda.

`KN-FS-002` se kontrolisano usklađuje sa `KN-FS-001` kada je to potrebno. To usklađivanje nije sadržaj verzije 0.1.0.

## 2.3. Odnos prema KN-FS-003 i KN-BM-003

`KN-FS-003` **nije** nadređen `KN-FS-002`.

`KN-FS-003` v0.1.22 služi samo kao strukturni obrazac i uporedni izvor. Zajednički funkcionalni obrazac smije se preuzeti samo kada **nije** suprotan `KN-BM-002`.

`KN-BM-003` je uporedni poslovni profil ženskog preduzetništva. Nije SSOT za mlade. Pravila ženskog profila se ne prepisuju.

## 2.4. Tabela izvora

| ID | Naziv | Verzija | Status | Uloga u KN-FS-002 |
|----|-------|---------|--------|-------------------|
| DK-DS-001 | Digital Kotor Documentation Standard v1 | 1.0.0 | USVOJENO | Document ID, tipovi, statusi, sljedivost, folderi |
| METHODOLOGY.md | Metodologija dokumentacije | 1.0 | AKTIVAN | FS se piše prema BM, ne prema kodu; BM → FS → TS |
| KN-RG-001 | Registar skraćenica i oznaka dokumentacije Konkursa | 1.0.13 | USVOJENO | kanonski Document ID i evidencija ovog dokumenta |
| KN-BM-001 | Zajednički poslovni model modula Konkursi | 1.0.1 | USVOJEN | zajednička pravila `BM-KN-001`–`BM-KN-015` |
| KN-BM-002 | Poslovni profil konkursa za podršku preduzetništvu mladih | 1.0.4 | USVOJEN | **SSOT** poslovnih pravila mladih; `BM-ML-001`–`BM-ML-058` |
| KN-BM-003 | Poslovni profil: Konkurs za podršku ženskom preduzetništvu | 1.0.7 | USVOJEN | samo uporedni BM; nije izvor pravila mladih |
| KN-FS-001 | Zajedničke funkcionalnosti modula Konkursi | 0.2.13 | USVOJENO | zajednički FS; ne ovlašćuje izmišljanje zajedničkih pravila |
| KN-FS-003 | Funkcionalna specifikacija: Konkurs za podršku ženskom preduzetništvu | 0.1.22 | U IZRADI | samo strukturni/uporedni FS |
| KN-TS-001 | Zajednička tehnička specifikacija modula Konkursi | 0.1.0 | NACRT | zajednički tehnički sloj; postoji; NACRT |

## 2.5. Lokalne oznake funkcionalnih odluka F-*

Oznake `F-01`, `F-02`, `F-05` i `F-06` jesu lokalne oznake odobrenih funkcionalnih odluka, korišćene radi sljedivosti izrade `KN-FS-002`.

* nijesu Document ID;
* nijesu kanonske interne funkcionalne oznake;
* ne predstavljaju namespace `FR-ML-*` ili `FS-ML-*`;
* ne upisuju se kao nove oznake u `KN-RG-001`;
* numeričke praznine nijesu rezervisane i nemaju poslovno značenje;
* `F-03` i `F-04` nijesu uvedene kao važeće odluke.

Ova napomena **ne** uvodi novu funkcionalnu oznaku.

---

# 3. Akteri i funkcionalna ovlašćenja

Status poglavlja: USVOJENO

Ovo poglavlje određuje **ko** na Platformi koristi funkcije ovog profila. Ne određuje u potpunosti **kada** i **kako**; to pripada Poglavlju 4 i narednim poglavljima.

Poslovni akter iz `KN-BM-002` **nije** automatski funkcionalni akter ovog dokumenta.

## 3.1. Princip funkcionalnog aktera

Funkcionalni akter je akter koji stvarno obavlja, prima ili kontroliše profilnu funkciju **preko Platforme**.

Poslovni akter iz BM-a postaje funkcionalni akter `KN-FS-002` samo ako ima takvu interakciju u usvojenom V1 profilu.

Ovlašćenja su ograničena fazom Poziva i usvojenim pravilima `KN-BM-002`. Vidljivost je sama po sebi funkcionalno ovlašćenje.

Odsustvo ovlašćenja znači da akcija ili sadržaj **ne** smiju biti dostupni kroz redovnu interakciju sa Platformom.

Ovo poglavlje ne određuje rute, middleware, policies ni bazu.

Ovo poglavlje **ne** opisuje način imenovanja Komisije van potvrđenog poslovnog modela.

## 3.2. Komisija — sastav, kvorum i punovažne odluke

Komisija za raspodjelu sredstava ima **tačno tri mjesta**. Predsjednik Komisije **jeste jedan od ta tri člana** (`BM-ML-001`).

Administrator Konkursa **nije** automatski član Komisije (`BM-KN-014`).

Ovaj dokument razdvaja tri pravila koja se **ne** smiju poistovjećivati:

1. **formalno kompletiranje** — sva tri mjesta su popunjena;
2. **kvorum prve sjednice i administrativne provjere** — prisutna su najmanje dva člana;
3. **obavezno učešće sva tri člana** — prigovor, intervju odnosno usmeno obrazloženje, konačno individualno ocjenjivanje i druge punovažne odluke; prosjek se računa iz tri kompletne zaključene ocjene (`BM-ML-003`).

Način tehničkog evidentiranja prisustva **nije** predmet ovog poglavlja. Razrađuje se u kasnijem poglavlju ili u TS-u.

### Formalni sastav

Odluka F-02 i `BM-ML-001`:

* Poziv se može sačuvati i objaviti **prije** povezivanja sva tri člana;
* nepostojanje Komisije **ne** blokira objavljivanje;
* sva tri mjesta moraju biti formalno popunjena **najkasnije do isteka roka** za prijave;
* sistem **upozorava** administratora ako Komisija još nije formalno kompletirana;
* predsjednik se bira među ta tri člana;
* Komisija **nema** pristup prijavama dok rok traje (`BM-ML-005`);
* nakon isteka pristup dobijaju samo **važeći** članovi povezani sa konkretnim Pozivom;
* zamjene se vode sa revizijskim tragom, prema `BM-ML-007` i `BM-ML-039`.

Upozorenje o nekompletnoj Komisiji **nije** blokada objavljivanja.

### Prva sjednica i administrativna provjera

Administrativna provjera može početi kada su sva tri mjesta Komisije formalno popunjena i kada su na sjednici prisutna najmanje dva člana.

* prva sjednica i administrativna provjera mogu početi samo ako su sva tri mjesta formalno popunjena (`BM-ML-001`; F-02);
* **nije** potrebno da sva tri člana budu prisutna;
* dovoljan je kvorum od najmanje dva prisutna člana (`BM-ML-002`);
* bez najmanje dva prisutna člana sjednica i administrativna provjera se blokiraju odnosno odlažu;
* predsjednik rezultat rada Komisije, uključujući sva tri eliminatorna kriterijuma, evidentira u M3 (`BM-ML-035`).

Prva sjednica **ne** zahtijeva prisustvo sva tri člana.

### Radnje koje zahtijevaju sva tri člana

Sva tri člana potrebna su za (`BM-ML-003`):

* odlučivanje o prigovoru, uključujući odluku o svakom osporenom kriterijumu;
* intervju odnosno usmeno obrazloženje;
* konačno individualno ocjenjivanje;
* druge punovažne odluke određene poslovnim modelom.

Prosjek se računa iz tri kompletne zaključene ocjene. Ovaj uslov se **ne** prenosi na prvu sjednicu ni na administrativnu provjeru.

## 3.3. Podnosilac

Funkcionalni akter: **DA**. Osnov: `KN-BM-002` Poglavlja 7 i 8; `BM-ML-019`–`BM-ML-024`; `BM-ML-054`.

### Vidljivost

* javni podaci objavljenog Poziva;
* sopstvena prijava, uključujući sopstveno osnovno stanje prijave;
* evidentirani konačan rezultat, rang-lista i raspodjela, **samo** u obimu V1 evidencije; to **nije** kompletna prijava drugog podnosioca (`BM-ML-054`).

Platforma u V1 **evidentira** konačan rezultat, rang-listu i raspodjelu. Detaljno javno objavljivanje službenog rezultata ostaje odloženo kroz pitanje 10. V1 **ne** generiše i **ne** objavljuje Odluku o raspodjeli, Rješenje o dodjeli ni Rješenje o odbijanju. Ne uvodi se javni PDF lifecycle ženskog profila. Privatnost prijava ostaje prema `BM-ML-054`. Ovo poglavlje **ne** uvodi novo pravilo o javnim podacima ni javni sadržaj prijave.

### Radnje

Ovlašćenja zavise od osnovnog funkcionalnog stanja prijave. Detalj stanja: Poglavlje 4.

**U pripremi**, samo dok rok za prijavu traje:

* uređivati sopstvenu prijavu;
* obrisati sopstvenu prijavu;
* podnijeti sopstvenu prijavu.

**Podnesena:**

* sadržaj više nije izmjenjiv;
* ne može se obrisati;
* ne može se povući;
* ne može se ponovo podnijeti na istom Pozivu (`BM-ML-023`; `BM-KN-015`).

Gdje usvojeni tok to predviđa, podnosilac podnosi prigovor Komisiji putem digitalnog servisa (`BM-ML-036`). Detalj: Poglavlje 14.

### Ograničenja

* vidi samo svoje prijave;
* nema pristup kompletnoj prijavi drugog podnosioca;
* ograničenje važi tokom roka, nakon isteka, nakon ocjenjivanja i nakon arhiviranja;
* arhiviranje samo po sebi ne otvara tuđe prijave.

## 3.4. Administrator Konkursa

Funkcionalni akter: **DA**. Osnov: `KN-BM-002` `BM-ML-004`, `BM-ML-033`, `BM-ML-051`, `BM-ML-053`; `BM-KN-014`.

Na Platformi upravlja administrativnim radnjama Poziva u okviru usvojenih ovlašćenja, uključujući sažeto:

* pripremu i čuvanje godišnje instance i Poziva;
* unos službenog zavodnog broja dobijenog iz pisarnice;
* objavljivanje Poziva;
* povezivanje tri člana Komisije sa Pozivom, uključujući određivanje predsjednika među ta tri člana.

Detalj kreiranja, čuvanja i objave: Poglavlja 5, 6 i 22.

Tokom roka Administrator Konkursa vidi **samo zbirni broj podnesenih prijava** konkretnog Poziva. **Nema** sadržajni pristup prijavama, obrascima ni prilozima, ni prije ni poslije isteka roka (`BM-ML-004`).

Administrator Konkursa **ne može** biti član Komisije.

Administrator Konkursa **nema** ovlašćenje da:

* utvrđuje potpunost umjesto Komisije;
* ocjenjuje biznis plan;
* donosi odluku o iznosu podrške;
* mijenja individualne ocjene;
* zaključava i arhivira Poziv umjesto predsjednika Komisije;
* objavi Poziv automatski umjesto ručne radnje;
* kreira drugi Poziv automatski.

## 3.5. Član Komisije

Funkcionalni akter: **DA**. Osnov: `BM-ML-001`–`BM-ML-003`, `BM-ML-005`, `BM-ML-039`, `BM-ML-040`, `BM-ML-055`.

Član Komisije:

* **nema** pristup prijavama, obrascima ni prilozima **dok rok traje**;
* nakon isteka pristupa **samo Podnesenim** prijavama konkretnog Poziva za koji ima važeće povezivanje;
* **nema** pristup prijavama `U pripremi`;
* pregleda dokumente unutar Platforme, **bez** redovnog korisničkog preuzimanja (`BM-ML-055`);
* unosi sopstvene individualne ocjene, uključujući izmjenjiv **nacrt**;
* vidi samo sopstvene individualne ocjene dok sva tri člana ne završe individualno ocjenjivanje svih prijava uključenih u ciklus (`BM-ML-040`);
* nakon zaključavanja sopstvenih ocjena ne mijenja ih;
* **nema** ovlašćenje za operativne radnje koje BM dodjeljuje predsjedniku Komisije.

Detalj ocjenjivanja: Poglavlje 16.

## 3.6. Predsjednik Komisije

Funkcionalni akter: **DA**. Osnov: `BM-ML-001`, `BM-ML-002`, `BM-ML-035`, `BM-ML-040`, `BM-ML-042`, `BM-ML-048`; odluka F-05.

Predsjednik Komisije **jeste** jedan od tri člana Komisije.

Pri individualnom ocjenjivanju predsjednik ima:

* ista pravila ocjenjivanja;
* istu težinu;
* ista ograničenja tajnosti;
* **nema** privilegovan uvid u ocjene drugih članova dok sva tri člana ne završe ciklus;
* **nema** ovlašćenje da mijenja ocjenu drugog člana.

Sva ograničenja iz §3.5 važe i za predsjednika.

Dodatne Platform radnje predsjednik izvršava **u ime Komisije**, sažeto:

* evidentira rezultat tri eliminatorna kriterijuma na M3 (`BM-ML-035`);
* evidentira dodatne bodove kada su ispunjeni uslovi (`BM-ML-042`);
* evidentira iznose raspodjele (`BM-ML-048`);
* ručno zaključuje i arhivira Poziv kada su ispunjeni uslovi odluke F-05.

Predsjednik **nije** samostalni donosilac poslovne odluke Komisije tamo gdje BM zahtijeva Komisiju. Sistem **ne** ocjenjuje biznis plan i **ne** određuje iznos umjesto Komisije.

Detalj arhiviranja: Poglavlje 4 i Poglavlje 23.

## 3.7. Zamjenski član Komisije

Funkcionalni akter: **DA**, samo dok važi formalna zamjena. Osnov: `BM-ML-007`; `BM-ML-039`; O-01.

Zamjenski član od važećeg imenovanja ima prava i obaveze člana kojeg mijenja, u radnjama za koje je zamjena važeća.

Zamjena se vodi sa revizijskim tragom.

Za jednu prijavu i jedno mjesto Komisije individualnu ocjenu svih deset kriterijuma daje **jedno lice**: redovni član ili formalno imenovani zamjenski član. Nije dozvoljeno spajati dio ocjena redovnog i dio ocjena zamjenskog člana (`BM-ML-039`).

Ovo poglavlje **ne** razrađuje vanplatformsko imenovanje sekretara Sekretarijata.

## 3.8. Javni / neprijavljeni korisnik

Funkcionalni akter: **DA**, ograničeno. Osnov: `BM-ML-033`; `BM-ML-054`.

Javni ili neprijavljeni korisnik smije vidjeti javne podatke objavljenog Poziva na digitalnom servisu, u obimu koji kasnija poglavlja potvrde za objavu Poziva.

Javnost **ne** vidi sadržaj prijava, obrasce M1a/M1b i M2, priloge, individualne ocjene ni interne napomene Komisije.

## 3.9. Sistem

Funkcionalni izvršilac automatskih provjera i vremenskih uslova: **DA**.

Sistem **nije** donosilac poslovnih odluka.

Sistem smije:

* računati rok i onemogućiti novo podnošenje po isteku (`BM-ML-033`);
* upozoriti administratora ako Komisija nije formalno kompletirana (F-02);
* blokirati početak administrativne provjere ako nijesu formalno popunjena sva tri mjesta, ili ako nije evidentiran kvorum od najmanje dva prisutna člana (`BM-ML-001`; `BM-ML-002`; F-02);
* blokirati arhiviranje ako nijesu ispunjeni uslovi F-05;
* računati izvedene vrijednosti koje BM predviđa (prosjek, prag, gornji limiti), bez donošenja odluke o iznosu.

Sistem **ne**:

* utvrđuje potpunost umjesto Komisije (`BM-ML-035`);
* ocjenjuje biznis plan (`BM-ML-038`);
* donosi odluku o iznosu podrške (`BM-ML-048`);
* objavljuje Poziv automatski (`BM-ML-033`; `BM-ML-053`);
* kreira drugi Poziv automatski (`BM-ML-049`; `BM-ML-051`).

## 3.10. Akteri van profilnog funkcionalnog obuhvata Platforme

Sekretarijat za razvoj preduzetništva, komunalne poslove i saobraćaj ostaje poslovni akter `KN-BM-002`. U V1 **nije** profilni funkcionalni akter Platforme za imenovanje, službene akte, ugovor ili isplatu.

Administrator platforme **nije** profilni funkcionalni akter `KN-FS-002`. Samim postojanjem te uloge ne postaje Administrator Konkursa niti član Komisije.

## 3.11. Matrica funkcionalnih ovlašćenja

Vrijednosti se odnose na redovnu interakciju sa Platformom u ovom profilu. `USL` znači da važi samo u fazi i pod uslovom iz Poglavlja 4 i navedenog BM izvora. Tokovi kasnijih poglavlja nijesu ovdje razrađeni.

| Funkcija | Podnosilac | Admin | Predsjednik | Član | Zamjenski član | Javno | Sistem |
|----------|------------|-------|-------------|------|----------------|-------|--------|
| Vidi javne podatke objavljenog Poziva | DA | DA | DA | DA | DA | DA | — |
| Vidi sopstvenu prijavu | DA | NE | NE | NE | NE | NE | — |
| Vidi tuđu kompletnu prijavu | NE | NE | USL — nakon isteka, samo Podnesene konkretnog Poziva | USL — isto | USL — dok važi zamjena | NE | — |
| Vidi prijave U pripremi drugog lica | NE | NE | NE | NE | NE | NE | — |
| Kreira / uređuje / briše svoju prijavu | USL — U pripremi, dok rok traje | NE | NE | NE | NE | NE | — |
| Zbirni broj podnesenih prijava tokom roka | NE | DA | NE | NE | NE | NE | — |
| Sadržajni pristup prijavi, obrascima i prilozima | samo svojoj | NE | USL — nakon isteka | USL — nakon isteka | USL — nakon isteka, dok važi zamjena | NE | — |
| Pregled dokumenata bez preuzimanja | svoji | NE | USL | USL | USL | NE | — |
| Čuvanje i objava Poziva | NE | DA | NE | NE | NE | NE | NE — ne objavljuje automatski |
| Objava Poziva bez kompletirane Komisije | NE | DA — dozvoljeno; sistem upozorava | NE | NE | NE | NE | upozorenje, ne blokada objave |
| Povezivanje tri člana i predsjednika | NE | DA | NE | NE | NE | NE | — |
| Administrativna / eliminatorna provjera / M3 | NE | NE — ne sprovodi provjeru i ne mijenja M3 | USL — učestvuje ako je prisutan; evidentira sva tri kriterijuma u M3 | USL — učestvuje ako je prisutan | USL — učestvuje ako ima važeće ovlašćenje | NE | blokira početak ako Komisija nije formalno kompletirana ili ako nije evidentiran kvorum od najmanje dva prisutna člana; ne utvrđuje ispunjenost kriterijuma |
| Podnošenje prigovora | USL — svoja prijava sa najmanje jednim aktiviranim kriterijumom | NE | NE | NE | NE | NE | — |
| Odluka o prigovoru | NE | NE | USL — u ime Komisije | USL — sva tri člana | USL | NE | NE |
| Individualno ocjenjivanje | NE | NE | USL — samo svoje ocjene | USL — samo svoje | USL — svoje mjesto, bez spajanja ocjena | NE | NE — ne ocjenjuje |
| Uvid u tuđe individualne ocjene prije kraja ciklusa | NE | NE | NE | NE | NE | NE | — |
| Evidentiranje iznosa raspodjele | NE | NE | USL | NE | USL ako mijenja predsjednika | NE | NE — ne određuje iznos |
| Zaključivanje i arhiviranje Poziva | NE | NE | USL — F-05 | NE | USL ako mijenja predsjednika | NE | blokira ako uslovi nijesu ispunjeni |
| Automatsko kreiranje drugog Poziva | NE | NE | NE | NE | NE | NE | NE |

## 3.12. Prihvatni kriterijumi — ovlašćenja i F-02

### 3.12.1 — Administrator nema sadržajni pristup prijavi

**Ako:** korisnik ima ulogu Administratora Konkursa za konkretan Poziv.

**Kada:** pokuša da otvori sadržaj prijave, obrazaca M1a/M1b ili M2, priloga, individualnih ocjena ili interne napomene.

**Onda:** Platforma **ne** prikazuje taj sadržaj. Tokom roka Administrator vidi samo zbirni broj podnesenih prijava, bez liste identiteta i bez ulaska u konkretnu prijavu. Ograničenje važi i nakon isteka roka.

Izvor: `BM-ML-004`; `BM-KN-014`.

### 3.12.2 — Komisija nema pristup tokom roka

**Ako:** rok za podnošenje prijava konkretnog Poziva još traje.

**Kada:** predsjednik, član ili zamjenski član Komisije pokuša pristupiti prijavama tog Poziva.

**Onda:** Platforma **ne** omogućava pristup prijavama, obrascima ni prilozima.

Izvor: `BM-ML-005`; odluka F-02.

### 3.12.3 — Pristup Komisije nakon isteka roka

**Ako:** rok za podnošenje je istekao i korisnik je tada važeći član Komisije povezan sa konkretnim Pozivom.

**Kada:** pristupi prijavama tog Poziva.

**Onda:** Platforma omogućava pristup samo prijavama u stanju **Podnesena** koje pripadaju tom Pozivu. Prijave **U pripremi** ostaju nedostupne. Članstvo na drugom Pozivu **nije** dovoljno.

Izvor: `BM-ML-005`; `BM-ML-024`; odluka F-02.

### 3.12.4 — Objavljivanje Poziva bez kompletirane Komisije

**Ako:** Administrator Konkursa ima valjanu konfiguraciju Poziva potrebnu za objavu, a Komisija još nema sva tri određena člana.

**Kada:** pokrene objavljivanje Poziva.

**Onda:** Platforma **ne** blokira objavljivanje samo zbog nekompletirane Komisije. Predsjednik mora biti jedan od tri člana kada Komisija bude kompletirana.

Izvor: odluka F-02; `BM-ML-001`; `BM-ML-033`.

### 3.12.5 — Upozorenje za nekompletnu Komisiju

**Ako:** Poziv je sačuvan ili objavljen, a nijesu određena sva tri člana Komisije.

**Kada:** Administrator Konkursa pregleda ili uređuje taj Poziv, ili pokrene objavu.

**Onda:** Platforma prikazuje upozorenje da Komisija nije formalno kompletirana i da sva tri mjesta moraju biti popunjena najkasnije do isteka roka. Upozorenje **ne** zamjenjuje blokadu iz §3.12.6.A.

Izvor: odluka F-02.

### 3.12.6 — Administrativna provjera: formalni sastav, kvorum i dozvoljeni početak

Način tehničkog evidentiranja prisustva **nije** određen ovim kriterijumima.

#### A. Formalno kompletiranje

**Ako:** nijesu popunjena sva tri mjesta Komisije, uključujući predsjednika kao jednog od ta tri.

**Kada:** korisnik pokuša da započne administrativnu provjeru.

**Onda:** Platforma blokira početak provjere. Sistem ne utvrđuje potpunost umjesto Komisije.

Izvor: `BM-ML-001`; odluka F-02.

#### B. Kvorum

**Ako:** su sva tri mjesta Komisije formalno popunjena, ali je prisutno manje od dva člana.

**Kada:** korisnik pokuša da započne administrativnu provjeru.

**Onda:** Platforma blokira početak, odnosno sjednica mora biti odložena. Prva sjednica **ne** zahtijeva prisustvo sva tri člana.

Izvor: `BM-ML-002`.

#### C. Dozvoljeni početak

**Ako:** su sva tri mjesta formalno popunjena i prisutna su najmanje dva člana.

**Kada:** predsjednik pokrene administrativnu provjeru.

**Onda:** Platforma dozvoljava predsjedniku da u M3 evidentira sva tri eliminatorna kriterijuma.

Izvor: `BM-ML-001`; `BM-ML-002`; `BM-ML-035`; odluka F-02.

### 3.12.7 — Tajnost individualnih ocjena

**Ako:** ciklus individualnog ocjenjivanja konkretnog Poziva nije završen, jer nisu sva tri člana zaključala sve potrebne ocjene.

**Kada:** predsjednik, drugi član, zamjenski član, administrator ili podnosilac pokuša da vidi tuđu individualnu ocjenu.

**Onda:** Platforma **ne** prikazuje tuđu individualnu ocjenu. Predsjednik nema privilegovan uvid. Član vidi samo sopstveni nacrt ili sopstvenu zaključanu ocjenu.

Izvor: `BM-ML-040`; `BM-ML-039`.

---

# 4. Funkcionalna stanja i odvojeni rezultati postupka

Status poglavlja: USVOJENO

Ovo poglavlje određuje **kada** koje funkcije na Platformi jesu ili nijesu dostupne. Ne razrađuje forme, validacije polja ni tehničku realizaciju.

## 4.1. Princip razdvajanja slojeva

Razlikuju se najmanje pet slojeva:

1. **status prijave**;
2. **rezultat M3 za tri eliminatorna kriterijuma**;
3. **stanje prigovora**;
4. **stanje individualnog ocjenjivanja i ciklusa**;
5. **rangiranje, raspodjela i arhiviranje**.

Dodatno se razlikuju:

* **poslovni status** — kada ga BM prepoznaje;
* **funkcionalno stanje** — potrebno da Platforma kontroliše dostupne funkcije;
* **događaj / međaš** — nešto što se dogodi, a ne mora biti trajno stanje;
* **izvedeni uslov** — utvrđuje se iz podataka ili vremena, a ne kao zasebno trajno stanje.

Faza procesa **nije** automatski status.

Kanonski statusi prijave su **STORED** vrijednosti `draft`, `submitted`, `evaluated`, `approved` i `rejected` (`BM-ML-020`; `KN-PATCH-FS-007`). UI može koristiti poslovne oznake. Tehničko čuvanje kolona i enumova pripada TS sloju. Pet statusa **ne** zamjenjuje rezultate administrativne provjere, prigovor, eliminatore, ocjene, bodove, rang ili iznos.

## 4.2. Statusi prijave

Kanonski statusi prijave su:

| Tehnički status | UI oznaka | Značenje |
|----------------|-----------|----------|
| `draft` | U pripremi | Prijava se uređuje i još nije konačno podnesena |
| `submitted` | Podnesena | Prijava je konačno podnesena i zaključana |
| `evaluated` | Ocijenjena | Završene su sve potrebne ocjene i obračun |
| `approved` | Odobrena | Konačno podržana prijava sa evidentiranim iznosom |
| `rejected` | Odbijena | Konačno nepodržana ili isključena prijava, uz razlog |

**Nisu** statusi prijave:

* `Potpuna`;
* `Nepotpuna`;
* `Eliminisana`;
* `Povučena`;
* `Arhivirana`.

Istek roka **nije** dodatni status prijave (`BM-ML-020`; `BM-ML-024`). Arhiviranje je status **Poziva**, ne novi status prijave.

`draft` / `U pripremi`, samo dok rok traje: uređivanje DA, brisanje DA, podnošenje DA.

Nakon isteka roka prijava `draft` / `U pripremi` ostaje `draft`, sačuvana i samo za pregled podnosioca. Ne postaje `submitted` i ne prelazi automatski u `rejected` (`BM-ML-024`).

`submitted` nastaje eksplicitnim podnošenjem. Nakon toga: uređivanje NE, brisanje NE, povlačenje NE, ponovno podnošenje na istom Pozivu NE (`BM-ML-023`). Nije dozvoljen povratak u `draft`. Prijava ostaje zaključana u svim statusima nakon `submitted`. Promjena statusa **ne** otključava prijavu.

## 4.3. Rezultat M3 i tri eliminatorna kriterijuma

Rezultat M3 **nije** status prijave.

Elektronski M3 prikazuje **tačno tri** eliminatorna kriterijuma kao tri odvojene stavke (`BM-ML-035`):

1. prijava je nepotpuna;
2. korisnik koji je ranije dobio sredstva nije dostavio obavezne izvještaje M4 i M4a za ranije finansirani biznis plan;
3. biznis plan nije povezan sa prioritetnim oblastima konkursa.

Za prvi kriterijum Komisija evidentira `Potpuna` ili `Nepotpuna`. `Nepotpuna` znači da je prvi razlog aktiviran. Za drugi i treći kriterijum Komisija evidentira rezultat provjere i da li je razlog aktiviran.

Prijava ostaje `submitted` (`BM-ML-035`). Aktiviranje bilo kojeg kriterijuma, uključujući `Nepotpuna`, **ne** smije odmah postaviti `rejected`.

Sistemska provjera je pomoćna. Sistem **ne** donosi konačnu odluku o ispunjenosti kriterijuma. Platforma **ne** odlučuje da li je kriterijum ispunjen.

Administrativna i eliminatorna provjera na M3 može početi kada su sva tri mjesta Komisije formalno popunjena i kada su na sjednici prisutna najmanje dva člana.

Prva sjednica **ne** zahtijeva prisustvo sva tri člana. Ako nijesu formalno popunjena sva tri mjesta, početak je blokiran (`BM-ML-001`; F-02). Bez kvoruma od najmanje dva prisutna člana sjednica i M3 provjera se blokiraju odnosno odlažu (`BM-ML-002`). Predsjednik rezultat evidentira u M3 (`BM-ML-035`).

## 4.4. Stanja prigovora

Prigovor je zaseban tok (`BM-ML-036`; `BM-ML-037`):

* **Podnesen**;
* **Prihvaćen**;
* **Odbijen**.

`Prihvaćen` i `Odbijen` su konačni ishodi konkretnog prigovora na Platformi. Nije dozvoljeno vraćanje u `Podnesen` ni ponovno odlučivanje o istom prigovoru.

Prihvaćen na konkretan kriterijum uklanja aktivaciju tog razloga. Odbijen prigovor na konkretan kriterijum čini taj razlog konačno aktivnim. Ako nakon odluke nijedan razlog nije konačno aktivan, prijava ostaje `submitted`. Ako makar jedan razlog ostane konačno aktivan, ili ako rok istekne bez prigovora, prijava prelazi u `rejected` uz sve konačne aktivne razloge. Tokom otvorenog prava na prigovor prijava **nikada** ne napušta `submitted`. Ne uvodi se prelaz `rejected` → `submitted`.

## 4.5. Individualno ocjenjivanje i ciklus

Individualna ocjena jednog člana za jednu prijavu:

* **nacrt**;
* **zaključana**.

Ciklus ocjenjivanja konkretnog Poziva:

* **nije završen**;
* **završen** kada sva tri člana zaključaju sve potrebne individualne ocjene prijava uključenih u ciklus (`BM-ML-003`; `BM-ML-039`; `BM-ML-040`).

Tuđe ocjene postaju dostupne Komisiji tek kada je ciklus završen.

## 4.6. Rangiranje i raspodjela

Rang-lista je jedan poslovni objekat sa dvije faze (`BM-ML-045`):

* **preliminarna rang-lista**;
* **konačna rang-lista**.

Evidentirani iznosi raspodjele (`BM-ML-048`) **ne** zamjenjuju status prijave. Status `approved` ili `rejected` određuje se tek pri potvrdi konačne rang-liste i evidentiranju raspodjele (`BM-ML-044`).

## 4.7. Poziv — funkcionalne činjenice, ne enum katalog

Ovaj dokument **ne** uvodi kompletan statusni katalog Poziva jer BM to nije potvrdio kao tehnički enum.

Smiju se evidentirati funkcionalne činjenice:

* Poziv je **sačuvan**;
* Poziv je **objavljen**;
* rok **traje**;
* rok je **istekao**, kao vremenski izvedena činjenica (`BM-ML-033`);
* Poziv je **arhiviran** nakon završetka postupka.

Ove činjenice **nisu** proglašene konačnim tehničkim enum vrijednostima.

## 4.8. Godišnja instanca

Godišnja instanca **ne** dobija izmišljeni workflow. Koristi se kao konfiguracioni okvir (`KN-BM-002` Poglavlje 15; `BM-KN-002`). Detalj: Poglavlje 5.

## 4.9. Arhiviranje — odluka F-05

Arhiviranje **nije** status prijave. Predstavlja zaključavanje Poziva/postupka za dalje izmjene. **Nije** brisanje (`BM-ML-058`). Arhiviranje **ne** mijenja status prijave. Status prijave `archived` **ne** postoji.

Predsjednik Komisije **ručno** pokreće zaključivanje i arhiviranje Poziva.

Administrator Konkursa **ne** arhivira Poziv.

Platforma **ne** arhivira Poziv automatski.

Predsjednik može pokrenuti arhiviranje **tek kada su ispunjeni svi preduslovi iz §23.2**. §23.2 je jedinstvena puna funkcionalna lista preduslova arhiviranja. Ovo poglavlje **ne** vodi zasebnu užu listu preduslova.

Ako nedostaje makar jedan preduslov iz §23.2, Platforma **blokira** arhiviranje i prikazuje razlog ili spisak razloga.

Arhiviranje:

* **ne** zavisi od ugovora;
* **ne** zavisi od isplate;
* **ne** zavisi od realizacije;
* **ne** zavisi od M4/M4a;
* **ne** zavisi od de minimis dokumentacije;
* **ne** zavisi od drugih procesa van V1;
* **ne** briše podatke;
* **ne** pokreće automatski drugi Poziv (`BM-ML-051`).

Detaljni ekran, potvrda i prihvatni kriterijumi arhiviranja razrađuju se u Poglavlju 23. Ovdje se F-05 evidentira kao potvrđena funkcionalna odluka i granica razrade.

## 4.10. Tabela prelaza

Ova tabela je mapa stanja. Detalji tokova razrađuju se u navedenim poglavljima. Godišnja instanca, konfiguracija, objavljivanje i rok razrađuju se u Poglavljima 5 i 6. Prijava, obrasci, dokumentacija i podnošenje razrađuju se u Poglavljima 7–10. Privatnost, istek roka, prva sjednica i prigovori razrađuju se u Poglavljima 11–14. Usmeno obrazloženje, individualno ocjenjivanje i dodatni bodovi razrađuju se u Poglavljima 15–17. Eliminatorni kriterijumi, obračun i prag, rangiranje i raspodjela razrađuju se u Poglavljima 18–21. Drugi Poziv i zaključivanje odnosno arhiviranje razrađuju se u Poglavljima 22 i 23. Funkcionalne zabrane, granica V1, objedinjeni prihvatni kriterijumi, matrica sljedivosti i odložene teme razrađuju se u Poglavljima 24–28.

Poslovni rokovi sjednica (`BM-ML-034`) evidentiraju se kao sljedeće činjenice. Platforma **ne** zakazuje sjednice automatski i **ne** vodi sjednicu kao poseban poslovni objekat.

* prva sjednica: najkasnije sedam dana od isteka roka za prijave;
* druga sjednica i usmena obrazloženja: najkasnije sedam dana od prve sjednice;
* treća sjednica: najkasnije sedam dana od održavanja druge sjednice i usmenih intervjua;
* svi blagovremeni prigovori moraju biti riješeni prije druge sjednice;
* druga sjednica se **ne** održava dok postoji blagovremen neriješen prigovor;
* Platforma **ne** produžava automatski rok druge sjednice;
* ovaj dokument **ne** uvodi pravilo za slučaj u kojem se propisani rokovi objektivno ne mogu istovremeno ispuniti.

Rok za prijave ističe **dvadesetog narednog kalendarskog dana u 23:59:59** po lokalnom vremenu Kotora. Dan objave se **ne** računa kao prvi dan. Rok **nije** 480 sati. Neradni dan **ne** pomjera rok (`BM-ML-033`).

| Objekat | Početno stanje/rezultat | Akcija | Uslov | Novo stanje/rezultat | Uloga | BM izvor |
|---------|-------------------------|--------|-------|----------------------|-------|----------|
| Prijava | — | kreiranje | prijavljen podnosilac; Poziv objavljen; rok traje | `draft` / U pripremi | podnosilac | `BM-ML-019`; `BM-ML-021` |
| Prijava | `draft` | uređivanje ili brisanje | rok traje | ostaje `draft` ili je obrisana | podnosilac | `BM-ML-021` |
| Prijava | `draft` | konačno podnošenje | rok traje; obavezna polja ispunjena | `submitted` / Podnesena | podnosilac | `BM-ML-022`; `BM-ML-023` |
| Prijava | `draft` | istek roka | nije podnesena | ostaje `draft`; samo pregled; nije `rejected` | sistem | `BM-ML-024` |
| Prijava | `submitted` | izmjena, brisanje, povlačenje ili ponovno podnošenje | — | zabranjeno; status ne otključava | svi | `BM-ML-023` |
| Poziv | sačuvan | objava | obavezna konfiguracija; zavodni broj; Komisija nije uslov | objavljen; rok kreće | Administrator | `BM-ML-033`; F-02 |
| Poziv | objavljen | istek vremena | dvadeseti naredni kalendarski dan u 23:59:59 | rok istekao; novo podnošenje zabranjeno | sistem | `BM-ML-033` |
| Komisija | nekompletna | objava Poziva | F-02 | objava dozvoljena; upozorenje ostaje | Administrator / sistem | F-02 |
| Komisija | formalno nekompletna | početak administrativne provjere | nije popunjeno svih tri mjesta | blokirano | sistem | F-02; `BM-ML-001` |
| Sjednica Komisije | Komisija formalno kompletna | početak administrativne provjere | prisutna manje od dva člana | blokirano odnosno sjednica se odlaže | sistem | `BM-ML-002` |
| Sjednica Komisije | Komisija formalno kompletna | početak administrativne provjere | prisutna najmanje dva člana | administrativna provjera može početi | predsjednik / prisutni članovi | `BM-ML-002`; F-02 |
| Sjednica Komisije | rok za prijave istekao | prva sjednica | najkasnije sedam dana od isteka roka; Platforma ne zakazuje | poslovni rok; Platforma ne vodi sjednicu kao objekat | Komisija | `BM-ML-034` |
| Sjednica Komisije | prva sjednica održana | druga sjednica i usmena obrazloženja | najkasnije sedam dana od prve sjednice; svi blagovremeni prigovori riješeni | nije dozvoljena dok postoji blagovremen neriješen prigovor; rok se ne produžava automatski | Komisija | `BM-ML-034` |
| Sjednica Komisije | druga sjednica i usmena održani | treća sjednica | najkasnije sedam dana od druge sjednice i usmenih intervjua; Platforma ne zakazuje | poslovni rok evidentiran | Komisija | `BM-ML-034` |
| Admin. rezultat / M3 | (nema) | evidentiranje u M3 | `submitted`; rok istekao; formalno kompletna Komisija; kvorum najmanje dva prisutna člana | sva tri kriterijuma evidentirana odvojeno; status ostaje `submitted`; aktiviranje bilo kojeg kriterijuma ne postavlja odmah `rejected` | predsjednik u ime Komisije | `BM-ML-002`; `BM-ML-035` |
| Prigovor | — | podnošenje | najmanje jedan aktiviran kriterijum; rok 3 dana; digitalni servis; obrazloženje po osporenom kriterijumu | Podnesen; prijava ostaje `submitted`; neaktivirani kriterijumi se ne mogu osporavati | podnosilac | `BM-ML-036` |
| Prigovor | Podnesen | odluka Komisije | sva tri člana; odluka po svakom osporenom kriterijumu | ako nijedan razlog nije konačno aktivan → `submitted`; ako makar jedan ostane konačno aktivan → `rejected` uz sve konačne aktivne razloge | Komisija | `BM-ML-003`; `BM-ML-036`; `BM-ML-037` |
| Prigovor | Prihvaćen ili Odbijen | ponovno otvaranje | — | zabranjeno; nema `rejected` → `submitted`; isti ciklus se ne ponavlja | svi | `BM-ML-037` |
| Admin. rezultat / M3 | najmanje jedan aktiviran kriterijum | istek 3 dana bez prigovora | obavještenje poslato | svi prethodno aktivirani kriterijumi konačni; prijava `rejected` uz sve aktivne razloge | sistem | `BM-ML-036` |
| Individualna ocjena | — | unos nacrta | prijava ide u ocjenjivanje; važeći član | nacrt | član ili zamjenski član | `BM-ML-039` |
| Individualna ocjena | nacrt | Završi ocjenjivanje | unesena svih 10 kriterijuma; evidentirano završeno usmeno obrazloženje te prijave | zaključana | isto lice | `BM-ML-039`; Poglavlje 16 |
| Prijava | `submitted` | završene tri kompletne ocjene i obračun | prijava u ocjenjivanju | `evaluated` | sistem | `BM-ML-039`; `BM-ML-044` |
| Ciklus ocjenjivanja | nije završen | treći član zaključi sve potrebne ocjene | tri zaključane ocjene po prijavi u ciklusu | ciklus završen; otvara se međusobni uvid | sistem | `BM-ML-003`; `BM-ML-040` |
| Rang-lista | — | nastanak preliminarne | ciklus završen | preliminarna | sistem / Komisija | `BM-ML-045` |
| Rang-lista | preliminarna | završetak treće sjednice / konačna faza | BM tok rangiranja | konačna | Komisija | `BM-ML-045` |
| Prijava | `evaluated` | potvrda podrške i iznosa | konačna rang-lista; iznos evidentiran | `approved` | predsjednik | `BM-ML-044`; `BM-ML-048` |
| Prijava | `evaluated` | potvrda nepodrške ispod praga | puna ocjena < 30 | `rejected`; bodovi i rang sačuvani | predsjednik | `BM-ML-044` |
| Prijava | `evaluated` | prag ispunjen, nema dovoljno sredstava | konačna raspodjela | `rejected` uz razlog nedovoljnih sredstava; bodovi i rang sačuvani | predsjednik | `BM-ML-044`; `BM-ML-048` |
| Raspodjela | (nema evidentiranog iznosa) | unos iznosa | konačna faza | iznos evidentiran; prijava ostaje zaključana | predsjednik | `BM-ML-048` |
| Poziv | konačni rezultat; iznosi evidentirani | ručno zaključi i arhiviraj | svi preduslovi iz §23.2 ispunjeni | Poziv arhiviran; status prijave nepromijenjen; nije obrisan; drugi Poziv se ne kreira automatski | predsjednik / sistem blokira ako uslovi nisu ispunjeni | F-05; `BM-ML-058`; `BM-ML-051` |

## 4.11. Prihvatni kriterijumi — stanja i F-05

### 4.11.1 — Statusi prijave

**Ako:** prijava postoji na konkretnom Pozivu.

**Kada:** Platforma određuje njen status.

**Onda:** status je jedna od pet STORED vrijednosti: `draft`, `submitted`, `evaluated`, `approved` ili `rejected`. UI koristi oznake U pripremi, Podnesena, Ocijenjena, Odobrena i Odbijena. Platforma **ne** koristi `Potpuna`, `Nepotpuna`, `Eliminisana`, `Povučena` ni `Arhivirana` kao status prijave. Nije dozvoljen povratak u `draft` nakon podnošenja.

Izvor: `BM-ML-020`; `BM-ML-023`; `BM-ML-043`; `KN-PATCH-FS-007`.

### 4.11.2 — Odvojeni rezultati tri eliminatorna kriterijuma

**Ako:** za prijavu u statusu `submitted` predsjednik evidentira M3.

**Kada:** evidentiranje uspije.

**Onda:** Platforma prikazuje i čuva tri odvojene stavke, a ne samo zbirno `Potpuna` / `Nepotpuna`. Status ostaje `submitted`. Aktiviranje bilo kojeg kriterijuma **ne** postavlja odmah `rejected` i ne mijenja zaključani sadržaj.

Izvor: `BM-ML-035`.

### 4.11.3 — Uslovi za arhiviranje

**Ako:** makar jedan preduslov iz §23.2 nije ispunjen.

**Kada:** predsjednik pokuša zaključiti i arhivirati Poziv.

**Onda:** Platforma blokira akciju i prikazuje razlog ili spisak razloga.

Izvor: odluka F-05; §23.2; `BM-ML-037`; `BM-ML-039`; `BM-ML-045`; `BM-ML-048`.

### 4.11.4 — Blokada arhiviranja

**Ako:** predsjednik pokuša arhivirati Poziv djelimično ili zaobići pojedinačne preduslove iz §23.2.

**Kada:** Platforma ocjenjuje da li se arhiviranje može izvršiti bez svih preduslova.

**Onda:** Platforma **ne** dozvoljava djelimično arhiviranje niti zaobilaženje pojedinačnih preduslova iz §23.2. Arhiviranje se ne izvršava djelimično.

Izvor: odluka F-05; §23.2.

### 4.11.5 — Arhiviranje nije brisanje i ne pokreće drugi Poziv

**Ako:** Poziv je uspješno arhiviran.

**Kada:** korisnik ili sistem naknadno čita podatke tog postupka.

**Onda:** prijava, obrasci, prilozi, rezultati, ocjene, rang-lista, iznosi raspodjele i revizijski trag ostaju sačuvani. Arhiviranje **ne** zavisi od ugovora, isplate, realizacije, M4/M4a ni de minimis dokumentacije. Platforma **ne** kreira drugi Poziv automatski.

Izvor: odluka F-05; `BM-ML-058`; `BM-ML-051`; `BM-ML-056`; `BM-ML-057`.

### 4.11.6 — Treća sjednica najkasnije sedam dana

**Ako:** su održani druga sjednica i usmena obrazloženja.

**Kada:** Komisija zakazuje treću sjednicu.

**Onda:** poslovni rok je najkasnije sedam dana od održavanja druge sjednice i usmenih intervjua. Platforma **ne** zakazuje treću sjednicu automatski i **ne** vodi sjednicu kao poseban poslovni objekat.

Izvor: `BM-ML-034`.

### 4.11.7 — Nema automatskog produženja roka druge sjednice

**Ako:** postoji blagovremen prigovor koji mora biti riješen prije druge sjednice.

**Kada:** se razmatra rok druge sjednice.

**Onda:** Platforma **ne** produžava automatski rok druge sjednice. Druga sjednica se **ne** održava dok postoji blagovremen neriješen prigovor. Ovaj dokument **ne** uvodi pravilo za slučaj u kojem se propisani rokovi objektivno ne mogu istovremeno ispuniti.

Izvor: `BM-ML-034`.

---

# 5. Godišnja instanca i konfiguracija Poziva

Status poglavlja: USVOJENO

Ovo poglavlje određuje godišnju instancu, pojedinačni Poziv, ručnu konfiguraciju, zavodni broj, prvi Poziv u drugom kvartalu i primjenu odluke F-02 pri čuvanju i objavljivanju.

Ne određuje tehnički model podataka, API, tabele, kolone ni tehničke enum vrijednosti. Mehanika objavljivanja i računanje roka pripadaju Poglavlju 6. Detaljni tok drugog Poziva pripada Poglavlju 22. Detalj prve sjednice, kvoruma i administrativne provjere ostaje u Poglavljima 3, 4 i 13.

## 5.1. Razdvajanje godišnje instance, Poziva i redoslijeda Poziva

Platforma razdvaja tri poslovna objekta:

**A. Godišnja instanca** — okvir za jednu kalendarsku godinu konkursa za podršku preduzetništvu mladih. Nije pojedinačni Poziv i nije pojedinačna prijava (`KN-BM-002` Poglavlje 15; `BM-KN-002`).

**B. Pojedinačni Poziv** — konkretan Javni konkurs unutar te instance, sa sopstvenim podacima i sopstvenim postupkom.

**C. Prvi i eventualni drugi Poziv** — oba, kada postoje, pripadaju istoj godišnjoj instanci (`BM-ML-050`). Drugi Poziv nije nova godišnja instanca. Detaljni tok drugog Poziva **nije** sadržaj ovog poglavlja; ovdje se samo evidentira da u istoj instanci može postojati drugi Poziv prema `BM-ML-049`–`BM-ML-052`.

## 5.2. Godišnja instanca

Godišnja instanca predstavlja konfiguracioni okvir za jednu kalendarsku godinu.

Funkcionalno mora omogućiti evidenciju najmanje:

* kalendarske godine;
* profila konkursa za podršku preduzetništvu mladih;
* važeće verzije poslovnog profila;
* ukupnog odobrenog godišnjeg budžeta;
* povezanih Poziva;
* iskorišćenih i preostalih godišnjih sredstava.

Način tehničkog čuvanja, obračuna i identifikatora **nije** određen ovim poglavljem.

Promjena konfiguracionih vrijednosti godišnje instance **ne** mijenja verziju poslovnog profila (`BM-KN-002`). Godina ili budžet nijesu nova verzija `KN-BM-002`. Poziv **ne** smije samostalno mijenjati pravila profila.

Platforma **ne** izmišlja niti samostalno određuje iznos godišnjeg budžeta. Iznos je konfiguraciona vrijednost zasnovana na odobrenom budžetu.

Ukupni budžet svih Poziva iste godišnje instance **ne** smije preći odobreni godišnji budžet.

Neraspoređena sredstva prvog Poziva predstavljaju poslovni osnov za drugi Poziv prema `BM-ML-049`. Platforma **ne** kreira drugi Poziv automatski (`BM-ML-051`).

## 5.3. Pojedinačni Poziv

Prvi i eventualni drugi Poziv pripadaju istoj godišnjoj instanci, ali svaki Poziv ima sopstvene:

* podatke;
* službeni zavodni broj;
* datum objavljivanja;
* rok;
* raspoloživi budžet;
* prijave;
* administrativnu provjeru;
* prigovore;
* ocjenjivanje;
* rang-listu;
* raspodjelu;
* arhivu.

Podaci i rezultati prvog Poziva ostaju sačuvani radi istorije i sljedivosti. **Ne** prenose se podaci ni rezultati prvog Poziva u drugi Poziv (`BM-ML-050`; `BM-ML-052`).

## 5.4. Konfiguracija Poziva

Administrator Konkursa ručno kreira i uređuje Poziv prije objavljivanja.

Platforma razdvaja najmanje sljedeće radnje:

* kreiranje novog Poziva;
* čuvanje nacrta konfiguracije;
* uređivanje prije objave;
* provjeru spremnosti;
* posebno pokretanje objavljivanja.

**Čuvanje nije objavljivanje** (`BM-ML-033`; `BM-ML-053`).

Poziv se **ne** smije automatski objaviti zbog:

* čuvanja podataka;
* početka drugog kvartala;
* unosa zavodnog broja;
* kompletiranja Komisije;
* postojanja raspoloživog budžeta.

Platforma **ne** donosi odluku o raspisivanju Poziva i **ne** bira samostalno datum objavljivanja (`BM-ML-053`).

## 5.5. Zavodni broj

Službeni zavodni broj:

* dolazi iz pisarnice;
* Administrator ga ručno unosi;
* Platforma ga **ne** generiše;
* pripada konkretnom Pozivu;
* prvi i drugi Poziv imaju sopstvene zavodne brojeve;
* mora biti unesen prije objavljivanja Poziva.

Ovo poglavlje **ne** određuje format zavodnog broja, masku, algoritam, automatsko povezivanje sa pisarnicom ni provjeru prema eksternom registru.

Zavodni broj je **obavezno polje prije objave**. Ovaj dokument **ne** uvodi automatsku validaciju jedinstvenosti zavodnog broja, jer poslovni model takvo pravilo nije potvrdio.

## 5.6. Prvi Poziv

Prvi Poziv:

* pripada godišnjoj instanci tekuće godine;
* Administrator ga ručno priprema;
* raspisuje se u drugom kvartalu;
* drugi kvartal je april, maj i jun;
* Platforma **ne** bira datum objavljivanja;
* Platforma ga **ne** objavljuje automatski 1. aprila niti na početku drugog kvartala;
* rok **ne** mora u cjelosti isteći unutar drugog kvartala;
* objava mora biti pokrenuta odgovornom korisničkom akcijom (`BM-ML-053`).

U istoj godišnjoj instanci može postojati drugi Poziv prema `BM-ML-049`–`BM-ML-052`. Detaljni tok drugog Poziva razrađuje se u Poglavlju 22.

## 5.7. Komisija — odluka F-02

Primjenjuje se usvojena odluka F-02, dosljedno Poglavljima 3 i 4:

* Poziv se može sačuvati i objaviti bez povezane kompletne Komisije;
* nedodijeljena ili nekompletna Komisija **ne** blokira objavljivanje;
* sistem pri provjeri spremnosti i prije objavljivanja prikazuje jasno **neblokirajuće** upozorenje ako sva tri mjesta nijesu popunjena;
* sva tri mjesta moraju biti formalno popunjena najkasnije do isteka roka za prijave;
* predsjednik mora biti jedan od tri člana (`BM-ML-001`);
* administrativna provjera **ne** može početi ako sva tri mjesta nijesu formalno popunjena;
* kada je Komisija formalno kompletna, za prvu sjednicu i administrativnu provjeru potreban je kvorum od najmanje dva prisutna člana (`BM-ML-002`);
* članovi Komisije **nemaju** sadržajni pristup prijavama dok rok traje (`BM-ML-005`).

Detalji kvoruma ostaju povezani sa Poglavljima 3, 4 i 13. Način tehničkog evidentiranja prisustva **nije** određen ovim poglavljem.

Ovo poglavlje **ne** uvodi:

* automatsko imenovanje;
* podrazumijevane članove;
* četvrtog člana;
* automatsko odlaganje roka;
* automatsko otkazivanje Poziva.

Upozorenje o nekompletnoj Komisiji šalje se pri **provjeri spremnosti** i pri **pokušaju objavljivanja**. Dodatni trenuci slanja upozorenja ovdje se ne izmišljaju.

## 5.8. Funkcionalni prelazi — instanca, konfiguracija i spremnost

Ove činjenice **nijesu** konačne tehničke enum vrijednosti.

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Godišnja instanca | — | kreiranje | Administrator; kalendarska godina; profil mladih | instanca evidentirana | Administrator | `BM-KN-002`; `KN-BM-002` Pogl. 15 |
| Poziv | — | kreiranje | pripada postojećoj godišnjoj instanci tekuće godine | novi Poziv sačuvan kao nacrt konfiguracije | Administrator | `BM-ML-053`; `BM-ML-050` |
| Godišnja instanca | postoji | dodjela raspoloživog budžeta Pozivu | zbir budžeta svih Poziva instance ne prelazi odobreni godišnji budžet | budžet Poziva evidentiran | Administrator | `KN-BM-002` Pogl. 15.1 |
| Godišnja instanca | postoji | pokušaj da zbir budžeta Poziva pređe godišnji iznos | zbir bi prešao odobreni godišnji budžet | blokirano | sistem | `KN-BM-002` Pogl. 15.1 |
| Poziv | sačuvan | čuvanje nacrta konfiguracije | Administrator pokreće čuvanje | ostaje sačuvan; **nije** objavljen | Administrator | `BM-ML-033`; `BM-ML-053` |
| Poziv | sačuvan | uređivanje prije objave | Poziv još nije objavljen | nacrt ažuriran; **nije** objavljen | Administrator | `BM-ML-033`; `BM-ML-053` |
| Poziv | sačuvan | provjera spremnosti | nedostaje zavodni broj ili druga potvrđena obavezna konfiguracija | Poziv nije spreman za objavu | sistem | `BM-ML-033`; `BM-ML-053` |
| Poziv | sačuvan | provjera spremnosti | sva tri mjesta Komisije nijesu formalno popunjena | neblokirajuće upozorenje; objava ostaje moguća | sistem | F-02 |

## 5.9. Prihvatni kriterijumi — instanca, konfiguracija, zavodni broj i F-02

### 5.9.1 — Kreiranje godišnje instance

**Ako:** korisnik ima ulogu Administratora Konkursa.

**Kada:** kreira godišnju instancu konkursa za podršku preduzetništvu mladih.

**Onda:** Platforma omogućava evidenciju kalendarske godine, profila mladih, važeće verzije poslovnog profila, ukupnog odobrenog godišnjeg budžeta, povezanih Poziva i iskorišćenih odnosno preostalih godišnjih sredstava. Promjena tih vrijednosti **ne** mijenja verziju poslovnog profila.

Izvor: `KN-BM-002` Poglavlje 15; `BM-KN-002`.

### 5.9.2 — Povezivanje prvog Poziva sa godišnjom instancom

**Ako:** postoji godišnja instanca tekuće godine za profil mladih.

**Kada:** Administrator kreira prvi Poziv.

**Onda:** Platforma povezuje taj Poziv sa tom godišnjom instancom. Prvi Poziv **nije** nova godišnja instanca.

Izvor: `BM-ML-053`; `BM-ML-050`.

### 5.9.3 — Zabrana prekoračenja godišnjeg budžeta

**Ako:** zbir raspoloživih budžeta Poziva iste godišnje instance bi prešao odobreni godišnji budžet.

**Kada:** Administrator pokuša sačuvati takav budžet Poziva.

**Onda:** Platforma blokira čuvanje te vrijednosti. Ukupni budžet svih Poziva instance **ne** smije preći odobreni godišnji budžet.

Izvor: `KN-BM-002` Poglavlje 15.1.

### 5.9.4 — Ručni unos zavodnog broja

**Ako:** Administrator priprema Poziv za objavljivanje.

**Kada:** unosi službeni zavodni broj.

**Onda:** Platforma prima broj kao ručno uneseno obavezno polje koje dolazi iz pisarnice i pripada konkretnom Pozivu.

Izvor: `BM-ML-053`.

### 5.9.5 — Zabrana automatskog generisanja zavodnog broja

**Ako:** Poziv još nema unesen zavodni broj.

**Kada:** Administrator čuva ili priprema objavu.

**Onda:** Platforma **ne** generiše zavodni broj. Broj ostaje prazan dok ga Administrator ručno ne unese.

Izvor: `BM-ML-053`.

### 5.9.6 — Čuvanje bez objavljivanja

**Ako:** Administrator čuva nacrt konfiguracije Poziva.

**Kada:** čuvanje uspije.

**Onda:** Poziv ostaje sačuvan i **nije** objavljen. Rok **ne** počinje. Prijave se **ne** mogu kreirati za taj Poziv.

Izvor: `BM-ML-033`; `BM-ML-053`.

### 5.9.7 — Blokada administrativne provjere ako Komisija nije formalno kompletna

**Ako:** nijesu popunjena sva tri mjesta Komisije, uključujući predsjednika kao jednog od ta tri.

**Kada:** korisnik pokuša da započne administrativnu provjeru.

**Onda:** Platforma blokira početak provjere. Kada su sva tri mjesta formalno popunjena, za prvu sjednicu i administrativnu provjeru i dalje važi kvorum od najmanje dva prisutna člana, prema §3.12.6.

Izvor: `BM-ML-001`; `BM-ML-002`; odluka F-02.

---

# 6. Objavljivanje Poziva i rok za prijave

Status poglavlja: USVOJENO

Ovo poglavlje određuje objavljivanje Poziva na digitalnom servisu i rok za podnošenje prijava. Ne određuje tehnički model, API ni tehničke enum vrijednosti. Vanjski kanali objave nijesu integracije Platforme.

## 6.1. Objavljivanje kao posebna akcija

Objavljivanje je posebna izričita akcija Administratora Konkursa.

Čuvanje nacrta **nije** objavljivanje (`BM-ML-033`; `BM-ML-053`).

Prije objavljivanja Platforma provjerava samo potvrđene obavezne konfiguracione vrijednosti.

Obavezno prije objavljivanja:

* Poziv pripada godišnjoj instanci;
* evidentirana je godina;
* evidentiran je profil mladih;
* evidentiran je raspoloživi budžet konkretnog Poziva;
* unesen je službeni zavodni broj;
* uneseni su podaci potrebni da se Poziv prikaže podnosiocima;
* Administrator izričito potvrđuje objavljivanje.

Konkretna polja potrebna da se Poziv prikaže podnosiocima definišu se samo na osnovu potvrđenih izvora. Ovaj dokument **ne** uvodi izmišljeni katalog polja.

Komisija **nije** blokirajući preduslov objavljivanja. Nekompletna Komisija izaziva **neblokirajuće** upozorenje prema F-02, pri provjeri spremnosti i pri pokušaju objavljivanja.

## 6.2. Posljedice objavljivanja

Nakon objavljivanja:

* Poziv je javno dostupan na digitalnom servisu;
* aktivira se mogućnost kreiranja i podnošenja prijava;
* počinje računanje roka;
* datum i vrijeme stvarne objave moraju biti evidentirani.

Konfiguracija koja utiče na objavljeni rok i poslovni identitet Poziva **ne** smije se tiho mijenjati. Kasnija izmjena **ne** smije retroaktivno promijeniti rok ili izvorno objavljene podatke bez posebno odobrenog postupka. Ovaj dokument **ne** tvrdi da je cijela konfiguracija apsolutno neizmjenjiva, jer BM to nije potvrdio.

Članovi Komisije **nemaju** sadržajni pristup prijavama dok rok traje (`BM-ML-005`). Administrator **nema** sadržajni pristup prijavama (`BM-ML-004`).

## 6.3. Rok od 20 kalendarskih dana

Primjenjuje se `BM-ML-033`:

* rok traje **20 kalendarskih dana**;
* rok **nije** 480 sati od časa objavljivanja;
* dan objavljivanja se **ne** računa kao prvi od 20 dana;
* rok ističe u **23:59:59** dvadesetog narednog kalendarskog dana;
* koristi se lokalno vrijeme Kotora;
* neradni dan **ne** pomjera rok.

Primjer:

* objava 1. septembra u 09:00;
* prvi dan je 2. septembar;
* dvadeseti dan je 21. septembar;
* rok ističe 21. septembra u 23:59:59.

Vrijeme objave **ne** skraćuje posljednji dan roka. Ako je Poziv objavljen 1. septembra u 09:00, posljednji dan i dalje traje do 21. septembra u 23:59:59, a ne do 21. septembra u 09:00.

Ako posljednji dan pada u subotu, nedjelju, praznik ili drugi neradni dan, rok se **ne** pomjera i završava se u 23:59:59 tog dana.

Ovo poglavlje **ne** uvodi vremensku zonu u tehničkom formatu. Poslovno važi lokalno vrijeme Kotora.

## 6.4. Funkcionalne posljedice roka

* Prije objavljivanja prijava se **ne** može kreirati za taj Poziv.
* Nakon objavljivanja i dok rok traje, prijava se može kreirati i podnijeti.
* Poslije 23:59:59 posljednjeg dana novo podnošenje **nije** moguće.
* Nacrt koji nije podnesen ostaje `U pripremi` i samo za pregled, prema `BM-ML-024`.
* Sistem **ne** pomjera rok zbog vikenda, praznika ili drugog neradnog dana.
* Vrijeme objave **ne** skraćuje posljednji dan roka.
* Po isteku roka Platforma automatski onemogućava novo konačno podnošenje, bez mijenjanja sačuvanih prijava `U pripremi` (`BM-ML-033`; `BM-ML-024`).

## 6.5. Drugi kanali objave

Poslovni izvor (`BM-ML-033`; Odluka, član 6) navodi objavljivanje preko:

* dnevnog lista;
* internet stranice Opštine Kotor;
* digitalnog servisa;
* lokalnog javnog emitera;
* oglasne table Opštine.

Ti kanali ostaju obaveza Konkursa. `KN-FS-002` funkcionalno uređuje **samo** objavljivanje na digitalnom servisu.

Ovo poglavlje **ne** uvodi:

* automatsko objavljivanje na drugim kanalima;
* integracije sa dnevnim listom;
* integracije sa emiterom;
* integracije sa oglasnom tablom;
* automatsku potvrdu da je objava izvršena na drugim kanalima.

Objava na digitalnom servisu **sama po sebi ne potvrđuje** da je objava kroz ostale propisane kanale izvršena.

## 6.6. Funkcionalne činjenice Poziva

Ovaj dokument **ne** uvodi konačne tehničke enum vrijednosti.

Smiju se koristiti funkcionalne činjenice:

* Poziv je **sačuvan**;
* Poziv je **objavljen**;
* rok **traje**;
* rok je **istekao**.

## 6.7. Funkcionalni prelazi — objavljivanje i rok

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Poziv | — | kreiranje Poziva | Administrator; godišnja instanca postoji | sačuvan nacrt konfiguracije | Administrator | `BM-ML-053` |
| Poziv | sačuvan | čuvanje nacrta konfiguracije | čuvanje pokrenuto | ostaje sačuvan; nije objavljen | Administrator | `BM-ML-033`; `BM-ML-053` |
| Poziv | sačuvan | provjera spremnosti | nedostaju potvrđene obavezne vrijednosti | nije spreman za objavu | sistem | `BM-ML-033`; `BM-ML-053` |
| Poziv | sačuvan | pokušaj objave bez zavodnog broja | zavodni broj nije unesen | blokirano | sistem | `BM-ML-053`; `BM-ML-033` |
| Poziv | sačuvan | pokušaj objave sa nekompletnom Komisijom | sva tri mjesta nijesu formalno popunjena; ostala obavezna konfiguracija jeste | objava dozvoljena; neblokirajuće upozorenje | Administrator / sistem | F-02 |
| Poziv | sačuvan | uspješna objava | obavezna konfiguracija; zavodni broj; izričita akcija Administratora; Komisija nije uslov | objavljen; datum i vrijeme objave evidentirani | Administrator | `BM-ML-033`; `BM-ML-053`; F-02 |
| Poziv | objavljen | početak roka | objava izvršena | rok traje; prijave se mogu kreirati i podnositi; dan objave se ne računa | sistem | `BM-ML-033` |
| Poziv | objavljen; rok traje | istek roka | 23:59:59 dvadesetog narednog kalendarskog dana; lokalno vrijeme Kotora | rok istekao; novo podnošenje zabranjeno | sistem | `BM-ML-033` |
| Prijava | `draft` / U pripremi ili ne postoji | pokušaj podnošenja nakon roka | rok je istekao | blokirano; nacrt ostaje `draft` samo za pregled | podnosilac / sistem | `BM-ML-033`; `BM-ML-024` |

## 6.8. Prihvatni kriterijumi — objavljivanje i rok

### 6.8.1 — Objavljivanje posebnom akcijom

**Ako:** Poziv je sačuvan i obavezna konfiguracija je potpuna.

**Kada:** Administrator izričito pokrene objavljivanje.

**Onda:** Platforma objavljuje Poziv na digitalnom servisu, evidentira datum i vrijeme stvarne objave i pokreće rok. Čuvanje samo po sebi **ne** izaziva ovaj ishod.

Izvor: `BM-ML-033`; `BM-ML-053`.

### 6.8.2 — Blokada objave bez zavodnog broja

**Ako:** službeni zavodni broj nije unesen.

**Kada:** Administrator pokuša objaviti Poziv.

**Onda:** Platforma blokira objavljivanje.

Izvor: `BM-ML-053`; `BM-ML-033`.

### 6.8.3 — Dozvoljena objava bez kompletne Komisije

**Ako:** sva tri mjesta Komisije nijesu formalno popunjena, a ostale potvrđene obavezne vrijednosti jesu unesene.

**Kada:** Administrator izričito pokrene objavljivanje.

**Onda:** Platforma **dozvoljava** objavu. Nekompletna Komisija **nije** razlog blokade objave.

Izvor: odluka F-02; `BM-ML-033`.

### 6.8.4 — Upozorenje zbog nekompletne Komisije

**Ako:** sva tri mjesta Komisije nijesu formalno popunjena.

**Kada:** Administrator pokrene provjeru spremnosti ili pokuša objavljivanje.

**Onda:** Platforma prikazuje jasno neblokirajuće upozorenje da Komisija nije formalno kompletirana i da sva tri mjesta moraju biti popunjena najkasnije do isteka roka.

Izvor: odluka F-02.

### 6.8.5 — Ručno objavljivanje prvog Poziva u drugom kvartalu

**Ako:** prvi Poziv pripada godišnjoj instanci tekuće godine.

**Kada:** Administrator ručno pokrene objavljivanje u drugom kvartalu (april, maj ili jun).

**Onda:** Platforma objavljuje Poziv samo zbog te izričite akcije. Platforma **ne** bira datum objavljivanja umjesto Administratora. Rok **ne** mora u cjelosti isteći unutar drugog kvartala.

Izvor: `BM-ML-053`.

### 6.8.6 — Zabrana automatskog objavljivanja

**Ako:** nastupi čuvanje podataka, početak drugog kvartala, unos zavodnog broja, kompletiranje Komisije ili postojanje raspoloživog budžeta.

**Kada:** nijedna izričita akcija objavljivanja nije pokrenuta.

**Onda:** Platforma **ne** objavljuje Poziv. Platforma **ne** objavljuje prvi Poziv automatski 1. aprila.

Izvor: `BM-ML-033`; `BM-ML-053`.

### 6.8.7 — Računanje roka od 20 dana

**Ako:** Poziv je objavljen.

**Kada:** Platforma računa rok za prijave.

**Onda:** rok traje 20 kalendarskih dana. Dan objavljivanja se **ne** računa. Rok **nije** 480 sati od časa objave.

Izvor: `BM-ML-033`.

### 6.8.8 — Istek u 23:59:59

**Ako:** Poziv je objavljen 1. septembra u 09:00, prema lokalnom vremenu Kotora.

**Kada:** nastupi dvadeseti naredni kalendarski dan.

**Onda:** prvi dan je 2. septembar, dvadeseti dan je 21. septembar, i rok ističe 21. septembra u 23:59:59. Vrijeme objave **ne** skraćuje posljednji dan.

Izvor: `BM-ML-033`.

### 6.8.9 — Neradni dan bez pomjeranja

**Ako:** dvadeseti naredni kalendarski dan pada u subotu, nedjelju, praznik ili drugi neradni dan.

**Kada:** Platforma određuje trenutak isteka.

**Onda:** rok se **ne** pomjera i ističe u 23:59:59 tog dana.

Izvor: `BM-ML-033`.

### 6.8.10 — Zabrana podnošenja poslije roka

**Ako:** rok je istekao, odnosno prošlo je 23:59:59 dvadesetog narednog kalendarskog dana.

**Kada:** podnosilac pokuša konačno podnijeti prijavu.

**Onda:** Platforma blokira novo podnošenje. Administrator **nije** potreban za prestanak podnošenja.

Izvor: `BM-ML-033`.

### 6.8.11 — Nacrt ostaje U pripremi

**Ako:** prijava je `U pripremi` i nije konačno podnesena.

**Kada:** rok istekne.

**Onda:** prijava ostaje `draft` / `U pripremi` i samo za pregled. Ne postaje `submitted`, `Nepotpuna` niti prelazi automatski u `rejected`. Nije dostupna Komisiji i ne prenosi se automatski na drugi Poziv.

Izvor: `BM-ML-024`; `BM-ML-033`.

### 6.8.12 — Digitalni servis bez automatske objave na drugim kanalima

**Ako:** Administrator objavi Poziv na digitalnom servisu.

**Kada:** objava na Platformi uspije.

**Onda:** Poziv je javno dostupan na digitalnom servisu. Platforma **ne** objavljuje automatski u dnevnom listu, na internet stranici Opštine, preko lokalnog javnog emitera ni na oglasnoj tabli, i **ne** potvrđuje da su ti kanali izvršeni.

Izvor: `BM-ML-033`.

---

# 7. Prijava podnosioca

Status poglavlja: USVOJENO

Ovo poglavlje određuje kreiranje prijave, vlasništvo, kategoriju podnosioca, rad sa nacrtom i odnos prvog i drugog Poziva.

Ne određuje tehnički model, API, tabele, kolone ni tehničke enum vrijednosti. Obrasci pripadaju Poglavlju 8. Prateća dokumentacija pripada Poglavlju 9. Kontrola prije podnošenja i zaključavanje pripadaju Poglavlju 10. Status kreiranja je `draft` / `U pripremi`; konačno podnošenje daje `submitted` / `Podnesena` (`BM-ML-020`).

Platforma **ne** donosi automatsku pravnu odluku o podobnosti. Evidentira podatke i primjenjuje potvrđene funkcionalne kontrole.

## 7.1. Uslovi za kreiranje prijave

Prijava se može kreirati samo:

* za **objavljeni** Poziv;
* **dok rok traje**;
* od **prijavljenog podnosioca**;
* u okviru konkretne godišnje instance i konkretnog Poziva (`BM-ML-019`; `BM-ML-033`);
* kada je identitet podržan za ovaj profil i kada su namjera, pravni oblik i poslovna faza utvrđeni prema odluci F-06 i `BM-ML-009`–`BM-ML-010`.

Ako identitet nije podržan, ili ako namjera, pravni oblik ili poslovna faza nijesu utvrđeni, Platforma **ne** pretpostavlja vrijednost, **ne** svrstava identitet u youth kategoriju `ostalo`, **ne** prikazuje proizvoljan obrazac ni paket i **ne** dozvoljava kreiranje ni konačno podnošenje. Prikazuje se jasna poruka. Postupak izmjene korisničkog naloga **nije** određen ovim poglavljem.

Prijava pripada tačno jednom Pozivu. Nacrt **nije** podnesena prijava i **nije** dostupan Komisiji (`BM-ML-021`).

Čuvanje nacrta **nije** podnošenje.

## 7.2. Vlasništvo prijave

Podnosilac može pristupati **samo svojoj** prijavi.

Administrator **nema** sadržajni pristup prijavi (`BM-ML-004`). Komisija **nema** pristup dok rok traje (`BM-ML-005`).

## 7.3. Jedan biznis plan po Pozivu

Jedno fizičko lice, preduzetnik ili društvo može konkurisati sa **jednim biznis planom po jednom Pozivu** (`BM-ML-014`; `BM-KN-015`).

Platforma **ne** uvodi proizvoljno višestruko podnošenje na istom Pozivu. Ako već postoji prijava istog podnosioca na istom Pozivu u stanju `U pripremi` ili `Podnesena`, kreiranje još jedne prijave na taj Poziv **nije** dozvoljeno.

Nakon `Podnesene` prijave **nije** dozvoljeno povlačenje ni ponovno podnošenje na istom Pozivu (`BM-ML-023`). Profil mladih je stroži od opšte mogućnosti povlačenja iz `BM-KN-015`; zajednička granica jedne aktivno podnijete prijave ostaje.

## 7.4. Radnje dok je prijava U pripremi i rok traje

Dok je prijava `U pripremi` i rok traje, podnosilac može (`BM-ML-021`):

* pregledati prijavu;
* unositi i mijenjati podatke;
* popunjavati obrasce;
* dodavati, uklanjati i zamjenjivati priloge;
* obrisati nacrt;
* pokrenuti konačno podnošenje.

Namjera neregistrovanog fizičkog lica, tip prijave, poslovna faza i M1 obrazac čuvaju se uz nacrt i ostaju zaključani. Pri ponovnom otvaranju nacrta Platforma može ponovo pročitati aktuelni `is_registered` iz korisničkog identiteta. Prikaz dodatnih podataka prilagođava se aktuelnom statusu registracije. Namjera, tip prijave, poslovna faza i M1 obrazac **ne** mijenjaju se automatski. **Ne** uvodi se automatsko brisanje nacrta niti obavezno kreiranje novog nacrta. Ako živi `is_registered` i sačuvani tok naprave kombinaciju za koju nije određena posebna validacija, prikaz prati aktuelni status registracije, a zaključane činjenice ostaju nepromijenjene.

## 7.5. Kategorije podnosilaca

Funkcionalno se razdvajaju (`BM-ML-009`; `BM-ML-010`; odluka F-06):

* pravni oblik: neregistrovano fizičko lice; registrovani preduzetnik; registrovano privredno društvo (DOO, AD, OD ili KD);
* namjera neregistrovanog fizičkog lica: planirana registracija kao preduzetnik ili planirano osnivanje privrednog društva;
* poslovna faza: započinjanje poslovanja; razvoj poslovanja.

Youth kategorija `ostalo` **nije** zamjena za privredno društvo. Nevladino udruženje, nevladina fondacija, sportska organizacija i dio stranog privrednog društva **ne** klasifikuju se automatski kao privredno društvo podobno za ovaj profil.

### A. Neregistrovano fizičko lice

Prije kreiranja prijave bira namjeru:

* planira registraciju kao preduzetnik → **M1a** i paket za započinjanje preduzetnika;
* planira osnivanje privrednog društva → **M1b** i paket za društvo koje započinje poslovanje. Platforma zaključava M1b i fazu **započinjanje**. PIB, CRPS broj, registrovano sjedište, formalni osnivač i formalni izvršni direktor **ne** prikazuju se i **ne** zahtijevaju. Ostala primjenjiva M1b polja ostaju. Status kompletnosti **ne** smije biti negativan samo zbog podataka koji nastaju registracijom. Platforma **ne** predstavlja planirano društvo kao već registrovano. Provjera pred ugovor ostaje van V1 (`BM-ML-011`).

Namjera se čuva uz prijavu. Poslovna faza je **započinjanje**. Poseban peti paket se **ne** uvodi (`BM-ML-029`; `BM-ML-011`).

### B. Registrovani preduzetnik

Pravni oblik preuzima se iz korisničkog identiteta. Korisnik ga **ne** može krivotvoriti ni proizvoljno promijeniti. Obrazac je **M1a**. Podnosilac bira započinjanje ili razvoj. Komisija provjerava da li izabrana faza odgovara Odluci i priloženoj dokumentaciji.

### C. Registrovano privredno društvo

Pravni oblik preuzima se iz korisničkog identiteta. Obuhvat je DOO, AD, OD i KD. Obrazac je **M1b**. Podnosilac bira započinjanje ili razvoj. Komisija provjerava izbor. Odgovarajući blok podataka registrovanog društva (PIB, CRPS, sjedište, osnivač, izvršni direktor i nosilac) **ostaje obavezan**.

### D. Nepodržani identitet

Platforma **ne** svrstava identitet proizvoljno u `ostalo`, **ne** prikazuje proizvoljan obrazac ni paket i **blokira** kreiranje i podnošenje za ovaj profil, uz jasnu poruku.

**Registrovani pravni oblik** preuzima se sa potvrđenog korisničkog naloga. Podnosilac ga **ne** bira unutar prijave i **ne** može ga promijeniti u nacrtu.

**Poslovna faza** u V1:

* neregistrovano fizičko lice automatski pripada započinjanju;
* registrovani preduzetnik ili privredno društvo bira započinjanje ili razvoj **prije** kreiranja prijave;
* izbor se čuva uz prijavu;
* Platforma **ne** računa automatski starost biznisa;
* **ne** uvodi se CRPS integracija niti novo obavezno identity polje datuma registracije;
* **ne** izjednačava se „trenutak raspisivanja“ sa datumom objavljivanja ako izvor to izričito ne određuje;
* **ne** uvodi se obračun 365 dana, vremenska zona ni dodatna formula;
* ručni izbor korisnika **nije** konačna pravna odluka Komisije.

Poslovna faza određuje odgovarajući dokumentacioni paket. Nakon kreiranja prijave podnosilac **ne** mijenja namjeru, pravni oblik, obrazac ni fazu proizvoljno u započetoj prijavi.

Kategorija utiče na obrazac M1, dokumentacioni paket i finansijska ograničenja, ali **ne** mijenja osnovni identitet podnosioca.

### Teritorija i starost

* fizičko lice i preduzetnik moraju imati prebivalište na teritoriji opštine Kotor;
* privredno društvo mora imati sjedište na teritoriji opštine Kotor;
* podnosilac, odnosno nosilac biznisa kada se prijavljuje društvo, mora pripadati starosnoj grupi 18–30 godina, prema `BM-ML-009`.

Platforma evidentira prebivalište odnosno sjedište, adresu i grad. Može prikazati upozorenje kada podatak nedostaje ili ukazuje da nije Kotor. Platforma **ne** odbija prijavu automatski samo na osnovu adrese. Komisija prema dokumentaciji odlučuje o ispunjenosti teritorijalnog uslova.

### Privredno društvo, nosilac i ovlašćeno lice

Kod prijave **registrovanog** društva razlikuju se (`BM-ML-012`; `BM-ML-013`):

* **privredno društvo** (DOO, AD, OD ili KD) kao formalni podnosilac;
* **osnivač**, **izvršni direktor** i **nosilac biznisa**, koji se evidentiraju u M1b odnosno prijavi; nosilac mora biti osnivač ili jedan od osnivača i istovremeno izvršni direktor; preko njega se provjeravaju lični uslovi, uključujući starost;
* **ovlašćeno lice** koje u ime društva popunjava i podnosi prijavu.

Ovlašćeno lice korisničkog naloga **nije** automatski nosilac biznisa. Nosilac i ovlašćeno lice **mogu** biti ista osoba, ali se to **ne pretpostavlja**. Uloge se vode odvojeno. Platforma kontroliše popunjenost, ali **ne** donosi konačni pravni zaključak. Komisija iz dokumentacije provjerava `BM-ML-012`. **Ne** uvodi se nova identity uloga niti CRPS integracija u V1.

Kod neregistrovanog fizičkog lica koje planira društvo, M1b **ne** prikazuje i **ne** zahtijeva PIB, CRPS, registrovano sjedište, formalnog osnivača ni formalnog izvršnog direktora. Pravilo o nosiocu ostaje poslovni uslov Odluke; formalni podaci se zahtijevaju kada društvo već postoji kao registrovani podnosilac.

Ovaj dokument **ne** uvodi obavezno punomoćje ni elektronski potpis, jer BM to nije odredio.

Fizičko lice koje u trenutku prijave nema registrovanu djelatnost može učestvovati. Naknadna registracija, poreska evidencija i žiro račun do ugovora vode se prema `BM-ML-011` i **nisu** uslov početne potpunosti. Provjera prije ugovora je **van V1**.

## 7.6. Drugi Poziv

Na drugom Pozivu (`BM-ML-014`; `BM-ML-052`):

* isti podnosilac može ponovo konkurisati;
* mora kreirati **novu** prijavu;
* ništa iz prvog Poziva **ne** prenosi se automatski: ni obrasci, ni podaci, ni prilozi, ni potpunost, ni prigovori, ni ocjene, ni bodovi, ni rang, ni odluka;
* nova prijava prolazi cijeli postupak drugog Poziva.

Platforma **ne** uvodi dugme ni funkciju automatskog kopiranja prethodne prijave.

## 7.7. Pravni oblik, namjera i poslovna faza — odluka F-06

Odobreno pravilo F-06 zatvara raniju nedoumicu da korisnik nikada ne bira poslovnu fazu i da se obrazac određuje samo kao M1a ili samo kao M1b za DOO. Funkcionalni princip ženskog toka primjenjuje se ovdje, uz pravila mladih iz `KN-BM-002`.

**Prije kreiranja prijave:**

* neregistrovano fizičko lice bira namjeru: budući preduzetnik ili planirano privredno društvo;
* registrovani preduzetnik ili privredno društvo bira poslovnu fazu započinjanje ili razvoj;
* registrovani pravni oblik preuzima se iz identiteta i **ne** krivotvori se.

**Nakon kreiranja prijave:**

* namjera, tip prijave, poslovna faza i obrazac čuvaju se uz nacrt;
* podnosilac ih **ne** mijenja unutar prijave;
* **ne** uvodi se funkcija promjene kategorije sa brisanjem, skrivanjem ili prenosom prethodnih podataka;
* pri ponovnom otvaranju nacrta aktuelni `is_registered` može se ponovo pročitati; prikaz dodatnih podataka prati taj status; namjera, tip prijave, poslovna faza i M1 obrazac se **ne** mijenjaju automatski; paket se **ne** mijenja automatski.

**Obrazac:**

* neregistrovano fizičko lice koje planira registraciju kao preduzetnik i registrovani preduzetnik koriste **M1a**;
* neregistrovano fizičko lice koje planira osnivanje privrednog društva i registrovano privredno društvo (DOO, AD, OD ili KD) koriste **M1b**;
* Platforma prikazuje odgovarajući obrazac;
* podnosilac **ne** može ručno prebacivati M1a i M1b.

Za drugi Poziv namjera, oblik i faza se **ponovo** utvrđuju. Prebacivanje postojeće prijave u drugu kategoriju **nije** dozvoljeno.

Ako se osnovni potvrđeni podaci korisničkog naloga moraju ispraviti, to **nije** promjena kategorije unutar prijave i **ne** razrađuje se ovim korakom. Postupak izmjene korisničkog naloga **nije** određen.

Izvor: `BM-ML-009`; `BM-ML-010`; `BM-ML-025`; `BM-ML-029`; odluka F-06.

## 7.8. Funkcionalni prelazi — prijava i kategorija

Osnovni statusi ovog poglavlja su `draft` / `U pripremi` i `submitted` / `Podnesena`. Kasniji statusi `evaluated`, `approved` i `rejected` razrađuju se u Poglavljima 4, 13–14 i 16–20.

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Prijava | — | kreiranje | prijavljen podnosilac; Poziv objavljen; rok traje; nema postojeće prijave istog podnosioca na tom Pozivu; podržan identitet; namjera, pravni oblik i poslovna faza utvrđeni | `draft` / U pripremi; prikazani odgovarajući M1 obrazac i dokumentacioni paket | podnosilac / sistem | `BM-ML-019`; `BM-ML-014`; `BM-ML-020`; `BM-ML-025`; F-06 |
| Prijava | — | kreiranje ili prikaz obrasca i paketa | identitet nije podržan, ili namjera, oblik ili faza nijesu utvrđeni | nema proizvoljnog paketa ni obrasca; nema svrstavanja u `ostalo`; kreiranje i konačno podnošenje nijesu dozvoljeni; jasna poruka | sistem | `BM-ML-009`; `BM-ML-010`; F-06 |
| Prijava | `draft` | uređivanje nacrta | rok traje; vlasnik prijave | ostaje `draft`; namjera, tip, M1 i poslovna faza ostaju zaključani; `is_registered` se može ponovo pročitati; prikaz dodatnih podataka prati aktuelni status | podnosilac | `BM-ML-021`; F-06 |
| Prijava | `draft` | brisanje nacrta | rok traje; vlasnik prijave | nacrt obrisan | podnosilac | `BM-ML-021` |
| Prijava | `draft` | pokušaj ručne promjene pravnog oblika, namjere, obrasca ili faze | prijava već kreirana | funkcija nije dostupna; promjena nije dozvoljena | podnosilac / sistem | F-06; `BM-ML-025` |
| Prijava | `draft` | nova prijava na drugom Pozivu | drugi Poziv objavljen; rok drugog Poziva traje | nova prijava `draft`; bez prenosa; namjera, oblik i faza se ponovo utvrđuju, bez prebacivanja stare prijave | podnosilac | `BM-ML-052`; `BM-ML-014`; F-06 |

## 7.9. Prihvatni kriterijumi — prijava

### 7.9.1 — Kreiranje samo na objavljenom Pozivu

**Ako:** Poziv nije objavljen ili rok ne traje, ili korisnik nije prijavljeni podnosilac.

**Kada:** pokuša kreirati prijavu.

**Onda:** Platforma blokira kreiranje.

Izvor: `BM-ML-019`; `BM-ML-033`.

### 7.9.2 — Vlasništvo prijave

**Ako:** prijava pripada podnosiocu A.

**Kada:** podnosilac B, Administrator ili Komisija tokom roka pokuša otvoriti njen sadržaj.

**Onda:** Platforma **ne** prikazuje tu prijavu tom korisniku, osim granica iz Poglavlja 3.

Izvor: `BM-ML-004`; `BM-ML-005`; `BM-ML-021`.

### 7.9.3 — Uređivanje nacrta

**Ako:** prijava je `U pripremi` i rok traje.

**Kada:** vlasnik mijenja podatke, obrasce ili priloge.

**Onda:** Platforma dozvoljava izmjenu podataka prikazanog obrasca i priloga. Namjera, tip prijave, M1 obrazac i poslovna faza ostaju zaključani. Aktuelni `is_registered` može se ponovo pročitati. Prikaz dodatnih podataka prati taj status. Tip, namjera, faza, M1 obrazac i paket se **ne** mijenjaju automatski. Komisija **ne** vidi nacrt.

Izvor: `BM-ML-021`.

### 7.9.4 — Brisanje nacrta

**Ako:** prijava je `U pripremi` i rok traje.

**Kada:** vlasnik obriše nacrt.

**Onda:** Platforma briše taj nacrt. Brisanje **nije** povlačenje `Podnesene` prijave.

Izvor: `BM-ML-021`.

### 7.9.5 — Nova prijava na drugom Pozivu bez prenosa

**Ako:** isti podnosilac ima prijavu na prvom Pozivu.

**Kada:** kreira prijavu na drugom Pozivu.

**Onda:** nastaje nova prijava. Podaci, obrasci, prilozi i rezultati prvog Poziva **ne** prenose se automatski. Nema funkcije kopiranja prethodne prijave.

Izvor: `BM-ML-052`; `BM-ML-014`.

### 7.9.6 — Zabrana ručne promjene pravnog oblika, namjere i faze

**Ako:** je prijava kreirana sa utvrđenom namjerom, pravnim oblikom, obrascem i poslovnom fazom.

**Kada:** podnosilac uređuje prijavu.

**Onda:** ne postoji funkcija ručne promjene pravnog oblika, namjere, M1 obrasca ili poslovne faze. Nema brisanja, skrivanja ni prenosa podataka zbog takve promjene.

Izvor: odluka F-06; `BM-ML-025`; `BM-ML-029`.

### 7.9.7 — Nepodržani identitet ili nedovoljni podaci

**Ako:** identitet nije podržan za ovaj profil (uključujući NVO, sportsku organizaciju ili stranu poslovnu jedinicu), ili namjera, pravni oblik ili poslovna faza nijesu utvrđeni.

**Kada:** se prijava kreira ili provjerava prije podnošenja.

**Onda:** Platforma **ne** pretpostavlja vrijednost, **ne** svrstava identitet u `ostalo`, **ne** prikazuje proizvoljan obrazac ni paket i **ne** dozvoljava kreiranje ni konačno podnošenje. Prikazuje se jasna poruka. Postupak ispravke naloga **nije** određen.

Izvor: `BM-ML-009`; `BM-ML-010`; odluka F-06.

---

# 8. Obrasci M1a/M1b i M2

Status poglavlja: USVOJENO

Zvanične oznake ovog profila su `M1a`, `M1b` i `M2` (`KN-BM-002` §9.1).

**Ne** koriste se kao zvanične oznake: `1Ma`, `1Mb`, `P2`, niti „Obrazac 2“ kao zamjena za `M2`. `P2` ostaje samo izvorna evidencija sljedivosti.

M3, M4 i M4a **nisu** dio početne prijave u ovom poglavlju.

## 8.1. Izbor M1a ili M1b

Platforma određuje obrazac prema namjeri neregistrovanog fizičkog lica ili prema registrovanom pravnom obliku (`BM-ML-025`; odluka F-06). Nema ručnog selektora M1a/M1b.

* neregistrovano fizičko lice koje planira registraciju kao preduzetnik → **M1a**;
* registrovani preduzetnik → **M1a**;
* neregistrovano fizičko lice koje planira osnivanje privrednog društva → **M1b**;
* registrovano privredno društvo (DOO, AD, OD ili KD) → **M1b**.

Pogrešan obrazac **nije** moguće ručno izabrati. Obrazac se **ne** mijenja tokom uređivanja prijave.

Kod planiranog društva M1b je obrazac namjere neregistrovanog fizičkog lica. PIB, CRPS, registrovano sjedište, formalni osnivač i formalni izvršni direktor **ne** prikazuju se i **ne** zahtijevaju. Ostala primjenjiva M1b polja ostaju. Kod registrovanog društva formalni podnosilac je privredno društvo; nosilac biznisa, ovlašćeno lice i blok podataka registrovanog društva vode se prema §7.5.

Promjena namjere, pravnog oblika ili poslovne faze unutar nacrta **nije** dozvoljena. **Ne** uvodi se ponašanje brisanja, skrivanja ili prenosa podataka zbog takve promjene.

## 8.2. Oblast i djelatnost

Oblast i djelatnost su **dva različita** obavezna podatka (`BM-ML-026`). Evidentiraju se u **M1a i M1b**.

**Oblast:**

* pretežna oblast realizacije biznis plana;
* slobodan tekst;
* obavezna;
* jedna vrijednost u potvrđenom obimu.

**Djelatnost:**

* konkretnija djelatnost ili podoblast;
* slobodan tekst;
* obavezna;
* dodaje se u M1;
* **ne** uvodi se šifra djelatnosti;
* **ne** uvodi se šifrarnik;
* **ne** izmišlja se mogućnost više djelatnosti.

Platforma **ne** pretpostavlja da je naziv oblasti istovremeno naziv djelatnosti.

## 8.3. Ostala polja M1a i M1b

Oblast i djelatnost su potvrđene u `BM-ML-026`.

Ostala obavezna polja M1a/M1b **ne** izmišljaju se u ovom dokumentu. `KN-FS-002` zadržava strukturu izvornog obrasca iz priloga Odluke. Potpuni katalog polja mora biti prenesen iz tog potvrđenog priloga, bez izmišljanja novih polja.

Prazna obavezna polja izvornog obrasca **blokiraju** konačno podnošenje (`BM-ML-022`), osim registracionih podataka društva koji u V1 nijesu obavezni za planirano društvo.

Za neregistrovano fizičko lice koje planira društvo, nedostatak PIB-a, CRPS broja, registrovanog sjedišta, formalnog osnivača i formalnog izvršnog direktora **ne** blokira podnošenje i **ne** čini M1b nepopunjenim. Za registrovano društvo ti podaci **ostaju obavezni**.

Sekcija dodatnih podataka (broj računa, PDV broj, website) prikazuje se prema aktuelnom `is_registered`, ne prema `applicant_type`. Ako je `is_registered` = NE, sekcija **nije** primjenjiva i **ne prikazuje se**.

## 8.4. Obrazac M2

`M2` je zvanična oznaka forme za biznis plan.

Ostala polja M2, osim tačaka 7 i 22 razrađenih ovdje, zadržavaju strukturu izvornog obrasca. Potpuni katalog tih polja prenosi se iz potvrđenog priloga, bez izmišljanja.

### Tačka 7

U tački 7 podnosilac bira **tačno jedan** odgovor (`BM-ML-027`):

* izbor je obavezan;
* odgovori su međusobno isključivi;
* istovremeni izbor više odgovora **nije** dozvoljen;
* ponuđeni odgovori su oni iz izvornog obrasca M2; ovaj dokument ih **ne** rekonstruira pretpostavkom;
* ako postoji i izabere se `Drugo`, tekstualno objašnjenje je **obavezno**;
* najveći broj izbora **nije** izmišljen, jer je uvijek jedan.

Tačan UI element nije određen ovim poglavljem.

### Tačka 22 — tabela nabavki

Tabela pripada tački 22 i **nije** posebna nova tačka (`BM-ML-028`).

* obavezna je za svakog podnosioca;
* mora biti popunjena prije podnošenja;
* **ne** može biti prazna;
* mora imati **najmanje jednu** stavku.

Svaka stavka sadrži samo potvrđena polja:

* vrstu nabavke;
* cijenu po predračunu u eurima.

Ako postoji više vrsta nabavke, svaka se unosi kao posebna stavka. Cijene se sabiraju u ukupni iznos u eurima. Polje `UKUPNO` Platforma **računa**; podnosilac ga **ne** unosi kao posebno obavezno polje.

Dok je prijava `U pripremi` i rok traje, podnosilac može dodavati i uklanjati stavke. Nakon podnošenja tabela je zaključana.

Odnos ukupne vrijednosti i traženog iznosa **provjerava Komisija**. Platforma **ne** uvodi automatsku blokadu zato što zbir nije jednak traženom iznosu.

**Ne** uvode se kao obavezna polja: količina, jedinica mjere, jedinična cijena, poseban PDV, cijena bez PDV-a, dobavljač, napomena, dodatni opis namjene.

## 8.5. Funkcionalni prelazi — obrasci

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Prijava | — | prikaz M1a/M1b | neregistrovano fizičko lice koje planira preduzetnika, ili registrovani preduzetnik | prikazuje se M1a; nema ručnog izbora M1b | sistem | `BM-ML-025`; F-06 |
| Prijava | — | prikaz M1a/M1b | neregistrovano fizičko lice koje planira društvo, ili registrovano privredno društvo (DOO, AD, OD ili KD) | prikazuje se M1b; nema ručnog izbora M1a | sistem | `BM-ML-025`; F-06 |
| M2 tačka 7 | U pripremi | izbor odgovora | tačno jedan odgovor | izbor evidentiran | podnosilac | `BM-ML-027` |
| M2 tačka 7 | U pripremi | izbor `Drugo` | tekst objašnjenja unesen | objašnjenje evidentirano | podnosilac | `BM-ML-027` |
| M2 tačka 22 | U pripremi | dodavanje ili uklanjanje stavke | rok traje | tabela ažurirana; zbir preračunat | podnosilac | `BM-ML-028` |

## 8.6. Prihvatni kriterijumi — obrasci

### 8.6.1 — Izbor M1a

**Ako:** neregistrovano fizičko lice planira registraciju kao preduzetnik, ili je potvrđeni pravni oblik naloga registrovani preduzetnik.

**Kada:** se kreira prijava.

**Onda:** Platforma prikazuje **M1a** i **ne** omogućava ručni izbor M1b.

Izvor: `BM-ML-025`; odluka F-06.

### 8.6.2 — Izbor M1b

**Ako:** neregistrovano fizičko lice planira osnivanje privrednog društva, ili je potvrđeni pravni oblik naloga privredno društvo (DOO, AD, OD ili KD).

**Kada:** se kreira prijava.

**Onda:** Platforma prikazuje **M1b** i **ne** omogućava ručni izbor M1a. Ako je riječ o planiranom društvu, blok podataka registrovanog društva **nije** vidljiv niti obavezan. Ako je riječ o registrovanom društvu, taj blok **ostaje obavezan**.

Izvor: `BM-ML-025`; odluka F-06.

### 8.6.3 — Oblast

**Ako:** podnosilac popunjava M1a ili M1b.

**Kada:** ostavi oblast praznom i pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje. Oblast je obavezan slobodan tekst, jedna vrijednost, bez šifrarnika.

Izvor: `BM-ML-026`; `BM-ML-022`.

### 8.6.4 — Djelatnost

**Ako:** podnosilac popunjava M1a ili M1b.

**Kada:** ostavi djelatnost praznom i pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje. Djelatnost je obavezan slobodan tekst, odvojen od oblasti, bez šifre i bez više vrijednosti.

Izvor: `BM-ML-026`; `BM-ML-022`.

### 8.6.5 — Tačka 7

**Ako:** tačka 7 M2 nema tačno jedan izabrani odgovor.

**Kada:** podnosilac pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje.

Izvor: `BM-ML-027`; `BM-ML-022`.

### 8.6.6 — Drugo

**Ako:** u tački 7 je izabrano `Drugo`, a tekstualno objašnjenje nije uneseno.

**Kada:** podnosilac pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje.

Izvor: `BM-ML-027`; `BM-ML-022`.

### 8.6.7 — Tabela tačke 22

**Ako:** tabela tačke 22 je prazna ili nema nijednu stavku sa vrstom nabavke i cijenom po predračunu u eurima.

**Kada:** podnosilac pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje.

Izvor: `BM-ML-028`; `BM-ML-022`.

### 8.6.8 — Zbir cijena

**Ako:** tabela ima jednu ili više stavki.

**Kada:** Platforma prikazuje ukupan iznos.

**Onda:** zbir cijena po predračunu računa Platforma. **Ne** blokira podnošenje zato što zbir nije jednak traženom iznosu. Odnos provjerava Komisija.

Izvor: `BM-ML-028`.

### 8.6.9 — Najmanje jedna stavka

**Ako:** podnosilac ukloni sve stavke tabele 22.

**Kada:** pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje jer tabela mora imati najmanje jednu stavku.

Izvor: `BM-ML-028`.

---

# 9. Prateća dokumentacija

Status poglavlja: USVOJENO

Prateća dokumentacija određuje se prema **prethodno utvrđenoj** namjeri, pravnom obliku i poslovnoj fazi započinjanje/razvoj (`BM-ML-029`; odluka F-06).

Podnosilac **ne** bira paket. Paket se **ne** mijenja ručno. Platforma **ne** kombinuje dokumente iz različitih paketa i **ne** uvodi funkciju automatskog prenosa priloga u drugi paket.

Prenose se **četiri** paketa iz člana 14, izvornim redoslijedom. Paketi ženskog profila se **ne** prepisuju. Dokument koji nije u konkretnom paketu **ne** dodaje se.

Stavke 1 i 2 svakog paketa (obrasci M1a/M1b i M2) jesu forme iz Poglavlja 8, a ne dodatni upload istog obrasca.

Nedostatak obaveznog pratećeg dokumenta pri podnošenju obrađuje se prema `BM-ML-022`: upozorenje, bez blokade. Konačnu potpunost utvrđuje Komisija. Platforma **ne** donosi automatsku pravnu odluku o potpunosti.

## 9.1. Paket 1 — Preduzetnik koji započinje poslovanje

Izvorni naziv: *Preduzetnici koje započinju biznis (čiji biznisi nisu stariji od godinu dana u trenutku raspisivanja konkursa ili tek planiraju otpočinjanje).*

Ovaj paket koristi registrovani preduzetnik koji započinje poslovanje i fizičko lice koje planira registraciju kao preduzetnik. Fizičko lice koje planira osnivanje privrednog društva **ne** koristi ovaj paket, nego paket 3. Poseban peti paket se **ne** uvodi (`BM-ML-029`; `BM-ML-011`).

| # | Naziv dokumenta | Kada je obavezan | Uslovna obaveznost | Ograničenje starosti | Na koga se odnosi | BM izvor |
|---|-----------------|------------------|--------------------|----------------------|-------------------|----------|
| 1 | Prijava na konkurs (obrazac M1a) | obavezna forma prijave | — | — | podnosilac | `BM-ML-029`; `BM-ML-025` |
| 2 | Forma za biznis plan (obrazac M2) | obavezna forma | — | — | podnosilac | `BM-ML-029` |
| 3 | Ovjerena kopija lične karte | obavezna za potpunost | — | — | podnosilac | `BM-ML-029` |
| 4 | Rješenje o upisu u CRPS | uslovno | ukoliko ima registrovanu djelatnost | — | podnosilac / preduzetnik | `BM-ML-029` |
| 5 | Rješenje o registraciji PJ Poreske uprave | uslovno | ukoliko ima registrovanu djelatnost | — | podnosilac / preduzetnik | `BM-ML-029` |
| 6 | Rješenje o registraciji za PDV ili potvrda da nije PDV obveznik | uslovno | ukoliko ima registrovanu djelatnost; jedno od dva prema PDV statusu | — | podnosilac / preduzetnik | `BM-ML-029` |
| 7 | Potvrda da se ne vodi krivični postupak, izdata od Osnovnog suda | obavezna za potpunost | — | — | podnosilac odnosno preduzetnik | `BM-ML-029` |
| 8 | Uvjerenje lokalne uprave o urednom izmirivanju poreza po osnovu prireza, članskog doprinosa, lokalnih komunalnih taksi i naknada | obavezno za potpunost | — | ne starije od 30 dana | podnosilac odnosno preduzetnik | `BM-ML-029` |
| 9 | Uvjerenje lokalne uprave o urednom izmirivanju poreza na nepokretnost | obavezno za potpunost | — | ne starije od 30 dana | podnosilac odnosno preduzetnik | `BM-ML-029` |
| 10 | Dokaz o broju poslovnog žiro računa | **nije** obavezan za početno podnošenje | može se dobrovoljno priložiti; odsustvo ne čini prijavu nepotpunom | — | podnosilac / preduzetnik | `BM-ML-032`; `BM-ML-029` |
| 11 | Predračuni za planiranu nabavku | obavezni za potpunost | — | — | podnosilac | `BM-ML-029` |

## 9.2. Paket 2 — Preduzetnik u razvoju

Izvorni naziv: *Preduzetnici koje planiraju razvoj poslovanja.*

| # | Naziv dokumenta | Kada je obavezan | Uslovna obaveznost | Ograničenje starosti | Na koga se odnosi | BM izvor |
|---|-----------------|------------------|--------------------|----------------------|-------------------|----------|
| 1 | Prijava na konkurs (obrazac M1a) | obavezna forma | — | — | preduzetnik | `BM-ML-029` |
| 2 | Forma za biznis plan (obrazac M2) | obavezna forma | — | — | preduzetnik | `BM-ML-029` |
| 3 | Ovjerena kopija lične karte | obavezna za potpunost | — | — | preduzetnik | `BM-ML-029` |
| 4 | Rješenje o upisu u CRPS | obavezno za potpunost | — | — | preduzetnik | `BM-ML-029` |
| 5 | Rješenje o registraciji PJ Poreske uprave | obavezno za potpunost | — | — | preduzetnik | `BM-ML-029` |
| 6 | Rješenje o registraciji za PDV ili potvrda da nije PDV obveznik | obavezno za potpunost | jedno od dva prema PDV statusu | — | preduzetnik | `BM-ML-029` |
| 7 | Potvrda da se ne vodi krivični postupak, izdata od Osnovnog suda | obavezna za potpunost | — | — | preduzetnik | `BM-ML-029` |
| 8 | Uvjerenje lokalne uprave o urednom izmirivanju poreza po osnovu prireza, članskog doprinosa, lokalnih komunalnih taksi i naknada | obavezno za potpunost | — | ne starije od 30 dana | preduzetnik | `BM-ML-029` |
| 9 | Uvjerenje lokalne uprave o urednom izmirivanju poreza na nepokretnost | obavezno za potpunost | — | ne starije od 30 dana | preduzetnik | `BM-ML-029` |
| 10 | Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa | obavezna za potpunost | — | ne starija od 30 dana | preduzetnik | `BM-ML-029` |
| 11 | IOPPD za posljednji mjesec uplate ili potvrda Poreske uprave da preduzetnik nema zaposlenih | obavezno za potpunost | **jedno od ta dva**, ne oba | — | preduzetnik | `BM-ML-029` |
| 12 | Dokaz o broju poslovnog žiro računa | **nije** obavezan za početno podnošenje | može se dobrovoljno priložiti; odsustvo ne čini prijavu nepotpunom | — | preduzetnik | `BM-ML-032`; `BM-ML-029` |
| 13 | Predračuni za planiranu nabavku | obavezni za potpunost | — | — | preduzetnik | `BM-ML-029` |

## 9.3. Paket 3 — Društvo koje započinje poslovanje

Izvorni naziv: *Društva koja započinju biznis (čiji biznisi nisu stariji od godinu dana u trenutku raspisivanja konkursa ili tek planiraju otpočinjanje).*

Ovaj paket koristi registrovano privredno društvo koje započinje poslovanje i fizičko lice koje planira osnivanje privrednog društva. Za planirano društvo uslovna CRPS i poreska dokumentacija ostaju uslovna „ukoliko ima registrovanu djelatnost“; njihov nedostatak uz početnu prijavu **ne** čini M1b nepopunjenim.

| # | Naziv dokumenta | Kada je obavezan | Uslovna obaveznost | Ograničenje starosti | Na koga se odnosi | BM izvor |
|---|-----------------|------------------|--------------------|----------------------|-------------------|----------|
| 1 | Prijava na konkurs (obrazac M1b) | obavezna forma | — | — | privredno društvo | `BM-ML-029`; `BM-ML-025` |
| 2 | Forma za biznis plan (obrazac M2) | obavezna forma | — | — | privredno društvo | `BM-ML-029` |
| 3 | Ovjerena kopija lične karte nosioca biznisa | obavezna za potpunost | — | — | nosilac biznisa | `BM-ML-029` |
| 4 | Rješenje o upisu u CRPS | uslovno | ukoliko ima registrovanu djelatnost | — | društvo | `BM-ML-029` |
| 5 | Rješenje o registraciji PJ Poreske uprave | uslovno | ukoliko ima registrovanu djelatnost | — | društvo | `BM-ML-029` |
| 6 | Rješenje o registraciji za PDV ili potvrda da nije PDV obveznik | uslovno | ukoliko ima registrovanu djelatnost; jedno od dva prema PDV statusu | — | društvo | `BM-ML-029` |
| 7 | Važeći Statut društva | uslovno | ukoliko ima registrovanu djelatnost | — | društvo | `BM-ML-029` |
| 8 | Važeći karton deponovanih potpisa | uslovno | ukoliko ima registrovanu djelatnost | — | društvo | `BM-ML-029` |
| 9 | Potvrda da se ne vodi krivični postupak, izdata od Osnovnog suda | obavezna za potpunost | — | — | podnosilac prijave odnosno nosilac biznisa | `BM-ML-029` |
| 10 | Uvjerenje lokalne uprave o urednom izmirivanju poreza po osnovu prireza, članskog doprinosa, lokalnih komunalnih taksi i naknada | obavezno za potpunost | — | ne starije od 30 dana | podnosilac prijave odnosno nosilac biznisa | `BM-ML-029` |
| 11 | Uvjerenje lokalne uprave o urednom izmirivanju poreza na nepokretnost | obavezno za potpunost | — | ne starije od 30 dana | podnosilac prijave odnosno nosilac biznisa | `BM-ML-029` |
| 12 | Predračuni za planiranu nabavku | obavezni za potpunost | — | — | društvo | `BM-ML-029` |

## 9.4. Paket 4 — Društvo u razvoju

Izvorni naziv: *Društva koja planiraju razvoj poslovanja.*

| # | Naziv dokumenta | Kada je obavezan | Uslovna obaveznost | Ograničenje starosti | Na koga se odnosi | BM izvor |
|---|-----------------|------------------|--------------------|----------------------|-------------------|----------|
| 1 | Prijava na konkurs (obrazac M1b) | obavezna forma | — | — | privredno društvo | `BM-ML-029` |
| 2 | Forma za biznis plan (obrazac M2) | obavezna forma | — | — | privredno društvo | `BM-ML-029` |
| 3 | Ovjerena kopija lične karte nosioca biznisa | obavezna za potpunost | — | — | nosilac biznisa | `BM-ML-029` |
| 4 | Rješenje o upisu u CRPS | obavezno za potpunost | — | — | društvo | `BM-ML-029` |
| 5 | Rješenje o registraciji PJ Poreske uprave | obavezno za potpunost | — | — | društvo | `BM-ML-029` |
| 6 | Rješenje o registraciji za PDV ili potvrda da nije PDV obveznik | obavezno za potpunost | jedno od dva prema PDV statusu | — | društvo | `BM-ML-029` |
| 7 | Važeći Statut društva | obavezan za potpunost | — | — | društvo | `BM-ML-029` |
| 8 | Važeći karton deponovanih potpisa | obavezan za potpunost | — | — | društvo | `BM-ML-029` |
| 9 | Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i Analitika dobavljača) za prethodnu godinu; ako ne vodi analitiku kupaca jer posluje isključivo sa fizičkim licima i naplata je odmah putem registar kase — periodični izvještaj sa registar kase | obavezno za potpunost | alternativa analitici kupaca kako je u izvornom paketu | — | društvo | `BM-ML-029` |
| 10 | Potvrda da se ne vodi krivični postupak, izdata od Osnovnog suda | obavezna za potpunost | — | — | društvo i nosilac biznisa | `BM-ML-029` |
| 11 | Uvjerenje lokalne uprave o urednom izmirivanju poreza po osnovu prireza, članskog doprinosa, lokalnih komunalnih taksi i naknada | obavezno za potpunost | — | ne starije od 30 dana | nosilac biznisa i društvo | `BM-ML-029` |
| 12 | Uvjerenje lokalne uprave o urednom izmirivanju poreza na nepokretnost | obavezno za potpunost | — | ne starije od 30 dana | nosilac biznisa i društvo | `BM-ML-029` |
| 13 | Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa | obavezna za potpunost | — | ne starija od 30 dana | nosilac biznisa i društvo | `BM-ML-029` |
| 14 | IOPPD za posljednji mjesec uplate ili potvrda Poreske uprave da društvo nema zaposlenih | obavezno za potpunost | **jedno od ta dva**, ne oba; vidi §9.5 | — | društvo | `BM-ML-031`; `BM-ML-029` |
| 15 | Predračuni za planiranu nabavku | obavezni za potpunost | — | — | društvo | `BM-ML-029` |

Izvorni član 14 u stavci 14 paketa društva u razvoju navodi IOPPD. Funkcionalno se primjenjuje `BM-ML-031`: društvo sa zaposlenima dostavlja IOPPD; društvo bez zaposlenih dostavlja potvrdu Poreske uprave da nema zaposlenih. Izvorni zapis se ne „ispravlja“ u katalogu; primjenjuje se odobreno pravilo.

Rok starosti potvrde da nema zaposlenih **nije** uveden, jer BM to nije potvrdio.

## 9.5. IOPPD ili potvrda za društvo u razvoju

Za društvo u razvoju zahtijeva se **jedan** odgovarajući dokaz (`BM-ML-031`):

* ako ima zaposlene — IOPPD za posljednji mjesec uplate poreza i doprinosa, ovjeren od Poreske uprave;
* ako nema zaposlene — potvrdu Poreske uprave da nema zaposlenih.

Ne zahtijevaju se oba. Nedostatak oba odgovarajuća dokaza čini dokumentaciju **nepotpunom**, ali **ne** blokira tehničko podnošenje (`BM-ML-022`).

## 9.6. Dokaz o žiro računu

Dokaz o žiro računu **nije** obavezan za početno podnošenje (`BM-ML-032`).

* može se dobrovoljno priložiti;
* njegovo odsustvo **ne** čini prijavu nepotpunom;
* **ne** prikazuje se kao nedostajući obavezni prilog početne prijave;
* provjera prije ugovora je **van V1**.

Ovo pravilo ima prednost nad različitim navođenjem žiro računa u paketima člana 14.

## 9.7. Jedan dokument po tipu

Za svaki tip priloga postoji jedno funkcionalno mjesto (`BM-ML-030`).

Više fajlova koji zajedno čine isti dokaz mogu se tretirati kao **jedan logički dokument**. Tehnički način objedinjavanja, PDF spajanje, limiti veličine, ekstenzije i storage **nisu** određeni ovim poglavljem.

Dok je prijava `U pripremi` i rok traje, dozvoljeno je dodavanje, uklanjanje i zamjena. Zamjena je uklanjanje postojećeg dokumenta i učitavanje zamjene. Poslije podnošenja izmjena **nije** dozvoljena (`BM-ML-023`).

## 9.8. Funkcionalni prelazi — prilozi

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Prilog | U pripremi | prikaz dokumentacionog paketa | namjera, pravni oblik i poslovna faza utvrđeni | prikazuje se odgovarajući paket; podnosilac ne bira paket | sistem | `BM-ML-029`; F-06 |
| Prilog | U pripremi | dodavanje | rok traje; tip iz odgovarajućeg paketa | prilog evidentiran na tom tipu | podnosilac | `BM-ML-030`; `BM-ML-029` |
| Prilog | U pripremi | zamjena | rok traje; postoji prilog tog tipa | stari uklonjen; novi evidentiran | podnosilac | `BM-ML-030` |
| Prilog | U pripremi | uklanjanje | rok traje | tip ostaje prazan | podnosilac | `BM-ML-030` |

## 9.9. Prihvatni kriterijumi — dokumentacija

### 9.9.1 — Četiri dokumentaciona paketa

**Ako:** su namjera, pravni oblik i poslovna faza započinjanje/razvoj utvrđeni.

**Kada:** se otvori dio za dokumentaciju.

**Onda:** Platforma prikazuje odgovarajući paket iz `BM-ML-029`. Podnosilac **ne** bira paket. Paket ženskog profila se ne prikazuje.

Izvor: `BM-ML-029`; odluka F-06.

### 9.9.2 — IOPPD ili potvrda

**Ako:** kategorija je društvo u razvoju i nije priložen ni IOPPD ni potvrda da nema zaposlenih.

**Kada:** podnosilac pokuša konačno podnošenje.

**Onda:** Platforma prikazuje upozorenje o nedostajućem dokazu i **dozvoljava** podnošenje. Dokumentacija je poslovno nepotpuna dok Komisija ne utvrdi potpunost.

Izvor: `BM-ML-031`; `BM-ML-022`.

### 9.9.3 — Žiro račun

**Ako:** dokaz o žiro računu nije priložen uz početnu prijavu.

**Kada:** podnosilac pokrene kontrolu prije podnošenja.

**Onda:** Platforma **ne** prikazuje žiro račun kao nedostajući obavezni prilog i **ne** označava prijavu nepotpunom zbog tog odsustva.

Izvor: `BM-ML-032`.

### 9.9.4 — Jedan dokument po tipu

**Ako:** za tip priloga već postoji dokument.

**Kada:** podnosilac dodaje drugi fajl kao isti tip, osim zamjene.

**Onda:** Platforma vodi jedan dokument po tipu. Više fajlova istog dokaza tretiraju se kao jedan logički dokument, bez određivanja tehničkog spajanja.

Izvor: `BM-ML-030`.

### 9.9.5 — Zamjena dokumenta u nacrtu

**Ako:** prijava je `U pripremi` i rok traje.

**Kada:** vlasnik zamijeni prilog.

**Onda:** prethodni dokument tog tipa se uklanja i učitava se zamjena.

Izvor: `BM-ML-030`; `BM-ML-021`.

---

# 10. Podnošenje i zaključavanje

Status poglavlja: USVOJENO

Ovo poglavlje određuje kontrolu prije podnošenja, izričitu potvrdu, zaključavanje i istek roka za nacrt. Ne uvodi elektronski potpis ni dodatni pravni korak. `U pripremi` mapira se na `draft`, a `Podnesena` na `submitted`. Kasniji statusi ne otključavaju prijavu.

## 10.1. Kontrola prije podnošenja

Prije podnošenja Platforma razdvaja (`BM-ML-022`):

**A. blokirajuće uslove;**
**B. nedostajuće priloge koji izazivaju upozorenje;**
**C. rok;**
**D. potvrdu korisnika.**

### A. Blokiraju

* prazna obavezna polja M1a/M1b, uključujući oblast i djelatnost;
* prazna obavezna polja M2;
* nije utvrđen pravni oblik;
* nije utvrđena namjera neregistrovanog fizičkog lica, kada je potrebna;
* nije utvrđena poslovna faza započinjanje/razvoj;
* prikazani M1 ne odgovara namjeri ili registrovanom pravnom obliku;
* prikazani dokumentacioni paket ne odgovara namjeri, pravnom obliku i poslovnoj fazi;
* tačka 7 bez tačno jednog odgovora;
* `Drugo` bez objašnjenja;
* prazna tabela tačke 22 ili bez najmanje jedne stavke;
* pokušaj podnošenja nakon roka;
* već postojeća `Podnesena` prijava istog podnosioca na istom Pozivu.

Nedostatak PIB-a, CRPS broja, registrovanog sjedišta ili formalnih podataka o osnivaču i izvršnom direktoru **ne** blokira podnošenje planiranog društva. Za registrovano društvo prazan blok tih podataka **blokira** konačno podnošenje.

Ako identitet nije podržan ili potrebni potvrđeni podaci nijesu dostupni, Platforma **ne** popunjava fazu pretpostavkom, **ne** svrstava identitet u `ostalo`, prikazuje jasnu poruku i **ne** dozvoljava konačno podnošenje. Postupak ispravke naloga **nije** određen.

Nedostajući ili ne-kotorski teritorijalni podatak može izazvati **upozorenje**. **Ne** blokira podnošenje samo na osnovu adrese.

### B. Upozoravaju, ne blokiraju

Nedostajući prateći dokument:

* prikazuje upozorenje;
* prikazuje listu nedostajućih dokumenata;
* **ne** blokira podnošenje;
* **ne** znači da je prijava potpuna;
* konačnu potpunost utvrđuje Komisija.

Dokaz o žiro računu **ne** ulazi u listu nedostajućih obaveznih priloga početne prijave (`BM-ML-032`).

### C. Rok

Ako rok nije počeo ili je istekao, konačno podnošenje je blokirano (`BM-ML-033`; `BM-ML-024`).

### D. Potvrda

Konačno podnošenje zahtijeva **izričitu potvrdu** podnosioca. Prije potvrde Platforma prikazuje jasno upozorenje da će prijava biti zaključana. Podnosilac može:

* vratiti se na pregled i uređivanje;
* izričito potvrditi konačno podnošenje.

Tačan izgled upozorenja nije određen ovim poglavljem. Elektronski potpis se **ne** uvodi.

## 10.2. Nakon potvrde

Izričitom potvrdom prijava prelazi iz `draft` / `U pripremi` u `submitted` / `Podnesena` (`BM-ML-023`).

Evidentira se trenutak podnošenja.

Zaključavaju se podaci, obrasci, prilozi i tabela nabavki.

Nakon toga prijava se **ne** može:

* mijenjati;
* dopunjavati;
* obrisati;
* povući;
* ponovo podnijeti na istom Pozivu.

Prigovor **ne** otključava prijavu i **ne** omogućava dopunu ni zamjenu dokumentacije.

`Podnesena` prijava ostaje dostupna podnosiocu za pregled. Promjena korisničkog naloga **ne** mijenja podnesenu prijavu.

## 10.3. Istek roka za nacrt

Ako prijava ostane `U pripremi` nakon isteka roka (`BM-ML-024`):

* ostaje sačuvana;
* dostupna je podnosiocu samo za pregled;
* ne može se podnijeti;
* ne postaje `submitted` / `Podnesena`;
* ne postaje `Nepotpuna`;
* ne prelazi automatski u `rejected`;
* Komisija joj ne pristupa;
* ne prenosi se automatski u drugi Poziv.

## 10.4. Funkcionalni prelazi — podnošenje i zaključavanje

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Prijava | `draft` | provjera prije podnošenja | obavezna polja prazna, ili tačka 7, ili tabela 22, ili nije utvrđen pravni oblik, namjera ili faza, ili M1 ili paket ne odgovaraju utvrđenim podacima, ili registrovano društvo nema blok podataka društva | blokirano; prikazuje šta nedostaje; faza se ne popunjava pretpostavkom; planirano društvo se ne blokira zbog PIB-a, CRPS-a, sjedišta, osnivača ili direktora | sistem | `BM-ML-022`; F-06 |
| Prijava | `draft` | provjera prije podnošenja | nedostaju prateći dokumenti paketa, osim žiro računa | upozorenje i lista; podnošenje ostaje moguće | sistem | `BM-ML-022`; `BM-ML-032` |
| Prijava | `draft` | konačna potvrda | blokirajući uslovi nijesu ispunjeni; rok traje; nema Podnesene na istom Pozivu | `draft` → `submitted`; zaključano | podnosilac | `BM-ML-023`; `BM-KN-015` |
| Prijava | `submitted` | pokušaj izmjene, dopune, brisanja, povlačenja ili ponovnog podnošenja | — | zabranjeno | svi | `BM-ML-023` |
| Prijava | `submitted` | prigovor | najmanje jedan aktivirani eliminatorni kriterijum; objedinjeno obavještenje poslato; rok od tri dana traje | jedan prigovor preko digitalnog servisa; može obuhvatiti jedan, više ili sve aktivirane kriterijume; neaktivirani se ne mogu osporavati; prijava ostaje zaključana; status ostaje `submitted` | podnosilac | `BM-ML-023`; `BM-ML-036` |
| Prijava | `draft` | istek roka | nije podnesena | ostaje `draft`; samo pregled; nije `rejected` | sistem | `BM-ML-024` |

## 10.5. Prihvatni kriterijumi — podnošenje

### 10.5.1 — Obavezna polja koja blokiraju

**Ako:** nije utvrđen pravni oblik, namjera ili poslovna faza, prikazani M1 ne odgovara namjeri ili registrovanom obliku, prikazani paket ne odgovara utvrđenim podacima, prazna su obavezna polja M1 ili M2, tačka 7 nema jedan odgovor, `Drugo` nema tekst, ili tabela 22 nema najmanje jednu stavku.

**Kada:** podnosilac pokuša konačno podnošenje.

**Onda:** Platforma blokira podnošenje i prikazuje šta treba popuniti odnosno utvrditi. Faza se **ne** popunjava pretpostavkom. Teritorijalni podatak **ne** blokira podnošenje sam po sebi. Nedostatak PIB-a i CRPS-a **ne** blokira planirano društvo. Registrovano društvo **mora** popuniti blok podataka društva.

Izvor: `BM-ML-022`; `BM-ML-025`; `BM-ML-026`; `BM-ML-027`; `BM-ML-028`; `BM-ML-009`; `BM-ML-010`; odluka F-06.

### 10.5.2 — Dokumenti koji upozoravaju

**Ako:** nedostaje obavezni prateći dokument iz odgovarajućeg paketa, osim dokaza o žiro računu.

**Kada:** podnosilac pokrene kontrolu prije podnošenja.

**Onda:** Platforma prikazuje upozorenje i listu nedostajućih dokumenata i **dozvoljava** podnošenje. To **ne** znači da je prijava potpuna.

Izvor: `BM-ML-022`; `BM-ML-029`.

### 10.5.3 — Izričita potvrda

**Ako:** blokirajući uslovi nijesu ispunjeni i rok traje.

**Kada:** podnosilac pokrene konačno podnošenje.

**Onda:** Platforma prikazuje upozorenje da će prijava biti zaključana i čeka izričitu potvrdu. Bez potvrde stanje ostaje `U pripremi`.

Izvor: `BM-ML-022`; `BM-ML-023`.

### 10.5.4 — Zaključavanje

**Ako:** podnosilac izričito potvrdi konačno podnošenje.

**Kada:** potvrda uspije.

**Onda:** status postaje `submitted` / `Podnesena`. Evidentira se trenutak podnošenja. Podaci, obrasci, prilozi i tabela nabavki se zaključavaju. Naknadna promjena naloga ili ponovno čitanje `is_registered` **ne** mijenja podnesenu prijavu.

Izvor: `BM-ML-023`.

### 10.5.5 — Zabrana povlačenja

**Ako:** prijava je `Podnesena`.

**Kada:** podnosilac pokuša povući, obrisati ili ponovo podnijeti prijavu na istom Pozivu.

**Onda:** Platforma blokira radnju.

Izvor: `BM-ML-023`; `BM-KN-015`.

### 10.5.6 — Prigovor bez otključavanja

**Ako:** prijava je `Podnesena` i podnesen je prigovor.

**Kada:** podnosilac pokuša izmijeniti obrasce ili priloge.

**Onda:** Platforma **ne** otključava prijavu.

Izvor: `BM-ML-023`.

### 10.5.7 — Nacrt nakon isteka

**Ako:** prijava je `U pripremi` i rok je istekao.

**Kada:** podnosilac pokuša uređivanje, brisanje ili podnošenje.

**Onda:** dozvoljen je samo pregled. Status ostaje `draft` / `U pripremi`. Prijava ne postaje `submitted`, `Nepotpuna` niti prelazi automatski u `rejected`.

Izvor: `BM-ML-024`.

---

# 11. Privatnost i pregled dokumenata

Status poglavlja: USVOJENO

Ovo poglavlje određuje privatnost prijave i pregled dokumentacije bez korisničkog preuzimanja (`BM-ML-004`; `BM-ML-005`; `BM-ML-054`; `BM-ML-055`).

Ne određuje tehnički format pregledača, privremene kopije ni implementaciju zaštite. To pripada TS-u. Ne uvodi javni tok službenih akata.

Strukturni obrazac privatnosti i pregleda bez preuzimanja usklađen je sa `KN-FS-003` Poglavljima 8 i 9.4, uz pravila mladih: tri člana, M1a/M1b i M2, i `BM-ML-054` / `BM-ML-055`.

## 11.1. Šta nije javno

Prijava, obrasci M1a ili M1b, obrazac M2 i prilozi **nijesu** javni sadržaj (`BM-ML-054`).

Javno objavljivanje rezultata konkursa **ne** otvara javnosti:

* obrasce M1a ili M1b;
* obrazac M2;
* priloženu dokumentaciju;
* lične podatke koji nijesu određeni za javno objavljivanje;
* individualne ocjene;
* interne napomene Komisije;
* razloge nepotpunosti.

Arhiviranje **ne** mijenja privatnost i **ne** otvara sadržaj prijave javnosti.

## 11.2. Pristup po akterima

**Podnosilac** vidi samo svoje prijave. Ne vidi tuđu prijavu, tuđe obrasce ni tuđe priloge.

**Administrator Konkursa** nema sadržajni pristup prijavama, obrascima, prilozima ni razlozima nepotpunosti (`BM-ML-004`). Dok rok traje vidi samo zbirni broj podnesenih prijava konkretnog Poziva, bez liste identiteta i bez ulaska u pojedinačnu prijavu. Istek roka **ne** daje Administratoru novo pravo sadržajnog uvida.

**Komisija** nema pristup sadržaju prijava dok rok traje (`BM-ML-005`). Činjenica da je prijava Podnesena prije isteka **ne** čini je vidljivom Komisiji dok rok traje.

**Administrator platforme** nije profilni akter ovog dokumenta i **ne** stječe uvid u sadržaj prijava samom platformskom ulogom.

## 11.3. Pregled bez preuzimanja

Nakon isteka roka, aktivni članovi Komisije konkretnog Poziva pregledaju Podnesene obrasce i priloge **unutar Platforme**, bez redovnog korisničkog preuzimanja (`BM-ML-055`).

Pregled je potreban za administrativnu provjeru, razmatranje prigovora, ocjenjivanje i odlučivanje Komisije.

**Nije** dozvoljeno:

* masovno preuzimanje;
* izvoz kompletnog paketa prijave;
* dijeljenje priloga neovlašćenim licima;
* javno objavljivanje priloga.

Tehnički format pregledača ostaje za TS.

Ograničenja pristupa sprovode se na strani servera. Sakrivanje stavke u interfejsu **nije** dovoljno. Poznavanje identifikatora, rute ili URL-a **ne** smije zaobići pravilo. Ovo poglavlje ne određuje tehnički mehanizam.

## 11.4. Funkcionalni prelazi — privatnost

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Prijava | rok traje | pokušaj javnog ili tuđeg uvida | sadržaj M1a/M1b, M2 ili priloga | zabranjeno | javnost / drugi podnosilac | `BM-ML-054` |
| Prijava | rok traje | pregled zbirnog broja | Administrator | vidi samo zbir podnesenih; bez sadržaja | Administrator | `BM-ML-004` |
| Prijava | rok traje | pokušaj uvida Komisije | sadržaj prijave | zabranjeno | Komisija | `BM-ML-005` |
| Prilog | Podnesena; rok istekao | pregled unutar Platforme | aktivni član konkretnog Poziva | pregled dozvoljen; preuzimanje nije | član Komisije | `BM-ML-055` |
| Prilog | Podnesena | masovno preuzimanje ili izvoz paketa | — | zabranjeno | svi | `BM-ML-055` |
| Javni rezultat | objavljen | otvaranje sadržaja prijave | javni korisnik | sadržaj prijave ostaje zatvoren | javnost | `BM-ML-054` |

## 11.5. Prihvatni kriterijumi — privatnost

### 11.5.1 — Prijava nije javna

**Ako:** postoji prijava sa obrascima M1a ili M1b, M2 ili prilozima.

**Kada:** neprijavljeni ili javni korisnik pokuša otvoriti njen sadržaj.

**Onda:** Platforma **ne** prikazuje taj sadržaj. Javni rezultat **ne** otvara sadržaj prijave.

Izvor: `BM-ML-054`.

### 11.5.2 — Vlasništvo podnosioca

**Ako:** prijava pripada podnosiocu A.

**Kada:** podnosilac B pokuša otvoriti tu prijavu.

**Onda:** Platforma **ne** prikazuje tuđu prijavu.

Izvor: `BM-ML-054`.

### 11.5.3 — Administrator bez sadržajnog pristupa

**Ako:** korisnik je Administrator Konkursa.

**Kada:** dok rok traje pokuša otvoriti sadržaj prijave ili razloge nepotpunosti.

**Onda:** Platforma prikazuje samo zbirni broj podnesenih prijava. Sadržaj i razlozi ostaju nedostupni. Ograničenje važi i nakon isteka roka.

Izvor: `BM-ML-004`; `BM-ML-054`.

### 11.5.4 — Komisija tokom roka

**Ako:** rok za prijave još traje.

**Kada:** član Komisije pokuša pristupiti sadržaju prijava.

**Onda:** Platforma **ne** omogućava pristup.

Izvor: `BM-ML-005`.

### 11.5.5 — Pregled bez preuzimanja

**Ako:** rok je istekao i korisnik je aktivni član Komisije konkretnog Poziva.

**Kada:** otvori prilog Podnesene prijave.

**Onda:** Platforma omogućava pregled unutar Platforme i **ne** omogućava redovno korisničko preuzimanje, masovno preuzimanje ni izvoz kompletnog paketa.

Izvor: `BM-ML-055`.

---

# 12. Istek roka i pristup Komisije

Status poglavlja: USVOJENO

Ovo poglavlje određuje istek roka i otvaranje pristupa Komisije. Ne određuje prvu sjednicu u detalju; to pripada Poglavlju 13.

Strukturni obrazac isteka i otključavanja Komisije usklađen je sa `KN-FS-003` Poglavljem 9, uz rok od 20 kalendarskih dana i sastav od tri člana.

## 12.1. Istek roka

Rok ističe u **23:59:59** dvadesetog narednog kalendarskog dana od dana objave, prema lokalnom vremenu Kotora (`BM-ML-033`). Dan objave se ne računa.

Za istek **nije** potrebna radnja Administratora ni Komisije. Platforma **ne** mijenja rok niti automatski odlaže sjednicu.

Istek sam po sebi **ne** mijenja osnovno stanje prijave.

Nakon isteka novo podnošenje **nije** moguće.

## 12.2. Nacrt nakon isteka

Prijava `U pripremi` ostaje sačuvana i dostupna **samo podnosiocu za pregled** (`BM-ML-024`).

Nacrt **ne** prelazi automatski u `submitted`, `Nepotpuna` ili `rejected`. Komisiji **nije** dostupan.

## 12.3. Pristup Komisije nakon isteka

Komisiji se otvaraju **samo prijave sa stanjem `Podnesena`** konkretnog Poziva (`BM-ML-005`).

Pristup imaju samo **aktivni** članovi Komisije povezani sa tim Pozivom.

Sva tri mjesta Komisije moraju biti **formalno popunjena** prije administrativne provjere (F-02; `BM-ML-001`). Ako Komisija nije formalno kompletna:

* rok ističe redovno;
* rok se **ne** produžava;
* pristup administrativnoj provjeri ostaje blokiran dok se sva tri mjesta ne popune.

Administrativna provjera može početi kada su sva tri mjesta formalno popunjena i kada su prisutna **najmanje dva** člana (`BM-ML-002`). Prva sjednica **ne** zahtijeva prisustvo sva tri člana.

Nema zasebne ručne radnje otključavanja prije stvarnog isteka roka.

## 12.4. Funkcionalni prelazi — istek i pristup

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Poziv | objavljen; rok traje | istek roka | 23:59:59 dvadesetog narednog kalendarskog dana | rok istekao; novo podnošenje zabranjeno; rok se ne pomjera | sistem | `BM-ML-033` |
| Prijava | `draft` | istek roka | nije podnesena | ostaje `draft`; samo pregled podnosioca; nije `rejected` | sistem | `BM-ML-024` |
| Prijava | Podnesena | istek roka | Komisija formalno kompletna | dostupna aktivnim članovima Komisije tog Poziva | sistem | `BM-ML-005`; F-02 |
| Prijava | Podnesena | istek roka | Komisija formalno nekompletna | rok istekao; administrativna provjera blokirana | sistem | F-02; `BM-ML-001` |
| Prijava | `draft` | pokušaj uvida Komisije nakon isteka | — | zabranjeno | Komisija | `BM-ML-005`; `BM-ML-024` |

## 12.5. Prihvatni kriterijumi — istek i pristup

### 12.5.1 — Istek u 23:59:59

**Ako:** Poziv je objavljen.

**Kada:** nastupi 23:59:59 dvadesetog narednog kalendarskog dana.

**Onda:** rok ističe. Novo podnošenje nije moguće. Platforma **ne** mijenja rok niti automatski odlaže sjednicu.

Izvor: `BM-ML-033`.

### 12.5.2 — Nacrt ostaje U pripremi

**Ako:** prijava je `U pripremi` i rok istekne.

**Kada:** sistem obradi istek.

**Onda:** prijava ostaje `draft` / `U pripremi`, dostupna samo podnosiocu za pregled. Ne postaje `submitted`, `Nepotpuna` niti prelazi automatski u `rejected`.

Izvor: `BM-ML-024`.

### 12.5.3 — Komisija vidi samo Podnesene

**Ako:** rok je istekao i član je aktivni član Komisije konkretnog Poziva.

**Kada:** otvara listu prijava.

**Onda:** vidi samo prijave u stanju `Podnesena`. Prijave `U pripremi` nijesu dostupne.

Izvor: `BM-ML-005`.

### 12.5.4 — Formalno kompletiranje prije provjere

**Ako:** nijesu popunjena sva tri mjesta Komisije.

**Kada:** se pokuša započeti administrativna provjera.

**Onda:** Platforma blokira početak. Rok se ne produžava.

Izvor: `BM-ML-001`; F-02.

### 12.5.5 — Kvorum dva člana

**Ako:** su sva tri mjesta formalno popunjena i prisutna su najmanje dva člana.

**Kada:** predsjednik pokrene administrativnu provjeru.

**Onda:** Platforma dozvoljava početak. Prva sjednica ne zahtijeva prisustvo sva tri člana.

Izvor: `BM-ML-002`; F-02.

---

# 13. Prva sjednica, M3 i tri eliminatorna kriterijuma

Status poglavlja: USVOJENO

Ovo poglavlje određuje prvu sjednicu, elektronski M3 i tri eliminatorna kriterijuma (`BM-ML-001`; `BM-ML-002`; `BM-ML-003`; `BM-ML-035`).

Načelo da Komisija utvrđuje rezultat, da je sistemska provjera pomoćna, da Administrator ne sprovodi provjeru i da rezultati M3 nijesu osnovna stanja prijave usklađeno je sa `KN-FS-003` Poglavljem 10.2. Sastav, kvorum, kriterijumi i M3 slijede pravila mladih: **tačno tri člana** i **tačno tri eliminatorna kriterijuma** Odluke za mlade. Ne preuzimaju se ženski kriterijumi, Komisija od pet članova ni ženski model kvoruma.

Ne uvodi se javni tok službenih akata. Tehnički mehanizam evidencije prisustva **nije** određen ovim poglavljem.

## 13.1. Sastav i elektronski M3

Komisija ima **tačno tri člana**. Predsjednik je **jedan od ta tri člana** (`BM-ML-001`).

Elektronski M3 prikazuje i koristi **samo tri člana**. Višak mjesta i potpisa iz izvornog obrasca M3 **ne** koristi se i **ne** uvodi dodatne članove.

Elektronski M3 prikazuje **tačno tri eliminatorna kriterijuma** kao tri odvojene stavke, a ne samo jedno zbirno polje `Potpuna` / `Nepotpuna`.

## 13.2. Prva sjednica i kvorum

Prva sjednica može se održati uz kvorum od **najmanje dva** prisutna člana, ako su sva tri mjesta formalno popunjena (`BM-ML-002`; F-02).

Evidentiraju se **stvarno prisutni** članovi. Bez najmanje dva prisutna člana sjednica i administrativna provjera se blokiraju odnosno odlažu. Platforma **ne** odlaže sjednicu automatski umjesto Komisije i **ne** mijenja rok.

Prva sjednica **ne** zahtijeva prisustvo sva tri člana. Sva tri člana potrebna su za prigovor, intervju i konačno ocjenjivanje (`BM-ML-003`).

Komisija prvu sjednicu poslovno zakazuje najkasnije sedam dana od isteka roka (`BM-ML-034`). Platforma **ne** vodi sjednicu kao poseban poslovni objekat i **ne** određuje termin umjesto Komisije.

## 13.3. M3 i tri eliminatorna kriterijuma

U provjeru ulaze samo prijave u stanju `Podnesena`.

Sistemske provjere su **pomoćne**. Sistem može evidentirati nedostajuće dokumente ili druge nalaze, ali **ne** donosi konačnu odluku o ispunjenosti kriterijuma (`BM-ML-035`). Platforma **ne** odlučuje da li je kriterijum ispunjen.

Predsjednik u ime Komisije evidentira u M3 tačno tri odvojene stavke:

1. prijava je nepotpuna — rezultat `Potpuna` ili `Nepotpuna`; `Nepotpuna` aktivira prvi razlog;
2. ranije finansirani korisnik nije dostavio obavezne izvještaje M4 i M4a;
3. biznis plan nije povezan sa prioritetnim oblastima.

To **nije** status prijave. Prijava ostaje `submitted`. Aktiviranje bilo kojeg kriterijuma **ne** postavlja odmah `rejected`.

Napomena „odbiti aplikaciju“ iz izvornog M3 **ne** ukida pravo na obavještenje i prigovor.

Ako nijedan kriterijum nije aktiviran, prigovorni tok se ne otvara i prijava može nastaviti. Ako je aktiviran najmanje jedan kriterijum, prijava ne ide dalje dok je otvoren rok za prigovor ili dok traje odlučivanje o podnesenom prigovoru.

Za svaki kriterijum odvojeno se evidentira najmanje: rezultat provjere; da li je razlog aktiviran; obrazloženje; relevantni član odnosno sastav Komisije; datum i vrijeme; status konačnosti; veza sa prigovorom i odlukom po prigovoru kada postoje; konačni tipizirani razlog odbijanja kada postoji.

Administrator **ne** provjerava kriterijume, **ne** mijenja M3 i **nema** pristup razlozima (`BM-ML-004`).

Za odlučivanje o prigovoru potrebna su **sva tri člana** (`BM-ML-003`). Detalj: Poglavlje 14.

## 13.4. Funkcionalni prelazi — prva sjednica i M3

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Sjednica | Komisija formalno kompletna | početak prve sjednice / administrativne provjere | prisutna manje od dva člana | blokirano odnosno sjednica se odlaže | sistem | `BM-ML-002` |
| Sjednica | Komisija formalno kompletna | početak prve sjednice / administrativne provjere | prisutna najmanje dva člana | provjera može početi; evidentiraju se stvarno prisutni | predsjednik / prisutni članovi | `BM-ML-002`; F-02 |
| M3 | `submitted`; rok istekao | evidentiranje rezultata | kvorum; predsjednik u ime Komisije | tri odvojene stavke; status ostaje `submitted`; aktiviranje ne postavlja odmah `rejected` | predsjednik | `BM-ML-035`; `BM-ML-002` |
| M3 | elektronski prikaz | korišćenje mjesta Komisije | BM-ML-001 | prikazuju se samo tri člana; višak izvornog obrasca se ne koristi | sistem | `BM-ML-001` |
| Admin. rezultat | (nema) | sistemska pomoćna provjera | nedostaju prilozi | lista nedostataka; nije konačna odluka | sistem | `BM-ML-035`; `BM-ML-022` |

## 13.5. Prihvatni kriterijumi — M3 i tri eliminatorna kriterijuma

### 13.5.1 — Tri člana u M3

**Ako:** se otvori elektronski M3.

**Kada:** Platforma prikazuje članove Komisije.

**Onda:** prikazuju se i koriste samo tri člana. Višak mjesta i potpisa izvornog obrasca se ne koristi.

Izvor: `BM-ML-001`.

### 13.5.2 — Kvorum prve sjednice

**Ako:** su sva tri mjesta formalno popunjena, a prisutno je manje od dva člana.

**Kada:** se pokuša započeti administrativna provjera.

**Onda:** Platforma blokira početak. Evidentiraju se stvarno prisutni članovi. Platforma ne odlaže sjednicu automatski kao zamjenu za odluku Komisije.

Izvor: `BM-ML-002`.

### 13.5.3 — Predsjednik evidentira tri kriterijuma

**Ako:** je kvorum ispunjen i prijava je `Podnesena`.

**Kada:** predsjednik evidentira rezultat u M3.

**Onda:** upisuju se tri odvojene stavke. Status ostaje `submitted`. Aktiviranje bilo kojeg kriterijuma ne postavlja odmah `rejected`.

Izvor: `BM-ML-035`; `BM-ML-002`.

### 13.5.4 — Sistemska provjera je pomoćna

**Ako:** sistem evidentira nedostajuće dokumente.

**Kada:** Komisija utvrđuje ispunjenost kriterijuma.

**Onda:** sistemski nalaz **nije** konačna odluka. Konačan rezultat određuje Komisija. Platforma ne odlučuje da li je kriterijum ispunjen.

Izvor: `BM-ML-035`.

### 13.5.5 — Administrator ne provjerava

**Ako:** korisnik je Administrator Konkursa.

**Kada:** pokuša evidentirati M3, mijenjati M3 ili otvoriti razloge.

**Onda:** Platforma **ne** omogućava tu radnju ni uvid u razloge.

Izvor: `BM-ML-004`; `BM-ML-035`.

### 13.5.6 — Prigovor zahtijeva sva tri člana

**Ako:** se odlučuje o prigovoru.

**Kada:** Komisija evidentira ishod.

**Onda:** u odlučivanju učestvuju sva tri člana. Kvorum od dva člana **nije** dovoljan za prigovor. Odluka se evidentira po svakom osporenom kriterijumu.

Izvor: `BM-ML-003`.

### 13.5.7 — M3 prikazuje tačno tri kriterijuma

**Ako:** se otvori elektronski M3.

**Kada:** Platforma prikazuje eliminatornu provjeru.

**Onda:** prikazuju se tačno tri odvojene stavke iz `BM-ML-035`. Ne uvodi se četvrti kriterijum. Zbirno polje `Potpuna` / `Nepotpuna` **nije** jedini prikaz.

Izvor: `BM-ML-035`; `BM-ML-043`.

### 13.5.8 — Odvojeno evidentiranje po kriterijumu

**Ako:** predsjednik evidentira M3.

**Kada:** se čuva rezultat.

**Onda:** za svaki kriterijum odvojeno ostaju rezultat, da li je razlog aktiviran, obrazloženje, sastav, datum i vrijeme, status konačnosti, veza sa prigovorom i odlukom kada postoje, te konačni tipizirani razlog kada postoji. Svi pojedinačni razlozi ostaju u revizijskom tragu.

Izvor: `BM-ML-035`; `BM-ML-043`.

### 13.5.9 — Aktiviranje ne postavlja rejected

**Ako:** je aktiviran jedan ili više kriterijuma.

**Kada:** predsjednik završi početni M3 nalaz.

**Onda:** prijava ostaje `submitted`. `rejected` ne nastaje samo zbog početnog M3 nalaza.

Izvor: `BM-ML-035`; `BM-ML-020`.

---

# 14. Obavještenja i prigovori

Status poglavlja: USVOJENO

Ovo poglavlje određuje objedinjeno obavještenje o aktiviranim eliminatornim kriterijumima i tok jednog prigovora (`BM-ML-023`; `BM-ML-034`; `BM-ML-036`; `BM-ML-037`; `BM-ML-003`).

Obrazac toka prigovora usklađen je sa `KN-FS-003` Poglavljem 10.4–10.5, uz obavezni registrovani e-mail, youth kriterijume, Komisiju od tri člana i youth rokove. **Kanal obavještavanja i kanal podnošenja prigovora nijesu isti.** Ne preuzimaju se ženski eliminatorni kriterijumi, Komisija od pet članova ni ženski rokovi.

Obavještenje se šalje na registrovanu e-mail adresu podnosioca na digitalnom servisu Opštine Kotor. Prigovor se podnosi **isključivo** preko odgovarajuće funkcije digitalnog servisa. Obični spoljni e-mail **nije** zabranjen kanal za slanje obavještenja; on **nije** važeći kanal za podnošenje prigovora.

Ne uvodi se širi e-mail lifecycle, status isporuke, automatsko ponovno slanje, automatsko produženje ni ponovno računanje roka. Neuspjela isporuka **ne** mijenja automatski odluku Komisije ni rok. Ne uvodi se javni tok službenih akata.

## 14.1. Objedinjeno obavještenje

Ako nijedan od tri kriterijuma nije aktiviran, prigovorni tok se **ne** otvara i obavještenje se ne šalje.

Ako je aktiviran najmanje jedan kriterijum, Platforma priprema **jedno** objedinjeno obavještenje, bez obzira da li je aktiviran jedan ili više razloga (`BM-ML-036`).

Obavještenje:

* navodi svaki aktivirani kriterijum posebno;
* za svaki navodi obrazloženje Komisije;
* navodi pravo na prigovor;
* navodi rok od tri dana;
* navodi da se prigovor podnosi kroz digitalni servis;
* šalje se na **registrovanu e-mail adresu** podnosioca;
* **nije** javno.

Rok od tri dana počinje od **evidentiranog trenutka slanja** obavještenja, a **ne** od trenutka kada je podnosilac otvorio ili pročitao poruku.

Podnosilac vidi aktivirane razloge i informacije potrebne za prigovor. Administrator **nema** pristup razlozima (`BM-ML-004`).

Prijava ostaje **zaključana**. Prigovor **nije** dopuna (`BM-ML-023`; `BM-ML-036`).

## 14.2. Podnošenje prigovora

Za jednu prijavu i jedan ciklus eliminatorne provjere podnosilac podnosi **jedan** prigovor.

Prigovor se podnosi **isključivo preko odgovarajuće funkcije digitalnog servisa**:

* za konkretnu prijavu;
* od strane podnosioca;
* u roku od **tri dana** od **evidentiranog slanja** obavještenja, ne od otvaranja ni čitanja poruke;
* tri dana **ne** tumače se kao tri radna dana.

Odgovor na primljeni e-mail ili prigovor poslat običnim spoljnim e-mailom **ne** predstavlja pravilno podnesen prigovor. Obični e-mail **nije** važeći kanal za podnošenje prigovora.

Prigovor može osporiti samo aktivirane kriterijume: jedan, više ili sve aktivirane. **Ne** smije osporavati kriterijum koji nije aktiviran. Za svaki osporeni kriterijum podnosilac unosi obrazloženje.

Prigovor **može** ukazati da je relevantna činjenica ili dokument već postojao u blagovremeno podnesenoj prijavi. **Ne** omogućava:

* izmjenu M1a/M1b ili M2;
* izmjenu podataka prijave;
* dodavanje novog dokumenta;
* zamjenu ili uklanjanje postojećeg priloga;
* naknadnu dopunu prijave;
* otključavanje prijave.

## 14.3. Odlučivanje Komisije

Komisija provjerava sadržaj koji je postojao **prije isteka roka** Poziva.

O prigovoru učestvuju i odlučuju **sva tri člana** (`BM-ML-003`). Komisija odlučuje o **svakom osporenom kriterijumu odvojeno**.

Za svaki osporeni kriterijum evidentira se najmanje `Prihvaćen` ili `Odbijen`, obrazloženje, datum i vrijeme i članovi koji su odlučivali. Ne uvodi se automatska odluka Platforme.

Ako je prigovor djelimično prihvaćen, svaki kriterijum zadržava svoj pojedinačni ishod.

Komisija odlučuje u roku od sedam dana od prijema (`BM-ML-036`).

Druga sjednica **ne** održava se dok postoji bilo koji blagovremen neriješen prigovor (`BM-ML-034`). Svi blagovremeni prigovori moraju biti riješeni prije druge sjednice. Platforma **ne** produžava automatski rok druge sjednice. Ovaj dokument **ne** uvodi pravilo za slučaj u kojem se propisani rokovi objektivno ne mogu istovremeno ispuniti. Platforma **ne** zakazuje sjednice automatski.

## 14.4. Ishod i obavještenje

Podnosilac dobija obavještenje o ishodu na **registrovanu e-mail adresu** na digitalnom servisu, istim kanalom obavještavanja. Ne uvodi se širi e-mail lifecycle, status isporuke ni automatsko ponovno slanje. Neuspjela isporuka **ne** mijenja automatski ishod ni rok.

* Ako nijedan eliminatorni razlog više nije konačno aktivan → prijava ostaje `submitted` i nastavlja postupak ocjenjivanja.
* Ako makar jedan razlog ostane konačno aktivan → prijava prelazi u `rejected`; čuvaju se svi konačni aktivni razlozi; odbijanje se ne svodi na jednu slobodnu opštu napomenu.

Ako rok od tri dana istekne bez prigovora, svi prethodno aktivirani kriterijumi postaju konačni i prijava prelazi u `rejected` uz sve aktivne razloge.

Završeni ishod **ne** može se ponovo otvoriti (`BM-ML-037`). Predsjednik i Administrator **ne** mogu produžiti ili ponovo otvoriti rok. Isti prigovorni ciklus se **ne** ponavlja. Ne uvodi se prelaz `rejected` → `submitted`.

Prigovor ostaje odvojen objekat. Stanja prigovora ostaju `Podnesen`, `Prihvaćen` i `Odbijen`.

## 14.5. Funkcionalni prelazi — obavještenja i prigovori

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Obavještenje | nijedan kriterijum nije aktiviran | slanje | M3 bez aktivnog razloga | prigovorni tok se ne otvara; obavještenje se ne šalje | sistem | `BM-ML-036` |
| Obavještenje | najmanje jedan aktiviran kriterijum | slanje | evidentiranje u M3 | jedno objedinjeno obavještenje na registrovanu e-mail adresu; svaki aktivirani razlog posebno; rok 3 dana od evidentiranog slanja; nije javno | sistem | `BM-ML-036` |
| Prigovor | — | podnošenje | najmanje jedan aktiviran razlog; rok 3 dana od slanja; samo funkcija digitalnog servisa; obrazloženje po osporenom kriterijumu | Podnesen; prijava ostaje zaključana; neaktivirani se ne mogu osporavati | podnosilac | `BM-ML-036`; `BM-ML-023` |
| Prigovor | — | pokušaj podnošenja odgovorom na e-mail ili običnim spoljnim e-mailom | — | nije pravilno podnesen prigovor | podnosilac | `BM-ML-036` |
| Prigovor | Podnesen | odluka | sva tri člana; odluka po svakom osporenom kriterijumu; provjera sadržaja koji je postojao prije isteka roka | pojedinačni ishodi; ako nijedan razlog nije konačno aktivan → `submitted`; ako makar jedan ostane → `rejected` uz sve konačne aktivne razloge | Komisija / predsjednik | `BM-ML-003`; `BM-ML-036`; `BM-ML-037` |
| Prigovor | Prihvaćen ili Odbijen | ponovno otvaranje ili produženje roka | — | zabranjeno; nema `rejected` → `submitted`; isti ciklus se ne ponavlja | predsjednik / Administrator / svi | `BM-ML-037` |
| Admin. rezultat / M3 | najmanje jedan aktiviran kriterijum | istek 3 dana bez prigovora | obavještenje poslato | svi prethodno aktivirani kriterijumi konačni; prijava `rejected` uz sve aktivne razloge | sistem | `BM-ML-036` |
| Druga sjednica | — | pokušaj održavanja | postoji bilo koji blagovremen neriješen prigovor | nije dozvoljeno | Komisija | `BM-ML-034` |

## 14.6. Prihvatni kriterijumi — prigovor

### 14.6.1 — Obavještenje na registrovanu e-mail adresu

**Ako:** je aktiviran najmanje jedan eliminatorni kriterijum.

**Kada:** se šalje obavještenje.

**Onda:** Platforma šalje jedno objedinjeno obavještenje na registrovanu e-mail adresu podnosioca, sa svakim aktiviranim razlogom, obrazloženjem, pravom na prigovor, rokom od tri dana i napomenom da se prigovor podnosi kroz digitalni servis. Obavještenje nije javno. Administrator razloge **ne** vidi. Ne uvodi se status isporuke ni automatsko ponovno slanje. Neuspjela isporuka **ne** mijenja automatski odluku Komisije ni rok.

Izvor: `BM-ML-036`; `BM-ML-004`.

### 14.6.2 — Rok od slanja, ne od otvaranja

**Ako:** je obavještenje evidentirano kao poslato.

**Kada:** podnosilac kasnije prvi put otvori ili pročita poruku.

**Onda:** rok od tri dana **ne** pomjera se zbog trenutka otvaranja ni čitanja.

Izvor: `BM-ML-036`.

### 14.6.3 — Prigovor samo preko digitalnog servisa

**Ako:** podnosilac pokuša podnijeti prigovor odgovorom na primljeni e-mail ili običnim spoljnim e-mailom.

**Kada:** sistem prima samo podnošenje preko funkcije digitalnog servisa.

**Onda:** obični e-mail **nije** važeći kanal za podnošenje prigovora. Odgovor na primljeni e-mail **nije** pravilno podnesen prigovor. Prigovor preko funkcije digitalnog servisa zahtijeva obrazloženje za svaki osporeni kriterijum.

Izvor: `BM-ML-036`.

### 14.6.4 — Nema novog dokumenta

**Ako:** prijava je `Podnesena` i podnosi se prigovor.

**Kada:** podnosilac pokuša dodati, zamijeniti ili dopuniti prilog.

**Onda:** Platforma blokira izmjenu. Prigovor ne omogućava izmjenu M1a/M1b ili M2, izmjenu podataka prijave, dodavanje novog dokumenta, zamjenu ili uklanjanje postojećeg priloga ni otključavanje prijave. Prigovor može samo ukazati na činjenicu ili dokument koji je već postojao prije isteka roka.

Izvor: `BM-ML-036`; `BM-ML-023`.

### 14.6.5 — Provjera postojanja dokumenta

**Ako:** prigovor ukazuje na sporni dokument.

**Kada:** Komisija odlučuje.

**Onda:** Komisija provjerava sadržaj koji je postojao u podnesenoj prijavi prije isteka roka. U odlučivanju učestvuju sva tri člana. Odluka se evidentira po svakom osporenom kriterijumu, sa obrazloženjem, datumom, vremenom i članovima koji su odlučivali.

Izvor: `BM-ML-036`; `BM-ML-003`.

### 14.6.6 — Ishod Prihvaćen ili Odbijen

**Ako:** Komisija odluči o prigovoru.

**Kada:** ishodi po kriterijumima se evidentiraju.

**Onda:** ako nijedan razlog nije konačno aktivan, prijava ostaje `submitted` i nastavlja. Ako makar jedan razlog ostane konačno aktivan, prijava prelazi u `rejected` uz sve konačne aktivne razloge i ne ide u ocjenjivanje. Podnosilac dobija obavještenje na registrovanu e-mail adresu na digitalnom servisu.

Izvor: `BM-ML-036`.

### 14.6.7 — Istek bez prigovora

**Ako:** su prošla tri dana od slanja obavještenja i prigovor nije podnesen.

**Kada:** rok istekne.

**Onda:** svi prethodno aktivirani kriterijumi postaju konačni. Prijava prelazi u `rejected` uz sve aktivne razloge i ne razmatra se dalje.

Izvor: `BM-ML-036`.

### 14.6.8 — Zabrana ponovnog otvaranja

**Ako:** je prigovor `Prihvaćen` ili `Odbijen`.

**Kada:** predsjednik ili Administrator pokuša ponovo otvoriti prigovor ili produžiti rok.

**Onda:** Platforma blokira radnju.

Izvor: `BM-ML-037`.

### 14.6.9 — Druga sjednica čeka prigovor

**Ako:** postoji bilo koji blagovremen neriješen prigovor.

**Kada:** se pokuša održati druga sjednica.

**Onda:** druga sjednica se ne održava dok Komisija ne odluči o tom prigovoru.

Izvor: `BM-ML-034`.

### 14.6.10 — Prigovor nije status prijave

**Ako:** postoji prigovor.

**Kada:** Platforma određuje status prijave.

**Onda:** prigovor ostaje odvojen objekat. Tokom otvorenog prava na prigovor prijava ostaje `submitted`. `rejected` nastaje tek kada je makar jedan razlog konačno aktivan. Nema prelaza `rejected` → `submitted`.

Izvor: `BM-ML-020`; `BM-ML-037`.

### 14.6.11 — Jedno objedinjeno obavještenje

**Ako:** je aktivirano više eliminatornih kriterijuma.

**Kada:** Platforma priprema obavještenje.

**Onda:** šalje se jedno objedinjeno obavještenje. Svaki aktivirani razlog naveden je posebno. Ne otvara se poseban prigovorni tok po kriterijumu.

Izvor: `BM-ML-036`.

### 14.6.12 — Osporavanje samo aktiviranih razloga

**Ako:** podnosilac podnosi prigovor.

**Kada:** bira kriterijume koje osporava.

**Onda:** može osporiti jedan, više ili sve aktivirane kriterijume. Platforma **ne** omogućava osporavanje kriterijuma koji nije aktiviran.

Izvor: `BM-ML-036`.

### 14.6.13 — Odluka po kriterijumu

**Ako:** Komisija odlučuje o prigovoru.

**Kada:** evidentira ishod.

**Onda:** za svaki osporeni kriterijum evidentira se `Prihvaćen` ili `Odbijen`, obrazloženje, datum i vrijeme i članovi koji su odlučivali. Ne uvodi se automatska odluka Platforme.

Izvor: `BM-ML-037`; `BM-ML-003`.

### 14.6.14 — Djelimično prihvatanje

**Ako:** je prigovor prihvaćen samo za neke osporene kriterijume.

**Kada:** se utvrđuje konačni ishod prijave.

**Onda:** svaki kriterijum zadržava svoj pojedinačni ishod. Ako nijedan razlog nije konačno aktivan, prijava ostaje `submitted`. Ako makar jedan razlog ostane konačno aktivan, prijava prelazi u `rejected` uz sve konačne aktivne razloge.

Izvor: `BM-ML-036`; `BM-ML-037`.

---

# 15. Usmeno obrazloženje

Status poglavlja: USVOJENO

Ovo poglavlje određuje zakazivanje, uslove održavanja i evidenciju usmenog obrazloženja (`BM-ML-003`; `BM-ML-034`; `BM-ML-038`).

Obrazac ženskog profila iz `KN-FS-003` Poglavlja 12 koristi se samo gdje nije suprotan pravilima mladih. `KN-FS-003` usmeno obrazloženje vodi van Platforme i **ne** uvodi zakazivanje, evidenciju prisustva, nedolazak, zapisnik ni tehničku kapiju kriterijuma 10. Za mlade Platforma **evidentira** termin, održavanje i završetak, jer `BM-ML-003` i `BM-ML-039` zahtijevaju da se konačno ocjenjivanje završi tek nakon usmenog obrazloženja.

Ne uvodi se videokonferencijska integracija, kalendarska integracija, SMS ni drugi kanal koji nije odobren. Ne uvodi se javni tok službenih akata. Platforma **ne** vodi sjednicu kao poseban poslovni objekat (`BM-ML-034`).

## 15.1. Zakazivanje

Predsjednik Komisije, za prijave za koje nijedan eliminatorni razlog nije konačno aktivan, na Platformi:

* određuje datum i vrijeme usmenog obrazloženja;
* evidentira mjesto ili način održavanja kao poslovni podatak, bez tehničke integracije;
* potvrđuje poziv podnosiocu.

Nakon potvrde poziva Platforma šalje obavještenje na **registrovanu e-mail adresu** podnosioca na digitalnom servisu, prema usvojenom toku Poglavlja 14. Ne uvodi se širi e-mail lifecycle, status isporuke ni automatsko ponovno slanje.

Promjena termina **mora** imati evidentiran razlog. Čuvaju se:

* prvobitni termin;
* novi termin;
* odgovorno lice;
* datum i vrijeme promjene.

Platforma **ne** mijenja termin automatski i **ne** određuje termin umjesto Komisije (`BM-ML-034`).

Druga sjednica i usmena obrazloženja poslovno se zakazuju najkasnije sedam dana od održavanja prve sjednice (`BM-ML-034`). Druga sjednica **ne** održava se dok postoji blagovremen neriješen prigovor.

## 15.2. Uslovi održavanja

Za usmeno obrazloženje potrebna su **sva tri člana** Komisije (`BM-ML-003`). Predsjednik je **jedan od ta tri člana**.

Ako nijesu prisutna sva tri člana, usmeno obrazloženje **ne** može se evidentirati kao pravilno održano.

Zamjenski član sa važećim ovlašćenjem može učestvovati umjesto člana kojeg mijenja (`BM-ML-007`).

Podnosilac ili njegovo ovlašćeno lice prisustvuje obrazloženju.

Usmeno obrazloženje **nije** izmjena ni dopuna zaključane prijave.

## 15.3. Evidentiranje održavanja

Predsjednik evidentira:

* da je usmeno obrazloženje održano;
* stvarni datum i vrijeme;
* mjesto ili način održavanja;
* prisutna tri člana Komisije;
* podnosioca ili ovlašćenog predstavnika;
* eventualnu službenu napomenu;
* završetak usmenog obrazloženja.

Tek nakon **evidentiranog završetka** može se završiti konačno individualno ocjenjivanje svih deset kriterijuma. Detalj: Poglavlje 16.

Nacrti ocjena mogu se unositi **ranije**. Platforma **ne** zaključava unos nacrta prije usmenog obrazloženja (`BM-ML-039`).

## 15.4. Nedolazak podnosioca

`KN-FS-003` Poglavlje 12 **ne** uređuje nedolazak, ne evidentira ga na Platformi i **ne** određuje posljedicu.

Ovaj dokument **ne** izmišlja dodatne posljedice. Nedolazak **ne** znači:

* automatsku eliminaciju prijave;
* automatsku ocjenu kriterijuma 10;
* automatsku zabranu novog termina;
* novo osnovno stanje prijave.

Predsjednik može evidentirati nedolazak. Evidentira se:

* ko bilježi nedolazak;
* stvarni termin;
* razlog, **samo ako je poznat**.

Novi termin **nije** automatska posljedica nedolaska. Ženski profil u `KN-FS-003` taj tok **ne uređuje**. Odsustvo pravila u ženskom profilu **nije** zabrana. Za mlade se **ne** izmišlja posljedica nedolaska niti automatski novi termin bez posebnog pravila. Eventualna izmjena termina ostaje samo opšta radnja iz §15.1, sa evidentiranim razlogom i istorijom.

## 15.5. Zapisnik ili službena bilješka

`KN-FS-003` **ne** uvodi zapisnik usmenog obrazloženja, evidenciju pitanja i odgovora, prilaganje zapisnika ni posebno potvrđivanje i zaključavanje zapisnika.

Ovaj dokument **ne** proširuje taj obim. Poseban tok zapisnika, prilog zapisnika i evidencija pitanja i odgovora **nijesu** uvedeni.

Jedini dozvoljeni tekstualni unos u tom obimu je **službena napomena** iz §15.3. Povezana je sa konkretnom prijavom. Čuva se revizijski trag.

Službena napomena **ne** omogućava:

* dopunu prijave;
* izmjenu M1a/M1b ili M2;
* dodavanje novih dokaza;
* zamjenu priloga;
* promjenu podnesenog biznis plana.

## 15.6. Funkcionalni prelazi — usmeno obrazloženje

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Termin | Prijava Potpuna | zakazivanje | predsjednik; datum, vrijeme, mjesto ili način | termin evidentiran; poziv potvrđen | predsjednik | `BM-ML-034` |
| Obavještenje | poziv potvrđen | slanje | registrovana e-mail adresa | obavještenje poslato; bez lifecycle-a | sistem | Poglavlje 14; `BM-ML-036` |
| Termin | postoji | izmjena | evidentiran razlog | novi termin; čuvaju se stari, novi, lice i vrijeme; nije automatski | predsjednik | `BM-ML-034` |
| Usmeno | zakazano | evidencija održavanja | nijesu prisutna sva tri člana | nije pravilno održano | sistem | `BM-ML-003` |
| Usmeno | zakazano | evidencija održavanja | prisutna sva tri člana; podnosilac ili ovlašćeno lice | održano; evidentiran završetak | predsjednik | `BM-ML-003`; `BM-ML-038` |
| Usmeno | zakazano | nedolazak | predsjednik bilježi | evidentiran nedolazak; nema eliminacije ni automatske ocjene | predsjednik | `KN-FS-003` §12 granica |
| Individualna ocjena | nacrt | Završi ocjenjivanje | usmeno nije evidentirano kao završeno | blokirano | član | `BM-ML-039` |

## 15.7. Prihvatni kriterijumi — usmeno obrazloženje

### 15.7.1 — Zakazivanje i obavještenje

**Ako:** prijava ima rezultat `Potpuna`.

**Kada:** predsjednik odredi termin i potvrdi poziv.

**Onda:** Platforma čuva datum, vrijeme i mjesto ili način, i šalje obavještenje na registrovanu e-mail adresu. Ne uvodi se videokonferencija, kalendar ni SMS.

Izvor: `BM-ML-034`.

### 15.7.2 — Promjena termina

**Ako:** postoji zakazani termin.

**Kada:** predsjednik ga izmijeni.

**Onda:** izmjena zahtijeva razlog. Čuvaju se prvobitni i novi termin, lice i vrijeme. Platforma **ne** mijenja termin automatski.

Izvor: `BM-ML-034`.

### 15.7.3 — Sva tri člana

**Ako:** nijesu prisutna sva tri člana, uključujući važećeg zamjenskog člana na mjestu odsutnog.

**Kada:** se pokuša evidentirati pravilno održano usmeno obrazloženje.

**Onda:** Platforma blokira tu evidenciju.

Izvor: `BM-ML-003`; `BM-ML-007`.

### 15.7.4 — Završetak prije konačnog ocjenjivanja

**Ako:** usmeno obrazloženje nije evidentirano kao završeno.

**Kada:** član pokuša `Završi ocjenjivanje`.

**Onda:** Platforma blokira završavanje. Unos nacrta ostaje dozvoljen.

Izvor: `BM-ML-039`.

### 15.7.5 — Nedolazak bez izmišljene posljedice

**Ako:** podnosilac ne dođe.

**Kada:** predsjednik evidentira nedolazak.

**Onda:** čuvaju se lice koje bilježi, stvarni termin i razlog ako je poznat. Prijava **ne** biva eliminisana, kriterijum 10 **ne** dobija automatsku ocjenu, a novi termin **nije** automatski.

Izvor: granica `KN-FS-003` §12; `BM-ML-038`.

### 15.7.6 — Nema proširenog zapisnika

**Ako:** se unosi službena napomena uz evidenciju održavanja.

**Kada:** korisnik pokuša dopuniti prijavu, M1a/M1b, M2 ili priloge.

**Onda:** Platforma blokira izmjenu zaključane prijave. Poseban zapisnik, pitanja i odgovori i prilog zapisnika **nijesu** uvedeni.

Izvor: `BM-ML-023`; granica `KN-FS-003` §12.3.

---

# 16. Individualno ocjenjivanje

Status poglavlja: USVOJENO

Ovo poglavlje određuje individualno ocjenjivanje, nacrt, završavanje, zamjenu člana, tajnost i prosjek (`BM-ML-003`; `BM-ML-007`; `BM-ML-038`–`BM-ML-041`; O-01).

Strukturni obrazac nacrta, eksplicitnog završavanja, tajnosti i opcione napomene usklađen je sa `KN-FS-003` Poglavljem 13, uz **tačno tri člana** i pravila mladih. Ne preuzima se pet članova. Ne preuzima se odsustvo kapije za završavanje: za mlade `Završi ocjenjivanje` zahtijeva evidentiran završetak usmenog obrazloženja.

Ne određuje eliminatorne kriterijume, prag, rang-listu ni raspodjelu. To pripada Poglavljima 18–21.

## 16.1. Kriterijumi i skala

Svaki od tri člana samostalno ocjenjuje svih deset kriterijuma. Predsjednik učestvuje kao **jedan od tri** ocjenjivača, istom težinom.

Ocjena po kriterijumu je **cijeli broj od 1 do 5** (`BM-ML-038`):

* **1** — uopšte ne odgovara navedenom;
* **5** — u potpunosti odgovara navedenom;
* **2**, **3** i **4** su međuvrijednosti. Posebni tekstualni opisi za njih se **ne** uvode.

Svih deset kriterijuma mora biti prikazano. Kriterijumi se preuzimaju vjerno iz `BM-ML-038`:

1. Obrazac biznis plana je detaljno popunjen sa svim neophodnim informacijama i jasno su precizirani proizvodi/usluge koje će se ponuditi na tržištu.
2. Jasno su identifikovani potencijalni kupci i njihove karakteristike.
3. Biznis plan će omogućiti samozapošljavanje i/ili zapošljavanje (stalno ili sezonsko) lica sa teritorije opštine Kotor.
4. Prepoznata je i navedena konkurencija kao i slabosti i snage iste.
5. Jasno su navedeni potrebni resursi i identifikovani dobavljači.
6. Biznis ideja je ....................... finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja).
7. Podaci o preduzetniku (fizičko lice/preduzetnik posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti).
8. Preduzetnik planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.
9. Razvijena matrica rizika je jasna i logična.
10. Usmeno obrazloženje biznis plana (preduzetnik je uvjerljivi siguran u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovora na sva pitanja).

Kriterijum 1 ostaje jedan kriterijum. Numeracija se **ne** mijenja. Izvorni zapis kriterijuma 6 prenosi se vjerno, uključujući niz tačaka. Nedostajuća riječ se **ne** dopunjava.

**Ne** uvode se ponderi. Opciona napomena **ne** donosi bodove.

U ocjenjivanje ulaze samo prijave za koje nijedan eliminatorni razlog nije konačno aktivan i za koje ne traje rok ni neriješen blagovremen prigovor.

## 16.2. Nacrt

Dok član **nije** odabrao `Završi ocjenjivanje`, njegovo ocjenjivanje ostaje **nacrt**.

Član Komisije:

* vidi samo svoje ocjene i napomene;
* može unositi i mijenjati nacrt;
* može sačuvati djelimično popunjene ocjene;
* može unositi nacrt **prije** usmenog obrazloženja;
* **ne** može završiti ocjenjivanje dok usmeno obrazloženje te prijave nije evidentirano kao završeno;
* **ne** može završiti dok nije unio svih deset ocjena;
* prije završavanja dobija jasno upozorenje da će ocjene biti zaključane.

Platforma **ne** zaključava unos nacrta prije usmenog obrazloženja i **ne** uvodi posebno otključavanje kriterijuma 10 (`BM-ML-039`).

Predsjednik **nema** privilegovan uvid u ocjene drugih članova tokom ciklusa. Administrator i podnosilac **nemaju** pristup individualnim ocjenama.

## 16.3. Završavanje

Radnja `Završi ocjenjivanje`:

* zahtijeva svih deset ocjena;
* zahtijeva evidentirano završeno usmeno obrazloženje te prijave;
* zahtijeva izričitu potvrdu člana;
* zaključava ocjene i napomenu;
* **ne** dozvoljava izmjenu ni vraćanje u nacrt;
* evidentira člana, datum i vrijeme završavanja.

Član može odustati od potvrde i vratiti se na nacrt.

Prijava **nema** kompletno završeno ocjenjivanje dok ne postoje **tačno tri** kompletne zaključane ocjene (`BM-ML-003`).

Ne uvodi se automatsko završavanje samo zato što su polja popunjena.

## 16.4. Zamjena člana

Primjenjuje se riješeni O-01 i `BM-ML-039`.

Za jednu prijavu i jedno mjesto Komisije svih deset kriterijuma daje **jedno lice**: redovni član ili formalno imenovani zamjenski član.

Djelimične ocjene dva lica **ne** smiju se spajati. Ne smije postojati četvrta ocjena.

Ako prethodni član **nije** završio i zaključao kompletnu ocjenu te prijave:

* nezavršeni nacrt ostaje u revizijskom tragu i **ne** ulazi u obračun;
* zamjenski član **ne** nastavlja taj nacrt i **ne** vidi ga;
* zamjenski član samostalno ocjenjuje svih deset kriterijuma.

Ako je prethodni član **završio i zaključao** kompletnu ocjenu te prijave:

* zaključana ocjena ostaje važeća;
* zamjenski član je **ne** ponavlja.

Ako je prethodni član završio neke prijave, a druge nije:

* završene ocjene ostaju;
* zamjenski član kompletno ocjenjuje **samo** nezavršene prijave.

Identiteti, zamjena, nacrti i izvršene radnje ostaju u revizijskom tragu (`BM-ML-007`).

## 16.5. Tajnost i međusobni uvid

Dok ciklus traje, svaki član vidi **samo** svoje ocjene i napomene. Predsjednik **ne** vidi tuđe ocjene. Administrator i podnosilac ih **ne** vide (`BM-ML-040`).

Međusobni uvid otvara se tek kada **sva tri člana** završe ocjenjivanje **svih** prijava u ciklusu. Završetak jedne prijave ili rada samo jednog člana **ne** otvara uvid.

Uvid je **samo za čitanje**. **Ne** omogućava izmjenu zaključenih ocjena ni napomena.

Nakon otvaranja, u skladu sa `KN-FS-003` §13.5–§13.6 i `BM-ML-040`, članovi Komisije vide konačne individualne ocjene drugih članova i njihove opcione napomene. **Ne** uvodi se širi zajednički prikaz privatnih napomena koje ženski profil ne prikazuje. Napomene po pojedinačnom kriterijumu se **ne** uvode.

## 16.6. Obračun

Za svaki kriterijum:

`prosjek kriterijuma = zbir tri zaključane ocjene / 3`

Platforma:

* koristi punu nezaokruženu vrijednost za dalje računanje;
* prikazuje vrijednost na dvije decimale;
* sabira prosjeke svih deset kriterijuma;
* **ne** računa konačan rezultat ako nedostaje kompletna ocjena jednog člana;
* za prag, rangiranje i izjednačenje koristi punu nezaokruženu vrijednost (`BM-ML-041`).

Prikazano zaokruživanje **ne** smije promijeniti prag ili rang. Način tehničkog čuvanja preciznosti pripada TS-u.

Detalj praga i rangiranja: Poglavlja 19 i 20.

## 16.7. Funkcionalni prelazi — ocjenjivanje

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Individualna ocjena | — | unos nacrta | prijava ide u ocjenjivanje; važeći član | nacrt; dozvoljen i prije usmenog | član ili zamjenski član | `BM-ML-039` |
| Individualna ocjena | nacrt | Završi ocjenjivanje | nema svih 10 ocjena ili usmeno nije završeno | blokirano | član | `BM-ML-039` |
| Individualna ocjena | nacrt | Završi ocjenjivanje | svih 10 ocjena; usmeno završeno; izričita potvrda | zaključana; evidentirani član, datum i vrijeme | isto lice | `BM-ML-039` |
| Individualna ocjena | zaključana | izmjena ili povratak u nacrt | — | zabranjeno | svi | `BM-ML-039` |
| Mjesto Komisije | zamjena; prethodni nacrt nije zaključen | ocjenjivanje zamjenskog člana | O-01 | nacrt prethodnog van obračuna; zamjena ocjenjuje svih 10 | zamjenski član | `BM-ML-007`; `BM-ML-039` |
| Mjesto Komisije | zamjena; prethodna ocjena zaključana | ocjenjivanje zamjenskog člana | O-01 | prethodna ocjena ostaje; ne ponavlja se | zamjenski član | `BM-ML-039` |
| Ciklus | nije završen | treći član zaključi sve potrebne ocjene | tri kompletne ocjene po prijavi u ciklusu | ciklus završen; međusobni uvid samo za čitanje | sistem | `BM-ML-003`; `BM-ML-040` |
| Prosjek | tri zaključane ocjene | obračun | — | puna vrijednost za dalji račun; prikaz na 2 decimale | sistem | `BM-ML-041` |
| Prijava | `submitted` | treća kompletna ocjena i obračun | tačno tri kompletne zaključane ocjene konkretne prijave | `evaluated`; individualni nacrti ostaju odvojeni | sistem | `BM-ML-039`; `BM-ML-044` |
| Prosjek | nedostaje kompletna ocjena | obračun konačnog rezultata | — | nije izračunat; status ostaje `submitted` | sistem | `BM-ML-003`; `BM-ML-041` |

## 16.8. Prihvatni kriterijumi — ocjenjivanje

### 16.8.1 — Skala i deset kriterijuma

**Ako:** član unosi ocjenu.

**Kada:** popunjava kriterijume.

**Onda:** prikazuju se svih deset kriterijuma iz `BM-ML-038`. Svaka ocjena je cijeli broj od 1 do 5. Ponderi se ne primjenjuju.

Izvor: `BM-ML-038`.

### 16.8.2 — Nacrt prije usmenog obrazloženja

**Ako:** usmeno obrazloženje još nije evidentirano kao završeno.

**Kada:** član unosi ocjene.

**Onda:** Platforma dozvoljava nacrt, uključujući kriterijum 10. `Završi ocjenjivanje` ostaje blokiran.

Izvor: `BM-ML-039`.

### 16.8.3 — Završavanje

**Ako:** su unesene sve ocjene, usmeno je završeno i član potvrdi upozorenje.

**Kada:** izvrši `Završi ocjenjivanje`.

**Onda:** ocjene i napomena se zaključavaju. Izmjena i povratak u nacrt nijesu dozvoljeni. Evidentiraju se član, datum i vrijeme.

Izvor: `BM-ML-039`.

### 16.8.4 — Tačno tri kompletne ocjene

**Ako:** nedostaje zaključana ocjena jednog mjesta Komisije.

**Kada:** se ocjenjuje kompletnost prijave.

**Onda:** ocjenjivanje te prijave **nije** kompletno. Prosjek se **ne** računa. Četvrta ocjena se **ne** uvodi.

Izvor: `BM-ML-003`; `BM-ML-039`.

### 16.8.5 — Zamjena bez spajanja

**Ako:** je član zamijenjen prije zaključavanja ocjene konkretne prijave.

**Kada:** zamjenski član ocjenjuje.

**Onda:** nacrt prethodnog člana ne ulazi u obračun i nije mu vidljiv. Zamjenski član ocjenjuje svih deset kriterijuma. Ako je prethodna ocjena već zaključana, ostaje važeća i ne ponavlja se.

Izvor: `BM-ML-007`; `BM-ML-039`; O-01.

### 16.8.6 — Tajnost tokom ciklusa

**Ako:** ciklus nije završen.

**Kada:** predsjednik, drugi član, Administrator ili podnosilac pokuša vidjeti tuđe ocjene.

**Onda:** Platforma **ne** prikazuje tuđe ocjene ni napomene.

Izvor: `BM-ML-040`.

### 16.8.7 — Međusobni uvid samo za čitanje

**Ako:** sva tri člana završe ocjenjivanje svih prijava u ciklusu.

**Kada:** se otvori međusobni uvid.

**Onda:** članovi vide tuđe zaključane ocjene i opcione napomene, samo za čitanje. Izmjena zaključenih ocjena ostaje zabranjena.

Izvor: `BM-ML-040`.

### 16.8.8 — Prikaz ne mijenja prag ni rang

**Ako:** se prikazuje prosjek na dvije decimale.

**Kada:** se provjerava prag, rang ili izjednačenje.

**Onda:** koristi se puna nezaokružena vrijednost. Prikaz **ne** smije promijeniti ishod.

Izvor: `BM-ML-041`.

---

# 17. Dodatni bodovi

Status poglavlja: USVOJENO

Ovo poglavlje određuje dodatne bodove profila mladih (`BM-ML-042`).

Funkcionalni obrazac da predsjednik evidentira osnov, da Platforma **ne** odlučuje samostalno o činjenici i da se bodovi **ne** tretiraju kao jedanaesti kriterijum usklađen je sa `KN-FS-003` §14.3. Koriste se **isključivo** bodovi mladih. **Ne** uvodi se bod Zavoda za zapošljavanje. Maksimum je **6**, ne 8.

Dodatni bodovi ulaze u konačni rezultat tek nakon osnovnog zbira prosječnih ocjena (`BM-ML-044`). Detalj praga i rangiranja: Poglavlja 19 i 20.

## 17.1. Vrste i maksimum

Vrste dodatnih bodova:

1. **+1** — potvrđeno prisustvo Info danu **i** obuci za pisanje biznis plana;
2. **+2** — fizičko lice koje tek planira registraciju biznisa;
3. **+3** — inovativna i/ili zelena biznis ideja.

Ukupan maksimum je **6**. Bodovi su kumulativni unutar tog limita.

Dodatni bodovi **nijesu** individualno ocjenjivanje člana Komisije.

## 17.2. Prisustvo Info danu i obuci

Jedno prisustvo **nije** dovoljno. Oba prisustva moraju biti potvrđena (`BM-ML-042`).

* prisustvo Info danu potvrđuje organizator, odnosno član Komisije koji ga održava;
* prisustvo obuci potvrđuje organizator, odnosno član Komisije koji je održava;
* bod se evidentira tek kada postoje **obje** potvrde;
* podnosilac **ne** potvrđuje prisustvo sopstvenom izjavom.

`KN-FS-003` ne razrađuje poseban tok dvije potvrde; predsjednik tamo samo evidentira ostvareni osnov. Za mlade se zadržava to načelo predsjednikovog evidentiranja boda, uz obavezne dvije potvrde iz `BM-ML-042`.

Info dan bez obuke ili obuka bez Info dana **ne** daje taj bod.

## 17.3. Fizičko lice koje planira registraciju

Platforma koristi **već utvrđenu i potvrđenu kategoriju** prijave (F-06; `BM-ML-009`). Bod **ne** zavisi od ručnog proizvoljnog izbora. **Ne** uvodi se nova provjera CRPS-a.

Ako kategorija **nije** pouzdano utvrđena, bod se **ne** dodjeljuje automatski.

## 17.4. Inovativna i/ili zelena ideja

To je **jedna** kategorija od najviše **tri** boda. Inovativna i zelena zajedno **ne** daju šest bodova (`BM-ML-042`).

Komisija utvrđuje ispunjenost. Platforma **ne** odlučuje automatski da je ideja inovativna ili zelena.

Značenje se prenosi iz `BM-ML-042` i člana 20 Odluke, bez novog kriterijuma ocjenjivanja.

## 17.5. Evidentiranje i zaključavanje

Predsjednik evidentira konačne dodatne bodove na osnovu potvrđenih činjenica i odluke Komisije, u ime Komisije.

Predsjednik može unositi osnov čim raspolaže potrebnim podacima. **Ne** mora čekati završetak ciklusa ocjenjivanja. Rano evidentiranje **ne** smije otkriti tajne individualne ocjene.

Platforma čuva najmanje:

* vrstu dodatnog boda;
* dodijeljeni broj bodova;
* osnov evidentiranja;
* odgovorno lice;
* datum i vrijeme.

Prije potvrde Platforma provjerava:

* dozvoljene vrijednosti (+1, +2, +3 prema vrsti);
* da zbir **ne** prelazi 6;
* da Info dan bez obuke ili obuka bez Info dana **ne** daje +1.

Nakon izričite potvrde predsjednika dodatni bodovi se **zaključavaju**. `KN-FS-003` §14.3 dozvoljava ispravku osnova dok preliminarna rang-lista nije formirana; konačna zaključanost tamo nastaje sa konačnom rang-listom. Ovaj dokument **ne** uvodi ponovno otvaranje zaključanih dodatnih bodova bez posebnog odobrenog pravila.

Dodatni bodovi ulaze u konačni rezultat tek nakon osnovnog zbira prosječnih ocjena.

## 17.6. Funkcionalni prelazi — dodatni bodovi

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| +1 | nema obje potvrde | pokušaj dodjele | samo Info dan ili samo obuka | bod se ne dodjeljuje | sistem | `BM-ML-042` |
| +1 | obje potvrde organizatora | evidentiranje | predsjednik | +1 evidentiran | predsjednik | `BM-ML-042` |
| +2 | kategorija utvrđena | evidentiranje | fizičko lice koje planira registraciju | +2 | predsjednik / sistem provjera kategorije | `BM-ML-042`; F-06 |
| +2 | kategorija nije pouzdana | automatska dodjela | — | zabranjeno | sistem | F-06; `BM-ML-042` |
| +3 | odluka Komisije | evidentiranje | jedna kategorija inovativna i/ili zelena | najviše +3, ne +6 | predsjednik | `BM-ML-042` |
| Zbir | pred potvrdu | provjera | zbir > 6 ili nedozvoljena vrijednost | potvrda blokirana | sistem | `BM-ML-042` |
| Dodatni bodovi | uneseni | potvrda predsjednika | provjere prošle | zaključani; nema ponovnog otvaranja | predsjednik | `BM-ML-042` |
| Konačni rezultat | zbir prosjeka | primjena dodatnih bodova | dodatni bodovi evidentirani | zbir prosjeka + dodatni bodovi | sistem | `BM-ML-044` |

## 17.7. Prihvatni kriterijumi — dodatni bodovi

### 17.7.1 — Maksimum 6

**Ako:** se sabiraju dodatni bodovi.

**Kada:** predsjednik potvrđuje zbir.

**Onda:** zbir **ne** može preći 6. Bod Zavoda za zapošljavanje **nije** dostupan.

Izvor: `BM-ML-042`.

### 17.7.2 — Info dan i obuka

**Ako:** postoji samo jedna od dvije potvrde.

**Kada:** se pokuša dodijeliti +1.

**Onda:** Platforma **ne** dodjeljuje bod. Podnosilac **ne** može potvrditi prisustvo sopstvenom izjavom.

Izvor: `BM-ML-042`.

### 17.7.3 — Kategorija za +2

**Ako:** kategorija prijave nije pouzdano utvrđena.

**Kada:** se pokuša automatski dodijeliti +2.

**Onda:** bod se **ne** dodjeljuje. Ne uvodi se CRPS integracija.

Izvor: `BM-ML-042`; F-06.

### 17.7.4 — Jedna kategorija +3

**Ako:** je ideja i inovativna i zelena.

**Kada:** Komisija utvrdi ispunjenost.

**Onda:** dodjeljuje se najviše **3** boda, ne 6. Platforma **ne** odlučuje automatski o inovativnosti ni zelenosti.

Izvor: `BM-ML-042`.

### 17.7.5 — Zaključavanje nakon potvrde

**Ako:** predsjednik potvrdi dodatne bodove.

**Kada:** se naknadno pokuša izmjena ili ponovno otvaranje.

**Onda:** Platforma blokira radnju. Ne uvodi se ponovno otvaranje.

Izvor: `BM-ML-042`.

### 17.7.6 — Redoslijed u rezultatu

**Ako:** postoje prosjeci i dodatni bodovi.

**Kada:** se formira konačni rezultat.

**Onda:** dodatni bodovi ulaze tek nakon osnovnog zbira deset prosječnih ocjena.

Izvor: `BM-ML-044`.

---

# 18. Eliminatorni kriterijumi

Status poglavlja: USVOJENO

Ovo poglavlje određuje tri eliminatorna razloga iz člana 20 Odluke (`BM-ML-015`; `BM-ML-018`; `BM-ML-035`–`BM-ML-037`; `BM-ML-043`) i posebnu provjeru podobnosti prema `BM-ML-006`.

Član 20 sadrži tačno tri eliminatorna kriterijuma; četvrti eliminatorni kriterijum nije uveden. Ne uvodi se poslovno stanje `Eliminisana`. Konačno aktivan eliminatorni razlog dovodi do `rejected` uz odvojene razloge. Ne uvodi se V1 modul za upload, obradu ili odobravanje obrazaca M4/M4a tekućeg projekta. M4/M4a se u ovom poglavlju pominju samo kao postojeći eliminatorni kriterijum za ranije finansiranog korisnika.

## 18.1. Tri eliminatorna razloga

Postoje **tačno tri** eliminatorna razloga, sva tri na elektronskom M3:

1. prijava je nepotpuna (`BM-ML-035`–`BM-ML-037`; `BM-ML-043`);
2. korisnik koji je ranije dobio sredstva nije dostavio obavezne izvještaje M4 i M4a za ranije finansirani biznis plan (`BM-ML-018`);
3. biznis plan nije povezan sa prioritetnim oblastima iz člana 12 Odluke (`BM-ML-015`).

Platforma **ne** dodaje eliminatorni razlog koji nije propisan Odlukom.

## 18.2. M3, obavještenje i prigovor za sva tri razloga

Sva tri razloga evidentiraju se na M3 kao odvojene stavke. Aktiviranje bilo kojeg razloga **ne** smije odmah preskočiti pravo na prigovor. Tok prigovora ostaje Poglavlje 14.

Dok traje rok za prigovor ili postoji bilo koji neriješen blagovremen prigovor, prijava **ne** ulazi u ocjenjivanje i ostaje `submitted`.

* Ako nakon odluke nijedan razlog nije konačno aktivan, prijava ostaje `submitted` i nastavlja.
* Ako makar jedan razlog ostane konačno aktivan, ili ako rok istekne bez prigovora, prijava prelazi u `rejected` uz sve konačne aktivne razloge i **ne** ulazi u pozitivno ocjenjivanje.

Ne uvodi se druga paralelna elektronska provjera istog razloga.

## 18.3. M4/M4a i prioritetne oblasti

Drugi i treći kriterijum evidentiraju se na M3 zajedno sa prvim. Komisija provjerava:

* da li je za ranije finansirani ili djelimično finansirani biznis plan izvršeno propisano izvještavanje M4/M4a i pratećim dokumentima (`BM-ML-018`);
* da li je biznis plan povezan sa najmanje jednom prioritetnom oblasti iz člana 12 (`BM-ML-015`).

Ova provjera odnosi se na **ranije** ispunjene obaveze, a ne na izvještavanje o projektu koji tek bude podržan. Upload, obrada i odobravanje M4/M4a tekućeg projekta ostaju **van V1**.

Sistemska provjera može biti samo **pomoćna**. Platforma **ne** donosi samostalno konačnu odluku o eliminaciji. Administrator **ne** odlučuje o eliminatornim razlozima i **ne** mijenja M3.

Predsjednik evidentira rezultat rada Komisije na M3. Za svaki kriterijum ostaju odvojeni rezultat, aktivacija, obrazloženje, sastav, vrijeme, konačnost i veza sa prigovorom.

## 18.4. Dejstvo eliminatornog rezultata

Eliminatorni rezultat **nije** novo poslovno ime statusa prijave. Poslovno stanje `Eliminisana` ne uvodi se. Konačno aktivan eliminatorni razlog dovodi do `rejected` uz odvojene razloge. Odbijanje se ne svodi na jednu slobodnu opštu napomenu.

Prijava sa makar jednim konačno aktivnim eliminatornim razlogom:

* **ne** dobija pozitivne ocjene;
* **ne** ulazi u rang-listu podrške;
* dobija `rejected` tek kada je makar jedan razlog konačan, nakon odluke o prigovoru ili isteka roka bez prigovora.

Početni M3 nalaz **ne** postavlja `rejected`.

## 18.5. Povezani član Komisije — BM-ML-006

Zabrana učešća povezanog člana **nije** četvrti eliminatorni kriterijum iz člana 20. Vodi se kao **posebna provjera uslova podobnosti** (`BM-ML-006`).

Platforma:

* **ne** utvrđuje automatski povezanost;
* **ne** blokira automatski kreiranje ili podnošenje prijave;
* **ne** uvodi automatsko povezivanje naloga i članova Komisije.

Komisija provjerava zabranu učešća i evidentira utvrđenu nepodobnost i razlog. Platforma čuva rezultat i revizijski trag. Administrator **ne** odlučuje.

Ako je nepodobnost konačno utvrđena, prijava **ne** ulazi u pozitivno ocjenjivanje. Status prijave uređuje se prema `BM-ML-020` i ne miješa se sa četvrtim eliminatornim kriterijumom.

## 18.6. Funkcionalni prelazi — eliminatorni razlozi

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| M3 | najmanje jedan aktiviran razlog | pokušaj ocjenjivanja | rok za prigovor traje ili prigovor neriješen | ocjenjivanje zabranjeno; status ostaje `submitted` | sistem | `BM-ML-035`; `BM-ML-036` |
| M3 | najmanje jedan aktiviran razlog | prigovor riješen | nijedan razlog nije konačno aktivan | prijava ostaje `submitted`; nastavlja postupak | Komisija | `BM-ML-036` |
| M3 | najmanje jedan aktiviran razlog | prigovor riješen ili istek 3 dana | makar jedan razlog konačno aktivan | prijava `rejected` uz sve konačne aktivne razloge; ne ulazi u ocjenjivanje | sistem / Komisija | `BM-ML-036`; `BM-ML-043` |
| Eliminatorni razlog | — | automatska odluka Platforme ili Administratora | — | zabranjeno | sistem / Administrator | `BM-ML-043` |
| Podobnost | `submitted` | provjera BM-ML-006 | Komisija utvrdi povezanost | nepodobnost evidentirana; nije 4. kriterijum čl. 20 | Komisija | `BM-ML-006` |
| Prijava | konačni eliminatorni razlog | status prijave | makar jedan razlog konačno aktivan | `rejected` uz sve konačne aktivne razloge; nema stanja Eliminisana | sistem | `BM-ML-043`; `BM-ML-020` |

## 18.7. Prihvatni kriterijumi — eliminatorni razlozi

### 18.7.1 — Tačno tri razloga

**Ako:** se evidentira eliminatorni razlog iz člana 20.

**Kada:** predsjednik upisuje razlog.

**Onda:** dozvoljena su samo tri razloga iz §18.1. Član 20 sadrži tačno tri eliminatorna kriterijuma; četvrti eliminatorni kriterijum nije uveden.

Izvor: `BM-ML-043`.

### 18.7.2 — Prigovor prije ocjenjivanja

**Ako:** je aktiviran najmanje jedan eliminatorni razlog i rok za prigovor traje ili postoji neriješen blagovremen prigovor.

**Kada:** se pokuša pozitivno ocjenjivanje.

**Onda:** Platforma blokira ocjenjivanje. Prijava ostaje `submitted`.

Izvor: `BM-ML-036`; `BM-ML-043`.

### 18.7.3 — Platforma ne eliminira sama

**Ako:** sistemska pomoćna provjera označi nedostatak.

**Kada:** se utvrđuje konačni eliminatorni razlog.

**Onda:** sistemski nalaz **nije** odluka. Konačni razlog evidentira predsjednik na osnovu odluke Komisije, sa licem, datumom i vremenom.

Izvor: `BM-ML-043`; `BM-ML-035`.

### 18.7.4 — Nema stanja Eliminisana

**Ako:** je konačno utvrđen eliminatorni razlog.

**Kada:** Platforma određuje status prijave.

**Onda:** prijava prelazi u `rejected` uz sve konačne aktivne razloge. Poslovno stanje `Eliminisana` se ne uvodi. Prijava ne dobija pozitivne ocjene i ne ulazi u rang-listu podrške. `rejected` ne nastaje samo zbog početnog M3 nalaza.

Izvor: `BM-ML-043`; `BM-ML-020`.

### 18.7.5 — BM-ML-006 nije četvrti kriterijum

**Ako:** Komisija utvrdi zabranu učešća povezanog člana.

**Kada:** se evidentira nepodobnost.

**Onda:** to **nije** četvrti eliminatorni kriterijum iz člana 20. Platforma **ne** blokira automatski podnošenje i **ne** utvrđuje povezanost sama.

Izvor: `BM-ML-006`.

### 18.7.6 — Tri kriterijuma na M3 i pravo na prigovor

**Ako:** se otvori elektronski M3 ili se aktivira eliminatorni razlog.

**Kada:** Komisija evidentira rezultat ili podnosilac ostvaruje pravo na prigovor.

**Onda:** M3 prikazuje tačno tri kriterijuma. Pravo na prigovor postoji za svaki aktivirani kriterijum. Bodovanje je dozvoljeno tek kada nijedan razlog nije konačno aktivan.

Izvor: `BM-ML-035`; `BM-ML-036`; `BM-ML-043`.

---

# 19. Obračun i prag

Status poglavlja: USVOJENO

Ovo poglavlje određuje formulu konačne ocjene i prag podrške (`BM-ML-038`; `BM-ML-041`; `BM-ML-042`; `BM-ML-044`).

Ne određuje rang-listu ni iznose. To pripada Poglavljima 20 i 21.

## 19.1. Formula

Za svaki od deset kriterijuma:

`prosjek kriterijuma = zbir tri zaključane ocjene / 3`

Osnovni zbir:

`osnovni zbir = zbir prosjeka svih deset kriterijuma`

Konačna ocjena:

`konačna ocjena = osnovni zbir + potvrđeni dodatni bodovi`

Svih deset kriterijuma ocjenjuje se cijelim brojem **1–5**. Za obračun su potrebne **tačno tri** kompletne zaključane ocjene.

Platforma **ne** računa konačan rezultat ako nedostaje ocjena jednog člana.

## 19.2. Maksimumi i prag

* maksimalni osnovni zbir: **50**;
* maksimalni dodatni bodovi: **6**;
* maksimalna konačna ocjena: **56**;
* prag podrške: **30**.

Rezultat **ispod 30** ne ispunjava prag i **ne** može biti podržan.

Rezultat **tačno 30 ili više** ispunjava prag, ali **ne** garantuje dodjelu sredstava.

## 19.3. Preciznost

Za prag, rangiranje i izjednačenje koristi se **puna nezaokružena vrijednost** (`BM-ML-041`).

Prikaz rezultata je na **dvije decimale**. Zaokruživanje prikaza **ne** smije:

* promijeniti prolaznost;
* promijeniti rang;
* proizvesti lažno izjednačenje.

## 19.4. Ko računa, ko odlučuje

Platforma **automatski** računa rezultat. Platforma **ne** odlučuje ko dobija sredstva.

Nakon promjene dozvoljenog ulaznog podatka **prije** zaključavanja, rezultat se ponovo računa.

Nakon zaključavanja ocjena i dodatnih bodova **nema** ručne izmjene konačne ocjene.

## 19.5. Funkcionalni prelazi — obračun i prag

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Konačna ocjena | tri zaključane ocjene; dodatni bodovi potvrđeni | obračun | — | osnovni zbir + dodatni bodovi; prikaz na 2 decimale; prijava `evaluated` | sistem | `BM-ML-044`; `BM-ML-041` |
| Konačna ocjena | nedostaje kompletna ocjena | obračun | — | nije izračunata | sistem | `BM-ML-003`; `BM-ML-041` |
| Prag | konačna ocjena < 30 | provjera praga | puna vrijednost | prag nije ispunjen; nema podrške | sistem | `BM-ML-044` |
| Prag | konačna ocjena ≥ 30 | provjera praga | puna vrijednost | prag ispunjen; dodjela nije garantovana | sistem | `BM-ML-044` |
| Konačna ocjena | ulaz izmijenjen prije zaključavanja | ponovni obračun | dozvoljena izmjena | nova vrijednost | sistem | `BM-ML-041`; `BM-ML-042` |
| Konačna ocjena | ocjene i dodatni bodovi zaključani | ručna izmjena ocjene | — | zabranjeno | svi | `BM-ML-039`; Poglavlje 17 |

## 19.6. Prihvatni kriterijumi — obračun i prag

### 19.6.1 — Formula i maksimum 56

**Ako:** postoje tri zaključane ocjene i potvrđeni dodatni bodovi.

**Kada:** Platforma računa konačnu ocjenu.

**Onda:** `konačna ocjena = zbir deset prosjeka + dodatni bodovi`. Maksimum je 56. Ručno polje ocjene **nije** dozvoljeno.

Izvor: `BM-ML-044`.

### 19.6.2 — Nema rezultata bez treće ocjene

**Ako:** nedostaje kompletna zaključana ocjena jednog mjesta Komisije.

**Kada:** se pokuša obračun konačne ocjene.

**Onda:** Platforma **ne** izračunava konačan rezultat.

Izvor: `BM-ML-003`; `BM-ML-041`.

### 19.6.3 — Prag 30

**Ako:** je puna nezaokružena konačna ocjena manja od 30.

**Kada:** se provjerava prag.

**Onda:** prijava ne ispunjava prag i ne može biti podržana. Pri konačnoj potvrdi rezultata dobija `rejected`; bodovi i rang ostaju sačuvani. Ako je ≥ 30, prag je ispunjen, ali dodjela nije garantovana i prijava **nije** automatski `approved`.

Izvor: `BM-ML-044`.

### 19.6.4 — Prikaz ne mijenja prolaznost

**Ako:** prikaz na dvije decimale izgleda kao 30,000, a puna vrijednost je ispod 30.

**Kada:** se utvrđuje prag.

**Onda:** koristi se puna vrijednost. Prikaz **ne** pretvara rezultat ispod 30 u prolaz.

Izvor: `BM-ML-041`.

---

# 20. Rangiranje i izjednačenje

Status poglavlja: USVOJENO

Ovo poglavlje određuje preliminarnu i konačnu fazu rang-liste i izjednačenje (`BM-ML-041`; `BM-ML-044`; `BM-ML-045`; `BM-ML-046`).

Ne uvodi javno objavljivanje službene Odluke. To je odloženo pitanje 10 i van trenutnog obuhvata V1.

Konačna faza rang-liste poslovno se veže za treću sjednicu. Komisija treću sjednicu zakazuje najkasnije sedam dana od održavanja druge sjednice i usmenih intervjua (`BM-ML-034`). Platforma **ne** zakazuje sjednice automatski i **ne** vodi sjednicu kao poseban poslovni objekat.

## 20.1. Jedan objekat, dvije faze

Rang-lista je **jedan** poslovni objekat sa dvije faze:

1. preliminarna rang-lista;
2. konačna rang-lista.

Preliminarna faza i njeni podaci ostaju sačuvani radi istorije i revizijskog traga. Prelazak u konačnu fazu **ne** mijenja zaključane individualne ocjene niti ponovno računa bodove.

## 20.2. Preliminarna rang-lista

Platforma je priprema **automatski** tek kada su ispunjeni svi sljedeći uslovi:

* sva tri člana završe ocjenjivanje svih prijava u ciklusu;
* postoje tačno tri kompletne ocjene po prijavi u ciklusu;
* dodatni bodovi su potvrđeni;
* konačne ocjene su izračunate;
* nema neriješenih uslova koji blokiraju rangiranje, uključujući bilo koji neriješen blagovremen prigovor;
* nema prijave u ciklusu sa konačno aktivnim eliminatornim razlogom.

Preliminarna lista:

* prikazuje prijave koje ispunjavaju uslove za rangiranje;
* raspoređuje ih od najveće ka najmanjoj konačnoj ocjeni;
* **ne** sadrži konačne iznose raspodjele;
* **ne** dozvoljava ručnu izmjenu bodova ili redosljeda;
* čuva punu vrijednost za rang, a dvije decimale za prikaz.

Završetak ocjenjivanja samo jedne prijave ili samo jednog člana **ne** stvara preliminarnu rang-listu.

## 20.3. Prag na rang-listi

Prijava sa manje od 30 bodova **ne** može biti podržana. Može biti evidentirana u odgovarajućem prikazu rezultata, ali **ne** ulazi među prijave kojima se raspodjeljuju sredstva.

Rezultat 30 ili više **ne** garantuje podršku ako nema dovoljno sredstava.

## 20.4. Jednaki bodovi

Prijave sa jednakom **punom nezaokruženom** konačnom ocjenom imaju **istu** rang-poziciju.

Numeracija koristi obrazac:

`1, 2, 2, 4`

Tehnički redosljed prikaza unutar iste pozicije **ne** predstavlja prednost.

Ako sredstva **nijesu** dovoljna za sve prijave sa istim brojem bodova, primjenjuje se tačno član 22 i `BM-ML-046`:

1. ako je samo jedan izjednačeni plan namijenjen otpočinjanju biznisa, prednost ima taj plan;
2. ako nijedan ili svi izjednačeni planovi pripadaju otpočinjanju, Komisija odlučuje većinom glasova ukupnog broja članova.

**Ne** koristi se kao kriterijum:

* vrijeme podnošenja;
* broj prijave;
* tehnički ID;
* abecedni red;
* proizvoljna odluka predsjednika;
* zaokružena vrijednost na dvije decimale.

Odluka o izjednačenju:

* **ne** mijenja bodove;
* evidentira prijave na koje se odnosi;
* evidentira primijenjeno pravilo;
* kada je potrebno glasanje, evidentira glasove **sva tri člana**;
* evidentira rezultat, datum i vrijeme;
* čuva revizijski trag.

## 20.5. Konačna rang-lista

Konačna faza nastaje nakon:

* rješavanja izjednačenja koja utiču na raspodjelu;
* evidentiranja odluke `Podržava se` ili `Ne podržava se`;
* evidentiranja konačnih iznosa raspodjele.

Predsjednik **potvrđuje** konačnu rang-listu.

Nakon potvrde:

* bodovi se ne mijenjaju;
* redosljed se ne mijenja proizvoljno;
* iznosi se zaključavaju;
* podržana prijava sa evidentiranim iznosom prelazi u `approved`;
* prijava ispod praga prelazi u `rejected`;
* prijava iznad praga koja nije podržana zbog nedovoljnih sredstava prelazi u `rejected` uz poseban razlog;
* čuva se revizijski trag.

Javno objavljivanje službene Odluke **nije** sadržaj ovog poglavlja.

## 20.6. Funkcionalni prelazi — rangiranje

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Rang-lista | ciklus nije završen | formiranje preliminarne | — | nije formirana | sistem | `BM-ML-045` |
| Rang-lista | — | formiranje preliminarne | tri kompletne ocjene po prijavi; dodatni bodovi potvrđeni; ocjene izračunate; nema blokade | preliminarna; bez iznosa; bez ručne izmjene redosljeda | sistem | `BM-ML-045`; `BM-ML-041` |
| Rang-pozicija | jednaka puna ocjena | dodjela pozicije | `1, 2, 2, 4` | ista pozicija; prikaz ne daje prednost | sistem | `BM-ML-046` |
| Izjednačenje | ista ocjena; sredstva nijesu dovoljna | primjena čl. 22 | samo jedan plan otpočinjanja | prednost tom planu; bodovi nepromijenjeni | Komisija / sistem | `BM-ML-046` |
| Izjednačenje | ista ocjena; sredstva nijesu dovoljna | glasanje | nijedan ili svi su otpočinjanje | glasovi sva tri člana; evidencija pravila, rezultata i vremena | Komisija | `BM-ML-046`; `BM-ML-003` |
| Rang-lista | preliminarna | potvrda konačne | izjednačenja riješena; Podržava se / Ne podržava se; iznosi evidentirani | konačna; zaključana; podržane `approved`; nepodržane `rejected` uz razlog | predsjednik | `BM-ML-045`; `BM-ML-044` |
| Konačna rang-lista | potvrđena | izmjena bodova ili proizvoljan redosljed | — | zabranjeno | svi | `BM-ML-045` |

## 20.7. Prihvatni kriterijumi — rangiranje

### 20.7.1 — Preliminarna tek na kraju ciklusa

**Ako:** nijesu sva tri člana završila ocjenjivanje svih prijava u ciklusu.

**Kada:** se pokuša formirati preliminarna rang-lista.

**Onda:** Platforma je **ne** formira.

Izvor: `BM-ML-045`.

### 20.7.2 — Nema ručne izmjene redosljeda

**Ako:** postoji preliminarna rang-lista.

**Kada:** predsjednik, član ili Administrator pokuša ručno izmijeniti bodove ili redosljed.

**Onda:** Platforma blokira radnju.

Izvor: `BM-ML-045`.

### 20.7.3 — Ispod praga nije u raspodjeli

**Ako:** je konačna ocjena manja od 30.

**Kada:** se određuju prijave za raspodjelu sredstava.

**Onda:** prijava **ne** ulazi među prijave kojima se raspodjeljuju sredstva. Pri potvrdi konačnog rezultata dobija `rejected`; bodovi i rang ostaju sačuvani.

Izvor: `BM-ML-044`.

### 20.7.4 — Ista puna ocjena, ista pozicija

**Ako:** dvije prijave imaju jednaku punu nezaokruženu ocjenu.

**Kada:** se dodjeljuje rang-pozicija.

**Onda:** dijele istu poziciju po obrascu `1, 2, 2, 4`. Tehnički redosljed, ID, vrijeme podnošenja i abeceda **ne** daju prednost.

Izvor: `BM-ML-046`; `BM-ML-041`.

### 20.7.5 — Izjednačenje po članu 22

**Ako:** sredstva nijesu dovoljna za sve izjednačene prijave.

**Kada:** Komisija rješava izjednačenje.

**Onda:** primjenjuje se samo pravilo otpočinjanja ili glasanje sva tri člana. Bodovi se ne mijenjaju. Predsjednik **ne** odlučuje proizvoljno.

Izvor: `BM-ML-046`.

### 20.7.6 — Potvrda konačne liste

**Ako:** su riješena izjednačenja, evidentirane odluke `Podržava se` / `Ne podržava se` i iznosi.

**Kada:** predsjednik potvrdi konačnu rang-listu.

**Onda:** bodovi, redosljed i iznosi se zaključavaju. Podržana prijava sa evidentiranim iznosom prelazi u `approved`. Prijava ispod praga i prijava iznad praga bez dovoljno sredstava prelaze u `rejected` uz odgovarajući razlog. Javna objava Odluke se **ne** pokreće.

Izvor: `BM-ML-045`.

---

# 21. Budžet i raspodjela

Status poglavlja: USVOJENO

Ovo poglavlje određuje procentualne limite i evidentiranje iznosa (`BM-ML-047`; `BM-ML-048`).

Ne preuzimaju se ženski limiti 20/10/5%. Ne uvodi se automatska dodjela sredstava. Ne uvodi se javni tok službenih akata. Detaljan tok drugog Poziva pripada Poglavlju 22.

## 21.1. Osnovica

Procentualni limit računa se od **raspoloživog budžeta konkretnog Poziva**.

Za drugi Poziv osnovica je **njegov** raspoloživi budžet, a ne prvobitni godišnji budžet ako je za drugi Poziv raspoloživo manje sredstava.

## 21.2. Limiti 30/20/15

* **30%** — start-up, odnosno inovativni tehnološki biznis;
* **20%** — fizičko lice, preduzetnik ili društvo kojem ranije nijesu dodjeljivana sredstva;
* **15%** — fizičko lice, preduzetnik ili društvo kojem su ranije dodjeljivana sredstva.

Ranije finansirano fizičko lice pripada limitu **15%**.

Ako se kategorije preklapaju:

* procenti se **ne** sabiraju;
* primjenjuje se **najveći** odgovarajući procenat.

Procenat je **gornja granica**, a ne automatski dodijeljeni iznos.

## 21.3. Prikaz predsjedniku

Za svaku rangiranu prijavu Platforma predsjedniku prikazuje:

* rang;
* konačnu ocjenu;
* traženi iznos;
* primjenjivi procenat;
* izračunati maksimalni procentualni limit;
* već raspodijeljeni iznos;
* preostali budžet Poziva;
* najveći trenutno dozvoljeni iznos.

## 21.4. Određivanje iznosa

Komisija **određuje** iznos. Predsjednik ga **evidentira**. Platforma **ne** dodjeljuje automatski maksimalni ili preostali iznos.

Dodijeljeni iznos **ne** smije biti veći od:

* traženog iznosa;
* procentualnog limita;
* preostalog budžeta Poziva.

Komisija može odrediti **manji** iznos. Kada je iznos manji od traženog, evidentira se obrazloženje.

**Ne** uvodi se posebna automatska ponuda umanjenog iznosa podnosiocu. **Ne** uvodi se posebna saglasnost podnosioca, jer nije potvrđena poslovnim pravilom.

Zbir svih raspodijeljenih iznosa **ne** smije premašiti budžet Poziva. Nakon evidentiranja iznosa Platforma ponovo računa preostali budžet.

Raspodjela se vodi prema konačnoj rang-listi do utroška sredstava.

Potvrda raspodjele **blokira se** ako bilo koji iznos krši ograničenja. Razlog blokade mora biti prikazan ovlašćenom korisniku.

## 21.5. Preostala sredstva

Nakon potvrđivanja raspodjele prvog Poziva Platforma:

* evidentira ukupno raspodijeljeni iznos;
* računa i prikazuje preostali iznos.

Predsjednik potvrđuje raspodjelu. Postojanje preostalih sredstava predstavlja osnov za drugi Poziv. Platforma **ne** kreira i **ne** objavljuje drugi Poziv automatski. Detalj: Poglavlje 22.

## 21.6. Konačna evidencija

Predsjednik potvrđuje konačne iznose raspodjele.

Nakon potvrde:

* iznosi se zaključavaju;
* čuvaju se odgovorno lice, datum i vrijeme;
* čuva se revizijski trag;
* podaci se koriste za konačnu rang-listu;
* javni tok službenih akata se **ne** pokreće.

## 21.7. Funkcionalni prelazi — budžet i raspodjela

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Limit | rangirana prijava | izračun limita | budžet konkretnog Poziva | 30/20/15%; pri preklapanju najveći; procenti se ne sabiraju | sistem | `BM-ML-047` |
| Iznos | Komisija odredi | evidencija | predsjednik | iznos unesen; nije automatski maksimum | predsjednik | `BM-ML-048` |
| Iznos | unos | provjera | veći od traženog, procenta ili preostalog budžeta | blokirano; razlog prikazan | sistem | `BM-ML-048` |
| Iznos | manji od traženog | evidencija | obrazloženje | iznos sačuvan sa obrazloženjem | predsjednik | `BM-ML-048` |
| Budžet | evidentiran iznos | ponovni obračun | — | ažuriran preostali budžet | sistem | `BM-ML-048` |
| Raspodjela | iznosi uneseni | potvrda | bilo koji iznos krši ograničenje | potvrda blokirana; razlog prikazan | sistem | `BM-ML-048` |
| Raspodjela | iznosi valjani | potvrda predsjednika | — | iznosi zaključani; lice, datum, vrijeme; preostali iznos prikazan; podržane prijave `approved` | predsjednik | `BM-ML-048`; `BM-ML-044` |
| Drugi Poziv | preostala sredstva | automatsko kreiranje ili objava | — | zabranjeno | sistem | `BM-ML-051`; Poglavlje 22 |

## 21.8. Prihvatni kriterijumi — raspodjela

### 21.8.1 — Osnovica konkretnog Poziva

**Ako:** se računa procentualni limit.

**Kada:** Platforma određuje osnovicu.

**Onda:** koristi se raspoloživi budžet konkretnog Poziva, a ne tuđi budžet ni automatski puni godišnji iznos za drugi Poziv sa manjim preostalim sredstvima.

Izvor: `BM-ML-047`.

### 21.8.2 — Limiti 30/20/15 i preklapanje

**Ako:** prijava istovremeno pripada više kategorija.

**Kada:** se određuje primjenjivi procenat.

**Onda:** procenti se ne sabiraju. Primjenjuje se najveći odgovarajući procenat. Ranije finansirano fizičko lice koristi 15%. Ženski limiti 20/10/5% **nijesu** dostupni.

Izvor: `BM-ML-047`.

### 21.8.3 — Nema automatske dodjele

**Ako:** je izračunat maksimalni dozvoljeni iznos.

**Kada:** se evidentira dodijeljeni iznos.

**Onda:** Platforma **ne** upisuje automatski maksimum ni preostali budžet. Komisija određuje, predsjednik evidentira.

Izvor: `BM-ML-048`.

### 21.8.4 — Tri gornje granice

**Ako:** uneseni iznos premašuje traženi iznos, procentualni limit ili preostali budžet.

**Kada:** se pokuša sačuvati ili potvrditi.

**Onda:** Platforma blokira radnju i prikazuje razlog.

Izvor: `BM-ML-048`.

### 21.8.5 — Zbir ne premašuje budžet

**Ako:** zbir raspodijeljenih iznosa premašuje budžet Poziva.

**Kada:** predsjednik potvrđuje raspodjelu.

**Onda:** potvrda je blokirana. Razlog je prikazan ovlašćenom korisniku.

Izvor: `BM-ML-048`.

### 21.8.6 — Zaključavanje i drugi Poziv

**Ako:** predsjednik potvrdi valjanu raspodjelu.

**Kada:** se završi potvrda.

**Onda:** iznosi se zaključavaju uz lice, datum i vrijeme. Preostali iznos se prikazuje. Drugi Poziv se **ne** kreira automatski. Javni tok akata se **ne** pokreće.

Izvor: `BM-ML-048`; `BM-ML-051`.

---

# 22. Drugi Poziv

Status poglavlja: USVOJENO

Ovo poglavlje razrađuje drugi Poziv unutar iste godišnje instance. Izvori: `BM-ML-014`; `BM-ML-033`; `BM-ML-047`; `BM-ML-049`; `BM-ML-050`; `BM-ML-051`; `BM-ML-052`.

Ne određuje tehnički model baze, API, identifikatore ni konkretne ID strukture. Ne prenosi opcionost drugog Poziva iz profila ženskog preduzetništva. Kod mladih je drugi Javni konkurs **obavezan** kada nakon prvog Poziva ostanu neraspoređena sredstva (`BM-ML-049`).

## 22.1. Uslov za drugi Poziv

Nakon potvrđene raspodjele prvog Poziva Platforma:

* prikazuje odobreni budžet prvog Poziva;
* prikazuje ukupno raspodijeljeni iznos;
* računa i prikazuje neraspoređeni iznos;
* evidentira da postoje ili ne postoje neraspoređena sredstva.

Ako su sva sredstva raspodijeljena:

* drugi Poziv se **ne** raspisuje;
* Platforma **ne** nudi automatsko kreiranje drugog Poziva.

Ako postoje neraspoređena sredstva:

* drugi Javni konkurs je **obavezan** prema `BM-ML-049`;
* Platforma **ne** donosi pravnu ili poslovnu odluku umjesto odgovornog korisnika;
* Platforma **ne** kreira i **ne** objavljuje drugi Poziv automatski;
* odgovorni korisnik **ručno** započinje pripremu drugog Poziva.

Opcionalnost drugog Poziva iz ženskog profila **ne** važi ovdje. Kod mladih je drugi Poziv obavezan kada ostanu sredstva. Platforma ne pretvara tu obavezu u automatsko kreiranje ni automatsko objavljivanje.

## 22.2. Godišnja instanca

Prvi i drugi Poziv pripadaju **istoj godišnjoj instanci**.

Drugi Poziv je **novi i odvojen** Poziv. **Ne** kreira se nova godišnja instanca samo zato što postoji drugi Poziv.

Podaci prvog Poziva ostaju **nepromijenjeni i sačuvani**. Platforma čuva vezu između godišnje instance, prvog Poziva i drugog Poziva.

Ovo poglavlje **ne** određuje tehnički model baze ni konkretne ID strukture.

## 22.3. Ručno kreiranje i objavljivanje

Administrator Konkursa **ručno** kreira drugi Poziv.

Drugi Poziv ima sopstvene:

* podatke Poziva;
* službeni zavodni broj iz pisarnice;
* datum i vrijeme objavljivanja;
* rok za prijave;
* raspoloživi budžet;
* prijave;
* administrativnu provjeru;
* prigovore;
* usmeno obrazloženje;
* ocjenjivanje;
* dodatne bodove;
* rang-listu;
* raspodjelu;
* arhiviranje.

Čuvanje podataka **ne** predstavlja objavljivanje.

Prije objave primjenjuju se **iste kontrole obaveznih podataka** kao za prvi Poziv (Poglavlja 5 i 6).

Platforma **ne** generiše službeni zavodni broj. Administrator ga unosi ručno, iz pisarnice.

Administrator **posebno** pokreće objavljivanje. Objava pokreće **sopstveni rok** drugog Poziva prema `BM-ML-033`.

Drugi Poziv mora biti objavljen **najkasnije do isteka trećeg kvartala** tekuće godine. Treći kvartal je jul, avgust i septembar.

**Ne** uvodi se automatsko objavljivanje posljednjeg dana kvartala. **Ne** uvodi se automatsko pomjeranje roka. Platforma **ne** zaključuje sama da su ispunjeni pravni i poslovni uslovi za objavljivanje; odgovorni korisnik pokreće objavljivanje.

## 22.4. Budžet drugog Poziva

Raspoloživi budžet drugog Poziva određuje se u granicama **neraspoređenih godišnjih sredstava** nakon prvog Poziva.

Budžet se **ne** postavlja automatski bez unosa i potvrde odgovornog korisnika.

Platforma **blokira** potvrdu budžeta koji premašuje raspoloživa preostala godišnja sredstva. Prikazuje razlog blokade.

Zbir budžeta i raspodjela svih Poziva iste godišnje instance **ne** smije premašiti odobreni godišnji budžet.

Za limite **30/20/15%** osnovica je budžet **konkretnog drugog Poziva**, prema `BM-ML-047`. **Ne** koristi se tuđi budžet ni automatski puni godišnji iznos.

## 22.5. Nova prijava na drugom Pozivu

Isti podnosilac **može** ponovo konkurisati.

Za drugi Poziv:

* kreira **novu** prijavu;
* ništa se iz prve prijave **ne** prenosi automatski;
* pravni oblik se ponovo uzima sa potvrđenog naloga;
* neregistrovano fizičko lice ponovo bira namjeru;
* registrovani preduzetnik ili društvo ponovo bira poslovnu fazu;
* Platforma određuje odgovarajući M1a ili M1b;
* ponovo određuje dokumentacioni paket;
* M2 se ponovo popunjava;
* tabela nabavki ponovo se popunjava;
* dokumentacija se ponovo prilaže;
* prijava se ponovo konačno podnosi u roku drugog Poziva;
* prolazi novu administrativnu provjeru, prigovor, usmeno obrazloženje, ocjenjivanje, rangiranje i raspodjelu.

**Ne** prenose se automatski:

* podaci obrazaca;
* prilozi;
* potpunost;
* prigovori;
* ocjene;
* dodatni bodovi;
* rang;
* dodijeljeni iznos;
* konačni rezultat.

Ranije učešće samo po sebi **nije** razlog za zabranu nove prijave.

Ostali uslovi podobnosti i pravila o ranijem finansiranju **ponovo** se primjenjuju. Ovo pravilo **ne** ukida eliminatorne kriterijume.

Jedan podnosilac može imati **najviše jednu** konačno podnesenu prijavu po pojedinačnom Pozivu (`BM-ML-014`).

## 22.6. Funkcionalni prelazi — drugi Poziv

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Godišnja instanca | potvrđena raspodjela prvog Poziva; sva sredstva raspodijeljena | prikaz stanja | nema preostalih sredstava | drugi Poziv se ne raspisuje; nema ponude automatskog kreiranja | sistem | `BM-ML-049`; `BM-ML-051` |
| Godišnja instanca | potvrđena raspodjela prvog Poziva; postoje preostala sredstva | prikaz stanja | neraspoređeni iznos veći od nule | drugi Javni konkurs obavezan; Platforma ne odlučuje i ne kreira automatski | sistem | `BM-ML-049`; `BM-ML-051` |
| Drugi Poziv | preostala sredstva | pokušaj automatskog kreiranja ili objave | — | zabranjeno | sistem | `BM-ML-051` |
| Drugi Poziv | ista godišnja instanca; preostala sredstva evidentirana | ručno kreiranje nacrta | Administrator Konkursa | nacrt drugog Poziva u istoj instanci; prvi Poziv nepromijenjen | Administrator | `BM-ML-050`; `BM-ML-051` |
| Drugi Poziv | nacrt | čuvanje | — | sačuvan; nije objavljen; rok ne kreće | Administrator | `BM-ML-051`; `BM-ML-033` |
| Budžet drugog Poziva | unos | potvrda | iznos premašuje preostala godišnja sredstva | blokirano; razlog prikazan | sistem | `BM-ML-049`; `BM-ML-047` |
| Budžet drugog Poziva | unos | potvrda | iznos u granicama preostalih godišnjih sredstava; zbir ne premašuje godišnji budžet | budžet sačuvan; nije automatski upisan | Administrator | `BM-ML-049`; Poglavlje 5 |
| Drugi Poziv | sačuvan | pokušaj objave | nedostaje zavodni broj ili drugi obavezni podaci | blokirano | sistem | `BM-ML-033`; `BM-ML-053` |
| Drugi Poziv | sačuvan; obavezni podaci uneseni | izričita objava | zavodni broj; iste kontrole kao za prvi Poziv | objavljen; sopstveni rok kreće; datum i vrijeme evidentirani | Administrator | `BM-ML-033`; `BM-ML-051`; `BM-ML-053` |
| Prijava | isti podnosilac; drugi Poziv objavljen; rok traje | kreiranje nove prijave | najviše jedna konačno podnesena po Pozivu | nova prijava `draft`; prva prijava se ne otvara ni ne mijenja; njen status se ne prenosi | podnosilac | `BM-ML-014`; `BM-ML-052` |
| Prijava | postoji prijava iz prvog Poziva | pokušaj automatskog prenosa | — | zabranjeno; podaci, prilozi, potpunost, prigovori, ocjene, dodatni bodovi, rang, iznos i konačni rezultat se ne prenose | sistem | `BM-ML-052` |
| Prijava | nova prijava `draft` | konačno podnošenje | rok drugog Poziva traje; obavezna polja ispunjena | `submitted`; prolazi puni postupak drugog Poziva | podnosilac | `BM-ML-022`; `BM-ML-023`; `BM-ML-052` |

## 22.7. Prihvatni kriterijumi — drugi Poziv

### 22.7.1 — Nema preostalih sredstava

**Ako:** su nakon potvrđene raspodjele prvog Poziva sva sredstva raspodijeljena.

**Kada:** Platforma prikazuje stanje godišnje instance.

**Onda:** drugi Poziv se ne raspisuje. Platforma ne nudi automatsko kreiranje drugog Poziva.

Izvor: `BM-ML-049`; `BM-ML-051`.

### 22.7.2 — Obavezan drugi Poziv kada ostanu sredstva

**Ako:** nakon potvrđene raspodjele prvog Poziva postoje neraspoređena sredstva.

**Kada:** se utvrđuje uslov za drugi Javni konkurs.

**Onda:** drugi Javni konkurs je obavezan. Platforma ne donosi tu odluku umjesto odgovornog korisnika i ne kreira drugi Poziv automatski. Opcionalnost iz ženskog profila ne važi.

Izvor: `BM-ML-049`; `BM-ML-051`.

### 22.7.3 — Nema automatskog kreiranja

**Ako:** postoje preostala sredstva ili je završen prvi Poziv.

**Kada:** sistem ili korisnik očekuje automatsko kreiranje ili objavljivanje drugog Poziva.

**Onda:** Platforma ne kreira i ne objavljuje drugi Poziv automatski.

Izvor: `BM-ML-051`.

### 22.7.4 — Ista godišnja instanca

**Ako:** Administrator ručno kreira drugi Poziv.

**Kada:** se uspostavlja veza sa godišnjom instancom.

**Onda:** drugi Poziv pripada istoj godišnjoj instanci kao prvi. Nova godišnja instanca se ne kreira samo zbog drugog Poziva. Podaci prvog Poziva ostaju nepromijenjeni i sačuvani.

Izvor: `BM-ML-050`.

### 22.7.5 — Čuvanje nije objava

**Ako:** Administrator sačuva nacrt drugog Poziva.

**Kada:** se završi čuvanje.

**Onda:** podaci su sačuvani. Poziv nije objavljen. Rok ne kreće.

Izvor: `BM-ML-051`; `BM-ML-033`.

### 22.7.6 — Blokada budžeta iznad preostalih sredstava

**Ako:** uneseni budžet drugog Poziva premašuje raspoloživa preostala godišnja sredstva ili bi zbir premašio odobreni godišnji budžet.

**Kada:** se pokuša potvrditi budžet.

**Onda:** Platforma blokira potvrdu i prikazuje razlog. Budžet se ne postavlja automatski.

Izvor: `BM-ML-049`; Poglavlje 5.

### 22.7.7 — Osnovica 30/20/15% je budžet drugog Poziva

**Ako:** se na drugom Pozivu računaju limiti 30/20/15%.

**Kada:** Platforma određuje osnovicu.

**Onda:** koristi se budžet konkretnog drugog Poziva, prema `BM-ML-047`.

Izvor: `BM-ML-047`.

### 22.7.8 — Objava bez obaveznih podataka

**Ako:** drugi Poziv nema službeni zavodni broj ili drugi obavezni podatak za objavu.

**Kada:** Administrator pokuša objavu.

**Onda:** objava je blokirana. Platforma ne generiše zavodni broj.

Izvor: `BM-ML-033`; `BM-ML-053`.

### 22.7.9 — Uspješna objava i rok trećeg kvartala

**Ako:** su uneseni obavezni i valjani podaci drugog Poziva.

**Kada:** Administrator izričito pokrene objavljivanje.

**Onda:** drugi Poziv je objavljen. Pokreće se njegov sopstveni rok prema `BM-ML-033`. Datum i vrijeme objave se evidentiraju. Ne uvodi se automatsko objavljivanje posljednjeg dana trećeg kvartala niti automatsko pomjeranje roka. Poslovni rok objave je najkasnije do isteka trećeg kvartala tekuće godine.

Izvor: `BM-ML-033`; `BM-ML-051`.

### 22.7.10 — Nova prijava bez prenosa

**Ako:** isti podnosilac kreira prijavu na drugom Pozivu.

**Kada:** se otvara nova prijava.

**Onda:** ništa se iz prve prijave ne prenosi automatski. Nova prijava počinje kao `draft`. Pravni oblik se ponovo uzima sa potvrđenog naloga. Namjera i poslovna faza se ponovo utvrđuju. M1a ili M1b, dokumentacioni paket, M2, tabela nabavki i prilozi popunjavaju se i prilažu ponovo. Ranije učešće samo po sebi nije zabrana. Najviše jedna konačno podnesena prijava po pojedinačnom Pozivu.

Izvor: `BM-ML-014`; `BM-ML-052`.

---

# 23. Zaključivanje i arhiviranje Poziva

Status poglavlja: USVOJENO

Ovo poglavlje razrađuje ručno zaključivanje i arhiviranje Poziva. Izvori: `BM-ML-054`; `BM-ML-058`; usvojena funkcionalna odluka F-05. Sažetak F-05 ostaje u Poglavlju 4; ovdje se razrađuju preduslovi, potvrda, čuvanje i granice.

## 23.1. Odgovorna uloga

Predsjednik Komisije **ručno** pokreće zaključivanje i arhiviranje Poziva.

Administrator Konkursa **ne** zaključuje Poziv.

Platforma **ne** arhivira Poziv automatski samo zato što je:

* istekao rok za prijave;
* završena prva sjednica;
* završeno ocjenjivanje;
* potrošen budžet;
* nastupio kraj kvartala.

Zamjenski član može postupati samo ako ima **važeće formalno ovlašćenje** za ulogu koju mijenja i ako je takvo postupanje već dozvoljeno postojećim pravilima (§3.7; §3.11). **Ne** uvodi se posebno novo pravo arhiviranja.

## 23.2. Preduslovi

Predsjednik može zaključiti i arhivirati Poziv **tek kada su ispunjeni svi** sljedeći uslovi:

* završena administrativna provjera svih podnesenih prijava;
* nema neriješenih blagovremenih prigovora;
* za prijave koje ulaze u ocjenjivanje završena su usmena obrazloženja u potvrđenoj granici;
* završene su sve potrebne individualne ocjene;
* postoje tačno tri kompletne ocjene po ocjenjivanoj prijavi;
* dodatni bodovi su potvrđeni;
* eliminatorni razlozi su konačno evidentirani;
* obračun je završen;
* sva relevantna izjednačenja su riješena;
* konačna rang-lista je potvrđena;
* konačni rezultati su evidentirani;
* iznosi raspodjele su evidentirani i potvrđeni;
* zbir iznosa ne premašuje budžet Poziva;
* preostali iznos je evidentiran.

Platforma **prije arhiviranja** provjerava ove uslove.

Ako bilo koji uslov **nije** ispunjen:

* arhiviranje se **blokira**;
* predsjedniku se prikazuje konkretan razlog ili spisak razloga;
* Platforma **ne** završava preostalu poslovnu radnju automatski.

## 23.3. Ručno zaključivanje

Prije konačne potvrde Platforma prikazuje **upozorenje** da će Poziv biti zaključen i arhiviran.

Predsjednik **izričito** potvrđuje akciju.

Platforma evidentira:

* Poziv;
* godišnju instancu;
* odgovorno lice;
* datum i vrijeme;
* potvrđenu konačnu rang-listu;
* konačne rezultate;
* raspodijeljeni iznos;
* preostali iznos;
* rezultat provjere preduslova.

Nakon potvrde Poziv je **arhiviran** i dostupan **samo za pregled** ovlašćenim korisnicima.

**Ne** uvodi se ponovno otvaranje arhiviranog Poziva bez posebnog odobrenog pravila.

## 23.4. Arhiviranje nije brisanje

Arhiviranje **ne** briše:

* Poziv;
* prijave;
* M1a/M1b;
* M2;
* priloge;
* rezultate administrativne provjere;
* obavještenja;
* prigovore;
* evidencije usmenog obrazloženja;
* nacrte i zaključane ocjene u dozvoljenom revizijskom obimu;
* dodatne bodove;
* eliminatorne razloge;
* rang-liste;
* rezultate raspodjele;
* revizijski trag.

Nema automatskog brisanja zbog proteka vremena.

Konkretan broj godina čuvanja **nije** određen.

Arhiviranje **ne** čini dokumentaciju javnom. Pristup ostaje ograničen prema `BM-ML-054`.

Tehnički format arhive, backup, enkripcija, skladište i tehničko brisanje ostaju za TS.

## 23.5. Granica V1

Arhiviranje **ne** zavisi od:

* generisanja ili potpisivanja ugovora;
* isplate sredstava;
* realizacije projekta;
* M4/M4a;
* faktura i bankovnih izvoda poslije realizacije;
* de minimis dokumentacije;
* praćenja ugovornih obaveza;
* javnog objavljivanja službenih akata.

Ti procesi su van V1 ili odloženi.

Platforma u V1 evidentira konačan rezultat i raspodjelu, ali **ne** pokreće javni PDF tok Odluke.

## 23.6. Odnos prema drugom Pozivu

Arhiviranje prvog Poziva **ne** kreira drugi Poziv.

Postojanje preostalih sredstava ostaje evidentirano.

Drugi Poziv Administrator kreira **ručno** prema Poglavlju 22.

Prvi Poziv ostaje arhiviran i **ne** otključava se zbog drugog Poziva.

Arhiviranje drugog Poziva sprovodi se prema **istim** uslovima.

## 23.7. Funkcionalni prelazi — zaključivanje i arhiviranje

| Objekat | Početna činjenica | Akcija/događaj | Uslov | Rezultat | Uloga | BM/F izvor |
|---------|-------------------|----------------|-------|----------|-------|------------|
| Poziv | postoji neriješen blagovremen prigovor | pokušaj arhiviranja | predsjednik | blokirano; razlog prikazan; preostali rad se ne završava automatski | sistem | F-05; `BM-ML-036` |
| Poziv | individualne ocjene nijesu završene ili nema tačno tri kompletne ocjene | pokušaj arhiviranja | predsjednik | blokirano; razlog prikazan | sistem | F-05; `BM-ML-039` |
| Poziv | konačna rang-lista nije potvrđena | pokušaj arhiviranja | predsjednik | blokirano; razlog prikazan | sistem | F-05; `BM-ML-045` |
| Poziv | iznosi raspodjele nijesu evidentirani i potvrđeni | pokušaj arhiviranja | predsjednik | blokirano; razlog prikazan | sistem | F-05; `BM-ML-048` |
| Poziv | zbir iznosa premašuje budžet Poziva | pokušaj arhiviranja | predsjednik | blokirano; razlog prikazan | sistem | F-05; `BM-ML-048` |
| Poziv | svi preduslovi iz §23.2 ispunjeni | pokretanje zaključivanja | predsjednik | prikazano upozorenje; čeka izričitu potvrdu | predsjednik | F-05 |
| Poziv | upozorenje prikazano | izričita potvrda predsjednika | svi preduslovi i dalje ispunjeni | Poziv arhiviran; status prijave nepromijenjen; evidencija lica, datuma, vremena, rang-liste, rezultata, iznosa i preduslova; samo pregled | predsjednik | F-05; `BM-ML-058` |
| Poziv | arhiviran | pokušaj izmjene | — | zabranjeno; nema ponovnog otvaranja bez posebnog odobrenog pravila | svi | F-05; `BM-ML-058` |
| Poziv | arhiviranje sa preostalim sredstvima | potvrda predsjednika | preduslovi ispunjeni | Poziv arhiviran; preostali iznos ostaje evidentiran; drugi Poziv se ne kreira | predsjednik / sistem | F-05; `BM-ML-049`; `BM-ML-051` |
| Drugi Poziv | prvi Poziv arhiviran; preostala sredstva | pokušaj automatskog kreiranja | — | zabranjeno; Administrator kreira ručno prema Poglavlju 22; prvi Poziv ostaje arhiviran | sistem | `BM-ML-051`; `BM-ML-050` |

## 23.8. Prihvatni kriterijumi — arhiviranje

### 23.8.1 — Administrator ne arhivira

**Ako:** Administrator Konkursa pokuša zaključiti ili arhivirati Poziv.

**Kada:** se pokrene akcija zaključivanja.

**Onda:** radnja nije dostupna Administratoru. Predsjednik ručno pokreće zaključivanje i arhiviranje.

Izvor: F-05; `BM-ML-004`.

### 23.8.2 — Nema automatskog arhiviranja

**Ako:** je istekao rok, završena prva sjednica, završeno ocjenjivanje, potrošen budžet ili nastupio kraj kvartala.

**Kada:** sistem procjenjuje da li da arhivira Poziv.

**Onda:** Platforma ne arhivira Poziv automatski.

Izvor: F-05.

### 23.8.3 — Neriješen prigovor blokira

**Ako:** postoji neriješen blagovremen prigovor.

**Kada:** predsjednik pokuša arhivirati Poziv.

**Onda:** arhiviranje je blokirano. Prikazan je konkretan razlog. Platforma ne završava prigovor automatski.

Izvor: F-05; `BM-ML-036`.

### 23.8.4 — Nezavršene ocjene blokiraju

**Ako:** nijesu završene sve potrebne individualne ocjene ili ne postoje tačno tri kompletne ocjene po ocjenjivanoj prijavi.

**Kada:** predsjednik pokuša arhivirati Poziv.

**Onda:** arhiviranje je blokirano. Prikazan je konkretan razlog.

Izvor: F-05; `BM-ML-039`.

### 23.8.5 — Nema potvrđene rang-liste

**Ako:** konačna rang-lista nije potvrđena.

**Kada:** predsjednik pokuša arhivirati Poziv.

**Onda:** arhiviranje je blokirano. Prikazan je konkretan razlog.

Izvor: F-05; `BM-ML-045`.

### 23.8.6 — Nema evidentiranih iznosa ili je budžet prekoračen

**Ako:** iznosi raspodjele nijesu evidentirani i potvrđeni, zbir premašuje budžet Poziva, ili preostali iznos nije evidentiran.

**Kada:** predsjednik pokuša arhivirati Poziv.

**Onda:** arhiviranje je blokirano. Prikazan je konkretan razlog.

Izvor: F-05; `BM-ML-048`.

### 23.8.7 — Upozorenje i izričita potvrda

**Ako:** su svi preduslovi iz §23.2 ispunjeni.

**Kada:** predsjednik pokrene zaključivanje.

**Onda:** Platforma prikazuje upozorenje da će Poziv biti zaključen i arhiviran. Arhiviranje se izvršava tek nakon izričite potvrde. Evidentiraju se Poziv, godišnja instanca, odgovorno lice, datum i vrijeme, potvrđena konačna rang-lista, konačni rezultati, raspodijeljeni iznos, preostali iznos i rezultat provjere preduslova. Nakon potvrde Poziv je dostupan samo za pregled ovlašćenim korisnicima. Status prijave ostaje nepromijenjen. Status prijave `archived` ne postoji.

Izvor: F-05; `BM-ML-058`.

### 23.8.8 — Nema izmjene ni ponovnog otvaranja

**Ako:** je Poziv arhiviran.

**Kada:** korisnik pokuša izmijeniti podatke ili ponovo otvoriti Poziv.

**Onda:** izmjena je zabranjena. Ponovno otvaranje se ne uvodi bez posebnog odobrenog pravila.

Izvor: F-05; `BM-ML-058`.

### 23.8.9 — Arhiviranje nije brisanje ni javnost

**Ako:** je Poziv arhiviran.

**Kada:** ovlašćeni korisnik čita arhivu.

**Onda:** Poziv, prijave, M1a/M1b, M2, prilozi, rezultati administrativne provjere, obavještenja, prigovori, evidencije usmenog obrazloženja, nacrti i zaključane ocjene u dozvoljenom revizijskom obimu, dodatni bodovi, eliminatorni razlozi, rang-liste, rezultati raspodjele i revizijski trag ostaju sačuvani. Nema automatskog brisanja zbog proteka vremena. Broj godina čuvanja nije određen. Arhiviranje ne čini dokumentaciju javnom. Pristup ostaje ograničen prema `BM-ML-054`.

Izvor: `BM-ML-054`; `BM-ML-058`.

### 23.8.10 — Granica V1 i drugi Poziv

**Ako:** predsjednik arhivira Poziv.

**Kada:** se završi potvrda.

**Onda:** arhiviranje ne zavisi od ugovora, isplate, realizacije, M4/M4a, faktura, de minimis dokumentacije, praćenja ugovornih obaveza ni javnog objavljivanja službenih akata. Javni PDF tok Odluke se ne pokreće. Arhiviranje prvog Poziva ne kreira drugi Poziv. Preostala sredstva ostaju evidentirana. Prvi Poziv se ne otključava zbog drugog Poziva.

Izvor: F-05; `BM-ML-051`; `BM-ML-056`; `BM-ML-057`; `KN-PATCH-BM-010`.

---

# 24. Funkcionalne zabrane i zaštita poslovnih pravila

Status poglavlja: USVOJENO

Ovo poglavlje **objedinjuje** zabrane već razrađene u Poglavljima 1–23. **Ne** uvodi nova poslovna pravila, nove oznake ni nove tokove.

Svaka zabrana čuva se na Platformi kao blokada, nedostupnost radnje ili zabranjeni ishod. Izvori su navedena poglavlja i `BM-ML-*` oznake.

## 24.1. Godišnja instanca i Poziv

Platforma **ne** smije:

* samostalno određivati godišnji budžet;
* generisati službeni zavodni broj;
* tretirati čuvanje kao objavljivanje;
* automatski objaviti prvi Poziv;
* automatski kreirati ili objaviti drugi Poziv;
* drugi Poziv tretirati kao novu godišnju instancu;
* dozvoliti da zbir budžeta Poziva premaši godišnji budžet;
* pomjeriti rok zbog neradnog dana;
* računati rok kao tačno 480 sati;
* primati prijave nakon isteka roka.

Izvori: Poglavlja 5, 6 i 22; `BM-ML-033`; `BM-ML-049`–`BM-ML-053`; F-02.

## 24.2. Prijava i dokumentacija

Platforma **ne** smije:

* dozvoliti proizvoljan izbor registrovanog pravnog oblika;
* dozvoliti ručni izbor M1a/M1b suprotno namjeri ili registrovanom obliku;
* svrstati nepodržani identitet u youth kategoriju `ostalo`;
* prikazati proizvoljan dokumentacioni paket;
* dozvoliti konačno podnošenje bez utvrđenog oblika, namjere kada je potrebna i poslovne faze;
* automatski pretvoriti nacrt u `submitted`;
* dozvoliti izmjenu, dopunu, brisanje ili povlačenje prijave nakon `submitted`;
* dozvoliti da promjena naloga izmijeni podnesenu prijavu;
* dozvoliti ponovno podnošenje na istom Pozivu;
* pretvoriti M3 `Nepotpuna` odmah u `rejected`;
* vratiti `rejected` u `submitted`;
* automatski prevesti nepodneseni nacrt nakon isteka roka u `rejected`;
* dodijeliti `approved` samo zbog prelaska praga 30;
* otključati prijavu promjenom statusa;
* nedostajući prilog tretirati kao sistemsku konačnu odluku Komisije;
* zahtijevati dokaz o žiro računu kao obavezan prilog početne prijave;
* automatski prenijeti prijavu i priloge na drugi Poziv;
* odbiti prijavu automatski samo na osnovu teritorijalnog podatka;
* uvesti CRPS integraciju ili automatski obračun starosti biznisa;
* zahtijevati PIB, CRPS, registrovano sjedište, formalnog osnivača ili formalnog izvršnog direktora za planirano društvo;
* tretirati planirano društvo kao već registrovano;
* automatski promijeniti namjeru, tip prijave, poslovnu fazu, M1 obrazac ili paket zbog ponovnog čitanja `is_registered`;
* izmijeniti podnesenu prijavu ponovnim čitanjem identiteta.

Izvori: Poglavlja 7–10 i 22; `BM-ML-014`; `BM-ML-019`–`BM-ML-032`; `BM-ML-052`; F-06.

## 24.3. Privatnost i pristup

Platforma **ne** smije:

* javno prikazati prijavu, M1a/M1b, M2 ili priloge;
* dozvoliti podnosiocu pristup tuđoj prijavi;
* dati administratoru sadržajni pristup prijavama ili razlozima nepotpunosti;
* Komisiji otvoriti sadržaj dok traje rok;
* Komisiji otvoriti prijave `U pripremi`;
* omogućiti redovno masovno preuzimanje ili izvoz paketa;
* javnim rezultatom otvoriti privatnu dokumentaciju.

Izvori: Poglavlja 3, 11, 12 i 23; `BM-ML-004`; `BM-ML-005`; `BM-ML-054`; `BM-ML-055`.

## 24.4. Komisija i M3

Platforma **ne** smije:

* tretirati predsjednika kao četvrtog člana;
* koristiti više od tri mjesta u elektronskom M3;
* započeti administrativnu provjeru bez formalno popunjena tri mjesta;
* dozvoliti prvu sjednicu sa manje od dva prisutna člana;
* sistemsku provjeru proglasiti konačnom odlukom o ispunjenosti kriterijuma;
* dozvoliti administratoru da odlučuje o kriterijumima ili mijenja M3;
* svesti M3 na jedno zbirno polje `Potpuna` / `Nepotpuna`;
* postaviti `rejected` samo zbog početnog M3 nalaza;
* dozvoliti odlučivanje o prigovoru bez sva tri člana;
* automatski utvrditi povezanost podnosioca i člana Komisije;
* automatski odbiti prijavu prema `BM-ML-006`.

Izvori: Poglavlja 3, 12, 13, 14 i 18; `BM-ML-001`–`BM-ML-006`; `BM-ML-035`; F-02.

## 24.5. Obavještenje i prigovor

Platforma **ne** smije:

* računati rok za prigovor od otvaranja poruke;
* običan odgovor na e-mail prihvatiti kao prigovor;
* dozvoliti podnošenje prigovora izvan funkcije digitalnog servisa;
* prigovor koristiti za dodavanje ili zamjenu dokumentacije;
* otključati prijavu zbog prigovora;
* osporiti kriterijum koji nije aktiviran;
* ponovo otvoriti prihvaćen ili odbijen prigovor;
* ponoviti isti prigovorni ciklus;
* automatski produžiti rok;
* održati drugu sjednicu dok postoji bilo koji blagovremen neriješen prigovor.

Izvori: Poglavlja 10 i 14; `BM-ML-023`; `BM-ML-034`; `BM-ML-036`; `BM-ML-037`.

## 24.6. Usmeno obrazloženje i ocjenjivanje

Platforma **ne** smije:

* evidentirati pravilno održano usmeno obrazloženje bez sva tri člana;
* nedolazak podnosioca automatski pretvoriti u eliminaciju ili ocjenu kriterijuma 10;
* zapisnik koristiti za dopunu prijave;
* blokirati čuvanje nacrta ocjena prije usmenog obrazloženja;
* dozvoliti završavanje ocjena prije evidentiranog završetka usmenog obrazloženja;
* dozvoliti završavanje bez svih deset ocjena;
* omogućiti izmjenu zaključane ocjene;
* spojiti djelimične ocjene prethodnog i zamjenskog člana;
* koristiti četvrtu ocjenu;
* članovima otvoriti tuđe ocjene prije završetka cijelog ciklusa;
* predsjedniku dati privilegovan raniji uvid;
* koristiti zaokruženu vrijednost za prag ili rang.

Izvori: Poglavlja 15, 16 i 19; `BM-ML-003`; `BM-ML-007`; `BM-ML-038`–`BM-ML-041`; O-01.

## 24.7. Bodovi, rangiranje i raspodjela

Platforma **ne** smije:

* dodijeliti +1 samo za Info dan ili samo za obuku;
* dodijeliti više od šest dodatnih bodova;
* sabrati inovativni i zeleni bod kao 3+3;
* dodati bod Zavoda za zapošljavanje;
* uvesti četvrti eliminatorni kriterijum;
* ručno mijenjati izračunate bodove ili proizvoljno mijenjati rang;
* koristiti vrijeme podnošenja, broj prijave, tehnički ID ili abecedu kao tie-break;
* automatski dodijeliti maksimalni ili preostali iznos;
* dozvoliti iznos iznad traženog, procentualnog limita ili preostalog budžeta;
* dozvoliti da zbir raspodjele premaši budžet Poziva;
* automatski ponuditi umanjeni iznos ili pretpostaviti saglasnost podnosioca.

Izvori: Poglavlja 17–21; `BM-ML-042`–`BM-ML-048`.

## 24.8. Arhiviranje i granica V1

Platforma **ne** smije:

* automatski arhivirati Poziv;
* dozvoliti arhiviranje prije završetka svih preduslova;
* dozvoliti administratoru da arhivira Poziv;
* ponovo otvoriti arhivirani Poziv bez novog odobrenog pravila;
* arhiviranje tretirati kao brisanje ili kao novi status prijave;
* arhiviranjem mijenjati status prijave;
* automatski brisati podatke zbog proteka vremena;
* arhiviranje usloviti ugovorom, isplatom, realizacijom, M4/M4a ili de minimis dokumentacijom;
* u V1 generisati ili objavljivati službene akte čiji je detaljan tok odložen.

Izvori: Poglavlja 1, 4, 23, 25 i 28; F-05; `BM-ML-051`; `BM-ML-054`; `BM-ML-056`–`BM-ML-058`; `KN-PATCH-BM-010`.

## 24.9. Prihvatni kriterijum — objedinjene zabrane

### 24.9.1 — Zabrane ne uvode nova pravila

**Ako:** se provjerava bilo koja zabrana iz §24.1–§24.8.

**Kada:** Platforma blokira ili ne nudi radnju.

**Onda:** ishod je isti kao u izvornom poglavlju 1–23. Ovo poglavlje **ne** mijenja poslovno pravilo.

Izvor: Poglavlja 1–23.

---

# 25. Granica V1

Status poglavlja: USVOJENO

Ovo poglavlje **sistematski objedinjuje** šta ulazi i šta ne ulazi u V1. **Ne** mijenja granicu iz `KN-BM-002` v1.0.2 i `KN-PATCH-BM-010`. Sažetak iz Poglavlja 1 ostaje važeći.

## 25.1. U V1 ulazi

* godišnja instanca;
* prvi Poziv;
* eventualni drugi Poziv;
* priprema i čuvanje Poziva;
* ručno objavljivanje;
* rok od 20 dana;
* nacrt prijave;
* M1a/M1b i M2;
* dokumentacioni paketi;
* konačno podnošenje i zaključavanje;
* pristup Komisije nakon isteka roka;
* administrativna provjera i M3;
* obavještenje o nepotpunosti;
* prigovor;
* usmeno obrazloženje u usvojenoj funkcionalnoj granici;
* individualno ocjenjivanje;
* zamjena člana u dijelu ocjena;
* dodatni bodovi;
* eliminatorni kriterijumi;
* obračun i prag;
* rangiranje i izjednačenje;
* evidentiranje konačnog rezultata;
* evidentiranje raspodjele;
* zaključivanje i arhiviranje Poziva.

## 25.2. Van V1 je

* generisanje ugovora;
* dostavljanje i potpisivanje ugovora;
* upravljanje ugovorom;
* isplata sredstava;
* realizacija projekta;
* praćenje realizacije i ugovornih obaveza;
* operativno pribavljanje žiro računa prije ugovora;
* podnošenje i obrada M4/M4a;
* fakture i bankovni izvodi nakon realizacije;
* odobravanje izvještaja;
* povraćaj sredstava;
* de minimis obrazac i njegova obrada;
* detaljan lifecycle službenih akata;
* javni PDF tok Odluke;
* dostavljanje rješenja;
* pravni lijekovi nakon konačne raspodjele;
* konačnost i izvršnost službenih akata.

## 25.3. Poslovne granice koje ostaju evidentirane

`BM-ML-011`, `BM-ML-018`, `BM-ML-032`, `BM-ML-056` i `BM-ML-057` ostaju **relevantne poslovne činjenice**. Njihovo navođenje **ne** uvodi operativne ekrane ili tokove van V1.

* `BM-ML-011` — neregistrovano fizičko lice smije podnijeti prijavu; registracija, poreska evidencija i žiro do ugovora nijesu V1 proces.
* `BM-ML-018` — neizvršeno ranije izvještavanje ostaje eliminatorni razlog u konkursu; modul M4/M4a tekućeg projekta nije V1.
* `BM-ML-032` — dokaz o žiro računu nije obavezan uz početnu prijavu; operativna provjera pred ugovor nije V1.
* `BM-ML-056` — prihvatljivost troškova počinje stvarnim datumom ugovora iz člana 26; ugovor se ne zaključuje u V1.
* `BM-ML-057` — M4/M4a nijesu dio početne prijave; izvještavanje nakon realizacije nije V1.

`BM-ML-008` ostaje poslovna obaveza izjava o tajnosti i sukobu interesa. V1 **ne** uvodi ekran elektronskog potpisivanja tih izjava. Način potpisivanja ostaje van platforme dok se ne odobri poseban korak. Detalj: §25.6.3.

`BM-ML-016` i `BM-ML-017` ostaju važeća pravila o prihvatljivim i neprihvatljivim troškovima. U V1 podnosilac unosi plan nabavki u M2, a Komisija provjerava usklađenost. Platforma **ne** donosi automatsku odluku o opravdanosti troška i **ne** uvodi katalog kontrole prije ugovora.

## 25.4. Javni rezultat

U V1:

* evidentira se konačan rezultat;
* evidentira se konačna rang-lista i raspodjela;
* podaci se zaključavaju i arhiviraju.

U V1 se **ne** uvodi:

* automatsko generisanje Odluke o raspodjeli;
* objavljivanje službene Odluke;
* javni PDF;
* pojedinačna rješenja;
* javni prikaz koji nije odobren poslovnim modelom.

Pitanje 10 ostaje **odloženo**. Detalj: Poglavlje 28.

## 25.5. Kontrolna tabela granice V1

| Funkcionalnost | U V1 | Van V1 | Izvor |
|----------------|------|--------|-------|
| Godišnja instanca i prvi Poziv | DA | — | Poglavlja 5 i 6; `BM-ML-053` |
| Drugi Poziv, ručno | DA | automatsko kreiranje/objava | Poglavlje 22; `BM-ML-049`–`BM-ML-052` |
| Prijava, M1a/M1b, M2, prilozi, podnošenje | DA | — | Poglavlja 7–10 |
| Administrativna provjera, M3, prigovor | DA | — | Poglavlja 13 i 14 |
| Usmeno obrazloženje, ocjene, dodatni bodovi | DA | — | Poglavlja 15–17 |
| Eliminatorni razlozi, prag, rang, raspodjela | DA | — | Poglavlja 18–21 |
| Zaključivanje i arhiviranje | DA | automatsko arhiviranje | Poglavlje 23; F-05 |
| Evidentiranje konačnog rezultata i rang-liste | DA | javni PDF / objava Odluke | Poglavlja 20, 21 i 28; pitanje 10 |
| Izjave Komisije (`BM-ML-008`) | poslovna obaveza evidentirana | elektronski tok potpisivanja | §25.3; §25.6.3; Poglavlje 28 |
| Žiro račun uz početnu prijavu | opciono, nije obavezan | operativna provjera pred ugovor | `BM-ML-032`; Poglavlje 9 |
| Ugovor, isplata, realizacija | — | DA | `BM-ML-056`; `KN-PATCH-BM-010` |
| M4/M4a, fakture, izvodi, izvještaji, povraćaj | — | DA | `BM-ML-057`; `BM-ML-018` |
| De minimis obrazac i obrada | — | DA | pitanje 3; Poglavlje 28 |
| Službeni akti, rješenja, pravni lijekovi | — | DA | pitanje 10; Poglavlje 28 |

## 25.6. Prihvatni kriterijumi — granica V1

### 25.6.1 — Van V1 nije V1 funkcija

**Ako:** korisnik pokuša pokrenuti ugovor, isplatu, realizaciju, M4/M4a, de minimis obradu, javni PDF Odluke ili dostavu rješenja.

**Kada:** Platforma ocjenjuje obuhvat V1.

**Onda:** radnja nije V1 funkcija ovog profila. Navođenje `BM-ML-011`, `BM-ML-018`, `BM-ML-032`, `BM-ML-056` i `BM-ML-057` ne uvodi te procese.

Izvor: `KN-BM-002` §2.6; `KN-PATCH-BM-010`; §1.5.1.

### 25.6.2 — Javni rezultat bez službenih akata

**Ako:** je raspodjela potvrđena i Poziv arhiviran.

**Kada:** se traži javni PDF ili objava službene Odluke.

**Onda:** V1 evidentira konačan rezultat, rang-listu i raspodjelu. Ne generiše i ne objavljuje službene akte. Pitanje 10 ostaje odloženo.

Izvor: Poglavlja 20, 21, 23 i 28.

### 25.6.3 — Izjave članova Komisije van posebnog V1 toka

**Ako:** poslovno pravilo `BM-ML-008` zahtijeva izjave članova.

**Kada:** se razmatra V1 funkcionalni obuhvat.

**Onda:** `BM-ML-008` ostaje važeća poslovna obaveza: svaki član potpisuje izjavu o tajnosti podataka i izjavu o sprečavanju sukoba interesa. Način potpisivanja nije određen poslovnim modelom. `KN-FS-002` **ne** uvodi poseban ekran, elektronski potpis, upload ili workflow potpisivanja izjava. Platforma **ne** smije pretpostaviti da je izjava elektronski potpisana. Buduća funkcionalna razrada zahtijeva odobren izvor.

Izvor: `BM-ML-008`; §25.3.

---

# 26. Objedinjeni prihvatni kriterijumi

Status poglavlja: USVOJENO

Ovo poglavlje je **centralni indeks** prihvatnih kriterijuma iz Poglavlja 5–23. **Ne** duplira pune tekstove. Svaki red upućuje na izvorni Ako/Kada/Onda kriterijum.

Kriterijumi Poglavlja 1, 3 i 4 ostaju u tim poglavljima. Završni kontrolni kriterijumi u §26.2 samo **provjeravaju** već usvojene zabrane i granicu V1.

## 26.1. Indeks kriterijuma Poglavlja 5–23

Ukupno redova ovog indeksa: **142**. Nema preskakanja i nema duplikata.

| Oznaka | Oblast | Sažetak uslova | Očekivani rezultat | Poglavlje | Izvor |
|--------|--------|----------------|--------------------|-----------|-------|
| 5.9.1 | Instanca | Administrator kreira godišnju instancu | Instanca evidentirana | 5 | `BM-KN-002` |
| 5.9.2 | Instanca | Kreira se prvi Poziv | Pripada instanci tekuće godine | 5 | `BM-ML-053`; `BM-ML-050` |
| 5.9.3 | Instanca | Zbir budžeta bi premašio godišnji iznos | Potvrda blokirana | 5 | Poglavlje 5 |
| 5.9.4 | Zavodni broj | Administrator unosi broj iz pisarnice | Broj sačuvan; nije generisan | 5 | `BM-ML-033`; `BM-ML-053` |
| 5.9.5 | Zavodni broj | Broj još nije unesen | Platforma ga ne generiše | 5 | `BM-ML-053` |
| 5.9.6 | Čuvanje | Čuva se nacrt Poziva | Nije objavljen; rok ne kreće | 5 | `BM-ML-033` |
| 5.9.7 | Komisija | Provjera bez tri formalna mjesta | Administrativna provjera blokirana | 5 | `BM-ML-001`; F-02 |
| 6.8.1 | Objava | Administrator izričito objavljuje | Poziv objavljen; rok kreće | 6 | `BM-ML-033` |
| 6.8.2 | Objava | Nema zavodnog broja | Objava blokirana | 6 | `BM-ML-053` |
| 6.8.3 | Objava | Komisija nije kompletna | Objava dozvoljena | 6 | F-02 |
| 6.8.4 | Objava | Komisija nije kompletna | Neblokirajuće upozorenje | 6 | F-02 |
| 6.8.5 | Prvi Poziv | Ručna objava u drugom kvartalu | Datum ne bira Platforma | 6 | `BM-ML-053` |
| 6.8.6 | Prvi Poziv | Očekuje se automatska objava | Nema automatske objave | 6 | `BM-ML-033`; `BM-ML-053` |
| 6.8.7 | Rok | Objavljen Poziv | Rok 20 kalendarskih dana | 6 | `BM-ML-033` |
| 6.8.8 | Rok | Istek | dvadeseti naredni kalendarski dan u 23:59:59 | 6 | `BM-ML-033` |
| 6.8.9 | Rok | Istek pada na neradni dan | Rok se ne pomjera | 6 | `BM-ML-033` |
| 6.8.10 | Rok | Pokušaj podnošenja poslije isteka | Zabranjeno | 6 | `BM-ML-033` |
| 6.8.11 | Nacrt | Istek bez podnošenja | Ostaje `draft` / U pripremi; nije `rejected` | 6 | `BM-ML-024` |
| 6.8.12 | Kanali | Objava na digitalnom servisu | Nema automatske objave na drugim kanalima | 6 | `BM-ML-033` |
| 7.9.1 | Prijava | Poziv nije objavljen | Platforma blokira kreiranje odnosno započinjanje prijave | 7 | `BM-ML-019` |
| 7.9.2 | Prijava | Podnosilac otvara prijavu | Vidi samo svoju | 7 | `BM-ML-054` |
| 7.9.3 | Nacrt | Rok traje | Uređivanje dozvoljeno; živi `is_registered`; zaključani tip, namjera, faza i obrazac | 7 | `BM-ML-021` |
| 7.9.4 | Nacrt | Rok traje | Brisanje nacrta dozvoljeno | 7 | `BM-ML-021` |
| 7.9.5 | Drugi Poziv | Isti podnosilac | Nova prijava bez prenosa | 7 | `BM-ML-014`; `BM-ML-052` |
| 7.9.6 | Kategorija | Pokušaj ručne promjene oblika, namjere, obrasca ili faze | Zabranjeno | 7 | F-06; `BM-ML-009`; `BM-ML-025` |
| 7.9.7 | Kategorija | Nepodržani identitet ili nedovoljni podaci | Nema proizvoljnog paketa ni `ostalo`; kreiranje i podnošenje zabranjeni | 7 | F-06 |
| 8.6.1 | M1a | Budući ili registrovani preduzetnik | Prikazuje se M1a | 8 | `BM-ML-025` |
| 8.6.2 | M1b | Planirano ili registrovano privredno društvo | Prikazuje se M1b; blok registrovanog društva samo ako je društvo registrovano | 8 | `BM-ML-025` |
| 8.6.3 | M1 | Unos oblasti | Obavezno slobodno tekstualno polje | 8 | `BM-ML-026` |
| 8.6.4 | M1 | Unos djelatnosti | Obavezno odvojeno polje; nema šifrarnika | 8 | `BM-ML-026` |
| 8.6.5 | M2 t.7 | Izbor odgovora | Tačno jedan odgovor | 8 | `BM-ML-027` |
| 8.6.6 | M2 t.7 | Izbor Drugo | Obavezan tekst | 8 | `BM-ML-027` |
| 8.6.7 | M2 t.22 | Tabela nabavki | Obavezna; nije nova tačka | 8 | `BM-ML-028` |
| 8.6.8 | M2 t.22 | Više stavki | Zbir računa Platforma | 8 | `BM-ML-028` |
| 8.6.9 | M2 t.22 | Prazna tabela | Podnošenje blokirano | 8 | `BM-ML-028` |
| 9.9.1 | Dokumenti | Utvrđena kategorija | Tačno odgovarajući od četiri paketa | 9 | `BM-ML-029` |
| 9.9.2 | Dokumenti | Društvo u razvoju | IOPPD ili potvrda, ne oba | 9 | `BM-ML-031` |
| 9.9.3 | Dokumenti | Nema dokaza o žiro računu | Nije obavezno; prijava nije nepotpuna samo zbog toga | 9 | `BM-ML-032` |
| 9.9.4 | Dokumenti | Tip priloga | Jedan dokument po tipu | 9 | `BM-ML-030` |
| 9.9.5 | Dokumenti | Nacrt; rok traje | Zamjena dokumenta dozvoljena | 9 | `BM-ML-030` |
| 10.5.1 | Podnošenje | Nedostaju obavezna polja | Podnošenje blokirano; PIB i CRPS ne blokiraju planirano društvo | 10 | `BM-ML-022` |
| 10.5.2 | Podnošenje | Nedostaju dokumenti | Upozorenje; nije konačna odluka Komisije | 10 | `BM-ML-022` |
| 10.5.3 | Podnošenje | Izričita potvrda u roku | Status `submitted` / Podnesena | 10 | `BM-ML-023` |
| 10.5.4 | Zaključavanje | Prijava Podnesena | Izmjena, brisanje, povlačenje i ponovno podnošenje zabranjeni | 10 | `BM-ML-023` |
| 10.5.5 | Zaključavanje | Pokušaj povlačenja | Zabranjeno | 10 | `BM-ML-023` |
| 10.5.6 | Prigovor | Podnesena prijava | Prigovor ne otključava prijavu | 10 | `BM-ML-023`; `BM-ML-036` |
| 10.5.7 | Nacrt | Istek roka | Ostaje `draft` / U pripremi, samo pregled; nije `rejected` | 10 | `BM-ML-024` |
| 11.5.1 | Privatnost | Javni korisnik | Prijava, M1a/M1b, M2 i prilozi nijesu javni | 11 | `BM-ML-054` |
| 11.5.2 | Privatnost | Podnosilac | Vidi samo svoju prijavu | 11 | `BM-ML-054` |
| 11.5.3 | Privatnost | Administrator | Nema sadržajni pristup ni razlozima | 11 | `BM-ML-004` |
| 11.5.4 | Privatnost | Rok traje | Komisija nema sadržajni pristup | 11 | `BM-ML-005` |
| 11.5.5 | Pregled | Komisija nakon isteka | Pregled bez redovnog preuzimanja | 11 | `BM-ML-055` |
| 12.5.1 | Istek | Kraj roka | 23:59:59 dvadesetog narednog kalendarskog dana | 12 | `BM-ML-033` |
| 12.5.2 | Istek | Nacrt nije podnesen | Ostaje `draft` / U pripremi; nije `rejected` | 12 | `BM-ML-024` |
| 12.5.3 | Pristup | Istek; Komisija kompletna | Vidi samo Podnesene | 12 | `BM-ML-005` |
| 12.5.4 | Komisija | Nisu popunjena tri mjesta | Provjera blokirana | 12 | `BM-ML-001`; F-02 |
| 12.5.5 | Kvorum | Manje od dva prisutna | Provjera blokirana odnosno odložena | 12 | `BM-ML-002` |
| 13.5.1 | M3 | Prikaz članova | Tačno tri mjesta; predsjednik je jedan od tri | 13 | `BM-ML-001` |
| 13.5.2 | Prva sjednica | Formalno kompletna Komisija | Kvorum najmanje dva | 13 | `BM-ML-002` |
| 13.5.3 | M3 | Evidentiranje rezultata | Predsjednik upisuje tri odvojene stavke; status ostaje `submitted` | 13 | `BM-ML-035` |
| 13.5.4 | M3 | Sistemska provjera | Nije konačna odluka Komisije | 13 | `BM-ML-035` |
| 13.5.5 | M3 | Administrator | Ne odlučuje o kriterijumima i ne mijenja M3 | 13 | `BM-ML-004` |
| 13.5.6 | Prigovor | Odluka Komisije | Potrebna sva tri člana; odluka po kriterijumu | 13 | `BM-ML-003` |
| 13.5.7 | M3 | Prikaz kriterijuma | Tačno tri odvojene stavke | 13 | `BM-ML-035` |
| 13.5.8 | M3 | Evidencija po kriterijumu | Odvojeni rezultat, aktivacija, obrazloženje i trag | 13 | `BM-ML-035` |
| 13.5.9 | M3 | Početni nalaz | Ne postavlja odmah `rejected` | 13 | `BM-ML-035`; `BM-ML-020` |
| 14.6.1 | Obavještenje | Najmanje jedan aktiviran razlog | Jedno objedinjeno obavještenje na registrovanu e-mail adresu | 14 | `BM-ML-036` |
| 14.6.2 | Rok prigovora | Računanje roka | Od slanja, ne od otvaranja | 14 | `BM-ML-036` |
| 14.6.3 | Prigovor | Kanal podnošenja | Samo funkcija digitalnog servisa | 14 | `BM-ML-036` |
| 14.6.4 | Prigovor | Pokušaj nove dokumentacije | Zabranjeno; prijava se ne otključava | 14 | `BM-ML-036` |
| 14.6.5 | Prigovor | Ispitivanje | Sadržaj koji je postojao prije isteka roka | 14 | `BM-ML-036` |
| 14.6.6 | Prigovor | Odluka | Ishod po kriterijumu; `submitted` ili `rejected` | 14 | `BM-ML-036`; `BM-ML-037` |
| 14.6.7 | Prigovor | Istek bez podnošenja | Svi aktivni razlozi konačni; prijava `rejected` | 14 | `BM-ML-036` |
| 14.6.8 | Prigovor | Prihvaćen ili Odbijen | Ponovno otvaranje zabranjeno | 14 | `BM-ML-037` |
| 14.6.9 | Druga sjednica | Bilo koji neriješen blagovremen prigovor | Sjednica nije dozvoljena | 14 | `BM-ML-034` |
| 14.6.10 | Status | Ishod prigovora | Prigovor odvojen; `submitted` dok traje pravo; zatim `rejected` | 14 | `BM-ML-020` |
| 14.6.11 | Obavještenje | Više aktivnih razloga | Jedno objedinjeno obavještenje | 14 | `BM-ML-036` |
| 14.6.12 | Prigovor | Izbor kriterijuma | Može osporiti samo aktivirane | 14 | `BM-ML-036` |
| 14.6.13 | Prigovor | Odluka Komisije | Evidentira se po kriterijumu | 14 | `BM-ML-037` |
| 14.6.14 | Prigovor | Djelimično prihvatanje | Pojedinačni ishodi; `rejected` ako ostane makar jedan razlog | 14 | `BM-ML-036`; `BM-ML-037` |
| 15.7.1 | Usmeno | Zakazivanje | Termin i obavještenje evidentirani | 15 | `BM-ML-034` |
| 15.7.2 | Usmeno | Promjena termina | Nije automatska; razlog evidentiran | 15 | `BM-ML-034` |
| 15.7.3 | Usmeno | Evidencija održavanja | Sva tri člana obavezna | 15 | `BM-ML-003` |
| 15.7.4 | Usmeno | Završavanje ocjena | Blokirano dok nije evidentiran završetak | 15 | `BM-ML-039` |
| 15.7.5 | Usmeno | Nedolazak podnosioca | Nije automatska eliminacija ni ocjena 10 | 15 | `BM-ML-038` |
| 15.7.6 | Usmeno | Zapisnik | Nema dopune prijave | 15 | Poglavlje 15 |
| 16.8.1 | Ocjene | Unos | Skala 1–5; deset kriterijuma | 16 | `BM-ML-038` |
| 16.8.2 | Ocjene | Prije usmenog | Nacrt se smije čuvati | 16 | `BM-ML-039` |
| 16.8.3 | Ocjene | Završavanje | Svih 10; usmeno završeno; zatim zaključano | 16 | `BM-ML-039` |
| 16.8.4 | Ocjene | Kompletnost | Tačno tri kompletne ocjene | 16 | `BM-ML-003` |
| 16.8.5 | Zamjena | Prethodni nacrt ili zaključana ocjena | Bez spajanja djelimičnih ocjena | 16 | `BM-ML-007`; O-01 |
| 16.8.6 | Tajnost | Ciklus nije završen | Nema uvida u tuđe ocjene | 16 | `BM-ML-040` |
| 16.8.7 | Uvid | Ciklus završen | Međusobni uvid samo za čitanje | 16 | `BM-ML-040` |
| 16.8.8 | Prikaz | Zaokruživanje na 2 decimale | Prag i rang koriste punu nezaokruženu vrijednost | 16 | `BM-ML-041` |
| 17.7.1 | Dodatni bodovi | Zbir dodatnih bodova | Maksimum 6 | 17 | `BM-ML-042` |
| 17.7.2 | +1 | Samo Info dan ili samo obuka | Bod se ne dodjeljuje | 17 | `BM-ML-042` |
| 17.7.3 | +2 | Kategorija fizičkog lica | Bod prema utvrđenoj kategoriji | 17 | `BM-ML-042`; F-06 |
| 17.7.4 | +3 | Inovativna i/ili zelena ideja | Jedna kategorija +3, ne 3+3 | 17 | `BM-ML-042` |
| 17.7.5 | Dodatni bodovi | Potvrda predsjednika | Zaključano; nema bod Zavoda | 17 | `BM-ML-042` |
| 17.7.6 | Rezultat | Redoslijed | Dodatni bodovi nakon zbira prosjeka | 17 | `BM-ML-044` |
| 18.7.1 | Eliminacija | Unos razloga | Tačno tri razloga; četvrti nije uveden | 18 | `BM-ML-043` |
| 18.7.2 | Eliminacija | Aktiviran razlog ili neriješen prigovor | Ocjenjivanje blokirano; ostaje `submitted` | 18 | `BM-ML-036`; `BM-ML-043` |
| 18.7.3 | Eliminacija | Sistemska pomoć | Nije konačna odluka Komisije | 18 | `BM-ML-043` |
| 18.7.4 | Status | Konačni razlog | `rejected` uz sve konačne aktivne razloge; nema Eliminisana | 18 | `BM-ML-020` |
| 18.7.5 | Podobnost | Povezanost sa članom | Nije četvrti kriterijum; nema automatskog odbijanja | 18 | `BM-ML-006` |
| 18.7.6 | M3 | Tri kriterijuma | Prikaz tri stavke; prigovor na svaki aktivirani; bodovanje tek bez konačnog razloga | 18 | `BM-ML-035`; `BM-ML-036` |
| 19.6.1 | Obračun | Tri kompletne ocjene | Formula; maksimum 56 | 19 | `BM-ML-041`; `BM-ML-044` |
| 19.6.2 | Obračun | Nedostaje treća ocjena | Nema konačnog rezultata | 19 | `BM-ML-003` |
| 19.6.3 | Prag | Puna ocjena | Prag 30; ispod praga nije `approved`; konačni `rejected` pri potvrdi | 19 | `BM-ML-044` |
| 19.6.4 | Prikaz | 2 decimale | Ne mijenja prolaznost ni rang | 19 | `BM-ML-041` |
| 20.7.1 | Rang | Ciklus nije završen | Nema preliminarne liste | 20 | `BM-ML-045` |
| 20.7.2 | Rang | Pokušaj ručne izmjene redosljeda | Zabranjeno | 20 | `BM-ML-045` |
| 20.7.3 | Rang | Ispod praga | Nije u raspodjeli | 20 | `BM-ML-044` |
| 20.7.4 | Tie | Ista puna ocjena | Ista pozicija; sljedeći broj se preskače | 20 | `BM-ML-046` |
| 20.7.5 | Tie | Član 22 | Nema vremena, ID-a ni abecede kao tie-break | 20 | `BM-ML-046` |
| 20.7.6 | Rang | Potvrda konačne liste | Lista zaključana; `approved` ili `rejected` | 20 | `BM-ML-045` |
| 21.8.1 | Limit | Račun procenta | Osnovica je budžet konkretnog Poziva | 21 | `BM-ML-047` |
| 21.8.2 | Limit | Preklapanje kategorija | 30/20/15; najveći; ne sabiraju se; nema 20/10/5 | 21 | `BM-ML-047` |
| 21.8.3 | Raspodjela | Evidentiranje iznosa | Nema automatske dodjele maksimuma | 21 | `BM-ML-048` |
| 21.8.4 | Raspodjela | Iznos iznad traženog, procenta ili preostalog | Blokirano | 21 | `BM-ML-048` |
| 21.8.5 | Raspodjela | Zbir premašuje budžet Poziva | Potvrda blokirana | 21 | `BM-ML-048` |
| 21.8.6 | Raspodjela | Valjana potvrda | Iznosi zaključani; drugi Poziv se ne kreira automatski | 21 | `BM-ML-048`; `BM-ML-051` |
| 22.7.1 | Drugi Poziv | Nema preostalih sredstava | Ne raspisuje se; nema automatske ponude | 22 | `BM-ML-049` |
| 22.7.2 | Drugi Poziv | Ima preostalih sredstava | Obavezan; Platforma ne odlučuje sama | 22 | `BM-ML-049` |
| 22.7.3 | Drugi Poziv | Automatsko kreiranje ili objava | Zabranjeno | 22 | `BM-ML-051` |
| 22.7.4 | Instanca | Ručno kreiranje | Ista godišnja instanca; prvi Poziv nepromijenjen | 22 | `BM-ML-050` |
| 22.7.5 | Čuvanje | Nacrt drugog Poziva | Nije objava | 22 | `BM-ML-051` |
| 22.7.6 | Budžet | Iznos iznad preostalih sredstava | Blokirano; razlog prikazan | 22 | `BM-ML-049` |
| 22.7.7 | Limit | Drugi Poziv | Osnovica 30/20/15 je budžet tog Poziva | 22 | `BM-ML-047` |
| 22.7.8 | Objava | Nema zavodnog broja ili obaveznih podataka | Blokirano | 22 | `BM-ML-033`; `BM-ML-053` |
| 22.7.9 | Objava | Izričita objava | Sopstveni rok; nema automatske objave krajem kvartala | 22 | `BM-ML-033`; `BM-ML-051` |
| 22.7.10 | Prijava | Isti podnosilac na drugom Pozivu | Nova prijava; ništa se ne prenosi | 22 | `BM-ML-014`; `BM-ML-052` |
| 23.8.1 | Arhiva | Administrator pokušava zaključiti | Radnja nije dostupna | 23 | F-05; `BM-ML-004` |
| 23.8.2 | Arhiva | Istek, sjednica, ocjene, budžet ili kvartal | Nema automatskog arhiviranja | 23 | F-05 |
| 23.8.3 | Arhiva | Neriješen prigovor | Blokirano | 23 | F-05 |
| 23.8.4 | Arhiva | Nezavršene ili nepotpune ocjene | Blokirano | 23 | F-05 |
| 23.8.5 | Arhiva | Nema potvrđene rang-liste | Blokirano | 23 | F-05 |
| 23.8.6 | Arhiva | Nema iznosa ili je budžet prekoračen | Blokirano | 23 | F-05 |
| 23.8.7 | Arhiva | Svi preduslovi; izričita potvrda | Arhiviran; samo pregled | 23 | F-05; `BM-ML-058` |
| 23.8.8 | Arhiva | Pokušaj izmjene ili otvaranja | Zabranjeno | 23 | F-05 |
| 23.8.9 | Arhiva | Čitanje arhive | Nije brisanje ni javnost | 23 | `BM-ML-054`; `BM-ML-058` |
| 23.8.10 | V1 | Arhiviranje | Ne zavisi od ugovora/M4; ne kreira drugi Poziv | 23 | F-05; `KN-PATCH-BM-010` |

Brojanje indeksa: 5.9 (7) + 6.8 (12) + 7.9 (7) + 8.6 (9) + 9.9 (5) + 10.5 (7) + 11.5 (5) + 12.5 (5) + 13.5 (9) + 14.6 (14) + 15.7 (6) + 16.8 (8) + 17.7 (6) + 18.7 (6) + 19.6 (4) + 20.7 (6) + 21.8 (6) + 22.7 (10) + 23.8 (10) = **142**.

## 26.2. Završni kontrolni kriterijumi

Ovi kriterijumi **ne** uvode nova pravila. Provjeravaju objedinjene zabrane i granicu V1.

### 26.2.1 — Katalog pet statusa prijave

**Ako:** prijava postoji.

**Kada:** Platforma određuje status.

**Onda:** vrijednost je `draft`, `submitted`, `evaluated`, `approved` ili `rejected`. Aktiviranje eliminatornog razloga ne postavlja odmah `rejected`. Tokom otvorenog prava na prigovor prijava ostaje `submitted`. Nema prelaza `rejected` → `submitted`. Nepodneseni nacrt nakon isteka roka ostaje `draft`. `evaluated` nastaje tek nakon tri kompletne ocjene i obračuna. `approved` i konačni `rejected` rezultata nastaju pri potvrdi rang-liste i raspodjele. Rezultati M3 i prigovor ostaju odvojeni. Arhiviranje Poziva ne mijenja status prijave.

Izvor: 4.11.1; 14.6.10; 18.7.4; `BM-ML-020`; `KN-PATCH-FS-007`.

### 26.2.2 — Privatnost prijave

**Ako:** javni korisnik ili treće lice traži sadržaj prijave.

**Kada:** se prikazuje sadržaj.

**Onda:** prijava, M1a/M1b, M2 i prilozi nijesu javni.

Izvor: 11.5.1; `BM-ML-054`.

### 26.2.3 — Administrator bez sadržajnog pristupa

**Ako:** Administrator Konkursa otvara prijavu ili razloge nepotpunosti.

**Kada:** se traži sadržaj.

**Onda:** sadržajni pristup je zabranjen.

Izvor: 11.5.3; 13.5.5; `BM-ML-004`.

### 26.2.4 — Komisija bez pristupa tokom roka

**Ako:** rok traje.

**Kada:** član Komisije traži sadržaj prijave.

**Onda:** pristup je zabranjen. Prijave `U pripremi` ostaju nedostupne i nakon isteka.

Izvor: 11.5.4; 12.5.3; `BM-ML-005`.

### 26.2.5 — M3 sa tri člana

**Ako:** se prikazuje elektronski M3.

**Kada:** se određuju mjesta Komisije.

**Onda:** koriste se tačno tri mjesta. Predsjednik je jedan od tri. Četvrto mjesto se ne koristi.

Izvor: 13.5.1; `BM-ML-001`.

### 26.2.6 — Kvorum dva za administrativnu provjeru

**Ako:** počinje prva sjednica ili administrativna provjera.

**Kada:** nijesu prisutna najmanje dva člana, ili nijesu popunjena tri mjesta.

**Onda:** početak je blokiran.

Izvor: 12.5.4; 12.5.5; 13.5.2; `BM-ML-001`; `BM-ML-002`.

### 26.2.7 — Sva tri člana za prigovor, usmeno i ocjenjivanje

**Ako:** se odlučuje o prigovoru, evidentira usmeno obrazloženje ili završava ocjenjivanje.

**Kada:** nijesu učestvovala sva tri člana, odnosno nema tačno tri kompletne ocjene.

**Onda:** radnja je blokirana ili nije pravilno održana.

Izvor: 13.5.6; 15.7.3; 16.8.4; `BM-ML-003`.

### 26.2.8 — E-mail obavještenje i prigovor preko digitalnog servisa

**Ako:** je aktiviran najmanje jedan eliminatorni razlog ili se podnosi prigovor.

**Kada:** se bira kanal.

**Onda:** obavještenje ide na registrovanu e-mail adresu. Prigovor se podnosi samo preko digitalnog servisa. Odgovor na e-mail nije prigovor.

Izvor: 14.6.1; 14.6.3; `BM-ML-036`.

### 26.2.9 — Zabrana dopune kroz prigovor

**Ako:** podnosilac pokuša dodati ili zamijeniti dokument kroz prigovor.

**Kada:** se prigovor obrađuje.

**Onda:** dopuna je zabranjena. Prijava ostaje zaključana.

Izvor: 10.5.6; 14.6.4; `BM-ML-023`; `BM-ML-036`.

### 26.2.10 — Nacrt ocjena prije usmenog

**Ako:** usmeno obrazloženje još nije završeno.

**Kada:** član čuva ocjene.

**Onda:** čuvanje nacrta je dozvoljeno.

Izvor: 16.8.2; `BM-ML-039`.

### 26.2.11 — Zabrana završavanja prije usmenog

**Ako:** usmeno obrazloženje prijave nije evidentirano kao završeno.

**Kada:** član pokrene Završi ocjenjivanje.

**Onda:** završavanje je blokirano.

Izvor: 15.7.4; 16.8.3; `BM-ML-039`.

### 26.2.12 — Tačno tri kompletne ocjene

**Ako:** se računa konačni rezultat.

**Kada:** nema tačno tri kompletne zaključane ocjene.

**Onda:** rezultat se ne izračunava. Četvrta ocjena se ne koristi.

Izvor: 16.8.4; 19.6.2; `BM-ML-003`.

### 26.2.13 — Maksimum 56 i prag 30

**Ako:** postoje tri kompletne ocjene i dodatni bodovi.

**Kada:** se računa konačna ocjena.

**Onda:** maksimum je 56. Prag podrške je 30.

Izvor: 19.6.1; 19.6.3; `BM-ML-044`.

### 26.2.14 — Puna nezaokružena vrijednost

**Ako:** se određuje prag ili rang.

**Kada:** prikaz koristi 2 decimale.

**Onda:** obračun koristi punu nezaokruženu vrijednost.

Izvor: 16.8.8; 19.6.4; `BM-ML-041`.

### 26.2.15 — Maksimum šest dodatnih bodova

**Ako:** se dodjeljuju dodatni bodovi.

**Kada:** zbir bi prešao 6, ili se dodaje bod Zavoda, ili se +1 daje samo za Info dan, ili se +3 sabira kao 3+3.

**Onda:** nedozvoljeni bod se ne dodjeljuje. Maksimum ostaje 6.

Izvor: 17.7.1–17.7.5; `BM-ML-042`.

### 26.2.16 — Tri eliminatorna kriterijuma

**Ako:** se evidentira eliminatorni razlog iz člana 20.

**Kada:** predsjednik upisuje razlog.

**Onda:** dozvoljena su tačno tri razloga na M3. Četvrti eliminatorni kriterijum nije uveden. Pravo na prigovor postoji za svaki aktivirani razlog. `rejected` nastaje tek kada je makar jedan razlog konačno aktivan.

Izvor: 18.7.1; `BM-ML-043`.

### 26.2.17 — Rang i tie-break

**Ako:** postoji ista puna ocjena ili se pokuša ručno mijenjati redoslijed.

**Kada:** se formira rang.

**Onda:** ručna izmjena je zabranjena. Tie-break nije vrijeme, broj prijave, tehnički ID ni abeceda. Primjenjuje se član 22 / `BM-ML-046`.

Izvor: 20.7.2; 20.7.4; 20.7.5.

### 26.2.18 — Limiti 30/20/15

**Ako:** se računa procentualni limit.

**Kada:** Platforma određuje osnovicu.

**Onda:** osnovica je budžet konkretnog Poziva. Limiti su 30/20/15. Procenti se ne sabiraju.

Izvor: 21.8.1; 21.8.2; `BM-ML-047`.

### 26.2.19 — Zabrana automatske dodjele

**Ako:** se evidentira dodijeljeni iznos.

**Kada:** postoji izračunati maksimum ili preostali budžet.

**Onda:** Platforma ne upisuje automatski taj iznos i ne pretpostavlja saglasnost podnosioca.

Izvor: 21.8.3; `BM-ML-048`.

### 26.2.20 — Drugi Poziv bez automatskog kreiranja

**Ako:** postoje preostala sredstva ili je prvi Poziv arhiviran.

**Kada:** se očekuje automatsko kreiranje.

**Onda:** drugi Poziv se ne kreira automatski.

Izvor: 22.7.3; 23.8.10; `BM-ML-051`.

### 26.2.21 — Nova prijava bez prenosa

**Ako:** isti podnosilac kreira prijavu na drugom Pozivu.

**Kada:** se otvara nova prijava.

**Onda:** ništa se iz prve prijave ne prenosi automatski. Nova prijava počinje kao `draft`.

Izvor: 22.7.10; `BM-ML-052`.

### 26.2.22 — Ručno arhiviranje

**Ako:** predsjednik pokreće zaključivanje.

**Kada:** nijesu ispunjeni svi preduslovi, ili akciju pokreće Administrator, ili sistem pokušava automatski.

**Onda:** arhiviranje je blokirano ili nije dostupno. Izvršava se samo ručnom izričitom potvrdom predsjednika. Arhiviranje ne mijenja status prijave.

Izvor: 23.8.1; 23.8.2; 23.8.7; F-05.

### 26.2.23 — Granica V1

**Ako:** se pokrene ugovor, isplata, realizacija, M4/M4a, de minimis obrada ili javni PDF službenog akta.

**Kada:** Platforma ocjenjuje V1.

**Onda:** radnja nije V1 funkcija.

Izvor: 25.6.1; 1.5.1; `KN-PATCH-BM-010`.

---

# 27. Matrica sljedivosti

Status poglavlja: USVOJENO

Matrica povezuje tačno `BM-ML-001`–`BM-ML-058` sa razradom u `KN-FS-002`. **Ne** uvodi `BM-ML-059`. **Ne** uvodi `FR-ML-*` ni `FS-ML-*`. **Ne** izmišlja implementaciju.

## 27.1. Matrica BM-ML-001–BM-ML-058

| BM oznaka | Naziv poslovnog pravila | Matično BM poglavlje | FS poglavlje/pododjeljak | Prihvatni kriterijumi | Funkcionalni rezultat | V1 status |
|-----------|-------------------------|----------------------|--------------------------|-----------------------|-----------------------|-----------|
| BM-ML-001 | Sastav Komisije | 6.1 | §3.2; §13.1; 13.5.1 | 3.12.4; 13.5.1; 26.2.5 | Tačno tri mjesta; predsjednik je jedan od tri; M3 bez četvrtog mjesta | U V1 |
| BM-ML-002 | Radni kvorum i administrativna provjera | 6.1 | §3.2; §12.4; §13.2 | 3.12.6; 12.5.5; 13.5.2; 26.2.6 | Provjera uz kvorum najmanje dva, nakon formalno tri mjesta | U V1 |
| BM-ML-003 | Obavezno prisustvo svih članova | 6.1 | §3.2; §14; §15; §16 | 13.5.6; 15.7.3; 16.8.4; 26.2.7 | Prigovor, usmeno i konačne ocjene zahtijevaju sva tri člana | U V1 |
| BM-ML-004 | Pristup Administratora Konkursa | 6.2 | §3.4; §11.2 | 3.12.1; 11.5.3; 26.2.3 | Samo zbirni broj tokom roka; nema sadržaja ni razloga | U V1 |
| BM-ML-005 | Vremenska granica pristupa Komisije | 6.2 | §3.5; §11; §12 | 3.12.2; 11.5.4; 12.5.3; 26.2.4 | Nema sadržaja dok rok traje; nakon isteka samo Podnesene | U V1 |
| BM-ML-006 | Zabrana učešća povezanog člana Komisije | 6.3 | §18.5 | 18.7.5 | Komisija utvrđuje; nije 4. kriterijum; nema automatskog odbijanja | U V1 |
| BM-ML-007 | Mandat i formalna zamjena člana Komisije | 6.3 | §3.7; §16.4 | 16.8.5 | Zamjena sa tragom; bez spajanja djelimičnih ocjena | U V1 |
| BM-ML-008 | Izjave članova Komisije | 6.3 | §25.3 | 25.6.3 | Poslovna obaveza je evidentirana, bez posebnog V1 toka potpisivanja | Poslovna granica; nema V1 ekrana |
| BM-ML-009 | Kategorije podnosilaca i teritorijalni uslov | 7.1 | §7.5 | 7.9.6; 7.9.7 | FL, preduzetnik i društvo DOO/AD/OD/KD; namjera; upozorenje za teritoriju; nema `ostalo` | U V1 |
| BM-ML-010 | Započinjanje i razvoj biznisa | 7.1 | §7.5; §7.7 | 7.9.6; 7.9.7 | Odluka ostaje pravilo; V1 izbor registrovanog; Komisija provjerava | U V1 |
| BM-ML-011 | Naknadna registracija fizičkog lica | 7.2 | §1.3; §7; §25.3 | 1.5.1; 9.9.3; 25.6.1 | Smije podnijeti prijavu; operativna provjera pred ugovor van V1 | Poslovna granica; u V1 samo početna prijava |
| BM-ML-012 | Nosilac biznisa u društvu | 7.3 | §7.5 | 7.9.6; 8.6.2 | Nosilac ostaje pravilo Odluke; formalni blok društva obavezan samo za registrovano društvo | U V1 |
| BM-ML-013 | Formalni podnosilac društva i ovlašćeno lice | 7.3 | §7.5; §8.1 | 8.6.2 | M1b za planirano ili registrovano društvo; registrovano društvo ostaje formalni podnosilac | U V1 |
| BM-ML-014 | Jedan biznis plan po Pozivu | 7.4 | §7.3; §22.5 | 7.9.5; 22.7.10; 26.2.21 | Najviše jedna konačno podnesena prijava po Pozivu | U V1 |
| BM-ML-015 | Prioritetne oblasti | 7.5 | §18.1 | 18.7.1 | Treći eliminatorni razlog; Komisija utvrđuje | U V1 |
| BM-ML-016 | Prihvatljivi troškovi | 7.6 | §8.4; §25.3 | 8.6.8; 8.6.9 | Unos nabavki u M2; Komisija provjerava; Platforma ne odlučuje o opravdanosti | U V1 unos; bez auto-kataloga |
| BM-ML-017 | Neprihvatljivi troškovi i početak prihvatljivosti | 7.6 | §1.3; §25.3; §28.2 | 1.5.1; 25.6.1 | Granica od datuma ugovora; nema V1 kontrole prije ugovora | Poslovna granica |
| BM-ML-018 | Ranije finansirani biznis planovi | 7.7 | §1.3; §18.1 | 18.7.1; 25.6.1 | Eliminatorni razlog 2; bez V1 modula M4/M4a tekućeg projekta | U V1 kao razlog; modul izvještaja van V1 |
| BM-ML-019 | Elektronsko podnošenje prijave | 8.1 | §7.1; §10 | 7.9.1; 10.5.3 | Prijava samo preko digitalnog servisa u roku | U V1 |
| BM-ML-020 | Osnovna stanja prijave | 8.2 | §4.2; §10 | 4.11.1; 26.2.1 | Pet statusa: draft, submitted, evaluated, approved, rejected | U V1 |
| BM-ML-021 | Upravljanje prijavom U pripremi | 8.3 | §7.4 | 7.9.3; 7.9.4 | Uređivanje nacrta; živi `is_registered`; tip, namjera, faza i obrazac ostaju zaključani | U V1 |
| BM-ML-022 | Kontrola prije podnošenja | 8.4 | §10.1 | 10.5.1; 10.5.2 | Blokada obaveznih polja; planirano društvo bez PIB/CRPS bloka; upozorenje za dokumente | U V1 |
| BM-ML-023 | Konačno podnošenje i zaključavanje | 8.5 | §10.2 | 10.5.3; 10.5.4; 10.5.5 | Izričita potvrda; podnesena ostaje zaključana i nakon izmjene naloga | U V1 |
| BM-ML-024 | Prijava U pripremi nakon isteka roka | 8.6 | §6.7; §12 | 6.8.11; 12.5.2 | Ostaje U pripremi, samo pregled | U V1 |
| BM-ML-025 | Izbor obrasca M1a ili M1b | 9.2 | §8.1 | 8.6.1; 8.6.2 | M1a za preduzetnika; M1b za planirano ili registrovano društvo; nema ručnog selektora | U V1 |
| BM-ML-026 | Obavezni podaci oblast i djelatnost | 9.3 | §8.2 | 8.6.3; 8.6.4 | Dva odvojena obavezna polja; nema šifrarnika | U V1 |
| BM-ML-027 | Jednostruki izbor u tački 7 obrasca M2 | 9.4 | §8.3 | 8.6.5; 8.6.6 | Tačno jedan odgovor; Drugo zahtijeva tekst | U V1 |
| BM-ML-028 | Tabela nabavki u tački 22 obrasca M2 | 9.4 | §8.4 | 8.6.7; 8.6.8; 8.6.9 | Obavezna tabela; zbir računa Platforma | U V1 |
| BM-ML-029 | Katalog dokumentacije prema kategoriji podnosioca | 9.5 | §9 | 9.9.1 | Četiri paketa; nema proizvoljnog paketa | U V1 |
| BM-ML-030 | Jedan dokument po tipu priloga | 9.6 | §9 | 9.9.4; 9.9.5 | Jedan dokument; zamjena samo u nacrtu | U V1 |
| BM-ML-031 | IOPPD ili potvrda Poreske uprave za društvo u razvoju | 9.7 | §9 | 9.9.2 | Jedan odgovarajući dokaz, ne oba | U V1 |
| BM-ML-032 | Dokaz o žiro računu | 9.7 | §9; §1.3; §25.3 | 9.9.3; 25.6.1 | Nije obavezan uz početnu prijavu; provjera pred ugovor van V1 | U V1 opciono; operativna provjera van V1 |
| BM-ML-033 | Objavljivanje i rok za prijave | 10.2 | §6 | 6.8.1–6.8.12 | Ručna objava; 20 dana; 23:59:59; bez pomjeranja | U V1 |
| BM-ML-034 | Rokovi sjednica Komisije | 10.3 | §4.10; §13.2; §14.3; §15.1; §20 | 4.11.6; 4.11.7; 14.6.9; 15.7.1 | Platforma evidentira; ne zakazuje umjesto Komisije; treća sjednica najkasnije sedam dana; nema automatskog produženja roka druge sjednice | U V1 evidencija |
| BM-ML-035 | M3 i tri eliminatorna kriterijuma | 10.4 | §13 | 13.5.3; 13.5.7; 13.5.8; 4.11.2 | Tri odvojene stavke na M3; status ostaje submitted dok traje prigovor | U V1 |
| BM-ML-036 | Podnošenje i dejstvo prigovora | 10.5 | §14 | 14.6.1–14.6.14; 26.2.8; 26.2.9 | Objedinjeno e-mail obavještenje; jedan prigovor na aktivirane; rejected tek nakon konačnosti | U V1 |
| BM-ML-037 | Konačnost ishoda prigovora | 10.5 | §14 | 14.6.8; 14.6.13; 14.6.14 | Ishod po kriterijumu; Prihvaćen/Odbijen se ne otvara ponovo | U V1 |
| BM-ML-038 | Deset pozitivnih kriterijuma i skala | 11.1 | §16.1 | 16.8.1 | Skala 1–5; deset kriterijuma | U V1 |
| BM-ML-039 | Nacrt i završavanje individualnog ocjenjivanja | 11.2 | §16 | 16.8.2; 16.8.3; 26.2.10; 26.2.11 | Nacrt prije usmenog; završavanje tek nakon usmenog | U V1 |
| BM-ML-040 | Tajnost i međusobni uvid | 11.3 | §16.5 | 16.8.6; 16.8.7 | Uvid tek nakon cijelog ciklusa; samo čitanje | U V1 |
| BM-ML-041 | Prosjek i preciznost obračuna | 11.4 | §16.6; §19 | 16.8.8; 19.6.1; 26.2.14 | Puna nezaokružena vrijednost za prag i rang | U V1 |
| BM-ML-042 | Dodatni bodovi | 11.5 | §17 | 17.7.1–17.7.6; 26.2.15 | +1/+2/+3; maksimum 6; nema boda Zavoda | U V1 |
| BM-ML-043 | Eliminatorni kriterijumi | 11.6 | §18 | 18.7.1–18.7.6; 26.2.16 | Tačno tri razloga na M3; nema stanja Eliminisana; konačni razlog daje rejected | U V1 |
| BM-ML-044 | Konačna ocjena i prag podrške | 11.7 | §19 | 19.6.1; 19.6.3; 26.2.13 | Maksimum 56; prag 30; evaluated pa approved/rejected pri potvrdi | U V1 |
| BM-ML-045 | Preliminarna i konačna faza rang-liste | 11.8 | §20 | 20.7.1; 20.7.2; 20.7.6 | Jedan objekat, dvije faze; bez ručne izmjene ranga | U V1 |
| BM-ML-046 | Jednaki bodovi i rang-pozicije | 11.9 | §20 | 20.7.4; 20.7.5; 26.2.17 | 1, 2, 2, 4; član 22; bez tehničkog tie-breaka | U V1 |
| BM-ML-047 | Procentualni limiti i njihovo preklapanje | 11.10 | §21; §22.4 | 21.8.1; 21.8.2; 26.2.18 | 30/20/15 budžeta konkretnog Poziva | U V1 |
| BM-ML-048 | Određivanje i kontrola dodijeljenog iznosa | 11.10 | §21 | 21.8.3–21.8.6; 26.2.19 | Komisija određuje; Platforma ne dodjeljuje automatski | U V1 |
| BM-ML-049 | Uslov za raspisivanje drugog Javnog konkursa | 16.1 | §22.1 | 22.7.1; 22.7.2 | Obavezan kada ostanu sredstva; nije automatski | U V1 |
| BM-ML-050 | Ista godišnja instanca i odvojeni Poziv | 16.2 | §22.2 | 22.7.4 | Prvi i drugi Poziv u istoj instanci | U V1 |
| BM-ML-051 | Ručno kreiranje i objavljivanje drugog Poziva | 16.3 | §22.3 | 22.7.3; 22.7.5; 26.2.20 | Administrator ručno; čuvanje nije objava | U V1 |
| BM-ML-052 | Ponovno konkurisanje na drugom Pozivu | 16.4 | §22.5 | 22.7.10; 26.2.21 | Nova prijava; bez automatskog prenosa | U V1 |
| BM-ML-053 | Raspisivanje prvog Javnog konkursa | 15.2 | §5.6; §6 | 5.9.2; 6.8.5; 6.8.6 | Prvi Poziv u Q2; ručna objava | U V1 |
| BM-ML-054 | Privatnost prijave i odvojenost javnih rezultata | 14.1 | §11; §23.4 | 11.5.1–11.5.4; 23.8.9; 26.2.2 | Privatnost ostaje i nakon arhiviranja | U V1 |
| BM-ML-055 | Pregled dokumentacije bez preuzimanja | 14.2 | §11.3 | 11.5.5 | Pregled u Platformi; nema redovnog masovnog preuzimanja | U V1 |
| BM-ML-056 | Granica prema ugovoru i početak prihvatljivosti troškova | 13.1 | §1.3; §25; §28.2 | 1.5.1; 23.8.10; 25.6.1 | Evidentirana granica; nema V1 toka ugovora | Van V1 |
| BM-ML-057 | Granica prema izvještavanju nakon realizacije | 13.2 | §1.3; §25; §28.2 | 1.5.1; 25.6.1 | Evidentirana granica; nema V1 toka M4/M4a | Van V1 |
| BM-ML-058 | Čuvanje arhivirane dokumentacije | 14.3 | §23 | 23.8.7–23.8.10; 26.2.22 | Ručno arhiviranje nije brisanje | U V1 |

## 27.2. Potvrda matrice

* broj redova: **58**;
* raspon: `BM-ML-001`–`BM-ML-058`;
* rupa: **nema**;
* duplikata BM oznaka: **nema**;
* svaka BM oznaka ima FS vezu;
* `BM-ML-059`: **ne postoji**.

Pravila bez direktne V1 operativne funkcije evidentirana su u Poglavljima 25 i 28, uz prihvatne kriterijume koji potvrđuju odsustvo V1 toka: `BM-ML-008`, `BM-ML-011` (operativni dio pred ugovor), `BM-ML-017` (kontrola prije ugovora), `BM-ML-032` (operativna provjera žira), `BM-ML-056`, `BM-ML-057`.

---

# 28. Odložene teme i teme van V1

Status poglavlja: USVOJENO

Ovo poglavlje **ne** rješava pitanja 3 i 10 i **ne** otvara novo pitanje. **Ne** uvodi procese van V1.

## 28.1. Odložena poslovna razrada

### Pitanje 3 — de minimis

Izjava o de minimis pomoći:

* **ne** dostavlja se uz početnu prijavu;
* detaljna struktura, polja, potpis, ovjera, format, dostavljanje i obrada **nijesu** dio V1;
* nema zasebnog funkcionalnog toka;
* nema nove oznake.

### Pitanje 10 — službeni akti

Potvrđeno je samo:

* Odluka o raspodjeli;
* Rješenje o dodjeli;
* Rješenje o odbijanju;
* nadležni Sekretarijat;
* činjenica da akti nastaju nakon rangiranja.

Nijesu razrađeni:

* generisanje;
* objavljivanje;
* javni sadržaj;
* dostavljanje;
* pravni lijekovi;
* konačnost;
* izvršnost;
* neposredna veza sa ugovorom.

Javni PDF lifecycle iz ženskog profila **ne** preuzima se.

## 28.2. Poznate teme van V1

Van V1 ostaju operativni procesi:

* generisanje, dostavljanje, potpisivanje i upravljanje ugovorom;
* isplata sredstava;
* realizacija projekta;
* praćenje realizacije i ugovornih obaveza;
* podnošenje i obrada M4/M4a;
* fakture i bankovni izvodi nakon realizacije;
* kontrola i odobravanje izvještaja;
* povraćaj sredstava;
* operativno pribavljanje žiro računa prije ugovora.

## 28.3. Tehničke teme za TS

Ostaje za `KN-TS-001` (postoji; NACRT):

* tehnički model podataka;
* API;
* autorizacioni mehanizam;
* format pregledača dokumenata;
* privremene tehničke kopije;
* storage;
* backup;
* enkripcija;
* log infrastruktura;
* e-mail transport i tehnički status isporuke;
* zaštita fajlova;
* tehničko brisanje;
* performanse;
* migracije;
* implementacione enum vrijednosti;
* elektronsko ili papirno potpisivanje izjava iz `BM-ML-008`, ako kasnije bude obuhvaćeno platformom.

## 28.4. Pravilo buduće izmjene

Svaka kasnija razrada odložene teme mora:

* imati odobren poslovni izvor;
* prvo uskladiti poslovni model ako uvodi novo ili izmijenjeno pravilo;
* zatim izmijeniti FS kroz novu verziju i odgovarajući PATCH;
* ažurirati `KN-RG-001`;
* sačuvati sljedivost;
* **ne** mijenjati postojeću verziju retroaktivno.

## 28.5. Granica usvojene verzije 1.0.0

Verzija 1.0.0 usvaja funkcionalni obuhvat Poglavlja 1–28 i funkcionalnu primjenu `BM-ML-001`–`BM-ML-058`.

V1 završava konačnim rezultatom, evidentiranjem raspodjele i arhiviranjem. Ugovor, isplata, realizacija, M4/M4a, de minimis i ugovorno praćenje ostaju van V1. Detaljan lifecycle službenih akata, njihovo javno objavljivanje i PDF tok ostaju odloženi. Odlaganje pitanja 3 i 10 predstavlja usvojenu granicu, a ne grešku specifikacije.

Buduća izmjena ovog usvojenog dokumenta zahtijeva novu verziju i odgovarajući PATCH.

---

**Kraj dokumenta KN-FS-002 v1.0.4**
