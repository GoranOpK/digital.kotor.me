# Digital Kotor
# Funkcionalna specifikacija Konkursa
## Modul: Konkursi

**Oznaka dokumenta:** KN-FS-001
**Naziv:** Funkcionalna specifikacija Konkursa
**Modul:** Konkursi
**Namespace:** KN
**Status dokumenta:** USVOJENO
**Verzija:** 0.2.12
**Datum:** 2026-09-03

**Poslovni SSOT:** KN-BM-001 v0.2.8 USVOJENO (`docs/business-model/Business_Model_Konkursi.md`; `KN-PATCH-BM-001`)

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1.0 | 2026-08-18 | Kreirana početna struktura KN-FS-001. Sljedivost uspostavljena prema KN-BM-001. Funkcionalni zahtjevi i BR identifikatori nijesu uneseni; čekaju usvajanje poslovnih pravila iz KN-BM-001 nakon analize Odluke. Bez preuzimanja zahtjeva iz postojeće implementacije ženskog preduzetništva. |
| 0.2.0 | 2026-08-18 | Izvršena prva puna funkcionalna derivacija iz KN-BM-001 v0.2.7 USVOJENO. Pokriveno KN-BR-001…080. Primijenjena V1 granica (PO DECISION 1). Otvorene PO odluke evidentirane u §14. KN-TS nije mijenjan. Kod nije mijenjan. Status ostaje NACRT. |
| 0.2.1 | 2026-08-18 | FS PO DECISION 1 resolved / USVOJENO. Postojeći Konkurs administrator (`konkurs_admin`, prikaz: Administrator konkursa) ponovo se koristi kao aplikacioni administrator KN modula. Nova uloga nije kreirana. Pravna/poslovna nadležnost Sekretarijata ostaje nepromijenjena. Stavka 1 uklonjena iz otvorenih PO DECISION REQUIRED. KN-TS nije mijenjan. Kod nije mijenjan. Status ostaje NACRT. |
| 0.2.2 | 2026-08-18 | FS PO DECISION 2 resolved / USVOJENO. Prije konačne predaje: tehnički međuspremnik i izmjena sadržaja. Konačna predaja: posebna svjesna radnja; evidentira se trenutak predaje. Poslije predaje: zaključani P1/P2/prilozi; nema brisanja predane prijave; nema reopen/unlock. Nepotpunost dokumentacije nije automatska zabrana zaprimanja. `draft`/`submitted` nijesu kanonski runtime statusi. Pravilo „jedna prijava po korisniku“ nije uvedeno. Stavka 2 uklonjena iz otvorenih PO DECISION REQUIRED. KN-TS nije mijenjan. Kod nije mijenjan. Status ostaje NACRT. |
| 0.2.3 | 2026-08-18 | FS PO DECISION 7 USVOJENO: registrovani korisnik smije imati najviše jednu konačno predanu prijavu po Javnom pozivu/konkursu (User + Competition). Tehnički nacrt ne zauzima to pravo trajno. Ista osoba smije predati prijave na različite konkurse; različiti korisnici smiju predati na isti konkurs. Pravilo je eksplicitna PO odluka, zadržana iz platformskog obrasca, iako KN-BM/Odluka to ne propisuju. KN-FR-042 usklađen. Status ostaje NACRT. |
| 0.2.4 | 2026-08-18 | Ispravljena numeracija/evidencija: pravilo jedne konačno predane prijave po korisniku po konkursu nije posebna FS PO DECISION 7. Evidentirano je kao dio **FS PO DECISION 2 — RESOLVED / USVOJENO** i KN-FR-042. Nova PO odluka nije otvorena. Tehničko rješenje (index, controller, migracija) nije određeno. Kod nije mijenjan. Status ostaje NACRT. |
| 0.2.5 | 2026-08-18 | FS PO DECISION 3 resolved / USVOJENO — OPCIJA A. Pojedinačne ocjene članova Komisije su cijeli brojevi 1–5. Prosjek po kriterijumu = zbir/3 i smije imati decimale. Konačna ocjena = zbir 10 prosjeka i smije imati decimale. Nema rounding/floor/ceil/truncation. Ranije „bez decimala“ odnosi se samo na pojedinačne ocjene. Prag ispod 30 ostaje na stvarno izračunatoj konačnoj ocjeni. Stavka 3 uklonjena iz otvorenih PO DECISION REQUIRED. KN-BM/KN-TS/kod nijesu mijenjani. Status ostaje NACRT. |
| 0.2.6 | 2026-08-19 | Konzistentnost Unit 1 / governance: BM PO DECISION vs FS PO DECISION u status tabeli (§2, §5, §8, §9, §11); governance usklađen sa usvojenim FS PO odlukama (uključujući KN-FR-042); historijski red 0.2.3 ostaje nepromijenjen; §14.2 stavka 5 bez zastarjele oznake 0.2.2. Nema novih poslovnih odluka. FS PO DECISION 1–3 ostaju RESOLVED / USVOJENO. Stavke 4–6 ostaju PENDING PO. KN-BM/KN-TS/kod nijesu mijenjani. Status ostaje NACRT. |
| 0.2.7 | 2026-08-19 | Unit 5 korekcija: §6.7 intervju ostaje poslovni korak; zakazivanje i evidencija intervjua nijesu V1 digitalni workflow; V1 ostaje elektronsko bodovanje P3, uključujući kriterijum 10 nakon održanog intervjua. KN-FR-041: uklonjena pogrešna sljedivost KN-BR-041 (zaključavanje ostaje FS PO DECISION 2 / KN-BR-009). KN-FR-030: preliminarna rang lista se automatski formira (KN-BR-038). Nova PO odluka nije donesena. KN-BM/KN-PRO/KN-TS/kod nijesu mijenjani. Status ostaje NACRT. |
| 0.2.8 / KN-PATCH-FS-001 | 2026-09-03 | Usklađivanje sa potvrđenim PO pravilom bodovanja i KN-BM-001 v0.2.8 / KN-PATCH-BM-001: pojedinačne ocjene = cijeli brojevi 1–5; formula nepromijenjena; konačni/ukupni skor prikazuje se na dvije decimale (DISPLAY); prag na CALCULATION VALUE (KN-FR-029). Izmijenjeni KN-FR-028, §8.1, §13 t.5, §14.1 FS PO DECISION 3. OPEN PO 4–6 nepromijenjene. Status ostaje NACRT. KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.9 / KN-PATCH-FS-002 | 2026-09-03 | FS PO DECISION 4 resolved / USVOJENO — **OPTION D**. Tokom cijelog bodovanja član (uključujući Predsjednika) vidi samo sopstvene ocjene. Tuđe individualne ocjene i agregati Komisiji postaju dostupni tek kad je bodovanje završeno za cijeli konkurs i rang-lista formirana. `konkurs_admin` nije scoring actor. Isto pravilo za žensko / mladi. Vidljivost liste podnosiocu / javna objava liste **nije** odlučena ovom odlukom. Izmijenjeni KN-FR-035, §10, §13, §14. PO 5–6 PENDING. Status ostaje NACRT. KN-BM/KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.10 / KN-PATCH-FS-003 | 2026-09-03 | FS PO DECISION 5 resolved / USVOJENO — **OPTION B — BALANCED**. Kanonski V1 katalog statusa **prijave**: `draft`, `submitted`, `approved`, `rejected` (isti model žensko / mladi). `draft` = STORED; ne zauzima User+Competition=1. `evaluated` = DERIVED (nije kanonski). Admin review / potpunost / prigovor / intervju / bodovanje / rangiranje = nisu statusi prijave. UI label za `submitted` ostaje **PENDING PO 6**. Izmijenjeni KN-FR-011, KN-FR-040, KN-FR-041, KN-FR-031, §9, §12, §13, §14. PO 6 PENDING. Status ostaje NACRT. KN-BM/KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.11 / KN-PATCH-FS-004 | 2026-09-03 | **PO CORRECTION** FS PO DECISION 5 — **OPTION B — BALANCED**, `evaluated` zadržan kao **STORED** kanonski status prijave (isti model žensko / mladi). Kanonski katalog: `draft`, `submitted`, `evaluated`, `approved`, `rejected`. Minimalni prelazi: `draft`→`submitted`; `submitted`→`evaluated` (kad je evaluacija završena i nije ranije odbijena); `submitted`→`rejected`; `evaluated`→`approved` \| `evaluated`→`rejected`. Bez nove PO: nema povratka u `draft` iz `submitted`/`evaluated`/`approved`/`rejected`. Klasifikacija admin review / prigovor / intervju / bodovanje u toku / rangiranje nepromijenjena (nisu statusi prijave). UI label za `submitted` ostaje **PENDING PO 6**. Izmijenjeni KN-FR-031, KN-FR-041, §9, §12, §13, §14. PO 6 PENDING. Status ostaje NACRT. KN-BM/KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.12 / KN-PATCH-FS-005 | 2026-09-03 | FS PO DECISION 6 resolved / USVOJENO — **OPTION A**. Kanonski UI labeli statusa prijave (backend nepromijenjen): `draft`→**Nacrt**; `submitted`→**Podnesena**; `evaluated`→**Ocijenjena**; `approved`→**Odobrena**; `rejected`→**Odbijena**. **„U obradi“ nije** kanonski UI label za `submitted`. Isto za žensko / mladi (ako youth UI nije implementiran — isti labeli). UI-only; **NEW BUSINESS RULE = NO**. Javni poziv vs Javni konkurs ostaje kontekstualno; prijava ≠ zahtjev. Izmijenjeni KN-FR-040, §3.2, §9, §12, §13, §14. Sve FS PO odluke zatvorene (0 otvorenih). Status ostaje NACRT. KN-BM/KN-PRO/KN-TS/kod nijesu mijenjani. |
| 0.2.12 — PO APPROVAL | 2026-09-03 | Formalno PO usvajanje KN-FS-001 v0.2.12. Status: USVOJENO. Nema promjene funkcionalnih zahtjeva ni poslovnih pravila. Zaključana FS baseline i SSOT za izradu KN-TS. |

Napomena uz historiju 0.2.3 (red se **ne** mijenja): oznaka „FS PO DECISION 7“ u tom redu je tadašnja numeracija. Od v0.2.4 isto pravilo (jedna konačno predana prijava, User + Competition) evidentirano je kao dio **FS PO DECISION 2 — RESOLVED / USVOJENO** i KN-FR-042. Trenutna numeracija nije FS PO DECISION 7.

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (`KN-PATCH-FS-001`, …),
- datum,
- kratak naziv,
- kratak opis izmjene.

PATCH model: KN-RG-001 / DK-DS-001 §8. Izdat: `KN-PATCH-FS-001` (v0.2.8), `KN-PATCH-FS-002` (v0.2.9), `KN-PATCH-FS-003` (v0.2.10), `KN-PATCH-FS-004` (v0.2.11), `KN-PATCH-FS-005` (v0.2.12).

---

## Svrha dokumenta

Dokument predstavlja referentnu funkcionalnu specifikaciju cjeline Konkursi.

U verziji 0.2.12 razrađuje **kako** usvojena poslovna pravila iz KN-BM-001 v0.2.8 treba da se ponašaju u digitalnom servisu za V1, uz sljedivost `KN-BR-*` → `KN-FR-*` → KN-TS = PENDING, uključujući **FS PO DECISION 1**, **FS PO DECISION 2**, **FS PO DECISION 3**, **FS PO DECISION 4** (OPTION D — scoring visibility; `KN-PATCH-FS-002`), **FS PO DECISION 5** (OPTION B — BALANCED, PO CORRECTION: `evaluated` STORED; `KN-PATCH-FS-004`) i **FS PO DECISION 6** (OPTION A — kanonski UI labeli statusa prijave; `submitted` → **Podnesena**; `KN-PATCH-FS-005`).

Identifikatori `KN-FR-*` su lokalni identifikatori funkcionalnih zahtjeva ovog dokumenta (DK-DS-001 §5, MODULE-INTERNAL; KN-RG-001: `{NS}-FR-001`). **Nisu** Document ID, **nisu** Feature Registry dokument, **nisu** `KN-BR-*` i **nisu** KK `BR-*`.

KN-BM-001 ostaje poslovni SSOT. Ovaj FS **ne** uvodi tiho poslovna pravila mimo KN-BM-001 i **ne** izvodi zahtjeve iz postojeće implementacije ženskog preduzetništva, platformskog obrasca niti ponašanja koda. Pravilo koje BM ne propisuje smije stajati u FS **samo** ako je eksplicitno zabilježeno i usvojeno kao FS PO odluka (§14); takva odluka **ne** mijenja tiho KN-BM-001. Status dokumenta: **USVOJENO**.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Svrha modula | USVOJENO — derivacija iz KN-BM-001 v0.2.8 |
| 2. Obuhvat V1 | USVOJENO — BM PO DECISION 1 |
| 3. Granice funkcionalnosti | USVOJENO — OUTSIDE CURRENT V1 |
| 4. Regulatorna i dokumentaciona pravila | USVOJENO |
| 5. Akteri | USVOJENO — poslovni vs aplikacioni akter; FS PO DECISION 1 — RESOLVED / USVOJENO |
| 6. Funkcionalni zahtjevi i tokovi | USVOJENO — KN-FR-001 … KN-FR-042 |
| 7. Poslovna pravila (BR) | USVOJENO — mapa KN-BR → KN-FR / kategorija |
| 8. Validacije | USVOJENO — BUSINESS VALIDATION iz KN-BM-001; usvojene FS PO odluke smiju dodati FS zahtjev; TECHNICAL = PENDING TS |
| 9. Statusi | USVOJENO — FS PO DECISION 5 — RESOLVED WITH PO CORRECTION — OPTION B; UI labeli — FS PO DECISION 6 — OPTION A |
| 10. Autorizacija sa poslovnog stanovišta | USVOJENO — vidljivost/unos; bez middleware |
| 11. Ivice slučajeva (edge cases) | USVOJENO — BM §28; usvojene FS PO odluke gdje su već evidentirane |
| 12. Sljedivost prema Business Modelu | USVOJENO |
| 13. Prihvatni kriterijumi V1 | USVOJENO |
| 14. PO DECISION REQUIRED | USVOJENO — 0 otvorenih; stavke 1–6 RESOLVED / USVOJENO (stavka 5 sa PO CORRECTION; stavka 6 OPTION A) |
| 15. KN-BR coverage 001–080 | USVOJENO |

---

# Pravila upravljanja Functional Specification

1. Funkcionalna specifikacija pripada cjelini Konkursi (KN-FS-001).

2. Posljednja usvojena verzija Functional Specification predstavlja jedini izvor istine za funkcionalne zahtjeve. KN-FS-001 **v0.2.12** je **USVOJENO** (formalno PO usvajanje) i predstavlja zaključanu FS baseline i SSOT za izradu KN-TS. Poslovni SSOT ostaje KN-BM-001 v0.2.8 USVOJENO.

3. Poglavlja sa statusom USVOJENO mijenjaju se isključivo kroz PATCH.

4. Cursor ne smije samostalno unositi, pretpostavljati ili dopunjavati funkcionalne zahtjeve niti poslovna pravila. Postojeća implementacija, platformski obrazac i ponašanje koda **nisu** izvor zahtjeva.

5. KN-FS-001 ostaje sljediv prema KN-BM-001. Ne dokumentuje privremena tehnička ograničenja trenutne implementacije. **Ne** uvodi tiho pravila kojih nema u KN-BM-001. Ako FS treba pravilo koje KN-BM-001 ne propisuje, to pravilo smije stajati u FS **samo** kada je eksplicitno zabilježeno i usvojeno kao FS PO odluka (§14). Usvojena FS PO odluka **ne** mijenja tiho KN-BM-001 i ostaje sljediva kao FS-nivo odluka dok se BM formalno ne izmijeni.

6. Ako KN-BM-001 nije jednoznačan za funkcionalni zahtjev, **ne bira se rješenje**. Stavka se označava `PO DECISION REQUIRED` (§14). Preporuka **nije** odluka.

7. Identifikatori `KN-BR-*` se **ne** mijenjaju. FS ih referencira; ne duplicira kao novi poslovni katalog.

---

# Upravljanje promjenama

Svaka izmjena funkcionalnog sadržaja mora biti rezultat usvojene odluke i evidentirana kroz `KN-PATCH-FS-*`.

---

## Sadržaj

1. Svrha modula
2. Obuhvat V1
3. Granice funkcionalnosti
4. Regulatorna i dokumentaciona pravila
5. Akteri
6. Funkcionalni zahtjevi i tokovi
7. Poslovna pravila (BR)
8. Validacije
9. Statusi
10. Autorizacija sa poslovnog stanovišta
11. Ivice slučajeva (edge cases)
12. Sljedivost prema Business Modelu
13. Prihvatni kriterijumi V1
14. PO DECISION REQUIRED
15. KN-BR coverage 001–080

---

# 1. Svrha modula

Cjelina **Konkursi** (`KN`) dokumentuje podršku preduzetnicima i mikro, malim i srednjim preduzećima (MMSP) kroz dodjelu subvencija.

Ovaj dokument razrađuje **kako** usvojena poslovna pravila iz KN-BM-001 v0.2.8 treba da se ponašaju u digitalnom servisu Opštine (`digital.kotor.me` / `www.digital.kotor.me`) za **V1 FUNCTIONAL SCOPE**.

Terminologija prati **BM PO DECISION 7**: termini iz pravnih i poslovnih izvora zadržavaju se prema stvarnom kontekstu. Ne uvodi se univerzalni zamjenski termin (npr. preduzetnik / društvo / MMSP / DOO; plan ulaganja / biznis plan; Javni poziv / Javni konkurs).

Postojeći tok ženskog preduzetništva **nije** izvor zahtjeva ovog FS-a.

---

# 2. Obuhvat V1

**Status:** USVOJENO — **BM PO DECISION 1** (KN-BM-001 §6)

**V1 FUNCTIONAL SCOPE** — samo koraci koje Odluka eksplicitno vezuje za digitalni servis:

| # | Korak | Izvor BM | KN-BR |
|---|-------|----------|-------|
| 1 | Objava Javnog poziva na digitalnom servisu | KN-BM §6; čl.6 | KN-BR-003, KN-BR-006, KN-BR-007 |
| 2 | Elektronsko podnošenje prijave i dokumentacije | čl.14, čl.16 | KN-BR-009, KN-BR-078 … KN-BR-080 |
| 3 | Elektronsko popunjavanje P2 | čl.17 | KN-BR-077; KN-BM §14 |
| 4 | Prigovor putem digitalnog servisa | čl.18 | KN-BR-042 |
| 5 | Obavještavanje registrovanim mailom | čl.18 | KN-BR-042 |
| 6 | Elektronsko bodovanje P3 | čl.20–21 | KN-BR-029 … KN-BR-040 |
| 7 | Generisanje predloga Odluke i arhiva aplikacije | čl.23 | KN-BR-051, KN-BR-052 |
| 8 | Objava Odluke na digitalnom servisu | čl.25 | KN-BR-056 |

Cjelokupan pravni proces **nije** automatski V1. Spisak se **ne** proširuje bez PO odluke.

Pomoćne V1 funkcije koje nužno služe gornjim koracima (pregled prijava, potpunost, P3, rang liste, prag 30, limiti čl.19 pri utvrđivanju iznosa na konačnoj listi) izvode se iz istih BM pravila i ostaju u V1.

---

# 3. Granice funkcionalnosti

**Status:** USVOJENO

## 3.1 BUSINESS PROCESS — OUTSIDE CURRENT V1

Prema BM PO DECISION 1 i KN-BM-001 §29, sljedeći koraci **nisu** V1 softverski obuhvat. FS **ne** projektuje njihove funkcije.

| Korak | BM | KN-BR | Oznaka |
|-------|----|-------|--------|
| Ugovor o dodjeli subvencija | §20 | KN-BR-057, KN-BR-058 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Isplata | §20 | KN-BR-059 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Realizacija / monitoring / terenska kontrola | §21 | KN-BR-060 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Preusmjeravanje sredstava | §21 | KN-BR-061, KN-BR-062 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Izvještavanje P4 / P4a | §22 | KN-BR-063 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Povraćaj | §23 | KN-BR-064, KN-BR-065 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Javna promocija | §24 | KN-BR-066 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Izvještaj Skupštini | §24 | KN-BR-067 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Rješenja o dodjeli/odbijanju; tužba; odustanak nakon Odluke | §19 | KN-BR-053 … KN-BR-055 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| De minimis izjava (nakon odobrenja sredstava) | KN-BR-018 | KN-BR-018 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Post-obaveza ne-gašenja 3 godine | KN-BR-019 | KN-BR-019 | BUSINESS PROCESS — OUTSIDE CURRENT V1 |
| Kanali objave van digitalnog servisa (dnevni list, Radio Kotor, oglasna tabla, vebsajt Opštine kao zaseban kanal) | KN-BR-007, KN-BR-056 | — | BUSINESS PROCESS — OUTSIDE CURRENT V1 (osim kanala digitalnog servisa) |

## 3.2 Šta ovaj FS nije

* nije KN-TS (nema API, ruta, DB šema, queue, SMTP, Laravel Mail, middleware, permission stringova);
* nije UI dizajn; kanonski UI labeli statusa prijave određeni su **FS PO DECISION 6 — OPTION A** (`submitted` → **Podnesena**; **„U obradi“ nije** label za `submitted`); kontekstualni termini (npr. Javni poziv / Javni konkurs) ostaju po BM PO DECISION 7 — **ne** forsira se jedan univerzalni label;
* nije pun procesni runtime state machine za sve faze (admin review, prigovor, intervju, bodovanje, rangiranje) — samo minimalni kanonski katalog statusa **prijave** iz **FS PO DECISION 5**;
* nije tehnička integracija Zoom/Teams/Viber/WhatsApp (KN-BR-047 = poslovno dozvoljen kanal, ne integracija);
* nije preuzimanje legacy ponašanja ženskog preduzetništva koje krši FS PO DECISION 2 (izmjena/brisanje predane prijave; reopen); širi legacy katalog van **FS PO DECISION 5** **nije** kanonski. Status **`evaluated`** je zadržan kao **STORED** kanonski status (**PO CORRECTION** FS PO DECISION 5; dokazani women lifecycle, isti model za mlade).

MIME limiti, veličina fajla, storage putanje: **TECHNICAL VALIDATION — PENDING TS**. BM ih ne propisuje.

---

# 4. Regulatorna i dokumentaciona pravila

Funkcionalni sadržaj prati:

* DK-DS-001 i `docs/METHODOLOGY.md`;
* KN-RG-001;
* KN-PRO-001 (pravni izvor, preko BM);
* KN-BM-001 v0.2.8 USVOJENO (jedini poslovni SSOT za ovu derivaciju).

Pravni akt: **Odluka o podršci preduzetnicima i mikro, malim i srednjim preduzećima kroz dodjelu subvencija**, Skupština Opštine Kotor, broj `11-016/26-12679`.

Zabranjeno:

* izmišljanje poslovnih pravila;
* preuzimanje zahtjeva iz koda ili dokumentacije ženskog preduzetništva;
* uvođenje runtime uloga, ruta ili statusa kao funkcionalnih pravila **mimo** usvojenih FS PO odluka;
* Član 3 / Član 4 / dodatni ocjenjivači;
* ocjena 0, prazna ocjena, neocijenjen kriterijum;
* izmišljeno pravilo zaokruživanja.

---

# 5. Akteri

**Status:** USVOJENO — poslovni vs aplikacioni akter; **FS PO DECISION 1 — RESOLVED / USVOJENO**

Ovo su **poslovni akteri** iz KN-BM-001 §8. Ne mapiraju se na Laravel middleware ni permission stringove. Tabela opisuje šta akter **funkcionalno mora moći** u V1, gdje je relevantan.

**Razlika poslovnog/pravnog i aplikacionog aktera (FS PO DECISION 1 — USVOJENO):**

* **POSLOVNI / PRAVNI AKTER:** Sekretarijat, Komisija i drugi akteri prema Odluci i KN-BM-001. Pravna nadležnost Sekretarijata **nije** izmijenjena ovom odlukom.
* **APLIKACIONI AKTER:** postojeći **Administrator konkursa** (`konkurs_admin`) izvršava administrativne funkcije KN modula na digitalnom servisu. To je postojeća platformska uloga (žensko preduzetništvo, ovaj KN konkurs, budući konkursni moduli). **Nova KN-specifična administratorska uloga se ne kreira.**
* `konkurs_admin` **ne** postaje član Komisije zbog administratorske uloge.
* Komisija i dalje ima tačno: Predsjednik, Član 1, Član 2.
* Permission stringovi, middleware, Laravel role konfiguracija i zaštita ruta ostaju za KN-TS-001, po postojećem Digital Kotor standardu. Ovaj FS ih ne specifikuje.

| Akter | V1 funkcionalne potrebe | Izvor |
|-------|-------------------------|-------|
| Preduzetnik | Popuniti P1a i P2; priložiti dokumentaciju čl.14; sačuvati tehnički međuspremnik prije predaje; predati elektronski posebnom radnjom; nakon predaje sadržaj je zaključan; uložiti prigovor putem digitalnog servisa u roku | KN-BM §8.1; KN-BR-009, KN-BR-042; FS PO DECISION 2 |
| Društvo / MMSP | Isto, sa P1b umjesto P1a (P1b je u obrascu naslovljen kao DOO; FS ne koriguje izvor) | KN-BM §8.2, §13.1 |
| Odgovorno lice | Potpis / naznaka tačnosti na P1b; kontakt podaci P1b | KN-BM §8.3 |
| Komisija (tijelo) | Pregled prijava; potpunost; odluka o prigovoru; bodovanje P3; rang liste; predlog Odluke | KN-BM §8.5 |
| Predsjednik | Bodovanje kao jedan od tri člana; unos zaključaka/obrazloženja odbijenih; zatvaranje Javnog poziva i arhiva | KN-BM §8.6; KN-BR-040, KN-BR-052 |
| Član 1 | Bodovanje svih 10 kriterijuma; intervju (poslovno); potpis rang liste; uvid samo u sopstvene bodove tokom bodovanja | KN-BM §8.7; KN-BR-034, KN-BR-035 |
| Član 2 | Isto kao Član 1 | KN-BM §8.8 |
| Sekretarijat | Pravni/poslovni akter: objava Odluke (čl.25); operativni koraci ugovora/nadzora **nisu** V1. Nadležnost **nije** prenesena na novu ulogu. | KN-BR-051, KN-BR-056; §3.1; FS PO DECISION 1 |
| Sekretar Sekretarijata | Imenovanje Komisije **nije** digitalni V1 kanal u BM; sastav 3 člana je pretpostavka V1 bodovanja | KN-BR-050, KN-BR-068 |
| Administrator konkursa (`konkurs_admin`) | Aplikacioni administrator KN: izvršava administrativne radnje koje ovaj FS dodjeljuje administratorskoj strani digitalnog servisa, uključujući unos i objavu Javnog poziva. Nije član Komisije. Nije nova uloga. | FS PO DECISION 1; postojeći `roles.name = konkurs_admin` |
| Korisnik subvencije | U V1: vidi objavljenu Odluku na digitalnom servisu. Ugovor/isplata/P4 = OUTSIDE V1 | KN-BM §8.4 |
| Opština Kotor | Kanali objave; digitalni kanal = V1 za Javni poziv i Odluku | KN-BR-007, KN-BR-056 |
| Skupština; Upravni sud; Predsjednik Skupštine | Nisu V1 operatori digitalnog servisa | KN-BM §8.12–8.13 |

**Komisija ima tačno 3 člana:** Predsjednik, Član 1, Član 2. Ne uvode se Član 3, Član 4 niti dodatni ocjenjivači (BM PO DECISION 2; KN-BR-050).

Zamjenski član (čl.7) i zamjena mandata (KN-BR-068) ostaju poslovna pravila imenovanja. FS ne projektuje digitalni workflow imenovanja (nije V1 kanal). Ko boduje: trenutna tročlana Komisija.

---

# 6. Funkcionalni zahtjevi i tokovi

**Status:** USVOJENO

Sljedivost: `KN-BR-*` → `KN-FR-*` → KN-TS = PENDING.

Tužba se **ne** pretvara u žalbu. Žalba se **ne** uvodi.

## 6.1 Javni poziv (V1 FUNCTIONAL SCOPE)

| ID | Zahtjev | KN-BR | Preduslov |
|----|---------|-------|-----------|
| KN-FR-001 | Digitalni servis omogućava objavu Javnog poziva (instrument raspodjele subvencija za tekuću godinu). Termin **Javni poziv** se koristi za ovaj akt (čl.5–6). Termin **Javni konkurs** ostaje samo za ranije programe iz čl.15 (KN-BR-016). | KN-BR-003, KN-BR-007 | BM PO DECISION 1; BM PO DECISION 7; FS PO DECISION 1 |
| KN-FR-002 | Objavljeni Javni poziv naročito sadrži: ukupan iznos, najviši iznos, vrste subvencija, uslove, dokumentaciju, kriterijume evaluacije, rok i način podnošenja, informativne sastanke i druge bitne podatke iz KN-BR-006. | KN-BR-006 | — |
| KN-FR-003 | Javni poziv je otvoren 20 dana od dana objavljivanja. Elektronska prijava se prima samo u tom roku. | KN-BR-008 | — |
| KN-FR-004 | Javni poziv se može raspisati jedan put godišnje, u trećem kvartalu tekuće godine. | KN-BR-004, KN-BR-005 | BUSINESS VALIDATION pri objavi |
| KN-FR-005 | Unos i objavu Javnog poziva na digitalnom servisu izvršava postojeći **Administrator konkursa** (`konkurs_admin`). Nova KN-specifična administratorska uloga se ne kreira. Raspisivanje ostaje poslovno kod Komisije (KN-BR-003); pravna nadležnost Sekretarijata ostaje nepromijenjena. | KN-BR-003, KN-BR-007 | **FS PO DECISION 1 — USVOJENO** |

## 6.2 Prijava P1a / P1b / P2 (V1 FUNCTIONAL SCOPE)

| ID | Zahtjev | KN-BR |
|----|---------|-------|
| KN-FR-006 | Podnosilac bira tip: preduzetnik → P1a; društvo/MMSP → P1b. Razlike obrazaca ostaju. | KN-BR-080; KN-BM §13.1 |
| KN-FR-007 | P1a obavezna polja: vrsta subvencije; ime i prezime; JMBG/PIB; adresa; šifra djelatnosti; broj zaposlenih; broj žiro računa; kontakt telefon; e-mail; website; datum registracije u CRPS; naznaka tačnosti / izjava o materijalnoj i krivičnoj odgovornosti; potpis preduzetnika + M.P. | KN-BM §13.2 |
| KN-FR-008 | P1b obavezna polja: vrsta subvencije; naziv društva; ime i prezime odgovornog lica; PIB društva; sjedište; datum osnivanja; šifra djelatnosti; broj zaposlenih; broj žiro računa; kontakt telefon odgovornog lica; e-mail odgovornog lica; website; naznaka tačnosti; potpis odgovornog lica + M.P. | KN-BM §13.3 |
| KN-FR-009 | P2 se popunjava elektronski. Struktura I–VII i polja iz KN-BM §14.1 (uključujući sopstveno učešće). FS ne izmišlja polja za P2 IV–VI koja BM nije nabrojao. | KN-BR-077; KN-BM §14 |
| KN-FR-010 | Vrste subvencija: tačno 3 iz čl.12. Podnosilac može konkurisati za najviše 2; podrška najviše za 1. | KN-BR-023, KN-BR-024 |
| KN-FR-011 | **Prije konačne predaje** podnosilac mora moći: unijeti P1a ili P1b; unijeti P2; dodavati i mijenjati potrebne priloge; sačuvati prijavu u statusu **`draft`** (tehnički međuspremnik); vratiti se i nastaviti; mijenjati uneseni sadržaj; brisati `draft` prema **FS PO DECISION 2**. Status **`draft`** je **STORED** kanonski status prijave (**FS PO DECISION 5**); **ne** zauzima pravo User+Competition=1 (KN-FR-042). | KN-BR-009; KN-BM §13 | **FS PO DECISION 2 — USVOJENO**; **FS PO DECISION 5 — USVOJENO, OPTION B** |
| KN-FR-012 | Starost nije uslov učešća. FS ne uvodi starosno polje kao uslov. | KN-BR-017 |
| KN-FR-040 | **Konačna predaja** je posebna, svjesna radnja podnosioca (**prelaz `draft` → `submitted`**). Sistem evidentira trenutak konačne predaje. Kanonska backend vrijednost statusa prijave nakon predaje je **`submitted`** (**FS PO DECISION 5**). Kanonski UI label za `submitted` je **Podnesena** (**FS PO DECISION 6 — OPTION A**). **„U obradi“ nije** kanonski UI label za status `submitted`. Prijava se tada smatra elektronski podnesenom (KN-BR-009). Tehnički uslovi da sistem izvrši predaju razlikuju se od naknadne poslovne provjere potpunosti od strane Komisije. Ne uvode se dodatne submit validacije kojih nema u KN-BM/Odluci. Nedostatak dokumenta **ne** onemogućava automatski zaprimanje. Prelaz `submitted` → `draft` **nije** dozvoljen bez nove PO odluke. | KN-BR-009 | **FS PO DECISION 2 — USVOJENO**; **FS PO DECISION 5 — USVOJENO, OPTION B**; **FS PO DECISION 6 — USVOJENO, OPTION A** |
| KN-FR-041 | **Poslije statusa `submitted`** podnosilac ne može mijenjati P1a/P1b, P2, niti dodavati, mijenjati ili brisati priloge, niti brisati predatu prijavu, niti reopen/unlock (**FS PO DECISION 2**). Predati sadržaj je sadržaj koji Komisija pregleda. Nema admin reopen, unlock ni vraćanja podnosiocu na izmjenu bez posebne buduće PO odluke. Prelazi iz `submitted`/`evaluated`/`approved`/`rejected` nazad u `draft` **nisu** dozvoljeni bez nove PO odluke. | KN-BR-009 | **FS PO DECISION 2 — USVOJENO**; **FS PO DECISION 5 — USVOJENO, OPTION B (PO CORRECTION)** |
| KN-FR-042 | Registrovani korisnik smije konačno podnijeti **samo jednu** prijavu na isti Javni poziv/konkurs (User + Competition). Status **`draft`** prije konačne predaje ne predstavlja konačno podnošenje i **ne** zauzima to pravo trajno; brisanje `draft` prije predaje ne predstavlja korišćenje prava na podnošenje. Nakon prelaza u **`submitted`** druga prijava istog korisnika za isti konkurs nije dozvoljena. Konačno predata prijava ne može se obrisati radi ponovnog apliciranja. Ista osoba smije predati na različite konkurse. Različiti registrovani korisnici smiju predati na isti konkurs. Pravilo je dio **FS PO DECISION 2** (eksplicitna PO odluka; nije izvedeno iz KN-BM/Odluke). Tehnički mehanizam = KN-TS-001. | — | **FS PO DECISION 2 — USVOJENO**; **FS PO DECISION 5 — USVOJENO, OPTION B** |

Postojeći modul ženskog preduzetništva smije se koristiti samo kao tehnički obrazac za save-then-submit, tehničko čuvanje nacrta, posebnu radnju konačne predaje i evidentiranje trenutka predaje. **Ne** preuzimaju se: izmjena predane prijave; izmjena priloga nakon predaje; hard delete predane prijave; širi legacy katalog van **FS PO DECISION 5**. Status **`evaluated`** je zadržan kao **STORED** kanonski (**PO CORRECTION** FS PO DECISION 5; dokazani women lifecycle). Jedna konačno predana prijava po korisniku po konkursu je **dio FS PO DECISION 2**, ne slijepa kopija ostalih legacy pravila.

## 6.3 Dokumentacija i prilozi (V1 FUNCTIONAL SCOPE)

Checklista = čl.14 + obrasci (PO Q2). De minimis izjava **nije** stavka prijave (KN-BR-018).

| ID | Zahtjev | KN-BR |
|----|---------|-------|
| KN-FR-013 | Preduzetnik prilaže stavke KN-BM §13.4 (1–13), uključujući uslovni IOPPD. | KN-BM §13.4 |
| KN-FR-014 | Društvo prilaže stavke KN-BM §13.5 (1–15), uključujući izuzetak godišnjih računa za društva registrovana u tekućoj godini i uslovnu analitiku kupaca. | KN-BM §13.5 |
| KN-FR-015 | Prateća dokumentacija se elektronski učitava u PDF formatu. | KN-BR-079 |
| KN-FR-016 | Uvjerenja o porezima iz čl.14 ne smiju biti starija od 30 dana. | KN-BR-078 |
| KN-FR-017 | Veličina fajla, MIME izvan PDF, storage: **TECHNICAL VALIDATION — PENDING TS**. | — |

## 6.4 Administrativna provjera i prigovor (V1 FUNCTIONAL SCOPE)

Ne uvodi se pun procesni state machine za sve faze (BM PO DECISION 3 + **FS PO DECISION 5**). Tok pregleda/prigovora/intervjua ostaje procesni (DERIVED / PROCESS EVENT / FLAG), ne katalog statusa prijave: prijava (`submitted`) → pregled → potpuna/nepotpuna → obavještenje → prigovor → odluka → intervju za potpune.

| ID | Zahtjev | KN-BR |
|----|---------|-------|
| KN-FR-018 | Komisija zakazuje prvu sjednicu najkasnije 7 dana od isteka roka za prijavu i pregleda elektronski zaprimljene prijave. | KN-BR-041 |
| KN-FR-019 | Nakon zaprimanja Komisija pregleda dokumentaciju i utvrđuje potpunost/nepotpunost. Nepotpuna prijava se označava i ne razmatra se dalje, uz pravo na prigovor. Nedostatak formalnih uslova je eliminatorni kriterijum. Ovo **nije** automatska tehnička zabrana zaprimanja pri konačnoj predaji (KN-FR-040). | KN-BR-020 | FS PO DECISION 2 |
| KN-FR-020 | Komisija obavještava podnosioca nepotpune prijave **registrovanim mailom** o pravu na prigovor putem digitalnog servisa u roku **3 dana** od slanja obavještenja. | KN-BR-042 |
| KN-FR-021 | Digitalni servis omogućava prigovor u tom roku od 3 dana. | KN-BR-042 |
| KN-FR-022 | Komisija odlučuje o prihvatanju ili odbijanju prigovora u roku 7 dana od prijema. | KN-BR-043 |
| KN-FR-023 | Druga sjednica i intervju za potpune prijave: u roku 7 dana od prve sjednice. Intervju zahtijeva prisustvo svih članova (poslovno). FS ne bira Zoom/Teams/Viber integraciju. | KN-BR-044, KN-BR-046, KN-BR-047 |

Obavještenje (KN-FR-020): događaj = utvrđena nepotpuna prijava; primalac = podnosilac (registrovani mail); svrha = pravo na prigovor; rok = 3 dana od slanja. SMTP/queue/template = PENDING TS.

## 6.5 Bodovanje, rangiranje, predlog Odluke (V1 FUNCTIONAL SCOPE)

Deset pozitivnih kriterijuma: KN-BM §17.1. Tri eliminatorna: KN-BM §17.2 (sva tri ostaju; P3 ne ukida E2 i E3).

| ID | Zahtjev | KN-BR |
|----|---------|-------|
| KN-FR-024 | Bodovanje vrše tačno Predsjednik, Član 1 i Član 2. P3 kolone Član 3/Član 4 nisu dodatni članovi. | KN-BR-050, KN-BR-034 |
| KN-FR-025 | Svaki od 3 člana ocjenjuje **svih 10** pozitivnih kriterijuma. | KN-BR-031, KN-BR-034 |
| KN-FR-026 | Ocjena po kriterijumu je **pojedinačna ocjena člana Komisije** i mora biti **isključivo cijeli broj** 1, 2, 3, 4 ili 5. Nema 0, prazne ocjene, neocijenjenog kriterijuma ni decimalne pojedinačne ocjene. | KN-BR-030 | **FS PO DECISION 3 — USVOJENO, OPCIJA A** |
| KN-FR-027 | **DURING SCORING:** tokom cijelog procesa bodovanja član Komisije (uključujući Predsjednika) ima uvid **isključivo u sopstvene** individualne ocjene. Nije dovoljno da je samo jedan član završio sopstveni unos da bi vidio tuđe ocjene. Post-scoring gate: KN-FR-035 / **FS PO DECISION 4 — OPTION D**. | KN-BR-035 | **FS PO DECISION 4 — USVOJENO, OPTION D; KN-PATCH-FS-002** |
| KN-FR-028 | **INPUT:** pojedinačne ocjene članova su cijeli brojevi 1–5 (KN-FR-026). **CALCULATION VALUE:** **Prosjek po kriterijumu** = zbir ocjena sva 3 člana / 3 (Predsjednik, Član 1, Član 2). Primjer: 4 + 3 + 3 = 10; 10 / 3 = 3,333… Prosjek smije imati decimale koje proizlaze iz formule. **Konačna ocjena** = zbir prosječnih ocjena svih 10 kriterijuma; CALCULATION VALUE smije imati decimale koje proizlaze iz formule. **DISPLAY VALUE:** **Konačni/ukupni skor prikazuje se na dvije decimale.** Ne pretvarati pojedinačne ocjene u decimale. Ne uvodi se rounding, floor, ceil ili truncation kao novo **poslovno** pravilo koje mijenja CALCULATION VALUE, prag ili rangiranje. Prag ostaje na stvarno izračunatoj konačnoj ocjeni (KN-FR-029). Ranije „bez decimala“ odnosi se samo na **pojedinačne ocjene članova 1–5**. | KN-BR-036, KN-BR-037 | **FS PO DECISION 3 — USVOJENO, OPCIJA A; KN-PATCH-FS-001** |
| KN-FR-029 | Plan sa **stvarno izračunatom** konačnom ocjenom ispod 30 bodova ne podržava se. Prag se primjenjuje na konačnu ocjenu formule, bez pretvaranja u cijeli broj. | KN-BR-029 | **FS PO DECISION 3 — USVOJENO, OPCIJA A** |
| KN-FR-030 | Po završetku ocjenjivanja **automatski** se formira preliminarna rang lista sa bodovima, bez utvrđenih iznosa. | KN-BR-038 |
| KN-FR-031 | Treća sjednica u roku 7 dana od druge sjednice i intervjua. Konačna rang lista: podržava/odbija, iznos, ime/naziv, vrsta, bodovi, potrebna i odobrena sredstva, potpisi svih članova. Predsjednik unosi zaključke i obrazloženja odbijenih. Kad je evaluacija/bodovanje završeno po funkcionalnim pravilima i prijava nije ranije odbijena, rezultat prelaza je **STORED** status **`evaluated`** (**FS PO DECISION 5**; prelaz `submitted` → `evaluated`). **Ishodi prijave** kroz konačni proces mapiraju se na kanonske statuse **`approved`** / **`rejected`** (prelazi `evaluated` → `approved` | `evaluated` → `rejected`). Odbijanje prije završene evaluacije: `submitted` → `rejected`. Preliminarna/konačna rang-lista **nije** status prijave. | KN-BR-039, KN-BR-040 | **FS PO DECISION 5 — USVOJENO, OPTION B (PO CORRECTION)** |
| KN-FR-032 | Iznos: ≤ 20% budžeta; ≤ 50% potrebnog, ili ≤ 80% za autentične proizvode/usluge (čl.12 t.1). Raspodjela po konačnoj listi do utroška. | KN-BR-025 … KN-BR-028 |
| KN-FR-033 | Eliminacija: E2 prethodni izvještaj; E3 plan van vrsta čl.12. | KN-BR-021, KN-BR-022, KN-BR-033 |
| KN-FR-034 | Eligibility na pregledu: teritorija; krivični postupak; porezi; zakonito poslovanje; zabrana člana Komisije; prethodni izvještaji ove Odluke i ranijih Javnih konkursa (žensko / mladi) ako izvještaji nijesu dostavljeni. | KN-BR-010 … KN-BR-016 |
| KN-FR-035 | **FS PO DECISION 4 — OPTION D (scoring visibility među članovima Komisije).** **DURING SCORING:** član vidi samo svoje individualne ocjene (KN-FR-027 / KN-BR-035); Predsjednik **nema** širi uvid tokom bodovanja. **POST-SCORING GATE:** individualne ocjene drugih članova i agregatni rezultati (prosjeci / konačni skor) postaju vidljivi članovima Komisije **tek** kada je (1) bodovanje završeno za **cijeli konkurs** i (2) rang-lista formirana. Do tada tuđe individualne ocjene = **NOT VISIBLE** (nije dovoljno da je samo jedan član završio sopstveni unos). `konkurs_admin` **nije** scoring actor i ne dobija ovo pravo po osnovu admin uloge. Isto pravilo za žensko preduzetništvo i preduzetništvo mladih. **Nije** odluka o tome da li podnosilac vidi preliminarnu/konačnu listu niti o javnoj objavi liste. | KN-BR-035, KN-BR-038, KN-BR-040 | **FS PO DECISION 4 — USVOJENO, OPTION D; KN-PATCH-FS-002** |
| KN-FR-036 | Kvorum = većina članova; bez kvoruma sjednica se odlaže. Punovažne odluke i intervju: svi članovi. | KN-BR-045, KN-BR-046 |

## 6.6 Predlog Odluke, arhiva, objava Odluke (V1 FUNCTIONAL SCOPE)

| ID | Zahtjev | KN-BR |
|----|---------|-------|
| KN-FR-037 | Komisija generiše predlog Odluke o dodjeli putem digitalnog servisa i predlaže Sekretarijatu. | KN-BR-051 |
| KN-FR-038 | Nakon predloga Odluke predsjednik zatvara Javni poziv i pohranjuje ga u arhivu aplikacije. | KN-BR-052 |
| KN-FR-039 | Sekretarijat ostaje pravni/poslovni akter objave Odluke (čl.25). Na digitalnom servisu tu administrativnu radnju izvršava postojeći Administrator konkursa (`konkurs_admin`); nova uloga se ne kreira. Ostali kanali objave nisu V1. Rok objave: 45 dana od isteka roka za prijavu (poslovni rok; scheduler = PENDING TS). | KN-BR-051, KN-BR-056 | FS PO DECISION 1 |

Sadržaj Odluke (čl.24, informativno za predlog): ime/naziv korisnika; vrsta; iznosi; ukupan iznos potreban za realizaciju svakog plana. Rješenja i tužba = OUTSIDE V1.

## 6.7 Intervju — digital vs kanal

Usmeni intervju je poslovni korak (KN-BR-044, KN-BR-046). Sjednice **mogu** biti elektronske (zoom/teams ili viber/whatsapp) — **poslovno dozvoljen kanal** (KN-BR-047). FS **ne** bira tehničku integraciju tih platformi (TS ili posebna PO odluka). Zakazivanje i evidencija intervjua **nisu** V1 digitalni workflow.

V1 digitalno obuhvata elektronsko bodovanje P3. Kriterijum 10 (prezentacija plana na intervjuu) ostaje dio P3 i ocjenjuje se nakon održanog intervjua.

---

# 7. Poslovna pravila (BR)

**Status:** USVOJENO

Ovaj FS **ne** kreira nove `KN-BR-*`. Kanonski katalog ostaje KN-BM-001 (`KN-BR-001` … `KN-BR-080`).

Puna pokrivenost: §15 (80/80). Sažetak:

* **A — V1 FUNCTIONAL REQUIREMENT:** ima `KN-FR-*` ili je BUSINESS VALIDATION u V1 toku.
* **B — BUSINESS RULE — NO DIRECT V1 FUNCTION:** važi poslovno; nema posebne V1 funkcije (npr. javnost rada, naknada, Poslovnik).
* **C — OUTSIDE CURRENT V1:** pravni proces van digitalnog V1.

---

# 8. Validacije

**Status:** USVOJENO — BUSINESS VALIDATION iz KN-BM-001; usvojene FS PO odluke smiju dodati FS zahtjev; TECHNICAL = PENDING TS

## 8.1 BUSINESS VALIDATION (iz KN-BM)

| Validacija | Pravilo | KN-BR |
|------------|---------|-------|
| Rok prijave | samo u 20 dana od objave | KN-BR-008 |
| Jedna predana prijava | User + Competition = najviše jedna konačno predana prijava (`submitted`+); `draft` ne zauzima pravo trajno | FS PO DECISION 2; FS PO DECISION 5; KN-FR-042 |
| Jedan poziv / treći kvartal | KN-BR-004, KN-BR-005 | KN-BR-004, KN-BR-005 |
| Potpunost dokumentacije | Komisija utvrđuje nakon zaprimanja (checklista čl.14 + P1a/P1b + P2). **Nije** automatska zabrana zaprimanja pri konačnoj predaji | KN-BR-020, KN-BR-080; FS PO DECISION 2 |
| PDF prilozi | PDF | KN-BR-079 |
| Starost uvjerenja | ≤ 30 dana | KN-BR-078 |
| Broj vrsta | max 2 u konkurenciji; max 1 odobrena | KN-BR-024 |
| Katalog vrsta | 3 vrste čl.12 | KN-BR-023 |
| Eligibility | KN-BR-010 … KN-BR-016 | KN-BR-010 … KN-BR-016 |
| Skala | Pojedinačne ocjene članova: cijeli brojevi 1, 2, 3, 4 ili 5; nema 0 / prazno / neocijenjeno / decimalne pojedinačne ocjene | KN-BR-030; FS PO DECISION 3 |
| Svih 10 × 3 člana | Predsjednik, Član 1 i Član 2 ocjenjuju svih 10 pozitivnih kriterijuma | KN-BR-034 |
| Prosjek po kriterijumu | zbir ocjena sva 3 člana / 3 (CALCULATION VALUE; smije imati decimale iz formule); nema poslovnog rounding pravila koje mijenja obračun | KN-BR-036; FS PO DECISION 3; KN-PATCH-FS-001 |
| Konačna ocjena | zbir 10 prosjeka (CALCULATION VALUE); **konačni/ukupni skor prikazuje se na dvije decimale** (DISPLAY); prag/rangiranje na CALCULATION VALUE | KN-BR-037; FS PO DECISION 3; KN-PATCH-FS-001 |
| Prag | stvarno izračunata konačna ocjena < 30 → bez podrške | KN-BR-029; FS PO DECISION 3 |
| Limiti iznosa | 20%; 50% ili 80% | KN-BR-025 … KN-BR-027 |
| Bruto plate u planu | ugovor ≥ 12 mj.; subvencionisani period ≤ 6 mj. | KN-BR-071 |
| Marketing troškovi u P2 / planu ulaganja | website / domen / oglašavanje: planirani period do 12 mjeseci | KN-BR-076 |
| Rok prigovora | 3 dana od slanja obavještenja | KN-BR-042 |
| Rok odluke o prigovoru | 7 dana od prijema | KN-BR-043 |

Ne uvodi se Laravel validator sintaksa.

## 8.2 TECHNICAL VALIDATION — PENDING TS

Veličina fajla, storage, MIME izvan PDF, SMTP, queue, cron/scheduler za isteke rokova, autentikacija sesije.

---

# 9. Statusi

**Status:** USVOJENO — **FS PO DECISION 5 — RESOLVED WITH PO CORRECTION — OPTION B — BALANCED** (`evaluated` = STORED); **UI labeli — FS PO DECISION 6 — OPTION A**

## 9.1 Kanonski V1 katalog statusa **prijave** (žensko = mladi)

Isti model za žensko preduzetništvo i preduzetništvo mladih (**WOMEN STATUS MODEL = SAME; YOUTH STATUS MODEL = SAME**). Ako youth UI nije implementiran, koristiće iste kanonske UI labele (**FS PO DECISION 6**).

| Status | UI label (kanonski) | Značenje | Napomena |
|--------|---------------------|----------|----------|
| `draft` | **Nacrt** | tehnički pre-final; **STORED** | editable/deletable po **FS PO DECISION 2**; **ne** zauzima User+Competition=1 |
| `submitted` | **Podnesena** | konačna predaja (eksplicitna radnja); **STORED** | nakon toga podnosilac nema edit/delete/reopen (**FS PO DECISION 2**); kanonska backend vrijednost = `submitted`; UI label = **Podnesena** (**FS PO DECISION 6 — OPTION A**); **„U obradi“ nije** kanonski UI label za ovaj status |
| `evaluated` | **Ocijenjena** | faza bodovanja/evaluacije završena po funkcionalnim pravilima; **STORED** | nije samo DERIVED; podaci o završetku bodovanja smiju usloviti prelaz, ali rezultat je pohranjeni status `evaluated` |
| `approved` | **Odobrena** | odobrena kroz konačni proces; **STORED** | ishod |
| `rejected` | **Odbijena** | odbijena kroz odgovarajući proces; **STORED** | ishod |

### Kanonski UI labeli (FS PO DECISION 6 — OPTION A)

| Backend | UI label |
|---------|----------|
| `draft` | Nacrt |
| `submitted` | Podnesena |
| `evaluated` | Ocijenjena |
| `approved` | Odobrena |
| `rejected` | Odbijena |

**Eksplicitno:** **„U obradi“ nije** kanonski UI label za status `submitted`. Backend vrijednosti ostaju nepromijenjene. Odluka je **UI-only**; **NEW BUSINESS RULE = NO**.

### Minimalni kanonski prelazi

* `draft` → `submitted`
* `submitted` → `evaluated` (kad je evaluacija završena i prijava nije ranije odbijena)
* `submitted` → `rejected` (odbijanje prije završene evaluacije)
* `evaluated` → `approved`
* `evaluated` → `rejected`

**Nije** dozvoljeno bez nove PO odluke: `submitted`/`evaluated`/`approved`/`rejected` → `draft`. **FS PO DECISION 2** nepromijenjena.

Međukoraci procesa (pregled, potpunost, prigovor, intervju, bodovanje u toku, rangiranje) **postoje**, ali **nisu** statusi prijave.

## 9.2 Šta **nije** kanonski status prijave

| Pojam | Klasifikacija |
|-------|---------------|
| admin review | NOT CANONICAL APPLICATION STATUS (PROCESS EVENT / FLAG) |
| docs complete / incomplete (potpuna/nepotpuna) | NOT CANONICAL APPLICATION STATUS (FLAG / PROCESS EVENT) |
| complaint / prigovor | NOT CANONICAL APPLICATION STATUS (SEPARATE ENTITY / PROCESS EVENT) |
| interview / intervju | NOT CANONICAL APPLICATION STATUS (PROCESS EVENT) |
| scoring in progress / scoring complete | NOT CANONICAL APPLICATION STATUS (PROCESS EVENT / DERIVED) — podatak o završetku smije usloviti prelaz u `evaluated`, ali sam nije status prijave |
| preliminary ranking / final ranking | NOT CANONICAL APPLICATION STATUS (COMPETITION-LEVEL / DERIVED ARTIFACT) |
| Decision formed / predlog Odluke / Decision publication | NOT CANONICAL APPLICATION STATUS (PROCESS EVENT) |
| contracted / reporting | NOT CANONICAL APPLICATION STATUS (OUTSIDE CURRENT V1 / SEPARATE ENTITY) |

## 9.3 Statusi **konkursa** (odvojeno od prijave)

Osnovni pohranjeni statusi konkursa (domain review): `draft`, `published`, `completed`.

`closed` tretira se kao **DERIVED / OPTIONAL** tehničko pitanje za budući KN-TS — **nije** nova PO odluka u okviru PO 5.

Prijava i konkurs ostaju odvojeni nivoi.

## 9.4 BM granica

BM PO DECISION 3 zabranjuje tihi zatvoreni katalog bez PO. Ovaj §9 uvodi **samo** katalog usvojen **FS PO DECISION 5 — OPTION B** (sa **PO CORRECTION**: `evaluated` = STORED). Tabela faza u KN-BM-001 §26 **nije** runtime katalog prijave.

---

# 10. Autorizacija sa poslovnog stanovišta

**Status:** USVOJENO

Bez Laravel middleware / permission stringova. Tehničko mapiranje postojećeg `konkurs_admin` ostaje KN-TS-001.

| Ko | Vidi | Unosi / mijenja | Ocjenjuje | Rezultati |
|----|------|-----------------|-----------|-----------|
| Podnosilac (preduzetnik / društvo) | sopstvenu prijavu i priloge; obavještenje o nepotpunosti; kanal prigovora | **Prije konačne predaje:** P1a ili P1b, P2, prilozi, tehnički međuspremnik, izmjena sadržaja, konačna predaja. **Poslije konačne predaje:** nema izmjene P1/P2/priloga ni brisanja predane prijave. Prigovor u roku. | ne | Objavljena Odluka na digitalnom servisu (čl.25). Pregled tuđih prijava nije u BM. Vidljivost preliminarne/konačne rang liste **podnosiocu** nije odlučena FS PO DECISION 4. |
| Član 1, Član 2 | prijave za rad Komisije; **DURING SCORING:** samo sopstvene individualne ocjene (KN-FR-027) | oznaka potpunosti (Komisija); odluka o prigovoru; P3 bodovi | da, svih 10 kriterijuma, 1–5 | **POST-SCORING GATE (FS PO DECISION 4 — OPTION D):** tuđe individualne ocjene + agregati tek kad je bodovanje završeno za cijeli konkurs i rang-lista formirana (KN-FR-035) |
| Predsjednik Komisije | isto scoring visibility kao član: **DURING SCORING** samo sopstvene ocjene; **nema** dodatnog uvida u tuđe individualne ocjene tokom bodovanja | kao član + zaključci/obrazloženja odbijenih; zatvaranje poziva; Komisija generiše predlog Odluke (KN-FR-037, KN-BR-051) | da, svih 10 kriterijuma, 1–5 | isti POST-SCORING GATE kao članovi (KN-FR-035); uloga predsjednika na listi (zaključci) **nije** ekstra scoring-visibility privilegija tokom bodovanja |
| Sekretarijat (poslovni/pravni akter) | predlog Odluke | pravna nadležnost objave Odluke ostaje Sekretarijatu; nije ukinuta FS PO DECISION 1 | ne | Objavljena Odluka |
| Administrator konkursa (`konkurs_admin`) (aplikacioni akter) | administrativni uvid potreban za administratorske radnje V1 | unos i objava Javnog poziva; izvršenje administrativnih radnji koje ovaj FS dodjeljuje administratorskoj strani, uključujući digitalnu objavu Odluke kao izvršenje, ne kao zamjena Sekretarijata | **ne** — nije član Komisije; **nije** scoring actor; **ne** dobija pravo tuđih scoring podataka po osnovu admin uloge | Nije ocjenjivač |
| Član Komisije kao podnosilac | učešće zabranjeno (lično ili društvo čiji je predstavnik) | ne smije konkurisati | — | KN-BR-014 |

Izmjena tuđe prijave od strane Komisije nije propisana. Javnost rada Komisije (KN-BR-048) **nije** izjednačena sa javnim uvidom u bodove. Admin reopen / unlock / vraćanje podnosiocu na izmjenu **nije** u V1 (**FS PO DECISION 2**).

---

# 11. Ivice slučajeva (edge cases)

**Status:** USVOJENO — BM §28; usvojene FS PO odluke gdje su već evidentirane

BM §28, sa V1 oznakom; usvojene FS PO odluke navedene su gdje već postoje u ovoj tabeli:

| Edge case | V1 ponašanje | KN-BR |
|-----------|--------------|-------|
| Nepotpuna prijava | Komisija utvrđuje nakon zaprimanja; ne razmatra se dalje; prigovor. Nije automatska zabrana zaprimanja | KN-BR-020, KN-BR-042; FS PO DECISION 2 |
| Prigovor | Odluka u 7 dana; prihvaćen ili odbijen | KN-BR-043 |
| Član Komisije / njegovo društvo | Nema pravo učešća | KN-BR-014 |
| Plan ispod 30 | Ne podržava se; prag na stvarno izračunatoj konačnoj ocjeni | KN-BR-029; FS PO DECISION 3 |
| Nedovoljna sredstva | Podrška do utroška po listi | KN-BR-028 |
| Manje od traženog | Konačna lista: potrebna vs odobrena | KN-BR-040, KN-BR-025 … 027 |
| Nema kvoruma | Sjednica se odlaže | KN-BR-045 |
| Prestanak mandata člana Komisije | Novi član u roku 15 dana; mandat novog člana traje do isteka mandata Komisije; razriješeni se ne imenuje ponovo. Imenovanje/zamjena nije V1 digitalni kanal | KN-BR-068 |
| Društvo registrovano tekuće godine | Izuzetak od kompleta godišnjih računa | KN-BM §13.5 |
| Nema analitike kupaca | Periodični izvještaj registra kase | KN-BM §13.5 |
| Odustanak / tužba / preusmjeravanje / povraćaj | OUTSIDE CURRENT V1 | KN-BR-053 … 055, 061 … 065 |
| Pokušaj izmjene ili brisanja nakon konačne predaje | Nije dozvoljeno; nema reopen/unlock | FS PO DECISION 2; KN-FR-041 |

---

# 12. Sljedivost prema Business Modelu

```text
DK-DS-001 / METHODOLOGY
        ↓
KN-RG-001
        ↓
KN-PRO-001
        ↓
KN-BM-001 v0.2.8 USVOJENO
        ↓
KN-FS-001 v0.2.12 USVOJENO
        ↓
KN-TS-001 = PENDING
```

| KN-BM | KN-FS | KN-TS |
|-------|-------|-------|
| KN-BR-001 … KN-BR-080 | KN-FR-001 … KN-FR-042 i/ili kategorija A/B/C u §15 | PENDING |
| BM PO DECISION 1 (V1 obuhvat) | §2, §3 | PENDING |
| FS PO DECISION 1 (aplikacioni administrator) | §5, KN-FR-005, KN-FR-039, §10, §14 | USVOJENO u FS; TS = PENDING |
| FS PO DECISION 2 (prijava prije/poslije predaje; jedna konačno predana prijava) | §6.2, KN-FR-011, KN-FR-040 … KN-FR-042, KN-FR-019, §8, §9, §10 | USVOJENO u FS; TS = PENDING |
| FS PO DECISION 3 (pojedinačne ocjene 1–5; formula; konačni skor prikaz na 2 decimale) | §6.5, KN-FR-026, KN-FR-028, KN-FR-029, §8, §14 | USVOJENO u FS — OPCIJA A; KN-PATCH-FS-001; TS = PENDING |
| FS PO DECISION 4 (scoring visibility među članovima Komisije) | §6.5, KN-FR-027, KN-FR-035, §10, §13, §14 | USVOJENO u FS — OPTION D; KN-PATCH-FS-002; TS = PENDING |
| FS PO DECISION 5 (kanonski statusi prijave V1) | §6.2, KN-FR-011, KN-FR-031, KN-FR-040, KN-FR-041, KN-FR-042, §9, §13, §14 | USVOJENO u FS — OPTION B, PO CORRECTION (`evaluated` STORED); KN-PATCH-FS-004; TS = PENDING |
| FS PO DECISION 6 (kanonski UI labeli statusa prijave) | §6.2, KN-FR-040, §9, §13, §14 | USVOJENO u FS — OPTION A (`submitted` → Podnesena); KN-PATCH-FS-005; TS = PENDING |
| BM PO DECISION 2, 4 | §5, §6.5 | PENDING |
| BM PO DECISION 3 | §9 (granica; katalog usvojen FS PO DECISION 5) | PENDING |
| BM PO DECISION 5 | nije V1 funkcija; dokumentacioni RG | — |
| BM PO DECISION 7 | §1, terminologija (kontekstualno: Javni poziv / Javni konkurs ostaje); status UI labeli = FS PO DECISION 6 | kontekstualni termini zadržani; status UI = USVOJENO u FS — OPTION A |
| BM granica izvora (bivša BM stavka 6 — naknada/Poslovnik) | nema V1 funkcije naknade/Poslovnika | — |

---

# 13. Prihvatni kriterijumi V1

**Status:** USVOJENO — prihvatni kriterijumi usvojenog FS-a; ne za produkcijski go-live.

1. Javni poziv se može objaviti na digitalnom servisu sa sadržajem KN-BR-006. Unos i objavu izvršava postojeći Administrator konkursa (`konkurs_admin`); nova uloga se ne kreira.
2. Prijava u 20 dana: P1a ili P1b + P2 + PDF prilozi čl.14. Status `draft` (STORED) prije konačne predaje. Konačna predaja = prelaz `draft` → `submitted` (posebna svjesna radnja; evidentira se trenutak). Poslije `submitted` sadržaj je zaključan. Nepotpunost dokumentacije utvrđuje Komisija nakon zaprimanja, ne kao automatska zabrana zaprimanja.
3. Prigovor putem digitalnog servisa; obavještenje registrovanim mailom; rokovi 3 i 7 dana.
4. Tačno 3 člana (Predsjednik, Član 1, Član 2) boduju svih 10 kriterijuma pojedinačnim ocjenama 1–5. **DURING SCORING:** Member A ne vidi individualne ocjene Member B/C; Predsjednik nema širi scoring visibility (FS PO DECISION 4 — OPTION D; KN-FR-027).
5. Pojedinačne ocjene su cijeli brojevi 1–5; nema 0 / prazne / neocijenjenog / decimalne pojedinačne ocjene. Prosjek i konačna CALCULATION VALUE računaju se postojećom formulom. **Konačni/ukupni skor prikazuje se na dvije decimale.** Prag na stvarno izračunatoj konačnoj ocjeni.
6. Prag ispod 30 na stvarno izračunatoj konačnoj ocjeni; preliminarna lista bez iznosa; konačna sa iznosima i potpisima.
7. **SCORING VISIBILITY GATE (FS PO DECISION 4 — OPTION D):** Ako je Member A završio sopstvene ocjene, ali bodovanje konkursa još traje — Member A i dalje **ne** vidi individualne ocjene B/C. Tek nakon što je kompletno bodovanje završeno za cijeli konkurs **i** rang-lista formirana, članovima Komisije postaju dostupne tuđe individualne ocjene i agregati (KN-FR-035). `konkurs_admin` nije scoring actor.
8. Predlog Odluke i arhiva; objava Odluke na digitalnom servisu.
9. Kanonski statusi prijave V1 (**FS PO DECISION 5 — OPTION B, PO CORRECTION**): `draft`, `submitted`, `evaluated`, `approved`, `rejected` (svi **STORED**). Nema punog procesnog state machine-a za admin review / prigovor / intervju / bodovanje u toku / rangiranje kao statuse prijave. Kanonski UI labeli (**FS PO DECISION 6 — OPTION A**): `draft`→Nacrt; `submitted`→**Podnesena**; `evaluated`→Ocijenjena; `approved`→Odobrena; `rejected`→Odbijena. **„U obradi“ nije** label za `submitted`. Kontekstualni termini (Javni poziv / Javni konkurs) ostaju po BM PO DECISION 7.
10. Ugovor, P4/P4a, isplata, preusmjeravanje nisu u V1.

---

# 14. PO DECISION REQUIRED

**Status:** USVOJENO

Oznake stavki su redni brojevi ovog FS registra. **Nisu** Document ID. Preporuka **nije** odluka.

**Otvoreno:** 0 stavki.

**Zatvoreno:** stavka 1 — **FS PO DECISION 1**; stavka 2 — **FS PO DECISION 2**; stavka 3 — **FS PO DECISION 3**; stavka 4 — **FS PO DECISION 4**; stavka 5 — **FS PO DECISION 5** (sa **PO CORRECTION**); stavka 6 — **FS PO DECISION 6** (**OPTION A**). Nisu isto što i BM PO DECISION 1 / 2 / 3 / 4 / 5.

KN-BM-001 v0.2.8 nema otvorenih BM stavki. Ove stavke su **funkcionalne** nejednoznačnosti pri derivaciji V1.

---

## 14.1 RESOLVED / USVOJENO

### 1. Akter digitalne objave Javnog poziva — RESOLVED / USVOJENO

* **Izvor:** KN-BR-003 (Komisija raspisuje); KN-BR-007 (Objava: Opština; Komisija); KN-BM §6 V1 = objava na digitalnom servisu.
* **Odluka (FS PO DECISION 1):** Postojeći Konkurs administrator izvršava aplikacioni unos i objavu Javnog poziva u KN modulu.
* **Aplikacioni identifier:** postojeći `roles.name = konkurs_admin`; prikaz `Administrator konkursa`. Nova uloga se **ne** kreira. Uloga je platformski predviđena za postojeći modul ženskog preduzetništva, ovaj KN konkurs i buduće module konkursa. Poslovna pravila ženskog preduzetništva **nisu** SSOT za KN.
* **Pravna nadležnost:** Sekretarijata ostaje **nepromijenjena**. `konkurs_admin` ne postaje član Komisije. Komisija ostaje Predsjednik + Član 1 + Član 2.
* **Obuhvat uloge u ovom FS:** samo administrativne radnje koje KN-FS već dodjeljuje administratorskoj strani digitalnog servisa. Permission stringovi, middleware, Laravel role konfiguracija i zaštita ruta = KN-TS-001.
* **Status:** **RESOLVED / USVOJENO**. Uklonjena iz otvorenih PO DECISION REQUIRED.

---

### 2. Ponašanje prijave prije i poslije predaje — RESOLVED / USVOJENO

* **Izvor:** KN-BR-009; KN-BM §13; KN-FR-011, KN-FR-040 … KN-FR-042.
* **Odluka (FS PO DECISION 2):**
  1. **Prije konačne predaje:** podnosilac unosi P1a ili P1b, P2 i priloge; smije sačuvati tehnički nacrt/međuspremnik, vratiti se, nastaviti i mijenjati sadržaj do konačne predaje. Tehnički nacrt je kanonski status **`draft`** (**FS PO DECISION 5 — OPTION B**; STORED). Tehnički nacrt prije konačne predaje ne predstavlja konačno podnošenje. Brisanje tehničkog nacrta prije predaje ne predstavlja korišćenje prava na podnošenje.
  2. **Konačna predaja:** posebna svjesna radnja; sistem evidentira trenutak predaje; prijava je tada elektronski podnesena (kanonski status **`submitted`** — **FS PO DECISION 5**; UI label = **Podnesena** — **FS PO DECISION 6 — OPTION A**). Tehnički uslovi predaje ≠ poslovna provjera potpunosti Komisije. Nedostatak dokumenta ne onemogućava automatski zaprimanje. Ne uvode se submit validacije kojih nema u KN-BM/Odluci.
  3. **Poslije konačne predaje:** nema izmjene P1/P2/priloga; nema brisanja predane prijave; nema admin reopen/unlock/vraćanja na izmjenu bez buduće PO odluke. Predati sadržaj pregleda Komisija. Konačno predata prijava ne može se obrisati radi ponovnog apliciranja.
  4. **Jedna konačno predana prijava:** registrovani korisnik smije konačno podnijeti samo jednu prijavu na isti Javni poziv/konkurs (User + Competition). Nakon konačne predaje druga prijava istog korisnika za isti konkurs nije dozvoljena. Ista osoba smije predati na različite konkurse. Različiti registrovani korisnici smiju predati na isti konkurs. KN-BM/Odluka ovo **ne** propisuju; pravilo je eksplicitna PO odluka u okviru ove stavke. Tehnički mehanizam (index, controller, migracija, race-condition) = KN-TS-001; ovaj FS ga **ne** određuje.
* **Legacy:** save-then-submit i evidentiranje trenutka smiju se tehnički reuse-ovati. Ne preuzimaju se: izmjena predane prijave; izmjena priloga nakon predaje; hard delete predane prijave. Kanonski katalog statusa prijave određuje **FS PO DECISION 5**, ne legacy modul.
* **Status:** **RESOLVED / USVOJENO**. Uklonjena iz otvorenih PO DECISION REQUIRED.

---

### 3. Prikaz prosjeka/konačne ocjene — RESOLVED / USVOJENO — OPCIJA A

* **Izvor:** KN-BR-036, KN-BR-037; KN-BM PO DECISION 4; Odluka / KN-PRO čl.21; KN-FR-026, KN-FR-028, KN-FR-029; KN-BM-001 v0.2.8 / `KN-PATCH-BM-001`.
* **Odluka (FS PO DECISION 3 — OPCIJA A; potvrđeno / usklađeno `KN-PATCH-FS-001`):**
  1. **Pojedinačne ocjene (INPUT):** svaki član Komisije (Predsjednik, Član 1, Član 2) ocjenjuje svih 10 pozitivnih kriterijuma. Svaka pojedinačna ocjena je isključivo cijeli broj 1, 2, 3, 4 ili 5. Nema 0, prazne ocjene, neocijenjenog kriterijuma ni decimalne pojedinačne ocjene.
  2. **Prosjek po kriterijumu (CALCULATION VALUE):** zbir ocjena sva 3 člana / 3, prema Odluci. Primjer: 4 + 3 + 3 = 10; 10 / 3 = 3,333… Izračunati prosjek smije imati decimale koje proizlaze iz formule. Formula se ne mijenja.
  3. **Konačna ocjena (CALCULATION VALUE):** zbir prosječnih ocjena svih 10 kriterijuma, prema Odluci / KN-BM-001. Smije imati decimale koje proizlaze iz formule. Prag „ispod 30“ i rangiranje primjenjuju se na **stvarno izračunatu** konačnu ocjenu (KN-FR-029).
  4. **DISPLAY:** **Konačni/ukupni skor prikazuje se na dvije decimale.**
  5. **Tumačenje „bez decimala“:** odnosi se samo na **pojedinačne ocjene članova Komisije 1–5**. Ne odnosi se na CALCULATION VALUE prosjeka ni konačne ocjene, niti na zabranu DISPLAY-a na dvije decimale.
  6. Ne uvodi se nova poslovna metoda zaokruživanja (rounding/floor/ceil/truncation) koja mijenja CALCULATION VALUE, prag ili rangiranje. Tehnički mehanizam prikaza = PENDING KN-TS.
* **Status:** **RESOLVED / USVOJENO — OPCIJA A** (usklađeno `KN-PATCH-FS-001`). Uklonjena iz otvorenih PO DECISION REQUIRED.

---

### 4. Vidljivost ocjena članova Komisije (scoring visibility) — RESOLVED / USVOJENO — OPTION D

* **Izvor:** KN-BR-035; KN-BR-038, KN-BR-040; Odluka / KN-PRO čl.21; KN-FR-027, KN-FR-035.
* **Odluka (FS PO DECISION 4 — OPTION D; `KN-PATCH-FS-002`):**
  1. **DURING SCORING:** tokom cijelog procesa bodovanja svaki član Komisije (Predsjednik, Član 1, Član 2) ima uvid **isključivo u sopstvene** individualne ocjene.
  2. **POST-SCORING GATE:** individualne ocjene drugih članova i agregatni rezultati postaju vidljivi članovima Komisije **tek** kada je bodovanje završeno za **cijeli konkurs** i rang-lista formirana.
  3. Nije dovoljno da je samo jedan član završio sopstveni unos.
  4. Predsjednik **nema** posebno pravo da tokom bodovanja vidi individualne ocjene ostalih članova.
  5. `konkurs_admin` nije član Komisije i **ne** dobija scoring-visibility pravo po osnovu admin uloge.
  6. Isto pravilo za žensko preduzetništvo i preduzetništvo mladih (jedan KN funkcionalni model). Legacy women ponašanje je tehnička potvrda kompatibilnosti, ne pravni izvor.
  7. **Van obuhvata ove odluke:** da li podnosilac vidi preliminarnu/konačnu rang-listu; javna objava liste; kanal/trenutak objave podnosiocima — ostaje neodlučeno ovom odlukom.
* **Status:** **RESOLVED / USVOJENO — OPTION D**. Uklonjena iz otvorenih PO DECISION REQUIRED.

---

### 5. Imenovani runtime statusi za V1 — RESOLVED WITH PO CORRECTION — OPTION B — BALANCED

* **Izvor:** BM PO DECISION 3; KN-BM §16, §26; ovaj FS §9; KN-FR-011, KN-FR-031, KN-FR-040 … KN-FR-042.
* **Odluka (FS PO DECISION 5 — OPTION B — BALANCED; `KN-PATCH-FS-003`; **PO CORRECTION** `KN-PATCH-FS-004`):**
  1. **Kanonski V1 katalog statusa prijave** (isti za žensko preduzetništvo i preduzetništvo mladih): `draft`, `submitted`, `evaluated`, `approved`, `rejected`.
  2. **`draft`:** STORED tehnički pre-final; editable/deletable po **FS PO DECISION 2**; **ne** zauzima User+Competition=1.
  3. **`submitted`:** STORED; konačna predaja eksplicitnom radnjom; kanonska backend vrijednost = `submitted`; nakon toga podnosilac nema edit/delete/reopen (**FS PO DECISION 2**). UI label za `submitted` = **Podnesena** (**FS PO DECISION 6 — OPTION A**; **„U obradi“ nije** label za ovaj status).
  4. **`evaluated`:** STORED kanonski status prijave (**PO CORRECTION** — zadržan iz dokazanog women lifecycle; isti model za mlade). Znači da je faza bodovanja/evaluacije završena po funkcionalnim pravilima. Podaci o završetku bodovanja smiju usloviti prelaz, ali rezultat prelaza je **pohranjeni** status `evaluated` (nije samo DERIVED).
  5. **`approved` / `rejected`:** STORED ishodi kroz konačni / odgovarajući proces; prelazi `evaluated` → `approved` | `evaluated` → `rejected`. Odbijanje prije završene evaluacije: `submitted` → `rejected`.
  6. **Minimalni prelazi:** `draft` → `submitted`; `submitted` → `evaluated` (kad je evaluacija završena i nije ranije odbijena); `submitted` → `rejected`; `evaluated` → `approved`; `evaluated` → `rejected`. Bez nove PO: nema `submitted`/`evaluated`/`approved`/`rejected` → `draft`. **FS PO DECISION 2** nepromijenjena.
  7. **Nisu** kanonski statusi prijave: admin review; docs complete/incomplete; complaint/prigovor; interview; scoring in progress / scoring complete; preliminary/final ranking; Decision formed / Decision publication; contracted; reporting — klasifikuju se kao DERIVED / PROCESS EVENT / FLAG / SEPARATE ENTITY / COMPETITION-LEVEL (**OPTION B** klasifikacija nepromijenjena).
  8. **Konkurs vs prijava:** odvojeni nivoi. Osnovni pohranjeni statusi konkursa: `draft`, `published`, `completed`. `closed` = DERIVED/OPTIONAL (tehničko pitanje za budući TS; nije proširenje PO 5).
  9. **Domain lock:** samo žensko + mladi; ne inventarišu se statusi za druge konkurse.
* **Status:** **RESOLVED WITH PO CORRECTION — OPTION B — BALANCED** (`evaluated` = STORED). Uklonjena iz otvorenih PO DECISION REQUIRED.

---

### 6. Kanonski UI labeli statusa prijave — RESOLVED / USVOJENO — OPTION A

* **Izvor:** BM PO DECISION 7; KN-BM §33; ovaj FS §9; KN-FR-040.
* **Odluka (FS PO DECISION 6 — OPTION A; `KN-PATCH-FS-005`):**
  1. **Kanonski UI labeli** za V1 statuse **prijave** (backend vrijednosti nepromijenjene; UI-only):
     * `draft` → **Nacrt**
     * `submitted` → **Podnesena**
     * `evaluated` → **Ocijenjena**
     * `approved` → **Odobrena**
     * `rejected` → **Odbijena**
  2. **Eksplicitno:** **„U obradi“ nije** kanonski UI label za status `submitted`. Backend ostaje `submitted`.
  3. **WOMEN LABEL MODEL = SAME; YOUTH LABEL MODEL = SAME.** Ako youth UI nije implementiran, koristiće iste kanonske UI labele.
  4. **NEW BUSINESS RULE = NO.** Odluka ne mijenja KN-BM-001 niti uvodi novo poslovno pravilo.
  5. **Van obuhvata / ostaje kontekstualno:** Javni poziv vs Javni konkurs **ne** rješava se jednim univerzalnim labelom; **prijava ≠ zahtjev**; ostale BM terminološke varijacije ostaju kontekstualne (BM PO DECISION 7).
* **Status:** **RESOLVED / USVOJENO — OPTION A**. Uklonjena iz otvorenih PO DECISION REQUIRED.

---

## 14.2 Otvorena pitanja (PENDING PO)

Nema otvorenih FS PO DECISION stavki. Sve stavke 1–6 su **RESOLVED / USVOJENO**.

---

# 15. KN-BR coverage 001–080

Kategorije: **A** = V1 FUNCTIONAL REQUIREMENT; **B** = BUSINESS RULE — NO DIRECT V1 FUNCTION; **C** = OUTSIDE CURRENT V1.

| KN-BR | Kat. | KN-FS |
|-------|------|-------|
| KN-BR-001 | B | domen; nema ekrana |
| KN-BR-002 | B | iznos budžeta ulazi u sadržaj poziva (KN-FR-002), ne kao apsolut u kodu |
| KN-BR-003 | A | KN-FR-001, KN-FR-005 |
| KN-BR-004 | A | KN-FR-004 |
| KN-BR-005 | A | KN-FR-004 |
| KN-BR-006 | A | KN-FR-002 |
| KN-BR-007 | A | digitalni kanal KN-FR-001; ostali kanali C |
| KN-BR-008 | A | KN-FR-003 |
| KN-BR-009 | A | KN-FR-011, KN-FR-040, KN-FR-041; FS PO DECISION 5 |
| KN-BR-010 | A | KN-FR-034 |
| KN-BR-011 | A | KN-FR-034 |
| KN-BR-012 | A | KN-FR-034 |
| KN-BR-013 | A | KN-FR-034 |
| KN-BR-014 | A | KN-FR-034; §10 |
| KN-BR-015 | A | KN-FR-034 |
| KN-BR-016 | A | KN-FR-034; termin Javni konkurs |
| KN-BR-017 | A | KN-FR-012 |
| KN-BR-018 | C | nakon odobrenja; nije checklista prijave |
| KN-BR-019 | C | ugovor |
| KN-BR-020 | A | KN-FR-019; zaprimanje KN-FR-040 |
| KN-BR-021 | A | KN-FR-033 |
| KN-BR-022 | A | KN-FR-033 |
| KN-BR-023 | A | KN-FR-010 |
| KN-BR-024 | A | KN-FR-010 |
| KN-BR-025 | A | KN-FR-032 |
| KN-BR-026 | A | KN-FR-032 |
| KN-BR-027 | A | KN-FR-032 |
| KN-BR-028 | A | KN-FR-032 |
| KN-BR-029 | A | KN-FR-029 |
| KN-BR-030 | A | KN-FR-026 |
| KN-BR-031 | A | KN-FR-025 |
| KN-BR-032 | A | KN-FR-025; naziv kriterijuma 9 (KN-BM §17.1 / P3) |
| KN-BR-033 | A | KN-FR-033 |
| KN-BR-034 | A | KN-FR-024, KN-FR-025 |
| KN-BR-035 | A | KN-FR-027 |
| KN-BR-036 | A | KN-FR-028 |
| KN-BR-037 | A | KN-FR-028 |
| KN-BR-038 | A | KN-FR-030 |
| KN-BR-039 | A | KN-FR-031; FS PO DECISION 5 |
| KN-BR-040 | A | KN-FR-031; FS PO DECISION 5 |
| KN-BR-041 | A | KN-FR-018 |
| KN-BR-042 | A | KN-FR-020, KN-FR-021 |
| KN-BR-043 | A | KN-FR-022 |
| KN-BR-044 | A | KN-FR-023 |
| KN-BR-045 | A | KN-FR-036 |
| KN-BR-046 | A | KN-FR-023, KN-FR-036 |
| KN-BR-047 | B | dozvoljen kanal; nema V1 integracije |
| KN-BR-048 | B | javnost rada; nije javni uvid u bodove |
| KN-BR-049 | B | izjave pri imenovanju; nije V1 kanal |
| KN-BR-050 | A | KN-FR-024 |
| KN-BR-051 | A | KN-FR-037, KN-FR-039 |
| KN-BR-052 | A | KN-FR-038 |
| KN-BR-053 | C | odustanak nakon Odluke |
| KN-BR-054 | C | rješenja |
| KN-BR-055 | C | tužba |
| KN-BR-056 | A | digitalni kanal KN-FR-039; ostali kanali C |
| KN-BR-057 | C | ugovor |
| KN-BR-058 | C | sadržaj ugovora |
| KN-BR-059 | C | isplata |
| KN-BR-060 | C | nadzor |
| KN-BR-061 | C | preusmjeravanje |
| KN-BR-062 | C | ćutanje 3 dana |
| KN-BR-063 | C | P4/P4a |
| KN-BR-064 | C | povraćaj izvještaj |
| KN-BR-065 | C | povraćaj gašenje |
| KN-BR-066 | C | promocija |
| KN-BR-067 | C | izvještaj Skupštini |
| KN-BR-068 | B | imenovanje zamjene; nije V1 kanal |
| KN-BR-069 | B | pravo na naknadu; iznos nije u BM |
| KN-BR-070 | B | Poslovnik; sadržaj nije u BM |
| KN-BR-071 | A | BUSINESS VALIDATION u P2 |
| KN-BR-072 | B | post-nabavka; nije V1 ekran |
| KN-BR-073 | B | trošak prije ugovora; ugovor van V1 |
| KN-BR-074 | B | način plaćanja pri realizaciji |
| KN-BR-075 | B | sopstvene usluge pri realizaciji |
| KN-BR-076 | A | BUSINESS VALIDATION u P2 |
| KN-BR-077 | A | KN-FR-009 |
| KN-BR-078 | A | KN-FR-016 |
| KN-BR-079 | A | KN-FR-015 |
| KN-BR-080 | A | KN-FR-006 … KN-FR-009 |

**KN-BR COVERAGE = 80/80.**

---

**Kraj dokumenta KN-FS-001 v0.2.12**
