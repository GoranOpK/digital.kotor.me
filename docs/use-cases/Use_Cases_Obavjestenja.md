# Digital Kotor
# Use Case Specification
## Funkcionalnost: Obavještenja

**Feature ID:** FT-004  
**Status dokumenta:** U IZRADI  
**Verzija:** 0.1

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-31 | Početna Use Case specifikacija za FT-004 Obavještenja. Identifikovani usvojeni poslovni use case-ovi, kandidati i otvorena pitanja na osnovu Business Model v0.1 + PATCH-001. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument identifikuje **poslovne interakcije** oko funkcionalnosti Obavještenja.

Jedino pitanje koje dokument odgovara:

> Koje interakcije postoje oko funkcionalnosti Obavještenja?

Dokument **ne** odgovara kako se interakcije implementiraju.

Dokument **nije** Functional Specification i **nije** Technical Specification.

Služi za provjeru da su poslovni scenariji identifikovani prije početka Functional Specification.

---

# Izvor istine

Usvojeni Business Model:

`docs/business-model/Business_Model_Obavjestenja.md`

Feature Registry:

`docs/features/Feature-Registry.md` — FT-004

Svaki usvojeni use case mora biti sljediv do jednog ili više pravila BM-OB-* i odluka PO-OB-*.

---

# Status razvoja

| Poglavlje | Status |
|-----------|--------|
| Aktori | USVOJENO |
| Usvojeni use case-ovi (UC-OB-001 do UC-OB-005) | USVOJENO |
| Odnosi među use case-ovima | USVOJENO |
| Van opsega | USVOJENO |
| Kandidati (nije usvojeno) | OTVORENO |
| Otvorena pitanja | OTVORENO |
| Matrica sljedivosti | USVOJENO |

---

## Sadržaj

1. Uvod  
2. Aktori  
3. Pregled use case-ova  
4. Usvojeni use case-ovi  
5. Odnosi među use case-ovima  
6. Van opsega  
7. Kandidati (nije usvojeno u Business Modelu)  
8. Otvorena pitanja  
9. Matrica sljedivosti  

---

# 1. Uvod

Obavještenja su unakrsna platformska funkcionalnost Digital Kotor za javno predstavljanje zvaničnog sadržaja nastalog u drugim funkcionalnostima.

Interakcije se dijele na:

* **javni pregled** (panel i pristup zvaničnom sadržaju);
* **nastajanje i objava** Obavještenja iz izvorne funkcionalnosti;
* **zamjenu vidljivosti** u aktivnom panelu.

Nema zasebnog inboxa, praćenja čitanja, administratorskog odobrenja objave ni ručnog uredničkog workflow-a u usvojenom scenariju.

---

# 2. Aktori

| Aktor | Tip | Opis |
|-------|-----|------|
| **Javnost** | Primarni (pregled) | Svaka osoba koja pristupa Digital Kotor bez obaveze prijave. Obuhvata neautentifikovane posjetioce i autentifikovane korisnike. Za Obavještenja BM ne razlikuje prava pregleda između ove dvije grupe. |
| **Izvorna funkcionalnost** | Primarni (nastajanje) | Funkcionalnost Digital Kotor (npr. konkursi, tenderi ili druga) koja vodi poslovni proces, proizvodi zvanični sadržaj i utvrđuje da je sadržaj spreman ili obavezan za javnu objavu. |
| **Platforma Digital Kotor** | Podržavajući | Platforma koja prikazuje panel Obavještenja i omogućava objavu putem Obavještenja kao kanal prezentacije. |

**Napomena o terminologiji:** Pojam „Posjetilac“ u Business Modelu (§7.2) odgovara akteru **Javnost** u ovom dokumentu (sa ili bez prijave).

**Nisu aktori ovog dokumenta** (tehnički ili van poslovnog opsega):

* Queue Worker, Laravel Event, Scheduler, Database;
* Administrator kao odobravalac objave Obavještenja (usvojeni scenario ne uključuje to ovlašćenje);
* Urednik CMS-a za Obavještenja.

**Broj identifikovanih poslovnih aktora:** 3

---

# 3. Pregled use case-ova

## 3.1 Usvojeni

| ID | Naziv | Primarni aktor |
|----|-------|----------------|
| UC-OB-001 | Pregled panela Obavještenja | Javnost |
| UC-OB-002 | Pristup zvaničnom sadržaju putem Obavještenja | Javnost |
| UC-OB-003 | Utvrđivanje da je zvanični sadržaj spreman ili obavezan za javnu objavu | Izvorna funkcionalnost |
| UC-OB-004 | Nastajanje i javna vidljivost Obavještenja | Izvorna funkcionalnost / Platforma Digital Kotor |
| UC-OB-005 | Zamjena Obavještenja u aktivnom panelu | Izvorna funkcionalnost / Platforma Digital Kotor |

## 3.2 Kandidati (nije usvojeno)

| ID | Naziv | Razlog statusa kandidata |
|----|-------|--------------------------|
| C-UC-OB-001 | Pristup zvaničnom sadržaju nakon nestanka sa aktivnog panela | BM-OB-13 isključuje automatski gubitak dostupnosti, ali arhiviranje i dugoročna dostupnost nisu usvojeni |
| C-UC-OB-002 | Ispravka ili zamjena već objavljenog zvaničnog sadržaja | Otvoreno pitanje BM §11 tačka 7 |
| C-UC-OB-003 | Ručna objava opšteg opštinskog obavještenja | Van usvojenog scenarija; otvoreno pitanje BM §11 tačka 5 |
| C-UC-OB-004 | Istovremena vidljivost više Obavještenja iste kategorije | Otvoreno pitanje BM §11 tačka 2 |

---

# 4. Usvojeni use case-ovi

---

## UC-OB-001 — Pregled panela Obavještenja

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | UC-OB-001 |
| **Name** | Pregled panela Obavještenja |
| **Goal** | Omogućiti javnosti da na početnoj stranici Digital Kotor vidi panel Obavještenja sa javno vidljivim Obavještenjima. |
| **Primary actor** | Javnost |
| **Supporting actors** | Platforma Digital Kotor |
| **Preconditions** | Postoji početna stranica Digital Kotor. Panel Obavještenja je dio prezentacije početne stranice. |
| **Trigger** | Javnost pristupa početnoj stranici Digital Kotor. |
| **Business rules** | BM-OB-01, BM-OB-02, BM-OB-03, BM-OB-04, BM-OB-10 |
| **PO decisions** | PO-OB-01, PO-OB-02, PO-OB-03, PO-OB-04, PO-OB-10 |

### Main flow

1. Javnost otvara početnu stranicu Digital Kotor.
2. Platforma prikazuje panel dobrodošlice.
3. Ispod panela dobrodošlice platforma prikazuje panel **Obavještenja**.
4. U panelu su prikazana javno vidljiva Obavještenja.
5. Za svako Obavještenje javnost može uočiti najmanje naslov i referencu na zvanični sadržaj; kratak opis može biti prikazan ako postoji.

### Alternative flows

**A1 — Nema vidljivih Obavještenja**

1. Panel Obavještenja je prisutan.
2. Nema stavki za prikaz (prazan skup vidljivih Obavještenja).
3. Use case završava bez pristupa sadržaju.

**A2 — Autentifikovani korisnik**

1. Tok je isti kao Main flow.
2. Prijava ne mijenja pravo pregleda panela.

### Exceptions

Nema usvojenih poslovnih izuzetaka koji uslovljavaju prijavu ili registraciju radi pregleda.

### Postconditions

* Javnost je vidjela panel Obavještenja (sa ili bez stavki).
* Nije kreiran zapis o čitanju, potvrdi prijema ni inbox poruka.

---

## UC-OB-002 — Pristup zvaničnom sadržaju putem Obavještenja

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | UC-OB-002 |
| **Name** | Pristup zvaničnom sadržaju putem Obavještenja |
| **Goal** | Omogućiti javnosti da iz Obavještenja pristupi referenciranom zvaničnom sadržaju kroz stabilan javni mehanizam pristupa. |
| **Primary actor** | Javnost |
| **Supporting actors** | Platforma Digital Kotor; Izvorna funkcionalnost (kao nosilac zvaničnog sadržaja) |
| **Preconditions** | Postoji javno vidljivo Obavještenje sa referencom na zvanični sadržaj. Zvanični sadržaj je javno dostupan kroz stabilan javni mehanizam koji ne zavisi od administrativnog interfejsa. |
| **Trigger** | Javnost bira Obavještenje / referencu radi pristupa zvaničnom sadržaju. |
| **Business rules** | BM-OB-01, BM-OB-03, BM-OB-04, BM-OB-05, BM-OB-12, BM-OB-10 |
| **PO decisions** | PO-OB-01, PO-OB-03, PO-OB-04, PO-OB-05, PO-OB-12, PO-OB-10 |

### Main flow

1. Javnost vidi Obavještenje u panelu (UC-OB-001).
2. Javnost slijedi referencu na zvanični sadržaj.
3. Platforma / izvorna funkcionalnost isporučuje zvanični sadržaj kroz stabilan javni mehanizam pristupa.
4. Javnost pregleda zvanični sadržaj (dinamički generisan ili statički dokument).

### Alternative flows

**A1 — Dinamički generisan sadržaj**

1. Referencirani sadržaj je dinamički generisan.
2. Tok ostaje isti na poslovnom nivou; način generisanja nije predmet ovog dokumenta.

**A2 — Statički dokument**

1. Referencirani sadržaj je statički dokument.
2. Tok ostaje isti na poslovnom nivou; format i skladištenje nisu predmet ovog dokumenta.

### Exceptions

**E1 — Zvanični sadržaj nije javno dostupan**

1. Prekršen je uslov BM-OB-12 (dok je Obavještenje javno vidljivo, sadržaj mora biti javno dostupan).
2. To je poslovno odstupanje od usvojenog modela; sanacija nije detaljno usvojena u Business Modelu.

### Postconditions

* Javnost je pristupila zvaničnom sadržaju ili je naišla na odstupanje E1.
* Nije evidentirano pročitano/nepročitano stanje ni potvrda prijema.

---

## UC-OB-003 — Utvrđivanje da je zvanični sadržaj spreman ili obavezan za javnu objavu

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | UC-OB-003 |
| **Name** | Utvrđivanje da je zvanični sadržaj spreman ili obavezan za javnu objavu |
| **Goal** | Izvorna funkcionalnost utvrdi da je njen poslovni proces dostigao stanje u kojem je određeni zvanični sadržaj spreman ili obavezan za javnu objavu. |
| **Primary actor** | Izvorna funkcionalnost |
| **Supporting actors** | — (Obavještenja ne učestvuju u utvrđivanju) |
| **Preconditions** | Postoji poslovni proces u izvornoj funkcionalnosti. Postoji ili nastaje zvanični sadržaj koji može biti predmet javne objave. |
| **Trigger** | Poslovni proces izvorne funkcionalnosti dostigne tačku u kojoj se razmatra javna objava zvaničnog sadržaja. |
| **Business rules** | BM-OB-06, BM-OB-07, BM-OB-08 |
| **PO decisions** | PO-OB-06, PO-OB-07, PO-OB-08 |

### Main flow

1. Izvorna funkcionalnost vodi svoj poslovni proces.
2. Izvorna funkcionalnost proizvodi ili čini dostupnim zvanični sadržaj.
3. Izvorna funkcionalnost utvrđuje da je proces dostigao stanje u kojem je sadržaj spreman ili obavezan za javnu objavu.
4. Time je ispunjen poslovni preduslov za automatsko nastajanje Obavještenja (UC-OB-004).

### Alternative flows

**A1 — Početni izvor: konkurs**

1. Izvorna funkcionalnost je konkurs.
2. Tok je isti; konkretno poslovno stanje konkursa nije detaljno usvojeno u Business Modelu Obavještenja (otvoreno pitanje).

**A2 — Početni izvor: tender ili druga funkcionalnost**

1. Izvorna funkcionalnost je tender ili druga funkcionalnost Digital Kotor.
2. Tok je isti na nivou odgovornosti; nedokumentovano ponašanje tendera / budućih funkcionalnosti nije dio ovog use case-a.

### Exceptions

**E1 — Stanje za objavu nije dostignuto**

1. Izvorna funkcionalnost ne utvrđuje spremnost / obaveznost objave.
2. Obavještenje ne nastaje.

### Postconditions

* Utvrđeno je (ili nije) da je zvanični sadržaj spreman ili obavezan za javnu objavu.
* Obavještenja sama nisu donijela tu odluku.

---

## UC-OB-004 — Nastajanje i javna vidljivost Obavještenja

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | UC-OB-004 |
| **Name** | Nastajanje i javna vidljivost Obavještenja |
| **Goal** | Automatski nastati Obavještenje i učiniti ga javno vidljivim u oblasti Obavještenja, sa stabilnim javnim pristupom referenciranom zvaničnom sadržaju, bez administratorskog odobrenja. |
| **Primary actor** | Izvorna funkcionalnost |
| **Supporting actors** | Platforma Digital Kotor |
| **Preconditions** | UC-OB-003 je uspješno završen za dati zvanični sadržaj. Postoje podaci za Obavještenje: naslov; opcioni kratak opis; referenca na zvanični sadržaj. |
| **Trigger** | Utvrđeno je da je zvanični sadržaj spreman ili obavezan za javnu objavu. |
| **Business rules** | BM-OB-01, BM-OB-04, BM-OB-05, BM-OB-06, BM-OB-07, BM-OB-11, BM-OB-12 |
| **PO decisions** | PO-OB-01, PO-OB-04, PO-OB-05, PO-OB-06, PO-OB-07, PO-OB-11, PO-OB-12 |

### Main flow

1. Nakon UC-OB-003, odgovarajuće Obavještenje nastaje automatski.
2. Obavještenje sadrži naslov, opcioni kratak opis i referencu na zvanični sadržaj.
3. Obavještenje postaje javno vidljivo u oblasti Obavještenja (panel).
4. Referencirani zvanični sadržaj je javno dostupan kroz stabilan javni mehanizam pristupa koji ne zavisi od administrativnog interfejsa.
5. Nema dodatnog administratorskog odobrenja objave.
6. Javnost može izvršiti UC-OB-001 i UC-OB-002.

### Alternative flows

**A1 — Kratak opis izostavljen**

1. Obavještenje nastaje sa naslovom i referencom, bez kratkog opisa.
2. To je dozvoljeno (opis je opcioni). Izvor opisa nije usvojen (otvoreno pitanje).

### Exceptions

**E1 — Pokušaj ručne / uredničke objave umjesto automatskog nastajanja**

1. U usvojenom scenariju ručno kreiranje, uredničko odobrenje i administratorska potvrda nisu dio procesa.
2. Takav tok nije usvojeni use case (vidi C-UC-OB-003).

### Postconditions

* Obavještenje je javno vidljivo.
* Zvanični sadržaj je javno dostupan kroz stabilan javni mehanizam.
* Izvorna funkcionalnost ostaje odgovorna za proces i sadržaj.

---

## UC-OB-005 — Zamjena Obavještenja u aktivnom panelu

| Polje | Sadržaj |
|-------|---------|
| **Identifier** | UC-OB-005 |
| **Name** | Zamjena Obavještenja u aktivnom panelu |
| **Goal** | Zamijeniti vidljivost starijeg Obavještenja u aktivnom panelu odgovarajućim novim Obavještenjem iz narednog ekvivalentnog postupka, bez automatskog brisanja, arhiviranja ili uništavanja zvaničnog sadržaja. |
| **Primary actor** | Izvorna funkcionalnost |
| **Supporting actors** | Platforma Digital Kotor |
| **Preconditions** | Postoji Obavještenje vidljivo u aktivnom panelu. Nastaje novo odgovarajuće Obavještenje (preko UC-OB-003 i UC-OB-004) iz narednog ekvivalentnog postupka. |
| **Trigger** | Novo odgovarajuće Obavještenje iz narednog ekvivalentnog postupka postaje predmet zamjene u aktivnom panelu. |
| **Business rules** | BM-OB-09, BM-OB-13, BM-OB-06 |
| **PO decisions** | PO-OB-09, PO-OB-13, PO-OB-06 |

### Main flow

1. Novo Obavještenje nastaje i postaje javno vidljivo (UC-OB-004).
2. U aktivnom panelu novo Obavještenje zamjenjuje starije odgovarajuće Obavještenje (promjena vidljivosti u panelu).
3. Zamjena se ne tumači kao brisanje, arhiviranje, gubitak javne dostupnosti ni uništavanje referenciranog zvaničnog sadržaja.

### Alternative flows

Nema usvojenih alternativnih tokova. Kriterijumi „odgovarajuće“ zamjene i istovremena vidljivost nisu usvojeni (otvorena pitanja / kandidati).

### Exceptions

**E1 — Noviji postupak ne proizvede zamjensku odluku**

1. Ponašanje nije usvojeno u Business Modelu.
2. Scenarij ostaje otvoreno pitanje / kandidat za kasniju razradu.

### Postconditions

* Aktivni panel prikazuje novo Obavještenje umjesto starijeg (prema usvojenom prezentacionom očekivanju).
* Zamjena nije automatski izazvala brisanje ni uništavanje zvaničnog sadržaja.
* Dugoročna dostupnost / arhiviranje starijeg sadržaja nisu uređeni ovim use case-om.

---

# 5. Odnosi među use case-ovima

Tekstualni odnosi (bez UML):

```
UC-OB-003  →  vodi do  →  UC-OB-004
UC-OB-004  →  omogućava  →  UC-OB-001
UC-OB-004  →  omogućava  →  UC-OB-002
UC-OB-001  →  može nastaviti na  →  UC-OB-002
UC-OB-004 (novo)  →  može pokrenuti  →  UC-OB-005
UC-OB-005  →  zasniva se na  →  UC-OB-004
```

Opis:

* **UC-OB-003** je poslovni preduslov za **UC-OB-004**.
* **UC-OB-004** čini Obavještenje dostupnim za **UC-OB-001** i **UC-OB-002**.
* **UC-OB-001** često prethodi **UC-OB-002**, ali nije strogo obavezan ako javnost dođe do reference drugim putem (nije usvojeno kao zaseban tok).
* **UC-OB-005** nastaje kada novo **UC-OB-004** zamijeni starije u aktivnom panelu.

---

# 6. Van opsega

Sljedeće **nije** dio usvojene Use Case specifikacije, osim ako Business Model naknadno ne usvoji:

* praćenje pročitano / nepročitano;
* potvrda prijema (acknowledgment);
* korisnički inbox / privatne poruke;
* administratorsko odobrenje objave Obavještenja u automatskom scenariju;
* ručni urednički workflow objave;
* implementaciona arhitektura;
* tehničke integracije, rute, API, modeli podataka, redovi, događaji;
* objava putem `kotor.me`;
* proizvoljni eksterni URL-ovi kao usvojeni zahtjev.

---

# 7. Kandidati (nije usvojeno u Business Modelu)

Kandidati **nisu** usvojeni use case-ovi. Ne ulaze u Functional Specification dok Product Owner ne usvoji odgovarajuća pravila.

---

## C-UC-OB-001 — Pristup zvaničnom sadržaju nakon nestanka sa aktivnog panela

**Zašto kandidat:** BM-OB-13 kaže da zamjena ne podrazumijeva automatski gubitak javne dostupnosti, ali arhiviranje i dugoročna dostupnost eksplicitno ostaju van usvojenog obuhvata.

**Potrebna odluka:** BM §11 tačka 4.

---

## C-UC-OB-002 — Ispravka ili zamjena već objavljenog zvaničnog sadržaja

**Zašto kandidat:** Nije usvojeno kako se ponaša javni unos ako se zvanični sadržaj ispravi ili zamijeni.

**Potrebna odluka:** BM §11 tačka 7.

---

## C-UC-OB-003 — Ručna objava opšteg opštinskog obavještenja

**Zašto kandidat:** BM-OB-11 isključuje ručni workflow za automatski scenario; ručna opšta obavještenja nisu usvojena.

**Potrebna odluka:** BM §11 tačka 5.

---

## C-UC-OB-004 — Istovremena vidljivost više Obavještenja iste kategorije

**Zašto kandidat:** Nije usvojeno da li više stavki iste kategorije može biti istovremeno u aktivnom panelu.

**Potrebna odluka:** BM §11 tačka 2; utiče i na UC-OB-005.

---

# 8. Otvorena pitanja

Otvorena pitanja koja sprečavaju potpuni opis interakcija (preuzeta / usklađena sa Business Modelom):

1. Šta određuje da je novo Obavještenje „odgovarajuće“ radi UC-OB-005?
2. Može li više Obavještenja iste kategorije biti istovremeno vidljivo (C-UC-OB-004)?
3. Šta ako noviji postupak ne proizvede zamjenu (izuzetak UC-OB-005 / E1)?
4. Kako se uređuju arhiviranje i dugoročna dostupnost (C-UC-OB-001)?
5. Da li je dozvoljena ručna objava opštih obavještenja (C-UC-OB-003)?
6. Koje tačno poslovno stanje u svakoj izvornoj funkcionalnosti pokreće UC-OB-003?
7. Kako se ponaša Obavještenje pri ispravci zvaničnog sadržaja (C-UC-OB-002)?
8. Odakle dolazi kratak opis (uticaj na UC-OB-004 / A1)?
9. Koje je pravilo redoslijeda u panelu (uticaj na UC-OB-001)?
10. Postoji li maksimalan broj vidljivih Obavještenja (uticaj na UC-OB-001 / UC-OB-005)?

Ova pitanja **ne** uvode nova usvojena pravila.

---

# 9. Matrica sljedivosti

| Use case | BM-OB | PO-OB |
|----------|-------|-------|
| UC-OB-001 | BM-OB-01, BM-OB-02, BM-OB-03, BM-OB-04, BM-OB-10 | PO-OB-01, PO-OB-02, PO-OB-03, PO-OB-04, PO-OB-10 |
| UC-OB-002 | BM-OB-01, BM-OB-03, BM-OB-04, BM-OB-05, BM-OB-12, BM-OB-10 | PO-OB-01, PO-OB-03, PO-OB-04, PO-OB-05, PO-OB-12, PO-OB-10 |
| UC-OB-003 | BM-OB-06, BM-OB-07, BM-OB-08 | PO-OB-06, PO-OB-07, PO-OB-08 |
| UC-OB-004 | BM-OB-01, BM-OB-04, BM-OB-05, BM-OB-06, BM-OB-07, BM-OB-11, BM-OB-12 | PO-OB-01, PO-OB-04, PO-OB-05, PO-OB-06, PO-OB-07, PO-OB-11, PO-OB-12 |
| UC-OB-005 | BM-OB-09, BM-OB-13, BM-OB-06 | PO-OB-09, PO-OB-13, PO-OB-06 |

| Kandidat | Povezano BM / otvoreno pitanje |
|----------|--------------------------------|
| C-UC-OB-001 | BM-OB-13; BM §11.4 |
| C-UC-OB-002 | BM §11.7 |
| C-UC-OB-003 | BM-OB-11; BM §11.5 |
| C-UC-OB-004 | BM-OB-09; BM §11.2 |

---

# Završna napomena

Ovaj dokument ne mijenja Business Model, ne uvodi Functional Specification i ne uvodi Technical Specification.

Brojevi i nazivi use case-ova mogu se proširiti PATCH-om nakon novih Product Owner odluka.
