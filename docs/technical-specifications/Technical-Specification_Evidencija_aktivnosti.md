# Digital Kotor
# Technical Specification
## Evidencija aktivnosti (Audit)

**Feature ID:** FT-003  
**Oznaka dokumenta:** TS-012  
**Funkcionalna cjelina:** Evidencija aktivnosti  
**Modul:** Kalendar kulture  
**Status dokumenta:** USVOJEN  
**Verzija:** 1.0.6
**Datum:** 2026-08-14

**Implementacija FT-003:** **F8-03 CANONICAL EMITTERS IMPLEMENTED (local)** — TS12-* povezani na poslovne tokove preko `CulturalActivityEmitter` + safe recorder. Katalog §7 **KEEP**. V1 audit = **best-effort / failure-isolated** (nema durable replay). `repeatable()` uniqueness = known V1 limitation. Admin UI = **NOT STARTED**. Nije Technical Overview kompletnog FT-003.

---

# Istorija verzija

| Verzija | Datum | Opis |
|---------|--------|------|
| 1.0.0 | 2026-08-07 | Prvi nacrt Technical Specification za funkcionalnu cjelinu Evidencija aktivnosti (Audit). Usklađen sa BM-14 (BM-AL-01–BM-AL-08), BM-EP-09, BM-GL-09, BM-GL-20, BM-MF-20; FS §5.16 (BR-170–BR-188); Feature Registry FT-003; TS-003 v0.1.2, TS-004, TS-010 v1.0.1, TS-011 v1.0.1; METHODOLOGY. Operacionalizuje centralni prijem, trajno evidentiranje, nepromjenjivost, Admin pristup i V1 katalog bez širenja BR-188. Bez izmjene BM/FS/ostalih TS/Feature Registry. Bez izmjene implementacije. |
| 1.0.1 | 2026-08-07 | PATCH-001: završna tehnička usklađenja — jedinstvenost `(source_module, event_id)`; neuspjeh Evidencije ne poništava poslovnu radnju + pouzdana ponovna obrada; kanonski emiter; istorijski integritet izvršioca. Bez novih poslovnih odluka; bez širenja V1; bez izmjene BM/FS/FR/ostalih TS. |
| 1.0.2 | 2026-08-14 | **F8-01 canonical freeze:** status hygiene (uklonjen `(DRAFT)` / Nacrt); uklonjen stale FR-GAP; V1 katalog usklađen sa FS PATCH-FS-074; implementation-ready ugovor (identity, privacy, immutability, failure isolation, Admin V1, TM-AL). **FT-003 implementation = NOT STARTED.** Bez izmjene BM. Bez izmjene implementacije. |
| 1.0.3 | 2026-08-14 | **F8-02 status only:** centralni store foundation (`cultural_activity_records`) **IMPLEMENTED (local)**; katalog §7 **KEEP**; emiteri/Admin UI i dalje NOT STARTED. Pozivni contract: safe recorder nakon uspješnog poslovnog persist-a, van poslovne transakcije. Bez izmjene BM/FS kataloga. |
| 1.0.4 | 2026-08-14 | **F8-03 status only:** kanonski emiteri TS12-* **IMPLEMENTED (local)**; katalog §7 **KEEP**; Admin UI NOT STARTED. Bez izmjene BM/FS kataloga. |
| 1.0.5 | 2026-08-14 | **V1 retry semantics clarification:** failure isolation + idempotent ingest (`source_module` + `event_id`). V1 **ne** garantuje durable replay neupisanog audit događaja nakon završetka procesa. Queue/outbox i dalje nije V1 obaveza. Katalog §7 **KEEP**. Bez izmjene BM/FS. |
| 1.0.6 | 2026-08-14 | **F8-03 PO accept consistency:** `repeatable()` **KNOWN V1 LIMITATION** — nema matematičke/globalne uniqueness garancije kada su katalog ID, entity identity, canonical payload i persist timestamp do µs identični. DB unique `(source_module, event_id)` **KEEP**. Katalog §7 **KEEP**. Bez izmjene BM/FS. |

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
| 1.0.2 | 2026-08-14 | F8-01: USVOJEN bez DRAFT/Nacrt kontradikcije; FR-GAP uklonjen; §7 kanonska matrica + exclusions; §8.6 identity; §6.3 privacy; §11 paginacija; §20 TM-AL; implementacija pending. |
| 1.0.3 | 2026-08-14 | F8-02 status: centralni store foundation; katalog KEEP; emiteri/UI pending. |
| 1.0.4 | 2026-08-14 | F8-03 status: kanonski TS12-* emiteri **IMPLEMENTED (local)**; katalog KEEP; Admin UI pending. |
| 1.0.5 | 2026-08-14 | V1 retry: best-effort / failure-isolated; idempotent ingest; **nema** durable replay garancije; katalog KEEP. |
| 1.0.6 | 2026-08-14 | F8-03 PO accept: `repeatable()` uniqueness limitation; DB unique KEEP; katalog KEEP. |

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
* `docs/functional-specifications/Functional-Specification.md` (§5.16 BR-170–BR-188, BR-349–BR-350; PATCH-FS-074)
* `docs/features/Feature-Registry.md` (FT-003)
* `docs/METHODOLOGY.md` (M-TS-001–M-TS-005)
* `docs/technical-specifications/Technical-Specification_Dogadjaj.md` (TS-003)
* `docs/technical-specifications/Technical-Specification_Odrzavanje.md` (TS-004)
* `docs/technical-specifications/Technical-Specification_Urednicki_portal.md` (TS-010)
* `docs/technical-specifications/Technical-Specification_Newsletter.md` (TS-011 v1.0.3)

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | USVOJENO |
| 2. Granice odgovornosti | USVOJENO |
| 3. Arhitektonski principi | USVOJENO |
| 4. Komponente | USVOJENO |
| 5. Model audit događaja | USVOJENO |
| 6. Model audit zapisa | USVOJENO |
| 7. Katalog V1 | USVOJENO |
| 8. Prijem i evidentiranje | USVOJENO |
| 9. Nepromjenjivost | USVOJENO |
| 10. Autorizacija | USVOJENO |
| 11. Admin pristup | USVOJENO |
| 12. Razdvajanje od tehničkih logova | USVOJENO |
| 13. Integracije | USVOJENO |
| 14. Validacije | USVOJENO |
| 15. Matrica sljedivosti | USVOJENO |
| 16. Acceptance kriterijumi | USVOJENO |
| 17. Van obuhvata (Out of Scope) | USVOJENO |
| 18. Otvorena pitanja | USVOJENO |
| 19. Napomene za implementaciju | USVOJENO |
| 20. Test specification matrix (TM-AL) | USVOJENO |

---

# Pravila upravljanja ovim dokumentom

1. TS-012 pripada **FT-003** – Evidencija aktivnosti (Kalendar kulture).
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM i FS.
3. Nova poslovna pravila se ne uvode kroz Technical Specification.
4. Sve što nije definisano u BM ili FS, a zahtijeva poslovnu odluku, evidentira se kao **Otvoreno pitanje**.
5. Tehnički predlozi (zaštita od duplikata po `source_module` + `event_id`, kanonski emiter, izolacija neuspjeha Evidencije, istorijski izvršilac, minimalni hronološki prikaz) nisu nova poslovna pravila.
6. Product Owner donosi poslovne odluke; ovaj dokument ih ne pretpostavlja.
7. Granice V1 iz **BR-188** ostaju na snazi: napredni Admin UI, filteri, izvoz, retention i detaljna polja nisu dio V1. Paginacija hronološke liste je tehnički dozvoljena.

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
6. **Izolacija neuspjeha** — neuspjeh Evidencije ne poništava već završenu poslovnu radnju. Ponovni prijem iste već konstruisane kombinacije `source_module` + `event_id` je idempotentan. V1 **ne** garantuje durable replay neupisanog događaja nakon završetka procesa.
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

## 6.3 Dodatni kontekst (privacy-minimal)

Opcioni `context` objekat smije sadržati **samo** identifikatore i šifre potrebne za reviziju (npr. `request_id`, `occurrence_id`, `cycle_id`, `activity_code`).

**Zabranjeno** automatski čuvati:

* lozinke, hash lozinki, session, CSRF;
* unsubscribe tokene, invitation tokene, API tajne;
* kompletan request body / model snapshot;
* e-mail kao SSOT (koristiti `actor_user_id` / `object_id`);
* privatne razloge otkazivanja/odlaganja osim ako FS ne nalaže da je sam razlog predmet revizije — u V1 se evidentira **radnja** unosa razloga, a tekst razloga ostaje na entitetu;
* Newsletter delivery ledger sadržaj.

---

# 7. Katalog V1

Poslovni SSOT kataloga je **FS §5.16** (uključujući PATCH-FS-074 / BR-349–BR-350). TS-012 ne dodaje aktivnosti van tog kataloga. Tehnički ID-jevi `TS12-*` su stabilni identifikatori za emiter/testove; nisu nove RG-001 skraćenice.

Feature Registry **uključuje** Manifestacije u V1 katalogu FT-003. Stari FR-GAP je **zatvoren**.

Jedinstvenost zapisa Moderator aktivnosti: BR-180. Promjena aktivnog konteksta **nije** zaseban zapis (BR-181). Pri odobrenju kreiranja Organizatora: **dva** `event_id` (BR-179).

## 7.1 Kanonska matrica V1

| ID | Source module | Event type | Poslovna radnja | Actor | Target | Minimal context | BM/FS source |
| -- | ------------- | ---------- | --------------- | ----- | ------ | --------------- | ------------ |
| TS12-MOD-01 | TS-001 | `mod.add.submit` | Podnošenje zahtjeva za dodjelu Moderatora | User | moderator_request | request_id; organizer_id | BR-177 |
| TS12-MOD-02 | TS-001 | `mod.add.approve` | Odobravanje dodjele Moderatora | User | moderator_request | request_id; user_id granta | BR-177 |
| TS12-MOD-03 | TS-001 | `mod.add.reject` | Odbijanje dodjele Moderatora | User | moderator_request | request_id | BR-177 |
| TS12-MOD-04 | TS-001 | `mod.remove.submit` | Pokretanje uklanjanja Moderatora | User | moderator_request | request_id; organizer_id | BR-177 |
| TS12-MOD-05 | TS-001 | `mod.remove.approve` | Odobravanje uklanjanja Moderatora | User | moderator_request | request_id | BR-177 |
| TS12-MOD-06 | TS-001 | `mod.remove.reject` | Odbijanje uklanjanja Moderatora | User | moderator_request | request_id | BR-177 |
| TS12-MOD-07 | TS-001 | `mod.request.eligible` | Čeka registraciju → Podnesen (ADD ili Org-creation predloženi Moderator) | Sistem | request | request_id | BR-349; BR-314 |
| TS12-ORG-01 | TS-001 | `org.request.submit` | Podnošenje zahtjeva za kreiranje Organizatora | User | organizer_request | request_id | BR-178 |
| TS12-ORG-02 | TS-001 | `org.request.approve` | Odobrenje zahtjeva i kreiranje Organizatora | User | organizer | request_id; organizer_id | BR-178; BR-179 (zapis 1) |
| TS12-ORG-03 | TS-001 | `org.request.reject` | Odbijanje zahtjeva za kreiranje Organizatora | User | organizer_request | request_id | BR-178 |
| TS12-ORG-04 | TS-001 | `org.deactivate` | Deaktivacija Organizatora | User | organizer | organizer_id | BR-178 |
| TS12-ORG-05 | TS-003 | `org.event.link` | Naknadno povezivanje događaja sa Organizatorom | User | event | entry_id; organizer_id | BR-178; BR-052 |
| TS12-ORG-06 | TS-001 | `org.profile.significant` | Poslovno značajna izmjena podataka Organizatora | User | organizer | organizer_id | BR-178 |
| TS12-ORG-07 | TS-001 | `org.initial_moderator.grant` | Dodjela početnog Moderatora pri odobrenju kreiranja | User | moderator_grant | organizer_id; user_id | BR-179 (zapis 2); BR-180 |
| TS12-EV-01 | TS-003 | `event.create` | Kreiranje događaja | User | event | entry_id | BR-182 |
| TS12-EV-02 | TS-003 | `event.submit` | Slanje na odobrenje | User | event | entry_id | BR-182 |
| TS12-EV-03 | TS-003 | `event.return` | Vraćanje na doradu | User | event | entry_id | BR-182 |
| TS12-EV-04 | TS-003 | `event.resubmit` | Ponovno slanje na odobrenje | User | event | entry_id | BR-182 |
| TS12-EV-05 | TS-003 | `event.approve` | Odobravanje događaja | User | event | entry_id | BR-182 |
| TS12-EV-06 | TS-003 | `event.direct_publish` | Direktna objava Urednika | User | event | entry_id | BR-182 |
| TS12-EV-07 | TS-003 | `event.feature` | Isticanje događaja | User | event | entry_id | BR-182 |
| TS12-EV-08 | TS-003 | `event.unfeature` | Uklanjanje isticanja | User | event | entry_id | BR-182 |
| TS12-EV-09 | TS-003 | `event.cancel` | Otkazivanje događaja | User | event | entry_id | BR-182 |
| TS12-EV-10 | TS-003 | `event.cancellation_reason` | Unos/dopuna razloga otkazivanja | User | event | entry_id | BR-182 |
| TS12-EV-11 | TS-004 | `occ.postpone` | Odlaganje Održavanja | User | occurrence | occurrence_id; entry_id | BR-182 |
| TS12-EV-12 | TS-004 | `occ.cancel` | Otkazivanje pojedinačnog Održavanja (nije kaskada Event cancel) | User | occurrence | occurrence_id; entry_id | BR-182 |
| TS12-EV-13 | TS-004 | `occ.reschedule` | Promjena termina Održavanja | User | occurrence | occurrence_id; entry_id | BR-182 |
| TS12-EV-14 | TS-004 | `occ.location_change` | Promjena lokacije Održavanja | User | occurrence | occurrence_id; entry_id | BR-182 |
| TS12-EV-15 | TS-003 | `event.proposal.submit` | Podnošenje prijedloga izmjena | User | proposal | proposal_id; entry_id | BR-182 |
| TS12-EV-16 | TS-003 | `event.proposal.approve` | Odobravanje prijedloga izmjena | User | proposal | proposal_id; entry_id | BR-182 |
| TS12-EV-17 | TS-003 | `event.proposal.return` | Vraćanje prijedloga na doradu | User | proposal | proposal_id; entry_id | BR-182 |
| TS12-EV-18 | TS-003 | `event.auto_archive` | Automatsko arhiviranje događaja | Sistem | event | entry_id | BR-182; BR-184 |
| TS12-EV-19 | TS-004 | `occ.auto_finish` | Automatsko završavanje Održavanja | Sistem | occurrence | occurrence_id; entry_id | BR-349; BR-068 |
| TS12-EV-20 | TS-003 | `event.published_direct_edit` | Direktna izmjena objavljenog (Urednik, bez registrovanog Org) | User | event | entry_id | BR-349; BR-292 |
| TS12-EV-21 | TS-003 | `event.unpublished_delete` | Trajno brisanje nikad objavljenog događaja | User | event | entry_id | BR-349; BR-290 |
| TS12-MF-01 | TS-005 | `mf.create` | Kreiranje Manifestacije | User | manifestation | manifestation_id | BM-MF-20; §5.16 |
| TS12-MF-02 | TS-005 | `mf.submit` | Slanje na odobrenje | User | manifestation | manifestation_id | §5.16 |
| TS12-MF-03 | TS-005 | `mf.return` | Vraćanje na doradu | User | manifestation | manifestation_id | §5.16 |
| TS12-MF-04 | TS-005 | `mf.publish` | Odobravanje / objava (uključujući uredničku direktnu objavu) | User | manifestation | manifestation_id | §5.16; PO-MF-WF |
| TS12-MF-05 | TS-005 | `mf.cancel` | Otkazivanje Manifestacije | User | manifestation | manifestation_id | §5.16 |
| TS12-MF-06 | TS-005 | `mf.auto_archive` | Automatsko arhiviranje Manifestacije | Sistem | manifestation | manifestation_id | §5.16; BR-184 |
| TS12-MF-07 | TS-005 | `mf.event.add` | Dodavanje događaja Manifestaciji | User | manifestation | manifestation_id; entry_id | §5.16 |
| TS12-MF-08 | TS-005 | `mf.event.remove` | Uklanjanje događaja iz Manifestacije | User | manifestation | manifestation_id; entry_id | §5.16 |
| TS12-MF-09 | TS-005 | `mf.event.move` | Premještanje događaja između Manifestacija | User | manifestation | from_id; to_id; entry_id | §5.16 |
| TS12-MF-10 | TS-005 | `mf.organizer.change` | Promjena Organizatora Manifestacije | User | manifestation | manifestation_id; organizer_id | §5.16 |
| TS12-MF-11 | TS-005 | `mf.cover.change` | Promjena naslovne fotografije | User | manifestation | manifestation_id | §5.16 |
| TS12-MF-12 | TS-005 | `mf.webinfo.change` | Promjena Web stranica / Više informacije | User | manifestation | manifestation_id | §5.16 |
| TS12-NL-01 | TS-011 | `nl.activate` | Aktivacija pretplate | User | subscription | subscription_id | BR-185 |
| TS12-NL-02 | TS-011 | `nl.unsubscribe` | Odjava | User | subscription | subscription_id | BR-185 |
| TS12-NL-03 | TS-011 | `nl.reactivate` | Ponovna aktivacija | User | subscription | subscription_id | BR-185 |
| TS12-NL-04 | TS-011 | `nl.preferences.change` | Promjena izbora Organizatora / preferenci | User | subscription | subscription_id | BR-185 |
| TS12-NL-05 | TS-011 | `nl.send.regular` | Slanje redovnog Newslettera | Sistem | newsletter_cycle | cycle_id | BR-185; BR-184 |
| TS12-NL-06 | TS-011 | `nl.send.priority` | Slanje prioritetnog obavještenja | Sistem | newsletter_cycle | cycle_id | BR-185; BR-184 |

Kanonski emiter u koloni Source module je **vlasnik lifecycle-a**. Ako portal (TS-010) izvršava radnju, i dalje emituje **jedan** kanonski emiter entiteta (TS-001/003/004/005/011), ne paralelno TS-010.

## 7.2 Explicit exclusions

| Radnja | Razlog isključenja |
| ------ | ------------------ |
| Login / logout / verifikacija / lozinka / platformske uloge | BR-176 |
| Promjena aktivnog Org konteksta | BR-181 |
| GET / pregled / validaciona greška / autosave | nije poslovna odluka (BR-173) |
| Uređivanje nacrta; Sačuvaj i nastavi; generator OCC na Nacrtu | BR-183; BR-350 |
| Kaskadno otkazivanje OCC uz Event cancel | PO-AUTO-01; jedan zapis TS12-EV-09 |
| Dismiss odbijenog zahtjeva (BR-326/327) | workspace cleanup; lokalni trag BM-ORG-20 / BM-MOD-27 |
| Invitation / outcome e-mail i mail retry | BM-AL-08; BR-319; nije audit |
| Newsletter delivery ledger i SMTP retry | BR-186; ledger ≠ audit store |
| Pregled `/newsletter` bez izmjene | BR-186 |
| CRUD Lokacija / Kategorija / Medija | van BM-AL-07 |
| Brisanje Manifestacije; Arhiva MF lista; naslovni MF | van V1 |
| MOD-UX / navigacija / label korekcije | nije poslovna radnja |
| Cron tick bez katalog radnje | nije poslovna radnja |
| Tehnički Laravel/exception log | BM-AL-02 |
| Ponovna aktivacija Organizatora | nije usvojena u BM/FS |

---

# 8. Prijem i evidentiranje

## 8.1 Ugovor prijema

1. Emiter šalje audit događaj **tek nakon** uspješno završene i trajno sačuvane poslovne radnje.
2. Emiter određuje `activity_type` u skladu sa FS katalogom; TS-012 ne tumači poslovne razloge radnje.
3. TS-012 validira obavezne atribute i pripadnost katalogu.
4. TS-012 upisuje audit zapis.
5. Ponovni prijem iste kombinacije **`source_module` + `event_id`** **ne** stvara novi zapis (tehnička zaštita od duplikata).
6. **Neuspjeh** prijema ili trajnog evidentiranja audit događaja **ne smije** retroaktivno poništiti niti promijeniti već uspješno završenu poslovnu radnju.
7. Ako isti već konstruisani audit događaj (`source_module` + `event_id`) ponovo stigne, prijem je **idempotentan** (nema drugog zapisa). V1 **ne** obavezuje durable replay nakon završetka procesa.

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
| **Korisnik** | `actor_type = user`; `actor_user_id` stvarnog izvršioca (nalog). Uloga (Urednik/Moderator/Administrator) **nije** zaseban actor tip — SSOT je User + ovlašćenje u trenutku radnje. |
| **Sistem** | `actor_type = system`; `actor_user_id` prazan; **ne** izmišlja se nalog „Sistem“ |

Primjeri Sistem (BR-184): automatsko arhiviranje događaja/manifestacije; automatsko završavanje Održavanja; resolver Čeka→Podnesen; slanje redovnog/prioritetnog Newslettera.

### 8.4.1 Istorijski izvršilac

Audit zapis **trajno** čuva identitet izvršioca kakav je bio u trenutku nastanka poslovno značajne radnje.

Naknadna deaktivacija ili promjena statusa korisničkog naloga **ne smije** izmijeniti niti učiniti neodređenim izvršioca već evidentirane aktivnosti.

TS-012 **ne** određuje politiku životnog ciklusa korisničkih naloga; određuje samo da ta politika **ne smije** narušiti istorijski integritet Evidencije aktivnosti.

## 8.5 Zaštita od duplikata (tehnički predlog)

Kanonski ključ jedinstvenosti: kombinacija **`source_module` + `event_id`** (evidentirana kao `ingestion_event_id` ili ekvivalentan jedinstveni indeks).

Globalni UUID **nije** obavezno pravilo.

Implementacioni izbor (indeks, upsert) nije propisan; semantika V1 jeste:

* ista već konstruisana kombinacija `source_module` + `event_id` → najviše jedan zapis (**idempotent ingest**);
* neuspješno evidentiranje **ne** rollbackuje poslovnu radnju;
* V1 **ne** uvodi outbox, queue niti pending tabelu;
* V1 **ne** garantuje da će neupisani audit događaj kasnije biti rekonstruisan i ponovo poslat nakon što se proces/request završi.

Izuzetak semantike BR-179: odobrenje kreiranja Organizatora proizvodi **dva** događaja (dva `event_id` kod istog kanonskog emitera), ne jedan.

## 8.6 Identitet `event_id` (V1)

1. `event_id` dodjeljuje **kanonski emiter**, jedinstven **unutar** `source_module` **na nivou store-a** (nije obavezan globalni UUID). Jedinstvenost zapisa je DB ugovor `(source_module, event_id)`.
2. `event_id` **treba** razlikovati dvije poslovne radnje čiji se identity input razlikuje (katalog ID, entitet/kontekst, canonical payload i/ili persist vrijeme). V1 **ne** daje matematičku/globalnu uniqueness garanciju kada su svi ti ulazi identični (vidi known limitation za `repeatable()`).
3. `event_id` mora biti **deterministički unutar konkretnog emit pokušaja**. Ako se ista već poznata poslovna identiteta ponovo emituje (npr. `once()` na request/cycle/create id), koristi se isti `event_id` — **ne** random per-call ID.
4. Random UUID je dozvoljen **samo** ako je isti identifikator sačuvan uz poslovnu radnju **prije** emitovanja. V1 **ne** uvodi random UUID radi uniqueness.
5. Ponovni prijem već konstruisanog `source_module` + `event_id` → prijem je no-op (nema novog zapisa) — **ingest idempotency**.
6. Dvije radnje nad istim entitetom koje se razlikuju katalogom, payloadom ili persist vremenom (npr. postpone pa cancel) = **dva** `event_id`.
7. Korekcija pogrešno emitovanog zapisa **nije** UPDATE starog; ako poslovna korekcija postoji, to je **novi** audit događaj. Pogrešan emit bez poslovne korekcije ostaje u evidenciji (nema delete API).
8. V1 **ne tvrdi** da se `event_id` može rekonstruisati nakon potpunog audit failure-a i završetka procesa ako identitet događaja nije trajno sačuvan u poslovnom modelu (**durable replay nije V1 garancija**).

**Known V1 limitation (`repeatable()`):** identitet se izvodi iz TS12 katalog ID-a, business entity/context identiteta, canonical payload digest-a i persist vremena do mikrosekunde. Dvije odvojene poslovne radnje sa potpuno istim tim ulazima mogu proizvesti isti `event_id`. To **nije** durable retry problem, **nije** razlog za migraciju, outbox ni random UUID, i **ne** mijenja DB unique `(source_module, event_id)`.

## 8.7 Failure isolation — V1 best-effort (bez durable replay)

Neuspjeh prijema/store-a **ne** rollbackuje poslovnu radnju (PATCH-001; BM-AL-05; BR-170).

**V1 semantika:**

* Centralna Evidencija aktivnosti pokušava upis **odmah** nakon uspješne poslovne radnje.
* Neuspjeh evidencije **ne smije** oboriti poslovnu radnju.
* Neuspjeh se **tehnički loguje** (nije Audit zapis).
* Store ostaje idempotentan za ponovljeni prijem istog `source_module` + `event_id`.
* V1 **ne** garantuje trajni replay neupisanog audit događaja nakon završetka procesa.
* Queue / outbox / pending tabela **nije** V1 obaveza i **nije** uvedena.

**Known V1 limitation:** audit događaj koji ne uspije da se upiše nakon business success-a nema garantovan durable replay u istoj verziji sistema. Tehnički log ostaje jedini failure signal. To **nije** „eventualno će sigurno biti upisan“.

Durable outbox/retry (isti `event_id` nakon gubitka procesa) je eventualni **V1.1/V2 backlog**, ne F8-03 acceptance criterion.

---

# 9. Nepromjenjivost

1. Nakon nastanka audit zapis se **ne uređuje** kroz redovne aplikativne tokove (**nema UPDATE API**).
2. Audit zapis se **ne briše** kroz redovno korišćenje (**nema DELETE API**).
3. Korekcija poslovnog stanja entiteta **ne mijenja** prethodni audit zapis.
4. Nova propisana poslovna radnja proizvodi **novi** audit događaj / zapis (korekcija ≠ edit starog zapisa).
5. Identitet izvršioca u zapisu ostaje istorijski tačan i nakon naknadne deaktivacije ili promjene imena/uloge naloga (vidi §8.4.1).
6. Admin samo čita. Posebna retention / anonimizacija / sistemsko brisanje **nije** V1 (BR-188); vidi §17.
7. DB trigger **nije** V1 obaveza.

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

Tehnički se dozvoljava **osnovni hronološki prikaz** zapisa (lista po `occurred_at` silazno) dovoljan da Administrator pristupi evidenciji, plus **paginacija** kao tehnička nužnost V1.

Osnovni prikaz jednog zapisa: envelope iz §6.1 + privacy-minimal `context`.

**CANON SILENT — implementation choice later** za konkretni Laravel URI. Implementacija prati postojeći obrazac Administracije platforme; ovaj TS ne izmišlja kanonski path.

**Ne uvodi se** (BR-188):

* napredni filteri;
* puna pretraga;
* sortiranje kao zasebna poslovna funkcionalnost (osim hronološkog defaulta);
* izvoz;
* retention UI;
* delete / edit / bulk;
* dashboard analitika;
* Moderator/Urednik feed.

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
     (neuspjeh ovdje ne poništava poslovnu radnju; V1 best-effort, bez durable replay)
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
FS §5.16 BR-170…188, BR-349–350
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
AC-AL-17 · Ponovni prijem već konstruisanog `source_module` + `event_id` ne stvara duplikat. V1 **ne** zahtijeva reconstructed retry nakon gubitka procesa.
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

Napomena: način prenosa događaja (sinhrono/asinhrono), tačan oblik skladištenja kompozitnog ključa `source_module` + `event_id` i UI detalj hronološke liste / URI su **implementacioni** izbori unutar usvojenog BM/FS okvira (**CANON SILENT**).

---

# 19. Napomene za implementaciju

1. Emisiju vezati na uspješan commit poslovne transakcije, ne na UI klik.
2. Idempotentnost držati na kombinaciji `source_module` + `event_id` (deterministički `event_id`; ne zahtijevati globalni UUID).
3. Neuspjeh Evidencije ne smije rollback-ovati poslovnu radnju. Idempotent ingest za isti već konstruisani `event_id`. Queue/outbox **nije** V1 obaveza. Durable replay nakon završetka procesa **nije** V1 garancija.
4. Jedan kanonski emiter po poslovnoj radnji — bez paralelne emisije iz TS-010 i TS-003 za istu radnju.
5. Ne kreirati korisnički nalog za Sistem.
6. Sačuvati istorijski `actor_user_id`; deaktivacija ili promjena imena/uloge ne smije narušiti audit zapis.
7. Ne miješati ledger Newsletter dostave sa Audit zapisom.
8. Ne proširivati katalog van FS §5.16 / BR-349; poštovati §7.2 exclusions.
9. Admin V1 = hronološki pristup + paginacija; filteri ostaju OOS.
10. PATCH-053: ne emitovati „ponovnu objavu“ otkazanog događaja.
11. Nema aplikacionog UPDATE/DELETE API-ja za audit zapise; Admin samo čita.
12. **FT-003:** F8-02 = centralni store; F8-03 = kanonski emiteri (local); Admin UI = F8-04 (nije započet).
13. Safe facade (`CulturalActivityRecorder::record`) zvati **nakon** uspješnog persist-a poslovne radnje; ne u istoj DB transakciji čiji bi rollback poništio poslovni zapis zbog audit greške.

---

# 20. Test specification matrix

Bez test koda u ovom paketu. Naredna implementacija mora dokazati:

| TM ID | Scenario | Expected |
| ----- | -------- | -------- |
| TM-AL-01 | Validan emit iz kataloga | Trajni zapis; polja §6.1 |
| TM-AL-02 | Duplicate `source_module` + `event_id` | Nema drugog zapisa |
| TM-AL-03 | Ponovni prijem **već konstruisanog** `source_module` + `event_id` (isti input) | Najviše jedan zapis (idempotent ingest). **Nije** reconstructed retry nakon gubitka procesa |
| TM-AL-04 | Pokušaj UPDATE audit zapisa | Odbijeno / nema API |
| TM-AL-05 | Pokušaj DELETE audit zapisa | Odbijeno / nema API |
| TM-AL-06 | User actor | `actor_type=user` + `actor_user_id` |
| TM-AL-07 | Sistem actor (npr. auto-archive / NL send / auto-finish) | `actor_type=system`; prazan user id |
| TM-AL-08 | Emit failure nakon uspješne poslovne radnje | Poslovna radnja ostaje; tehnički log |
| TM-AL-09 | Guest / Moderator / Urednik / običan User na Admin listi | 403 / nema pristupa |
| TM-AL-10 | Administrator platforme | Vidi hronološku listu |
| TM-AL-11 | Redoslijed | Noviji `occurred_at` prije starijeg |
| TM-AL-12 | Paginacija | Druga stranica ne duplira prvu; stabilan poredak |
| TM-AL-13 | Privacy | Nema tokena/lozinke/unsubscribe tokena/request body u `context` |
| TM-AL-14 | Dva različita `TS12-*` nad istim entitetom | Dva zapisa |
| TM-AL-15 | BR-179 | Tačno dva zapisa; nema trećeg MOD duplikata |
| TM-AL-16 | Newsletter ledger red | Nije audit zapis; TS12-NL-05/06 su zasebni |
| TM-AL-17 | Emit van kataloga (npr. GET, dismiss, draft save) | Nema audit zapisa |
| TM-AL-18 | Integracija TS-001 | Bar jedan MOD/ORG emit iz §7.1 |
| TM-AL-19 | Integracija TS-003/004 | Bar jedan EV/OCC emit |
| TM-AL-20 | Integracija TS-005 | Bar jedan MF emit |
| TM-AL-21 | Integracija TS-011 | Bar jedan NL emit |
| TM-AL-22 | Deaktivacija User nakon zapisa | `actor_user_id` nepromijenjen |

---

# Kraj dokumenta
