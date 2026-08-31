# Digital Kotor
# Technical Specification
## Funkcionalnost: Obavještenja (platformska funkcionalnost Digital Kotora)

**Feature ID:** FT-004  
**Oznaka dokumenta:** DK-TS-001  
**Funkcionalna cjelina:** Obavještenja (platformska prezentacija)  
**Status dokumenta:** U IZRADI  
**Verzija:** 0.1.1
**Datum:** 2026-08-31
**Namespace:** DK-* (platforma Digital Kotor)
**Istorijski document ID:** TS-013

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-31 | Početna Technical Specification (TS-013) za FT-004. Jedna koherentna Laravel implementacija usvojenih FR-OB-001 do FR-OB-017. Neriješeni OFD evidentirani kao tehnički blocker-i. Bez uvođenja novih poslovnih pravila. |
| 2026-08-17 | 2026-08-17 | Administrativna migracija dokumentacionog ID-a sa `TS-013` na `DK-TS-001`; poslovni, funkcionalni i tehnički sadržaj ostaju nepromijenjeni. |
| 2026-08-29 | 2026-08-29 | Usklađivanje sa DK-FS-001 PATCH-FS-OB-002 i KN-FS-003 v0.1.16 za source-specific objavu i korekciju zvanične Odluke Konkursa. Razdvojene panel vidljivost i javna dostupnost; persisted trag korekcije; `competition_decision_html` nije target delivery zvanične Odluke. Header 0.1 KEEP. Status U IZRADI. Runtime **nije** implementiran ovim redoslijedom. |
| 0.1.1 | 2026-08-31 | Implementation-state closeout nakon Phase C. Delivery `competition_decision_signed_copy`, first publication i source-specific korekcija zvanične Odluke Konkursa prešli su iz target/design u **IMPLEMENTED** runtime. Ordinary UC-OB-005 semantika KEEP. OFD-OB-006 i OFD-OB-007 ostaju generički OTVORENO. Header 0.1.1. Status U IZRADI. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument opisuje **jednu** tehnički koherentnu realizaciju usvojene Functional Specification za Obavještenja.

DK-TS-001:

* implementira usvojene FR-OB zahtjeve;
* **ne** uvodi nova poslovna pravila;
* **ne** rješava Product Owner odluke (OFD) generički;
* **ne** mijenja Business Model, Use Case Specification ni Functional Specification;
* **ne** predstavlja napisan aplikativni kod.

Izvori istine:

* **DK-BM-001** — `docs/business-model/Business_Model_Obavjestenja.md`
* **DK-UC-001** — `docs/use-cases/Use_Cases_Obavjestenja.md`
* **DK-FS-001** — `docs/functional-specifications/Functional_Specification_Obavjestenja.md` (v0.1 + PATCH-FS-OB-001 + **PATCH-FS-OB-002**)
* **DK-FR-001** — `docs/features/Feature-Registry_Digital-Kotor.md` (v1.1.0)
* **KN-FS-003** v0.1.16 — `docs/functional-specifications/Functional-Specification_Konkursi_Zensko_Preduzetnistvo.md` (source-specific binding za zvaničnu Odluku Konkursa: §15.6, §15.7.1, §15.7.5, §16.6, §18.7.4)
* `docs/METHODOLOGY.md` (M-TS-001 … M-TS-005)

---

# Status razvoja Technical Specification

| Poglavlje | Status |
|-----------|--------|
| 1. Pregled funkcionalne cjeline | U IZRADI |
| 2. Arhitektonski principi | U IZRADI |
| 3. Tehnički model | U IZRADI |
| 4. Tokovi | U IZRADI |
| 5. Autorizacija i ovlašćenja | U IZRADI |
| 6. Model podataka | U IZRADI |
| 7. Validacije | U IZRADI |
| 8. Evidencija aktivnosti (Audit) | U IZRADI |
| 9. Integracije | U IZRADI |
| 10. Nefunkcionalni zahtjevi | U IZRADI |
| 11. Granice V1 (Out of Scope) | U IZRADI |
| 12. Otvorena pitanja | OTVORENO |
| 13. Matrica sljedivosti | U IZRADI |
| 14. Napomene za implementaciju | U IZRADI |

Ukupan status dokumenta: **U IZRADI** (zbog OFD blocker-a koji ograničavaju potpunu produkcijsku pokrivenost svih FR; source-specific KN binding ne zatvara OFD generički).

Source-specific runtime za zvaničnu Odluku Konkursa (`competition_decision_signed_copy`, first publication, KN korekcija) **jeste implementiran**. Ovaj dokument više **ne** tretira taj tok kao nerazvijeni TARGET. Generički OFD-OB-006 / OFD-OB-007 ostaju OTVORENO.

---

# Pravila upravljanja dokumentom

1. DK-TS-001 pripada FT-004 – Obavještenja.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM, UC i FS.
3. Nova poslovna pravila se ne uvode kroz DK-TS-001.
4. Ako implementacija zahtijeva rješavanje OFD, evidentira se blocker; OFD se **generički** ne rješava u TS. Source-specific binding iz KN-FS-003 / PATCH-FS-OB-002 nije zatvaranje OFD za sve izvore.
5. Izmjene sadržaja evidentiraju se novim redom u istoriji verzija. Ovaj dokument **nema** `PATCH-TS-*` porodicu.

---

# 1. Pregled funkcionalne cjeline

**Sekcijska sljedivost:** BM-OB-01–BM-OB-13; UC-OB-001–UC-OB-005; FR-OB-001–FR-OB-017; DK-FS PATCH-FS-OB-002; KN-FS-003 v0.1.16

## 1.1 Svrha

Obavještenja su unakrsna platformska prezentacija: javni panel na početnoj stranici i kanal automatskog nastajanja unosa koji referencira zvanični sadržaj izvornih funkcionalnosti.

## 1.2 Obuhvat ovog TS

* tabela i model Obavještenja;
* servis objave;
* događaj koji okida automatsku objavu (pokreće ga izvorna funkcionalnost);
* javne rute i Blade prezentacija panela;
* javna isporuka referenciranog zvaničnog sadržaja van administrativnog interfejsa;
* razdvajanje vidljivosti u aktivnom panelu od javne dostupnosti sadržaja konkretnog Notice-a;
* source-specific isporuka i korekcija zvanične Odluke Konkursa kao **kanal**, ne kao vlasnik dokumenta;
* testovi i uticaj na deploy/migracije.

## 1.3 Van ovog TS (usvojeno FS/BM)

Ručna objava, urednički workflow, arhiva, read-tracking, inbox, kriterijum „odgovarajuće“ zamjene, redoslijed, maksimum stavki, izvor opisa, tačni okidači po **svim** izvornim funkcionalnostima (OFD-OB-006 ostaje generički OTVORENO).

**Nije** dio ovog TS:

* Competition SQL / KN data schema;
* storage driver, disk, filesystem path, signed-URL tehnologija;
* HTTP status / redirect za nedostupan sadržaj;
* Notice CMS / admin CRUD Obavještenja;
* zamjena učitanog ali još neobjavljenog primjerka (namjerno nedefinisano).

Za zvaničnu Odluku Konkursa konkretan okidač objave i korekcija **jesu** vezani KN-FS-003 v0.1.16; to **nije** generički okidač za tenderi/ostale izvore.

## 1.4 Oznaka dokumenta

**DK-TS-001** (lokalna numeracija unutar `DK-*` namespace-a). Istorijski document ID: `TS-013`. `TS-013` se ne koristi kao aktivni kanonski document ID.

---

# 2. Arhitektonski principi

**Sekcijska sljedivost:** FR-OB-001–FR-OB-017; BM-OB-07; BM-OB-12; DK-FS PATCH-FS-OB-002; KN-FS-003 §15.6, §15.7.1, §15.7.5

## 2.1 Mjesto u sistemu

Obavještenja su **platformski sloj prezentacije**, ne zaseban Composer paket.

Realizacija se uklapa u postojeću Laravel monolitnu strukturu:

* kontroleri u `app/Http/Controllers/`;
* modeli u `app/Models/`;
* Blade u `resources/views/`;
* rute u `routes/web.php`;
* događaji/listeneri u `app/Events/` i `app/Listeners/`.

## 2.2 Odgovornosti

| Komponenta | Odgovornost |
|------------|-------------|
| Izvorna funkcionalnost | Utvrđuje spremnost/obaveznost objave (FR-OB-011) i emituje događaj; po potrebi zahtijeva ordinary panel supersession i/ili korekciju/public revoke |
| Modul **Konkursi** (za zvaničnu Odluku) | Vlasnik je zvaničnog dokumenta; čuva nepromjenjivi elektronski primjerak; određuje koji primjerak je predmet objave; Administrator konkursa pokreće objavu i korekciju |
| `NoticePublicationService` | Kreira zapis objave (Notice); postavlja vidljivost u aktivnom panelu; po pozivu vrši ordinary panel supersession; pri korekciji upisuje persisted trag i javno povlačenje prethodnog |
| `HomeController` + Blade partial | Render panela na početnoj stranici za sve posjetioce |
| `PublicNoticeContentController` | Javna isporuka referenciranog zvaničnog sadržaja **samo ako je konkretna objava javno dostupna** |
| Obavještenja sloj | **Ne** utvrđuje da li je postupak spreman za objavu (FR-OB-012); **ne** je vlasnik Odluke; **ne** kopira Odluku u Notice storage |

## 2.3 Jedna arhitektura automatske objave

Usvojena arhitektura (jedina u ovom TS):

1. Izvorna funkcionalnost dispečuje domen događaj `OfficialContentReadyForPublicPublication`.
2. Listener `PublishOfficialContentNotice` sinhrono poziva `NoticePublicationService::publish(...)`.
3. Nema queue job-a za V1 ovog TS (nije potreban za usvojene FR).
4. Nema Laravel Scheduler zadatka za Obavještenja.
5. Nema administratorskog odobrenja u toku Obavještenja (FR-OB-014). Objava zvanične Odluke Konkursa je radnja **izvornog** modula, ne Notice CMS.

## 2.4 Javni pristup sadržaju

Referenca u panelu vodi na **javnu** rutu koju kontroliše `PublicNoticeContentController`, ne na administrativne rute (FR-OB-010).

Ista konceptualna ruta ostaje kanal. Prije isporuke sadržaja provjerava se **javna dostupnost konkretne objave**. Ako objava nije javno dostupna, sadržaj se **ne** servira.

Potpisani primjerak **ne** smije zavisiti od stabilnog direktnog public storage URL-a koji zaobilazi tu provjeru.

Ovaj TS **ne** određuje HTTP status ni redirect.

## 2.5 Zabrane dizajna

* Ne koristiti postojeću stub tabelu `notifications` ni `NotificationController` (druga semantika; stub).
* Ne uvoditi read/unread kolone (FR-OB-017).
* Ne uvoditi admin CRUD / CMS za Obavještenja zbog ovog corrective-a.
* Ne kopirati zvaničnu Odluku u Notice storage.
* Ne uvoditi generički file delivery za sve FT-004 izvore.
* Ne tretirati svaki `supersedes_notice_id` kao korekciju / public revoke.
* Ne uvoditi audit subsystem niti generički versioning subsystem.
* Ne dizajnirati Competition SQL u ovom dokumentu.

## 2.6 CURRENT IMPLEMENTED vs LEGACY — zvanična Odluka Konkursa

**LEGACY RUNTIME — `competition_decision_html`**

`content_delivery = competition_decision_html` učitava `Competition` i renderuje živi HTML iz `CompetitionDecisionDocumentBuilder` (isti poslovni sadržaj kao admin Predlog).

To **nije** zvanična Odluka. **Ne** smije biti delivery **nove** zvanične Odluke. Postojeći HTML Notice redovi ostaju kompatibilan runtime path i **ne** migriraju se automatski u potpisani fajl.

**CURRENT IMPLEMENTED — nova objava zvanične Odluke Konkursa**

Javni objekat je elektronski primjerak fizički potpisane zvanične Odluke. Vlasnik primjerka je modul **Konkursi** (`CompetitionOfficialDecisionCopy`). FT-004 / Notice je kanal javne objave, nije vlasnik fajla i nije CMS dokumenta.

Nova objava zvanične Odluke ide delivery ključem **`competition_decision_signed_copy`** kroz `GET /obavjestenja/{notice}/sadrzaj`. Direct public storage URL se **ne** koristi.

---

# 3. Tehnički model

**Sekcijska sljedivost:** FR-OB-004–FR-OB-016; BM-OB-04; BM-OB-13; DK-FS PATCH-FS-OB-002; KN-FS-003 §15.7.5

## 3.1 Entitet `Notice` (Obavještenje)

Poslovni unos panela **i** zapis javne objave. Nije zvanični sadržaj. Nije vlasnik fajla.

### Posmatrana vidljivost u aktivnom panelu (FS §5)

| Tehničko polje | Posmatrano značenje |
|----------------|---------------------|
| `visible_in_active_panel = true` | Vidljivo u aktivnom panelu |
| `visible_in_active_panel = false` | Nije vidljivo u aktivnom panelu |

`visible_in_active_panel` **ne** određuje smije li javna FT-004 ruta isporučiti sadržaj.

Nema statusa nacrt / na odobrenju / arhivirano / obrisano / zakazano.

### Javna dostupnost sadržaja konkretnog Notice-a

Odvojena semantika od panela.

| Logičko stanje | Posmatrano značenje |
|----------------|---------------------|
| sadržaj javno dostupan | javna FT-004 ruta smije isporučiti referencirani sadržaj ove objave |
| sadržaj javno povučen | javna FT-004 ruta **ne** servira sadržaj ove objave |

Fizičko ime persisted atributa: **`publicly_available`** (boolean; default `true` za legacy i nove objave).

**Mora ostati moguće:**

* **UC-OB-005:** `visible_in_active_panel = false` **i** sadržaj i dalje javno dostupan preko postojeće javne rute.
* **KN korekcija:** `visible_in_active_panel = false` **i** sadržaj **nije** javno dostupan preko prethodne javne rute.

Postojeći (legacy) Notice zapisi tretiraju se kao **javno dostupni**, uključujući one sa `visible_in_active_panel = false`, osim ako budu eksplicitno javno povučeni novim correction tokom. `visible_in_active_panel = false` na postojećem zapisu **ne** znači automatski public revoke.

## 3.2 Referenca na zvanični sadržaj

Obavještenje čuva **neprozirnu** vezu ka izvoru:

* `source_type` — tip izvora (npr. `competition_decision`);
* `source_id` — identifikator izvorne funkcionalnosti (za HTML i signed-copy: `competitions.id`);
* `source_object_id` — identitet tačnog izvornog objekta kada je potreban (`CompetitionOfficialDecisionCopy.id` za signed-copy);
* `content_delivery` — interni runtime ključ načina isporuke koje `PublicNoticeContentController` razrješava.

Notice **ne** čuva kopiju fajla, path, disk, MIME, original filename ni hash.

### LEGACY — `competition_decision_html`

`source_type = competition_decision`, `source_id = competitions.id`, `source_object_id` može biti `null`. Isporuka renderuje živi HTML Predloga **van** admin middleware grupe.

To ostaje dokumentovano kao **postojeći kompatibilni** runtime path. **Nije** delivery zvanične Odluke.

### CURRENT IMPLEMENTED — zvanična Odluka Konkursa

Delivery je source-specific ključ **`competition_decision_signed_copy`**.

To je runtime vrijednost `content_delivery`, istog naming obrasca kao `competition_decision_html`. **Nije** nova DK dokumentaciona oznaka i **nije** generički file delivery za sve izvore.

Implementirani Notice za signed-copy:

* `source_type = competition_decision`
* `source_id = competition.id`
* `source_object_id = CompetitionOfficialDecisionCopy.id`
* `content_delivery = competition_decision_signed_copy`

Javna ruta `GET /obavjestenja/{notice}/sadrzaj` razrješava **tačan nepromjenjivi izvorni primjerak** (`source_object_id`) koji pripada **toj** Notice objavi, pa ga servira samo ako je ta objava `publicly_available = true`. Nema latest/first fallback-a.

`source_id = competitions.id` **samo po sebi nije dovoljno** da razlikuje više istorijskih potpisanih primjeraka iste Odluke. Identitet primjerka je `source_object_id`.

`ApplicationDocument` i `UserDocument` **nisu** vlasnici zvanične Odluke.

## 3.3 Zamjena vidljivosti (ordinary / UC-OB-005)

**Rule-level:** FR-OB-015, FR-OB-016; BM-OB-13; OFD-OB-001

`NoticePublicationService::publish` prima od izvorne funkcionalnosti zahtjev za ordinary panel supersession (ulazni `supersedes_notice_id` u event payload-u).

* Ako je ordinary supersession (`public_revoke = false`): novo Obavještenje `visible_in_active_panel = true`; staro `visible_in_active_panel = false`; zapisi se **ne** brišu; **javna dostupnost starog se ne gasi automatski** (`publicly_available` ostaje kakva je bila).
* Ako nije prosleđen prethodnik: kreira se novo vidljivo Obavještenje; TS **ne** automatski bira šta zamijeniti (OFD-OB-001 blocker).

`supersedes_notice_id` u event payload-u može se persistovati kao `Notice.superseded_notice_id`. To **samo po sebi nije** korekcija. Ordinary supersession (`public_revoke = false`) **ne** smije se reinterpretirati kao KN korekcija / public revoke.

## 3.4 Korekcija pogrešno objavljenog primjerka (KN-FS-003 §15.7.5)

Ovo **nije** UC-OB-005 i **nije** FS-OB-FLOW-03. Source-specific binding; OFD-OB-007 ostaje generički OTVORENO za ostale izvore.

Tehnički ishod korekcije (**IMPLEMENTED**, source-specific KN):

1. Izvor čuva **novi** ispravni nepromjenjivi primjerak (ne overwrite prethodnog).
2. Nastaje **nova** Notice objava (novi zapis).
3. Prethodni pogrešni Notice: `visible_in_active_panel = false`.
4. Prethodni pogrešni Notice: `publicly_available = false`; javna ruta **ne** servira stari sadržaj.
5. Prethodni Notice red, stari copy red i stari fajl ostaju.
6. Persisted veza: `newNotice.superseded_notice_id = oldNotice.id`, sa značenjem da je prethodni **javno povučen zbog korekcije** (`public_revoke = true`), a ne samo skinut s panela.
7. Korekcija **ne** mijenja rang-listu, rezultate ni iznose (to ostaje u izvornom modulu; FT-004 to ne dira).

Ovo pravilo **nije** generičko za sve FT-004 izvore. OFD-OB-007 ostaje generički OTVORENO.

Ovaj TS **ne** određuje HTTP status starog URL-a.

## 3.5 Komponente (pregled)

| Element | Put / naziv | FR |
|---------|-------------|-----|
| Model | `App\Models\Notice` | FR-OB-005–007, 015, 016 |
| Service | `App\Services\Notices\NoticePublicationService` | FR-OB-013–016 |
| Event | `App\Events\OfficialContentReadyForPublicPublication` | FR-OB-011–014 |
| Listener | `App\Listeners\PublishOfficialContentNotice` | FR-OB-013, 014 |
| Controller (home) | proširenje `HomeController@index` | FR-OB-001–007, 017 |
| Controller (public content) | `App\Http\Controllers\PublicNoticeContentController` | FR-OB-008–010 |
| Blade | `resources/views/partials/obavjestenja-panel.blade.php` | FR-OB-001–007 |
| Landing | uključivanje partiala u `landing.blade.php` | FR-OB-001, 004 |
| Izvor (Konkursi) | `CompetitionOfficialDecisionCopy`; `CompetitionOfficialDecisionController` (upload / publish / correct); emitovanje događaja | FR-OB-011; KN-FS-003 |

Repositories: projekat ih sistematski ne koristi — **ne uvode se**.

Policies: javni pristup Notice sadržaju bez Notice Policy klase. Admin Notice CRUD se **ne** uvodi. Autorizacija upload/publish/correction je **source-side** (Konkursi), ne Notice CMS.

---

# 4. Tokovi

**Sekcijska sljedivost:** FS-OB-FLOW-01–03; UC-OB-001–005; KN-FS-003 §15.7.1, §15.7.5

## 4.1 FS-OB-FLOW-01 — Javni pregled

1. Zahtjev `GET /` (bez `auth` middleware).
2. `HomeController@index` učitava `Notice::query()->where('visible_in_active_panel', true)`.
3. **Privremeni tehnički redoslijed** (dok OFD-OB-009 nije riješen): `orderByDesc('published_at')->orderByDesc('id')`. Ovo **nije** usvojeno poslovno pravilo redoslijeda.
4. View renderuje partial panela ispod panela dobrodošlice.
5. Link reference → javna ruta sadržaja.

**Isto ponašanje** za `@guest` i `@auth` (FR-OB-002, FR-OB-003).

## 4.2 FS-OB-FLOW-02 — Automatska objava

1. Izvorna funkcionalnost utvrdi spremnost (van Obavještenja; FR-OB-011).
2. Dispečuje `OfficialContentReadyForPublicPublication`.
3. Listener poziva `NoticePublicationService::publish`.
4. Obavještenje je `visible_in_active_panel = true` i javno dostupno (osim ako izvor eksplicitno naredi drugačije, što nije ordinary publish).
5. Javni sadržaj dostupan preko `PublicNoticeContentController` **ako** je objava javno dostupna.

**OFD-OB-006 ostaje generički OTVORENO** (tenderi i ostali izvori).

**Source-specific IMPLEMENTED:** za zvaničnu Odluku Konkursa Administrator konkursa pokreće first publication prema KN-FS-003 v0.1.16 §15.7.1 kroz `OfficialContentReadyForPublicPublication` (`public_revoke = false`, `source_object_id` = copy.id). To ne zatvara OFD-OB-006 za ostale izvore.

## 4.3 FS-OB-FLOW-03 — Zamjena u aktivnom panelu (ordinary)

1. Izvorna funkcionalnost pri objavi zahtijeva ordinary panel supersession (`supersedes_notice_id` u payload-u).
2. Servis postavi staro `visible_in_active_panel = false`, novo `true`.
3. Redovi ostaju (FR-OB-016).
4. Javna dostupnost starog **ostaje** (nije public revoke).

Bez zahtjeva za supersession tok zamjene se **ne** izvršava automatski.

Ovaj tok **ne** uređuje KN korekciju.

## 4.4 Korekcija zvanične Odluke Konkursa (tehnički tok)

Nije novi `FS-OB-FLOW-*` ID. Nije usvojeni generički UC. Binding: KN-FS-003 §15.7.5 / DK-FS PATCH-FS-OB-002.

1. Administrator konkursa u izvornom modulu postavlja **novi** ispravni nepromjenjivi primjerak.
2. Izvor dispečuje `OfficialContentReadyForPublicPublication` sa `content_delivery = competition_decision_signed_copy`, `source_object_id` novog copy-a, `supersedes_notice_id` aktivnog prethodnog Notice-a i `public_revoke = true` (ne ordinary supersession).
3. Servis u istoj transakciji: starom Notice-u `visible_in_active_panel = false` i `publicly_available = false`; kreira novi Notice (`visible_in_active_panel = true`, `publicly_available = true`, `superseded_notice_id = old.id`).
4. Javna ruta za prethodni Notice **ne** servira sadržaj.
5. Javna ruta za novi Notice servira ispravni primjerak, ako je nova objava javno dostupna.

---

# 5. Autorizacija i ovlašćenja

**Sekcijska sljedivost:** FR-OB-002, FR-OB-003, FR-OB-010, FR-OB-014, FR-OB-017; KN-FS-003 §15.7.1, §15.7.5, §16.1, §18.8.2

| Radnja | Pravilo |
|--------|----------------------|
| Pregled panela | Javna ruta `GET /` — **bez** `auth`, **bez** `verified` |
| Pristup zvaničnom sadržaju Obavještenja | Javna ruta — **bez** `auth`; isporuka samo ako je **ta** objava javno dostupna |
| Objava Obavještenja (kanal) | Samo kroz događaj iz izvorne funkcionalnosti; **nema** Notice approve rute |
| Admin CRUD Obavještenja | **Nije** u obuhvatu |

`RestrictRoleModuleAccess` i `role:*` **ne** primjenjuju se na javne rute Obavještenja.

### Source-side — zvanična Odluka Konkursa

| Radnja | Ko |
|--------|--------|
| Postavljanje (upload/store) potpisanog primjerka | Administrator konkursa |
| Objava kroz FT-004 | Administrator konkursa |
| Korekcija | Administrator konkursa |
| Predsjednik Komisije | **Nema** ovu poslovnu radnju (Predlog / Zaključi nisu objava Odluke) |
| Član Komisije | **Nema** |

**IMPLEMENTED:** `RoleMiddleware` i dalje propušta `superadmin` kroz `role:*`. To **nije** dovoljno niti dopušteno kao poslovni bypass za ove radnje (KN-FS-003 §16.1, §18.8.2).

Izvorni modul **sprovodi** source-side guard: `role.name === 'konkurs_admin'` za upload, publish i correct. Uloga Administratora platforme (`admin`) ili Super administratora **sama po sebi** ne daje pravo upload/publish/correction.

Ovaj TS **ne** dizajnira Policy klasu ni imena metoda.

Nema read-tracking autorizacije ni inbox dozvola.

---

# 6. Model podataka

**Sekcijska sljedivost:** FR-OB-005–007, 010, 015, 016; DK-FS PATCH-FS-OB-002

## 6.1 Tabela `notices` — IMPLEMENTED osnova

| Kolona | Tip | Null | Napomena |
|--------|--------|------|----------|
| `id` | BIGINT UNSIGNED PK | ne | identitet Notice-a |
| `title` | VARCHAR(255) | ne | FR-OB-005 |
| `short_description` | TEXT | da | FR-OB-007; izvor opisa = OFD-OB-008 |
| `visible_in_active_panel` | BOOLEAN | ne | default `true` pri objavi; **samo panel**; FR-OB-015 |
| `publicly_available` | BOOLEAN | ne | default `true`; javna isporuka; odvojeno od panela |
| `source_type` | VARCHAR(64) | ne | veza ka izvoru |
| `source_id` | BIGINT UNSIGNED | ne | izvorni entitet (Konkurs); vidi §3.2 za primjerak |
| `superseded_notice_id` | BIGINT UNSIGNED | da | persisted predecessor; restrictOnDelete |
| `source_object_id` | BIGINT UNSIGNED | da | tačan copy za signed-copy; null za legacy HTML |
| `content_delivery` | VARCHAR(64) | ne | runtime ključ isporuke |
| `published_at` | TIMESTAMP | ne | tehnički trenutak objave |
| `created_at` / `updated_at` | TIMESTAMP | | |

**Indeksi (CURRENT):**

* `(visible_in_active_panel, published_at)` — učitavanje panela;
* `(source_type, source_id)` — pronalaženje po izvoru.

**FK (IMPLEMENTED):**

* `superseded_notice_id` → `notices.id` — `restrictOnDelete`;
* `source_object_id` → `competition_official_decision_copies.id` — `restrictOnDelete`.

Nema obaveznog FK `source_id` ka `competitions`.

## 6.1.1 Implementirani persisted atributi Notice-a

Semantika iz PATCH-FS-OB-002 je **IMPLEMENTED** na Notice zapisu:

| Atribut | Semantika |
|---------|-----------|
| `publicly_available` | da / ne; odvojeno od `visible_in_active_panel`; legacy default = **dostupno** |
| `superseded_notice_id` | koji prethodni Notice je ova objava zamijenila, kada postoji |
| `public_revoke` (event payload, nije kolona) | ordinary panel supersession **naspram** korekcije / public revoke |
| `source_object_id` | jednoznačna referenca na nepromjenjivi primjerak koji ova objava servira |

Ovo **nije** audit tabela. **Nije** generički versioning.

Notice **ne** dobija: disk, MIME, original filename, hash, file blob/path, soft delete, generic audit metadata.

## 6.2 Izmijenjene tabele

**Notice:** migracija `2026_08_31_100100_add_publication_state_to_notices_table` dodaje `publicly_available`, `superseded_notice_id`, `source_object_id`. Legacy redovi ostaju `publicly_available = true`.

**Competition / KN tabele:** ovaj TS **ne** dizajnira KN schema. Vidi §6.4 za implementirano stanje vlasništva primjerka.

## 6.3 Šta se namjerno ne uvodi

* `is_read`, `read_at`, `acknowledged_at`;
* `archived_at`, soft deletes kao arhiva;
* `approved_by`, `approved_at`;
* `sort_order` / `priority` kao poslovni redoslijed (OFD-OB-009);
* `max_visible` konfiguracija (OFD-OB-010);
* tabela grupe „odgovarajućih“ zamjena (OFD-OB-001);
* file storage kolone na `notices`;
* generički file delivery za sve izvore;
* ponašanje za uploaded-but-unpublished replacement.

## 6.4 Ugovor prema izvoru Konkursi (implementation state)

Konkursi **implementiraju**:

* tabelu `competition_official_decision_copies` (`competition_id`, `storage_path`, `uploaded_by` nullable; insert-only; **nema** current/published/version flaga);
* FK `competition_id` → competitions, `restrictOnDelete`;
* FK `uploaded_by` → users, `restrictOnDelete`;
* više istorijskih primjeraka kada korekcija koristi novi copy (bez in-place overwrite objavljenog fajla);
* jednoznačno razrješavanje primjerka preko `Notice.source_object_id`;
* first publication i eksplicitnu korekciju kao odvojene source-side akcije.

Original filename **nije** identity primjerka. `ApplicationDocument` / `UserDocument` nisu vlasnici Odluke.

Ovaj TS i dalje **ne** preuzima KN SQL kao vlasništvo FT-004.

---

# 7. Validacije

**Sekcijska sljedivost:** FR-OB-005–007, 013

Pri `NoticePublicationService::publish`:

| Pravilo | Validacija |
|---------|------------|
| Naslov | obavezan, string, max 255 |
| Kratak opis | opciono, string |
| `source_type`, `source_id`, `content_delivery` | obavezni |
| Prethodni Notice (payload) | opciono; ako postoji mora referencirati postojeći `notices.id` |
| Ordinary vs korekcija | ako je korekcija / public revoke, prethodni Notice je obavezan |
| Delivery **nove** zvanične Odluke | `competition_decision_signed_copy`; `source_object_id` identifikuje konkretni primjerak |
| `competition_decision_html` | **nije** validan delivery za **novu** zvaničnu Odluku Konkursa |

Nema validacije „odgovarajuće“ zamjene (OFD-OB-001).

Nema validacije poslovnog stanja konkursa (OFD-OB-006) **unutar** Obavještenja. Izvor (Konkursi) ostaje odgovoran.

FT-004 **ne** validira niti mijenja rang-listu, rezultate ni iznose.

---

# 8. Evidencija aktivnosti (Audit)

**Status sadržaja:** Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva iz FS/BM za audit pregleda ili prijema.

Ne uvodi se:

* evidencija otvaranja Obavještenja;
* potvrda prijema;
* immutable audit trail objave kao poslovni zahtjev;
* zaseban audit subsystem za korekciju.

Interni trag korekcije je **Notice zapis + persisted veza** (§3.4, §6.1.1), ne audit tabela.

Operativni Laravel log (tehnički) dozvoljen je u implementacionim napomenama, ali nije FR.

---

# 9. Integracije

**Sekcijska sljedivost:** FR-OB-008–011; BM-OB-08; KN-FS-003 §15.7.1, §15.7.5

## 9.1 Ugovor događaja (ulaz iz izvornih funkcionalnosti)

Osnovna semantika ostaje: izvor signalizira da je zvanični sadržaj spreman za javnu objavu.

Logički ugovor (IMPLEMENTED payload; bez uvođenja novog contract-a):

* `title` (string)
* `short_description` (?string)
* `source_type` (string)
* `source_id` (izvorni entitet, npr. Konkurs)
* `content_delivery` (string)
* `source_object_id` (?int) — identitet izvornog objekta (`CompetitionOfficialDecisionCopy.id` za signed-copy)
* `public_revoke` (bool; default `false`) — ordinary panel supersession **naspram** korekcije / public revoke
* `supersedes_notice_id` (?int) — prethodni Notice; obavezan kada je `public_revoke = true`

Listener `PublishOfficialContentNotice` mapira ovaj payload u `NoticePublicationService::publish`. `source_object_id` i `public_revoke` se **ne** gube.

Payload polje `supersedes_notice_id` ostaje **ulaz**. **Nije** samo po sebi korekcija. Ordinary supersession (`public_revoke = false`) i KN korekcija (`public_revoke = true`) **ne** smiju se izjednačiti.

## 9.2 Isporuka sadržaja — `content_delivery`

| `content_delivery` | Status | Ponašanje |
|--------------------|--------|-----------|
| `competition_decision_html` | **LEGACY RUNTIME** | Učitava `Competition` po `source_id` i renderuje živi HTML Predloga. **Nije** zvanična Odluka. **Ne** smije biti delivery **nove** zvanične Odluke. Postojeći zapisi se **ne** migriraju. |
| `competition_decision_signed_copy` | **IMPLEMENTED** za novu zvaničnu Odluku Konkursa | Javna ruta razrješava tačan `CompetitionOfficialDecisionCopy` preko `source_object_id` i servira fajl sa private `local` diska **samo** ako je konkretni Notice `publicly_available`. Notice ne drži fajl. Nije generički file delivery za sve izvore. |

Drugi tipovi (tenderi, ostalo) — tek nakon OFD-OB-006 i dokumentacije izvorne funkcionalnosti.

**IMPLEMENTED RUNTIME** isporučuje i `competition_decision_html` (legacy) i `competition_decision_signed_copy` (nova zvanična Odluka Konkursa). Direct public storage URL se ne koristi.

## 9.3 Eksterni sistemi

Nema integracije sa `kotor.me`, MEGA, SMTP ili payment gateway za Obavještenja u ovom TS.

---

# 10. Nefunkcionalni zahtjevi

**Status sadržaja:** Za ovu funkcionalnu cjelinu trenutno nema primjenjivih usvojenih NFR iz FS.

FS eksplicitno isključuje performanse, availability ciljeve, caching, itd. TS ih ne uvodi.

---

# 11. Granice V1 (Out of Scope)

Usvojeno da **nije** dio V1 ovog TS / usvojenog FS obuhvata:

* arhiva i pregled arhive;
* ručna / urednička objava;
* administratorsko odobrenje objave Obavještenja;
* read/unread, acknowledgment, inbox;
* automatsko određivanje „odgovarajuće“ zamjene bez ulaza izvorne funkcionalnosti;
* poslovni algoritam redoslijeda i maksimum stavki;
* objava na `kotor.me`;
* queue/scheduler za Obavještenja;
* admin UI / CMS za Obavještenja;
* **uploaded-but-unpublished replacement** (namjerno nedefinisano);
* Competition SQL;
* storage driver / disk / path / signed URL;
* HTTP status / redirect za javno povučeni sadržaj;
* retroaktivna migracija `competition_decision_html` u potpisani fajl;
* retroaktivno gašenje svih starih hidden URL-ova.

---

# 12. Otvorena pitanja

## 12.1 Tehnički blocker-i zbog neriješenih OFD

| OFD | Uticaj na implementaciju | Blokira / ograničava |
|-----|--------------------------|----------------------|
| OFD-OB-001 | Servis ne može sam izračunati prethodnika | Potpuna automatska zamjena bez ulaza izvora |
| OFD-OB-002 | Nije poznato da li više vidljivih iz iste kategorije smije koegzistirati | Pravila pri objavi bez supersession |
| OFD-OB-003 | Nema ponašanja za otkazan / prazan novi postupak | Exception tok ordinary zamjene |
| OFD-OB-004 | Nema arhive / dugoročnog UI | Panel flag ≠ arhiva; public availability nije arhiva |
| OFD-OB-005 | Nema admin create UI | Namjerno van obuhvata |
| OFD-OB-006 | Nema generičke veze iz svih izvora ka događaju | **Ostaje generički OTVORENO.** Za zvaničnu Odluku Konkursa okidač je KN-FS-003 §15.7.1. |
| OFD-OB-007 | Nema generičkog toka ispravke za sve izvore | **Ostaje generički OTVORENO.** Za zvaničnu Odluku Konkursa ponašanje je KN-FS-003 §15.7.5. |
| OFD-OB-008 | Nije poznat izvor `short_description` | Kolona postoji; popuna zavisi od izvora |
| OFD-OB-009 | Redoslijed u panelu nije poslovno usvojen | Privremeni tehnički `orderBy published_at` |
| OFD-OB-010 | Nema limita broja stavki | Query bez `limit` osim ako PO odluči |

## 12.2 Ostalo

Uploaded-but-unpublished replacement ostaje **nedefinisano**.

Nema dodatnih tehničkih otvorenih pitanja koja bi zahtijevala PO odluku za ovaj source-specific corrective.

---

# 13. Matrica sljedivosti

## 13.1 FR → tehničke komponente

| FR | Tehnička realizacija |
|----|----------------------|
| FR-OB-001 | `landing.blade.php` + `partials/obavjestenja-panel.blade.php`; pozicija ispod panela dobrodošlice |
| FR-OB-002 | Panel na `GET /` bez auth middleware |
| FR-OB-003 | Isti query/view za guest i auth |
| FR-OB-004 | Partial se uvijek renderuje; lista može biti prazna |
| FR-OB-005 | Prikaz `notice.title` |
| FR-OB-006 | Link ka `route('notices.public-content', $notice)` |
| FR-OB-007 | Prikaz `short_description` ako nije null |
| FR-OB-008 | `PublicNoticeContentController@show`; isporuka samo ako je objava javno dostupna |
| FR-OB-009 | Controller grana po `content_delivery`. **IMPLEMENTED:** `competition_decision_html` (legacy HTML Predloga) i file response `competition_decision_signed_copy` za zvaničnu Odluku Konkursa. To **nije** generički file delivery za sve izvore. |
| FR-OB-010 | Javna ruta; zabranjen redirect na admin decision rutu kao jedini mehanizam; nova zvanična Odluka **nije** živi HTML Predloga |
| FR-OB-011 | Odgovornost izvora; događaj emituje **izvor**. Za Odluku Konkursa: KN-FS-003 §15.7.1. OFD-OB-006 ostaje generički OTVORENO. |
| FR-OB-012 | `NoticePublicationService` ne sadrži logiku „da li je konkurs spreman“ i ne mijenja rezultate |
| FR-OB-013 | Listener → `publish()` → `visible_in_active_panel = true` |
| FR-OB-014 | Nema Notice approve endpointa / statusa na odobrenju |
| FR-OB-015 | Ordinary supersession postavlja staro `visible_in_active_panel = false`; **ne** gasi javnu dostupnost |
| FR-OB-016 | Nema `delete()` na `Notice` ni na izvornom sadržaju u ordinary zamjeni; korekcija je odvojen tok (public revoke, red ostaje) |
| FR-OB-017 | Nema kolona/tabela read-tracking; nema poziva notifikacionog inboxa |

## 13.2 FR → BM → PO (sažetak)

Poklapa se sa FS matricom 11.B; TS ne mijenja taj lanac. Source-specific KN binding je u DK-FS PATCH-FS-OB-002; TS ga tehnički realizuje kao kanal.

## 13.3 Pokrivenost FR

Svaki FR-OB-001 … FR-OB-017 ima tehničku stavku u §13.1.

Ograničenje: FR-OB-011/013 end-to-end iz konkursa za **generičke** izvore i dalje OFD-OB-006. Za zvaničnu Odluku Konkursa binding **i** runtime veza (upload → first publish → signed-copy delivery → korekcija) **jesu implementirani**.

---

# 14. Napomene za implementaciju

**Nenormativno** u pogledu HTTP statusa, storage drivera, diskova, path-ova i PHP potpisa. Semantika u §2–§9 ostaje mjerodavna.

## 14.1 Rute (IMPLEMENTED javni kanal)

| Metoda | Putanja | Ime | Middleware | Controller |
|--------|---------|-----|------------|------------|
| GET | `/` | `home` | web (postojeće) | `HomeController@index` (proširenje) |
| GET | `/obavjestenja/{notice}/sadrzaj` | `notices.public-content` | web, **bez auth** | `PublicNoticeContentController@show` |

Bez novih **Notice** admin ruta. Upload/publish/correction pripadaju izvornom modulu Konkursi; ovaj TS **ne** određuje njihove URI.

## 14.2 Kontroleri

* `HomeController@index` — prosleđuje `$activeNotices` (`visible_in_active_panel = true`) u `landing`.
* `PublicNoticeContentController@show(Notice $notice)`:
  * **ne** koristi `visible_in_active_panel` kao jedini uslov isporuke;
  * **prije isporuke** provjerava javnu dostupnost konkretne objave;
  * ako objava **nije** javno dostupna: sadržaj se **ne** servira (ovaj TS **ne** određuje HTTP status ni redirect);
  * **ordinary UC-OB-005:** Notice skinut s panela ostaje javno dostupan; sadržaj **smije** biti isporučen;
  * **KN korekcija:** sadržaj prethodnog pogrešnog Notice-a **nije** javno dostupan preko prethodne javne rute;
  * `competition_decision_html`: LEGACY HTML Predloga; **nije** delivery zvanične Odluke;
  * `competition_decision_signed_copy`: **IMPLEMENTED** — razrješava `CompetitionOfficialDecisionCopy` preko `source_object_id` i servira fajl sa private `local` diska, bez stabilnog public storage URL-a koji zaobilazi `publicly_available` provjeru;
  * unknown `content_delivery`: sadržaj se ne servira.

OFD-OB-004 (arhiva UI) ostaje otvoren i **nije** isto što i public revoke korekcije.

## 14.3 Servisi

`App\Services\Notices\NoticePublicationService`

* `publish(...)` kreira Notice;
* transakcija pri ordinary panel supersession **i** pri korekciji (panel + public revoke + persisted trag);
* ordinary supersession **ne** postavlja public revoke;
* korekcija **ne** briše prethodni Notice.

## 14.4 Event / Listener

* Event: `OfficialContentReadyForPublicPublication`
* Listener: `PublishOfficialContentNotice` (sync, `ShouldQueue` **ne**)
* Registracija: Laravel auto-discovery (bez dvostrukog `Event::listen`)
* Logički ugovor: §9.1. Payload uključuje `public_revoke` i `source_object_id`; listener ih mapira u `publish()`.

## 14.5 Blade

* `resources/views/partials/obavjestenja-panel.blade.php`
* Uključiti u `landing.blade.php` neposredno ispod sekcije dobrodošlice (FR-OB-001)
* Bez novog vizuelnog redesign-a; koristiti postojeće stilove landing stranice

## 14.6 Migracije / deploy

* Migracija `create_notices_table` već postoji.
* **IMPLEMENTED:** `2026_08_31_100000_create_competition_official_decision_copies_table` i `2026_08_31_100100_add_publication_state_to_notices_table`.
* Storage: private Laravel disk `local` (`storage/app/private`); `CompetitionOfficialDecisionCopy.storage_path`; unique immutable path; original filename **nije** identity; direct public storage URL se ne koristi.
* Legacy redovi: `publicly_available = true`; `visible_in_active_panel = false` **ne** pretvara se u public revoke.
* `competition_decision_html` zapisi se **ne** pretvaraju u potpisane fajlove.
* Nema retroaktivnog gašenja svih starih hidden URL-ova.
* Nema promjene cron/queue worker konfiguracije za ovaj feature kao uslov ovog TS.

## 14.7 Testovi (implementation state)

Pokrivenost je **IMPLEMENTED** u `ObavjestenjaFeatureTest`, `CompetitionOfficialDecisionCopyFoundationTest`, `CompetitionOfficialDecisionUploadTest` i `CompetitionOfficialDecisionPublicationTest`:

| Test | Pokriva |
|------|---------|
| Feature: guest vidi panel na `/` | FR-OB-001, 002, 004 |
| Feature: auth i guest vide iste vidljive stavke | FR-OB-003 |
| Feature: panel prikazuje title / description / link | FR-OB-005–007 |
| Feature: guest otvara javni sadržaj bez auth kada je objava javno dostupna | FR-OB-008, 010 |
| Feature: `publish` kreira visible notice | FR-OB-013, 014 |
| Feature: ordinary supersession skida staro s panela, ne briše red, **stari URL ostaje dostupan** | FR-OB-015, 016; UC-OB-005 regresija |
| Feature: nema upisa u read-tracking | FR-OB-017 |
| Negative: Obavještenja sloj ne sadrži competition-status / ranking gate | FR-OB-012 |
| Feature: data foundation copy/Notice kolone i FK restrict | data foundation |
| Feature: Administrator konkursa postavlja potpisani primjerak | KN §15.6.3; upload |
| Feature: authorization — samo Administrator konkursa; predsjednik/član nemaju; platform `admin`/`superadmin` nisu business bypass | KN §16.1, §18.8.2 |
| Feature: first publish zvanične Odluke kroz FT-004 | KN §15.7.1 |
| Feature: public delivery `competition_decision_signed_copy` servira tačan source object | IMPLEMENTED |
| Feature: Notice nije vlasnik fajla | ownership |
| Feature: korekcija = nova objava + public revoke | KN §15.7.5 |
| Feature: prethodni pogrešni Notice skinut s panela i stari URL ne servira sadržaj | KN §15.7.5 |
| Feature: persisted correction trace | §3.4 |
| Feature: correction chain A → B → C zadržava pun trag | postojeća semantika |
| Feature: rang/rezultati/iznosi nepromijenjeni korekcijom | KN §15.7.5 |
| Feature: `competition_decision_html` nije delivery nove zvanične Odluke | PATCH-FS-OB-002 |

## 14.8 Administracija

Nema Notice admin UI. Tehnička podrška FT-004 = događaj + servis + javne rute. Upload/publish/correction UI pripada Konkursima, ne ovom TS-u kao Notice CMS.

## 14.9 Uticaj na postojeći stub

`NotificationController` i tabela `notifications` ostaju netaknuti (stub „Obavještenja“ u starom smislu ≠ FT-004). Eventualno uklanjanje stub UI-a nije dio ovog TS.

---

# Provjera usklađenosti

* Nova funkcionalnost van FR: **ne** (source-specific binding već u DK-FS PATCH-FS-OB-002 / KN-FS-003)
* Nova poslovna pravila: **ne**
* Riješeni OFD generički: **ne** (OFD-OB-006 i OFD-OB-007 ostaju generički OTVORENO)
* Protivrečnost BM/UC/FS: **nije identifikovana** (UC-OB-005 KEEP; KN korekcija odvojena)
* Svaki FR ima tehničku stavku: **da** (uz zabilježena ograničenja OFD; signed-copy je IMPLEMENTED, HTML ostaje LEGACY)
* Svaka tehnička komponenta u §3/§13 referencira FR: **da**
* Source-specific KN signed-copy runtime implementiran: **da**
* Generički OFD-OB-006 / OFD-OB-007 zatvoreni: **ne**
