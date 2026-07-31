# Digital Kotor
# Functional Specification
## Funkcionalnost: Obavještenja

**Feature ID:** FT-004  
**Status dokumenta:** U IZRADI  
**Verzija:** 0.1  
**Datum:** 2026-07-31

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-31 | Početna Functional Specification za FT-004 Obavještenja. Funkcionalni zahtjevi FR-OB-001 do FR-OB-017 izvedeni isključivo iz usvojenog Business Modela i usvojenih Use Case-ova UC-OB-001 do UC-OB-005. |
| PATCH-FS-OB-001 | 2026-07-31 | Editorial consistency improvements — ispravka formulacije svrhe dokumenta; preciziranje observable result / acceptance criteria (FR-OB-005, FR-OB-010, FR-OB-013); tipografska ispravka naslova FR-OB-015. Bez izmjene semantike zahtjeva. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

Svaki PATCH dobija:

- jedinstvenu oznaku (PATCH-FS-OB-001, PATCH-FS-OB-002...),
- datum,
- kratak naziv,
- kratak opis izmjene.

---

## Svrha dokumenta

Dokument odgovara na pitanje:

> Koje **posmatrano ponašanje** Digital Kotor mora obezbijediti za funkcionalnost Obavještenja?

Dokument **ne** odgovara kako se to ponašanje tehnički implementira.

Functional Specification je usklađena sa:

* Business Model: `docs/business-model/Business_Model_Obavjestenja.md`
* Use Case Specification: `docs/use-cases/Use_Cases_Obavjestenja.md`

Samo usvojena BM pravila i usvojeni Use Case-ovi smiju postati usvojeni funkcionalni zahtjevi.

Kandidati i otvorena pitanja **ne** smiju se tiho pretvoriti u zahtjeve.

---

# Status razvoja Functional Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Uvod i svrha | USVOJENO |
| 2. Obuhvat | USVOJENO |
| 3. Granice | USVOJENO |
| 4. Aktori i odgovornosti | USVOJENO |
| 5. Posmatrane funkcionalne razlike (vidljivost) | USVOJENO |
| 6. Funkcionalni tokovi | USVOJENO |
| 7. Funkcionalni zahtjevi (FR-OB-001 do FR-OB-017) | USVOJENO (u okviru usvojenog BM/UC) |
| 8. Funkcionalna odstupanja (neusklađenosti) | USVOJENO |
| 9. Van opsega (tehnički i nefunkcionalni) | USVOJENO |
| 10. Otvorene funkcionalne odluke (OFD) | OTVORENO |
| 11. Matrice sljedivosti | USVOJENO |

Ukupan status dokumenta: **U IZRADI** (zbog neriješenih Product Owner odluka).

---

# Pravila upravljanja Functional Specification

1. Functional Specification predstavlja zvaničnu funkcionalnu specifikaciju funkcionalnosti Obavještenja (FT-004).

2. Poglavlja i zahtjevi sa statusom USVOJENO mijenjaju se isključivo kroz PATCH koji predstavlja novu usvojenu odluku.

3. Functional Specification ostaje usklađena sa Business Modelom. Ne dokumentuje privremena tehnička ograničenja trenutne implementacije.

4. Cursor ne smije samostalno prepisivati, preformulisati ili reorganizovati usvojeni sadržaj bez PATCH-a.

5. Otvorene funkcionalne odluke (OFD) nisu usvojeni zahtjevi.

---

# Upravljanje promjenama

Svaka izmjena Functional Specification mora biti rezultat usvojene odluke i evidentirana kroz odgovarajući PATCH.

---

## Sadržaj

1. Uvod i svrha  
2. Obuhvat  
3. Granice  
4. Aktori i odgovornosti  
5. Posmatrane funkcionalne razlike (vidljivost)  
6. Funkcionalni tokovi  
7. Funkcionalni zahtjevi  
8. Funkcionalna odstupanja (neusklađenosti)  
9. Van opsega  
10. Otvorene funkcionalne odluke  
11. Matrice sljedivosti  

---

# 1. Uvod i svrha

**Status:** USVOJENO

Obavještenja su unakrsna platformska funkcionalnost Digital Kotor koja javnosti čini vidljivim zvanični sadržaj nastao u drugim funkcionalnostima platforme.

Obavještenje **nije** sama odluka, dokument, inbox poruka ni notifikacija sa stanjem pročitano/nepročitano.

Ova specifikacija razdvaja:

1. ponašanje funkcionalnosti **Obavještenja** (javna prezentacija i kanal objave);
2. obaveze **izvorne funkcionalnosti** (proces, sadržaj, utvrđivanje stanja za objavu);
3. posmatrano ponašanje **javne prezentacione oblasti** (panel na početnoj stranici).

---

# 2. Obuhvat

**Status:** USVOJENO

U obuhvatu su posmatrana ponašanja koja podržavaju usvojene Use Case-ove:

| Use Case | Naziv |
|----------|-------|
| UC-OB-001 | Pregled panela Obavještenja |
| UC-OB-002 | Pristup zvaničnom sadržaju putem Obavještenja |
| UC-OB-003 | Utvrđivanje da je zvanični sadržaj spreman ili obavezan za javnu objavu |
| UC-OB-004 | Nastajanje i javna vidljivost Obavještenja |
| UC-OB-005 | Zamjena Obavještenja u aktivnom panelu |

---

# 3. Granice

**Status:** USVOJENO

U usvojenom obuhvatu **nije** dio funkcionalnih zahtjeva:

* dugoročna arhiva ili pregled arhive;
* ispravka ili zamjena već objavljenog zvaničnog sadržaja (pored zamjene vidljivosti u panelu);
* ručna objava opštih opštinskih obavještenja;
* istovremena vidljivost više „odgovarajućih“ Obavještenja;
* izvor opcionog kratkog opisa;
* tačna pravila redoslijeda;
* maksimalan broj vidljivih unosa;
* ponašanje kada noviji postupak ne proizvede zamjenski sadržaj;
* tačno poslovno stanje-okidač za svaku izvornu funkcionalnost;
* praćenje čitanja, potvrda prijema, inbox;
* administratorsko odobrenje / urednički workflow u automatskom scenariju;
* objava putem `kotor.me`;
* obavezno vezivanje zvaničnog sadržaja za jedan format fajla.

---

# 4. Aktori i odgovornosti

**Status:** USVOJENO

| Aktor | Funkcionalna odgovornost |
|-------|--------------------------|
| **Javnost** | Pregleda panel i pristupa zvaničnom sadržaju bez obaveze prijave. |
| **Izvorna funkcionalnost** | Vodi proces, proizvodi zvanični sadržaj i utvrđuje da je sadržaj spreman ili obavezan za javnu objavu. |
| **Platforma Digital Kotor / funkcionalnost Obavještenja** | Prikazuje panel, čini Obavještenje javno vidljivim i omogućava pristup referenciranom sadržaju kroz stabilan javni mehanizam. |

---

# 5. Posmatrane funkcionalne razlike (vidljivost)

**Status:** USVOJENO

Ova specifikacija **ne** definiše potpuni životni ciklus sadržaja.

Definiše samo posmatranu razliku u odnosu na **aktivni panel Obavještenja**:

| Posmatrana razlika | Značenje |
|--------------------|----------|
| **Vidljivo u aktivnom panelu** | Obavještenje je prikazano javnosti u panelu Obavještenja na početnoj stranici. |
| **Nije vidljivo u aktivnom panelu** | Obavještenje nije prikazano u aktivnom panelu. |

Nisu usvojeni formalni statusi tipa: nacrt, na odobrenju, arhivirano, obrisano, isteklo, zakazano.

Zamjena u aktivnom panelu mijenja samo ovu posmatranu vidljivost i **ne** podrazumijeva brisanje, arhiviranje, gubitak javne dostupnosti ni uništavanje zvaničnog sadržaja (BM-OB-13).

Kriterijum „odgovarajuće“ zamjene nije usvojen (OFD-OB-001).

---

# 6. Funkcionalni tokovi

**Status:** USVOJENO

## FS-OB-FLOW-01 — Javni pregled

1. Javnost otvara početnu stranicu Digital Kotor.  
2. Panel Obavještenja je prikazan.  
3. Javno vidljiva Obavještenja su prikazana (ako postoje).  
4. Javnost može pristupiti referenciranom zvaničnom sadržaju.

**Pokriva:** UC-OB-001, UC-OB-002 · **FR:** FR-OB-001 do FR-OB-010, FR-OB-017

## FS-OB-FLOW-02 — Automatska objava

1. Izvorna funkcionalnost utvrdi da je zvanični sadržaj spreman ili obavezan za javnu objavu.  
2. Odgovarajuće Obavještenje nastaje automatski.  
3. Obavještenje postaje javno vidljivo u aktivnom panelu.  
4. Referencirani zvanični sadržaj je javno dostupan kroz stabilan javni mehanizam.

**Pokriva:** UC-OB-003, UC-OB-004 · **FR:** FR-OB-011 do FR-OB-014, FR-OB-005 do FR-OB-010

## FS-OB-FLOW-03 — Zamjena u aktivnom panelu

1. Novo odgovarajuće Obavještenje postaje vidljivo.  
2. Starije odgovarajuće Obavještenje prestaje biti vidljivo u aktivnom panelu.  
3. Zamjena sama po sebi ne briše niti uništava zvanični sadržaj i ne podrazumijeva automatsko arhiviranje ni automatski gubitak javne dostupnosti.

**Pokriva:** UC-OB-005 · **FR:** FR-OB-015, FR-OB-016  
**Napomena:** kriterijum „odgovarajuće“ i ponašanje bez zamjene ostaju OFD.

**Broj funkcionalnih tokova:** 3

---

# 7. Funkcionalni zahtjevi

**Status:** USVOJENO (u okviru usvojenog BM/UC)

---

## FR-OB-001 — Prikaz panela Obavještenja na početnoj stranici

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-001 |
| **Name** | Prikaz panela Obavještenja na početnoj stranici |
| **Requirement** | Sistem mora na početnoj stranici Digital Kotor, ispod panela „Dobrodošli na digitalnu platformu Opštine Kotor“, prikazati panel naslovljen „Obavještenja“. |
| **Rationale** | BM-OB-02 / PO-OB-02 |
| **Preconditions** | Javnost pristupa početnoj stranici. |
| **Trigger** | Otvaranje početne stranice. |
| **Expected observable result** | Panel Obavještenja je vidljiv ispod panela dobrodošlice. |
| **Related Use Case** | UC-OB-001 |
| **Related Business Rule** | BM-OB-02 |
| **Related PO decision** | PO-OB-02 |
| **Acceptance criteria** | Given da javnost otvara početnu stranicu Digital Kotor, When se stranica prikaže, Then je ispod panela dobrodošlice prisutan panel „Obavještenja“. |
| **Status** | USVOJENO |

---

## FR-OB-002 — Pregled bez autentifikacije

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-002 |
| **Name** | Pregled bez autentifikacije |
| **Requirement** | Javnosti mora biti omogućeno da vidi panel Obavještenja i javno vidljiva Obavještenja bez registracije i bez prijave. |
| **Rationale** | BM-OB-03 / PO-OB-03 |
| **Preconditions** | — |
| **Trigger** | Pregled početne stranice / panela. |
| **Expected observable result** | Neautentifikovani posjetilac vidi panel bez zahtjeva za prijavu. |
| **Related Use Case** | UC-OB-001 |
| **Related Business Rule** | BM-OB-03 |
| **Related PO decision** | PO-OB-03 |
| **Acceptance criteria** | Given da posjetilac nije prijavljen, When otvori početnu stranicu, Then vidi panel Obavještenja bez zahtjeva za autentifikaciju. |
| **Status** | USVOJENO |

---

## FR-OB-003 — Ista prava pregleda za prijavljene i neprijavljene

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-003 |
| **Name** | Ista prava pregleda za prijavljene i neprijavljene |
| **Requirement** | Sistem mora obezbijediti ista prava pregleda panela Obavještenja i javno vidljivih Obavještenja autentifikovanim korisnicima i neautentifikovanim posjetiocima. |
| **Rationale** | BM-OB-03 / PO-OB-03 |
| **Preconditions** | — |
| **Trigger** | Pregled panela. |
| **Expected observable result** | Nema razlike u pravu pregleda zbog statusa prijave. |
| **Related Use Case** | UC-OB-001, UC-OB-002 |
| **Related Business Rule** | BM-OB-03 |
| **Related PO decision** | PO-OB-03 |
| **Acceptance criteria** | Given isto javno vidljivo Obavještenje, When ga pregledaju neprijavljeni posjetilac i prijavljeni korisnik, Then oboje mogu vidjeti panel i Obavještenje bez dodatnog uslova prijave za pregled. |
| **Status** | USVOJENO |

---

## FR-OB-004 — Prisustvo panela bez vidljivih unosa

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-004 |
| **Name** | Prisustvo panela bez vidljivih unosa |
| **Requirement** | Sistem mora zadržati panel Obavještenja na početnoj stranici i kada nema javno vidljivih Obavještenja. |
| **Rationale** | UC-OB-001 A1; BM-OB-02 |
| **Preconditions** | Nema unosa vidljivih u aktivnom panelu. |
| **Trigger** | Otvaranje početne stranice. |
| **Expected observable result** | Panel postoji; skup stavki može biti prazan. |
| **Related Use Case** | UC-OB-001 |
| **Related Business Rule** | BM-OB-02 |
| **Related PO decision** | PO-OB-02 |
| **Acceptance criteria** | Given da nema javno vidljivih Obavještenja, When javnost otvori početnu stranicu, Then panel „Obavještenja“ je i dalje prikazan. |
| **Status** | USVOJENO |

---

## FR-OB-005 — Prikaz naslova

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-005 |
| **Name** | Prikaz naslova |
| **Requirement** | Za svako javno vidljivo Obavještenje sistem mora javnosti prikazati naslov. |
| **Rationale** | BM-OB-04 / PO-OB-04 |
| **Preconditions** | Obavještenje je vidljivo u aktivnom panelu. |
| **Trigger** | Pregled panela. |
| **Expected observable result** | Naslov je prikazan javnosti. |
| **Related Use Case** | UC-OB-001 |
| **Related Business Rule** | BM-OB-04 |
| **Related PO decision** | PO-OB-04 |
| **Acceptance criteria** | Given javno vidljivo Obavještenje sa naslovom, When javnost pregleda panel, Then naslov tog Obavještenja je prikazan. |
| **Status** | USVOJENO |

---

## FR-OB-006 — Prikaz reference na zvanični sadržaj

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-006 |
| **Name** | Prikaz reference na zvanični sadržaj |
| **Requirement** | Za svako javno vidljivo Obavještenje sistem mora javnosti izložiti referencu koja omogućava pristup zvaničnom sadržaju. |
| **Rationale** | BM-OB-04 / PO-OB-04 |
| **Preconditions** | Obavještenje je vidljivo u aktivnom panelu. |
| **Trigger** | Pregled panela. |
| **Expected observable result** | Postoji posmatrana referenca / način pristupa zvaničnom sadržaju. |
| **Related Use Case** | UC-OB-001, UC-OB-002 |
| **Related Business Rule** | BM-OB-04 |
| **Related PO decision** | PO-OB-04 |
| **Acceptance criteria** | Given javno vidljivo Obavještenje, When javnost pregleda panel, Then je dostupna referenca kojom se može pokrenuti pristup zvaničnom sadržaju. |
| **Status** | USVOJENO |

---

## FR-OB-007 — Opcioni kratak opis

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-007 |
| **Name** | Opcioni kratak opis |
| **Requirement** | Kada Obavještenje ima kratak opis, sistem mora taj opis prikazati u javnoj prezentaciji. Kada kratak opis ne postoji, sistem mora omogućiti prikaz Obavještenja bez opisa. |
| **Rationale** | BM-OB-04 / PO-OB-04; opis je opcioni |
| **Preconditions** | Obavještenje je vidljivo u aktivnom panelu. |
| **Trigger** | Pregled panela. |
| **Expected observable result** | Opis je prikazan ako postoji; odsustvo opisa ne sprečava prikaz Obavještenja. |
| **Related Use Case** | UC-OB-001, UC-OB-004 |
| **Related Business Rule** | BM-OB-04 |
| **Related PO decision** | PO-OB-04 |
| **Acceptance criteria** | Given Obavještenje sa kratkim opisom, When se prikaže u panelu, Then je opis vidljiv. Given Obavještenje bez kratkog opisa, When se prikaže u panelu, Then su naslov i referenca i dalje prikazani. |
| **Status** | USVOJENO |

**Napomena:** Izvor kratkog opisa nije usvojen (OFD-OB-008).

---

## FR-OB-008 — Pristup zvaničnom sadržaju

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-008 |
| **Name** | Pristup zvaničnom sadržaju |
| **Requirement** | Javnosti mora biti omogućeno da iz Obavještenja pristupi referenciranom zvaničnom sadržaju. |
| **Rationale** | BM-OB-01, BM-OB-03, BM-OB-04 / UC-OB-002 |
| **Preconditions** | Obavještenje je javno vidljivo; referenca postoji. |
| **Trigger** | Javnost slijedi referencu. |
| **Expected observable result** | Zvanični sadržaj je dostupan javnosti. |
| **Related Use Case** | UC-OB-002 |
| **Related Business Rule** | BM-OB-01, BM-OB-03, BM-OB-04 |
| **Related PO decision** | PO-OB-01, PO-OB-03, PO-OB-04 |
| **Acceptance criteria** | Given javno vidljivo Obavještenje, When neprijavljeni posjetilac slijedi referencu, Then pristupa zvaničnom sadržaju bez obaveze prijave. |
| **Status** | USVOJENO |

---

## FR-OB-009 — Dinamički i statički zvanični sadržaj

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-009 |
| **Name** | Dinamički i statički zvanični sadržaj |
| **Requirement** | Funkcionalnost mora tretirati dinamički prikazan zvanični sadržaj i statički dokument kao ravnopravne oblike javnog zvaničnog sadržaja za potrebe pristupa iz Obavještenja. |
| **Rationale** | BM-OB-05 / PO-OB-05 |
| **Preconditions** | Referenca vodi na zvanični sadržaj. |
| **Trigger** | Pristup sadržaju (UC-OB-002). |
| **Expected observable result** | Oba oblika su dozvoljena; zahtjev ne nameće jedan oblik. |
| **Related Use Case** | UC-OB-002 |
| **Related Business Rule** | BM-OB-05 |
| **Related PO decision** | PO-OB-05 |
| **Acceptance criteria** | Given Obavještenje koje referencira dinamički sadržaj ili statički dokument, When javnost pristupi referenci, Then pristup je funkcionalno dozvoljen u oba slučaja kao javni zvanični sadržaj. |
| **Status** | USVOJENO |

---

## FR-OB-010 — Stabilna javna dostupnost zvaničnog sadržaja

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-010 |
| **Name** | Stabilna javna dostupnost zvaničnog sadržaja |
| **Requirement** | Dok je Obavještenje javno vidljivo, referencirani zvanični sadržaj mora biti javno dostupan kroz stabilan javni mehanizam pristupa koji ne zavisi od administrativnog interfejsa. |
| **Rationale** | BM-OB-12 / PO-OB-12 |
| **Preconditions** | Obavještenje je vidljivo u aktivnom panelu. |
| **Trigger** | Trajanje javne vidljivosti Obavještenja / pristup sadržaju. |
| **Expected observable result** | Javnost pristupa sadržaju bez administrativnog interfejsa. |
| **Related Use Case** | UC-OB-002, UC-OB-004 |
| **Related Business Rule** | BM-OB-12, BM-OB-03 |
| **Related PO decision** | PO-OB-12, PO-OB-03 |
| **Acceptance criteria** | Given da je Obavještenje javno vidljivo, When neprijavljeni posjetilac pristupa referenciranom sadržaju, Then sadržaj je dostupan kroz javni mehanizam pristupa bez zahtjeva za korišćenjem administrativnog interfejsa. |
| **Status** | USVOJENO |

---

## FR-OB-011 — Utvrđivanje stanja za objavu od strane izvorne funkcionalnosti

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-011 |
| **Name** | Utvrđivanje stanja za objavu od strane izvorne funkcionalnosti |
| **Requirement** | Izvorna funkcionalnost mora utvrditi kada je njen poslovni proces dostigao stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu. |
| **Rationale** | BM-OB-07 / PO-OB-07; UC-OB-003 |
| **Preconditions** | Postoje poslovni proces i zvanični sadržaj u izvornoj funkcionalnosti. |
| **Trigger** | Poslovni proces dostigne tačku razmatranja javne objave. |
| **Expected observable result** | Izvorna funkcionalnost donosi odluku o spremnosti / obaveznosti objave. |
| **Related Use Case** | UC-OB-003 |
| **Related Business Rule** | BM-OB-06, BM-OB-07, BM-OB-08 |
| **Related PO decision** | PO-OB-06, PO-OB-07, PO-OB-08 |
| **Acceptance criteria** | Given poslovni proces izvorne funkcionalnosti, When se razmatra javna objava zvaničnog sadržaja, Then odluku o tome da li je sadržaj spreman ili obavezan za objavu donosi izvorna funkcionalnost. |
| **Status** | USVOJENO |

**Napomena:** Tačno stanje-okidač po izvornoj funkcionalnosti nije usvojeno (OFD-OB-006).

---

## FR-OB-012 — Obavještenja ne donose odluku o završetku postupka

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-012 |
| **Name** | Obavještenja ne donose odluku o stanju za objavu |
| **Requirement** | Funkcionalnost Obavještenja ne smije samostalno utvrđivati da li je poslovni proces dostigao stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu. |
| **Rationale** | BM-OB-07 / PO-OB-07 |
| **Preconditions** | — |
| **Trigger** | Nastajanje Obavještenja. |
| **Expected observable result** | Objava kroz Obavještenja slijedi odluku izvorne funkcionalnosti. |
| **Related Use Case** | UC-OB-003, UC-OB-004 |
| **Related Business Rule** | BM-OB-07 |
| **Related PO decision** | PO-OB-07 |
| **Acceptance criteria** | Given zvanični sadržaj izvorne funkcionalnosti, When se razmatra nastanak Obavještenja, Then Obavještenja ne zamjenjuju odluku izvorne funkcionalnosti o spremnosti / obaveznosti objave. |
| **Status** | USVOJENO |

---

## FR-OB-013 — Automatsko nastajanje i javna vidljivost

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-013 |
| **Name** | Automatsko nastajanje i javna vidljivost |
| **Requirement** | Kada izvorna funkcionalnost utvrdi da je zvanični sadržaj spreman ili obavezan za javnu objavu, odgovarajuće Obavještenje mora nastati automatski i postati javno vidljivo. |
| **Rationale** | BM-OB-06 / PO-OB-06; UC-OB-004 |
| **Preconditions** | FR-OB-011 ispunjen. |
| **Trigger** | Utvrđena spremnost / obaveznost javne objave. |
| **Expected observable result** | Obavještenje je vidljivo u aktivnom panelu. |
| **Related Use Case** | UC-OB-004 |
| **Related Business Rule** | BM-OB-06, BM-OB-01 |
| **Related PO decision** | PO-OB-06, PO-OB-01 |
| **Acceptance criteria** | Given da je izvorna funkcionalnost utvrdila da je zvanični sadržaj spreman ili obavezan za javnu objavu, When nastupi usvojeni okidač objave, Then odgovarajuće Obavještenje nastaje automatski, postaje javno vidljivo u aktivnom panelu i to bez zasebnog ručnog koraka kreiranja. |
| **Status** | USVOJENO |

---

## FR-OB-014 — Bez odobrenja i ručnog workflow-a u automatskom scenariju

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-014 |
| **Name** | Bez odobrenja i ručnog workflow-a u automatskom scenariju |
| **Requirement** | U usvojenom automatskom scenariju nastajanje i javna vidljivost Obavještenja ne smiju zahtijevati administratorsko odobrenje, uredničku potvrdu, ručno kreiranje Obavještenja niti zaseban inbox workflow objave. |
| **Rationale** | BM-OB-06, BM-OB-11 / PO-OB-06, PO-OB-11 |
| **Preconditions** | Automatski scenario iz FR-OB-013. |
| **Trigger** | Nastajanje Obavještenja. |
| **Expected observable result** | Nema obaveznog koraka odobrenja / ručnog kreiranja. |
| **Related Use Case** | UC-OB-004 |
| **Related Business Rule** | BM-OB-06, BM-OB-11 |
| **Related PO decision** | PO-OB-06, PO-OB-11 |
| **Acceptance criteria** | Given automatski scenario objave, When Obavještenje postane javno vidljivo, Then proces nije uslovljen administratorskim odobrenjem, uredničkom potvrdom, ručnim kreiranjem ni inbox workflow-om objave. |
| **Status** | USVOJENO |

---

## FR-OB-015 — Prestanak vidljivosti starijeg Obavještenja u aktivnom panelu

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-015 |
| **Name** | Prestanak vidljivosti starijeg Obavještenja u aktivnom panelu |
| **Requirement** | Kada novo odgovarajuće Obavještenje zamijeni starije prema usvojenom prezentacionom pravilu, starije Obavještenje mora prestati biti vidljivo u aktivnom panelu. |
| **Rationale** | BM-OB-09, BM-OB-13 / UC-OB-005 |
| **Preconditions** | Postoje starije vidljivo i novo odgovarajuće Obavještenje. |
| **Trigger** | Zamjena u aktivnom panelu. |
| **Expected observable result** | Aktivni panel više ne prikazuje starije Obavještenje. |
| **Related Use Case** | UC-OB-005 |
| **Related Business Rule** | BM-OB-09, BM-OB-13 |
| **Related PO decision** | PO-OB-09, PO-OB-13 |
| **Acceptance criteria** | Given starije Obavještenje vidljivo u aktivnom panelu i Given novo odgovarajuće Obavještenje koje ga zamjenjuje, When se zamjena izvrši, Then starije više nije vidljivo u aktivnom panelu a novo jeste. |
| **Status** | USVOJENO |

**Napomena:** Kriterijum „odgovarajuće“ nije usvojen (OFD-OB-001). Zahtjev važi kada je zamjena usklađena sa usvojenim BM pravilom.

---

## FR-OB-016 — Zamjena ne briše niti uništava zvanični sadržaj

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-016 |
| **Name** | Zamjena ne briše niti uništava zvanični sadržaj |
| **Requirement** | Zamjena Obavještenja u aktivnom panelu ne smije sama po sebi značiti brisanje zvaničnog sadržaja, uništavanje zvaničnog sadržaja, automatsko arhiviranje niti automatski gubitak javne dostupnosti. |
| **Rationale** | BM-OB-13 / PO-OB-13 |
| **Preconditions** | Izvršena zamjena u aktivnom panelu (FR-OB-015). |
| **Trigger** | Zamjena vidljivosti. |
| **Expected observable result** | Zamjena je ograničena na vidljivost u panelu; ne nameće brisanje / uništavanje sadržaja. |
| **Related Use Case** | UC-OB-005 |
| **Related Business Rule** | BM-OB-13 |
| **Related PO decision** | PO-OB-13 |
| **Acceptance criteria** | Given zamjenu starijeg Obavještenja novim u aktivnom panelu, When se posmatra posljedično značenje zamjene, Then zamjena sama po sebi ne predstavlja brisanje, uništavanje, automatsko arhiviranje ni automatski gubitak javne dostupnosti referenciranog zvaničnog sadržaja. |
| **Status** | USVOJENO |

**Napomena:** Dugoročna dostupnost / arhiva ostaju OFD-OB-004; ovaj zahtjev ne usvaja arhivu.

---

## FR-OB-017 — Bez praćenja čitanja i inboxa

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | FR-OB-017 |
| **Name** | Bez praćenja čitanja i inboxa |
| **Requirement** | Pregled panela Obavještenja i pristup zvaničnom sadržaju ne smiju zahtijevati niti kreirati stanje pročitano/nepročitano, potvrdu prijema, potvrdu isporuke niti unos u privatni korisnički inbox. |
| **Rationale** | BM-OB-10 / PO-OB-10 |
| **Preconditions** | — |
| **Trigger** | UC-OB-001 ili UC-OB-002. |
| **Expected observable result** | Nema obaveznog read tracking / inbox ponašanja. |
| **Related Use Case** | UC-OB-001, UC-OB-002 |
| **Related Business Rule** | BM-OB-10 |
| **Related PO decision** | PO-OB-10 |
| **Acceptance criteria** | Given javni pregled panela ili pristup zvaničnom sadržaju, When se radnja izvrši, Then sistem ne zahtijeva i ne uslovljava tu radnju stanjem pročitano/nepročitano, potvrdom prijema, potvrdom isporuke ni privatnim inbox unosom. |
| **Status** | USVOJENO |

---

**Broj funkcionalnih zahtjeva:** 17

---

# 8. Funkcionalna odstupanja (neusklađenosti)

**Status:** USVOJENO

Sljedeća posmatrana ponašanja predstavljaju **funkcionalnu neusklađenost** sa usvojenim zahtjevima (ne inventišu se tokovi oporavka):

| ID | Neusklađenost | Krši |
|----|---------------|------|
| NC-OB-001 | Javno vidljivo Obavještenje referencira sadržaj koji nije javno dostupan | FR-OB-010 |
| NC-OB-002 | Objava zahtijeva administratorsko odobrenje u automatskom scenariju | FR-OB-014 |
| NC-OB-003 | Autentifikovani i neautentifikovani posjetioci imaju različita prava pregleda | FR-OB-003 |
| NC-OB-004 | Zamjena u aktivnom panelu uzrokuje brisanje ili uništavanje referenciranog zvaničnog sadržaja | FR-OB-016 |
| NC-OB-005 | Pregled ili pristup zahtijeva read/unread, potvrdu prijema ili inbox | FR-OB-017 |
| NC-OB-006 | Pristup zvaničnom sadržaju zavisi od administrativnog interfejsa | FR-OB-010 |

---

# 9. Van opsega

**Status:** USVOJENO

Van ove Functional Specification su (između ostalog):

* dizajn baze podataka i skladištenje;
* struktura ruta i URL-ova;
* dizajn kontrolera i servisa;
* arhitektura događaja i redova;
* zakazivanje;
* keširanje;
* ciljevi performansi i dostupnosti;
* implementacija sigurnosti;
* audit logging;
* standardi pristupačnosti;
* tačan UI dizajn (boje, dimenzije, responzivni raspored);
* generisanje PDF-a i tehnologija renderovanja;
* eksterni kanali objave.

Ovi elementi mogu pripadati kasnijoj Technical Specification ili zasebnim nefunkcionalnim zahtjevima.

---

# 10. Otvorene funkcionalne odluke

**Status:** OTVORENO

Ove odluke **nisu** usvojeni zahtjevi. Značenje pitanja nije mijenjano u odnosu na Business Model / Use Case Specification.

| ID | Pitanje | Funkcionalna oblast | Povezano | Zašto FS ne može riješiti | Potrebna PO odluka |
|----|---------|---------------------|----------|---------------------------|--------------------|
| OFD-OB-001 | Šta određuje da je novo Obavještenje „odgovarajuće“ u odnosu na starije radi zamjene u aktivnom panelu? | Zamjena u panelu | UC-OB-005; C-UC-OB-004 | BM nije usvojio kriterijum | Da |
| OFD-OB-002 | Može li više od jednog Obavještenja iz iste kategorije izvora / postupka biti istovremeno vidljivo? | Panel / zamjena | C-UC-OB-004 | BM §11.2 | Da |
| OFD-OB-003 | Šta se dešava ako noviji postupak bude otkazan ili ne proizvede zamjensku odluku? | Zamjena | UC-OB-005 E1 | BM §11.3 | Da |
| OFD-OB-004 | Kako se uređuju arhiviranje i dugoročna dostupnost sadržaja nakon što Obavještenje više nije vidljivo u aktivnom panelu? | Životni ciklus | C-UC-OB-001 | BM-OB-13 van obuhvata | Da |
| OFD-OB-005 | Da li je ikada dozvoljena ručna objava opštih opštinskih obavještenja? | Objava | C-UC-OB-003 | BM-OB-11; BM §11.5 | Da |
| OFD-OB-006 | Koje poslovno stanje u svakoj izvornoj funkcionalnosti pokreće objavu? | Okidač | UC-OB-003 | BM §11.6 | Da |
| OFD-OB-007 | Može li već objavljeni zvanični sadržaj biti ispravljen ili zamijenjen, i kako se tada ponaša javni unos? | Sadržaj / panel | C-UC-OB-002 | BM §11.7 | Da |
| OFD-OB-008 | Da li se kratak opis unosi iz izvornog procesa, generiše automatski ili se izostavlja? | Struktura | UC-OB-004 A1 | BM §11.8 | Da |
| OFD-OB-009 | Da li se redoslijed određuje vremenom objave, poslovnim značajem ili drugim pravilom? | Panel | UC-OB-001 | BM §11.9 | Da |
| OFD-OB-010 | Postoji li maksimalan broj vidljivih Obavještenja na početnoj stranici? | Panel | UC-OB-001, UC-OB-005 | BM §11.10 | Da |

**Broj otvorenih funkcionalnih odluka:** 10

---

# 11. Matrice sljedivosti

**Status:** USVOJENO

## 11.A FR-OB → UC-OB

| FR | UC |
|----|-----|
| FR-OB-001 | UC-OB-001 |
| FR-OB-002 | UC-OB-001 |
| FR-OB-003 | UC-OB-001, UC-OB-002 |
| FR-OB-004 | UC-OB-001 |
| FR-OB-005 | UC-OB-001 |
| FR-OB-006 | UC-OB-001, UC-OB-002 |
| FR-OB-007 | UC-OB-001, UC-OB-004 |
| FR-OB-008 | UC-OB-002 |
| FR-OB-009 | UC-OB-002 |
| FR-OB-010 | UC-OB-002, UC-OB-004 |
| FR-OB-011 | UC-OB-003 |
| FR-OB-012 | UC-OB-003, UC-OB-004 |
| FR-OB-013 | UC-OB-004 |
| FR-OB-014 | UC-OB-004 |
| FR-OB-015 | UC-OB-005 |
| FR-OB-016 | UC-OB-005 |
| FR-OB-017 | UC-OB-001, UC-OB-002 |

## 11.B FR-OB → BM-OB → PO-OB

| FR | BM-OB | PO-OB |
|----|-------|-------|
| FR-OB-001 | BM-OB-02 | PO-OB-02 |
| FR-OB-002 | BM-OB-03 | PO-OB-03 |
| FR-OB-003 | BM-OB-03 | PO-OB-03 |
| FR-OB-004 | BM-OB-02 | PO-OB-02 |
| FR-OB-005 | BM-OB-04 | PO-OB-04 |
| FR-OB-006 | BM-OB-04 | PO-OB-04 |
| FR-OB-007 | BM-OB-04 | PO-OB-04 |
| FR-OB-008 | BM-OB-01, BM-OB-03, BM-OB-04 | PO-OB-01, PO-OB-03, PO-OB-04 |
| FR-OB-009 | BM-OB-05 | PO-OB-05 |
| FR-OB-010 | BM-OB-12, BM-OB-03 | PO-OB-12, PO-OB-03 |
| FR-OB-011 | BM-OB-06, BM-OB-07, BM-OB-08 | PO-OB-06, PO-OB-07, PO-OB-08 |
| FR-OB-012 | BM-OB-07 | PO-OB-07 |
| FR-OB-013 | BM-OB-06, BM-OB-01 | PO-OB-06, PO-OB-01 |
| FR-OB-014 | BM-OB-06, BM-OB-11 | PO-OB-06, PO-OB-11 |
| FR-OB-015 | BM-OB-09, BM-OB-13 | PO-OB-09, PO-OB-13 |
| FR-OB-016 | BM-OB-13 | PO-OB-13 |
| FR-OB-017 | BM-OB-10 | PO-OB-10 |

## 11.C Pokrivenost Use Case-ova

| UC | FR |
|----|-----|
| UC-OB-001 | FR-OB-001, FR-OB-002, FR-OB-003, FR-OB-004, FR-OB-005, FR-OB-006, FR-OB-007, FR-OB-017 |
| UC-OB-002 | FR-OB-003, FR-OB-006, FR-OB-008, FR-OB-009, FR-OB-010, FR-OB-017 |
| UC-OB-003 | FR-OB-011, FR-OB-012 |
| UC-OB-004 | FR-OB-007, FR-OB-010, FR-OB-012, FR-OB-013, FR-OB-014 |
| UC-OB-005 | FR-OB-015, FR-OB-016 |

Kandidati C-UC-OB-001 do C-UC-OB-004 **nisu** izvori usvojenih FR.

---

# Provjera protivrječnosti BM ↔ UC

U okviru ove izrade **nije pronađena** protivrječnost između usvojenog Business Modela i usvojenih Use Case-ova koja bi zahtijevala ispravku izvornih dokumenata.

Napomena o pokrivenosti: Feature Registry zapis FT-004 (prije ove FS) nije navodio PO-OB-12 i PO-OB-13; BM ih sadrži. To nije protivrječnost BM↔UC, već zaostajanje registra — ažurira se uz registraciju FS.

---

# Završna napomena

Dokument ostaje **U IZRADI** dok OFD-OB-001 do OFD-OB-010 ne budu riješeni ili eksplicitno isključeni Product Owner odlukom.
