# Digital Kotor
# Implementation Strategy
## IS-001 — Implementaciona strategija javnog portala

**Oznaka dokumenta:** IS-001  
**Naziv:** Implementaciona strategija javnog portala  
**Feature ID:** FT-001  
**Modul:** Kalendar kulture  
**Referentna specifikacija:** TS-009 v1.0.0 Stable  
**Status dokumenta:** Nacrt  
**Verzija:** 0.1.0  
**Datum:** 2026-07-31

---

# 1. Identitet dokumenta

| Stavka | Vrijednost |
|--------|------------|
| Oznaka | IS-001 |
| Naziv | Implementaciona strategija javnog portala |
| Tip | Operativni planski dokument |
| Referenca | TS-009 v1.0.0 Stable |
| Usvojene odluke | IS-001-01 … IS-001-08 |

### IS-001-01 — Identitet dokumenta

IS-001 je operativni planski dokument koji definiše:

* faze implementacije;
* međuzavisnosti;
* procjenu rizika;
* strategiju testiranja;
* strategiju implementacije;
* strategiju deploy-a;
* strategiju rollback-a;

za implementaciju **TS-009 v1.0.0**, bez mijenjanja usvojenih poslovnih, funkcionalnih i tehničkih pravila.

IS-001:

* ne definiše nove funkcionalnosti;
* ne predstavlja zamjenu za BM, FS ili TS;
* ne mijenja usvojena BM/FS/TS pravila;
* mora ostati potpuno sljediv prema TS-009.

---

# 2. Svrha i status

**Svrha:** omogućiti kontrolisanu, evolutivnu implementaciju javnog portala u skladu sa TS-009 v1.0.0, uz najmanji rizik za postojeću produkciju (princip IA-01).

**Status:** Nacrt (v0.1.0).

**Van svrhe:** SQL, Laravel kod, konačni dizajn klasa/metoda, nova Product Owner odluke, zamjena TS-003…TS-008.

---

# 3. Referentni dokumenti

| Dokument | Uloga |
|----------|--------|
| `docs/technical-specifications/Technical-Specification_Javni_portal.md` (TS-009 v1.0.0) | Referentna specifikacija javnog portala |
| `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-11, BM-05, …) | Poslovna pravila (ne mijenjaju se ovim dokumentom) |
| `docs/functional-specifications/Functional-Specification.md` (§5.1–§5.4, §5.13) | Funkcionalni zahtjevi |
| TS-003 Događaj | Domen Događaja; zavisnost Faze 4/6 |
| TS-004 Održavanje | Domen Održavanja; zavisnost Faze 4/5/6 |
| TS-005 Manifestacija | Domen Manifestacije; zavisnost Faze 4/5 |
| TS-007 Kategorije i oznake | Oznake događaja; zavisnost Faze 4/6 |
| TS-008 Mediji | Mediji / fallback; zavisnost Faze 4/6 (po obuhvatu) |
| `docs/features/Feature-Registry.md` | Registar FT-001 / plan TS |
| Radna analiza implementacione strategije (chat, TS-009 v1.0.0) | Ulaz za faze, rizike, zavisnosti (usklađena sa IS-001-01…08) |

**Napomena o putanji:** U repozitorijumu nije postojao usvojen folder za implementacione strategije. Dokument je smješten u `docs/implementation-strategies/` po predloženom standardu imenovanja, analogno `docs/technical-specifications/` i `docs/change-requests/`.

**Napomena o oznaci TS-010:** Feature Registry rezerviše **TS-010** za *Urednički portal*. IS-001 nije taj dokument i ne zamjenjuje ga.

---

# 4. Granice i načela

### Granice

IS-001 **ne smije**:

* mijenjati TS-009, BM ili FS;
* uvoditi nova poslovna pravila ili nove PO odluke;
* davati SQL, Laravel kod ili konačnu arhitekturu klasa;
* zamijeniti tehničke specifikacije domena (TS-003…TS-008);
* rješavati otvorena pitanja bez Product Owner-a.

IS-001 **smije** navoditi: pogođene tehničke slojeve, tip migracije, nivo rizika, zavisnosti, redoslijed, test/deploy/rollback obuhvat.

### Terminologija (usklađeno sa TS-009)

Koriste se kanonski nazivi: Kalendar kulture, Početna, Pretraga i pregled, Detalji događaja, Manifestacije, Detalji manifestacije, Arhiva događaja, Održavanje, Termin (samo vremenski atributi), Kategorija, **Oznake** (klasifikacija događaja), **Tagovi** (metapodaci medija, BM-09), Statusne oznake.

Gdje IS-001-03 u radnoj formulaciji faza koristi „Tagovi“ u kontekstu klasifikacije događaja, u ovom dokumentu koristi se kanonski pojam **Oznake** (BM-08 / TS-007), kako ne bi došlo do miješanja sa tagovima medija.

---

# 5. Implementacioni principi

### IS-001-02 — Implementacioni principi

1. Minimalne izmjene postojeće produkcione aplikacije.
2. Evolutivni razvoj bez nepotrebnog redizajna (usklađeno sa IA-01 / BM-PK-16 / BR-255).
3. Male i funkcionalno zaokružene implementacione cjeline.
4. Kontrolisan rizik.
5. Očuvanje kompatibilnosti postojećih funkcionalnosti.
6. Samostalno testiranje svake faze.
7. Mogućnost sigurnog rollback-a.
8. Potpuna sljedivost prema BM, FS i TS-009.

Implementacija **ne smije** uvoditi funkcionalnosti koje nijesu prethodno usvojene kroz dokumentaciju.

**Hibridna strategija isporuke (iz radne analize, usklađena sa principima):**

* Faze 1–3: tanke, portalne cjeline (jedna logička cjelina po isporuci).
* Faza 4: domenski modul(i) — ne djeliti na „pola“ entiteta bez konzistentnog modela.
* Faze 5–6: portalni potrošač nakon stabilnog domena.

---

# 6. Pregled trenutnog stanja

Na osnovu analize postojeće implementacije (bez izmjene koda u okviru IS-001):

| Područje | Stanje (sažetak) |
|----------|------------------|
| Početna | Hero statički; istaknuti / statistike / lista ispod kalendara postoje, djelimično odstupaju od TS-009 (npr. limiti, klikabilnost, dugme „Prikaži sve“) |
| Pretraga i pregled | Stranica postoji (UI naziv još „Pregled događaja“); filteri nepotpuni u odnosu na PO-TS9-04A |
| Detalji događaja | Postoje; flat datum/vrijeme/lokacija na događaju; bez bloka Manifestacije; statusne oznake ograničene |
| Arhiva događaja | Postoji lista (`published` + završen period); bez punih statusnih oznaka po BM-PK-13 |
| Manifestacije (portal) | Nisu implementirane (nema entiteta / ruta / UI) |
| Održavanja | Nisu zaseban model; podaci na događaju (TO odstupanje) |
| Oznake / Mediji (domen) | Nisu u skladu sa punim TS-007/TS-008 modelom na portalu |

Detaljna matrica odstupanja: §7.

---

# 7. Matrica funkcionalnosti i odstupanja

| Funkcionalnost (TS-009) | Postoji | Potrebna izmjena | Nova implementacija | Tipična faza |
|-------------------------|:------:|:----------------:|:-------------------:|:------------:|
| IA-01 evolutivni okvir | djelimično | — | — | sve |
| Rename → Pretraga i pregled | ne (UI) | da | ne | 1 |
| Filteri (datum, kategorija, lokacija) | djelimično | da | djelimično | 2 |
| Filter Manifestacija | ne | — | da | 5 (nakon 4) |
| Hero | da | provjera | ne | 1 |
| Istaknuti (max 3, prazno) | djelimično | da | ne | 1 |
| Statistike (klik, naziv mjeseca) | djelimično | da | ne | 1 |
| Lista ispod kalendara (max 3, „Prikaži sve“) | djelimično | da | ne | 1 |
| Detalji događaja (baseline) | djelimično | da | djelimično | 3, zatim 6 |
| Arhiva događaja (baseline) | da | da | ne | 3, zatim 6 |
| Manifestacije UI (lista/detalj/program/blok) | ne | — | da | 5 |
| Domen MF / Održavanja / Oznake / Mediji | ne / djelimično | — | da | 4 |

---

# 8. Matrica zavisnosti

```text
Faza 1 (UI usklađenje)
    └─► nije uslovljen domenom
    └─► preporučeni preduslov za Fazu 2 (rename)

Faza 2 (Pretraga i pregled)
    └─► preferira završenu Fazu 1
    └─► filter MF → tek nakon Faze 4 + 5

Faza 3 (Detalji + Arhiva, postojeći model)
    └─► može paralelno sa Fazom 2 ako nema dijeljenih konflikata
    └─► puna usklađenost → Faza 6

Faza 4 (Domenski model)
    └─► preduslov za Fazu 5
    └─► preduslov za punu Fazu 6 (više Održavanja, Oznake)

Faza 5 (Manifestacije na portalu)
    └─► zahtijeva Fazu 4 (Manifestacija + Održavanja za program)

Faza 6 (Završno usklađenje)
    └─► zahtijeva Faze 4 i 5 (gdje je relevantno)
```

**Obavezujući redoslijed** (IS-001-03): 1 → 2 → 3 → 4 → 5 → 6, osim ako posebna analiza i odobrenje potvrde da odstupanje ne narušava zavisnosti i IS-001-02.

---

# 9. Implementacione faze

### IS-001-03 — Implementacione faze

## 9.1 Faza 1 — Usklađenje postojećeg korisničkog interfejsa

| Stavka | Opis |
|--------|------|
| **Cilj** | Uskladiti postojeći javni UI sa TS-009 fazama 1–2 (terminologija, početna), bez novog domena |
| **Obuhvat** | Terminologija (Pretraga i pregled); Početna; Hero (provjera usklađenosti); istaknuti (max 3, prazno stanje); statistike (klikabilnost, naziv mjeseca); lista ispod kalendara (max 3, „Prikaži sve“); očuvanje postojećih javnih tokova; TD-TS9-01 (ne reklamirati `day` kao javni ekran) |
| **Zavisnosti** | Nema domenskih preduslova |
| **Rizik** | **Nizak** |
| **Uticaj na kod** | Controller (početna / query limiti); Model (eventualno validacija isticanja); Blade (index, navigacija); Route: ne; CSS: malo; JS: eventualno kalendar label; Baza: ne; Testovi: da |
| **Ulaz** | TS-009 usvojen; rizik/test/rollback plan; Faza 1 definisana |
| **Izlaz** | UI usklađen sa PO-TS9-03A (label), 06A–06D, BM-PK-15/21–23; testovi OK; PO potvrda |
| **Test** | Početna: Hero, ≤3 istaknuta, 3 statistike + navigacija na Pretragu i pregled, lista ≤3 / dan, dugme; navigacioni naziv |
| **Deploy** | Bez migracije; bez maintenance window; backup preporučen kao uobičajena praksa; feature flag nije neophodan; smoke: početna + events + show + archive |
| **Rollback** | **Potpuni rollback** (revert isporuke) — jednostavan |

**Minimalni skup izmjena:** proširenje postojeće početne i navigacije; bez novih ekrana.

---

## 9.2 Faza 2 — Pretraga i pregled

| Stavka | Opis |
|--------|------|
| **Cilj** | Centralna Pretraga i pregled sa filterima i URL stanjem (PO-TS9-04A), bez filtera Manifestacije |
| **Obuhvat** | Pretraga; filteri (datum, kategorija, lokacija — u granicama postojećeg modela); URL stanje; očuvanje konteksta; paginacija; sortiranje po usvojenim pravilima; „Poništi filtere“ |
| **Zavisnosti** | Preferira Fazu 1 (rename). Filter Manifestacije **nije** u ovoj fazi |
| **Rizik** | **Srednji** |
| **Uticaj na kod** | Controller (lista/query); Blade (events); Route: ne (ista ruta); CSS: malo; JS: ne obavezno; Baza: ne; Testovi: da |
| **Ulaz** | Faza 1 završena (ili odobren izuzetak); plan test/rollback |
| **Izlaz** | Filteri + URL u skladu sa BM-PK-18 / BR-257; regresija liste OK; PO potvrda |
| **Test** | Kombinovanje filtera; prazni rezultati; paginacija + query string; poništi; ulaz sa statistika / „Prikaži sve“ |
| **Deploy** | Bez migracije; bez MW; feature flag opciono ako treba postepeno uključivanje filter UI; smoke: events + index linkovi |
| **Rollback** | **Potpuni** ili **djelimični** (UI filtera) — bez migracije |

---

## 9.3 Faza 3 — Detalji događaja i Arhiva

| Stavka | Opis |
|--------|------|
| **Cilj** | Uskladiti Detalje događaja i Arhivu u **granicama postojećeg modela** (baseline TS-009 §7–§8), bez uvođenja punog domena Održavanja/MF |
| **Obuhvat** | Postojeći detalj; statusne oznake gdje model dozvoljava; navigacija i povratak (`back`); Arhiva; kartice Arhive; bez lažnog prikaza „više Održavanja“ dok domen ne postoji |
| **Zavisnosti** | Faze 1–2 nisu strogi preduslov; puna usklađenost sa BM-PK-09/13 → Faza 6 |
| **Rizik** | **Srednji** |
| **Uticaj na kod** | Controller (show/archive query samo uz jasno usklađenje); Blade (show, archive); Route: ne; Baza: ne |
| **Ulaz** | Plan test/rollback; jasna granica „postojeći model“ dokumentovana u CR |
| **Izlaz** | Prikaz i navigacija usklađeni u dogovorenom obimu; nema regresije 404/back; PO potvrda |
| **Test** | Show za published; back na arhivu/pregled sa paginacijom; arhiva lista; statusne oznake (gdje primjenjivo) |
| **Deploy** | Bez migracije; bez MW; feature flag nije neophodan; smoke: show + archive + events |
| **Rollback** | **Potpuni rollback** |

**Ograničenje:** Ne uvoditi nova polja/tabele u ovoj fazi.

---

## 9.4 Faza 4 — Razvoj domenskog modela

| Stavka | Opis |
|--------|------|
| **Cilj** | Uvesti / uskladiti domenske entitete potrebne za puni TS-009 potrošački sloj, prema TS-003/004/005/007/008 — **bez** portalskih MF ekrana (to je Faza 5) |
| **Obuhvat** | Manifestacija; Održavanja; Oznake; Mediji (ako su dio potvrđenog implementacionog obuhvata CR-a); migracije; modeli i relacije; urednički tokovi kao **zavisnost** (planirani TS-010 / postojeći admin — van IS-001 detalja) |
| **Zavisnosti** | Faze 1–3 završene ili odobren izuzetak. Zasebni CR za domen. |
| **Rizik** | **Visok** |
| **Uticaj na kod** | Novi/prošireni modeli; migracije; admin/urednički tokovi; javni portal u ovoj fazi **minimalno** (samo kompatibilnost čitanja); Testovi: obavezni domen + regresija portala |
| **Ulaz** | Usvojeni TS domena; migracioni plan; backup plan; test plan; rollback plan; procjena rizika potvrđena |
| **Izlaz** | Domen konzistentan sa BM/FS/TS; migracije uspješne na staging; javni portal nije regresiran; PO potvrda |
| **Test** | Integritet relacija; lifecycle; migracija podataka (dry-run); smoke javnog portala bez MF UI |
| **Deploy** | **Zahtijeva migraciju**; **prethodni backup obavezan**; maintenance window preporučen; posebna deploy provjera; feature flag samo ako smanjuje rizik ekspozicije nedovršenog UI (portal MF još nije Faza 5) |
| **Rollback** | **Rollback uz migraciju podataka** — složeno; plan unaprijed; u nekim scenarijima **rollback nije preporučljiv** bez restore-a |

**Migracioni tipovi (bez SQL-a):** nove tabele (npr. Manifestacija, Održavanja); proširenje postojeće tabele događaja (veza ka MF); eventualno katalog Oznaka / Medija. Najveći rizik: migracija flat datuma/vremena → Održavanja.

---

## 9.5 Faza 5 — Manifestacije u javnom portalu

| Stavka | Opis |
|--------|------|
| **Cilj** | PO-TS9-07A–07E na javnom portalu |
| **Obuhvat** | Navigacija „Manifestacije“; lista; Detalji manifestacije; program; blok Manifestacije na Detaljima događaja; filter po Manifestaciji na Pretrazi i pregledu |
| **Zavisnosti** | **Faza 4** (Manifestacija + Održavanja za program) |
| **Rizik** | **Srednji** (nakon stabilne Faze 4); **Visok** ako se radi prije Faze 4 |
| **Uticaj na kod** | Nove rute/prikazi; proširenje navigacije i show; filter na events; CSS za nove stranice; Baza: već Faza 4 |
| **Ulaz** | Faza 4 izlazni kriterijumi ispunjeni |
| **Izlaz** | Lista/detalj/program/blok/filter u skladu sa BM-PK-24–28 / BR-265–269; dvosmjerna navigacija; PO potvrda |
| **Test** | Paginacija liste MF; prazna lista; program (Otkazano, Vrijeme nije definisano); blok na show; filter MF; regresija index/events/archive |
| **Deploy** | Bez nove migracije ako je Faza 4 gotova; bez MW tipično; feature flag **preporučen** za navigaciju/rute MF dok se ne potvrdi stabilnost; smoke: MF lista/detalj + show + events filter |
| **Rollback** | **Djelimični** (sakrivanje nav/ruta) ili **potpuni** revert UI — bez undo Faze 4 |

---

## 9.6 Faza 6 — Završno usklađenje

| Stavka | Opis |
|--------|------|
| **Cilj** | Puna usklađenost javnog portala sa TS-009 nakon domena |
| **Obuhvat** | Puna podrška za više Održavanja; puna podrška za Oznake; završno usklađenje Detalja događaja; završno usklađenje Arhive; regresija; optimizacija (u granicama usvojenog); potvrda usklađenosti sa TS-009 |
| **Zavisnosti** | Faze 4 i 5 |
| **Rizik** | **Srednji do Visok** (široka regresija) |
| **Uticaj na kod** | Controller/query/Blade na više javnih prikaza; eventualno performanse upita; Testovi: široka regresija |
| **Ulaz** | Faze 4–5 završene; checklist usklađenosti sa TS-009 matricom |
| **Izlaz** | Nema neprihvatljive regresije; dokumentovana potvrda usklađenosti; PO potvrda zatvaranja implementacije TS-009 obuhvata |
| **Test** | End-to-end: početna → pretraga → detalj → MF → arhiva; statusne oznake; više Održavanja; oznake |
| **Deploy** | Po obimu; backup; MW po potrebi; smoke puni portal |
| **Rollback** | **Djelimični** po CR paketima; puni rollback teži zbjeći |

---

# 10. Upravljanje rizikom

### IS-001-04 — Upravljanje implementacionim rizikom

| Nivo | Kriterijumi |
|------|-------------|
| **Nizak** | Bez izmjena baze; male UI izmjene; jednostavan rollback |
| **Srednji** | Izmjene kontrolera ili query logike; pretraga/filteri/postojeći javni prikazi; obavezna regresija |
| **Visok** | Nove tabele; novi poslovni entiteti; migracije podataka; nove relacije; izmjene životnog ciklusa |

**Prije svake faze obavezno:** procjena rizika; plan testiranja; plan rollback-a.

**Eskalacija:** ako se rizik tokom rada poveća, rad na pogođenom dijelu se **zaustavlja** do nove procjene i odobrenja.

| Faza | Rizik | Ključni regresioni rizici |
|------|-------|---------------------------|
| 1 | Nizak | Pogrešan filter link; manje istaknutih; admin limit |
| 2 | Srednji | Paginacija/URL; prazni rezultati; ulaz sa početne |
| 3 | Srednji | 404 na show; pogrešan back; sadržaj arhive |
| 4 | Visok | Kalendar/liste/newsletter/admin; gubitak/korupcija termina |
| 5 | Srednji | Nav/layout; show; filter events |
| 6 | Srednji–Visok | Široka regresija svih javnih prikaza |

---

# 11. Ulazni i izlazni kriterijumi

### IS-001-05 — Ulazni i izlazni kriterijumi

**Ulazni (svaka faza):**

* BM, FS i TS zahtjevi usvojeni;
* faza definisana u IS-001;
* potrebne prethodne faze završene (ili odobren izuzetak);
* procijenjen rizik;
* postoji plan testiranja;
* postoji plan rollback-a.

**Tok:**

* izmjene unutar obuhvata faze;
* nema neodobrenih funkcionalnosti;
* izmjene sljedive prema dokumentaciji.

**Izlazni:**

* implementacija odgovara specifikaciji;
* planirani testovi uspješni;
* nema neprihvatljive regresije;
* potrebna dokumentacija usklađena (ako je bilo CR/TO ažuriranje — van izmjene TS-009 pravila);
* faza spremna za isporuku;
* Product Owner formalno potvrdio završetak.

**Odstupanje od specifikacije:** zaustaviti pogođeni dio → analiza → nova PO odluka → ažurirati dokumentaciju → tek onda nastavak.

---

# 12. Strategija testiranja

| Faza | Obavezno testirati | Ne smije biti narušeno |
|------|--------------------|------------------------|
| 1 | Početna (Hero, istaknuti, statistike, lista, dugme); navigacioni naziv | Arhiva, show, admin CRUD, ostali moduli platforme |
| 2 | Filteri, URL, poništi, paginacija, ulazi sa početne | Početna (izuzev linkova), arhiva |
| 3 | Show, back, arhiva kartice/paginacija, statusne oznake (obuhvat) | Admin, newsletter |
| 4 | Migracije (staging), relacije, lifecycle; smoke portala | Produkcijski podaci (backup); javni UI bez namjene Faze 5 |
| 5 | MF lista/detalj/program/blok/filter; regresija index/events/show/archive | Faza 4 podaci |
| 6 | E2E portal + regresija | Stabilnost performansi lista |

**Zajednički smoke nakon svake isporuke:** Početna, Pretraga i pregled, Detalji događaja, Arhiva događaja; nakon Faze 5 i Manifestacije.

---

# 13. Strategija isporuke

### IS-001-06 — Strategija isporuke (Deploy)

Opšta pravila:

* svaka faza samostalno isporučiva;
* jedna isporuka = jedna odobrena logička cjelina;
* naredna faza ne ide u produkciju prije stabilizacije prethodne;
* migracije se testiraju unaprijed;
* faze sa migracijama imaju posebnu deploy provjeru;
* nakon deploy-a — postimplementaciona provjera;
* vodi se evidencija isporuke.

| Faza | Bez MW? | Migracija? | Backup? | Feature flag? | Smoke odmah nakon deploy-a |
|------|:-------:|:----------:|:-------:|:-------------:|----------------------------|
| 1 | Da | Ne | Preporučen | Ne neophodan | Početna, events, show, archive |
| 2 | Da | Ne | Preporučen | Opciono | Events + linkovi sa početne |
| 3 | Da | Ne | Preporučen | Ne neophodan | Show, archive, back |
| 4 | Ne (preporučen MW) | **Da** | **Obavezan** | Opciono (ekspozicija) | Domen + smoke portala |
| 5 | Da (tipično) | Ne (ako 4 gotova) | Preporučen | **Preporučen** (MF nav/rute) | MF + show + events |
| 6 | Po obimu | Po obimu | Obavezan ako ima migracija | Po potrebi | Pun portal E2E |

---

# 14. Strategija rollback-a

### IS-001-07 — Strategija povratka (Rollback)

Klasifikacije: Potpuni; Djelimični; Uz migraciju podataka; Nije preporučljiv.

| Faza | Klasifikacija |
|------|----------------|
| 1 | Potpuni |
| 2 | Potpuni / djelimični |
| 3 | Potpuni |
| 4 | Uz migraciju podataka; u nekim slučajevima nije preporučljiv (restore) |
| 5 | Djelimični (UI/flag) ili potpuni revert UI |
| 6 | Djelimični po paketima |

**Aktivacija rollback-a:** ugrožena stabilnost; kritična greška; regresija onemogućava rad; odluka tehničke odgovorne osobe i/ili Product Owner-a.

Rollback se ograničava na pogođenu fazu kada je moguće.

**Evidencija:** datum/vrijeme; faza; razlog; aktivnosti; rezultat.  
**Nakon rollback-a:** analiza uzroka prije ponovne implementacije.

Plan rollback-a mora postojati **prije** isporuke.

---

# 15. Upravljanje promjenama specifikacije

### IS-001-08 — Upravljanje promjenama specifikacije

**TS-009 v1.0.0** je referentna specifikacija.

Proces promjene:

1. analiza;
2. prijedlog;
3. usaglašavanje;
4. Product Owner odluka;
5. dokumentovanje;
6. verzionisanje;
7. sljedivost.

Implementacioni problem **ne mijenja automatski** specifikaciju.

Ako je potrebna promjena poslovnog ili funkcionalnog pravila: prvo ažurirati i usvojiti dokumentaciju (BM/FS/TS), zatim implementacija.

Ne dozvoljavaju se nevidljive ili nedokumentovane izmjene stabilne specifikacije.

---

# 16. Matrica sljedivosti prema TS-009

| IS-001 | TS-009 / odluke | BM / FS (referenca) |
|--------|-----------------|---------------------|
| Faza 1 | IA-01, PO-TS9-03A, 05A/05B, 06A–06D, TD-TS9-01 | BM-PK-15–23; BR-117, 255–264 |
| Faza 2 | PO-TS9-03A, 04A | BM-PK-17–18; BR-256–257 |
| Faza 3 | §7 Detalji, §8 Arhiva (baseline) | BM-PK-05, 13; BR-106, 114 |
| Faza 4 | Granice TS-003/004/005/007/008 | BM-04/05/06/08/09 |
| Faza 5 | PO-TS9-07A–07E, §6 | BM-PK-24–28; BR-265–269 |
| Faza 6 | §7–§8 puni obuhvat + regresija | BM-PK-09–14; BR-110–115 |
| IS-001-02 | IA-01 | BM-PK-16; BR-255 |
| IS-001-08 | TS-009 Stable | — |

| Usvojena odluka | Primarne sekcije IS-001 |
|-----------------|-------------------------|
| IS-001-01 | §1, §2 |
| IS-001-02 | §5 |
| IS-001-03 | §9 |
| IS-001-04 | §10 |
| IS-001-05 | §11 |
| IS-001-06 | §13 |
| IS-001-07 | §14 |
| IS-001-08 | §15 |

---

# 17. Otvorena pitanja i pretpostavke

### Pretpostavke

* Implementacija prati IS-001 redoslijed faza osim uz odobreni izuzetak.
* Faza 4 obuhvata uredničke tokove samo kao zavisnost; detalj uredničkog portala ostaje u okviru planiranog TS-010 / zasebnih CR.
* Mediji u Fazi 4 ulaze samo ako CR potvrdi obuhvat (TS-008).

### Otvorena pitanja (bez PO odluke u IS-001)

1. Tačan CR raspored unutar Faze 4 (redoslijed Održavanja vs Manifestacija vs Oznake).
2. Da li Faza 3 mijenja query kriterijum Arhive ka punom BM-DG-04/`archived` statusu, ili ostaje datumski kriterijum do Faze 6.
3. Feature-flag infrastruktura — postoji li već u platformi ili se uvodi samo po potrebi Faze 5.
4. Obim Medija u prvom CR-u Faze 4 (samo fallback vs pun TS-008).

Ova pitanja **ne rješava** IS-001; zahtijevaju analizu i Product Owner / tehničko odobrenje prije pogođene faze.

---

# 18. Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 0.1.0 | 2026-07-31 | Nacrt. Formalizovane usvojene odluke IS-001-01 … IS-001-08. Ugrađeni relevantni zaključci radne implementacione analize TS-009 v1.0.0. Bez izmjene BM/FS/TS/implementacije. |

---

**Kraj dokumenta IS-001 v0.1.0 (Nacrt)**
