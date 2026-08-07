# Digital Kotor
# Technical Specification
## Newsletter

**Feature ID:** FT-001  
**Oznaka dokumenta:** TS-011  
**Funkcionalna cjelina:** Newsletter  
**Modul:** Kalendar kulture  
**Status dokumenta:** USVOJEN  
**Verzija:** 1.0.1 (DRAFT)  
**Datum:** 2026-08-07

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-07 | Prvi nacrt Technical Specification za funkcionalnu cjelinu Newsletter. Usklađen sa BM-13 (BM-NL-01–BM-NL-25), BM-GL-19, FS §5.15 (BR-138–BR-169), FS §5.16 katalog Newsletter (BR-184–BR-186), TS-003 v0.1.2, TS-004, TS-009, TS-010 v1.0.1, Feature Registry (FT-001), METHODOLOGY. Uvažava PO-DG-07 / PATCH-053: Otkazan terminalan; nema republish logike; G-NL-08 zatvoren. Tehnički predlozi (model podataka, obrada zadataka, raspored automatske obrade, evidencija dostave) bez novih poslovnih pravila. Bez izmjene BM/FS/ostalih TS. Bez izmjene implementacije. |
| 1.0.1 | 2026-08-07 | Završni PATCH nacrta prije validation-a: usvojena crnogorska terminologija; Pravila emitovanja okidača; Promjena na čekanju kao normativni dio prioritetnog toka; Evidencija dostavljenih Newsletter poruka (jedan Identitet pretplatnika); Kontrolni zapis promjene; cjelovito evidentiranje dostave; Arhitektura obrade Newsletter zadataka (obrada u grupama, raspodjela, pokazivač, skaliranje, ograničenje brzine); Raspored automatske obrade; Audit događaji; legacy PRAVILO 5.3.1–5.3.4; Van obuhvata PRAVILO 5.4.1–5.4.2. Bez novih BM/FS odluka. Bez izmjene drugih dokumenata. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.  
Kod svake naredne verzije dodaje se novi red u tabeli.  
Ne mijenjaju se postojeći redovi.

---

# Change Log

| Verzija | Datum | Izmjena |
|---------|--------|---------|
| 1.0.0 | 2026-08-07 | Kreiran TS-011 (NACRT). Kompletna tehnička specifikacija Newslettera. |
| 1.0.1 | 2026-08-07 | Završni PATCH: terminologija; okidači; Promjena na čekanju; Evidencija dostave; Kontrolni zapis promjene; cjelovita dostava; obrada zadataka; raspored; audit; legacy 5.3.1–5.3.4; van obuhvata 5.4.1–5.4.2. |

---

# Svrha dokumenta

Ovaj dokument opisuje kako će se usvojeni Business Model i Functional Specification za funkcionalnu cjelinu **Newsletter** tehnički realizovati u okviru FT-001 – Kalendar kulture.

TS-011 obrađuje jednu logički zaokruženu funkcionalnu cjelinu unutar FT-001 i ne predstavlja kompletnu tehničku specifikaciju svih cjelina Feature-a FT-001.

Dokument:

* ne uvodi nova poslovna pravila;
* ne zamjenjuje Business Model niti Functional Specification;
* nije Technical Overview trenutne implementacije;
* nije Change Request;
* ne definiše SQL, migracije, Laravel kod niti konkretne API ugovore;
* predlaže tehnički model (entiteti, zadaci, raspored automatske obrade) kao operacionalizaciju usvojenih BM/FS pravila.

Izvori istine za poslovna pravila:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-13 BM-NL-01–BM-NL-25; BM-GL-19; BM PATCH-031–033; usklađenost sa PATCH-053 / PO-DG-07)
* `docs/functional-specifications/Functional-Specification.md` (§5.15 BR-138–BR-169; §5.16 katalog Newsletter / BR-184–BR-186; PATCH-FS-031–034; usklađenost sa PATCH-FS-053)
* `docs/features/Feature-Registry.md` (FT-001 — Newsletter)
* `docs/METHODOLOGY.md` (M-TS-001–M-TS-005)
* `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003 v0.1.2)
* `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (TS-004)
* `docs/technical-specifications/Technical-Specification_Javni_portal.md` (TS-009)
* `docs/technical-specifications/Technical-Specification_Urednicki_portal.md` (TS-010 v1.0.1)

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Nacrt |
| 2. Granice odgovornosti | Nacrt |
| 3. Arhitektonski principi | Nacrt |
| 4. Komponente | Nacrt |
| 5. Pravila emitovanja okidača | Nacrt |
| 6. Model pretplate | Nacrt |
| 7. Model podataka | Nacrt |
| 8. Lifecycle pretplate | Nacrt |
| 9. Lifecycle Newsletter poruke | Nacrt |
| 10. Kandidati za slanje | Nacrt |
| 11. Redovni Newsletter | Nacrt |
| 12. Prioritetni Newsletter | Nacrt |
| 13. Evidencija dostavljenih Newsletter poruka | Nacrt |
| 14. Promjena na čekanju | Nacrt |
| 15. Arhitektura obrade Newsletter zadataka | Nacrt |
| 16. Raspored automatske obrade | Nacrt |
| 17. Validacije | Nacrt |
| 18. Guard uslovi | Nacrt |
| 19. Error handling | Nacrt |
| 20. Ponovni pokušaj | Nacrt |
| 21. Audit događaji | Nacrt |
| 22. Autorizacija | Nacrt |
| 23. Matrica sljedivosti | Nacrt |
| 24. Acceptance kriterijumi | Nacrt |
| 25. Napomene za implementaciju | Nacrt |
| 26. Legacy implementacija | Nacrt |
| 27. Van obuhvata (Out of Scope) | Nacrt |
| 28. Otvorena pitanja | Nacrt |

---

# Pravila upravljanja ovim dokumentom

1. TS-011 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz Technical Specification.
4. Sve što nije definisano u BM ili FS, a zahtijeva poslovnu odluku, evidentira se kao **Otvoreno pitanje**.
5. Tehnički predlozi (interval rasporeda automatske obrade, period objedinjavanja, imena komponenti, polja evidencije dostave) nisu poslovna pravila o učestalosti niti o sadržaju Newslettera.
6. Product Owner donosi poslovne odluke; ovaj dokument ih ne pretpostavlja.
7. Izmjene usvojenog sadržaja u narednim verzijama evidentiraju se novim redom u istoriji verzija.
8. **Otkazan** je terminalan za povratak u **Objavljen** (PO-DG-07 / PATCH-053). TS-011 **ne smije** sadržati logiku za republish / reaktivaciju otkazanog događaja. G-NL-08 je zatvoren.

---

# 1. Pregled funkcionalne cjeline

Izvori

Business Model:
- BM-NL-01–BM-NL-25
- BM-GL-19

Functional Specification:
- §5.15 (BR-138–BR-169)
- §5.16 katalog Newsletter (BR-184–BR-186)

## 1.1 Svrha funkcionalne cjeline

Funkcionalna cjelina **Newsletter** omogućava Kalendaru kulture da informiše registrovane i verifikovane korisnike o:

1. **novoobjavljenim** javno dostupnim kulturnim događajima (prvo uključivanje);
2. **poslovno značajnim promjenama** događaja koje utiču na odluku o prisustvu: otkazivanje, odlaganje, promjena datuma, vremena ili lokacije održavanja (prioritetna obavještenja).

Newsletter:

* služi isključivo informisanju o kulturnim događajima i njihovim poslovno značajnim promjenama;
* nije dio uredničkog procesa;
* nije kanal poslovne komunikacije između Organizatora, Moderatora, Urednika i Administratora;
* nije vezan za fiksni dan u sedmici niti za unaprijed definisanu kalendarsku sedmicu;
* sadržaj bira **Sistem** automatski.

## 1.2 Obuhvat dokumenta

Obuhvat TS-011:

1. obuhvat i granice odgovornosti modula Newsletter;
2. arhitektura i komponente;
3. pravila emitovanja okidača;
4. model pretplate (korisnik, izbor Organizatora, aktivna pretplata, odjava, reaktivacija);
5. konceptualni model podataka (pretplate, izbori Organizatora, Evidencija dostavljenih Newsletter poruka, Promjena na čekanju, token odjave, relacije);
6. lifecycle pretplate i Newsletter poruke;
7. kandidati za slanje; redovni i prioritetni Newsletter;
8. Evidencija dostavljenih Newsletter poruka (prvo uključivanje, zaštita od duplikata, posljednje stanje, agregacija, kontradiktorne poruke, cjelovito evidentiranje);
9. Promjena na čekanju (normativni dio prioritetnog toka);
10. Arhitektura obrade Newsletter zadataka i Raspored automatske obrade;
11. validacije, guard uslovi, error handling, ponovni pokušaj;
12. Audit događaji ka TS-012;
13. autorizacija, sljedivost, acceptance, implementacione napomene, legacy implementacija.

Van obuhvata ovog dokumenta: vidi §27 (PRAVILO 5.4.1–5.4.2).

## 1.3 Zavisnosti

| Zavisnost | Uloga u odnosu na TS-011 |
|-----------|---------------------------|
| Platforma Digital Kotor – korisnički nalozi | Identitet pretplatnika; verifikovan nalog (BR-139) |
| TS-001 Organizator | Filter izbora Organizatora; veza događaja → Organizator |
| TS-003 Događaj | Status **Objavljen** / **Otkazan** / **Arhiviran**; okidač objave; terminalnost Otkazan |
| TS-004 Održavanje | Budući termini; **Odgođen**; otkaz termina; promjena datuma/vremena/lokacije |
| TS-009 Javni portal | UI pretplate / odjave; linkovi ka detaljima događaja i pregledu Organizatora |
| TS-010 Urednički portal | Izvori okidača (objava, otkaz, odlaganje, izmjene termina/lokacije); bez upravljanja pretplatnicima |
| TS-012 Evidencija aktivnosti | Prima Audit događaje iz kataloga Newsletter |
| Infrastruktura elektronske pošte platforme | Isporuka e-mail poruka |

## 1.4 Tipovi sadržaja (norma)

Sistem razlikuje tačno tri tipa u smislu BR-165:

| Tip | Opis | Okidač |
|-----|------|--------|
| **Prvo uključivanje** | Novoobjavljeni događaj u statusu Objavljen | Javno objavljivanje događaja |
| **Prioritetno obavještenje** | Poslovno značajna promjena događaja već dostavljenog pretplatniku | Otkaz / odlaganje / promjena datuma, vremena ili lokacije |
| **Nije okidač** | Uređivačke izmjene | Ispravke teksta, fotografije, dodatne info koje ne mijenjaju način održavanja |

---

# 2. Granice odgovornosti

## 2.1 Šta Newsletter radi

* vodi pretplate registrovanih i verifikovanih korisnika;
* čuva filter izbora Organizatora (**obavezni V1**; BM-NL-04, BR-142–BR-143);
* periodično priprema i šalje redovni Newsletter o novoobjavljenim događajima;
* priprema i šalje prioritetna obavještenja o poslovno značajnim promjenama;
* vodi Evidenciju dostavljenih Newsletter poruka radi zaštite od duplikata;
* emituje Audit događaje ka centralnoj Evidenciji (TS-012).

## 2.2 Šta Newsletter ne radi

* ne upravlja statusima događaja niti održavanja;
* ne učestvuje u uredničkom workflow-u;
* ne šalje poslovna obavještenja Uredničkog portala (BR-128);
* ne omogućava Organizatoru, Moderatoru ni Uredniku upravljanje pretplatnicima ni ručno slanje;
* ne uvodi kategorije, personalizaciju, preporuke, kampanje ni Newsletter po ulozi (granice usvojene u BM-NL-16 / BR-157);
* **ne sadrži logiku republish-a** (nema putanje Otkazan → Objavljen; novi program = novi događaj, TS-003 / PATCH-053);
* ne mijenja prava korisnika ni poslovne procese zbog pretplate (BM-NL-08, BR-145).

## 2.3 Granica prema TS-003 / TS-004 / TS-010

| Izvor | Okidač za Newsletter | Napomena |
|-------|----------------------|----------|
| TS-010 / TS-003 | Prelaz u **Objavljen** (odobrenje / direktna objava) | Kandidat za **prvo uključivanje** |
| TS-010 / TS-003 | Prelaz u **Otkazan** | Prioritetno obavještenje **samo** pretplatnicima sa zapisom prvog uključivanja u Evidenciji dostavljenih Newsletter poruka; terminalan status — nema kasnijeg republish okidača |
| TS-004 | **Odgođen**; povratak sa novim terminom; promjena datuma/vremena/lokacije; otkaz pojedinačnog održavanja | Prioritetno obavještenje; scope = termin ili kompletan događaj (BR-162) |
| TS-003 | Prelaz u **Arhiviran** | Nije okidač prvog uključivanja; nije prioritetni okidač po BM-NL-17 |
| TS-010 | Uređivačke izmjene opisa/fotografija | **Nisu** okidač (BR-159) |

## 2.4 Granica prema TS-009

TS-009 obezbjeđuje UI površinu za pretplatu / odjavu / izbor Organizatora na javnom portalu. TS-011 definiše poslovno-tehnička pravila i backend ponašanje; UI detalji ostaju u TS-009 / implementaciji portala.

---

# 3. Arhitektonski principi

1. **BM/FS su izvor istine** — TS-011 ih operacionalizuje, ne proširuje.
2. **Automatski sadržaj** — Sistem bira događaje; nema ručnog izbora (BR-146).
3. **Dva kanala isporuke** — redovni (periodična agregacija novoobjavljenih) i prioritetni (blagovremena promjena).
4. **Jedan e-mail po pretplatniku po ciklusu** — objedinjavanje po pravilima BM-NL-06 / BR-153; prioritetna mogu biti objedinjena uz blagovremenost (BM-NL-24).
5. **Evidencija dostavljenih Newsletter poruka je izvor istine o „već dostavljeno"** — bez nje nema pouzdane zaštite od duplikata.
6. **Posljednje važeće stanje** — poruka odražava stanje u trenutku pripreme, ne istoriju međukoraka (BM-NL-22/23).
7. **Bez kontradiktornih poruka** u istom ciklusu pripreme (BM-NL-25).
8. **Nezavisnost** — pretplata ne utiče na prava ni statuse događaja.
9. **Terminalnost Otkazan** — otkazani događaj ne može ponovo ući kao novoobjavljeni sadržaj kroz republish; eventualni novi program je novi događaj (novi ID) i tada prolazi standardno prvo uključivanje.
10. **Tehnički interval ≠ poslovno pravilo** — raspored automatske obrade i period objedinjavanja su operativni parametri implementacije (BM-NL-07, BR-157).
11. **Jedan Identitet pretplatnika** — jedan kanonski izvor istine za pretplatnika u modelu podataka.

---

# 4. Komponente

Konceptualne komponente (bez obaveze imena klasa u kodu):

| Komponenta | Odgovornost |
|------------|-------------|
| **Usluga pretplate** | Aktivacija, odjava, reaktivacija, izbor Organizatora |
| **Razrješivač kandidata (redovni)** | Pronalazak kandidata za prvo uključivanje |
| **Razrješivač kandidata (prioritetni)** | Pronalazak i agregacija poslovno značajnih promjena |
| **Razrješivač publike** | Aktivni pretplatnici + filter Organizatora + filter Evidencije dostave |
| **Sastavljač poruke** | Sastavljanje sadržaja (grupisanje po Organizatoru, linkovi, odjava) |
| **Evidencija dostavljenih Newsletter poruka** | Evidencija uspješno dostavljenih stavki po pretplatniku |
| **Promjena na čekanju** | Tehnička evidencija još neobrađenih poslovno značajnih promjena |
| **Dispečer pošte** | Raspodjela zadataka / slanje e-mail poruka |
| **Prijem okidača** | Prima interne signale nakon uspješno sačuvanih poslovnih promjena (vidi §5) |
| **Mehanizam rasporeda automatske obrade** | Periodični redovni ciklus; period objedinjavanja za prioritet |
| **Emiter Audit događaja** | Emisija ka TS-012 |

---

# 5. Pravila emitovanja okidača

## 5.1 Norma

1. Newsletter dobija interni signal **isključivo** nakon uspješno završene i **trajno sačuvane** poslovne promjene.
2. Signal predstavlja **potvrđenu poslovnu promjenu**, a ne korisničku UI akciju.
3. Ako poslovna promjena nije uspješno sačuvana, Newsletter okidač se **ne emituje**.
4. Obrada Newslettera odvija se **odvojeno** od osnovne poslovne transakcije (TS-003 / TS-004 / TS-010 ne čekaju završetak Newsletter obrade).
5. Okidači imaju **jednoznačno značenje** po vrsti poslovne promjene (npr. objava događaja; otkaz događaja; odlaganje održavanja; promjena datuma/vremena/lokacije; otkaz održavanja).
6. Ova specifikacija **ne** vezuje emitovanje za konkretne Laravel Event klase, Redis, RabbitMQ niti drugu tehnologiju — način prenosa signala je implementacioni detalj unutar gornjih normi.

## 5.2 Mapiranje na poslovne promjene

| Vrsta poslovne promjene | Newsletter značenje |
|-------------------------|---------------------|
| Prelaz događaja u **Objavljen** | Kandidat za prvo uključivanje (redovni ciklus) |
| Prelaz događaja u **Otkazan** | Prioritetno obavještenje (uz Evidenciju prvog uključivanja) |
| **Odgođen** / novi termin / promjena datuma, vremena ili lokacije / otkaz održavanja | Prioritetno obavještenje |
| Uređivačke izmjene | Nema okidača |
| Otkazan → Objavljen | **Ne postoji**; signal se ne emituje (PATCH-053) |

---

# 6. Model pretplate

## 6.1 Korisnik

* Pretplatnik = registrovani i **verifikovani** korisnik platforme Digital Kotor (BM-NL-04, BR-139).
* Anonimni / neprijavljeni posjetilac **nema** pristup pretplati.
* Jedan korisnički nalog ↔ najviše jedna Newsletter pretplata.
* **Identitet pretplatnika** je jedan kanonski izvor istine u modelu (veza pretplate na platformskog korisnika); drugi identifikatori se ne vode kao ravnopravni izvori istine.

## 6.2 Izbor Organizatora (obavezni V1)

U skladu sa BM-NL-04 i BR-141–BR-143:

* Pretplatnik može izabrati **sve Organizatore** ili **jednog ili više konkretnih** Organizatora.
* Ako nije izabran nijedan konkretan Organizator, sistem tretira izbor kao **sve Organizatore**.
* Tehnički predlog: režim `all_organizers = true` **ili** skup zapisa konkretnih Organizatora; prazan skup konkretnih izbora ≡ svi Organizatori.
* Izbor Organizatora je **isključivo filter sadržaja**: ne daje prava nad Organizatorom ni događajima, ne otkriva pretplatnike Organizatoru/Moderatoru, ne omogućava slanje.
* Pretplatnik može **mijenjati** svoj izbor; novi izbor važi za buduća slanja; nema retroaktivnog slanja ranije objavljenih događaja.

Ovo pravilo **nije** van obuhvata V1.

## 6.3 Aktivna pretplata

Aktivni pretplatnik (BM-NL-12, BR-149):

* verifikovan korisnik;
* pretplata u statusu **aktivna**;
* nije izvršio odjavu.

Postojanje kandidata za slanje **nije** dio definicije aktivnog pretplatnika.

## 6.4 Odjava

* Dostupna iz Newsletter poruke (token link) i iz UI podešavanja (BR-154, BR-155).
* Deaktivira pretplatu; **ne** briše nalog; **ne** mijenja uloge ni pristup modulima.
* Tehnički: status odjavljen, vrijeme odjave, rotacija/invalidacija tokena odjave po potrebi.

## 6.5 Reaktivacija pretplate

* Ranije odjavljeni korisnik može ponovo aktivirati pretplatu (BR-141).
* Tehnički predlog: zadržati prethodni izbor Organizatora ako postoji; korisnik može izmijeniti pri ili nakon reaktivacije.
* Ponovno slanje početne potvrde aktivacije **nije obavezno** (BR-156); UI potvrda je dovoljna.
* Reaktivacija **ne** zahtijeva retroaktivno slanje ranije objavljenih događaja.

## 6.6 Potvrda prve aktivacije

* Nakon **prve** uspješne aktivacije Sistem šalje e-mail potvrdu (BM-NL-15, BR-156).
* Nije double opt-in; double opt-in nije V1.

---

# 7. Model podataka

Konceptualni model (bez SQL / migracija). Imena su predlog za implementaciju.

## 7.1 Entitet: Newsletter pretplata

| Atribut (konceptualno) | Opis |
|------------------------|------|
| `id` | Identifikator pretplate |
| Identitet pretplatnika | Kanonska veza na platformskog korisnika (unikatno) |
| `status` | aktivna \| odjavljena |
| `all_organizers` | `true` = svi Organizatori |
| `subscribed_at` | Vrijeme posljednje aktivacije |
| `unsubscribed_at` | Vrijeme odjave (nullable) |
| `first_activated_at` | Vrijeme prve aktivacije (za potvrdu BR-156) |
| `confirmation_sent_at` | Vrijeme slanja potvrde prve aktivacije |
| Token odjave | Tajni token za odjavu iz poruke |
| `created_at` / `updated_at` | Tehnički tragovi |

## 7.2 Entitet: Izbor Organizatora

| Atribut | Opis |
|---------|------|
| Veza na pretplatu | FK pretplate |
| `organizer_id` | Izabrani Organizator |
| unikatan par | (pretplata, Organizator) |

Pravilo: kada je `all_organizers = true`, selekcije mogu biti prazne; filter se ne primjenjuje na konkretne ID-jeve.

## 7.3 Entitet: Evidencija dostavljenih Newsletter poruka

Evidencija uspješno dostavljenih stavki pretplatniku. Služi BM-NL-11, BM-NL-18, BM-NL-21.

| Atribut | Opis |
|---------|------|
| `id` | Identifikator |
| Identitet pretplatnika | Kanonska veza na pretplatnika (preko pretplate); **jedini** izvor istine o tome *kome* je dostavljeno |
| `event_id` | Događaj |
| `occurrence_id` | Održavanje (nullable; za promjene na nivou termina) |
| `entry_type` | `first_include` \| `priority_change` |
| Kontrolni zapis promjene | Za prioritetne stavke; vidi §13.2 |
| Identifikator ciklusa / grupe za obradu | Veza na cjelovitu Newsletter poruku |
| `payload_snapshot` | Opciono: sažetak dostavljenog stanja (za podršku) |
| `sent_at` | Vrijeme uspješne dostave |
| unikati | Vidi §13.2 |

**Norma:** ne modelirati dva konkurentna identifikatora pretplatnika (npr. paralelno `subscription_id` i `user_id` kao ravnopravne izvore istine). Identitet pretplatnika je jedan.

## 7.4 Entitet: Promjena na čekanju

**Normativni** dio prioritetnog toka (nije opciono).

Tehnička evidencija poslovno značajne promjene koja je nastala, ali još nije obrađena za potrebe Newslettera. Detalji lifecycle-a: §14.

| Atribut | Opis |
|---------|------|
| `event_id` / `occurrence_id` | Predmet promjene |
| `change_kind` | otkaz događaja \| otkaz održavanja \| odlaganje \| promjena datuma/vremena \| promjena lokacije |
| `effective_state` | Posljednje važeće stanje (overwrite pri novoj promjeni) |
| `detected_at` | Vrijeme detekcije |
| Status | vidi §14.2 |

## 7.5 Relacije

```
Korisnik 1 ── 1 Newsletter pretplata
Newsletter pretplata 1 ── * Izbor Organizatora ── 1 Organizator
Newsletter pretplata 1 ── * Evidencija dostavljenih Newsletter poruka ── 1 Događaj
Evidencija dostavljenih Newsletter poruka 0..1 ── Održavanje (TS-004)
Događaj / Održavanje ── * Promjena na čekanju
```

## 7.6 Token odjave

* Generiše se pri aktivaciji / rotira pri reaktivaciji po potrebi.
* Omogućava odjavu bez aktivne sesije (link u e-mailu).
* Mora biti dovoljno entropije; ne smije biti predvidiv.
* Nakon uspješne odjave token se invalidira ili zamjenjuje.

---

# 8. Lifecycle pretplate

```
[nema pretplate]
       │ aktivacija (verifikovan korisnik)
       ▼
   Active  ←──────────────┐
       │ odjava           │ reaktivacija
       ▼                  │
 Unsubscribed ────────────┘
```

| Stanje | Smije primati Newsletter | Napomena |
|--------|--------------------------|----------|
| Active | Da | Uz ostale guard uslove |
| Unsubscribed | Ne | Evidencija dostave ostaje |
| Nema zapisa | Ne | |

Invariant:

* odjava ne briše Evidenciju dostavljenih Newsletter poruka;
* izmjena izbora Organizatora ne briše Evidenciju dostave i ne šalje retroaktivno stare događaje.

---

# 9. Lifecycle Newsletter poruke

## 9.1 Redovni ciklus

```
Raspored automatske obrade → Razrješavanje kandidata
  → Obrada u grupama / Raspodjela zadataka po pretplatnicima
  → Sastavljanje → Preskoči ako prazno → Zadatak slanja
  → Na uspjeh: cjelovito upisivanje u Evidenciju dostave (first_include)
  → Emisija Audit događaja (Sistem)
```

## 9.2 Prioritetni ciklus

```
Emitovanje okidača → Upsert Promjene na čekanju (posljednje stanje)
  → Period objedinjavanja → Agregacija po događaju/održavanju
  → Publika (Evidencija first_include ∩ aktivni ∩ filter Organizatora)
  → Jedno konačno stanje → Zadatak slanja
  → Na uspjeh: cjelovito upisivanje priority_change → Audit događaj (Sistem)
```

## 9.3 Stanja poruke (tehnička)

| Stanje | Opis |
|--------|------|
| Priprema ciklusa | Priprema u toku |
| U redu za slanje | Čeka proces obrade |
| Slanje u toku | Isporuka u toku |
| Dostavljeno | Uspješno; Evidencija dostave upisana cjelovito |
| Neuspjeh | Nakon iscrpljenih ponovnih pokušaja; vidi §19–20 |
| Preskočeno (prazno) | Nema sadržaja za pretplatnika |

---

# 10. Kandidati za slanje

## 10.1 Kandidat za prvo uključivanje

Događaj je kandidat ako **istovremeno**:

1. status = **Objavljen** (BM-NL-09, BR-147);
2. javno dostupan po pravilima portala;
3. ima ≥ 1 buduće održavanje u trenutku pripreme (BM-NL-10);
4. odgovara izboru Organizatora pretplatnika;
5. ne postoji zapis `first_include` u Evidenciji dostavljenih Newsletter poruka za (Identitet pretplatnika, događaj).

**Nisu** kandidati za prvo uključivanje: Nacrt, Na odobrenju, Arhiviran, **Otkazan**.

Napomena (BR-114 vs BR-147): javni portal može prikazati otkazane događaje; to **ne** proširuje prvo uključivanje. Prioritetno obavještenje o otkazu je zaseban tip.

## 10.2 Kandidat za prioritetno obavještenje

Promjena je kandidat ako:

1. tip je iz BM-NL-17 / BR-160 (otkaz događaja, odlaganje, promjena datuma/vremena/lokacije; uključujući otkaz termina po TS-004);
2. postoji `first_include` za pretplatnika i taj događaj (BM-NL-18);
3. pretplatnik je aktivan;
4. ista promjena (Kontrolni zapis promjene) nije već dostavljena (BM-NL-21);
5. filter Organizatora i dalje važi u trenutku slanja (tehnički predlog: da — ne šalje se pretplatniku koji više ne prati Organizatora).

## 10.3 Uređivačke izmjene

Ne ulaze u kandidate (BM-NL-14, BR-159).

---

# 11. Redovni Newsletter

## 11.1 Svrha

Objedinjeni e-mail o novoobjavljenim događajima koji odgovaraju aktivnim pretplatama (BM-NL-07, BR-148).

## 11.2 Pravila pripreme

1. Periodična provjera putem Rasporeda automatske obrade — bez fiksnog dana u sedmici kao poslovnog pravila.
2. Više događaja → jedna poruka po pretplatniku.
3. Grupisanje po Organizatoru; link ka pregledu Organizatora (BR-152).
4. Jedan događaj jednom; termini unutar stavke (BR-153).
5. Sadržaj stavke: naziv, datum, vrijeme (ako ima), lokacija (ako ima), budući termini, link ka detaljima (BR-151).
6. Posljednje važeće stanje u trenutku pripreme (BR-167).
7. Ako nema nijednog kandidata za pretplatnika → **ne slati** (BR-150).
8. Ne dodavati događaje „radi popune”.
9. Svaka poruka sadrži odjavu (BR-155).
10. Nakon uspješnog slanja → cjelovito upisivanje `first_include` u Evidenciju dostave (§13.3).

## 11.3 Odnos prema vremenu objave

Događaj ne mora biti poslat u trenutku objave; postaje kandidat za naredni redovni ciklus (BM-NL-09).

---

# 12. Prioritetni Newsletter

## 12.1 Svrha

Blagovremeno informisanje pretplatnika kojima je događaj **već** bio dostavljen o poslovno značajnim promjenama (BM-NL-17–20).

## 12.2 Izvori okidača (usklađenost sa TS-003 / TS-004 / TS-010)

| Poslovna radnja | Značenje okidača | Scope obavještenja |
|-----------------|------------------|-------------------|
| Otkazivanje događaja (→ Otkazan) | Otkaz događaja | Kompletan događaj |
| Odlaganje održavanja (→ Odgođen) | Odlaganje | Termin |
| Novi termin nakon Odgođen | Promjena datuma/vremena | Termin |
| Promjena datuma/vremena/lokacije | Promjena datuma/vremena / lokacije | Termin |
| Otkaz pojedinačnog održavanja | Otkaz održavanja | Termin (nije otkaz cijelog događaja) |

**Nema** okidača „republish / Otkazan → Objavljen”.

Ako se isti kulturni program kasnije ponovo organizuje, to je **novi događaj** (novi zapis) i može biti novo **prvo uključivanje** ako ispuni uslove §10.1 (PATCH-053 / BM-DG-09).

## 12.3 Publika

Isključivo aktivni pretplatnici sa `first_include` za taj događaj (BM-NL-18). Pretplatnici bez prvog uključivanja **ne** dobijaju obavještenje o otkazu/izmjeni.

## 12.4 Agregacija i blagovremenost

* Više uzastopnih promjena prije slanja → **jedno** obavještenje sa posljednjim stanjem (BM-NL-22), preko Promjene na čekanju (§14).
* Prioritetna mogu biti objedinjena ako nije ugrožena blagovremenost (BM-NL-24).
* Tehnički: period objedinjavanja prije flush-a (vidi §16).

## 12.5 Kontradiktorne poruke

U istom ciklusu pripreme pretplatnik ne smije dobiti međusobno kontradiktorna obavještenja za isti događaj (BM-NL-25). Implementacija: Promjena na čekanju drži samo **efektivno** stanje; međukoraci se označavaju kao zamijenjeni novijim stanjem.

---

# 13. Evidencija dostavljenih Newsletter poruka

## 13.1 Svrha

Evidencija dostavljenih Newsletter poruka služi za:

* evidenciju šta je dostavljeno pojedinom pretplatniku;
* sprječavanje duplih dostava;
* određivanje publike za prioritetna obavještenja;
* očuvanje posljednjeg dostavljenog stanja (u smislu već dostavljenih stavki / Kontrolnog zapisa).

Nije centralna Evidencija aktivnosti (TS-012). Nije tehnički log platforme za elektronsku poštu.

## 13.2 Prvo uključivanje i zaštita od duplikata

* Upis `entry_type = first_include` **tek nakon uspješne isporuke** e-maila, u okviru **cjelovite** operacije (§13.3).
* Ključ: (Identitet pretplatnika, događaj, first_include).
* Sprečava ponovno slanje istog događaja kao novoobjavljenog pri narednim ciklusima (BM-NL-11, BR-158).

| Pravilo | Mehanizam |
|---------|-----------|
| BM-NL-11 / BR-158 | Unikat `first_include` po (Identitet pretplatnika, događaj) |
| BM-NL-21 / BR-164 | Unikat `priority_change` po (Identitet pretplatnika, događaj, održavanje?, Kontrolni zapis promjene) |
| BM-NL-18 | Prioritet samo ako postoji `first_include` |

### Kontrolni zapis promjene

**Kontrolni zapis promjene** služi za:

* prepoznavanje već obrađenih poslovno značajnih promjena;
* sprječavanje duplih i kontradiktornih Newsletter poruka.

Način njegovog izračunavanja **nije** predmet TS-011 i ostaje implementacioni detalj.

## 13.3 Cjelovito evidentiranje dostave

Kada Newsletter poruka sadrži više događaja ili više stavki:

1. Evidentiranje dostave mora biti **cjelovita operacija**.
2. Sistem **ne smije** ostaviti djelimično evidentirane stavke iste Newsletter poruke.
3. Kod neuspjeha sistem mora zadržati stanje pogodno za **bezbjedan ponovni pokušaj**.
4. Ne smije nastati situacija da dio poruke izgleda kao dostavljen, a dio kao nedostavljen, bez jasnog tehničkog statusa.

Tehnička realizacija (transakcija, outbox i sl.) je implementacioni detalj unutar ove norme.

## 13.4 Posljednje stanje i agregacija

* Sastavljač poruke čita **trenutno** poslovno stanje događaja/održavanja iz TS-003/TS-004 u trenutku pripreme (BM-NL-23).
* Agregacija prioritetnih promjena ide preko Promjene na čekanju; u Evidenciju ulazi Kontrolni zapis **konačnog** dostavljenog stanja.

## 13.5 Kontradiktorne poruke

* Zabrana na nivou ciklusa: jedan finalni zapis po događaju/terminu po pretplatniku po ciklusu.
* Primjer: odlaganje pa otkaz prije flush-a → šalje se samo otkaz (konačno stanje).

## 13.6 Odjava i Evidencija

* Evidencija se ne briše pri odjavi.
* Pri reaktivaciji ne re-šalju se `first_include` stavke za već zabilježene događaje (tehnički predlog usklađen sa BM-NL-11 / BM-NL-18).

---

# 14. Promjena na čekanju

## 14.1 Definicija (normativno)

**Promjena na čekanju** je tehnička evidencija poslovno značajne promjene koja je nastala, ali još nije obrađena za potrebe Newslettera.

**Obavezan** je dio prioritetnog toka V1.

Služi za:

* objedinjavanje uzastopnih promjena;
* očuvanje posljednjeg važećeg stanja;
* sprječavanje duplih poruka;
* sprječavanje kontradiktornih poruka.

## 14.2 Lifecycle (minimum)

| Stanje | Značenje |
|--------|----------|
| **Na čekanju** | Promjena je zabilježena; čeka period objedinjavanja / flush |
| **Obrađeno** | Uključena u uspješno dostavljenu prioritetnu poruku (ili ispravno završena bez slanja, npr. bez publike) |
| **Zamijenjeno novijim stanjem** | Superseded novijom Promjenom na čekanju nad istim predmetom prije dostave |

Tehnički cleanup obrađenih / zamijenjenih zapisa je implementacioni detalj.

## 14.3 Ponašanje

1. Emitovani okidač (§5) → upsert Promjene na čekanju za (događaj, održavanje?).
2. Nova promjena nad istim predmetom overwrite-uje `effective_state` i raniji zapis označava kao **zamijenjen novijim stanjem**.
3. Flush nakon perioda objedinjavanja šalje **jedno** obavještenje sa posljednjim stanjem.
4. Nakon cjelovite uspješne dostave → status **obrađeno** + upis u Evidenciju dostave.

---

# 15. Arhitektura obrade Newsletter zadataka

## 15.1 Svrha

Arhitektura obrade Newsletter zadataka definiše kako Sistem priprema i dostavlja Newsletter poruke bez blokiranja osnovnih poslovnih transakcija (§5).

## 15.2 Predloženi zadaci (imena ilustrativna)

| Zadatak | Ulaz | Izlaz |
|---------|------|-------|
| Prijem okidača | Potvrđena poslovna promjena | Upsert Promjene na čekanju |
| Redovni ciklus Newslettera | Raspored automatske obrade | Grupe kandidata → sastavljanje → zadaci slanja |
| Flush prioritetnog Newslettera | Period objedinjavanja / sigurnosna provjera | Agregacija Promjena na čekanju → sastavljanje → zadaci slanja |
| Slanje Newsletter e-maila | Payload + primalac | Slanje; na uspjeh cjelovita Evidencija + Audit događaj |
| Potvrda aktivacije pretplate | Prva aktivacija | Potvrda e-mail |

## 15.3 Obavezne tehničke sposobnosti

Sistem mora podržavati:

1. **Obrada u grupama** — redovni i prioritetni ciklusi ne smiju zavisiti od obrade svih pretplatnika u jednom monolitskom koraku.
2. **Raspodjela zadataka** — slanje pojedinačnih poruka raspoređuje se na zadatke pogodne za paralelnu obradu.
3. **Pokazivač obrade** — napredak redovnog ciklusa mora biti nastavljiv (bez ponovnog nekontrolisanog prolaska već obrađenog skupa u istom ciklusu).
4. **Više procesa obrade** — dozvoljeno je paralelno izvršavanje nezavisnih zadataka slanja.
5. **Horizontalno skaliranje** — dodavanje kapaciteta procesa obrade ne smije lomiti semantiku Evidencije dostave ni Promjene na čekanju.
6. **Kontrolisani ponovni pokušaji** — vidi §20.
7. **Ograničenje brzine slanja** — prema mogućnostima servisa elektronske pošte (ops/konfiguracija).

Ne propisuju se konkretne veličine grupa.  
Ne propisuje se konkretna tehnologija reda za obradu.

## 15.4 Principi

* Teški rad (razrješavanje + sastavljanje) odvojen od pojedinačnog slanja.
* Jedan zadatak slanja po pretplatniku po poruci.
* Idempotentnost: pri ponovnom pokušaju provjeriti Evidenciju dostave prije ponovnog upisa / po mogućnosti prije ponovnog slanja.
* Cjelovito evidentiranje dostave (§13.3) važi i pri raspodjeli zadataka.

---

# 16. Raspored automatske obrade

## 16.1 Norma (BM/FS)

BM-NL-07 i BR-157 **namjerno ne** definišu interval. TS-011 predlaže tehničko rješenje **bez** uvođenja poslovnog pravila o učestalosti.

## 16.2 Početna tehnička konfiguracija

| Mehanizam | Početna vrijednost | Namjena |
|-----------|--------------------|---------|
| Redovna provjera | **6 sati** | Agregacija novoobjavljenih |
| Period objedinjavanja promjena | **15 minuta** | Blagovremenost prioritetnih uz agregaciju |
| Sigurnosna provjera prioritetnih promjena | **5 minuta** | Flush dospelih Promjena na čekanju |

Obavezno:

* vrijednosti su **konfigurabilne**;
* **ne** predstavljaju poslovno pravilo;
* mogu se mijenjati tehničkom konfiguracijom bez izmjene BM/FS;
* sistem mora spriječiti **nekontrolisano preklapanje** istovrsnih ciklusa automatske obrade;
* obrada mora podržavati grupisanje i horizontalno skaliranje (§15).

## 16.3 Šta raspored ne smije

* Ne smije uvesti fiksni „newsletter dan u sedmici” kao poslovni model.
* Ne smije slati prazne poruke.
* Ne smije pokretati ručno slanje iz uredničkog UI.

---

# 17. Validacije

| ID | Pravilo | Kada |
|----|---------|------|
| V-NL-01 | Korisnik mora biti autentifikovan i verifikovan | Aktivacija / izmjena / odjava (UI) |
| V-NL-02 | Organizator u selekciji mora postojati i biti validan | Izbor Organizatora |
| V-NL-03 | Ne aktivirati duplu pretplatu za isti Identitet pretplatnika | Aktivacija |
| V-NL-04 | Token odjave mora biti validan i aktivan | Odjava iz e-maila |
| V-NL-05 | Kandidat first_include: status Objavljen + budući termin | Redovni resolve |
| V-NL-06 | Kandidat priority: postoji first_include + dozvoljena vrsta promjene | Prioritetni resolve |
| V-NL-07 | Poruka mora sadržati link odjave | Sastavljanje |
| V-NL-08 | Prazan sadržaj → skip | Sastavljanje |

---

# 18. Guard uslovi

Redoslijed guard-ova pri slanju (po pretplatniku):

1. Pretplata aktivna.
2. Korisnik i dalje verifikovan / nalog aktivan (tehnički predlog).
3. Filter Organizatora.
4. Za redovni: nema `first_include`; događaj još Objavljen + budući termin.
5. Za prioritetni: postoji `first_include`; nema isti Kontrolni zapis promjene; efektivno stanje nije zamijenjeno novijim.
6. Non-empty payload.
7. Uspješan send → cjelovita Evidencija dostave → Audit događaj.

Guard „nema republish”:

* Prijem okidača **ignoriše** signal tipa „Otkazan → Objavljen” (takav prelaz nije dozvoljen u TS-003 / PATCH-053).
* Novi događaj (novi ID) nakon otkazanog programa tretira se isključivo kao kandidat za **prvo uključivanje**, ako ispunjava §10.1.

---

# 19. Error handling

| Scenarij | Ponašanje |
|----------|-----------|
| Greška servisa elektronske pošte | Ponovni pokušaj po §20; bez upisa u Evidenciju do uspjeha |
| Trajni neuspjeh nakon ponovnih pokušaja | Status neuspjeh; tehnički log; **ne** Audit događaj „slanje” (BR-186) |
| Korisnik odjavljen tokom obrade | Guard pri slanju → skip |
| Događaj više nije Objavljen u trenutku redovnog sastavljanja | Izbaciti iz first_include kandidata |
| Događaj otkazan prije first_include | Ne ulazi u redovni; nema prioritet bez first_include |
| Preklapanje istovrsnih ciklusa | Zaključavanje / zaštita → drugi ciklus se ne izvršava nekontrolisano |
| Nevažeći token odjave | Poruka o neuspjehu; bez promjene pretplate |
| Promjena na čekanju bez publike | Završiti bez slanja (obrađeno / bez dostave) |
| Neuspjeh cjelovitog upisa Evidencije | Stanje pogodno za bezbjedan ponovni pokušaj (§13.3) |

Tehničke greške slanja **ne** ulaze u centralnu Evidenciju (BR-186).

---

# 20. Ponovni pokušaj

Tehnički predlog (ops):

| Parametar | Predlog |
|-----------|---------|
| Max ponovnih pokušaja po zadatku slanja | 3 |
| Backoff | Eksponencijalni (npr. 1 min / 5 min / 15 min) |
| Idempotency | Ako Evidencija već sadrži cjeloviti zapis za istu poruku / Kontrolni zapis → ne šalji ponovo |
| Dead letter | Nakon max pokušaja → neuspjeh + ops alert (tehnički kanal) |

Ponovni pokušaj **nije** poslovno ponavljanje sadržaja i **nije** Audit događaj.

---

# 21. Audit događaji

## 21.1 Razgraničenje TS-011 / TS-012

* TS-011 **emituje** Audit događaje za aktivnosti definisane BM i FS (katalog Newsletter).
* TS-011 **ne** određuje: skladištenje, prikaz, pretragu, retenciju, administraciju audit podataka.
* To pripada **TS-012**.
* **Evidencija dostavljenih Newsletter poruka nije audit** i ne smije se poistovjećivati sa audit evidencijom.

## 21.2 Katalog (BR-185 / FS §5.16)

U centralnu Evidenciju **ulaze**:

| Aktivnost | Izvršilac |
|-----------|-----------|
| Aktivacija Newsletter pretplate | Korisnik |
| Odjava sa Newsletter pretplate | Korisnik |
| Ponovna aktivacija Newsletter pretplate | Korisnik |
| Promjena izbora Organizatora | Korisnik |
| Slanje redovnog Newslettera | **Sistem** (BR-184) |
| Slanje prioritetnog Newsletter obavještenja | **Sistem** (BR-184) |

## 21.3 Van centralne evidencije (BR-186)

* tehničke greške slanja i ponovni pokušaji;
* potvrda aktivacije kao zaseban audit zapis;
* pregled postavki bez izmjena;
* urednička poslovna obavještenja (BR-128).

## 21.4 Razgraničenje od kataloga događaja

Slanje Newslettera **ne** duplira zapise kataloga događaja (objava/otkaz ostaju u katalogu Događaji).

---

# 22. Autorizacija

| Radnja | Ko |
|--------|----|
| Aktivacija / odjava / reaktivacija / izbor Organizatora | Vlasnik naloga (verifikovan korisnik) |
| Odjava putem tokena | Posjednik validnog tokena (bez obavezne sesije) |
| Pregled / upravljanje tuđim pretplatama | **Niko** u V1 (ni Organizator, ni Moderator, ni Urednik) |
| Ručno slanje / izbor događaja za Newsletter | **Nije dozvoljeno** |
| Pokretanje rasporeda / zadataka | Sistem / ops infrastruktura |
| Čitanje centralne evidencije Newsletter audita | Administrator platforme (preko TS-012) |

Organizator i Moderator **nemaju** uvid u identitet pretplatnika (BR-143).

---

# 23. Matrica sljedivosti

| TS sekcija | BM | FS / BR | FT | Ostali TS |
|------------|----|---------|----|-----------|
| §1 Pregled | BM-NL-01–03, BM-GL-19 | BR-138–140, BR-144, BR-165 | FT-001 | — |
| §2 Granice | BM-NL-03, BM-NL-08, BM-NL-14, BM-NL-16 | BR-144–146, BR-157, BR-159 | FT-001 | TS-003, TS-004, TS-009, TS-010 |
| §3 Principi | BM-NL-06–13, BM-NL-22–25; PATCH-053 | BR-147–158, BR-166–169 | FT-001 | TS-003 |
| §4 Komponente | BM-NL-07 | BR-148, BR-163 | FT-001 | — |
| §5 Okidači | BM-NL-09, BM-NL-14, BM-NL-17; BM-DG-09 | BR-147, BR-159–160, BR-064 | FT-001 | TS-003, TS-004, TS-010 |
| §6 Pretplata | BM-NL-04, BM-NL-05, BM-NL-12, BM-NL-15 | BR-139–143, BR-149, BR-154–156 | FT-001 | TS-009 |
| §7 Model podataka | BM-NL-04, BM-NL-11, BM-NL-18, BM-NL-21 | BR-142, BR-158, BR-161, BR-164 | FT-001 | TS-001 |
| §8 Lifecycle pretplate | BM-NL-04, BM-NL-05 | BR-141, BR-154 | FT-001 | — |
| §9 Lifecycle poruke | BM-NL-07, BM-NL-20 | BR-148, BR-163 | FT-001 | — |
| §10 Kandidati | BM-NL-09–11, BM-NL-14, BM-NL-17–18 | BR-147, BR-158–161 | FT-001 | TS-003, TS-004 |
| §11 Redovni | BM-NL-06, BM-NL-07, BM-NL-13 | BR-148–153, BR-150 | FT-001 | TS-009 |
| §12 Prioritetni | BM-NL-17–25; BM-DG-09 | BR-160–169; BR-064 | FT-001 | TS-003, TS-004, TS-010 |
| §13 Evidencija dostave | BM-NL-11, BM-NL-18, BM-NL-21–25 | BR-158, BR-161, BR-164, BR-166–169 | FT-001 | — |
| §14 Promjena na čekanju | BM-NL-22–25 | BR-166–169 | FT-001 | — |
| §15 Obrada zadataka | BM-NL-07 | BR-148, BR-163 | FT-001 | — |
| §16 Raspored | BM-NL-07, BM-NL-16 | BR-148, BR-157 | FT-001 | — |
| §17 Validacije | BM-NL-* | BR-139–169 | FT-001 | — |
| §18 Guard | BM-NL-09–13, BM-DG-09 | BR-147–150, BR-064 | FT-001 | TS-003 |
| §19–20 Error / Ponovni pokušaj | — | BR-186 | FT-001 | — |
| §21 Audit događaji | — | BR-184–186 | FT-001 / FT-003 | TS-012 |
| §22 Autorizacija | BM-NL-03, BM-NL-04 | BR-143–144 | FT-001 | TS-010 |
| §26 Legacy | — | — | FT-001 | — |
| §27 Van obuhvata | BM-NL-16 | BR-157 | FT-001 | — |

Terminalnost Otkazan / zabrana republish: BM-DG-09, BR-064, TS-003 v0.1.2, TS-010 v1.0.1, G-NL-08 zatvoren.

---

# 24. Acceptance kriterijumi

AC-NL-01 · Samo verifikovani korisnik može aktivirati pretplatu.  
AC-NL-02 · Anonimni posjetilac ne može koristiti Newsletter.  
AC-NL-03 · Pretplatnik može izabrati sve ili konkretne Organizatore; prazan konkretan izbor ≡ svi.  
AC-NL-04 · Izbor Organizatora ne daje prava niti otkriva pretplatnike Organizatoru/Moderatoru; korisnik može mijenjati izbor.  
AC-NL-05 · Prvo uključivanje samo za Objavljen + budući termin; ne za Nacrt/Na odobrenju/Arhiviran/Otkazan.  
AC-NL-06 · Isti događaj se istom pretplatniku ne dostavlja ponovo kao first_include.  
AC-NL-07 · Prazan Newsletter se ne šalje.  
AC-NL-08 · Prioritet ide samo pretplatnicima sa first_include.  
AC-NL-09 · Ista prioritetna promjena (Kontrolni zapis promjene) se ne dostavlja dvaput.  
AC-NL-10 · Više Promjena na čekanju → jedno obavještenje sa posljednjim stanjem.  
AC-NL-11 · Nema kontradiktornih poruka u istom ciklusu.  
AC-NL-12 · Uređivačke izmjene ne okidaju Newsletter.  
AC-NL-13 · Odjava iz poruke i iz UI deaktivira pretplatu bez brisanja naloga.  
AC-NL-14 · Prva aktivacija šalje potvrdu; nije double opt-in.  
AC-NL-15 · Organizator/Moderator/Urednik ne mogu ručno slati ni birati događaje za Newsletter.  
AC-NL-16 · Ne postoji republish putanja; Otkazan ne vraća događaj u first_include bez novog događaja.  
AC-NL-17 · Audit događaji pokrivaju katalog BR-185; ponovni pokušaji grešaka nisu audit.  
AC-NL-18 · Redovni ciklus nije vezan za fiksni sedmični poslovni model.  
AC-NL-19 · Poruka sadrži odjavu i linkove ka događaju / pregledu Organizatora.  
AC-NL-20 · Okidač se emituje samo nakon trajno sačuvane poslovne promjene; obrada je odvojena od poslovne transakcije.  
AC-NL-21 · Evidencija dostave za više stavki u jednoj poruci je cjelovita.  
AC-NL-22 · Identitet pretplatnika je jedan kanonski izvor istine.  
AC-NL-23 · Legacy implementacija ne smije ostati aktivna paralelno nakon migracije (PRAVILO 5.3.4).

---

# 25. Napomene za implementaciju

1. Ne implementirati fiksni sedmični digest kao ciljni model.
2. Evidenciju dostave upisivati **cjelovito** sa uspješnim send-om (§13.3).
3. Signale vezati na uspješno sačuvane statusne prelaze (§5), ne na UI klikove.
4. Za događaje sa više termina koristiti `occurrence_id` u prioritetnoj Evidenciji.
5. Pri deaktivaciji Organizatora: filter i dalje po `organizer_id`; poslovna pravila o vidljivosti događaja ostaju u TS-001/TS-003.
6. UI pretplate uskladiti sa TS-009; backend ugovor sa ovim TS-om.
7. Konfiguracione ključeve rasporeda dokumentovati u ops/runbook-u, ne u BM.
8. Ne graditi urednički „Newsletter admin” u V1.
9. Potvrda aktivacije ≠ double opt-in gate.
10. Pri migraciji slijediti §26 (PRAVILO 5.3.1–5.3.4).
11. Izbor Organizatora je obavezni V1 (§6.2).

---

# 26. Legacy implementacija

## 26.1 Usvojena pravila

**PRAVILO 5.3.1**

TS-011 definiše ciljnu tehničku arhitekturu Newsletter sistema za verziju V1 i predstavlja izvor istine za njegovu implementaciju. Postojeća implementacija Newsletter funkcionalnosti ne predstavlja normativni izvor i ne može imati prednost u odnosu na usvojena pravila definisana u Business Model-u, Functional Specification-u i ovoj tehničkoj specifikaciji.

**PRAVILO 5.3.2**

Postojeća implementacija Newsletter funkcionalnosti može biti iskorišćena kao osnova za implementaciju, pod uslovom da je u potpunosti usklađena sa pravilima definisanim u Business Model-u, Functional Specification-u i TS-011. Svaki dio postojeće implementacije koji nije usklađen sa usvojenim pravilima mora biti izmijenjen ili zamijenjen.

**PRAVILO 5.3.3**

Migracija postojeće Newsletter funkcionalnosti na model definisan u TS-011 mora obezbijediti očuvanje svih podataka koji su i dalje validni prema usvojenom Business Model-u i Functional Specification-u, uz uklanjanje ili transformaciju podataka i ponašanja koja pripadaju starom modelu. Migracija ne smije proizvesti duple aktivne pretplate, duple evidencije dostave niti izgubiti validne podatke o postojećim pretplatnicima.

**PRAVILO 5.3.4**

Nakon završetka migracije, prethodna implementacija Newsletter funkcionalnosti ne smije ostati aktivna paralelno sa implementacijom definisanom u TS-011. U produkcionom okruženju u svakom trenutku smije biti aktivan isključivo jedan mehanizam za obradu i dostavu Newsletter poruka.

## 26.2 Postojeći legacy model (informativno)

| Aspekt | Legacy |
|--------|--------|
| Publika | E-mail adresa; nije obavezno vezano za verifikovan nalog |
| Tabela | `newsletter_subscribers` |
| Okidač | Fiksni sedmični raspored (ponedjeljak 09:00) |
| Sadržaj | Link ka sedmičnom pregledu; bez liste novoobjavljenih događaja |
| Filter Organizatora | Ne postoji |
| Prioritetne promjene | Ne postoje |
| Evidencija dostave | Ne postoji |
| Obrada | Artisan `cultural-calendar:send-weekly-newsletter` (sinhroni Mail) |

## 26.3 Ciljni model (ovaj TS)

| Aspekt | Cilj |
|--------|------|
| Publika | Verifikovani registrovani korisnik |
| Pretplata | Pretplata + izbor Organizatora |
| Okidač | Objava → first_include; poslovne promjene → prioritet |
| Cadence | Raspored automatske obrade + period objedinjavanja (bez fiksnog sedmičnog BM pravila) |
| Sadržaj | Kratak pregled događaja u e-mailu, grupisan po Organizatoru |
| Evidencija dostave | Obavezno |
| Ručno / kampanje | Nisu V1 (BM-NL-16 / BR-157) |

Ovaj odsjek **ne** mijenja BM ni FS.

---

# 27. Van obuhvata (Out of Scope)

**PRAVILO 5.4.1**

Poglavlje „Van obuhvata (Out of Scope)” definiše funkcionalnosti koje nisu predmet implementacije TS-011 u verziji V1. Funkcionalnosti navedene u ovom poglavlju ne smatraju se dijelom zahtjeva za implementaciju niti se njihovo nepostojanje smatra nedostatkom implementacije.

**PRAVILO 5.4.2**

Sve funkcionalnosti definisane usvojenim Business Model-om i Functional Specification-om predstavljaju obavezni dio implementacije TS-011. Funkcionalnosti koje nisu definisane tim dokumentima smatraju se van obuhvata ove tehničke specifikacije za verziju V1.

Napomena: granice V1 već usvojene u BM-NL-16 / BR-157 (npr. kategorije kao filter, personalizacija, kampanje, ručno slanje, Newsletter po ulozi, interval kao poslovno pravilo) ostaju na snazi kroz PRAVILO 5.4.2 — bez proširenja liste u ovom TS-u.

---

# 28. Otvorena pitanja

Nema otvorenih poslovnih pitanja.

Napomena: način izračunavanja Kontrolnog zapisa promjene, veličine grupa za obradu i tehnologija reda za obradu su **implementacioni** izbori unutar usvojenog BM/FS okvira.

Zatvoreno relevantno za ovaj TS:

* **G-NL-08** — zatvoren terminalnošću Otkazan (PO-DG-07 / PATCH-053); nema republish Newsletter putanje.

---

# Kraj dokumenta
