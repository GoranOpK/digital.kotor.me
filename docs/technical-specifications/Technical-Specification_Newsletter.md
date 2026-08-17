# Digital Kotor
# Technical Specification
## Newsletter

**Feature ID:** FT-001  
**Oznaka dokumenta:** KK-TS-011  
**Funkcionalna cjelina:** Newsletter  
**Modul:** Kalendar kulture  
**Status dokumenta:** USVOJEN  
**Verzija:** 1.0.4
**Datum:** 2026-08-15

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-07 | Prvi nacrt Technical Specification za funkcionalnu cjelinu Newsletter. Usklađen sa BM-13 (BM-NL-01–BM-NL-25), BM-GL-19, FS §5.15 (BR-138–BR-169), FS §5.16 katalog Newsletter (BR-184–BR-186), TS-003 v0.1.2, TS-004, TS-009, TS-010 v1.0.1, Feature Registry (FT-001), METHODOLOGY. Uvažava PO-DG-07 / PATCH-053: Otkazan terminalan; nema republish logike; G-NL-08 zatvoren. Tehnički predlozi (model podataka, obrada zadataka, raspored automatske obrade, evidencija dostave) bez novih poslovnih pravila. Bez izmjene BM/FS/ostalih TS. Bez izmjene implementacije. |
| 1.0.1 | 2026-08-07 | Završni PATCH nacrta prije validation-a: usvojena crnogorska terminologija; Pravila emitovanja okidača; Promjena na čekanju kao normativni dio prioritetnog toka; Evidencija dostavljenih Newsletter poruka (jedan Identitet pretplatnika); Kontrolni zapis promjene; cjelovito evidentiranje dostave; Arhitektura obrade Newsletter zadataka (obrada u grupama, raspodjela, pokazivač, skaliranje, ograničenje brzine); Raspored automatske obrade; Audit događaji; legacy PRAVILO 5.3.1–5.3.4; Van obuhvata PRAVILO 5.4.1–5.4.2. Bez novih BM/FS odluka. Bez izmjene drugih dokumenata. |
| 1.0.2 | 2026-08-14 | **PO-NL-01…PO-NL-22 / BM PATCH-073 / FS PATCH-FS-072:** tehnički ugovor pretplate na `User`; jedna pretplata; `User.email` kao adresa isporuke (nije Newsletter SSOT); režimi `all_events` / `selected_organizers`; „Bez organizatora“; validan izbor; bez confirmation e-mail polja; odjava čisti aktivne preference; deaktivirani Organizator KEEP veze; cascade pri brisanju `User`; delivery eligibility; Manifestacija nije dimenzija pretplate; testni legacy bez backfill-a pretplatnika. Uklonjene stale CURRENT oznake „DRAFT“ / „Nacrt“ iz zaglavlja i statusa poglavlja (istorija verzija 1.0.0/1.0.1 KEEP). Bez izmjene implementacije. |
| 1.0.3 | 2026-08-14 | **NL-03 temporal eligibility + ledger boundary / BM PATCH-074 / FS PATCH-FS-073:** `subscribed_at` = trenutna activation boundary; prva pretplata i reaktivacija nijesu retroaktivne; preference/širenje opsega nijesu retroaktivni; candidate ≠ queued/sent/delivered; `first_include` ledger row samo nakon uspješne isporuke; NL-03 ne piše ledger i ne šalje e-mail. Dokumentovan P1 gap: kanonski timestamp prve objave Event-a i preference effective-time. Bez izmjene implementacije. |
| 1.0.4 | 2026-08-15 | **Status hygiene (V1 closeout):** §26.2 označen kao **ISTORIJSKI / REPLACED**. Current runtime = `cultural-calendar:send-newsletter` + `cultural-calendar:send-newsletter-priority`; legacy weekly = no-op / nije kanonski invoker. Business contract KEEP. |
| — | 2026-08-17 | Administrativna migracija dokumentacionog ID-a na `KK-*` namespace. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |

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
| 1.0.2 | 2026-08-14 | PO-NL-01…22: pretplata na `User`; režimi opsega; „Bez organizatora“; delivery eligibility; bez confirmation e-mail polja; testni legacy bez migracije pretplatnika; cleanup stale DRAFT/Nacrt CURRENT oznaka. |
| 1.0.3 | 2026-08-14 | Temporal eligibility; candidate vs delivery evidence; NL-03 = eligibility/candidate foundation (bez ledger write / bez e-maila); `subscribed_at` kao activation boundary; P1 timestamp gap dokumentovan. |
| 1.0.4 | 2026-08-15 | §26.2 = historical/replaced; current runtime = regular + priority commands; weekly no-op. |
| 2026-08-17 | Administrativna migracija dokumentacionog ID-a na `KK-*` namespace. Poslovni i tehnički sadržaj, status i closeout ostaju nepromijenjeni. |

---

# Svrha dokumenta

Ovaj dokument opisuje kako će se usvojeni Business Model i Functional Specification za funkcionalnu cjelinu **Newsletter** tehnički realizovati u okviru FT-001 – Kalendar kulture.

KK-TS-011 obrađuje jednu logički zaokruženu funkcionalnu cjelinu unutar FT-001 i ne predstavlja kompletnu tehničku specifikaciju svih cjelina Feature-a FT-001.

Dokument:

* ne uvodi nova poslovna pravila;
* ne zamjenjuje Business Model niti Functional Specification;
* nije Technical Overview trenutne implementacije;
* nije Change Request;
* ne definiše SQL, migracije, Laravel kod niti konkretne API ugovore;
* predlaže tehnički model (entiteti, zadaci, raspored automatske obrade) kao operacionalizaciju usvojenih BM/FS pravila.

Izvori istine za poslovna pravila:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-13 BM-NL-01–BM-NL-47; BM-GL-19; BM-GL-27; BM PATCH-031–033; PATCH-073 / PO-NL-01…22; PATCH-074; usklađenost sa PATCH-053 / PO-DG-07)
* `docs/functional-specifications/Functional-Specification.md` (§5.15 BR-138–BR-169, BR-328–BR-348; §5.16 katalog Newsletter / BR-184–BR-186; PATCH-FS-031–034; PATCH-FS-072; PATCH-FS-073; usklađenost sa PATCH-FS-053)
* `docs/features/Feature-Registry.md` (FT-001 — Newsletter)
* `docs/METHODOLOGY.md` (M-TS-001–M-TS-005)
* `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (KK-TS-003 v0.1.2)
* `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (KK-TS-004)
* `docs/technical-specifications/Technical-Specification_Javni_portal.md` (KK-TS-009)
* `docs/technical-specifications/Technical-Specification_Urednicki_portal.md` (KK-TS-010 v1.0.1)

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | USVOJENO |
| 2. Granice odgovornosti | USVOJENO |
| 3. Arhitektonski principi | USVOJENO |
| 4. Komponente | USVOJENO |
| 5. Pravila emitovanja okidača | USVOJENO |
| 6. Model pretplate | USVOJENO |
| 7. Model podataka | USVOJENO |
| 8. Lifecycle pretplate | USVOJENO |
| 9. Lifecycle Newsletter poruke | USVOJENO |
| 10. Kandidati za slanje | USVOJENO |
| 11. Redovni Newsletter | USVOJENO |
| 12. Prioritetni Newsletter | USVOJENO |
| 13. Evidencija dostavljenih Newsletter poruka | USVOJENO |
| 14. Promjena na čekanju | USVOJENO |
| 15. Arhitektura obrade Newsletter zadataka | USVOJENO |
| 16. Raspored automatske obrade | USVOJENO |
| 17. Validacije | USVOJENO |
| 18. Guard uslovi | USVOJENO |
| 19. Error handling | USVOJENO |
| 20. Ponovni pokušaj | USVOJENO |
| 21. Audit događaji | USVOJENO |
| 22. Autorizacija | USVOJENO |
| 23. Matrica sljedivosti | USVOJENO |
| 24. Acceptance kriterijumi | USVOJENO |
| 25. Napomene za implementaciju | USVOJENO |
| 26. Legacy implementacija | USVOJENO |
| 27. Van obuhvata (Out of Scope) | USVOJENO |
| 28. Otvorena pitanja | USVOJENO |

---

# Pravila upravljanja ovim dokumentom

1. KK-TS-011 pripada FT-001 – Kalendar kulture.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz Technical Specification.
4. Sve što nije definisano u BM ili FS, a zahtijeva poslovnu odluku, evidentira se kao **Otvoreno pitanje**.
5. Tehnički predlozi (interval rasporeda automatske obrade, period objedinjavanja, imena komponenti, polja evidencije dostave) nisu poslovna pravila o učestalosti niti o sadržaju Newslettera.
6. Product Owner donosi poslovne odluke; ovaj dokument ih ne pretpostavlja.
7. Izmjene usvojenog sadržaja u narednim verzijama evidentiraju se novim redom u istoriji verzija.
8. **Otkazan** je terminalan za povratak u **Objavljen** (PO-DG-07 / PATCH-053). KK-TS-011 **ne smije** sadržati logiku za republish / reaktivaciju otkazanog događaja. G-NL-08 je zatvoren.

---

# 1. Pregled funkcionalne cjeline

Izvori

Business Model:
- BM-NL-01–BM-NL-47
- BM-GL-19
- BM-GL-27

Functional Specification:
- §5.15 (BR-138–BR-169, BR-328–BR-348)
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

Obuhvat KK-TS-011:

1. obuhvat i granice odgovornosti modula Newsletter;
2. arhitektura i komponente;
3. pravila emitovanja okidača;
4. model pretplate (`User`, režimi opsega, „Bez organizatora“, aktivna pretplata, odjava, reaktivacija, delivery eligibility);
5. konceptualni model podataka (pretplata na `User`, režim opsega, izbor Organizatora, izbor „Bez organizatora“, Evidencija dostavljenih Newsletter poruka, Promjena na čekanju, token odjave, relacije);
6. lifecycle pretplate i Newsletter poruke;
7. kandidati za slanje; redovni i prioritetni Newsletter;
8. Evidencija dostavljenih Newsletter poruka (prvo uključivanje, zaštita od duplikata, posljednje stanje, agregacija, kontradiktorne poruke, cjelovito evidentiranje);
9. Promjena na čekanju (normativni dio prioritetnog toka);
10. Arhitektura obrade Newsletter zadataka i Raspored automatske obrade;
11. validacije, guard uslovi, error handling, ponovni pokušaj;
12. Audit događaji ka KK-TS-012;
13. autorizacija, sljedivost, acceptance, implementacione napomene, legacy implementacija.

Van obuhvata ovog dokumenta: vidi §27 (PRAVILO 5.4.1–5.4.2).

## 1.3 Zavisnosti

| Zavisnost | Uloga u odnosu na KK-TS-011 |
|-----------|---------------------------|
| Platforma Digital Kotor – korisnički nalozi | Identitet pretplatnika (`User`); aktuelni `User.email`; verifikacija; aktivnost naloga |
| KK-TS-001 Organizator | Filter izbora Organizatora; veza događaja → registrovani Organizator; deaktivacija Organizatora |
| KK-TS-003 Događaj | Status **Objavljen** / **Otkazan** / **Arhiviran**; okidač objave; terminalnost Otkazan; `organizer_id` vs ručni naziv |
| KK-TS-005 Manifestacija | Van dimenzije pretplate u V1; Događaj se selektuje po sopstvenom Organizator kriterijumu |
| KK-TS-004 Održavanje | Budući termini; **Odgođen**; otkaz termina; promjena datuma/vremena/lokacije |
| KK-TS-009 Javni portal | UI pretplate / odjave; linkovi ka detaljima događaja i pregledu Organizatora |
| KK-TS-010 Urednički portal | Izvori okidača (objava, otkaz, odlaganje, izmjene termina/lokacije); bez upravljanja pretplatnicima |
| KK-TS-012 Evidencija aktivnosti | Prima Audit događaje iz kataloga Newsletter |
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

* vodi pretplate registrovanih i verifikovanih korisnika (jedna pretplata po `User`);
* čuva opseg preferenci V1 (režim „Svi događaji“ ili „Odabrani organizatori“, uključujući „Bez organizatora“; BM-NL-04, BM-NL-27–BM-NL-29, BR-142, BR-328–BR-330);
* periodično priprema i šalje redovni Newsletter o novoobjavljenim događajima;
* priprema i šalje prioritetna obavještenja o poslovno značajnim promjenama;
* vodi Evidenciju dostavljenih Newsletter poruka radi zaštite od duplikata;
* emituje Audit događaje ka centralnoj Evidenciji (KK-TS-012).

## 2.2 Šta Newsletter ne radi

* ne upravlja statusima događaja niti održavanja;
* ne učestvuje u uredničkom workflow-u;
* ne šalje poslovna obavještenja Uredničkog portala (BR-128);
* ne omogućava Organizatoru, Moderatoru ni Uredniku upravljanje pretplatnicima ni ručno slanje;
* ne uvodi kategorije, personalizaciju, preporuke, kampanje ni Newsletter po ulozi (granice usvojene u BM-NL-16 / BR-157);
* **ne sadrži logiku republish-a** (nema putanje Otkazan → Objavljen; novi program = novi događaj, KK-TS-003 / PATCH-053);
* ne mijenja prava korisnika ni poslovne procese zbog pretplate (BM-NL-08, BR-145).

## 2.3 Granica prema KK-TS-003 / KK-TS-004 / KK-TS-010

| Izvor | Okidač za Newsletter | Napomena |
|-------|----------------------|----------|
| KK-TS-010 / KK-TS-003 | Prelaz u **Objavljen** (odobrenje / direktna objava) | Kandidat za **prvo uključivanje** |
| KK-TS-010 / KK-TS-003 | Prelaz u **Otkazan** | Prioritetno obavještenje **samo** pretplatnicima sa zapisom prvog uključivanja u Evidenciji dostavljenih Newsletter poruka; terminalan status — nema kasnijeg republish okidača |
| KK-TS-004 | **Odgođen**; povratak sa novim terminom; promjena datuma/vremena/lokacije; otkaz pojedinačnog održavanja | Prioritetno obavještenje; scope = termin ili kompletan događaj (BR-162) |
| KK-TS-003 | Prelaz u **Arhiviran** | Nije okidač prvog uključivanja; nije prioritetni okidač po BM-NL-17 |
| KK-TS-010 | Uređivačke izmjene opisa/fotografija | **Nisu** okidač (BR-159) |

## 2.4 Granica prema KK-TS-009

KK-TS-009 obezbjeđuje UI površinu za pretplatu / odjavu / izbor Organizatora na javnom portalu. KK-TS-011 definiše poslovno-tehnička pravila i backend ponašanje; UI detalji ostaju u KK-TS-009 / implementaciji portala.

---

# 3. Arhitektonski principi

1. **BM/FS su izvor istine** — KK-TS-011 ih operacionalizuje, ne proširuje.
2. **Automatski sadržaj** — Sistem bira događaje; nema ručnog izbora (BR-146).
3. **Dva kanala isporuke** — redovni (periodična agregacija novoobjavljenih) i prioritetni (blagovremena promjena).
4. **Jedan e-mail po pretplatniku po ciklusu** — objedinjavanje po pravilima BM-NL-06 / BR-153; prioritetna mogu biti objedinjena uz blagovremenost (BM-NL-24).
5. **Evidencija dostavljenih Newsletter poruka je izvor istine o „već dostavljeno“** — upisuje se samo nakon uspješne e-mail isporuke. Nije registracija kandidata, nije pending i nije pre-send reservation.
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
| **Emiter Audit događaja** | Emisija ka KK-TS-012 |

---

# 5. Pravila emitovanja okidača

## 5.1 Norma

1. Newsletter dobija interni signal **isključivo** nakon uspješno završene i **trajno sačuvane** poslovne promjene.
2. Signal predstavlja **potvrđenu poslovnu promjenu**, a ne korisničku UI akciju.
3. Ako poslovna promjena nije uspješno sačuvana, Newsletter okidač se **ne emituje**.
4. Obrada Newslettera odvija se **odvojeno** od osnovne poslovne transakcije (KK-TS-003 / KK-TS-004 / KK-TS-010 ne čekaju završetak Newsletter obrade).
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

* Pretplata pripada `User` nalogu (BM-NL-39, BR-338). Aktuelni `User.email` je adresa za isporuku.
* Ne vodi se zasebna Newsletter kopija e-mail adrese kao nezavisni izvor istine.
* Anonimni / neprijavljeni posjetilac **nema** pristup pretplati.
* Jedan `User` ↔ najviše jedna Newsletter pretplata (BM-NL-26, BR-328).
* Aktivacija zahtijeva prijavljenog korisnika sa verifikovanom aktuelnom e-mail adresom (BM-NL-31, BR-332).

## 6.2 Opseg pretplate (obavezni V1)

U skladu sa BM-NL-27–BM-NL-29 i BR-142, BR-329–BR-330:

* Režim `all_events` („Svi događaji“): dinamički filter. **Ne** zahtijeva pivot svih Organizatora. Obuhvata događaje svih postojećih i budućih registrovanih Organizatora i grupu „Bez organizatora“.
* Režim `selected_organizers` („Odabrani organizatori“): pivot/relacija prema `CulturalOrganizer` za izabrane Organizatore **i/ili** eksplicitna preferenca `include_without_organizer` („Bez organizatora“).
* Prazan selektivni izbor **nije** validan i **nije** ekvivalent „Svi događaji“.
* Izbor je **isključivo filter sadržaja**.
* Novi izbor važi od uspješnog čuvanja nadalje. Promjena preference **ne proizvodi** retroaktivni `first_include` (BM-NL-33, BM-NL-34, BR-341, BR-346).
* Ako korisnik tek od čuvanja počne pratiti Organizatora, uključi „Bez organizatora“ ili pređe na `all_events`, ranije objavljeni Događaji koji nijesu pripadali prethodnom izboru **ne** postaju `first_include` kandidati.
* Promjena režima briše aktivne preference prethodnog režima; ne vraća automatski raniji selektivni izbor (BM-NL-34, BR-334).

## 6.3 „Bez organizatora“

Tehnički ugovor (BM-NL-28, BR-329):

* Uključeno: Događaj **bez** kanonske veze na `CulturalOrganizer` (`organizer_id` null), uključujući Događaj sa samo ručnim nazivom neregistrovanog Organizatora.
* Nije uključeno: Događaj sa registrovanim `CulturalOrganizer`.
* Ručni naziv nije virtualni Organizator i nije zaseban Newsletter izvor.

## 6.4 Aktivna pretplata vs dozvoljena isporuka

Aktivna pretplata (BM-NL-12, BR-149):

* pretplata u statusu **aktivna**;
* korisnik nije izvršio odjavu.

Dozvoljena isporuka (BM-NL-42):

* aktivna pretplata; **i**
* `User` nalog aktivan; **i**
* aktuelni `User.email` verifikovan.

Postojanje kandidata za slanje **nije** dio definicije aktivne pretplate.

## 6.5 Odjava

* Dostupna iz Newsletter poruke (token link) i iz UI akcijom „Odjavi se“ (BR-154, BR-155).
* Prije izvršenja: jednostavna potvrda. Bez ponovnog unosa lozinke. Bez e-mail confirmationa.
* Tehnički: status odjavljena; `unsubscribed_at`; uklanjanje aktivnih preferenci (pivot Organizatora i `include_without_organizer`); zapis pretplate **ostaje**; rotacija/invalidacija tokena odjave po potrebi.
* Evidencija dostave se ne briše odjavom.

## 6.6 Reaktivacija pretplate

* Koristi se postojeći zapis pretplate (BM-NL-36, BR-335).
* Prethodne preference se **ne** vraćaju.
* Korisnik mora napraviti novi kompletan validan izbor, kao kod prve pretplate.
* Reaktivacija **ne proizvodi** retroaktivni `first_include` (BM-NL-46, BR-347). Događaj objavljen prije trenutka reaktivacije nije `first_include` kandidat zbog reaktivacije.
* Reaktivacija postavlja novu vremensku granicu: `subscribed_at` se postavlja na trenutak reaktivacije i postaje trenutna activation boundary za buduću eligibility.
* Formulacija „ne zahtijeva retroaktivno slanje“ **nije** dovoljna: retroaktivni `first_include` nije dozvoljen.

## 6.7 Potvrda aktivacije

* Potvrda je **isključivo** poruka u aplikaciji (BM-NL-15, BM-NL-43, BR-156).
* Nema dodatnog e-mail confirmation linka. Nema obavezne servisne e-mail poruke.
* Nije double opt-in; double opt-in nije V1.
* Polja `confirmation_sent_at` i `first_activated_at` **nisu** dio kanonskog modela (nema poslovne potrebe).

## 6.8 Deaktivirani Organizator

* Veza pretplata → Organizator se **ne** briše kada Organizator postane neaktivan (BM-NL-37, BR-336).
* Neaktivni Organizator nije aktivan izvor dok je neaktivan.
* Reaktivacija Organizatora ponovo aktivira sačuvanu preferencu.

## 6.9 Manifestacija

* Manifestacija **nije** dimenzija pretplate u V1 (BM-NL-38, BR-337).
* Nema entiteta/preference „Prati Manifestaciju“.

## 6.10 `User` lifecycle

* Promjena e-maila: pretplata i preference ostaju; isporuka blokirana dok novi e-mail nije verifikovan.
* Deaktivacija naloga: nije odjava; pretplata i preference ostaju; isporuka blokirana.
* Trajno brisanje `User`: cascade uklanja pretplatu, aktivne preference i veze prema Organizatorima. Nema orphan pretplate.

## 6.11 Vremenska eligibility (first_include)

Za `first_include` eligibility:

* `subscribed_at` je **trenutna activation boundary** (prva aktivacija i svaka reaktivacija). Ne uvodi se `activated_at` / `reactivated_at`.
* Ako je trenutak relevantne prve objave Event-a **prije** trenutne activation boundary → Event **nije** `first_include` kandidat za tu pretplatu. Event se ne briše; to je recipient eligibility.
* Ako je trenutak relevantne prve objave Event-a **≥** trenutne activation boundary → Event **može** biti kandidat ako ispunjava ostale uslove.
* Preference i širenje opsega važe od uspješnog čuvanja nadalje (§6.2). Ne uvodi se `preference_effective_at` u ovom ugovoru.
* **NL-03 TECHNICAL IMPLEMENTATION QUESTION:** postojeći model nema pouzdan per-preference timestamp za query „kada je ova preferenca postala efektivna“. Poslovno pravilo ostaje; tehnički datum se ne izmišlja ovdje. Vidi §25.

### Matrica: nema retroaktivnog first_include

| Scenario | Raniji Event postaje first_include kandidat? |
|----------|-----------------------------------------------|
| Prva pretplata nakon objave Event-a | NE |
| Organizator dodat nakon objave Event-a | NE |
| „Bez organizatora“ uključeno nakon objave | NE |
| `selected_organizers` → `all_events` nakon objave | NE |
| Reaktivacija nakon objave | NE |
| Event objavljen nakon trenutne granice aktivacije / čuvanja preferenci i ostali uslovi PASS | DA |

---

# 7. Model podataka

Konceptualni model (bez SQL / migracija). Imena su predlog za implementaciju. Svako polje ima BM/FS sljedivost.

## 7.1 Entitet: Newsletter pretplata

| Atribut (konceptualno) | Odluka | Obrazloženje / sljedivost |
|------------------------|--------|---------------------------|
| `id` | KEEP | Identifikator pretplate |
| Identitet pretplatnika (`user_id`, unikatno) | KEEP | BM-NL-26, BM-NL-39 |
| `status` aktivna \| odjavljena | KEEP | BM-NL-12, BM-NL-35 |
| `scope_mode` `all_events` \| `selected_organizers` | CHANGE (umjesto `all_organizers`) | BM-NL-27, BR-142 |
| `include_without_organizer` | KEEP (novo, samo uz selektivni režim) | BM-NL-28, BM-NL-29; u `all_events` je implicitno obuhvaćeno dinamičkim opsegom i **ne** zahtijeva zasebno čuvanje kao aktivnu selektivnu preferencu |
| `subscribed_at` | KEEP | Trenutna activation boundary: vrijeme posljednje aktivacije / reaktivacije. Koristi se za first_include temporal eligibility (BM-NL-45, BM-NL-46). |
| `unsubscribed_at` | KEEP | Vrijeme odjave (nullable) |
| `first_activated_at` | REMOVE | Bilo vezano za confirmation e-mail; nema kanonske potrebe (BM-NL-43) |
| `confirmation_sent_at` | REMOVE | Nema confirmation e-maila (BM-NL-15, BM-NL-43, BR-156) |
| Newsletter `email` kolona | REMOVE | SSOT je `User.email` (BM-NL-39) |
| Token odjave | KEEP | BR-155 odjava iz poruke |
| `created_at` / `updated_at` | KEEP | Tehnički tragovi |

Kada je `scope_mode = all_events`, pivot Organizatora je prazan; filter se **ne** materijalizuje kao skup svih `organizer_id`.

Kada je `scope_mode = selected_organizers`, mora postojati najmanje jedan `organizer_id` **ili** `include_without_organizer = true`.

## 7.2 Entitet: Izbor Organizatora

| Atribut | Opis |
|---------|------|
| Veza na pretplatu | FK pretplate |
| `organizer_id` | Izabrani `CulturalOrganizer` |
| unikatan par | (pretplata, Organizator) |

Pravilo: deaktivacija Organizatora **ne** briše ovaj zapis.

Pri odjavi i pri prelasku na `all_events` aktivni zapisi se uklanjaju.

## 7.3 Entitet: Evidencija dostavljenih Newsletter poruka

Evidencija uspješno dostavljenih stavki pretplatniku. Služi BM-NL-11, BM-NL-18, BM-NL-21.

Pitanje zadržavanja istorijskih zapisa nakon trajnog brisanja `User` **nije** predmet PO-NL-18; ostaje uz delivery ledger model (DEFER detalj retention-a).

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

**Norma:** ne modelirati dva konkurentna identifikatora pretplatnika. Identitet pretplatnika je jedan. Ne kopirati e-mail u evidenciju kao SSOT.

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
User 1 ── 0..1 Newsletter pretplata
Newsletter pretplata 1 ── * Izbor Organizatora ── 1 CulturalOrganizer
Newsletter pretplata 1 ── * Evidencija dostavljenih Newsletter poruka ── 1 Događaj
Evidencija dostavljenih Newsletter poruka 0..1 ── Održavanje (KK-TS-004)
Događaj / Održavanje ── * Promjena na čekanju
```

Trajno brisanje `User` uklanja pretplatu i aktivne preference (cascade). Nema orphan pretplate.

## 7.6 Token odjave

* Generiše se pri aktivaciji / rotira pri reaktivaciji po potrebi.
* Omogućava odjavu bez aktivne sesije (link u e-mailu), uz jednostavnu potvrdu.
* Mora biti dovoljno entropije; ne smije biti predvidiv.
* Nakon uspješne odjave token se invalidira ili zamjenjuje.

## 7.7 Odluke o poljima (sažetak)

| Polje / pretpostavka | CURRENT NEED | Odluka | Obrazloženje |
|----------------------|--------------|--------|--------------|
| `user_id` unikatno | Da | KEEP | Pretplata pripada `User` |
| `status` / `subscribed_at` / `unsubscribed_at` | Da | KEEP | Aktivna vs odjavljena pretplata |
| `scope_mode` | Da | CHANGE | Zamjenjuje `all_organizers` boolean semantiku praznog skupa |
| `include_without_organizer` | Da (selektivni režim) | KEEP | BM-NL-28/29 |
| Pivot `CulturalOrganizer` | Da (selektivni režim) | KEEP | Ne za `all_events` |
| `confirmation_sent_at` | Ne | REMOVE | Nema servisnog confirmation e-maila |
| `first_activated_at` | Ne | REMOVE | Nema kanonske potrebe bez confirmation e-maila |
| Newsletter `email` kolona | Ne | REMOVE | SSOT = `User.email` |
| Token odjave | Da | KEEP | Odjava iz poruke (BR-155) |
| Fiksni sedmični raspored | Ne | REMOVE (poslovno) | BM-NL-07; legacy command REPLACE |
| Migracija testnih pretplatnika | Ne | REMOVE | BM-NL-44, PO-NL-22 |
| `manifestation_id` preference | Ne | DEFER / van V1 | BM-NL-38 |

---

# 8. Lifecycle pretplate

```
[nema pretplate]
       │ aktivacija (prijavljen + verifikovan e-mail + validan izbor + „Pretplati se“)
       ▼
   Active  ←──────────────┐
       │ odjava           │ reaktivacija (novi kompletan izbor; isti zapis)
       ▼                  │
 Unsubscribed ────────────┘
```

| Stanje pretplate | Smije primati Newsletter | Napomena |
|------------------|--------------------------|----------|
| Nema zapisa (nikad pretplaćen) | Ne | Nije pretplatnik |
| Active + dozvoljena isporuka + relevantan sadržaj | Da | Jedini slučaj slanja |
| Active + dozvoljena isporuka + nema sadržaja | Ne | Pretplata ostaje aktivna; e-mail se ne šalje |
| Active + isporuka blokirana (`User`/e-mail) | Ne | Pretplata i preference ostaju |
| Unsubscribed | Ne | Preference očišćene; zapis ostaje |
| Trajno obrisan `User` | Ne | Pretplata ne postoji |

Invariant:

* odjava ne briše Evidenciju dostavljenih Newsletter poruka;
* odjava briše aktivne preference;
* izmjena preferenci ne briše Evidenciju dostave i ne proizvodi retroaktivni `first_include`;
* reaktivacija ne vraća stare preference i ne proizvodi retroaktivni `first_include`.

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

## 10.0 Candidate / eligibility vs delivery evidence

Sistem razlikuje:

**A. CANDIDATE / ELIGIBILITY**

Event je `first_include` kandidat ako u trenutku razrješavanja ispunjava uslove da bude razmatran za prvo uključivanje u narednom redovnom ciklusu.

Kandidat **nije**: queued, sent, delivered. Kandidat **nije** ledger red. NL-03 **ne** kreira `first_include` ledger zapis, **ne** postavlja `sent_at`, **ne** šalje e-mail i **ne** uvodi pending/registered/candidate tabelu.

**B. DELIVERY EVIDENCE**

`first_include` red u Evidenciji dostavljenih Newsletter poruka nastaje **samo** nakon uspješne e-mail isporuke (§13). `sent_at` = vrijeme uspješne isporuke.

Ledger služi za istorijsku evidenciju i deduplikaciju budućih `first_include` slanja. Ne služi za candidate registration, pending state ni pre-send reservation, osim ako kasniji delivery design eksplicitno uvede zaseban ugovor.

## 10.1 Kandidat za prvo uključivanje

Event može biti `first_include` kandidat samo ako **istovremeno**:

1. Event ispunjava kanonske Event uslove: status = **Objavljen**; javno dostupan po pravilima portala; ≥ 1 buduće relevantno održavanje u trenutku pripreme (BM-NL-09, BM-NL-10, BR-147);
2. pretplata / `User` imaju dozvoljenu isporuku (§6.4): pretplata aktivna; `User` aktivan; aktuelni `User.email` verifikovan;
3. Event odgovara trenutnom opsegu pretplate (`all_events` ili selektivni izbor, uključujući „Bez organizatora“); neaktivni Organizator nije aktivan izvor;
4. temporal eligibility PASS (§6.11): trenutak relevantne prve objave Event-a nije prije trenutne activation boundary (`subscribed_at`) niti prije trenutka od kojeg važi relevantna preferenca;
5. za pretplatu + Event **ne postoji** prethodno uspješno dostavljen `first_include` ledger zapis.

Postojanje `first_include` ledger zapisa = ALREADY DELIVERED → **nije** kandidat.

Nepostojanje ledger zapisa samo po sebi **ne** znači da je Event kandidat.

**Nisu** kandidati za prvo uključivanje: Nacrt, Na odobrenju, Arhiviran, **Otkazan**.

Napomena (BR-114 vs BR-147): javni portal može prikazati otkazane događaje; to **ne** proširuje prvo uključivanje. Prioritetno obavještenje o otkazu je zaseban tip i **nije** dio NL-03.

## 10.2 Kandidat za prioritetno obavještenje

Promjena je kandidat ako:

1. tip je iz BM-NL-17 / BR-160 (otkaz događaja, odlaganje, promjena datuma/vremena/lokacije; uključujući otkaz termina po KK-TS-004);
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

Događaj ne mora biti poslat u trenutku objave; postaje kandidat za naredni redovni ciklus samo ako ispunjava §10.1, uključujući temporal eligibility. „Još nije poslat” ne čini Event automatski kandidatom.

---

# 12. Prioritetni Newsletter

## 12.1 Svrha

Blagovremeno informisanje pretplatnika kojima je događaj **već** bio dostavljen o poslovno značajnim promjenama (BM-NL-17–20).

## 12.2 Izvori okidača (usklađenost sa KK-TS-003 / KK-TS-004 / KK-TS-010)

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

Nije centralna Evidencija aktivnosti (KK-TS-012). Nije tehnički log platforme za elektronsku poštu.

## 13.2 Prvo uključivanje i zaštita od duplikata

* Upis `entry_type = first_include` **tek nakon uspješne isporuke** e-maila, u okviru **cjelovite** operacije (§13.3). NL-03 **ne** piše ovaj red.
* `sent_at` = vrijeme uspješne isporuke. Ne postavlja se pri eligibility.
* Ključ: (Identitet pretplatnika, događaj, first_include).
* Sprečava ponovno slanje istog događaja kao novoobjavljenog pri narednim ciklusima (BM-NL-11, BR-158).
* Ledger **nije** candidate registration / pending / pre-send reservation.

| Pravilo | Mehanizam |
|---------|-----------|
| BM-NL-11 / BR-158 | Unikat `first_include` po (Identitet pretplatnika, događaj) |
| BM-NL-21 / BR-164 | Unikat `priority_change` po (Identitet pretplatnika, događaj, održavanje?, Kontrolni zapis promjene) |
| BM-NL-18 | Prioritet samo ako postoji `first_include` |

### Kontrolni zapis promjene

**Kontrolni zapis promjene** služi za:

* prepoznavanje već obrađenih poslovno značajnih promjena;
* sprječavanje duplih i kontradiktornih Newsletter poruka.

Način njegovog izračunavanja **nije** predmet KK-TS-011 i ostaje implementacioni detalj.

## 13.3 Cjelovito evidentiranje dostave

Kada Newsletter poruka sadrži više događaja ili više stavki:

1. Evidentiranje dostave mora biti **cjelovita operacija**.
2. Sistem **ne smije** ostaviti djelimično evidentirane stavke iste Newsletter poruke.
3. Kod neuspjeha sistem mora zadržati stanje pogodno za **bezbjedan ponovni pokušaj**.
4. Ne smije nastati situacija da dio poruke izgleda kao dostavljen, a dio kao nedostavljen, bez jasnog tehničkog statusa.

Tehnička realizacija (transakcija, outbox i sl.) je implementacioni detalj unutar ove norme.

## 13.4 Posljednje stanje i agregacija

* Sastavljač poruke čita **trenutno** poslovno stanje događaja/održavanja iz KK-TS-003/KK-TS-004 u trenutku pripreme (BM-NL-23).
* Agregacija prioritetnih promjena ide preko Promjene na čekanju; u Evidenciju ulazi Kontrolni zapis **konačnog** dostavljenog stanja.

## 13.5 Kontradiktorne poruke

* Zabrana na nivou ciklusa: jedan finalni zapis po događaju/terminu po pretplatniku po ciklusu.
* Primjer: odlaganje pa otkaz prije flush-a → šalje se samo otkaz (konačno stanje).

## 13.6 Odjava i Evidencija

* Evidencija se ne briše pri odjavi.
* Pri reaktivaciji ne re-šalju se `first_include` stavke za već zabilježene događaje (BM-NL-11 / BM-NL-18).
* Reaktivacija **takođe** ne proizvodi `first_include` kandidate za Event-e objavljene prije novog `subscribed_at`, čak i ako ledger red ne postoji (BM-NL-46).

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

BM-NL-07 i BR-157 **namjerno ne** definišu interval. KK-TS-011 predlaže tehničko rješenje **bez** uvođenja poslovnog pravila o učestalosti.

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
| V-NL-01 | Korisnik mora biti autentifikovan; aktuelni e-mail verifikovan | Aktivacija / izmjena / reaktivacija (UI) |
| V-NL-02 | U selektivnom režimu Organizator u selekciji mora postojati | Izbor Organizatora |
| V-NL-03 | Ne aktivirati duplu pretplatu za isti `User`; reaktivacija koristi isti zapis | Aktivacija / reaktivacija |
| V-NL-04 | Token odjave mora biti validan i aktivan | Odjava iz e-maila |
| V-NL-05 | Kandidat first_include: Objavljen + javno vidljiv + budući termin + opseg + temporal eligibility + nema uspješno dostavljen first_include ledger | Redovni resolve |
| V-NL-06 | Kandidat priority: postoji first_include + dozvoljena vrsta promjene | Prioritetni resolve |
| V-NL-07 | Poruka mora sadržati link odjave | Sastavljanje |
| V-NL-08 | Prazan sadržaj → skip | Sastavljanje |
| V-NL-09 | `scope_mode` mora biti `all_events` ili `selected_organizers`; selektivni režim zahtijeva ≥1 Organizatora i/ili `include_without_organizer` | Aktivacija / čuvanje preferenci |
| V-NL-10 | Prazan selektivni izbor se ne čuva i nije odjava | Čuvanje preferenci |
| V-NL-11 | Odjava UI zahtijeva jednostavnu potvrdu | Odjava |
| V-NL-12 | Dozvoljena isporuka: aktivan `User` + verifikovan `User.email` | Slanje |
| V-NL-13 | Temporal eligibility: Event publication ≥ current activation / relevant preference boundary | Redovni first_include resolve |

---

# 18. Guard uslovi

Redoslijed guard-ova pri slanju (po pretplatniku):

1. Pretplata aktivna.
2. Dozvoljena isporuka: `User` aktivan i aktuelni e-mail verifikovan.
3. Filter opsega (`all_events` ili selektivni izbor uključujući „Bez organizatora“); neaktivni Organizator nije aktivan izvor.
4. Za redovni: Event ispunjava §10.1 (uključujući temporal eligibility); nema uspješno dostavljen `first_include`.
5. Za prioritetni: postoji `first_include`; nema isti Kontrolni zapis promjene; efektivno stanje nije zamijenjeno novijim.
6. Non-empty payload.
7. Uspješan send → cjelovita Evidencija dostave → Audit događaj.

Guard „nema republish”:

* Prijem okidača **ignoriše** signal tipa „Otkazan → Objavljen” (takav prelaz nije dozvoljen u KK-TS-003 / PATCH-053).
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

## 21.1 Razgraničenje KK-TS-011 / KK-TS-012

* KK-TS-011 **emituje** Audit događaje za aktivnosti definisane BM i FS (katalog Newsletter).
* KK-TS-011 **ne** određuje: skladištenje, prikaz, pretragu, retenciju, administraciju audit podataka.
* To pripada **KK-TS-012**.
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
| Aktivacija / odjava / reaktivacija / izbor opsega | Vlasnik naloga (prijavljen; za aktivaciju/izmjenu: verifikovan e-mail) |
| Odjava putem tokena | Posjednik validnog tokena (bez obavezne sesije) |
| Pregled / upravljanje tuđim pretplatama | **Niko** u V1 (ni Organizator, ni Moderator, ni Urednik) |
| Ručno slanje / izbor događaja za Newsletter | **Nije dozvoljeno** |
| Pokretanje rasporeda / zadataka | Sistem / ops infrastruktura |
| Čitanje centralne evidencije Newsletter audita | Administrator platforme (preko KK-TS-012) |

Organizator i Moderator **nemaju** uvid u identitet pretplatnika (BR-143).

---

# 23. Matrica sljedivosti

| TS sekcija | BM | FS / BR | FT | Ostali TS |
|------------|----|---------|----|-----------|
| §1 Pregled | BM-NL-01–03, BM-GL-19 | BR-138–140, BR-144, BR-165 | FT-001 | — |
| §2 Granice | BM-NL-03, BM-NL-08, BM-NL-14, BM-NL-16 | BR-144–146, BR-157, BR-159 | FT-001 | KK-TS-003, KK-TS-004, KK-TS-009, KK-TS-010 |
| §3 Principi | BM-NL-06–13, BM-NL-22–25, BM-NL-42; PATCH-053 | BR-147–158, BR-166–169, BR-149 | FT-001 | KK-TS-003 |
| §4 Komponente | BM-NL-07 | BR-148, BR-163 | FT-001 | — |
| §5 Okidači | BM-NL-09, BM-NL-14, BM-NL-17; BM-DG-09 | BR-147, BR-159–160, BR-064 | FT-001 | KK-TS-003, KK-TS-004, KK-TS-010 |
| §6 Pretplata | BM-NL-04, BM-NL-05, BM-NL-12, BM-NL-15, BM-NL-26–BM-NL-47 | BR-139–143, BR-149, BR-154–156, BR-328–BR-348 | FT-001 | KK-TS-001, KK-TS-009 |
| §7 Model podataka | BM-NL-04, BM-NL-26–BM-NL-29, BM-NL-39, BM-NL-41 | BR-142, BR-328–BR-330, BR-338, BR-340 | FT-001 | KK-TS-001 |
| §8 Lifecycle pretplate | BM-NL-04, BM-NL-05, BM-NL-35, BM-NL-36, BM-NL-42, BM-NL-45–BM-NL-46 | BR-141, BR-154, BR-335, BR-345–BR-347 | FT-001 | — |
| §9 Lifecycle poruke | BM-NL-07, BM-NL-20 | BR-148, BR-163 | FT-001 | — |
| §10 Kandidati | BM-NL-09–11, BM-NL-14, BM-NL-17–18, BM-NL-33–34, BM-NL-45–BM-NL-47 | BR-147, BR-158–161, BR-341, BR-345–BR-348 | FT-001 | KK-TS-003, KK-TS-004 |
| §11 Redovni | BM-NL-06, BM-NL-07, BM-NL-13 | BR-148–153, BR-150 | FT-001 | KK-TS-009 |
| §12 Prioritetni | BM-NL-17–25; BM-DG-09 | BR-160–169; BR-064 | FT-001 | KK-TS-003, KK-TS-004, KK-TS-010 |
| §13 Evidencija dostave | BM-NL-11, BM-NL-18, BM-NL-21–25, BM-NL-47 | BR-158, BR-161, BR-164, BR-166–169, BR-348 | FT-001 | — |
| §14 Promjena na čekanju | BM-NL-22–25 | BR-166–169 | FT-001 | — |
| §15 Obrada zadataka | BM-NL-07 | BR-148, BR-163 | FT-001 | — |
| §16 Raspored | BM-NL-07, BM-NL-16 | BR-148, BR-157 | FT-001 | — |
| §17 Validacije | BM-NL-* | BR-139–169, BR-328–BR-348 | FT-001 | — |
| §18 Guard | BM-NL-09–13, BM-NL-42, BM-DG-09 | BR-147–150, BR-064 | FT-001 | KK-TS-003 |
| §19–20 Error / Ponovni pokušaj | — | BR-186 | FT-001 | — |
| §21 Audit događaji | — | BR-184–186 | FT-001 / FT-003 | KK-TS-012 |
| §22 Autorizacija | BM-NL-03, BM-NL-04 | BR-143–144 | FT-001 | KK-TS-010 |
| §26 Legacy | BM-NL-44 | BR-343 | FT-001 | — |
| §27 Van obuhvata | BM-NL-16 | BR-157 | FT-001 | — |

Terminalnost Otkazan / zabrana republish: BM-DG-09, BR-064, KK-TS-003 v0.1.2, KK-TS-010 v1.0.1, G-NL-08 zatvoren.

---

# 24. Acceptance kriterijumi

AC-NL-01 · Samo prijavljeni korisnik sa verifikovanom aktuelnom e-mail adresom može aktivirati pretplatu.
AC-NL-02 · Anonimni posjetilac ne može koristiti Newsletter.
AC-NL-03 · Pretplatnik bira „Svi događaji“ ili „Odabrani organizatori“; prazan selektivni izbor nije validan i nije odjava.
AC-NL-04 · Izbor opsega ne daje prava niti otkriva pretplatnike Organizatoru/Moderatoru; korisnik može mijenjati izbor akcijom „Sačuvaj izmjene“.
AC-NL-05 · Prvo uključivanje samo za Objavljen + budući termin; ne za Nacrt/Na odobrenju/Arhiviran/Otkazan.
AC-NL-06 · Isti događaj se istom pretplatniku ne dostavlja ponovo kao first_include.
AC-NL-07 · Prazan Newsletter se ne šalje.
AC-NL-08 · Prioritet ide samo pretplatnicima sa first_include.
AC-NL-09 · Ista prioritetna promjena (Kontrolni zapis promjene) se ne dostavlja dvaput.
AC-NL-10 · Više Promjena na čekanju → jedno obavještenje sa posljednjim stanjem.
AC-NL-11 · Nema kontradiktornih poruka u istom ciklusu.
AC-NL-12 · Uređivačke izmjene ne okidaju Newsletter.
AC-NL-13 · Odjava iz poruke i iz UI, uz jednostavnu potvrdu, deaktivira pretplatu bez brisanja naloga i bez brisanja zapisa pretplate; aktivne preference se uklanjaju.
AC-NL-14 · Aktivacija, izmjena preferenci i odjava potvrđuju se u aplikaciji; nema dodatnog e-mail confirmationa niti obavezne servisne e-mail poruke.
AC-NL-15 · Organizator/Moderator/Urednik ne mogu ručno slati ni birati događaje za Newsletter.
AC-NL-16 · Ne postoji republish putanja; Otkazan ne vraća događaj u first_include bez novog događaja.
AC-NL-17 · Audit događaji pokrivaju katalog BR-185; ponovni pokušaji grešaka nisu audit.
AC-NL-18 · Redovni ciklus nije vezan za fiksni sedmični poslovni model.
AC-NL-19 · Poruka sadrži odjavu i linkove ka događaju / pregledu registrovanog Organizatora (gdje postoji).
AC-NL-20 · Okidač se emituje samo nakon trajno sačuvane poslovne promjene; obrada je odvojena od poslovne transakcije.
AC-NL-21 · Evidencija dostave za više stavki u jednoj poruci je cjelovita.
AC-NL-22 · Identitet pretplatnika je `User`; `User.email` je adresa isporuke, ne Newsletter SSOT.
AC-NL-23 · Testni legacy pretplatnici se ne migriraju; kanonski model ima prednost; stari sedmični mehanizam ne ostaje paralelan nakon cutover-a (PRAVILO 5.3.4).
AC-NL-24 · „Bez organizatora“ uključuje Događaje bez veze na `CulturalOrganizer`, uključujući ručni naziv; registrovani Organizator nije u toj grupi.
AC-NL-25 · Deaktivirani Organizator ne briše preferencu; nije aktivan izvor dok je neaktivan.
AC-NL-26 · Manifestacija nije dimenzija pretplate u V1.
AC-NL-27 · Jedan `User` — jedna pretplata; reaktivacija ne kreira novi zapis i ne vraća stare preference.
AC-NL-28 · Prva pretplata i reaktivacija ne proizvode retroaktivni first_include; `subscribed_at` je trenutna activation boundary.
AC-NL-29 · Promjena preferenci, dodavanje Organizatora, „Bez organizatora“ i selected → all ne pretvaraju ranije objavljene Event-e u first_include kandidate.
AC-NL-30 · first_include ledger red nastaje samo nakon uspješne e-mail isporuke; `sent_at` = vrijeme uspješne isporuke; NL-03 ne piše ledger i ne šalje e-mail.
AC-NL-31 · Kandidat ≠ queued/sent/delivered; nepostojanje ledger zapisa samo po sebi ne čini Event kandidatom.

---

# 25. Napomene za implementaciju

1. Ne implementirati fiksni sedmični digest kao ciljni model.
2. Evidenciju dostave upisivati **cjelovito** sa uspješnim send-om (§13.3).
3. Signale vezati na uspješno sačuvane statusne prelaze (§5), ne na UI klikove.
4. Za događaje sa više termina koristiti `occurrence_id` u prioritetnoj Evidenciji.
5. Pri deaktivaciji Organizatora: preferenca ostaje; Organizator nije aktivan izvor dok je neaktivan.
6. UI pretplate uskladiti sa KK-TS-009; kanonski UI termini: Pretplati se, Odjavi se, Sačuvaj izmjene, Svi događaji, Odabrani organizatori, Bez organizatora.
7. Konfiguracione ključeve rasporeda dokumentovati u ops/runbook-u, ne u BM.
8. Ne graditi urednički „Newsletter admin” u V1.
9. Potvrda u aplikaciji ≠ double opt-in gate; nema confirmation e-maila.
10. Pri cutover-u slijediti §26: kanonski model ima prednost; **nema** migracije testnih pretplatnika.
11. Režim opsega je obavezni V1 (§6.2); `all_events` nije pivot svih Organizatora.
12. Ne uvoditi polja `confirmation_sent_at` / `first_activated_at` / Newsletter `email` kolonu.
13. **NL-03 formalni obuhvat = FIRST_INCLUDE ELIGIBILITY / CANDIDATE FOUNDATION.** IN: Event eligibility; Subscription/User delivery eligibility; scope matching; Organizer matching; „Bez organizatora“; temporal eligibility; exclusion ako postoji successfully-delivered first_include ledger. OUT: ledger write; `sent_at`; e-mail delivery; queue; scheduler; priority notifications; cancellation/postponement notifications; pending/candidate tabela.
14. Ne tretirati `CulturalEventEntry.created_at` / `updated_at` / `first_submitted_at` kao canonical first publication timestamp. `first_submitted_at` je trenutak slanja na odobrenje / može biti postavljen i pri direktnoj objavi; **nije** pouzdan publication time. Manifestacija ima `published_at`; Event **nema** ekvivalent. **NL-03 TECHNICAL GAP — CANONICAL FIRST PUBLICATION TIMESTAMP.**
15. `subscribed_at` je AVAILABLE activation boundary. Ne uvoditi `activated_at` / `reactivated_at` bez potrebe.
16. **NL-03 TECHNICAL IMPLEMENTATION QUESTION — preference effective-time:** poslovno pravilo „od čuvanja nadalje“ je usvojeno. Schema nema pouzdan timestamp po pojedinačnoj preferenci (`include_without_organizer`, `scope_mode`, dodavanje Organizatora) koji kasniji query može koristiti bez dvoznacnosti. `newsletter_subscriptions.updated_at` **nije** sigurna granica (mijenja se i pri odjavi/reaktivaciji/bilo kojem čuvanju). Pivot `created_at` na organizer vezi nije dovoljan za cijeli skup pravila. Ne izmišljati `preference_effective_at` u ovom dokumentacionom patchu. NL-03 implementacija mora riješiti tehnički datum **bez** mijenjanja usvojenog poslovnog pravila.
17. NL-03 ne smije kreirati first_include ledger zapis, postavljati `sent_at`, niti predstavljati kandidat kao poslat.

---

# 26. Legacy implementacija

## 26.1 Usvojena pravila

**PRAVILO 5.3.1**

KK-TS-011 definiše ciljnu tehničku arhitekturu Newsletter sistema za verziju V1 i predstavlja izvor istine za njegovu implementaciju. Postojeća implementacija Newsletter funkcionalnosti ne predstavlja normativni izvor i ne može imati prednost u odnosu na usvojena pravila definisana u Business Model-u, Functional Specification-u i ovoj tehničkoj specifikaciji.

**PRAVILO 5.3.2**

Postojeća implementacija Newsletter funkcionalnosti može biti iskorišćena kao osnova za implementaciju, pod uslovom da je u potpunosti usklađena sa pravilima definisanim u Business Model-u, Functional Specification-u i KK-TS-011. Svaki dio postojeće implementacije koji nije usklađen sa usvojenim pravilima mora biti izmijenjen ili zamijenjen.

**PRAVILO 5.3.3**

Postojeća Newsletter implementacija je **testna**. Postojeći testni pretplatnici nijesu produkcioni poslovni podaci. **Nema** obaveze migracije testnih pretplatnika, **nema** A/B/C/D legacy backfill modela i **nema** obaveze kompatibilnosti sa starim e-mail-only modelom. Kanonski model ima prednost (CANONICAL MODEL WINS). Novi Newsletter implementira se direktno prema BM/FS/KK-TS-011.

**PRAVILO 5.3.4**

Nakon cutover-a na kanonski model, prethodna testna implementacija Newsletter funkcionalnosti ne smije ostati aktivna paralelno sa implementacijom definisanom u KK-TS-011. U produkcionom okruženju u svakom trenutku smije biti aktivan isključivo jedan mehanizam za obradu i dostavu Newsletter poruka. Ovaj dokumentacioni korak **ne** naređuje trenutno brisanje starog koda.

## 26.2 Istorijski testni model (REPLACED — nije current runtime)

**CURRENT runtime (FAZA 7 CLOSED):** `cultural-calendar:send-newsletter` (regular) i `cultural-calendar:send-newsletter-priority` (priority). Legacy `cultural-calendar:send-weekly-newsletter` = **runtime no-op**; **nije** kanonski invoker. Tabela ispod opisuje **prethodni testni model** (PRAVILO 5.3.3 / 5.3.4) i **ne** opisuje produkcioni Newsletter.

| Aspekt | Istorijska testna implementacija (REPLACED) |
|--------|-------------------------------|
| Publika | E-mail adresa; nije obavezno vezano za verifikovan nalog |
| Tabela | `newsletter_subscribers` |
| Okidač | Fiksni sedmični raspored (ponedjeljak 09:00) |
| Sadržaj | Link ka sedmičnom pregledu; bez liste novoobjavljenih događaja |
| Filter Organizatora | Ne postoji |
| Prioritetne promjene | Ne postoje |
| Evidencija dostave | Ne postoji |
| Obrada | Artisan `cultural-calendar:send-weekly-newsletter` (sinhroni Mail); `Schedule` ponedjeljak 09:00 u `routes/console.php` |
| UI | Forma po e-mailu (`CulturalCalendarNewsletterController`) |
| Welcome mail | `CulturalCalendarNewsletterWelcomeMail` pri prvoj prijavi |

## 26.3 Klasifikacija prijelaza (dokumentaciono; kod se sada ne briše)

**REUSE (vjerovatno, uz usklađivanje):**

* Laravel Mail infrastruktura;
* koncept tokena odjave (ne e-mail SSOT);
* postojeći rasporedni mehanizam platforme (ne ponedjeljak-kao-poslovni-model);
* dijelovi e-mail predloška ako se mogu prilagoditi kanonskom sadržaju.

**REPLACE:**

* `NewsletterSubscriber` e-mail-only model i `newsletter_subscribers.email` kao SSOT;
* `CulturalCalendarNewsletterController` prijava/odjava po e-mailu bez `User` pretplate;
* welcome/confirmation e-mail pri pretplati;
* sedmični sadržaj i `cultural-calendar:send-weekly-newsletter` kao kanonski ciklus;
* implicitna pretplata / prazan izbor ≡ svi Organizatori (nije u starom kodu, ali jeste u starom TS ugovoru — zamijenjeno).

**REMOVE LATER (u odgovarajućem NL paketu, ne u ovom dokumentacionom koraku):**

* tabela `newsletter_subscribers` nakon cutover-a;
* `SendCulturalCalendarWeeklyNewsletter`;
* `CulturalCalendarNewsletterWeeklyMail` / weekly Blade;
* `CulturalCalendarNewsletterWelcomeMail`;
* ponedjeljak 09:00 `Schedule` za weekly newsletter.

## 26.4 Ciljni model (ovaj TS)

| Aspekt | Cilj |
|--------|------|
| Publika | `User` sa aktivnom pretplatom i dozvoljenom isporukom |
| Pretplata | Jedna pretplata po `User`; `User.email` kao adresa isporuke |
| Opseg | `all_events` ili `selected_organizers` + opciono „Bez organizatora“ |
| Okidač | Objava → first_include; poslovne promjene → prioritet |
| Cadence | Raspored automatske obrade + period objedinjavanja (bez fiksnog sedmičnog BM pravila) |
| Sadržaj | Kratak pregled događaja u e-mailu, grupisan po registrovanom Organizatoru ili „Bez organizatora“ |
| Evidencija dostave | Obavezno |
| Ručno / kampanje | Nisu V1 (BM-NL-16 / BR-157) |

Ovaj odsjek operacionalizuje usvojene BM/FS odluke i ne uvodi nova poslovna pravila.

---

# 27. Van obuhvata (Out of Scope)

**PRAVILO 5.4.1**

Poglavlje „Van obuhvata (Out of Scope)” definiše funkcionalnosti koje nisu predmet implementacije KK-TS-011 u verziji V1. Funkcionalnosti navedene u ovom poglavlju ne smatraju se dijelom zahtjeva za implementaciju niti se njihovo nepostojanje smatra nedostatkom implementacije.

**PRAVILO 5.4.2**

Sve funkcionalnosti definisane usvojenim Business Model-om i Functional Specification-om predstavljaju obavezni dio implementacije KK-TS-011. Funkcionalnosti koje nisu definisane tim dokumentima smatraju se van obuhvata ove tehničke specifikacije za verziju V1.

Napomena: granice V1 već usvojene u BM-NL-16 / BR-157 (npr. kategorije kao filter, personalizacija, kampanje, ručno slanje, Newsletter po ulozi, interval kao poslovno pravilo, „Prati Manifestaciju“, e-mail-only pretplatnik, obavezne servisne e-mail poruke) ostaju na snazi kroz PRAVILO 5.4.2 — bez proširenja liste u ovom TS-u.

---

# 28. Otvorena pitanja

Nema otvorenih poslovnih pitanja.

Napomena: način izračunavanja Kontrolnog zapisa promjene, veličine grupa za obradu i tehnologija reda za obradu su **implementacioni** izbori unutar usvojenog BM/FS okvira.

Zatvoreno relevantno za ovaj TS:

* **G-NL-08** — zatvoren terminalnošću Otkazan (PO-DG-07 / PATCH-053); nema republish Newsletter putanje.

---

# Kraj dokumenta
