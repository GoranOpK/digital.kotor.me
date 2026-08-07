# Digital Kotor
# Technical Specification
## Evidencija aktivnosti (Audit)

**Feature ID:** FT-003  
**Oznaka dokumenta:** TS-012  
**Funkcionalna cjelina:** Evidencija aktivnosti  
**Modul:** Kalendar kulture  
**Status dokumenta:** NACRT  
**Verzija:** 1.0.1 (DRAFT)  
**Datum:** 2026-08-07

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-07 | Prvi nacrt Technical Specification za funkcionalnu cjelinu Evidencija aktivnosti (Audit). Usklađen sa BM-14 (BM-AL-01–BM-AL-08), BM-EP-09, BM-GL-09, BM-GL-20, BM-MF-20; FS §5.16 (BR-170–BR-188); Feature Registry FT-003; TS-003 v0.1.2, TS-004, TS-010 v1.0.1, TS-011 v1.0.1; METHODOLOGY. Operacionalizuje centralni prijem, trajno evidentiranje, nepromjenjivost, Admin pristup i V1 katalog bez širenja BR-188. Bez izmjene BM/FS/ostalih TS/Feature Registry. Bez izmjene implementacije. |
| 1.0.1 | 2026-08-07 | PATCH-001: završna tehnička usklađenja — jedinstvenost `(source_module, event_id)`; neuspjeh Evidencije ne poništava poslovnu radnju + pouzdana ponovna obrada; kanonski emiter; istorijski integritet izvršioca. Bez novih poslovnih odluka; bez širenja V1; bez izmjene BM/FS/FR/ostalih TS. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.  
Kod svake naredne verzije dodaje se novi red u tabeli.  
Ne mijenjaju se postojeći redovi.

---

# Change Log

| Verzija | Datum | Izmjena |
|---------|--------|---------|
| 1.0.0 | 2026-08-07 | Kreiran TS-012 (NACRT). Kompletna tehnička specifikacija Evidencije aktivnosti: obuhvat, granice, arhitektura, model događaja/zapisa, katalog V1, prijem, nepromjenjivost, autorizacija, Admin pristup, razdvajanje od tehničkih logova, integracije, validacije, acceptance, sljedivost, Van obuhvata. |
| 1.0.1 | 2026-08-07 | PATCH-001: (1) jedinstvenost audit događaja = `source_module` + `event_id`; (2) neuspjeh prijema/evidentiranja ne poništava poslovnu radnju; pouzdana ponovna obrada; (3) jedan kanonski emiter po poslovnoj radnji; (4) istorijski izvršilac nepromjenjiv nakon deaktivacije naloga. Dopunjeni §3, §5–6, §8, §13, §14–16, §19. |

---

# Svrha dokumenta

Ovaj dokument opisuje kako će se usvojeni Business Model i Functional Specification za funkcionalnu cjelinu **Evidencija aktivnosti** tehnički realizovati u okviru **FT-003**.

TS-012:

* ne uvodi nova poslovna pravila;
* ne zamjenjuje Business Model niti Functional Specification;
* nije Technical Overview trenutne implementacije;
* nije Change Request;
* ne definiše SQL, migracije, Laravel kod niti konkretne API ugovore;
* predlaže tehnički model prijema, trajnog skladištenja i minimalnog Admin pristupa kao operacionalizaciju usvojenih BM/FS pravila.

Izvori istine za poslovna pravila:

* `docs/business-model/Business_Model_Kalendar_kulture_MASTER.md` (BM-14 BM-AL-01–BM-AL-08; BM-EP-09; BM-GL-09; BM-GL-20; BM-MF-20)
* `docs/functional-specifications/Functional-Specification.md` (§5.16 BR-170–BR-188; PATCH-FS-035 i usklađenja uključujući PATCH-FS-053 gdje se tiče kataloga Događaji)
* `docs/features/Feature-Registry.md` (FT-003)
* `docs/METHODOLOGY.md` (M-TS-001–M-TS-005)
* `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003 v0.1.2)
* `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (TS-004)
* `docs/technical-specifications/Technical-Specification_Urednicki_portal.md` (TS-010 v1.0.1)
* `docs/technical-specifications/Technical-Specification_Newsletter.md` (TS-011 v1.0.1)

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | Nacrt |
| 2. Granice odgovornosti | Nacrt |
| 3. Arhitektonski principi | Nacrt |
| 4. Komponente | Nacrt |
| 5. Model audit događaja | Nacrt |
| 6. Model audit zapisa | Nacrt |
| 7. Katalog V1 | Nacrt |
| 8. Prijem i evidentiranje | Nacrt |
| 9. Nepromjenjivost | Nacrt |
| 10. Autorizacija | Nacrt |
| 11. Admin pristup | Nacrt |
| 12. Razdvajanje od tehničkih logova | Nacrt |
| 13. Integracije | Nacrt |
| 14. Validacije | Nacrt |
| 15. Matrica sljedivosti | Nacrt |
| 16. Acceptance kriterijumi | Nacrt |
| 17. Van obuhvata (Out of Scope) | Nacrt |
| 18. Otvorena pitanja | Nacrt |
| 19. Napomene za implementaciju | Nacrt |

---

# Pravila upravljanja ovim dokumentom

1. TS-012 pripada **FT-003** – Evidencija aktivnosti (Kalendar kulture).
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz Technical Specification.
4. Sve što nije definisano u BM ili FS, a zahtijeva poslovnu odluku, evidentira se kao **Otvoreno pitanje**.
5. Tehnički predlozi (zaštita od duplikata po `source_module` + `event_id`, kanonski emiter, izolacija neuspjeha Evidencije, istorijski izvršilac, minimalni hronološki prikaz) nisu nova poslovna pravila.
6. Product Owner donosi poslovne odluke; ovaj dokument ih ne pretpostavlja.
7. Granice V1 iz **BR-188** ostaju na snazi: napredni Admin UI, filteri, izvoz, retention i detaljna polja nisu dio ovog nacrta.

---

# 1. Pregled funkcionalne cjeline

Izvori

Business Model:
- BM-AL-01–BM-AL-08
- BM-EP-09
- BM-GL-09, BM-GL-20
- BM-MF-20

Functional Specification:
- §5.16 (BR-170–BR-188)

## 1.1 Svrha

**Evidencija aktivnosti** je centralni poslovni zapis o poslovno značajnim radnjama izvršenim u modulu Kalendara kulture.

Svrha:

* dokumentovanje izvršenih poslovnih radnji;
* utvrđivanje odgovornosti;
* omogućavanje kontrole i naknadne provjere (revizije).

Evidencija aktivnosti:

* **nije** sredstvo komunikacije;
* **nije** poslovno obavještenje;
* **nije** zamjena za tehničke sistemske logove;
* **ne** utiče na tok poslovnih procesa niti na prava korisnika.

## 1.2 Obuhvat dokumenta

1. granice odgovornosti centralne Evidencije naspram izvornih modula i lokalnih audit tragova;
2. arhitektura prijema i trajnog evidentiranja;
3. model audit događaja i audit zapisa (minimalni sadržaj);
4. V1 katalog aktivnosti (Moderator, Organizatori, Manifestacije, Događaji / Održavanja, Newsletter);
5. ugovor prijema iz TS-003 / TS-004 / TS-010 / TS-011;
6. nepromjenjivost;
7. autorizacija i minimalni Admin pristup;
8. razdvajanje od tehničkih logova;
9. validacije, acceptance, sljedivost, Van obuhvata.

Van obuhvata: vidi §17 (usklađeno sa BR-188).

## 1.3 Zavisnosti

| Zavisnost | Uloga |
|-----------|--------|
| TS-001 Organizator / Moderator | Emisija kataloga Organizatori / Moderator (preko portala / TS-010) |
| TS-003 Događaj | Emisija kataloga Događaji |
| TS-004 Održavanje | Emisija aktivnosti održavanja kroz katalog Događaji |
| TS-005 Manifestacija | Emisija kataloga Manifestacije (granica; emiteri u uredničkom toku) |
| TS-010 Urednički portal | Obaveza emitovanja (TS-010.7); bez UI centralne evidencije |
| TS-011 Newsletter | Emisija kataloga Newsletter |
| Platforma Digital Kotor | Identitet korisnika; uloga Administrator platforme |

---

# 2. Granice odgovornosti

## 2.1 Šta TS-012 radi

* prima audit događaje nakon uspješno završenih poslovnih radnji iz kataloga;
* trajno evidentira audit zapise;
* čuva nepromjenjivost zapisa;
* omogućava pristup Administratori platforme;
* razlikuje korisničkog izvršioca i izvršioca **Sistem**.

## 2.2 Šta TS-012 ne radi

* ne određuje *zašto* se poslovna radnja smije desiti (to je BM/FS i izvorni TS);
* ne upravlja lifecycle-om događaja, održavanja, pretplata niti uredničkim workflow-om;
* ne zamjenjuje lokalne audit tragove na entitetima (BR-171);
* ne skladišti tehničke logove (BR-172, BR-186);
* ne uvodi napredne filtere, izvoz, retention politiku niti bogati Admin UI (BR-188).

## 2.3 Lokalno vs centralno

| Koncept | Vlasnik | Napomena |
|---------|---------|----------|
| Lokalni audit trag | TS-001 / TS-003 / TS-010 … | Vidljiv ovlašćenim ulogama na entitetu; ≠ centralna evidencija |
| Centralna Evidencija | **TS-012 / FT-003** | Direktan pristup samo Administrator |

Prikaz lokalnih tragova **nije** direktan pristup centralnoj Evidenciji (BR-175).

Izvorni moduli **ne** upravljaju sadržajem, integritetom niti životnim ciklusom već nastalih centralnih zapisa (BR-170).

---

# 3. Arhitektonski principi

1. **BM/FS su izvor istine** za katalog i pristup.
2. **Emituj-pa-zapiši** — emisija tek nakon uspješno sačuvane poslovne radnje.
3. **Jedan poslovni događaj → jedan (logički) audit zapis** (uz pravila dva zapisa pri odobrenju Organizatora — BR-179).
4. **Idempotentni prijem** — jedinstvenost po kombinaciji `source_module` + `event_id`; ponovni prijem iste kombinacije ne stvara novi zapis.
5. **Kanonski emiter** — svaka poslovno značajna radnja ima tačno jednog emitera audit događaja.
6. **Izolacija neuspjeha** — neuspjeh Evidencije ne poništava već završenu poslovnu radnju; omogućena je pouzdana ponovna obrada.
7. **Nepromjenjivost** — nema update/delete kroz redovne tokove; istorijski izvršilac ostaje sačuvan.
8. **Nezavisnost** — evidencija ne mijenja poslovna prava ni tokove.
9. **Sistem ≠ korisnik** — automatske radnje imaju tip izvršioca Sistem.
10. **Tehnički log ≠ Audit**.
11. **BR-188 granica** — konzervativan V1 bez širenja Admin UI.

---

# 4. Komponente

| Komponenta | Odgovornost |
|------------|-------------|
| **Prijem audit događaja** | Prima potvrđene događaje iz emitera |
| **Validacija prijema** | Provjera obaveznih atributa i pripadnosti katalogu |
| **Zaštita od duplikata** | Idempotentnost po kombinaciji `source_module` + `event_id` |
| **Trajno skladište zapisa** | Upis nepromjenjivog audit zapisa |
| **Admin pristup** | Minimalni hronološki pregled za Administratora |
| **Granica tehničkog loga** | Odbijanje / neprihvatanje tehničkih događaja kao Audita |

---

# 5. Model audit događaja

**Audit događaj** je poruka koju emiter šalje TS-012 nakon uspješno završene poslovne radnje iz V1 kataloga.

Konceptualni atributi (bez SQL):

| Atribut | Opis |
|---------|------|
| `event_id` | Identifikator događaja koje dodjeljuje kanonski emiter; jedinstven **u okviru** `source_module` |
| `occurred_at` | Vrijeme nastanka poslovne radnje |
| `activity_type` | Vrsta aktivnosti iz kataloga |
| `actor_type` | `user` \| `system` |
| `actor_user_id` | Identitet korisnika kada je `actor_type = user` (nullable za Sistem); istorijska vrijednost u trenutku radnje |
| `object_type` | Tip poslovnog objekta (npr. događaj, održavanje, organizator, pretplata, manifestacija, zahtjev) |
| `object_id` | Identitet objekta |
| `organizer_context_id` | Kontekst Organizatora kada je primjenjivo (BR-181) |
| `source_module` | Izvorni modul / TS (npr. TS-003, TS-011); dio ključa jedinstvenosti |
| `catalog_area` | Moderator \| Organizatori \| Manifestacije \| Događaji \| Newsletter |

**Jedinstvenost audit događaja** utvrđuje se kombinacijom **`source_module` + `event_id`**. Globalni UUID nije obavezno pravilo. Ponovni prijem iste kombinacije ne smije proizvesti novi audit zapis.

TS-012 **ne** propisuje Laravel Event klase, Redis ni queue tehnologiju.

---

# 6. Model audit zapisa

**Audit zapis** je trajno sačuvana evidencija audit događaja.

## 6.1 Minimalni sadržaj (V1)

| Atribut | Opis |
|---------|------|
| `id` | Jedinstveni identifikator audit zapisa |
| `recorded_at` | Vrijeme evidentiranja u centralnoj evidenciji |
| `occurred_at` | Vrijeme poslovne radnje |
| `activity_type` | Vrsta poslovno značajne aktivnosti |
| `actor_type` | `user` \| `system` |
| `actor_user_id` | Identitet korisnika u trenutku radnje (nullable za Sistem); istorijski nepromjenjiv (§8.4.1) |
| `object_type` | Tip objekta |
| `object_id` | Identitet objekta |
| `organizer_context_id` | Kontekst Organizatora (nullable) |
| `source_module` | Referenca na kanonskog emitera |
| `catalog_area` | Oblast kataloga |
| `ingestion_event_id` | Tehnički ključ zaštite od duplikata koji odgovara kombinaciji `source_module` + `event_id` (npr. kompozit ili ekvivalentan jedinstveni indeks) |

## 6.2 Šta se ne uvodi u V1

U skladu sa BR-188, **ne** uvode se kao obavezna polja:

* IP adresa;
* uređaj / browser / user-agent;
* session ID;
* kompletan prethodni/novi poslovni payload;
* tehnički request metadata.

Ako je za identifikaciju aktivnosti potreban kratak konzervativni opis (npr. šifra aktivnosti već pokrivena `activity_type`), ne proširuje se poslovni obuhvat.

---

# 7. Katalog V1

Katalog je **isključivo** onaj iz FS §5.16. TS-012 ne dodaje aktivnosti.

## 7.1 Moderator (BR-177)

Ulaze: podnošenje / odobravanje / odbijanje zahtjeva za dodjelu; pokretanje / odobravanje / odbijanje uklanjanja ovlašćenja Moderatora.

Jedinstvenost zapisa po BR-180. Promjena aktivnog konteksta **nije** zaseban zapis (BR-181).

## 7.2 Organizatori (BR-178, BR-179)

Ulaze: podnošenje / odobravanje / odbijanje zahtjeva za kreiranje; deaktivacija; naknadno povezivanje događaja; poslovno značajne izmjene Organizatora.

Pri odobrenju kreiranja Organizatora: **dva** zapisa (odobrenje+kreiranje; dodjela početnog Moderatora) — bez trećeg duplikata (BR-179).

## 7.3 Manifestacije (BM-AL-07, BM-MF-20, FS §5.16)

Ulaze: kreiranje; slanje na odobrenje; vraćanje na doradu; odobravanje/objava; otkazivanje; automatsko arhiviranje (**Sistem**); dodavanje/uklanjanje/premještanje događaja; promjena Organizatora; promjena naslovne fotografije; promjena polja Web stranica / Više informacije.

**Administrativni GAP (van izmjene ovog TS-a):** Feature Registry u sažetku FT-003 trenutno ne navodi Manifestacije u listi V1 kataloga, iako BM/FS to zahtijevaju. TS-012 **uključuje** Manifestacije. FR se u ovom zadatku ne mijenja.

## 7.4 Događaji i Održavanja (BR-182, BR-183)

Ulaze aktivnosti kataloga Događaji, uključujući:

* urednički tok (kreiranje, slanje, vraćanje, ponovno slanje, odobrenje, direktna objava);
* isticanje / uklanjanje isticanja;
* otkazivanje; unos/dopuna razloga otkazivanja;
* odlaganje održavanja; otkaz pojedinačnog održavanja; promjena termina; promjena lokacije;
* prijedlozi izmjena (podnošenje / odobrenje / vraćanje);
* automatsko arhiviranje (**Sistem**).

**Ne postoji** zaseban katalog Održavanja; aktivnosti nad Održavanjem idu kroz katalog Događaji (BR-182). Emiter: TS-003 / TS-004.

Ne ulaze: uređivanje nacrta, sitne izmjene, lock/unlock, pregled, pokušaj republish (BR-183 / BR-064).

## 7.5 Newsletter (BR-185, BR-186)

Ulaze: aktivacija; odjava; ponovna aktivacija; promjena izbora Organizatora; slanje redovnog Newslettera (**Sistem**); slanje prioritetnog obavještenja (**Sistem**).

Ne ulaze: tehničke greške slanja i retry; potvrda aktivacije kao zaseban audit; pregled postavki; BR-128 obavještenja.

Slanje Newslettera **ne** duplira katalog Događaji.

## 7.6 Van kataloga (platforma)

Autentikacija, nalog, platformske uloge Urednik/Administrator — van V1 kataloga Kalendara (BR-176).

---

# 8. Prijem i evidentiranje

## 8.1 Ugovor prijema

1. Emiter šalje audit događaj **tek nakon** uspješno završene i trajno sačuvane poslovne radnje.
2. Emiter određuje `activity_type` u skladu sa FS katalogom; TS-012 ne tumači poslovne razloge radnje.
3. TS-012 validira obavezne atribute i pripadnost katalogu.
4. TS-012 upisuje audit zapis.
5. Ponovni prijem iste kombinacije **`source_module` + `event_id`** **ne** stvara novi zapis (tehnička zaštita od duplikata).
6. **Neuspjeh** prijema ili trajnog evidentiranja audit događaja **ne smije** retroaktivno poništiti niti promijeniti već uspješno završenu poslovnu radnju.
7. Sistem mora omogućiti **pouzdanu ponovnu obradu** audit događaja čije evidentiranje nije uspješno završeno (bez propisivanja konkretne tehnologije).

## 8.2 Kanonski emiter

Svaka poslovno značajna radnja ima **jednog kanonskog emitera** audit događaja.

Kanonski emiter je tehnički modul koji upravlja životnim ciklusom poslovnog entiteta i potvrđuje uspješan završetak poslovne radnje.

Ostali moduli **ne** emituju zasebne audit događaje za istu poslovnu radnju.

## 8.3 Emiteri (V1)

| Kanonski emiter | Katalog | Referenca |
|-----------------|---------|-----------|
| TS-003 | Događaji (dio lifecycle događaja) | TS-003 §8.2 |
| TS-004 | Događaji — Održavanje | TS-004 §8.2 |
| TS-010 (urednički portal) | Moderator, Organizatori, Manifestacije; Događaji gdje je portal kanonski vlasnik toka | TS-010.7 |
| TS-011 | Newsletter | TS-011 §21 |
| TS-001 / tokovi Organizator–Moderator | Moderator / Organizatori (gdje je TS-001 kanonski; inače TS-010) | granica uz TS-010 |

Gdje više dokumenata opisuje istu oblast, emitovanje radi **samo** kanonski emiter za konkretnu radnju (npr. lifecycle Održavanja → TS-004; uredničko odobrenje u portalu → TS-010).

## 8.4 Izvršilac

| Tip | Pravilo |
|-----|---------|
| **Korisnik** | `actor_type = user`; čuva se `actor_user_id` stvarnog izvršioca u trenutku radnje |
| **Sistem** | `actor_type = system`; `actor_user_id` prazan; **ne** izmišlja se tehnički korisnički nalog „Sistem“ |

Primjeri Sistem (BR-184 i katalog): automatsko arhiviranje događaja/manifestacije; slanje redovnog/prioritetnog Newslettera.

### 8.4.1 Istorijski izvršilac

Audit zapis **trajno** čuva identitet izvršioca kakav je bio u trenutku nastanka poslovno značajne radnje.

Naknadna deaktivacija ili promjena statusa korisničkog naloga **ne smije** izmijeniti niti učiniti neodređenim izvršioca već evidentirane aktivnosti.

TS-012 **ne** određuje politiku životnog ciklusa korisničkih naloga; određuje samo da ta politika **ne smije** narušiti istorijski integritet Evidencije aktivnosti.

## 8.5 Zaštita od duplikata (tehnički predlog)

Kanonski ključ jedinstvenosti: kombinacija **`source_module` + `event_id`** (evidentirana kao `ingestion_event_id` ili ekvivalentan jedinstveni indeks).

Globalni UUID **nije** obavezno pravilo.

Implementacioni izbor (indeks, upsert, outbox, retry) nije propisan; semantika jeste:

* ista kombinacija → najviše jedan zapis;
* neuspješno evidentiranje → pouzdana ponovna obrada istog događaja.

Izuzetak semantike BR-179: odobrenje kreiranja Organizatora proizvodi **dva** događaja (dva `event_id` kod istog kanonskog emitera), ne jedan.

---

# 9. Nepromjenjivost

1. Nakon nastanka audit zapis se **ne uređuje** kroz redovne aplikativne tokove.
2. Audit zapis se **ne briše** kroz redovno korišćenje.
3. Korekcija poslovnog stanja entiteta **ne mijenja** prethodni audit zapis.
4. Nova propisana poslovna radnja proizvodi **novi** audit događaj / zapis.
5. Identitet izvršioca u zapisu ostaje istorijski tačan i nakon naknadne deaktivacije ili promjene statusa korisničkog naloga (vidi §8.4.1).
6. Posebna retention / anonimizacija / sistemsko brisanje **nije** V1 (BR-188); vidi §17.

---

# 10. Autorizacija

| Uloga | Direktan pristup centralnoj Evidenciji | Evidentiranje |
|-------|----------------------------------------|---------------|
| Administrator platforme | **Da** | Ne kao redovni izvršilac kataloga (osim ako izvrši radnju iz kataloga u drugoj ulozi) |
| Organizator (entitet) | Ne | — |
| Moderator | Ne | Emisija preko portala |
| Urednik | Ne | Emisija preko portala |
| Registrovani korisnik | Ne | Newsletter pretplata (TS-011) |
| Sistem | Nije uloga za pregled | Automatski zapisi |

---

# 11. Admin pristup

## 11.1 Norma

Pristup ima isključivo **Administrator platforme** (BM-AL-06, BR-174).

## 11.2 Minimalni V1 pregled

Tehnički se dozvoljava **osnovni hronološki prikaz** zapisa (npr. lista po `occurred_at` / `recorded_at` silazno) dovoljan da Administrator pristupi evidenciji.

**Ne uvodi se** (BR-188):

* napredni filteri;
* puna pretraga;
* sortiranje kao zasebna funkcionalnost;
* izvoz;
* dashboard analitika;
* detaljni audit explorer.

---

# 12. Razdvajanje od tehničkih logova

Centralna Evidencija **nije** tehnički log (BM-AL-02, BR-172).

Ne ulaze kao Audit:

* exception-i, queue greške, mail-provider greške, ponovni pokušaji (BR-186);
* browser / user-agent / session ID;
* serverski događaji bez poslovnog značaja;
* infrastruktura rasporeda / ograničenja brzine slanja.

Takvi podaci pripadaju tehničkom logovanju platforme, izvan TS-012.

**Evidencija dostavljenih Newsletter poruka (TS-011) nije audit.**

---

# 13. Integracije

| Dokument | Uloga prema TS-012 |
|----------|-------------------|
| TS-003 | Kanonski emiter kataloga Događaji (svoj lifecycle); ne projektuje skladište |
| TS-004 | Kanonski emiter aktivnosti Održavanja kroz katalog Događaji |
| TS-005 | Poslovni izvor Manifestacije; emisija preko kanonskog emitera u uredničkom toku / portalu |
| TS-010 | Kanonski emiter gdje portal potvrđuje završetak radnje (Moderator, Organizatori, Manifestacije, dio Događaja); lokalni ≠ centralni; bez UI centralne |
| TS-011 | Kanonski emiter kataloga Newsletter; skladište/pregled = TS-012 |
| TS-001 | Kanonski emiter Organizator/Moderator gdje upravlja lifecycle-om; inače usklađenje sa TS-010 |

Pravilo: **jedna poslovna radnja → jedan kanonski emiter → jedan audit događaj** (izuzev BR-179: dva događaja / dva `event_id`).

Tok:

```
Poslovna radnja (uspješno sačuvana)
  → Kanonski emiter (TS-003/004/010/011/…)
  → Audit događaj (source_module + event_id)
  → TS-012 prijem + validacija + idempotentnost
  → Trajni audit zapis
     (neuspjeh ovdje ne poništava poslovnu radnju; omogućena ponovna obrada)
  → Admin: minimalni hronološki pristup
```

---

# 14. Validacije

| ID | Pravilo |
|----|---------|
| V-AL-01 | Obavezni atributi događaja moraju biti prisutni |
| V-AL-02 | `activity_type` mora pripadati V1 katalogu |
| V-AL-03 | `actor_type = user` zahtijeva `actor_user_id` |
| V-AL-04 | `actor_type = system` zabranjuje lažni korisnički nalog |
| V-AL-05 | Dupla kombinacija `source_module` + `event_id` → nema novog zapisa |
| V-AL-06 | Tehnički događaji (mail retry, session, …) se ne prihvataju kao Audit |
| V-AL-07 | Redovni update/delete audit zapisa odbija se |
| V-AL-08 | Pristup listi zapisa samo za Administratora platforme |
| V-AL-09 | Neuspjeh prijema/evidentiranja ne smije poništiti niti promijeniti završenu poslovnu radnju |
| V-AL-10 | Naknadna deaktivacija naloga ne smije izmijeniti `actor_user_id` u već sačuvanom zapisu |
| V-AL-11 | Za istu poslovnu radnju smije emitovati samo kanonski emiter |

---

# 15. Matrica sljedivosti

```
BM-AL-01…08 (+ BM-EP-09, BM-GL-09/20, BM-MF-20)
        ↓
FS §5.16 BR-170…188
        ↓
FT-003
        ↓
TS-003 / TS-004 / TS-010 / TS-011  (emisija)
        ↓
TS-012  (prijem, skladište, Admin pristup)
```

| TS sekcija | BM | FS / BR | FT | Emiteri |
|------------|----|---------|----|---------|
| §1–2 Pregled / granice | BM-AL-01–08 | BR-170–175 | FT-003 | — |
| §5–6 Model | BM-AL-01, BM-AL-03 | BR-173; BR-188 | FT-003 | — |
| §7.1 Moderator | BM-AL-07 | BR-177, BR-180–181 | FT-003 | TS-010 / TS-001 |
| §7.2 Organizatori | BM-AL-07 | BR-178–179 | FT-003 | TS-010 / TS-001 |
| §7.3 Manifestacije | BM-AL-07, BM-MF-20 | §5.16 katalog | FT-003 | TS-010 / TS-005 |
| §7.4 Događaji / Održavanja | BM-AL-07 | BR-182–183 | FT-003 | TS-003, TS-004 |
| §7.5 Newsletter | BM-AL-07 | BR-184–186 | FT-003 | TS-011 |
| §8 Prijem | BM-AL-03–05 | BR-170 | FT-003 | kanonski emiteri |
| §8.2 Kanonski emiter | BM-AL-03 | BR-170 | FT-003 | jedan emiter / radnja |
| §8.4.1 Istorijski izvršilac | BM-AL-04 | BR-173; BR-187 | FT-003 | — |
| §9 Nepromjenjivost | BM-AL-04 | BR-187 | FT-003 | — |
| §10–11 Auth / Admin | BM-AL-06 | BR-174–175; BR-188 | FT-003 | — |
| §12 Tehnički log | BM-AL-02 | BR-172, BR-186 | FT-003 | — |
| §17 Van obuhvata | — | BR-188 | FT-003 | — |

---

# 16. Acceptance kriterijumi

AC-AL-01 · Validan audit događaj iz kataloga rezultuje trajnim zapisom.  
AC-AL-02 · Zapis sadrži minimalna polja iz §6.1.  
AC-AL-03 · Automatska radnja iz kataloga ima `actor_type = system`.  
AC-AL-04 · Korisnička radnja čuva `actor_user_id` stvarnog izvršioca.  
AC-AL-05 · Redovni update audit zapisa nije moguć.  
AC-AL-06 · Redovno brisanje audit zapisa nije moguće.  
AC-AL-07 · Moderator / Urednik / Organizator / običan korisnik nemaju direktan pristup centralnoj evidenciji.  
AC-AL-08 · Administrator platforme može pristupiti minimalnom hronološkom pregledu.  
AC-AL-09 · Ponovni prijem iste kombinacije `source_module` + `event_id` ne stvara dupli zapis.  
AC-AL-10 · Tehničke greške Newslettera / retry nisu Audit zapisi.  
AC-AL-11 · Katalog Manifestacije je dio V1 evidentiranja.  
AC-AL-12 · Aktivnosti Održavanja evidentiraju se kroz katalog Događaji, bez zasebnog kataloga Održavanja.  
AC-AL-13 · Newsletter aktivacije/slanja ulaze po BR-185; ne dupliraju katalog Događaji.  
AC-AL-14 · Emisija se ne prihvata za radnje van V1 kataloga (npr. pregled, sitne izmjene, auth platforme).  
AC-AL-15 · Lokalni audit trag ≠ centralni zapis.  
AC-AL-16 · Neuspjeh prijema ili trajnog evidentiranja ne poništava niti mijenja već uspješno završenu poslovnu radnju.  
AC-AL-17 · Audit događaj čije evidentiranje nije uspjelo može se pouzdano ponovo obraditi bez stvaranja duplikata nakon uspjeha.  
AC-AL-18 · Ista poslovna radnja ne proizvodi dva audit događaja iz različitih emitera (jedan kanonski emiter).  
AC-AL-19 · Deaktivacija ili promjena statusa korisničkog naloga ne mijenja niti briše `actor_user_id` već evidentiranog zapisa.  

---

# 17. Van obuhvata (Out of Scope)

U skladu sa **BR-188** i usvojenim V1, TS-012 **ne** uvodi:

1. napredne filtere;
2. naprednu / punu pretragu;
3. sortiranje kao zasebnu funkcionalnost;
4. izvoz (PDF, Excel, CSV, štampa, API izvoza);
5. retention politiku, arhiviranje zapisa, anonimizaciju, sistemsko brisanje;
6. detaljne IP / uređaj / browser podatke;
7. bogati Admin audit UI / explorer / dashboard analitiku;
8. katalog administrativnih sistemskih postavki platforme u okviru Evidencije Kalendara;
9. audit autentikacije i platformske dodjele uloga (BR-176);
10. SQL / migracije / Laravel ugovore u ovom dokumentu.

Posebna politika retencije **nije** predmet V1 dok se poslovno/funkcionalno ne usvoji; do tada važi nepromjenjivost BM-AL-04 / BR-187.

---

# 18. Otvorena pitanja

Nema otvorenih poslovnih pitanja.

Napomena: način prenosa događaja (sinhrono/asinhrono), tačan oblik skladištenja kompozitnog ključa `source_module` + `event_id` i UI detalj hronološke liste su **implementacioni** izbori unutar usvojenog BM/FS okvira.

**Administrativni GAP (nije predmet izmjene u ovom zadatku):** Feature Registry FT-003 u sažetku V1 kataloga ne navodi Manifestacije — uskladiti administrativno kasnije sa BM/FS/TS-012.

---

# 19. Napomene za implementaciju

1. Emisiju vezati na uspješan commit poslovne transakcije, ne na UI klik.
2. Idempotentnost držati na kombinaciji `source_module` + `event_id` (ne zahtijevati globalni UUID).
3. Neuspjeh Evidencije ne smije rollback-ovati poslovnu radnju; omogućiti pouzdanu ponovnu obradu.
4. Jedan kanonski emiter po poslovnoj radnji — bez paralelne emisije iz drugog modula.
5. Ne kreirati korisnički nalog za Sistem.
6. Sačuvati istorijski `actor_user_id`; deaktivacija naloga ne smije narušiti audit zapis.
7. Ne miješati ledger Newsletter dostave sa Audit zapisom.
8. Ne proširivati katalog „radi kompletnosti“ van FS §5.16.
9. Admin V1 = hronološki pristup; filteri ostaju OOS.
10. PATCH-053: ne emitovati „ponovnu objavu“ otkazanog događaja.

---

# Kraj dokumenta
