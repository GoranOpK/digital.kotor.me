# Digital Kotor
# Business Model
## Funkcionalnost: Obavještenja (platformska funkcionalnost Digital Kotora)

**Oznaka dokumenta:** DK-BM-001
**Feature ID:** FT-004  
**Status dokumenta:** U IZRADI  
**Verzija:** 0.1
**Namespace:** DK-* (platforma Digital Kotor)

Povezani dokumenti:

* Use Case Specification: **DK-UC-001** — `docs/use-cases/Use_Cases_Obavjestenja.md`
* Functional Specification: **DK-FS-001** — `docs/functional-specifications/Functional_Specification_Obavjestenja.md`
* Technical Specification: **DK-TS-001** — `docs/technical-specifications/Technical_Specification_Obavjestenja.md`
* Feature Registry: **DK-FR-001** — `docs/features/Feature-Registry_Digital-Kotor.md`
* Registar oznaka: **DK-RG-001** — `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Digital-Kotor.md`

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-31 | Uspostavljena početna struktura Business Modela funkcionalnosti Obavještenja (FT-004). Unesene usvojene Product Owner odluke PO-OB-01 do PO-OB-11 i odgovarajuća poslovna pravila BM-OB-01 do BM-OB-11. |
| PATCH-001 | 2026-07-31 | PO-OB-12 / BM-OB-12 — stabilna javna dostupnost referenciranog zvaničnog sadržaja; PO-OB-13 / BM-OB-13 — značenje zamjene (vidljivost u aktivnom panelu). Usklađena terminologija; ažurirana otvorena pitanja. |
| 2026-08-17 | 2026-08-17 | Administrativna dodjela dokumentacionog ID-a `DK-BM-001`; poslovni sadržaj, BM-OB / PO-OB pravila i status U IZRADI ostaju nepromijenjeni. |

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

Dokument predstavlja referentni poslovni model za planiranje, razvoj, testiranje i održavanje funkcionalnosti **Obavještenja** na platformi Digital Kotor.

Dokument opisuje ciljni poslovni model. Ne opisuje trenutnu implementaciju, ne definiše tehničku realizaciju i ne predstavlja Functional Specification ni Technical Specification.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| BM-OB / 1. Uvod | USVOJENO (PO-OB-01, PO-OB-02) |
| BM-OB / 2. Svrha | USVOJENO (PO-OB-01) |
| BM-OB / 3. Ciljevi | USVOJENO (izvedeno iz PO-OB-01–PO-OB-13) |
| BM-OB / 4. Opseg | USVOJENO (PO-OB-01–PO-OB-13) |
| BM-OB / 5. Poslovni principi | USVOJENO (BM-OB-01 do BM-OB-13) |
| BM-OB / 6. Poslovni koncepti | USVOJENO (PO-OB-01, PO-OB-04, PO-OB-05, PO-OB-07, PO-OB-12, PO-OB-13) |
| BM-OB / 7. Poslovni procesi | USVOJENO (PO-OB-06, PO-OB-07, PO-OB-08, PO-OB-12, PO-OB-13) |
| BM-OB / 8. Odnos sa drugim funkcionalnostima | USVOJENO (PO-OB-07, PO-OB-08) |
| BM-OB / 9. Granice | USVOJENO (PO-OB-03, PO-OB-05, PO-OB-10, PO-OB-11, PO-OB-12, PO-OB-13) |
| BM-OB / 10. Rječnik poslovnih pojmova | USVOJENO |
| BM-OB / 11. Otvorena pitanja | OTVORENO |
| BM-OB / 12. Registar usvojenih Product Owner odluka | USVOJENO (PO-OB-01 do PO-OB-13) |

---

# Pravila upravljanja Business Modelom

1. Business Model predstavlja zvaničnu poslovnu specifikaciju funkcionalnosti Obavještenja (FT-004).

2. Posljednja usvojena verzija Business Modela predstavlja jedini izvor istine (Single Source of Truth) za poslovna pravila ove funkcionalnosti.

3. Poglavlja i pravila sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu ili Product Owner odluku.

4. Kompletan Business Model generiše se isključivo na izričit zahtjev.

5. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno prepisivati, preformulisati ili reorganizovati usvojeni sadržaj.

6. Usvojene Product Owner odluke PO-OB-01 do PO-OB-13 i poslovna pravila BM-OB-01 do BM-OB-13 ne smiju se mijenjati niti proširivati bez nove Product Owner odluke.

7. Ako postoji razlika između implementacije sistema i Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se odlukom ne izmijeni sam Business Model.

8. Ovaj dokument ne uvodi nova poslovna pravila izvan usvojenih Product Owner odluka. Neriješena pitanja ostaju u poglavlju „Otvorena pitanja“.

---

# Upravljanje promjenama

Svaka izmjena Business Modela mora biti rezultat usvojene poslovne ili Product Owner odluke i evidentirana kroz odgovarajući PATCH.

---

## Sadržaj

1. Uvod  
2. Svrha  
3. Ciljevi  
4. Opseg  
5. Poslovni principi (BM-OB-01 do BM-OB-13)  
6. Poslovni koncepti  
7. Poslovni procesi  
8. Odnos sa drugim funkcionalnostima Digital Kotor  
9. Granice  
10. Rječnik poslovnih pojmova  
11. Otvorena pitanja  
12. Registar usvojenih Product Owner odluka (PO-OB-01 do PO-OB-13)

---

# 1. Uvod

**Status:** USVOJENO (PO-OB-01, PO-OB-02)

Business Model definiše poslovna pravila i poslovne koncepte funkcionalnosti **Obavještenja** na platformi Digital Kotor.

**Obavještenja** su **unakrsna (cross-platform) funkcionalnost** platforme Digital Kotor.

Obavještenja:

* nisu zaseban aplikativni modul;
* nisu korisnički inbox;
* nisu sistem privatnih poruka;
* nisu konvencionalni centar notifikacija sa stanjem pročitano/nepročitano;
* nisu urednički CMS za vijesti.

Obavještenja predstavljaju javnu platformsku oblast u kojoj se javnosti predstavljaju zvanične informacije nastale kroz druge funkcionalnosti Digital Kotor.

Na početnoj stranici Digital Kotor, ispod panela **„Dobrodošli na digitalnu platformu Opštine Kotor“**, postoji drugi panel naslovljen **„Obavještenja“**. To je zahtjev poslovne prezentacije, a ne arhitektonska odluka.

---

# 2. Svrha

**Status:** USVOJENO (PO-OB-01)

**BM-OB-01 — Svrha**

Obavještenja obezbjeđuju platformsku oblast kroz koju se zvanični sadržaj nastao unutar Digital Kotor čini vidljivim javnosti.

Primjer:

**Odluka o raspodjeli sredstava za podršku ženskom preduzetništvu za 2026. godinu**

Povezani sadržaj je zvanična odluka nastala u okviru funkcionalnosti konkursa. Konkretna tehnička putanja (npr. administrativna ruta za pregled odluke) služi samo kao ilustracija konteksta i **nije** trajni tehnički ugovor ovog Business Modela.

---

# 3. Ciljevi

**Status:** USVOJENO (izvedeno iz PO-OB-01 do PO-OB-13)

Ciljevi funkcionalnosti Obavještenja su:

1. omogućiti javno predstavljanje zvaničnog sadržaja nastalog u Digital Kotor;
2. povezati javnu platformsku prezentaciju sa zvaničnim sadržajem izvornih funkcionalnosti kroz stabilan javni mehanizam pristupa;
3. nastajati automatski kada relevantni poslovni proces dostigne stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu;
4. ostati kanal objave, a ne nosilac poslovnog postupka ni izvornog zvaničnog sadržaja;
5. ne uvoditi praćenje čitanja, potvrdu prijema ni privatni korisnički inbox u usvojenom obuhvatu.

---

# 4. Opseg

**Status:** USVOJENO (PO-OB-01 do PO-OB-13)

## 4.1 Obuhvat

U obuhvatu ovog Business Modela su:

* javna platformska oblast **Obavještenja**;
* struktura jednog Obavještenja (naslov, opciono kratak opis, referenca na zvanični sadržaj);
* automatsko nastajanje Obavještenja kada relevantni poslovni proces u drugoj funkcionalnosti Digital Kotor dostigne stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu;
* početno prepoznati izvori: konkursi, tenderi i drugi procesi Digital Kotor koji proizvode zvanični sadržaj koji mora biti javno predstavljen;
* stabilna javna dostupnost referenciranog zvaničnog sadržaja dok je Obavještenje javno vidljivo (BM-OB-12);
* očekivanje da Obavještenje ostaje vidljivo u aktivnom panelu dok ga ne zamijeni odgovarajuće novo Obavještenje iz narednog ekvivalentnog postupka (prezentaciono pravilo; zamjena se odnosi na vidljivost u panelu — BM-OB-13).

## 4.2 Van obuhvata (usvojeno)

Van usvojenog obuhvata su:

* obavezna registracija ili prijava radi pregleda Obavještenja;
* korisnički inbox i privatne poruke;
* stanje pročitano/nepročitano;
* dokaz da je određeni korisnik otvorio Obavještenje;
* potvrda prijema;
* dodatno administratorsko odobrenje objave Obavještenja nastalog kada poslovni proces dostigne stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu;
* urednički workflow (ručno kreiranje, uredničko odobrenje, publish/unpublish odobrenje) za usvojeni scenario automatskog nastajanja;
* objava putem sajta Opštine `kotor.me` kao dio ove funkcionalnosti;
* opšta podrška za proizvoljne eksterne URL-ove kao usvojeni zahtjev;
* detaljna pravila životnog ciklusa, arhiviranja i dugoročne dostupnosti (van usvojenog obuhvata dok se eksplicitno ne odluče — BM-OB-13);
* struktura URL-a, rute, kontroleri i implementacioni mehanizam javnog pristupa (FS / TS);
* Functional Specification, Technical Specification, model podataka, API i implementacioni detalji.

---

# 5. Poslovni principi

**Status:** USVOJENO

Sljedeća pravila BM-OB-01 do BM-OB-13 prenose usvojene Product Owner odluke PO-OB-01 do PO-OB-13 bez izmjene njihovog značenja.

---

## BM-OB-01 — Svrha

**Izvor:** PO-OB-01

Obavještenja obezbjeđuju platformsku oblast kroz koju se zvanični sadržaj nastao unutar Digital Kotor čini vidljivim javnosti.

---

## BM-OB-02 — Mjesto na platformi

**Izvor:** PO-OB-02

Na početnoj stranici Digital Kotor, ispod panela **„Dobrodošli na digitalnu platformu Opštine Kotor“**, postoji drugi panel naslovljen **„Obavještenja“**.

Ovo je zahtjev funkcionalne prezentacije, a ne arhitektonska odluka.

---

## BM-OB-03 — Javna dostupnost

**Izvor:** PO-OB-03

Obavještenja su vidljiva:

* autentifikovanim korisnicima;
* neautentifikovanim posjetiocima.

Pregled Obavještenja ne zahtijeva registraciju niti prijavu.

Povezani zvanični sadržaj mora biti dostupan kroz odgovarajući javni mehanizam isporuke. Stabilnost i nezavisnost tog mehanizma od administrativnog interfejsa uređuju se pravilom BM-OB-12. Konkretni tehnički mehanizam nije predmet ovog Business Modela.

---

## BM-OB-04 — Struktura Obavještenja

**Izvor:** PO-OB-04

Obavještenje sadrži:

1. naslov;
2. opcioni kratak opis;
3. referencu na zvanični sadržaj.

Zvanični sadržaj je odvojen od Obavještenja.

Obavještenje skreće pažnju na sadržaj i omogućava pristup do njega.

---

## BM-OB-05 — Vrste referenciranog sadržaja

**Izvor:** PO-OB-05

Referencirani zvanični sadržaj može biti:

* dinamički generisan sadržaj;
* statički dokument.

Business Model ostaje neutralan u pogledu konkretnog formata fajla, mehanizma skladištenja i tehničke isporuke.

Preferirani kontekst je sadržaj dostupan putem `digital.kotor.me`.

Eksterna objava putem sajta Opštine `kotor.me` je zaseban proces i nije dio ove funkcionalnosti.

Opšta podrška za proizvoljne eksterne URL-ove nije usvojeni zahtjev.

---

## BM-OB-06 — Automatsko nastajanje

**Izvor:** PO-OB-06

Obavještenja nastaju automatski kada relevantni poslovni proces u drugoj funkcionalnosti Digital Kotor dostigne stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu.

Primjer toka:

1. poslovni proces konkursa dostigne stanje u kojem je zvanična odluka spremna ili obavezna za javnu objavu;
2. nadležna komisija završava svoj rad;
3. zvanična odluka je generisana ili postaje dostupna;
4. objava odluke je potrebna;
5. odgovarajuće Obavještenje postaje javno vidljivo.

Za takvo Obavještenje ne postoji dodatni korak administratorskog odobrenja objave.

Administrator ne odlučuje da li se zakonski ili procedurom obavezan zvanični sadržaj koji je spreman ili obavezan za javnu objavu smije objaviti.

---

## BM-OB-07 — Odgovornost izvorne funkcionalnosti

**Izvor:** PO-OB-07

Izvorna funkcionalnost ostaje odgovorna za:

* poslovni proces;
* zvanični sadržaj;
* utvrđivanje da je sadržaj dostigao stanje pogodno ili obavezno za objavu.

Obavještenja predstavljaju platformski kanal objave.

Obavještenja samostalno ne utvrđuju da li je poslovni proces dostigao stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu.

---

## BM-OB-08 — Početni izvori

**Izvor:** PO-OB-08

Početno prepoznati izvori uključuju:

* konkurse;
* tendere;
* druge procese Digital Kotor koji proizvode zvanični sadržaj koji mora biti javno predstavljen;

kada poslovni proces dostigne stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu.

Ova lista nije zatvoren katalog tehničkih integracija.

Ovaj Business Model ne definiše nedokumentovano ponašanje za tendere ili buduće funkcionalnosti.

---

## BM-OB-09 — Trajanje vidljivosti

**Izvor:** PO-OB-09

Trenutno proizvodno očekivanje je da Obavještenje ostaje vidljivo u aktivnom panelu Obavještenja dok ga ne zamijeni odgovarajuće novo Obavještenje iz narednog ekvivalentnog postupka.

Primjer:

* odluka za 2026. ostaje vidljiva;
* kasnije je zamjenjuje odgovarajuća odluka za 2027.

Ovo je trenutno prezentaciono / proizvodno pravilo i podložno je kasnijem preciziranju.

Značenje zamjene uređuje se pravilom BM-OB-13.

Neriješeni detalji životnog ciklusa, arhiviranja i dugoročne dostupnosti evidentirani su kao otvorena pitanja.

---

## BM-OB-10 — Bez praćenja čitanja u usvojenom obuhvatu

**Izvor:** PO-OB-10

U trenutno opisanoj funkcionalnosti nisu potrebni:

* status pročitano/nepročitano;
* dokaz da je određeni korisnik otvorio Obavještenje;
* potvrda prijema;
* privatni korisnički inbox.

---

## BM-OB-11 — Bez ručnog uredničkog workflow-a u usvojenom scenariju

**Izvor:** PO-OB-11

Za zvanični sadržaj koji automatski nastaje kada poslovni proces dostigne stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu ne uvodi se:

* ručno kreiranje od strane administratora;
* uredničko odobrenje;
* odobrenje objave / povlačenja objave;
* administratorska potvrda odluke nadležne komisije.

Da li Digital Kotor kasnije može podržati ručno kreirana opšta opštinska obavještenja još nije usvojeno i ostaje otvoreno pitanje.

---

## BM-OB-12 — Stabilna javna dostupnost

**Izvor:** PO-OB-12

Kada je Obavještenje javno vidljivo, referencirani zvanični sadržaj mora biti javno dostupan kroz stabilan javni mehanizam pristupa.

Javni mehanizam pristupa ne zavisi od administrativnog interfejsa.

Ovaj Business Model namjerno ne definiše:

* strukturu URL-a;
* rutiranje;
* dizajn kontrolera;
* implementacioni mehanizam.

Ti elementi pripadaju Functional Specification i Technical Specification.

---

## BM-OB-13 — Značenje zamjene

**Izvor:** PO-OB-13

Zamjena Obavještenja odnosi se isključivo na njegovu vidljivost unutar aktivnog panela Obavještenja.

Zamjena **ne** podrazumijeva automatski:

* brisanje;
* arhiviranje;
* gubitak javne dostupnosti;
* uništavanje referenciranog zvaničnog sadržaja.

Životni ciklus, arhiviranje i dugoročna dostupnost ostaju van usvojenog obuhvata dok se eksplicitno ne odluče.

---

# 6. Poslovni koncepti

**Status:** USVOJENO

## 6.1 Obavještenje

**Obavještenje** je javni platformski unos koji sadrži:

* naslov;
* opcioni kratak opis;
* referencu na zvanični sadržaj.

Obavještenje **nije**:

* sama odluka;
* sam dokument;
* zvanični izvorni zapis;
* notifikacija poslata korisniku;
* poruka u inboxu.

## 6.2 Zvanični sadržaj

**Zvanični sadržaj** je autoritativni sadržaj koji proizvodi ili održava izvorna funkcionalnost Digital Kotor.

Zvanični sadržaj može biti generisan ili statički.

Obavještenje skreće pažnju na zvanični sadržaj i omogućava pristup do njega; ne zamjenjuje ga.

## 6.3 Izvorna funkcionalnost

**Izvorna funkcionalnost** je funkcionalnost Digital Kotor čiji poslovni proces proizvodi zvanični sadržaj i utvrđuje da je sadržaj spreman ili obavezan za objavu.

## 6.4 Objava putem Obavještenja

**Objava putem Obavještenja** je čin kojim Obavještenje postaje vidljivo u javnoj oblasti Obavještenja.

## 6.5 Zamjena Obavještenja

**Zamjena Obavještenja** odnosi se isključivo na vidljivost unutar aktivnog panela Obavještenja (BM-OB-13). Ne podrazumijeva automatski brisanje, arhiviranje, gubitak javne dostupnosti ni uništavanje referenciranog zvaničnog sadržaja.

## 6.6 Stabilna javna dostupnost

Dok je Obavještenje javno vidljivo, referencirani zvanični sadržaj mora biti javno dostupan kroz stabilan javni mehanizam pristupa koji ne zavisi od administrativnog interfejsa (BM-OB-12).

---

# 7. Poslovni procesi

**Status:** USVOJENO (PO-OB-06, PO-OB-07, PO-OB-08, PO-OB-12, PO-OB-13)

## 7.1 Nastajanje Obavještenja iz izvornog procesa

1. Izvorna funkcionalnost vodi svoj poslovni proces.
2. Izvorna funkcionalnost proizvodi ili čini dostupnim zvanični sadržaj.
3. Izvorna funkcionalnost utvrđuje da je poslovni proces dostigao stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu.
4. Odgovarajuće Obavještenje nastaje automatski.
5. Obavještenje postaje javno vidljivo u oblasti Obavještenja.
6. Referencirani zvanični sadržaj je javno dostupan kroz stabilan javni mehanizam pristupa (BM-OB-12).
7. Dodatno administratorsko odobrenje objave nije dio usvojenog procesa.

## 7.2 Pregled od strane javnosti

1. Posjetilac (sa ili bez prijave) pristupa početnoj stranici Digital Kotor.
2. U panelu **Obavještenja** vidi javno dostupna Obavještenja.
3. Putem reference pristupa zvaničnom sadržaju kroz stabilan javni mehanizam pristupa koji ne zavisi od administrativnog interfejsa.

## 7.3 Zamjena vidljivosti (prezentaciono očekivanje)

Trenutno proizvodno očekivanje je da se Obavještenje vidljivo u aktivnom panelu zamijeni odgovarajućim novim Obavještenjem iz narednog ekvivalentnog postupka (BM-OB-09).

Zamjena se odnosi isključivo na vidljivost u aktivnom panelu i ne podrazumijeva automatski brisanje, arhiviranje, gubitak javne dostupnosti ni uništavanje referenciranog zvaničnog sadržaja (BM-OB-13).

Kriterijumi „odgovarajuće“ zamjene, istovremena vidljivost, te arhiviranje i dugoročna dostupnost ostaju otvorena pitanja.

---

# 8. Odnos sa drugim funkcionalnostima Digital Kotor

**Status:** USVOJENO (PO-OB-07, PO-OB-08)

| Odnos | Opis |
|-------|------|
| Platforma Digital Kotor | Obavještenja su unakrsna platformska funkcionalnost prezentacije, ne zaseban modul. |
| Početna stranica | Panel Obavještenja prikazuje se ispod panela dobrodošlice (BM-OB-02). |
| Konkursi | Mogući početni izvor zvaničnog sadržaja (npr. odluka o raspodjeli sredstava). Konkursi ostaju odgovorni za postupak i sadržaj. |
| Tenderi | Mogući početni izvor. Ponašanje tendera nije ovim dokumentom detaljno definisano. |
| Druge funkcionalnosti | Mogu postati izvori kada proizvode zvanični sadržaj koji mora biti javno predstavljen. |
| `kotor.me` | Eksterna objava van obuhvata ove funkcionalnosti. |

Obavještenja ne preuzimaju upravljanje životnim ciklusom konkursa, tendera ili drugih postupaka.

---

# 9. Granice

**Status:** USVOJENO

Obavještenja **ne** predstavljaju:

* zaseban aplikativni modul;
* korisnički inbox;
* sistem privatnih poruka;
* centar notifikacija sa stanjem pročitano/nepročitano;
* urednički CMS za vijesti;
* izvorni zvanični zapis ili samu odluku / dokument;
* kanal za obaveznu objavu na `kotor.me`;
* mehanizam koji zahtijeva prijavu radi pregleda.

Obavještenja **ne određuju** samostalno da li je poslovni proces dostigao stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu.

Zamjena Obavještenja u aktivnom panelu **ne** podrazumijeva automatski brisanje, arhiviranje, gubitak javne dostupnosti ni uništavanje zvaničnog sadržaja (BM-OB-13).

---

# 10. Rječnik poslovnih pojmova

**Status:** USVOJENO

| Pojam | Značenje |
|-------|----------|
| Obavještenje | Javni platformski unos sa naslovom, opcionim kratkim opisom i referencom na zvanični sadržaj. |
| Zvanični sadržaj | Autoritativni sadržaj koji proizvodi ili održava izvorna funkcionalnost; može biti generisan ili statički. |
| Izvorna funkcionalnost | Funkcionalnost Digital Kotor koja vodi poslovni proces, proizvodi zvanični sadržaj i utvrđuje da je sadržaj spreman ili obavezan za objavu. |
| Objava putem Obavještenja | Čin kojim Obavještenje postaje vidljivo u javnoj oblasti Obavještenja. |
| Zamjena Obavještenja | Promjena vidljivosti unutar aktivnog panela Obavještenja; ne podrazumijeva automatski brisanje, arhiviranje, gubitak javne dostupnosti ni uništavanje referenciranog zvaničnog sadržaja. |
| Stabilna javna dostupnost | Javni pristup referenciranom zvaničnom sadržaju dok je Obavještenje javno vidljivo, kroz mehanizam koji ne zavisi od administrativnog interfejsa. |
| Panel Obavještenja | Oblast na početnoj stranici Digital Kotor namijenjena prikazu Obavještenja. |
| Aktivni panel Obavještenja | Panel u kojem su Obavještenja trenutno prikazana javnosti. |
| Javna dostupnost | Dostupnost Obavještenja i povezanog zvaničnog sadržaja bez obaveze registracije ili prijave. |
| Digital Kotor | Digitalna platforma Opštine Kotor (`digital.kotor.me`). |

---

# 11. Otvorena pitanja

**Status:** OTVORENO

Sljedeća pitanja nisu usvojena i **ne** predstavljaju poslovna pravila:

1. Šta određuje da je novo Obavještenje „odgovarajuće“ u odnosu na starije radi zamjene u aktivnom panelu (npr. ista izvorna funkcionalnost, ista kategorija postupka, isti ciklus)?
2. Može li više od jednog Obavještenja iz iste kategorije izvora / postupka biti istovremeno vidljivo?
3. Šta se dešava ako noviji postupak bude otkazan ili ne proizvede zamjensku odluku?
4. Kako se uređuju arhiviranje i dugoročna dostupnost sadržaja nakon što Obavještenje više nije vidljivo u aktivnom panelu?
5. Da li je ikada dozvoljena ručna objava opštih opštinskih obavještenja?
6. Koje poslovno stanje u svakoj izvornoj funkcionalnosti pokreće objavu?
7. Može li već objavljeni zvanični sadržaj biti ispravljen ili zamijenjen, i kako se tada ponaša javni unos?
8. Da li se kratak opis unosi iz izvornog procesa, generiše automatski ili se izostavlja?
9. Da li se redoslijed određuje vremenom objave, poslovnim značajem ili drugim pravilom?
10. Postoji li maksimalan broj vidljivih Obavještenja na početnoj stranici?

**Zatvoreno ovim PATCH-om:**

* ranije pitanje o značenju zamjene — riješeno pravilom PO-OB-13 / BM-OB-13 (zamjena = vidljivost u aktivnom panelu);
* ranije pitanje o stabilnom javnom URL-u nezavisnom od administrativnih ruta — riješeno na poslovnom nivou pravilom PO-OB-12 / BM-OB-12 (stabilan javni mehanizam pristupa; struktura URL-a ostaje FS/TS).

---

# 12. Registar usvojenih Product Owner odluka

**Status:** USVOJENO

| Oznaka | Naziv | Prenijeto u |
|--------|-------|-------------|
| PO-OB-01 | Svrha | BM-OB-01; §1; §2 |
| PO-OB-02 | Mjesto na platformi | BM-OB-02; §1 |
| PO-OB-03 | Javna dostupnost | BM-OB-03; §4.2; §9 |
| PO-OB-04 | Struktura Obavještenja | BM-OB-04; §6.1 |
| PO-OB-05 | Vrste referenciranog sadržaja | BM-OB-05; §6.2 |
| PO-OB-06 | Automatsko nastajanje | BM-OB-06; §7.1 |
| PO-OB-07 | Odgovornost izvorne funkcionalnosti | BM-OB-07; §6.3; §8 |
| PO-OB-08 | Početni izvori | BM-OB-08; §8 |
| PO-OB-09 | Trajanje vidljivosti | BM-OB-09; §7.3; otvorena pitanja |
| PO-OB-10 | Bez praćenja čitanja | BM-OB-10; §4.2; §9 |
| PO-OB-11 | Bez ručnog uredničkog workflow-a u usvojenom scenariju | BM-OB-11; §4.2; otvoreno pitanje 5 |
| PO-OB-12 | Stabilna javna dostupnost | BM-OB-12; §4.1; §6.6; §7.1; §7.2 |
| PO-OB-13 | Značenje zamjene | BM-OB-13; §4.1; §6.5; §7.3; §9 |

Oznake PO-OB-01 do PO-OB-13 ne renumerišu se i ne preimenuuju se.
