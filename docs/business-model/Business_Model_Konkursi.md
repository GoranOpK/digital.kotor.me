# Digital Kotor
# Poslovni model Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-BM-001
**Naziv:** Poslovni model Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** USVOJENO
**Verzija:** 0.2.10
**Datum:** 2026-09-03

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreiran početni kanonski poslovni model cjeline Konkursi. Otvorena struktura dokumenta. Ciljni poslovni model, ne opis postojeće implementacije. Poslovni sadržaj čeka analizu pravnog izvora (KN-PRO-001) i eksplicitne PO odluke. Bez izmišljenih uslova, procedura, statusa ili limita. |
| 0.2.0 | 2026-08-18 | Izvršena prva puna derivacija poslovnog modela iz KN-PRO-001 v0.1.3. Prenesene PO odluke Q1–Q6. Uspostavljena PRO→BM traceability. Status ostaje NACRT. FS/TS nijesu mijenjani. |
| 0.2.1 | 2026-08-18 | Uvedeno pravilo `PO DECISION REQUIRED` za sva pitanja koja izvor ne rješava jednoznačno; predložene opcije nisu usvojena poslovna pravila. Dodato dokumentaciono načelo KN-DOC-08: svaki budući pojedinačni konkurs ima katalog skraćenica po Digital Kotor obrascu; katalog konkretnog konkursa i njegov Document ID se ne izmišljaju unaprijed. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.2 | 2026-08-18 | Evidentirana PO DECISION 1 (V1 digitalni obuhvat): USVOJENO opcija 1. V1 obuhvata samo korake koje Odluka eksplicitno vezuje za digitalni servis. Cjelokupan pravni proces nije automatski softverski scope V1. Stavka 1 uklonjena iz `PO DECISION REQUIRED`. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.3 | 2026-08-18 | Evidentirana PO DECISION 2 (sastav Komisije): USVOJENO. Komisija ima tačno 3 člana — Predsjednik, Član 1, Član 2. Dodatne kolone trenutnog P3 nisu dodatni članovi. Odluka ima prednost nad obrascem. Stavka 2 uklonjena iz `PO DECISION REQUIRED`. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.4 | 2026-08-18 | Evidentirane PO DECISION 3 (životni ciklus/runtime statusi) i PO DECISION 4 (bodovanje). BM ne uvodi zatvoreni runtime katalog; ocjene 1–5 bez 0/praznog; svih 10 kriterijuma; prikaz bez decimala; prag 30. Stavke 3 i 4 uklonjene iz `PO DECISION REQUIRED`. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.5 | 2026-08-18 | Evidentirana PO DECISION 5 (katalog skraćenica pojedinačnog konkursa): USVOJENO — sljedeći slobodni `KN-RG-xxx`, bez rezervacije broja, bez KF tipa. Stavka 6 zatvorena granicom izvora Odluke (naknada/Poslovnik; nije nova PO odluka). Stavke 5 i 6 uklonjene iz `PO DECISION REQUIRED`. Preostaje stavka 7. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.6 | 2026-08-18 | Evidentirana PO DECISION 7 (terminološka standardizacija za budući KN-FS): USVOJENO opcija 1. Zadržavaju se izvorni termini po stvarnom kontekstu; ne uvodi se univerzalni zamjenski termin. Stavka 7 uklonjena iz `PO DECISION REQUIRED`. Registar nema otvorenih stavki. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.7 | 2026-08-18 | Post-audit editorial cleanup. Usklađen opis izvora u §1 (KN-PRO-001, Q1–Q6, PO DECISION 1–5 i 7). Uklonjena zastarjela legenda kolone „Klasa“ u §9. Usklađen status/izvor KN-BR registra u §9. Nema izmjene poslovnih pravila. Status ostaje NACRT. FS/TS/PRO/RG nijesu mijenjani. |
| 0.2.7 — PO APPROVAL | 2026-08-18 | Formalno PO usvajanje KN-BM-001 v0.2.7. Status: USVOJENO. Nema promjene poslovnih pravila. Zaključana BM baseline i SSOT za izradu KN-FS. |
| 0.2.8 / KN-PATCH-BM-001 | 2026-09-03 | Usklađivanje bodovanja sa potvrđenim PO pravilom: pojedinačne ocjene članova = cijeli brojevi 1–5; formula prosjeka i konačne ocjene nepromijenjena (Odluka/P3); konačni/ukupni skor prikazuje se na dvije decimale (DISPLAY); prag i rangiranje na CALCULATION VALUE. „Bez decimala“ ograničeno na pojedinačne ocjene. Izmijenjeni KN-BR-036, KN-BR-037, §17.3, §25, §35 BM PO DECISION 4. Status ostaje USVOJENO. KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.9 / KN-PATCH-BM-002 | 2026-09-03 | Administrativno usklađivanje živog pokazivača na KN-PRO-001: §34 `NACRT v0.1.3` → `NACRT v0.1.4`. Bez izmjene KN-BR, poslovnih pravila, žensko/mladi modela ili interpretacije Odluke. §1 zadržava v0.1.3 kao zaključanu derivacionu baseline. Status ostaje USVOJENO. KN-FS/KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.10 / KN-PATCH-BM-003 | 2026-09-03 | Administrativno usklađivanje živog pokazivača na KN-FS-001: §34 `NACRT — nije mijenjan ovim korakom` → `v0.2.12 USVOJENO`. Bez izmjene KN-BR, poslovnih pravila, žensko/mladi modela ili interpretacije Odluke. Status ostaje USVOJENO. KN-FS/KN-PRO/KN-TS/kod nijesu mijenjani. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (`KN-PATCH-BM-001`, …),
- datum,
- kratak naziv,
- kratak opis izmjene.

Naziv PATCH-a predstavlja zvanični naziv izmjene i koristi se u istoriji verzija. PATCH model: KN-RG-001 / DK-DS-001 §8. Izdat: `KN-PATCH-BM-001` (v0.2.8), `KN-PATCH-BM-002` (v0.2.9), `KN-PATCH-BM-003` (v0.2.10).

---

## Svrha dokumenta

Dokument predstavlja **ciljni** referentni poslovni model cjeline Konkursi za planiranje, razvoj, testiranje i održavanje.

U verziji 0.2.10 dokument zadržava punu derivaciju poslovnih pravila iz KN-PRO-001 i PO odluka Q1–Q6, uz usvojene **PO DECISION 1–5** i **PO DECISION 7**, granicu izvora za naknadu/Poslovnik (bivša stavka 6) i prazan `PO DECISION REQUIRED` registar, uz `KN-PATCH-BM-001` (prikaz konačnog/ukupnog skora na dvije decimale; pojedinačne ocjene = cijeli brojevi 1–5), `KN-PATCH-BM-002` (živi pokazivač na KN-PRO-001 v0.1.4 u §34) i `KN-PATCH-BM-003` (živi pokazivač na KN-FS-001 v0.2.12 USVOJENO u §34). **Ne** opisuje postojeći aplikacioni kod. **Ne** prenosi poslovna pravila iz postojeće implementacije ženskog preduzetništva. Status dokumenta: **USVOJENO**.

Identifikatori `KN-BR-*` su lokalni identifikatori poslovnih pravila ovog dokumenta (DK-DS-001 §5, MODULE-INTERNAL). **Nisu** Document ID, **nisu** Feature ID, **nisu** `KN-DOC-*` i **nisu** FS `BR-*`.

---

# Status razvoja Business Modela

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod | USVOJENO — derivacija iz KN-PRO-001 |
| 2. Svrha | USVOJENO — derivacija iz KN-PRO-001 |
| 3. Ciljevi | USVOJENO — derivacija iz KN-PRO-001 |
| 4. Opseg | USVOJENO — pravno obavezni proces; V1 = PO DECISION 1 (opcija 1) |
| 5. Usvojena dokumentaciona načela ovog paketa | USVOJENO — KN-DOC-01 … KN-DOC-08; PO DECISION 5 |
| 6. Obavezni obuhvat V1 | USVOJENO — PO DECISION 1 USVOJENO (opcija 1) |
| 7. Poslovni entiteti | USVOJENO — poslovni koncepti, ne podaci |
| 8. Poslovni akteri | USVOJENO — poslovne uloge, bez aplikacionog mapiranja |
| 9. Registar poslovnih pravila | USVOJENO — prva derivacija KN-BR-* |
| 10. Uslovi učešća | USVOJENO |
| 11. Vrste subvencija | USVOJENO — čl.12 + PO Q1 |
| 12. Prihvatljivi i neprihvatljivi troškovi | USVOJENO — čl.13 + PO Q5 |
| 13. Prijava i dokumentacija | USVOJENO — čl.14 + obrasci + PO Q2 |
| 14. Plan ulaganja P2 | USVOJENO |
| 15. Komisija i sjednice | USVOJENO — PO DECISION 2; naknada/Poslovnik = granica izvora Odluke |
| 16. Administrativna provjera i prigovor | USVOJENO — PO DECISION 3 |
| 17. Evaluacija i bodovanje | USVOJENO — čl.20 + P3 + PO Q4/Q6 + PO DECISION 2 + PO DECISION 4 |
| 18. Finansijska pravila | USVOJENO — čl.19 + PO Q1 |
| 19. Odluke i pravna zaštita | USVOJENO |
| 20. Ugovor i isplata | USVOJENO |
| 21. Realizacija i preusmjeravanje | USVOJENO |
| 22. Izvještavanje P4 / P4a | USVOJENO |
| 23. Povraćaj i post-obaveze | USVOJENO |
| 24. Javna promocija i izvještavanje Skupštini | USVOJENO — PO Q3 |
| 25. Business workflow | USVOJENO |
| 26. Business states | USVOJENO — procesni/narativni opis; PO DECISION 3 |
| 27. Poslovne validacije | USVOJENO |
| 28. Business edge cases | USVOJENO |
| 29. Out of scope / V1 granice | USVOJENO — V1 granica po PO DECISION 1 |
| 30. Legacy žensko preduzetništvo | USVOJENO — nije SSOT |
| 31. Traceability matrix | USVOJENO — KN-FS = PENDING |
| 32. Prenos PO odluka Q1–Q6 | USVOJENO |
| 33. Rječnik poslovnih pojmova | USVOJENO — PO DECISION 7 (opcija 1) |
| 34. Veza sa dokumentacijom | USVOJENO |
| 35. Registar usvojenih poslovnih odluka | USVOJENO — Q1–Q6 + PO DECISION 1–5 i 7; granica izvora stavka 6; BM USVOJENO |
| 36. PO DECISION REQUIRED | USVOJENO — nema otvorenih stavki |

---

# Pravila upravljanja Business Modelom

1. Poslovni model predstavlja ciljnu zvaničnu poslovnu specifikaciju cjeline Konkursi (KN-BM-001).

2. Posljednja **usvojena** verzija Business Modela predstavlja jedini izvor istine (Single Source of Truth) za poslovna pravila cjeline. KN-BM-001 **v0.2.10** je **USVOJENO** (formalno PO usvajanje v0.2.7; `KN-PATCH-BM-001` usklađuje prikaz konačnog skora; `KN-PATCH-BM-002` usklađuje živi pokazivač na KN-PRO-001 v0.1.4; `KN-PATCH-BM-003` usklađuje živi pokazivač na KN-FS-001 v0.2.12 USVOJENO) i predstavlja zaključanu BM baseline i SSOT za izradu KN-FS. Buduća izmjena poslovnog pravila ide kroz kontrolisanu izmjenu KN-BM-001, ne kroz KN-FS.

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu poslovnu ili projektnu odluku.

4. Cursor ima ulogu urednika verzionisanog dokumenta i ne smije samostalno unositi, pretpostavljati ili dopunjavati poslovna pravila.

5. Poslovna pravila se izvode isključivo iz: KN-PRO-001; usvojenih PO odluka Q1–Q6; PO DECISION 1–5; PO DECISION 7; KN-RG-001; važećeg Digital Kotor kanonskog standarda. Ne izmišljaju se. Ne preuzimaju se nekritički iz postojeće implementacije.

6. Ako postoji razlika između implementacije sistema i usvojenog Business Modela, implementacija se usklađuje sa Business Modelom, osim ako se odlukom ne izmijeni sam Business Model. Ovo pravilo je na snazi jer je KN-BM-001 **USVOJENO** (trenutno v0.2.10).

7. Source anomalies iz KN-PRO-001 ostaju u pravnom registru. BM koristi donesenu PO poslovnu interpretaciju i referencira KN-PRO; ne ponavlja cijeli anomaly register i ne predstavlja PO tumačenje kao doslovni tekst Odluke.

8. Ako izvor dopušta dvije ili više razumnih mogućnosti, ili ne daje jednoznačan odgovor, **ne bira se rješenje**. Stavka se označava `PO DECISION REQUIRED`. Preporuka u izvještaju **nije** odluka i **nije** usvojeno poslovno pravilo dok je Product Owner eksplicitno ne odobri.

---

# Upravljanje promjenama

Svaka izmjena poslovnog sadržaja Business Modela mora biti rezultat usvojene poslovne ili projektne odluke i evidentirana kroz odgovarajući PATCH (`KN-PATCH-BM-*`).

---

## Sadržaj

1. Uvod
2. Svrha
3. Ciljevi
4. Opseg
5. Usvojena dokumentaciona načela ovog paketa
6. Obavezni obuhvat V1
7. Poslovni entiteti
8. Poslovni akteri
9. Registar poslovnih pravila
10. Uslovi učešća
11. Vrste subvencija
12. Prihvatljivi i neprihvatljivi troškovi
13. Prijava i dokumentacija
14. Plan ulaganja P2
15. Komisija i sjednice
16. Administrativna provjera i prigovor
17. Evaluacija i bodovanje
18. Finansijska pravila
19. Odluke i pravna zaštita
20. Ugovor i isplata
21. Realizacija i preusmjeravanje
22. Izvještavanje P4 / P4a
23. Povraćaj i post-obaveze
24. Javna promocija i izvještavanje Skupštini
25. Business workflow
26. Business states
27. Poslovne validacije
28. Business edge cases
29. Out of scope / V1 granice
30. Legacy žensko preduzetništvo
31. Traceability matrix
32. Prenos PO odluka Q1–Q6
33. Rječnik poslovnih pojmova
34. Veza sa dokumentacijom
35. Registar usvojenih poslovnih odluka
36. PO DECISION REQUIRED

---

# 1. Uvod

Cjelina: **Konkursi** — podrška preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija.

Dokumentacioni namespace: **`KN`** (DK-DS-001 §1; KN-RG-001).

Ovo je **ciljni** poslovni model. Nije Technical Overview postojeće implementacije i nije opis trenutnog koda.

Jedini source of truth za poslovna pravila ovog nacrta:

1. **KN-PRO-001** v0.1.3 (`docs/pravni-okvir/Pravni_okvir_Konkursi.md`);
2. PO odluke **Q1–Q6** evidentirane u KN-PRO-001 §4.21;
3. usvojene **PO DECISION 1–5** i **PO DECISION 7**.

Pravni izvor Odluke (identitet akta, KN-PRO-001 §2):

**Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija**, Skupština Opštine Kotor, broj `11-016/26-12679`, donesena 17.06.2026., objavljena 22.06.2026.

Postojeći tok ženskog preduzetništva predstavlja raniju implementaciju cjeline Konkursi i biće predmet posebnog naknadnog dokumentacionog usklađivanja. Do završetka tog procesa ne predstavlja automatski kanonski izvor poslovnih pravila za KN-BM/FS/TS.

---

# 2. Svrha

Dokumentacija-first: poslovna pravila se usvajaju u KN-BM-001 prije funkcionalne i tehničke razrade.

Svrha cjeline Konkursi, izvedena iz KN-PRO-001 čl.1:

Urediti uslove, način i postupak dodjele subvencija kao instrumenta podrške preduzetnicima i mikro, malim i srednjim preduzećima (MMSP).

Ovaj dokument definiše šta cjelina poslovno radi, za koga, pod kojim uslovima i u kojim granicama. Ne definiše UI, aplikacione role, niti tehničku realizaciju.

---

# 3. Ciljevi

**Status:** USVOJENO — derivacija iz KN-PRO-001

Ciljevi cjeline, u granicama pravnog izvora:

* dodijeliti subvencije putem Javnog poziva (čl.5);
* raspodijeliti sredstva iz budžeta Opštine Kotor predviđena za razvoj preduzetništva (čl.2);
* ocijeniti planove ulaganja prema propisanim kriterijumima (čl.20–22);
* zaključiti ugovor, isplatiti sredstva i pratiti realizaciju (čl.26–29);
* izvijestiti Skupštinu o podržanim planovima, iznosima, realizaciji i efektima (čl.31).

Ne unose se indikatori, KPI niti očekivani ekonomski efekti koji nijesu navedeni u KN-PRO-001.

---

# 4. Opseg

**Status:** USVOJENO

## 4.1 Pravno obavezni poslovni proces

Poslovni opseg obuhvata cijeli životni ciklus koji uređuje Odluka:

objava Javnog poziva → prijava → dokumentacija → plan ulaganja → administrativna provjera → prigovor → Komisija → intervju → ocjenjivanje → bodovanje → rangiranje → odluka → rješenja → ugovor → isplata → realizacija → preusmjeravanje sredstava → monitoring → izvještavanje → povraćaj sredstava → promocija → izvještavanje Skupštini.

## 4.2 Šta ovaj dokument nije

* nije funkcionalna specifikacija (KN-FS-001 ostaje nedirnut);
* nije tehnička specifikacija (KN-TS-001 ostaje nedirnut);
* nije UI ni aplikacioni authorization model;
* nije opis postojećeg koda ženskog preduzetništva.

## 4.3 Digitalni kanal iz izvora

Odluka eksplicitno predviđa digitalni servis Opštine (`digital.kotor.me` / `www.digital.kotor.me`) za: objavu Javnog poziva (čl.6), elektronsko podnošenje prijave i dokumentacije (čl.14, čl.16), elektronsko popunjavanje P2 (čl.17), prigovor (čl.18), obavještavanje registrovanim mailom (čl.18), elektronsko bodovanje P3 (čl.20–21), generisanje predloga odluke i arhivu (čl.23), objavu odluke (čl.25).

Za ugovor, P4/P4a i zahtjev za preusmjeravanje izvor **ne** propisuje isključivo digitalni kanal. Ti koraci **nisu** u softverskom obuhvatu V1 (**PO DECISION 1**, opcija 1). Mogu se uključiti kasnije samo posebnom PO odlukom i odgovarajućom specifikacijom.

---

# 5. Usvojena dokumentaciona načela ovog paketa

Ova načela **nisu** poslovna pravila konkursa. Evidentiraju PO odluke kojima je otvoren kanonski dokumentacioni paket.

| ID | Načelo | Izvor |
|----|--------|--------|
| KN-DOC-01 | Otvara se rad na kanonskoj dokumentaciji cjeline `KN`. | PO odluka otvaranja paketa |
| KN-DOC-02 | Namespace `KN` je već usvojen (DK-DS-001) i ne predlaže se niti mijenja. | DK-DS-001 §1; KN-RG-001 |
| KN-DOC-03 | Dokumentacija prati DK-DS-001, METHODOLOGY i DK-RG-001. | PO odluka otvaranja paketa |
| KN-DOC-04 | Pravni i poslovni izvor novog modula je identifikovana Odluka. | KN-PRO-001 |
| KN-DOC-05 | Postojeća dokumentacija i implementacija ženskog preduzetništva se ovim paketom ne mijenjaju. | PO odluka otvaranja paketa |
| KN-DOC-06 | Legacy dokumentacija ženskog preduzetništva nije automatski source of truth za KN-BM/FS/TS. Biće predmet posebnog naknadnog usklađivanja. | PO odluka otvaranja paketa |
| KN-DOC-07 | Poslovna pravila novog modula izvode se iz zvanične Odluke i eksplicitnih PO odluka, ne nekritički iz postojeće implementacije. | PO odluka otvaranja paketa |
| KN-DOC-08 | Svaki budući pojedinačni konkurs u cjelini KN mora imati poseban katalog/dokument skraćenica tipa RG, po principu postojećeg Digital Kotor / Kalendar kulture dokumentacionog obrasca. KN-RG-001 ostaje modulni registar cjeline Konkursi i nije katalog pojedinačnog konkursa. Katalog konkretnog konkursa dobija sljedeći slobodni Document ID `KN-RG-xxx`; konkretan broj se **ne** rezerviše unaprijed. Prilikom formalnog otvaranja konkretnog konkursa prvo se provjerava KN registar i dodjeljuje sljedeći slobodni KN-RG broj. Ne uvodi se novi KF tip dokumenta. Katalog konkretnog konkursa se sada **ne** kreira. | PO DECISION 5 |

Oznake `KN-DOC-*` su dokumentaciona načela ovog paketa. **Nisu** BR identifikatori i **nisu** poslovna pravila iz Odluke. KN-DOC-08 **nije** Document ID konkretnog kataloga i **ne** rezerviše `KN-RG-xxx` broj.

---

# 6. Obavezni obuhvat V1

**Status:** USVOJENO — **PO DECISION 1 USVOJENO** (opcija 1)

**PO DECISION 1 — V1 digitalni obuhvat:** USVOJENO, opcija 1.

V1 obuhvata **samo** one korake poslovnog procesa koje važeća Odluka eksplicitno vezuje za digitalni servis (`digital.kotor.me` / `www.digital.kotor.me`).

Cjelokupan pravni proces **ne** smatra se automatski softverskim scope-om V1.

Koraci za koje Odluka ne propisuje digitalni kanal mogu kasnije biti uključeni samo na osnovu posebne PO odluke i odgovarajuće specifikacije.

V1 koraci već evidentirani u KN-PRO-001 kao eksplicitno vezani za digitalni servis (poglavlje 4.3):

* objava Javnog poziva (čl.6);
* elektronsko podnošenje prijave i dokumentacije (čl.14, čl.16);
* elektronsko popunjavanje P2 (čl.17);
* prigovor putem digitalnog servisa (čl.18);
* obavještavanje registrovanim mailom (čl.18);
* elektronsko bodovanje P3 (čl.20–21);
* generisanje predloga Odluke i arhiva aplikacije (čl.23);
* objava Odluke na digitalnom servisu (čl.25).

Ovaj spisak **ne** proširuje izvor. Ne uvodi nove digitalne kanale.

---

# 7. Poslovni entiteti

**Status:** USVOJENO

Ovo su poslovni koncepti. Nisu tabele, modeli ni kolone.

| Entitet | Značenje | Izvor |
|---------|----------|-------|
| Javni poziv | Instrument raspodjele subvencija za tekuću godinu | čl.2, čl.5–6 |
| Prijava | Zahtjev preduzetnika ili društva/MMSP za učešće | čl.14, čl.16, P1a/P1b |
| Plan ulaganja | Sastavni dio prijave (obrazac P2) | čl.17, P2 |
| Prateća dokumentacija | Isprave uz prijavu | čl.14 + obrasci (PO Q2) |
| Komisija za dodjelu subvencija | Tijelo koje raspisuje poziv, ocjenjuje i predlaže odluku | čl.7–11 |
| Lista za ocjenjivanje | Obrazac P3 | čl.20–21, P3 |
| Preliminarna rang lista | Rang po bodovima, bez utvrđenih iznosa | čl.21 |
| Konačna rang lista | Rang sa odlukom podržava/odbija i iznosima | čl.22 |
| Predlog Odluke o dodjeli | Akt koji Komisija predlaže Sekretarijatu | čl.23 |
| Odluka o dodjeli subvencija | Akt koji objavljuje Sekretarijat | čl.23–25 |
| Rješenje o dodjeli | Individualni akt korisniku | čl.24 |
| Rješenje o odbijanju | Individualni akt odbijenom podnosiocu | čl.24 |
| Izjava o odustanku | Akt podnosioca nakon odluke | čl.24 |
| Ugovor o dodjeli subvencija | Ugovor Sekretarijata i korisnika | čl.26 |
| Zahtjev za preusmjeravanje | Zahtjev korisnika zbog odstupanja od plana | čl.27 |
| Izvještaj o realizaciji | Obrazac P4 | čl.28, P4 |
| Finansijski izvještaj | Obrazac P4a | čl.28, P4a |
| Izvještaj Skupštini | Akt Sekretarijata | čl.31 |
| Tužba Upravnom sudu | Pravna zaštita protiv rješenja o odbijanju (nije žalba) | čl.24 |

---

# 8. Poslovni akteri

**Status:** USVOJENO

Ovo su **poslovni akteri**. Ne mapiraju se na aplikacione role, permissions ni middleware.

## 8.1 Preduzetnik

* **Poslovna uloga:** podnosilac prijave u obliku preduzetnika; potencijalni korisnik subvencije.
* **Odgovornosti:** podnosi P1a, P2 i prateću dokumentaciju; tačnost podataka; prigovor ako je prijava nepotpuna; realizacija; izvještavanje P4/P4a; ne-gašenje biznisa 3 godine ako je korisnik.
* **Prava:** učešće ako ispunjava uslove; prigovor u roku; tužba Upravnom sudu protiv rješenja o odbijanju.
* **Ograničenja:** elektronsko podnošenje; max dvije vrste za konkurisanje, podrška samo za jednu; zabrane iz čl.13–15 i čl.7.
* **Ulazi:** Javni poziv; obrasci P1a/P2; obavještenja Komisije/Sekretarijata.
* **Izlazi:** prijava; prigovor; ugovor; izvještaj; eventualni zahtjev za preusmjeravanje / odustanak / povraćaj.
* **Izvor:** čl.4, čl.14, čl.16, P1a.

## 8.2 Društvo / MMSP

* **Poslovna uloga:** podnosilac prijave kao društvo (obrazac P1b naslovljen kao DOO).
* **Odgovornosti:** podnosi P1b, P2 i prateću dokumentaciju društva; izvještavanje ako je korisnik.
* **Prava:** ista kao preduzetnik u pogledu učešća, prigovora i tužbe.
* **Ograničenja:** ista finansijska i eliminatorna pravila; član Komisije ili društvo čiji je predstavnik član nema pravo učešća.
* **Ulazi / izlazi:** kao kod preduzetnika, sa P1b umjesto P1a.
* **Izvor:** čl.4, čl.14, čl.16, P1b. Napomena: P1b je DOO forma; čl.14 govori šire o društvima/MMSP (KN-PRO FORM/TEXT INCONSISTENCY — bez korekcije izvora).

## 8.3 Odgovorno lice

* **Poslovna uloga:** ovlašćeno lice društva na P1b.
* **Odgovornosti:** potpis P1b; dostava ovjerene kopije lične karte; navedeni kontakt podaci.
* **Prava:** zastupa društvo u postupku prijave.
* **Ograničenja:** izvor ne dodjeljuje posebna procesna prava izvan prijave.
* **Ulazi:** podaci društva; Javni poziv.
* **Izlazi:** potpisana P1b.
* **Izvor:** čl.14, čl.16, P1b.

## 8.4 Korisnik subvencije

* **Poslovna uloga:** preduzetnik/društvo kome su dodijeljena sredstva i sa kojim je (ili treba biti) zaključen ugovor.
* **Odgovornosti:** de minimis izjava (čl.15); zaključenje ugovora; namjensko korišćenje; izvještaj P4/P4a; ne-gašenje/neprodaja 3 godine; eventualni povraćaj.
* **Prava:** isplata u roku; zahtjev za preusmjeravanje.
* **Ograničenja:** finansijski limiti; prihvatljivi troškovi; zabrana gotovine/sopstvenih usluga.
* **Ulazi:** Odluka/Rješenje o dodjeli; ugovor; sredstva.
* **Izlazi:** realizacija; izvještaji; zahtjev za preusmjeravanje.
* **Izvor:** čl.15, čl.24, čl.26–29.

## 8.5 Komisija za dodjelu subvencija

* **Poslovna uloga:** tijelo raspodjele i evaluacije.
* **Odgovornosti:** raspisivanje Javnog poziva; pregled dokumentacije i plana; intervju; evaluacija; rang liste; predlog Odluke; odluka o prigovoru.
* **Prava:** ocjena; bodovanje; utvrđivanje iznosa; elektronske sjednice.
* **Ograničenja:** kvorum; punovažne odluke i intervju uz prisustvo svih članova; tajnost bodova tokom ocjenjivanja; izjave o tajnosti i sukobu interesa; zabrana učešća članova.
* **Ulazi:** prijave; dokumentacija; prigovori; Poslovnik.
* **Izlazi:** oznaka potpunosti; odluka o prigovoru; P3; preliminarna i konačna rang lista; predlog Odluke.
* **Izvor:** čl.7, čl.18–23.
* **Sastav (PO DECISION 2):** tačno 3 člana — **Predsjednik**, **Član 1**, **Član 2**. Ne uvode se Član 3, Član 4 niti dodatni ocjenjivači. Institucionalne oznake iz čl.7 (predsjednik, sekretar Komisije, spoljašnji član) ne uvećavaju broj članova.

## 8.6 Predsjednik

* **Poslovna uloga:** predsjedava Komisijom; jedan od tri člana (PO DECISION 2). Pravni izvor: predsjednik Komisije, predstavnik Opštine (čl.7).
* **Odgovornosti:** elektronski unos zaključaka, napomena i obrazloženja odbijenih planova; zatvaranje Javnog poziva i pohrana u arhivu nakon predloga Odluke.
* **Prava:** članstvo i bodovanje.
* **Ograničenja:** ista kao ostali članovi (sukob interesa, tajnost).
* **Ulazi:** preliminarna lista; zaključci treće sjednice.
* **Izlazi:** uneseni zaključci; zatvoren Javni poziv.
* **Izvor:** čl.7, čl.22–23; PO DECISION 2.

## 8.7 Član 1

* **Poslovna uloga:** drugi od tri člana Komisije (PO DECISION 2). Bodovanje, intervju, potpis rang liste.
* **Odgovornosti:** individualno bodovanje; prisustvo intervjuu i punovažnim odlukama; izjave o tajnosti i sukobu interesa.
* **Prava:** uvid samo u sopstvene bodove tokom bodovanja.
* **Ograničenja:** nema pravo učešća u Javnom pozivu (lično ili društvo čiji je predstavnik).
* **Ulazi:** prijave, intervjui, P3.
* **Izlazi:** bodovi; potpis.
* **Izvor:** čl.7, čl.21–22; PO DECISION 2.

## 8.8 Član 2

* **Poslovna uloga:** treći od tri člana Komisije (PO DECISION 2). Bodovanje, intervju, potpis rang liste.
* **Odgovornosti:** individualno bodovanje; prisustvo intervjuu i punovažnim odlukama; izjave o tajnosti i sukobu interesa.
* **Prava:** uvid samo u sopstvene bodove tokom bodovanja.
* **Ograničenja:** nema pravo učešća u Javnom pozivu (lično ili društvo čiji je predstavnik).
* **Ulazi:** prijave, intervjui, P3.
* **Izlazi:** bodovi; potpis.
* **Izvor:** čl.7, čl.21–22; PO DECISION 2.

## 8.9 Zamjenski član

* **Poslovna uloga:** privremena zamjena odsutnog člana.
* **Odgovornosti / prava:** prema rješenju sekretara Sekretarijata.
* **Ograničenja:** imenovanje samo u slučaju odsustva.
* **Ulazi:** rješenje o imenovanju.
* **Izlazi:** učešće umjesto odsutnog člana.
* **Izvor:** čl.7.

## 8.10 Sekretarijat

* **Poslovna uloga:** operativni organ postupka, ugovora, nadzora, objave i izvještavanja.
* **Odgovornosti:** Poslovnik Komisije; objava Odluke (45 dana); izmjene/dopune Odluke pri odustanku; rješenja o dodjeli/odbijanju; ugovor; praćenje; odgovor na preusmjeravanje; prijem P4/P4a; promocija; izvještaj Skupštini; zahtjev za povraćaj.
* **Prava:** terenska kontrola; saglasnost na preusmjeravanje (uključujući ćutanje organa).
* **Ograničenja:** rokovi 45 / 10 / 10 / 3 / 30 dana prema članovima.
* **Ulazi:** predlog Odluke; izjava o odustanku; zahtjev za preusmjeravanje; izvještaji korisnika.
* **Izlazi:** Odluka; rješenja; ugovor; isplata; saglasnost; izvještaj Skupštini; zahtjev za povraćaj.
* **Izvor:** čl.7, čl.23–31.

## 8.11 Sekretar Sekretarijata

* **Poslovna uloga:** imenuje Komisiju i zamjene; razrješava članove.
* **Odgovornosti:** rješenje o imenovanju; postupak razrješenja uz pravo člana na izjašnjenje; imenovanje novog člana u roku 15 dana.
* **Prava:** razrješenje po razlozima čl.9.
* **Ograničenja:** razriješeni član ne može ponovo biti imenovan.
* **Ulazi:** potreba imenovanja / razlozi razrješenja / izjašnjenje člana.
* **Izlazi:** rješenja o imenovanju/razrješenju.
* **Izvor:** čl.7, čl.9–11.

## 8.12 Skupština Opštine Kotor

* **Poslovna uloga:** donosilac Odluke; primalac izvještaja.
* **Odgovornosti:** donosi Odluku (preambula, čl.32); prima izvještaj Sekretarijata (čl.31).
* **Prava:** nadzor kroz izvještaj.
* **Ograničenja:** izvor ne uređuje dalji skupštinski postupak po izvještaju.
* **Ulazi:** izvještaj Sekretarijata.
* **Izlazi:** Odluka kao pravni akt.
* **Izvor:** preambula, čl.31–32.

## 8.13 Ostali akteri iz KN-PRO (bez aplikacionog mapiranja)

| Akter | Uloga (sažeto) | Izvor |
|-------|----------------|-------|
| Predsjednik Skupštine | potpisnik akta | čl.32 |
| Upravni sud Crne Gore | odlučuje po tužbi protiv rješenja o odbijanju; tužba ne odlaže izvršenje | čl.24 |
| Opština Kotor | obezbjeđuje sredstva; kanali objave | čl.2, čl.6, čl.25 |

---

# 9. Registar poslovnih pravila

**Status:** USVOJENO — DERIVED FROM KN-PRO-001 / PO Q1–Q6 / PO DECISION 1–5 i PO DECISION 7

Konvencija: `KN-BR-{NNN}` = lokalni ID poslovnog pravila. Status svakog pravila: **DERIVED / USVOJENO**.

| ID | Naziv | Poslovno pravilo | Pravni izvor | PO | Akteri | Uslov | Posljedica | Napomena |
|----|-------|------------------|--------------|----|--------|-------|------------|----------|
| KN-BR-001 | Predmet uređivanja | Cjelina uređuje uslove, način i postupak dodjele subvencija preduzetnicima i MMSP. | KN-PRO čl.1 | — | Opština; podnosioci | Odluka na snazi | Subvencije se dodjeljuju samo po ovom postupku | |
| KN-BR-002 | Izvor sredstava | Sredstva obezbjeđuje budžet Opštine Kotor za tekuću godinu, pozicija za razvoj preduzetništva. Ukupan iznos definiše Javni poziv. | KN-PRO čl.2 | — | Opština; Komisija | Postoji budžetska pozicija | Raspodjela samo do iznosa Javnog poziva | Ne izmišljati apsolutni iznos |
| KN-BR-003 | Dodjela putem Javnog poziva | Subvencije se dodjeljuju putem Javnog poziva. | KN-PRO čl.5 | — | Komisija | Javni poziv raspisan | Učešće samo kroz Javni poziv | |
| KN-BR-004 | Učestalost | Javni poziv se može raspisati jedan put godišnje. | KN-PRO čl.5 | — | Komisija | Tekuća godina | Najviše jedan poziv u godini | |
| KN-BR-005 | Period raspisivanja | Javni poziv se raspisuje u trećem kvartalu tekuće godine. | KN-PRO čl.5 | — | Komisija | Treći kvartal | Poziv se raspisuje u tom periodu | |
| KN-BR-006 | Sadržaj Javnog poziva | Javni poziv naročito sadrži: ukupan iznos, najviši iznos, vrste subvencija, uslove, dokumentaciju, kriterijume evaluacije, rok i način podnošenja, informativne sastanke i druge bitne podatke. | KN-PRO čl.5 | — | Komisija | Raspisivanje | Poziv mora sadržati navedene elemente | |
| KN-BR-007 | Kanali objave | Javni poziv se objavljuje u jednom dnevnom listu, na vebsajtu Opštine, na digitalnom servisu i na Radio Kotoru. | KN-PRO čl.6 | — | Opština; Komisija | Raspisivanje | Objava na svim navedenim kanalima | |
| KN-BR-008 | Trajanje otvorenosti | Javni poziv je otvoren 20 dana od dana objavljivanja. | KN-PRO čl.6, čl.14 | — | Podnosioci | Objavljen poziv | Prijava samo u roku 20 dana | Isti rok za prijavu |
| KN-BR-009 | Elektronsko podnošenje | Prijava se podnosi Komisiji elektronski preko digitalnog servisa `digital.kotor.me`. | KN-PRO čl.14, čl.16 | PO DECISION 1 | Podnosilac | Otvoren poziv | Prijava po Odluci je elektronska | V1 digitalni korak |
| KN-BR-010 | ELIGIBILITY — teritorija | Pravo imaju preduzetnici i MMSP sa prebivalištem/sjedištem ili registrovanom djelatnošću na teritoriji opštine Kotor. | KN-PRO čl.4 | — | Podnosilac | Dokaz kroz dokumentaciju čl.14 | Bez uslova nema prava na subvenciju | ELIGIBILITY RULE |
| KN-BR-011 | ELIGIBILITY — krivični postupak | Protiv podnosioca se ne vodi krivični postupak pred Osnovnim sudom. | KN-PRO čl.4 | — | Podnosilac | Potvrda iz čl.14 | Bez uslova nema prava | ELIGIBILITY RULE |
| KN-BR-012 | ELIGIBILITY — porezi | Podnosilac uredno izmiruje poreske obaveze. | KN-PRO čl.4 | — | Podnosilac | Uvjerenja ne starija od 30 dana | Bez uslova nema prava | ELIGIBILITY RULE |
| KN-BR-013 | ELIGIBILITY — zakonito poslovanje | Podnosilac posluje u skladu sa važećim propisima. | KN-PRO čl.4 | — | Podnosilac | Opšti uslov | Bez uslova nema prava | ELIGIBILITY RULE |
| KN-BR-014 | ELIGIBILITY — zabrana člana Komisije | Član Komisije, ili društvo čiji je predstavnik član, nema pravo učešća u Javnom pozivu. | KN-PRO čl.7 | — | Član Komisije; društvo | Članstvo u Komisiji | Učešće zabranjeno | ELIGIBILITY RULE |
| KN-BR-015 | ELIGIBILITY — prethodni izvještaj ove Odluke | Ko nije dostavio P4 + P4a + prateću dokumentaciju za prethodno finansiran plan, ne može učestvovati u godini raspodjele. | KN-PRO čl.15 | — | Podnosilac | Postoji prethodno finansiranje bez izvještaja | Isključenje iz raspodjele | ELIGIBILITY RULE; vidi i KN-BR-021 |
| KN-BR-016 | ELIGIBILITY — raniji opštinski konkursi | Ko je koristio sredstva po Javnom konkursu za žensko preduzetništvo i/ili preduzetništvo mladih, a nije dostavio izvještaje, ne može učestvovati u godini dodjele. | KN-PRO čl.15 | — | Podnosilac | Ranije finansiranje bez izvještaja | Isključenje | ELIGIBILITY RULE; termin „Javni konkurs“ zadržan iz izvora |
| KN-BR-017 | ELIGIBILITY — nema starosnog uslova | Formulacija „MMSP mladih“ u čl.31 ne ograničava korisnike ove Odluke na mlade. Odluka ne propisuje starosni uslov. | KN-PRO čl.31, čl.4, čl.15, obrasci | Q3 | Svi podnosioci | — | Starost nije uslov učešća | PO interpretacija, ne korekcija izvora |
| KN-BR-018 | De minimis izjava | Preduzetnici/društva kojima su odobrena sredstva daju notarski ovjerenu pisanu izjavu (obrazac pomoći male vrijednosti) o de minimis pomoći u prethodne tri godine. | KN-PRO čl.15 | — | Korisnik | Odobrena sredstva | Obaveza izjave | Nije dio checkliste prijave (PO Q2: prijava = čl.14 + obrasci) |
| KN-BR-019 | Post-obaveza ne-gašenja | Korisnici se Ugovorom obavezuju da neće ugasiti biznis najmanje 3 godine od potpisivanja. | KN-PRO čl.15, čl.29 | — | Korisnik | Potpisan ugovor | Zabrana gašenja 3 godine | |
| KN-BR-020 | ELIMINATION — nepotpuna dokumentacija | Nepotpuna prijava se označava i Komisija je ne razmatra dalje. Nedostatak formalnih uslova (nepotpuna dokumentacija) je eliminatorni kriterijum. | KN-PRO čl.18, čl.20 elim. 1 | — | Komisija | Nepotpunost utvrđena | Eliminacija; pravo na prigovor | ELIMINATION RULE |
| KN-BR-021 | ELIMINATION — nedostavljen prethodni izvještaj | Nedostavljanje izvještaja o realizaciji sa finansijskim izvještajem i pratećom dokumentacijom (fakture i izvodi) iz prethodnog perioda finansiranog/djelimično finansiranog iz budžeta Opštine je eliminatorni kriterijum. | KN-PRO čl.20 elim. 2 | — | Komisija; podnosilac | Prethodno finansiranje bez izvještaja | Eliminacija | ELIMINATION RULE; povezano sa KN-BR-015 |
| KN-BR-022 | ELIMINATION — plan van vrsta subvencija | Plan ulaganja koji nije u vezi sa utvrđenim vrstama subvencija podleže eliminaciji. Vrste se utvrđuju prema čl.12; čl.13 ostaje izvor troškova. | KN-PRO čl.20 elim. 3, čl.12–13 | Q1 | Komisija | Plan nije u vezi sa vrstama/troškovima Odluke | Eliminacija | ELIMINATION RULE; izvorni tekst upućuje na čl.13 (anomaly u KN-PRO) |
| KN-BR-023 | Katalog vrsta | Postoje tri vrste: (1) autentični lokalni proizvodi/usluge; (2) inovativni proizvodi/usluge; (3) digitalizacija. | KN-PRO čl.12 | Q1 | Podnosilac; Komisija | Prijava | Vrsta se bira iz čl.12 | Ne iz čl.13 |
| KN-BR-024 | Dvije vrste / jedna podrška | Jedan preduzetnik/društvo može konkurisati za dvije vrste, a može biti podržan samo za jednu. | KN-PRO čl.19 | Q1 | Podnosilac; Komisija | Prijava / treća sjednica | Max 2 vrste u konkurenciji; max 1 odobrena | |
| KN-BR-025 | Limit 20% budžeta | Maksimalni iznos po korisniku ne može preći 20% planiranog budžeta za ovu namjenu. | KN-PRO čl.19 | — | Komisija | Određivanje iznosa | Iznos ≤ 20% budžeta | |
| KN-BR-026 | Limit 50% ulaganja | Iznos ne može biti veći od 50% potrebnih sredstava za realizaciju aktivnosti iz plana ulaganja. | KN-PRO čl.19 | — | Komisija | Određivanje iznosa | Iznos ≤ 50% potrebnog | |
| KN-BR-027 | Izuzetak 80% autentično | Za subvenciju za razvoj autentičnih lokalnih proizvoda i usluga (čl.12 tačka 1) iznos može biti do 80% potrebnih sredstava. | KN-PRO čl.19, čl.12 t.1 | Q1 | Komisija | Vrsta = autentični proizvodi/usluge | Iznos ≤ 80% potrebnog | Izvorni tekst upućuje na čl.13 t.1 |
| KN-BR-028 | Raspodjela po rang listi | Sredstva se raspodjeljuju po konačnoj rang listi do utroška sredstava. | KN-PRO čl.19, čl.22 | — | Komisija | Konačna rang lista | Podrška do utroška | |
| KN-BR-029 | Prag 30 bodova | Plan ulaganja sa konačnom ocjenom ispod 30 bodova ne podržava se. | KN-PRO čl.22 | PO DECISION 4 | Komisija | Konačna ocjena < 30 | Odbijanje | Usklađeno sa Odlukom i KN-PRO |
| KN-BR-030 | Skala 1–5 | Svaki pozitivni kriterijum ocjenjuje se ocjenom isključivo 1, 2, 3, 4 ili 5 prema P3 (1 = uopšte ne odgovara; 5 = u potpunosti odgovara). Nema ocjene 0, prazne ocjene niti neocijenjenog kriterijuma. | KN-PRO P3; čl.21 | Q4; PO DECISION 4 | Članovi Komisije | Bodovanje | Bod 1, 2, 3, 4 ili 5 po kriterijumu | EXPLICIT SOURCE RULE — FORM P3; PO DECISION 4 |
| KN-BR-031 | Deset pozitivnih kriterijuma | Evaluacija se vrši po 10 pozitivnih kriterijuma iz čl.20, razrađenih u P3. | KN-PRO čl.20, P3 | Q4 | Komisija | Intervju završen | Bodovanje svih 10 | P3 citation „Član 21 stav 2“ ne mijenja kriterijume |
| KN-BR-032 | Kriterijum 9 | Kriterijum 9 se vodi kao „Održivost i dugoročni efekti“. | KN-PRO čl.20 t.9, P3, P2 VII | Q6 | Komisija | Bodovanje | Koristi se pun naziv | „......“ ostaje source anomaly u KN-PRO |
| KN-BR-033 | Tri eliminatorna kriterijuma | Sva 3 eliminatorna kriterijuma čl.20 ostaju na snazi iako P3 operacionalizuje provjeru potpunosti dokumentacije. | KN-PRO čl.20, P3 | Q4 | Komisija | Evaluacija | P3 ne ukida elim. 2 i 3 | |
| KN-BR-034 | Individualno bodovanje | Svaki od tri člana Komisije (Predsjednik, Član 1, Član 2) mora ocijeniti svih 10 pozitivnih kriterijuma. | KN-PRO čl.21 | PO DECISION 2; PO DECISION 4 | Članovi Komisije | Nakon intervjua | Individualni bodovi za svih 10 kriterijuma | Nema neocijenjenog kriterijuma |
| KN-BR-035 | Privatnost bodova | Tokom bodovanja članovi imaju uvid samo u svoje bodove. | KN-PRO čl.21 | — | Članovi Komisije | Bodovanje u toku | Nema uvida u tuđe bodove | |
| KN-BR-036 | Prosječna ocjena | Prosječna ocjena po kriterijumu = zbir pojedinačnih ocjena sva 3 člana / 3. Pojedinačne ocjene su cijeli brojevi 1–5 (KN-BR-030). **CALCULATION VALUE** prosjeka smije imati decimale koje proizlaze iz formule. Ne uvodi se novo poslovno pravilo zaokruživanja prosjeka. | KN-PRO čl.21 | PO DECISION 4; KN-PATCH-BM-001 | Komisija | Svi članovi ocijenili kriterijum | Prosjek po kriterijumu (formula) | Formula ostaje usklađena sa Odlukom / KN-PRO |
| KN-BR-037 | Konačna ocjena | Konačna ocjena (**CALCULATION VALUE**) = zbir prosječnih ocjena svih 10 kriterijuma. **Konačni/ukupni skor prikazuje se na dvije decimale** (**DISPLAY VALUE**). Prag i rangiranje koriste stvarnu vrijednost formule, ne zasebno poslovno rounding pravilo. | KN-PRO čl.21 | PO DECISION 4; KN-PATCH-BM-001 | Komisija | Svi kriterijumi ocijenjeni | Konačna ocjena (formula); prikaz na 2 decimale | Usklađeno sa Odlukom, KN-PRO i KN-BR-029 |
| KN-BR-038 | Preliminarna rang lista | Po završetku ocjenjivanja automatski se formira preliminarna rang lista sa bodovima, bez utvrđenih iznosa. | KN-PRO čl.21 | — | Komisija | Svi planovi ocijenjeni | Preliminarna lista | |
| KN-BR-039 | Treća sjednica | Komisija zakazuje treću sjednicu u roku 7 dana od druge sjednice i usmenih intervjua. | KN-PRO čl.21 | — | Komisija | Druga sjednica/intervjui održani | Treća sjednica ≤ 7 dana | |
| KN-BR-040 | Konačna rang lista | Na trećoj sjednici Komisija za svaki plan konstatuje da li se podržava ili odbija i iznos subvencije. Lista sadrži ime/naziv, vrstu, bodove, potrebna sredstva, odobrena sredstva i potpise svih članova. | KN-PRO čl.22 | — | Komisija; predsjednik | Treća sjednica | Konačna rang lista | Predsjednik unosi zaključke i obrazloženja odbijenih |
| KN-BR-041 | Prva sjednica | Komisija zakazuje prvu sjednicu najkasnije 7 dana od isteka roka za prijavu i pregleda elektronski zaprimljene prijave. | KN-PRO čl.18 | — | Komisija | Istek roka prijave | Pregled potpunosti | |
| KN-BR-042 | Prigovor | Komisija obavještava podnosioca nepotpune prijave registrovanim mailom o pravu na prigovor putem digitalnog servisa u roku 3 dana od slanja obavještenja. | KN-PRO čl.18 | — | Komisija; podnosilac | Nepotpuna prijava | Rok 3 dana za prigovor | |
| KN-BR-043 | Odluka o prigovoru | Komisija odlučuje o prihvatanju ili odbijanju prigovora u roku 7 dana od prijema. | KN-PRO čl.18 | — | Komisija | Prigovor primljen | Prihvaćen ili odbijen | |
| KN-BR-044 | Druga sjednica i intervju | Druga sjednica i intervju za potpune prijave zakazuju se u roku 7 dana od prve sjednice. | KN-PRO čl.18 | — | Komisija; podnosilac | Potpuna prijava | Intervju | |
| KN-BR-045 | Kvorum | Kvorum je prisustvo većine članova. Bez kvoruma sjednica se odlaže. | KN-PRO čl.7 | — | Komisija | Sjednica | Odlaganje ako nema kvoruma | |
| KN-BR-046 | Punovažnost i intervju | Za intervju i punovažne odluke obavezno je prisustvo svih članova. | KN-PRO čl.7 | — | Komisija | Intervju / odluka | Svi članovi prisutni | |
| KN-BR-047 | Elektronske sjednice | Sjednice mogu biti elektronske (zoom/teams ili uključenje preko viber/whatsapp). | KN-PRO čl.7 | — | Komisija | Sjednica | Dozvoljen elektronski rad | Nije tehnički izbor platforme u BM |
| KN-BR-048 | Javnost rada | Rad Komisije je javan. | KN-PRO čl.7 | — | Komisija | Rad Komisije | Javnost | |
| KN-BR-049 | Tajnost i sukob interesa | Svi članovi potpisuju Izjavu o tajnosti podataka i Izjavu o sprečavanju sukoba interesa. | KN-PRO čl.7 | — | Članovi Komisije | Imenovanje | Potpisane izjave | |
| KN-BR-050 | Sastav Komisije | Komisija ima tačno 3 člana. Poslovne oznake: Predsjednik, Član 1, Član 2. Ne uvode se Član 3, Član 4 niti dodatni ocjenjivači. Pravni izvor čl.7: 2 predstavnika Opštine i 1 spoljašnji član; mandat 1 godina. Institucionalne oznake Odluke ne uvećavaju broj članova. | KN-PRO čl.7 | PO DECISION 2 | Sekretar Sekretarijata | Imenovanje | Komisija od 3 člana | Ako P3 ima više kolona, to nisu dodatni članovi |
| KN-BR-051 | Predlog i objava Odluke | Komisija generiše predlog Odluke putem digitalnog servisa i predlaže Sekretarijatu. Sekretarijat objavljuje Odluku u roku 45 dana od isteka roka za prijavu. | KN-PRO čl.23 | — | Komisija; Sekretarijat | Konačna rang lista | Objavljena Odluka | |
| KN-BR-052 | Zatvaranje poziva | Nakon predloga Odluke predsjednik Komisije zatvara Javni poziv i pohranjuje ga u arhivu aplikacije. | KN-PRO čl.23 | — | Predsjednik Komisije | Predlog Odluke predat | Poziv zatvoren; arhiva | |
| KN-BR-053 | Odustanak | Ako podnosilac odustane nakon Odluke, daje Izjavu o odustanku Sekretarijatu. Sekretarijat donosi odluku o izmjeni i dopuni. Prvobitno odobrena sredstva ostaju u budžetu. | KN-PRO čl.24 | — | Podnosilac; Sekretarijat | Odluka donesena | Izmjena Odluke; sredstva ostaju u budžetu | |
| KN-BR-054 | Rješenja | Sekretarijat donosi Rješenja o dodjeli (dostava korisnicima) i Rješenja o odbijanju (obavještenje o razlozima). | KN-PRO čl.24 | — | Sekretarijat | Odluka | Individualna rješenja | |
| KN-BR-055 | Tužba | Protiv rješenja o odbijanju može se podnijeti tužba Upravnom sudu u roku 20 dana od prijema. Tužba ne odlaže izvršenje Odluke o dodjeli. Tužba nije žalba. | KN-PRO čl.24 | — | Podnosilac; Upravni sud | Rješenje o odbijanju | Tužba; bez suspenzivnog dejstva | |
| KN-BR-056 | Dostava i objava Odluke | Odluka se dostavlja svim učesnicima i objavljuje na vebsajtu Opštine, digitalnom servisu, lokalnom javnom emiteru i oglasnoj tabli. | KN-PRO čl.25 | — | Sekretarijat | Donesena Odluka | Dostava + objava | |
| KN-BR-057 | Zaključenje ugovora | Nakon donošenja i javnog objavljivanja Odluke, Sekretarijat zaključuje Ugovor u roku 10 dana od izvršnosti Odluke. | KN-PRO čl.26 | — | Sekretarijat; korisnik | Izvršna Odluka | Ugovor | |
| KN-BR-058 | Sadržaj ugovora | Ugovor uređuje međusobna prava i obaveze, način korišćenja sredstava, izvještavanje i nadzor nad realizacijom. | KN-PRO čl.26 | — | Ugovorne strane | Zaključenje | Obaveze iz ugovora | Bez izmišljenih klauzula |
| KN-BR-059 | Isplata | U roku 10 dana od potpisivanja ugovora sredstva se uplaćuju na račun korisnika. | KN-PRO čl.26 | — | Sekretarijat; korisnik | Potpisan ugovor | Isplata ≤ 10 dana | |
| KN-BR-060 | Nadzor | Realizaciju zaključenog ugovora prati Sekretarijat (tok aktivnosti i utrošak sredstava). Može izvršiti terensku kontrolu. | KN-PRO čl.26–27 | — | Sekretarijat | Ugovor na snazi | Praćenje / kontrola | |
| KN-BR-061 | Preusmjeravanje | Pri odstupanju / nemogućnosti utroška (npr. nedostupnost predmeta nabavke, skok cijena) korisnik podnosi zahtjev za preusmjeravanje uz detaljno obrazloženje. | KN-PRO čl.27 | — | Korisnik; Sekretarijat | Odstupanje od plana | Zahtjev | Forma nije propisana |
| KN-BR-062 | Ćutanje organa | Sekretarijat odgovara u roku 3 dana; u protivnom smatra se da je saglasan. | KN-PRO čl.27 | — | Sekretarijat | Zahtjev podnesen | Saglasnost ako nema odgovora u 3 dana | |
| KN-BR-063 | Izvještaj korisnika | Korisnik podnosi P4 + P4a + fakture i izvode banke do isteka roka iz ugovora. Opravdava subvenciju i sopstveno učešće. | KN-PRO čl.28, P4, P4a | — | Korisnik | Rok iz ugovora | Dostavljen izvještaj | |
| KN-BR-064 | Povraćaj — izvještaj | Ako korisnik ne dostavi izvještaje i dokumentaciju u roku, to se smatra nenamjenskim trošenjem sredstava i nastaje obaveza povraćaja u cjelosti na zahtjev Sekretarijata. | KN-PRO čl.29 | — | Korisnik; Sekretarijat | Nedostavljen izvještaj | Povraćaj u cjelosti | |
| KN-BR-065 | Povraćaj — gašenje/prodaja | Gašenje ili prodaja biznisa prije isteka 3 godine od potpisivanja ugovora obavezuje na povraćaj u cjelosti na zahtjev Sekretarijata. | KN-PRO čl.29, čl.15 | — | Korisnik; Sekretarijat | Gašenje/prodaja < 3 godine | Povraćaj u cjelosti | |
| KN-BR-066 | Promocija | Sekretarijat može javno predstaviti realizovane aktivnosti i rezultate korisnika i organizovati prezentacije, sajmove i promotivne događaje. | KN-PRO čl.30 | — | Sekretarijat; korisnici | Podržani planovi | Javna promocija (ovlašćenje, ne obaveza) | |
| KN-BR-067 | Izvještaj Skupštini | Sekretarijat podnosi Skupštini izvještaj o podržanim planovima, iznosu dodijeljenih subvencija, realizovanim projektima i efektima, u roku 30 dana nakon isteka ugovornog roka za izvještaje korisnika. | KN-PRO čl.31 | Q3 | Sekretarijat; Skupština | Istek roka korisničkih izvještaja | Izvještaj ≤ 30 dana | Bez starosnog scope-a |
| KN-BR-068 | Imenovanje zamjene člana | U slučaju prestanka mandata prije isteka, sekretar Sekretarijata imenuje novog člana u roku 15 dana. Mandat novog člana traje do isteka mandata Komisije. Razriješeni ne može ponovo biti imenovan. | KN-PRO čl.11 | — | Sekretar Sekretarijata | Prestanak mandata | Novi član ≤ 15 dana | |
| KN-BR-069 | Naknada članovima | Članovi Komisije imaju pravo na naknadu za rad. | KN-PRO čl.7 | — | Članovi Komisije; Opština | Članstvo | Pravo na naknadu | Odluka ne propisuje iznos, način obračuna ni dinamiku isplate; ne uvode se bez posebnog pravnog izvora |
| KN-BR-070 | Poslovnik | Komisija radi u skladu sa Poslovnikom o radu Komisije koji donosi Sekretarijat. | KN-PRO čl.7 | — | Sekretarijat; Komisija | Rad Komisije | Primjena Poslovnika | Odluka ne propisuje sadržaj Poslovnika niti dodatne procedure koje nijesu u Odluci; ne uvode se bez posebnog pravnog izvora |
| KN-BR-071 | Bruto plate — ugovor i period | Za bruto plate novozaposlenih: (1) ugovor o radu mora biti zaključen na najmanje 12 mjeseci; (2) prihvatljivi/subvencionisani trošak bruto plate može obuhvatiti period do 6 mjeseci. | KN-PRO čl.13, P4 pitanje 4 | Q5 | Korisnik; Komisija | Trošak plata u planu/izvještaju | Ugovor ≥ 12 mj.; subvencionisani period ≤ 6 mj. | PO tumačenje za kanonski BM, ne korekcija izvora |
| KN-BR-072 | Zabranjeno otuđenje imovine | Materijalna imovina nabavljena iz subvencije ne smije se otuđiti minimalno 1 godinu od dana nabavke. | KN-PRO čl.13 | — | Korisnik | Nabavka materijalne imovine | Zabrana otuđenja ≥ 1 godinu | |
| KN-BR-073 | Zabranjeni troškovi prije ugovora | Troškovi podnosioca nastali prije potpisivanja Ugovora nisu prihvatljivi. | KN-PRO čl.13 | — | Korisnik | Trošak prije ugovora | Neprihvatljiv trošak | Izvor citira „Ugovor iz člana 27“; BM ne prepravlja citat |
| KN-BR-074 | Zabranjena gotovina i natura | Gotovinska plaćanja, plaćanja u naturi i robna razmjena nisu prihvatljivi. | KN-PRO čl.13 | — | Korisnik | Način plaćanja | Neprihvatljiv trošak | |
| KN-BR-075 | Zabranjene sopstvene usluge | Plaćanja za sopstvene usluge nisu prihvatljiva. | KN-PRO čl.13 | — | Korisnik | Plaćanje sebi | Neprihvatljiv trošak | |
| KN-BR-076 | Marketing vremenska ograničenja | Website izrada/unapređenje i održavanje, zakup domena/hosting i oglašavanje: do 12 mjeseci, u okviru prihvatljivih marketing troškova. | KN-PRO čl.13 | — | Korisnik | Marketing trošak | Period ≤ 12 mjeseci za navedene stavke | |
| KN-BR-077 | Sopstveno učešće | Sopstveno učešće se navodi u P2 i opravdava u izvještaju uz subvenciju. | KN-PRO čl.17, čl.28 | — | Podnosilac; korisnik | Prijava / izvještaj | Evidentirano i opravdano učešće | Izvor ne propisuje fiksni procenat sopstvenog učešća |
| KN-BR-078 | Starost uvjerenja | Uvjerenja o porezima navedena u čl.14 ne smiju biti starija od 30 dana. | KN-PRO čl.14 | Q2 | Podnosilac | Prijava | Dokument stariji od 30 dana nije valjan za tu stavku | |
| KN-BR-079 | PDF prateća dokumentacija | Prateća dokumentacija se elektronski učitava u PDF formatu putem digitalnog servisa. | KN-PRO čl.16 | Q2 | Podnosilac | Prijava | PDF upload | Dokumentacioni sadržaj = čl.14 + obrasci |
| KN-BR-080 | Sastavni obrasci | P1a, P1b, P2, P3, P4 i P4a su sastavni dio Odluke i poslovni izvor. | KN-PRO čl.14–17, čl.15, čl.20, čl.28 | — | Svi | Postupak | Primjena obrazaca | |

---

# 10. Uslovi učešća

**Status:** USVOJENO

Razlikuju se **ELIGIBILITY RULE** i **ELIMINATION RULE**. Ne spajaju se.

## 10.1 ELIGIBILITY RULE — osnovni uslovi (čl.4)

1. Prebivalište/sjedište ili registrovana djelatnost na teritoriji opštine Kotor — KN-BR-010.
2. Ne vodi se krivični postupak pred Osnovnim sudom — KN-BR-011.
3. Uredno izmirivanje poreskih obaveza — KN-BR-012.
4. Poslovanje u skladu sa važećim propisima — KN-BR-013.

## 10.2 ELIGIBILITY RULE — zabrane i prethodne obaveze

5. Član Komisije / društvo čiji je predstavnik član — nema pravo učešća — KN-BR-014.
6. Nedostavljen P4/P4a za prethodno finansiran plan — nema učešća u godini raspodjele — KN-BR-015.
7. Ranije opštinsko finansiranje (žensko preduzetništvo i/ili preduzetništvo mladih) bez izvještaja — nema učešća — KN-BR-016.
8. Nema starosnog uslova korisnika — KN-BR-017 (PO Q3).

## 10.3 ELIMINATION RULE (čl.18 i čl.20)

| # | Pravilo | Izvor | BM ID |
|---|---------|-------|-------|
| E1 | Nedostatak formalnih uslova (nepotpuna dokumentacija); nepotpuna prijava se ne razmatra dalje | čl.18, čl.20 elim. 1 | KN-BR-020 |
| E2 | Nedostavljanje izvještaja o realizaciji sa finansijskim izvještajem i pratećom dokumentacijom iz prethodnog budžetskog finansiranja | čl.20 elim. 2 | KN-BR-021 |
| E3 | Plan ulaganja nije u vezi sa utvrđenim vrstama subvencija / okvirom Odluke | čl.20 elim. 3; PO Q1 | KN-BR-022 |

P3 operacionalizuje naznaku potpune dokumentacije, ali **ne ukida** E2 i E3 (PO Q4).

---

# 11. Vrste subvencija

**Status:** USVOJENO  
**Izvor vrsta:** član 12 (PO Q1). Član 19→13 ostaje source anomaly u KN-PRO i **ne** utiče na ovaj katalog.

Ne izmišljaju se tehničke enum vrijednosti. Nazivi su poslovni.

| # | Poslovni naziv | Opis | Pravni izvor | Relevantni prihvatljivi troškovi |
|---|----------------|------|--------------|----------------------------------|
| 1 | Subvencije za razvoj autentičnih lokalnih proizvoda/usluga | Razvoj proizvoda/usluga zasnovanih na lokalnoj tradiciji, kulturnom naslijeđu, prirodnim resursima ili specifičnim zanatskim vještinama opštine | čl.12 t.1 | Troškovi iz čl.13 koji služe toj vrsti; finansijski izuzetak do 80% (KN-BR-027) |
| 2 | Subvencije za razvoj inovativnih proizvoda/usluga | Razvoj novog proizvoda/usluge ili značajno unapređenje; primjena novih tehnologija | čl.12 t.2 | Troškovi iz čl.13 koji služe toj vrsti; opšti limit 50% (KN-BR-026) |
| 3 | Subvencije za digitalizaciju | Nabavka softvera, digitalne opreme i alata; izrada/unapređenje veb stranica; online prodaja; digitalni marketing; digitalna transformacija | čl.12 t.3 | Troškovi iz čl.13 koji služe toj vrsti; opšti limit 50% (KN-BR-026) |

Broj vrsta = **3**. Podnosilac može konkurisati za **dvije**, biti podržan za **jednu** (KN-BR-024).

---

# 12. Prihvatljivi i neprihvatljivi troškovi

**Status:** USVOJENO  
**Izvor:** KN-PRO čl.13. Svaki unos ima pravni izvor.

## 12.1 Prihvatljivi troškovi

| # | Poslovno pravilo | Izvor |
|---|------------------|-------|
| 1 | Materijalna imovina (npr. mašine, alat, ICT oprema), uz zabranu otuđenja min. 1 godinu od nabavke | čl.13; KN-BR-072 |
| 2 | Nematerijalna imovina (softver, baza podataka, franšiza i sl.) ako je neophodna | čl.13 |
| 3 | Nabavka sirovina i repro materijala | čl.13 |
| 4 | Bruto plate novozaposlenih: ugovor min. 12 mjeseci; subvencionisani trošak plate do 6 mjeseci | čl.13; PO Q5; KN-BR-071 |
| 5 | Marketing i oglašavanje: logo/branding; website izrada ili unapređenje i održavanje do 12 mjeseci; zakup domena/hosting do 12 mjeseci; štampanje; oglašavanje do 12 mjeseci; digitalni sadržaj; promotivni događaji; oprema; pakovanje/ambalaža; učešće na sajmovima/izložbama i sl. | čl.13; KN-BR-076 |
| 6 | Uvođenje sistema automatizacije online prodaje i e-commerce | čl.13 |
| 7 | Troškovi sertifikacije i uvođenja standarda i sl. | čl.13 |

## 12.2 Neprihvatljivi troškovi

| # | Poslovno pravilo | Izvor |
|---|------------------|-------|
| 1 | Aktivnosti u nadležnosti/odgovornosti Vlade (npr. formalno obrazovanje, formalna zdravstvena zaštita) | čl.13 |
| 2 | Kupovina/raspodjela humanitarne pomoći | čl.13 |
| 3 | Jednokratna izrada/štampanje knjiga/brošura/biltena/časopisa ako publikacije nisu dio šireg programa/kontinuiranih aktivnosti | čl.13 |
| 4 | Nezakonite/štetne aktivnosti (npr. igre na sreću, duvan, alkoholna pića), **izuzev** proizvodnje vina i voćnih rakija | čl.13 |
| 5 | Poljoprivreda/marikutura/ribarstvo tamo gdje su posebna sredstva iz budžeta Opštine ili Ministarstva | čl.13 |
| 6 | Carine i uvozni troškovi | čl.13 |
| 7 | Novčane kazne i troškovi parničnog postupka | čl.13 |
| 8 | Troškovi nastali prije potpisivanja Ugovora | čl.13; KN-BR-073 |
| 9 | Gotovinska plaćanja, plaćanja u naturi i robna razmjena | čl.13; KN-BR-074 |
| 10 | Plaćanja za sopstvene usluge | čl.13; KN-BR-075 |
| 11 | Nemoralne i nelegalne aktivnosti | čl.13 |

Izvorni tekst „minimalno 12 mjeseci (do 6 mjeseci)“ ostaje u KN-PRO. BM primjenjuje PO Q5 (KN-BR-071), ne kao korekciju izvora.

---

# 13. Prijava i dokumentacija

**Status:** USVOJENO  
**Dokumentacioni zahtjevi:** član 14 + sastavni obrasci (PO Q2). Upućivanje čl.16 na čl.15 ostaje cross-reference anomaly u KN-PRO i **ne** mijenja ovu checklistu.

Ne projektuje se UI forma.

## 13.1 Tip podnosioca i obrazac

| Tip podnosioca | Obrazac | Izvor |
|----------------|---------|-------|
| Preduzetnik | P1a | čl.14, čl.16, P1a |
| Društvo / MMSP | P1b | čl.14, čl.16, P1b |

P1b je u fizičkom obrascu naslovljen kao DOO. BM koristi P1b za društvo/MMSP kako je propisano, bez korekcije izvora.

## 13.2 Obavezna polja P1a (čl.16 + P1a)

vrsta subvencije; ime i prezime; JMBG/PIB; adresa; šifra djelatnosti; broj zaposlenih; broj žiro računa; kontakt telefon; e-mail; website; datum registracije u CRPS; naznaka tačnosti / izjava o materijalnoj i krivičnoj odgovornosti (obrazac širi od čl.16); potpis preduzetnika + M.P.

## 13.3 Obavezna polja P1b (čl.16 + P1b)

vrsta subvencije; naziv društva; ime i prezime odgovornog lica; PIB društva; sjedište; datum osnivanja; šifra djelatnosti; broj zaposlenih; broj žiro računa; kontakt telefon odgovornog lica; e-mail odgovornog lica; website; naznaka tačnosti; potpis odgovornog lica + M.P.

## 13.4 Prateća dokumentacija — preduzetnik (čl.14)

Obaveznost: **da**, uz prijavu. Rok važenja: uvjerenja pod 8–10 **ne starija od 30 dana**.

1. prijava P1a  
2. popunjen P2  
3. ovjerena kopija lične karte  
4. rješenje o upisu u CRPS  
5. rješenje o registraciji PJ Poreske uprave  
6. PDV rješenje ili potvrda da nije PDV obveznik  
7. potvrda o nevođenju krivičnog postupka pred Osnovnim sudom  
8. uvjerenje lokalne uprave o urednom izmirivanju poreza (prirez, doprinosi, lokalne takse i naknade), ≤ 30 dana  
9. uvjerenje o urednom izmirivanju poreza na nepokretnost, ≤ 30 dana  
10. potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa, ≤ 30 dana  
11. IOPPD obrazac (posljednji mjesec uplate) ili potvrda da nema zaposlenih (ovjerena od Poreske uprave) — **uslovno**  
12. dokaz o broju poslovnog žiro računa  
13. predračuni za planiranu nabavku  

## 13.5 Prateća dokumentacija — društvo (čl.14)

1. prijava P1b  
2. P2  
3. ovjerena kopija lične karte ovlašćenog lica  
4. upis u CRPS  
5. registracija PJ Poreske uprave  
6. PDV rješenje ili potvrda o neobveznosti  
7. važeći Statut  
8. važeći karton deponovanih potpisa  
9. komplet godišnjih računa (bilans stanja, bilans uspjeha, analitika kupaca i dobavljača) za prethodnu godinu — **izuzetak:** društva registrovana u tekućoj godini; **uslovno:** ako nema analitiku kupaca (posao sa fizičkim licima, registar kasa) dostavlja periodični izvještaj sa registra kase  
10. potvrda o nevođenju krivičnog postupka na ime društva, izvršnog direktora i osnivača  
11. uvjerenje lokalne uprave o urednim poreskim obavezama, ≤ 30 dana  
12. uvjerenje o porezu na nepokretnost, ≤ 30 dana  
13. potvrda Poreske uprave, ≤ 30 dana  
14. IOPPD obrazac za posljednji mjesec uplate za zaposlene  
15. predračuni  

De minimis izjava (čl.15) **nije** stavka prijave; nastaje kada su sredstva odobrena (KN-BR-018).

---

# 14. Plan ulaganja P2

**Status:** USVOJENO  
**Izvor:** čl.17 + sastavni obrazac P2. Polja iz fizičkog P2 su poslovni zahtjevi jer je obrazac sastavni izvor. Ne prevode se u DB kolone.

P2 se popunjava elektronski putem digitalnog servisa (čl.17).

## 14.1 Poslovna struktura

**I. Osnovni podaci**

* oblik registracije; preduzetnik/društvo; kontakt osoba; PIB; adresa/sjedište; kontakt telefon; e-mail; website; šifra djelatnosti; godina osnivanja; broj zaposlenih; broj žiro računa i naziv banke (P2);
* vrsta subvencije (čl.17).

**II. Plan ulaganja**

* opis planiranog ulaganja;
* ciljevi ulaganja;
* planirane aktivnosti (aktivnost, kratak opis, period realizacije — P2).

**III. Finansijski okvir**

* vrsta troška / opis / iznos EUR;
* ukupna vrijednost ulaganja;
* sopstveno učešće;
* očekivani iznos subvencije.

**IV. Autentičnost / inovativnost / digitalizacija**

**V. Očekivani rezultati**

**VI. Uticaj na lokalnu ekonomiju**

**VII. Održivost i dugoročni efekti** (usklađeno sa PO Q6 / kriterijumom 9)

Potpis: Podnosilac; potpis i pečat (P2).

Čl.17 daje tematske cjeline; P2 ih razrađuje. Nije kontradikcija (KN-PRO 4.20.I).

---

# 15. Komisija i sjednice

**Status:** USVOJENO  
Ne projektuje se aplikacioni authorization model.

| Tema | Poslovno pravilo | Izvor |
|------|------------------|-------|
| Imenovanje | Sekretar Sekretarijata imenuje Komisiju rješenjem; može imenovati zamjenskog člana zbog odsustva | čl.7 |
| Sastav | Tačno 3 člana: Predsjednik, Član 1, Član 2. Institucionalne oznake čl.7 (predsjednik, sekretar Komisije, spoljašnji član) ne uvećavaju broj. Ne uvode se Član 3 ni Član 4. | čl.7; PO DECISION 2; KN-BR-050 |
| Mandat | 1 godina | čl.7 |
| Prestanak | Istek vremena, lični zahtjev ili razrješenje | čl.8 |
| Razrješenje | Razlozi čl.9; postupak čl.10 (pravo na izjašnjenje); novi član 15 dana (čl.11) | čl.9–11; KN-BR-068 |
| Nadležnosti | Raspisivanje poziva; pregled dokumentacije i plana; intervju; evaluacija; konačna rang lista; predlog Odluke | čl.7 |
| Kvorum | Većina članova; inače odlaganje | čl.7; KN-BR-045 |
| Intervju / punovažne odluke | Prisustvo svih članova | čl.7; KN-BR-046 |
| Elektronske sjednice | Dozvoljene (zoom/teams; viber/whatsapp) | čl.7; KN-BR-047 |
| Javnost | Rad je javan | čl.7; KN-BR-048 |
| Tajnost / sukob | Izjava o tajnosti i izjava o sprečavanju sukoba interesa | čl.7; KN-BR-049 |
| Prva sjednica | ≤ 7 dana od isteka prijave; pregled prijava | čl.18; KN-BR-041 |
| Druga sjednica | Intervju za potpune prijave; ≤ 7 dana od prve | čl.18; KN-BR-044 |
| Treća sjednica | Konačna rang lista / iznosi; ≤ 7 dana od druge i intervjua | čl.21–22; KN-BR-039 |
| Naknada | Članovi Komisije imaju pravo na naknadu za rad. Iznos naknade, način obračuna i dinamika isplate nijesu propisani Odlukom i ne uvode se. | čl.7; KN-BR-069 |
| Poslovnik | Komisija radi u skladu sa Poslovnikom o radu Komisije koji donosi Sekretarijat. Sadržaj Poslovnika i dodatne procedure koje nijesu sadržane u Odluci ne uvode se. | čl.7; KN-BR-070 |

---

# 16. Administrativna provjera i prigovor

**Status:** USVOJENO

Poslovni tok:

prijava → administrativna provjera → potpuna / nepotpuna → obavještenje → prigovor → odluka po prigovoru → intervju (za potpune)

Ne uvodi se zatvoreni katalog aplikacionih/runtime statusa predmeta ili prijave. Poslovni životni ciklus opisuje se procesno i narativno, isključivo prema fazama i radnjama koje proizlaze iz Odluke (poglavlje 26). Tabela/faze nisu runtime state machine. Konkretni runtime statusi, nazivi, dozvoljeni prelazi i pravila promjene statusa definišu se kasnije u KN-FS-001 kada to funkcionalna specifikacija bude zahtijevala (**PO DECISION 3**).

| Korak | Rok | Izvor |
|-------|-----|-------|
| Prijava | 20 dana od objave Javnog poziva | čl.6, čl.14 |
| Prva sjednica | najviše 7 dana od isteka prijave | čl.18 |
| Obavještenje o prigovoru | registrovani mail podnosioca | čl.18 |
| Prigovor | 3 dana od slanja obavještenja | čl.18 |
| Odluka o prigovoru | 7 dana od prijema prigovora | čl.18 |
| Druga sjednica / intervju | 7 dana od prve sjednice, za potpune prijave | čl.18 |

Ako je prijava nepotpuna: označava se u listi za ocjenjivanje (čl.21/P3) i ne razmatra se dalje, uz pravo na prigovor (KN-BR-020, KN-BR-042).

---

# 17. Evaluacija i bodovanje

**Status:** USVOJENO  
**Pozitivni kriterijumi:** član 20 + P3 (PO Q4).  
**Bodovanje:** čl.21–22 + P3; **PO DECISION 4**.  
P3 referenca „Član 21 stav 2“ je citation-only error u KN-PRO i ne mijenja kriterijume.

## 17.1 Deset pozitivnih kriterijuma (čl.20 + P3)

| # | Naziv | Opis iz izvora |
|---|-------|----------------|
| 1 | Kvalitet plana ulaganja | ciljevi, opis aktivnosti, period realizacije |
| 2 | Relevantnost ulaganja za razvoj biznisa | |
| 3 | Finansijska opravdanost | realni troškovi i jasna finansijska konstrukcija |
| 4 | Jačanje konkurentnosti | |
| 5 | Lokalna autentičnost proizvoda/usluga | |
| 6 | Inovativnost | novina ili značajno unapređenje; nova tehnologija i sl. |
| 7 | Doprinos digitalizaciji poslovanja | digitalni alati, online prodaja/promocija, sistemi za upravljanje |
| 8 | Uticaj na lokalnu ekonomiju | zapošljavanje, turizam, saradnja sa lokalnim dobavljačima |
| 9 | Održivost i dugoročni efekti | PO Q6; P2 VII |
| 10 | Prezentacija plana na intervjuu | jasan plan, spremno odgovaranje, vizija održivosti |

## 17.2 Tri eliminatorna kriterijuma (čl.20) — obavezno sva tri

1. Nedostatak formalnih uslova (nepotpuna dokumentacija).  
2. Nedostavljanje izvještaja o realizaciji sa finansijskim izvještajem i pratećom dokumentacijom iz prethodnog budžetskog finansiranja.  
3. Plan ulaganja nije u vezi sa utvrđenim vrstama subvencija (PO Q1: vrste = čl.12; čl.13 = troškovi).

P3 sadrži svih 10 pozitivnih kriterijuma i naznaku potpune dokumentacije. P3 **ne** sadrži sva 3 eliminatorna kriterijuma. BM zadržava sva 3 (KN-BR-033).

## 17.3 Mehanika bodovanja

**PO DECISION 4** (USVOJENO):

* Komisija ima tačno 3 člana: **Predsjednik**, **Član 1**, **Član 2** (**PO DECISION 2**);
* svaki od njih mora ocijeniti svih 10 pozitivnih kriterijuma (KN-BR-031, KN-BR-034);
* za svaki kriterijum dozvoljena je ocjena isključivo 1, 2, 3, 4 ili 5 (KN-BR-030);
* nema ocjene 0, prazne ocjene niti neocijenjenog kriterijuma;
* bodovanje, prosječna ocjena po kriterijumu, konačna ocjena i prag podrške ostaju usklađeni sa Odlukom i KN-PRO (KN-BR-036, KN-BR-037, KN-BR-029);
* plan ulaganja sa konačnom ocjenom ispod 30 bodova ne podržava se (KN-BR-029);
* **INPUT:** pojedinačne ocjene članova = cijeli brojevi 1–5;
* **CALCULATION:** formula prosjeka (zbir/3) i konačne ocjene (zbir 10 prosjeka) nepromijenjena; CALCULATION VALUE smije imati decimale koje proizlaze iz formule;
* **DISPLAY:** konačni/ukupni skor prikazuje se na **dvije decimale**;
* prag i rangiranje koriste CALCULATION VALUE (stvarnu vrijednost formule), ne zasebno poslovno rounding pravilo.

Ostala mehanika iz KN-PRO:

* skala 1–5 — EXPLICIT SOURCE RULE — FORM P3 (KN-BR-030);
* privatnost ocjena tokom bodovanja (KN-BR-035);
* prosječna ocjena po kriterijumu = zbir bodova svih članova / broj članova (3) (KN-BR-036; čl.21);
* konačna ocjena = zbir prosjeka (KN-BR-037);
* preliminarna rang lista bez iznosa (KN-BR-038);
* treća sjednica (KN-BR-039);
* konačna rang lista sa iznosima i potpisima (KN-BR-040).

Ako trenutni obrazac P3 sadrži dodatne kolone (npr. Član 3, Član 4), one **ne** predstavljaju dodatne članove Komisije. Odluka ima prednost nad obrascem. P3 se za poslovni model tumači u skladu sa Odlukom. Korigovani obrazac P3 biće dostavljen naknadno.

---

# 18. Finansijska pravila

**Status:** USVOJENO  
Ne izmišljaju se limiti koji nijesu u KN-PRO.

| Pravilo | Vrijednost | Izvor | BM ID |
|---------|------------|-------|-------|
| Broj vrsta u katalogu | 3 | čl.12; PO Q1 | KN-BR-023 |
| Broj vrsta za koje se može aplicirati | 2 | čl.19 | KN-BR-024 |
| Broj vrsta za koje se može dobiti podrška | 1 | čl.19 | KN-BR-024 |
| Procenat budžeta po korisniku | ≤ 20% planiranog budžeta za namjenu | čl.19 | KN-BR-025 |
| Procenat finansiranja ulaganja | ≤ 50% potrebnih sredstava | čl.19 | KN-BR-026 |
| Poseban procenat za autentične proizvode/usluge | ≤ 80% potrebnih sredstava | čl.19 + čl.12 t.1; PO Q1 | KN-BR-027 |
| Sopstveno učešće | navodi se u P2 i opravdava u izvještaju; fiksni % nije propisan | čl.17, čl.28 | KN-BR-077 |
| Raspodjela | po konačnoj rang listi do utroška | čl.19, čl.22 | KN-BR-028 |
| Odnos traženih i odobrenih | konačna lista sadrži potrebna i odobrena sredstva; odobreno može biti manje (utrošak / limiti) | čl.22, čl.19 | KN-BR-040 |
| Prag bodova | 30 | čl.22 | KN-BR-029 |

Ukupan iznos raspodjele definiše Javni poziv (čl.2). BM ne unosi apsolutni iznos.

---

# 19. Odluke i pravna zaštita

**Status:** USVOJENO  
Tužba se **ne** pretvara u žalbu.

| Artefakt | Ko poslovno proizvodi / dostavlja | Kada | Izvor |
|----------|-----------------------------------|------|-------|
| Predlog Odluke o dodjeli | Komisija generiše putem digitalnog servisa i predlaže Sekretarijatu | nakon konačne rang liste | čl.23 |
| Odluka o dodjeli | Sekretarijat objavljuje | u roku 45 dana od isteka roka za prijavu | čl.23 |
| Izmjene/dopune Odluke | Sekretarijat | nakon Izjave o odustanku | čl.24 |
| Rješenje o dodjeli | Sekretarijat donosi i dostavlja korisnicima | nakon Odluke | čl.24 |
| Rješenje o odbijanju | Sekretarijat donosi i obavještava o razlozima | nakon Odluke | čl.24 |
| Tužba Upravnom sudu | podnosilac protiv rješenja o odbijanju | 20 dana od prijema; ne odlaže izvršenje | čl.24 |

Sadržaj Odluke (čl.24): ime/naziv korisnika; vrsta subvencije; iznosi dodijeljeni za planove; ukupan iznos sredstava potreban za realizaciju svakog plana.

Kanali objave: vebsajt Opštine, digitalni servis, lokalni javni emiter, oglasna tabla (čl.25). Dostava svim učesnicima.

---

# 20. Ugovor i isplata

**Status:** USVOJENO

| Tema | Poslovni uslov | Izvor |
|------|----------------|-------|
| Preduslov | Donesena i javno objavljena Odluka | čl.26 |
| Strane | Sekretarijat i korisnici kojima su dodijeljene subvencije | čl.26 |
| Rok zaključenja | 10 dana od izvršnosti Odluke | čl.26 |
| Sadržaj | samo: prava i obaveze, način korišćenja sredstava, izvještavanje, nadzor | čl.26 |
| Isplata | na račun korisnika | čl.26 |
| Rok isplate | 10 dana od potpisivanja ugovora | čl.26 |
| Početak monitoringa | Sekretarijat prati realizaciju zaključenog ugovora | čl.26–27 |

Izvor ne propisuje elektronsku formu ugovora. Ugovor **nije** u softverskom obuhvatu V1 (**PO DECISION 1**). Kasnije uključivanje zahtijeva posebnu PO odluku i specifikaciju.

---

# 21. Realizacija i preusmjeravanje

**Status:** USVOJENO

Poslovni proces:

realizacija plana → praćenje Sekretarijata → eventualna terenska kontrola → odstupanje → zahtjev za preusmjeravanje uz obrazloženje → rok Sekretarijata 3 dana → pretpostavljena saglasnost kod ćutanja organa.

Primjeri razloga iz izvora: nedostupnost predmeta nabavke, skok cijena (čl.27). Forma zahtjeva nije propisana. Izvor ne propisuje digitalni kanal. Preusmjeravanje **nije** u softverskom obuhvatu V1 (**PO DECISION 1**). Kasnije uključivanje zahtijeva posebnu PO odluku i specifikaciju.

---

# 22. Izvještavanje P4 / P4a

**Status:** USVOJENO  
Ne projektuje se baza.

## 22.1 Obaveza (čl.28)

* P4 — Izvještaj o realizaciji planirane nabavke;
* P4a — finansijski izvještaj;
* fakture;
* izvodi banke;
* opravdanje dodijeljene subvencije;
* opravdanje sopstvenog učešća;
* rok: definisan ugovorom.

## 22.2 P4 — poslovna polja (EXPLICIT SOURCE RULE — FORM P4)

Identifikacija: ime i prezime preduzetnika ili nosioca biznisa; pravni status i naziv biznisa; vrsta subvencije; iznos odobrenih sredstava; broj ugovora; izvještajni period.

Pitanja 1–8: aktivnosti; problemi; uspjesi; broj novozaposlenih + vrsta ugovora + period (potvrđuje Q5 evidenciju); nabavke iz odobrenih sredstava; odstupanja i obrazloženje; zadovoljstvo saradnjom; preporuke.

Prilozi: P4a, fakture, izvod sa žiro računa. Potpisi i pečat obavezni.

## 22.3 P4a — finansijska tabela (EXPLICIT SOURCE RULE — FORM P4a)

Kolone: r.br.; vrsta nabavke; iznos računa (sa PDV-om i ostalim troškovima); dobavljač; broj fakture; datum izdavanja; broj izvoda i datum plaćanja; red UKUPNO; potpis/M.P.

Kanal podnošenja P4/P4a nije isključivo digitalni u izvoru. P4/P4a **nisu** u softverskom obuhvatu V1 (**PO DECISION 1**). Kasnije uključivanje zahtijeva posebnu PO odluku i specifikaciju.

---

# 23. Povraćaj i post-obaveze

**Status:** USVOJENO

| Kada nastaje obaveza povraćaja | Obim | Izvor |
|--------------------------------|------|-------|
| Nedostavljeni izvještaji i dokumentacija u ugovornom roku (nenamjensko trošenje) | u cjelosti, na zahtjev Sekretarijata | čl.29 |
| Gašenje ili prodaja biznisa prije isteka 3 godine od potpisivanja ugovora | u cjelosti, na zahtjev Sekretarijata | čl.29 |

Post-obaveze:

* ne-gašenje biznisa najmanje 3 godine od potpisivanja (čl.15, KN-BR-019);
* zabrana otuđenja materijalne imovine min. 1 godinu od nabavke (čl.13, KN-BR-072).

Izvor ne uređuje djelimičan povraćaj. BM ne izmišlja djelimičan povraćaj.

---

# 24. Javna promocija i izvještavanje Skupštini

**Status:** USVOJENO  
Starosni scope se **ne** uvodi zbog izraza „MMSP mladih“ (PO Q3).

| Proces | Sadržaj | Rok | Izvor |
|--------|---------|-----|-------|
| Javno predstavljanje | realizovane aktivnosti i rezultati korisnika čiji su planovi podržani | nije propisan; ovlašćenje Sekretarijata | čl.30 |
| Promotivni događaji | prezentacije, sajmovi, promotivni događaji | nije propisan | čl.30 |
| Izvještaj Skupštini | podržani planovi ulaganja; iznos dodijeljenih subvencija; realizovani projekti; efekti | 30 dana nakon isteka roka za izvještaje korisnika iz ugovora | čl.31; KN-BR-067 |

---

# 25. Business workflow

**Status:** USVOJENO

Kanonski end-to-end tok. Za svaki korak: akter, ulaz, poslovna radnja, izlaz, rok, pravni izvor.

| # | Faza | Akter | Ulaz | Poslovna radnja | Izlaz | Rok | Izvor |
|---|------|-------|------|-----------------|-------|-----|-------|
| 1 | Raspisivanje Javnog poziva | Komisija | Budžet; Odluka | Raspisuje Javni poziv sa propisanim sadržajem | Objavljen Javni poziv | treći kvartal; 1× godišnje | čl.5, čl.7 |
| 2 | Objava | Opština / Sekretarijat | Javni poziv | Objava na 4 kanala | Javnost obaviještena | uz raspisivanje | čl.6 |
| 3 | Prijava | Preduzetnik ili društvo | Javni poziv; P1a/P1b; P2; dokumentacija | Elektronsko podnošenje | Zaprimljena prijava | 20 dana od objave | čl.6, čl.14, čl.16 |
| 4 | Administrativna provjera | Komisija | Zaprimljene prijave | Pregled na prvoj sjednici | Potpuna / nepotpuna | prva sjednica ≤ 7 dana od isteka prijave | čl.18 |
| 5 | Obavještenje o nepotpunosti | Komisija | Nepotpuna prijava | Obavještenje registrovanim mailom | Obavještenje | nakon prve sjednice | čl.18 |
| 6 | Prigovor (ako postoji) | Podnosilac | Obavještenje | Prigovor putem digitalnog servisa | Prigovor | 3 dana od slanja obavještenja | čl.18 |
| 7 | Odluka po prigovoru | Komisija | Prigovor | Prihvatanje ili odbijanje | Odluka o prigovoru | 7 dana od prijema | čl.18 |
| 8 | Intervju | Komisija; podnosilac potpune prijave | Potpuna prijava | Usmeni intervju (svi članovi) | Održan intervju | druga sjednica ≤ 7 dana od prve | čl.7, čl.18 |
| 9 | Ocjenjivanje / bodovanje | Članovi Komisije | Plan, intervju, P3 | Individualni bodovi 1–5; privatnost tokom bodovanja | Popunjen P3 | nakon intervjua | čl.20–21, P3 |
| 10 | Preliminarna rang lista | Komisija / digitalni servis | Sve ocjene | Automatsko formiranje liste bez iznosa | Preliminarna rang lista | po završetku ocjenjivanja | čl.21 |
| 11 | Treća sjednica | Komisija; predsjednik | Preliminarna lista | Podržava/odbija; iznos; obrazloženja | Zaključci treće sjednice | ≤ 7 dana od druge sjednice i intervjua | čl.21–22 |
| 12 | Konačna rang lista | Komisija | Zaključci | Automatska lista sa iznosima i potpisima | Konačna rang lista | treća sjednica | čl.22 |
| 13 | Predlog Odluke | Komisija | Konačna lista | Generisanje predloga | Predlog Sekretarijatu | nakon rang liste | čl.23 |
| 14 | Zatvaranje poziva | Predsjednik Komisije | Predlog Odluke | Zatvaranje i arhiva | Zatvoren Javni poziv | nakon predloga | čl.23 |
| 15 | Odluka | Sekretarijat | Predlog | Objava Odluke | Objavljena Odluka | 45 dana od isteka prijave | čl.23, čl.25 |
| 16 | Rješenja | Sekretarijat | Odluka | Rješenje o dodjeli / odbijanju | Dostavljena rješenja | nakon Odluke | čl.24 |
| 17 | Pravna zaštita | Podnosilac; Upravni sud | Rješenje o odbijanju | Tužba | Postupak pred sudom | 20 dana; bez odlaganja izvršenja | čl.24 |
| 18 | Odustanak (opciono) | Podnosilac; Sekretarijat | Odluka | Izjava o odustanku; izmjena Odluke | Sredstva ostaju u budžetu | nakon Odluke | čl.24 |
| 19 | Ugovor | Sekretarijat; korisnik | Izvršna Odluka | Zaključenje ugovora | Ugovor | 10 dana od izvršnosti | čl.26 |
| 20 | Isplata | Sekretarijat | Potpisan ugovor | Uplata na račun | Isplaćena sredstva | 10 dana od potpisa | čl.26 |
| 21 | Realizacija / monitoring | Korisnik; Sekretarijat | Ugovor; plan | Realizacija; praćenje; terenska kontrola | Tok realizacije | tokom ugovora | čl.26–27 |
| 22 | Preusmjeravanje (opciono) | Korisnik; Sekretarijat | Odstupanje | Zahtjev + obrazloženje; odgovor ili ćutanje | Saglasnost / odbijanje | 3 dana; inače saglasnost | čl.27 |
| 23 | P4 / P4a | Korisnik | Ugovorni rok | Podnošenje izvještaja, faktura, izvoda | Izvještajni komplet | rok iz ugovora | čl.28, P4, P4a |
| 24 | Kontrola opravdanja | Sekretarijat | P4/P4a i prilozi | Provjera opravdanja subvencije i sopstvenog učešća | Prihvaćen ili neopravdan utrošak | nakon dostave | čl.28–29 |
| 25 | Povraćaj (opciono) | Sekretarijat; korisnik | Nedostavljen izvještaj ili gašenje/prodaja < 3 godine | Zahtjev za povraćaj | Povraćaj u cjelosti | 3 godine post-ugovor za gašenje | čl.29 |
| 26 | Promocija | Sekretarijat | Realizovani rezultati | Javno predstavljanje / događaji | Javna promocija | nije fiksiran | čl.30 |
| 27 | Izvještaj Skupštini | Sekretarijat | Izvještaji korisnika / realizacija | Podnošenje izvještaja Skupštini | Izvještaj Skupštini | 30 dana nakon isteka roka korisničkih izvještaja | čl.31 |

---

# 26. Business states

**Status:** USVOJENO — procesni/narativni opis; **PO DECISION 3 USVOJENO**

**PO DECISION 3** (USVOJENO): KN-BM-001 **ne** uvodi zatvoreni katalog aplikacionih/runtime statusa predmeta ili prijave. Poslovni životni ciklus u Business Modelu opisuje se procesno i narativno, isključivo prema fazama i radnjama koje proizlaze iz Odluke. Tabela/faze koje se koriste radi objašnjenja toka **ne** predstavljaju automatski runtime state machine.

Konkretni runtime statusi, nazivi statusa, dozvoljeni prelazi i pravila promjene statusa definišu se kasnije u KN-FS-001 kada to funkcionalna specifikacija bude zahtijevala, i ne smiju biti izmišljeni bez PO odluke ako Odluka ne daje jednoznačno pravilo. Odluka ostaje glavni poslovni/normativni izvor.

Nazivi koji doslovno postoje u izvoru (npr. prijava, prigovor, preliminarna rang lista, konačna rang lista, odluka, rješenje, ugovor) ostaju kao poslovni pojmovi. Sljedeća tabela **objašnjava** tok; nije zatvoreni katalog software stanja, nije tehnički enum i nije runtime kod.

| Naziv faze (obrazovno) | Značenje | Ulazni uslov | Izlazni događaj | Dozvoljene poslovne akcije |
|------------------------|----------|--------------|-----------------|----------------------------|
| Javni poziv raspisan | Poziv je objavljen i otvoren | Objava na propisanim kanalima | Istek 20 dana ili kasnije zatvaranje predsjednika | Prijave |
| Prijava podnesena | Elektronski zaprimljena prijava | Podnošenje u roku | Prva sjednica | Čekanje administrativne provjere |
| Prijava potpuna | Dokumentacija ocjenjena kao potpuna | Prva sjednica / prihvaćen prigovor | Zakazivanje intervjua | Intervju, evaluacija |
| Prijava nepotpuna | Dokumentacija ocjenjena kao nepotpuna | Prva sjednica | Obavještenje | Prigovor u roku 3 dana |
| Prigovor podnesen | Podnosilac je uložio prigovor | Obavještenje + rok | Odluka Komisije | Čekanje odluke 7 dana |
| Prigovor prihvaćen | Komisija prihvatila prigovor | Odluka o prigovoru | Tok potpune prijave | Intervju |
| Prigovor odbijen | Komisija odbila prigovor | Odluka o prigovoru | Ostaje nepotpuna / ne razmatra se dalje | Nema dalje evaluacije po čl.18 |
| Intervju održan | Usmeni intervju završen | Druga sjednica; svi članovi | Bodovanje | P3 |
| Ocjenjivanje u toku | Članovi unose bodove | Nakon intervjua | Sve ocjene unesene | Uvid samo u sopstvene bodove |
| Preliminarna rang lista formirana | Lista sa bodovima, bez iznosa | Završeno ocjenjivanje | Treća sjednica | Zakazivanje treće sjednice |
| Konačna rang lista utvrđena | Podržava/odbija + iznosi | Treća sjednica | Predlog Odluke | Predlog Sekretarijatu |
| Odluka objavljena | Odluka o dodjeli je javna | Objava Sekretarijata | Rješenja / ugovor / odustanak | Dostava, rješenja |
| Rješenje o dodjeli | Individualna dodjela | Odluka | Ugovor | Zaključenje ugovora |
| Rješenje o odbijanju | Individualno odbijanje | Odluka | Tužba ili kraj učešća | Tužba u 20 dana |
| Ugovor zaključen | Ugovor potpisan | Izvršna Odluka | Isplata | Isplata u 10 dana |
| Sredstva isplaćena | Uplata na račun | Potpisan ugovor | Realizacija | Monitoring |
| Realizacija u toku | Korisnik sprovodi plan | Isplata / ugovor | Izvještaj ili preusmjeravanje | Praćenje, terenska kontrola |
| Preusmjeravanje zatraženo | Podnesen zahtjev | Odstupanje | Odgovor ili ćutanje 3 dana | Čekanje Sekretarijata |
| Izvještaj podnesen | P4/P4a dostavljeni | Ugovorni rok | Kontrola opravdanja | Provjera Sekretarijata |
| Obaveza povraćaja | Nenamjensko trošenje ili gašenje/prodaja | Čl.29 | Povraćaj u cjelosti | Zahtjev Sekretarijata |
| Javni poziv zatvoren | Predsjednik zatvorio poziv i arhivirao | Predlog Odluke | Kraj prijema | Arhiva |

Ova tabela služi objašnjenju toka i **nije** runtime state machine (**PO DECISION 3**). Nije zatvoreni katalog aplikacionih/runtime statusa.

Konkretni runtime statusi, nazivi, dozvoljeni prelazi i pravila promjene statusa nijesu dio KN-BM-001; definišu se kasnije u KN-FS-001 ako i kada FS to zahtijeva, bez izmišljanja ako Odluka ne daje jednoznačno pravilo.

---

# 27. Poslovne validacije

**Status:** USVOJENO  
Ne definišu se implementacioni validatori.

| Validacija | Pravilo | Izvor |
|------------|---------|-------|
| Rok prijave | Prijava samo u 20 dana od objave | čl.6, čl.14 |
| Potpunost dokumentacije | Checklista čl.14 + P1a/P1b + P2 | PO Q2 |
| Eligibility | KN-BR-010 … KN-BR-017 | čl.4, čl.7, čl.15; Q3 |
| Zabrane | Član Komisije; prethodni izvještaji; sektorske zabrane troškova | čl.7, čl.13, čl.15 |
| Validnost uvjerenja | ≤ 30 dana | čl.14 |
| Broj vrsta | Max 2 u konkurenciji; max 1 odobrena | čl.19 |
| Finansijski limiti | 20% budžeta; 50% ili 80% ulaganja | čl.19; Q1 |
| Prag bodova | < 30 = bez podrške | čl.22 |
| Rok prigovora | 3 dana od slanja obavještenja | čl.18 |
| Rok odluke o prigovoru | 7 dana od prijema | čl.18 |
| Skala bodova | Pojedinačne ocjene: isključivo 1, 2, 3, 4 ili 5 (cijeli broj); nema 0 / prazno / neocijenjeno. Konačni/ukupni skor prikazuje se na dvije decimale. | P3; Q4; PO DECISION 4; KN-PATCH-BM-001 |
| Izvještavanje | P4/P4a + fakture + izvodi u ugovornom roku | čl.28 |
| Bruto plate | ugovor ≥ 12 mj.; subvencionisani period ≤ 6 mj. | Q5 |

---

# 28. Business edge cases

**Status:** USVOJENO  
Samo slučajevi sa osnovom u KN-PRO. Ne dodaju se ostali.

| Edge case | Poslovna posljedica | Izvor |
|-----------|---------------------|-------|
| Nepotpuna prijava | Označava se; ne razmatra se dalje; pravo na prigovor | čl.18 |
| Prigovor | Odluka u 7 dana; prihvatanje ili odbijanje | čl.18 |
| Član Komisije u sukobu interesa / učešće | Nema pravo učešća (lično ili društvo) | čl.7 |
| Odustanak korisnika nakon Odluke | Izjava; izmjena Odluke; sredstva ostaju u budžetu | čl.24 |
| Nedovoljna sredstva | Podrška do utroška prema rang listi | čl.19, čl.22 |
| Manje od traženog iznosa | Konačna lista razlikuje potrebna i odobrena sredstva; limiti čl.19 | čl.22, čl.19 |
| Zahtjev za preusmjeravanje | Obrazloženje; odgovor 3 dana | čl.27 |
| Ćutanje Sekretarijata | Saglasnost nakon 3 dana | čl.27 |
| Neopravdana / nedostavljena sredstva (izvještaj) | Nenamjensko trošenje; povraćaj u cjelosti | čl.29 |
| Gašenje/prodaja biznisa prije 3 godine | Povraćaj u cjelosti | čl.29 |
| Nedostavljen izvještaj | Ista posljedica kao nenamjensko trošenje | čl.29 |
| Nema kvoruma | Sjednica se odlaže | čl.7 |
| Prestanak mandata člana | Novi član u 15 dana; razriješeni se ne imenuje ponovo | čl.11 |
| Društvo registrovano u tekućoj godini | Izuzetak od kompleta godišnjih računa | čl.14 |
| Nema analitike kupaca | Periodični izvještaj sa registra kase | čl.14 |
| Plan ispod 30 bodova | Ne podržava se | čl.22 |
| Tužba | Ne odlaže izvršenje Odluke | čl.24 |

---

# 29. Out of scope / V1 granice

**Status:** USVOJENO — granica V1 po **PO DECISION 1**

Razdvojeno:

* **Pravno obavezni poslovni proces** — opisan u ovom dokumentu u cjelini; nije automatski softverski scope V1.
* **Softverski obuhvat V1** — **PO DECISION 1, opcija 1 USVOJENO:** samo koraci koje Odluka eksplicitno vezuje za digitalni servis (poglavlje 6).

Koraci za koje Odluka ne propisuje digitalni kanal (uključujući, kako je već evidentirano u KN-PRO: ugovor, isplatu, preusmjeravanje, P4/P4a, povraćaj, promociju, izvještaj Skupštini) **nisu** u V1. Mogu se uključiti kasnije samo posebnom PO odlukom i odgovarajućom specifikacijom.

Nova pitanja koja izvor ne rješava jednoznačno i dalje se evidentiraju u poglavlju 36 prema pravilu 8. Trenutno nema otvorenih stavki.

---

# 30. Legacy žensko preduzetništvo

Postojeći tok ženskog preduzetništva je ranija implementacija cjeline Konkursi i **nije** automatski SSOT za KN.

Ovaj dokument:

* **ne** radi legacy→KN mapping;
* **ne** označava legacy kao superseded;
* **ne** preuzima uslove, statuse ni procedure iz postojećeg koda.

Čl.15 koristi „Javni konkurs za razvoj ženskog preduzetništva“ i „Javni konkurs za razvoj preduzetništva mladih“ isključivo kao prethodne programe za zabranu učešća ako izvještaji nijesu dostavljeni (KN-BR-016).

---

# 31. Traceability matrix

**Status:** USVOJENO  
KN-FS zahtjevi se **ne** popunjavaju.

Lanac: `KN-PRO source → PO decision (ako postoji) → KN-BM business rule → KN-FS = PENDING`

| KN-PRO source | PO | KN-BM | KN-FS |
|---------------|----|-------|-------|
| čl.1–2, čl.5–6 | — | KN-BR-001 … KN-BR-009 | PENDING |
| čl.4 | — | KN-BR-010 … KN-BR-013 | PENDING |
| čl.7 | — | KN-BR-014, KN-BR-045 … KN-BR-050, KN-BR-068 … KN-BR-070 | PENDING |
| čl.12 | Q1 | KN-BR-023 | PENDING |
| čl.13 | Q5 | KN-BR-071 … KN-BR-076; poglavlje 12 | PENDING |
| čl.14 + P1a/P1b/P2 | Q2 | KN-BR-078 … KN-BR-080; poglavlja 13–14 | PENDING |
| čl.15 | Q3 (uz čl.31) | KN-BR-015 … KN-BR-019 | PENDING |
| čl.16 | Q2 | KN-BR-009, KN-BR-079; polja P1a/P1b | PENDING |
| čl.17 + P2 | — | poglavlje 14; KN-BR-077 | PENDING |
| čl.18 | — | KN-BR-020, KN-BR-041 … KN-BR-044 | PENDING |
| čl.19 | Q1 | KN-BR-024 … KN-BR-028 | PENDING |
| čl.20 + P3 | Q4, Q6 | KN-BR-031 … KN-BR-033; poglavlje 17 | PENDING |
| čl.21–22 + P3 | Q4 | KN-BR-029 … KN-BR-040 | PENDING |
| čl.23–25 | — | KN-BR-051 … KN-BR-056 | PENDING |
| čl.26 | — | KN-BR-057 … KN-BR-060 | PENDING |
| čl.27 | — | KN-BR-061 … KN-BR-062 | PENDING |
| čl.28 + P4/P4a | — | KN-BR-063; poglavlje 22 | PENDING |
| čl.29 | — | KN-BR-064 … KN-BR-065 | PENDING |
| čl.30–31 | Q3 | KN-BR-066 … KN-BR-067 | PENDING |
| čl.20 elim. 1–3 | Q1, Q4 | KN-BR-020 … KN-BR-022 | PENDING |

---

# 32. Prenos PO odluka Q1–Q6

BM koristi donesenu poslovnu interpretaciju. Cijeli anomaly register ostaje u KN-PRO-001.

| Q | BM primjena | BM ID |
|---|-------------|-------|
| Q1 | Vrste subvencija = čl.12. Čl.19→13 ne mijenja katalog ni broj vrsta. Čl.13 = troškovi. | KN-BR-023, KN-BR-027 |
| Q2 | Dokumentacija prijave = čl.14 + sastavni obrasci. Čl.16→15 ne mijenja checklistu. | poglavlje 13; KN-BR-080 |
| Q3 | „MMSP mladih“ nije starosno ograničenje korisnika. | KN-BR-017; poglavlje 24 |
| Q4 | Pozitivni kriterijumi = čl.20 + P3. Bodovanje = čl.21–22 + P3. Citation P3 ne mijenja kriterijume. Sva 3 eliminatorna ostaju. Skala 1–5 iz P3. | KN-BR-030 … KN-BR-033 |
| Q5 | Ugovor o radu ≥ 12 mjeseci; subvencionisani trošak plate ≤ 6 mjeseci. P4 evidentira vrstu ugovora i period. | KN-BR-071 |
| Q6 | Kriterijum 9 = „Održivost i dugoročni efekti“. | KN-BR-032 |

---

# 33. Rječnik poslovnih pojmova

**Status:** USVOJENO — **PO DECISION 7 USVOJENO** (opcija 1)

U KN-BM-001 i budućem KN-FS zadržavaju se termini iz pravnih i poslovnih izvora prema njihovom stvarnom kontekstu. Ne uvodi se sada jedan novi univerzalni termin tamo gdje izvori koriste različite termine ili pojmove. Ovo se posebno odnosi na: preduzetnik; društvo; MMSP; DOO; plan ulaganja; biznis plan; Javni poziv / Javni konkurs.

Pravno ili poslovno značenje izvornog termina **ne** mijenja se samo radi terminološkog ujednačavanja.

Ako budući KN-FS ili konkretan UI zahtijeva jedan jedinstveni naziv za određeni element, to se **ne** smije proizvoljno definisati. Terminološko pitanje se evidentira i, kada je potrebno, traži se PO odluka.

| Pojam | Značenje u ovom BM | Izvor |
|-------|--------------------|-------|
| Javni poziv | Akt raspodjele subvencija po ovoj Odluci | čl.5–6 |
| Javni konkurs | Termin iz čl.15 za ranije programe (žensko / mladi); nije korigovan | čl.15 |
| MMSP | Mikro, mala i srednja preduzeća | čl.1 |
| De minimis | Državna pomoć male vrijednosti | čl.12, čl.15 |
| Podnosilac | Preduzetnik ili društvo koji podnosi prijavu | čl.14 |
| Korisnik | Lice kome su dodijeljena sredstva | čl.15, čl.26 |
| Plan ulaganja | P2; u izvoru se javlja i „biznis plan“ kao terminološka varijacija | čl.17; KN-PRO 4.20.J |
| Preliminarna rang lista | Rang po bodovima bez iznosa | čl.21 |
| Konačna rang lista | Rang sa odlukom i iznosima | čl.22 |
| Tužba | Pravna zaštita protiv rješenja o odbijanju; nije žalba | čl.24 |
| Ćutanje organa | Saglasnost Sekretarijata ako ne odgovori u 3 dana | čl.27 |
| poslovna faza | Faza pravnog procesa opisana procesno/narativno; nije tehnički enum, nije zatvoreni runtime katalog i nije state machine | ovaj dokument §26; PO DECISION 3 |

Dokumentacioni termini namespace / Document ID / PRO / PATCH: KN-RG-001 i DK-DS-001.

Rječnik **ne** pretvara izvorne varijacije u jedan kanonski UI/FS label (**PO DECISION 7**).

---

# 34. Veza sa dokumentacijom

Dokumentaciona hijerarhija (nije prenos pravnih pravila):

```text
DK-DS-001 / METHODOLOGY
        ↓
KN-RG-001
        ↓
KN-PRO-001
        ↓
KN-BM-001
        ↓
KN-FS-001
        ↓
KN-TS-001
```

Minimalni kanonski lanac sadržaja (DK-DS-001 §11): `KN-BM-001` → `KN-FS-001` → `KN-TS-001`.

Budući KN-FS zadržava izvorne termine po kontekstu (**PO DECISION 7**, opcija 1). KN-FS se ovim korakom **ne** kreira i **ne** mijenja.

| Dokument | Putanja | Status |
|----------|---------|--------|
| KN-RG-001 | `docs/reference/Registar-skracenica-i-oznaka-dokumentacije-Konkursi.md` | USVOJENO |
| KN-PRO-001 | `docs/pravni-okvir/Pravni_okvir_Konkursi.md` | NACRT v0.1.4 |
| KN-FS-001 | `docs/functional-specifications/Functional-Specification_Konkursi.md` | v0.2.12 USVOJENO |
| KN-TS-001 | `docs/technical-specifications/Technical-Specification_Konkursi.md` | NACRT — nije mijenjan ovim korakom |

`KN-FR-*`, `KN-CR-REG-*`, `KN-IS-*`, `KN-IR-*` i UC **nisu** kreirani (DK-DS-001 §3).

---

# 35. Registar usvojenih poslovnih odluka

**Status:** USVOJENO

Usvojene PO interpretacije za derivaciju BM (prenijete iz KN-PRO-001 §4.21): **Q1–Q6**.

**PO DECISION 1 — V1 digitalni obuhvat:** USVOJENO, opcija 1. Vidi poglavlje 6.

**PO DECISION 2 — sastav Komisije:** USVOJENO. Komisija ima tačno 3 člana: Predsjednik, Član 1, Član 2. Vidi KN-BR-050 i poglavlja 8 i 15–17.

**PO DECISION 3 — životni ciklus / runtime statusi:** USVOJENO. KN-BM-001 ne uvodi zatvoreni katalog aplikacionih/runtime statusa. Životni ciklus se opisuje procesno i narativno prema Odluci. Tabela/faze nisu runtime state machine. Konkretni runtime statusi, nazivi, prelazi i pravila promjene definišu se kasnije u KN-FS-001 kada FS to zahtijeva, bez izmišljanja ako Odluka nije jednoznačna. Odluka ostaje glavni poslovni/normativni izvor. Vidi poglavlja 16 i 26.

**PO DECISION 4 — bodovanje:** USVOJENO. Komisija ima tačno 3 člana (Predsjednik, Član 1, Član 2). Svaki ocjenjuje svih 10 pozitivnih kriterijuma ocjenom isključivo 1, 2, 3, 4 ili 5 (cijeli broj). Nema 0, prazne ocjene ni neocijenjenog kriterijuma. Formula prosjeka, konačna ocjena i prag 30 ostaju usklađeni sa Odlukom i KN-PRO. **„Bez decimala“ odnosi se samo na pojedinačne ocjene članova Komisije.** **Konačni/ukupni skor prikazuje se na dvije decimale** (DISPLAY). Ne uvodi se novo poslovno rounding pravilo koje mijenja formulu ili prag; prag i rangiranje koriste CALCULATION VALUE. Vidi KN-BR-029, KN-BR-030, KN-BR-034, KN-BR-036, KN-BR-037, poglavlje 17 i `KN-PATCH-BM-001`.

**PO DECISION 5 — katalog skraćenica pojedinačnog konkursa:** USVOJENO. KN-RG-001 ostaje modulni registar cjeline Konkursi. Svaki budući pojedinačni konkurs mora imati poseban katalog/dokument skraćenica tipa RG, po principu postojećeg Digital Kotor / Kalendar kulture dokumentacionog obrasca. Katalog konkretnog konkursa dobija sljedeći slobodni Document ID `KN-RG-xxx`; konkretan broj se ne rezerviše unaprijed. Prilikom formalnog otvaranja konkretnog konkursa prvo se provjerava KN registar i dodjeljuje sljedeći slobodni KN-RG broj. Ne uvodi se novi KF tip dokumenta. Katalog konkretnog konkursa se sada ne kreira. Vidi KN-DOC-08 i poglavlje 5.

**GRANICA IZVORA — naknada Komisije i Poslovnik (bivša stavka 6):** **nije** PO odluka. Primarni izvor je Odluka. Odluka eksplicitno propisuje: (1) Komisija radi u skladu sa Poslovnikom o radu Komisije koji donosi Sekretarijat; (2) članovi Komisije imaju pravo na naknadu za rad. Odluka **ne** propisuje iznos naknade, način obračuna, dinamiku isplate, sadržaj Poslovnika niti dodatne procedure Poslovnika koje nijesu sadržane u Odluci. KN-BM-001 evidentira samo to što Odluka propisuje (KN-BR-069, KN-BR-070; poglavlje 15). Iznos naknade i sadržaj Poslovnika se ne uvode bez posebnog pravnog izvora. Ako takav akt/Poslovnik kasnije bude dostavljen, tretiraće se kao dodatni pravni/poslovni izvor i dokumentacija će se kontrolisano dopuniti. Stavka 6 nije `PO DECISION REQUIRED` jer Product Owner ne treba da izmisli nedostajući pravni sadržaj.

**PO DECISION 7 — terminološka standardizacija za budući KN-FS:** USVOJENO, opcija 1. U KN-BM-001 i budućem KN-FS zadržavaju se termini iz pravnih i poslovnih izvora prema njihovom stvarnom kontekstu. Ne uvodi se jedan novi univerzalni termin tamo gdje izvori koriste različite termine ili pojmove (npr. preduzetnik, društvo, MMSP, DOO, plan ulaganja, biznis plan, Javni poziv / Javni konkurs). Pravno ili poslovno značenje izvornog termina ne mijenja se samo radi ujednačavanja. Ako budući KN-FS ili konkretan UI zahtijeva jedan jedinstveni naziv, to se ne definiše proizvoljno: pitanje se evidentira i, kada je potrebno, traži se PO odluka. Vidi poglavlja 33 i 34.

Ovaj dokument je **USVOJENO** (KN-BM-001 v0.2.10; formalno PO usvajanje v0.2.7; `KN-PATCH-BM-001`; `KN-PATCH-BM-002`; `KN-PATCH-BM-003`). Derivirana `KN-BR-*` pravila imaju status **DERIVED / USVOJENO**.

Dokumentaciona načela: `KN-DOC-01` … `KN-DOC-08` (poglavlje 5). Nisu BR. KN-DOC-08 ne kreira katalog konkretnog konkursa i ne rezerviše `KN-RG-xxx` broj.

---

# 36. PO DECISION REQUIRED

**Status:** USVOJENO — nema otvorenih stavki

Oznake stavki su redni brojevi ovog registra. **Nisu** Document ID. Stavka 1 je uklonjena: **PO DECISION 1 USVOJENO**. Stavka 2 je uklonjena: **PO DECISION 2 USVOJENO** (Predsjednik + Član 1 + Član 2). Stavka 3 je uklonjena: **PO DECISION 3 USVOJENO** (životni ciklus / runtime statusi). Stavka 4 je uklonjena: **PO DECISION 4 USVOJENO** (bodovanje). Stavka 5 je uklonjena: **PO DECISION 5 USVOJENO** (katalog skraćenica pojedinačnog konkursa: sljedeći slobodni `KN-RG-xxx`). Stavka 6 je uklonjena: **granica izvora Odluke** (naknada/Poslovnik; **nije** PO odluka). Stavka 7 je uklonjena: **PO DECISION 7 USVOJENO** (terminološka standardizacija za budući KN-FS, opcija 1).

Trenutno **nema** otvorenih stavki. Nova pitanja koja izvor ne rješava jednoznačno i dalje se evidentiraju ovdje prema pravilu 8. Preporuka **nije** odluka.

Q1–Q6, PO DECISION 1–5 i PO DECISION 7 ostaju RESOLVED. Q1–Q6 nijesu BM blocker. Bivša stavka 6 nije otvorena PO odluka.

---

Napomena: DERIVED DATE stupanja na snagu (čl.32) ostaje u KN-PRO; nije BM blocker i nije stavka ovog registra.

---

**Kraj dokumenta KN-BM-001 v0.2.10 USVOJENO**
