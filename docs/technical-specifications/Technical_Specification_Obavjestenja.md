# Digital Kotor
# Technical Specification
## Funkcionalnost: Obavještenja

**Feature ID:** FT-004  
**Oznaka dokumenta:** TS-013  
**Funkcionalna cjelina:** Obavještenja (platformska prezentacija)  
**Status dokumenta:** U IZRADI  
**Verzija:** 0.1  
**Datum:** 2026-07-31

---

# Istorija verzija

| Verzija / PATCH | Datum | Opis |
|-----------------|--------|------|
| 0.1 | 2026-07-31 | Početna Technical Specification (TS-013) za FT-004. Jedna koherentna Laravel implementacija usvojenih FR-OB-001 do FR-OB-017. Neriješeni OFD evidentirani kao tehnički blocker-i. Bez uvođenja novih poslovnih pravila. |

Napomena:

Ovo poglavlje služi isključivo za evidenciju razvoja dokumenta.

Kod svake naredne verzije dodaje se novi red u tabeli.

Ne mijenjaju se postojeći redovi.

---

## Svrha dokumenta

Dokument opisuje **jednu** tehnički koherentnu realizaciju usvojene Functional Specification za Obavještenja.

TS-013:

* implementira usvojene FR-OB zahtjeve;
* **ne** uvodi nova poslovna pravila;
* **ne** rješava Product Owner odluke (OFD);
* **ne** mijenja Business Model, Use Case Specification ni Functional Specification;
* **ne** predstavlja napisan aplikativni kod.

Izvori istine:

* `docs/business-model/Business_Model_Obavjestenja.md`
* `docs/use-cases/Use_Cases_Obavjestenja.md`
* `docs/functional-specifications/Functional_Specification_Obavjestenja.md` (v0.1 + PATCH-FS-OB-001)
* `docs/features/Feature-Registry.md`
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

Ukupan status dokumenta: **U IZRADI** (zbog OFD blocker-a koji ograničavaju potpunu produkcijsku pokrivenost svih FR).

---

# Pravila upravljanja dokumentom

1. TS-013 pripada FT-004 – Obavještenja.
2. Tehnički sadržaj mora ostati usklađen sa usvojenim BM, UC i FS.
3. Nova poslovna pravila se ne uvode kroz TS-013.
4. Ako implementacija zahtijeva rješavanje OFD, evidentira se blocker; OFD se ne rješava u TS.
5. Izmjene usvojenog sadržaja evidentiraju se PATCH-om / novom verzijom.

---

# 1. Pregled funkcionalne cjeline

**Sekcijska sljedivost:** BM-OB-01–BM-OB-13; UC-OB-001–UC-OB-005; FR-OB-001–FR-OB-017

## 1.1 Svrha

Obavještenja su unakrsna platformska prezentacija: javni panel na početnoj stranici i kanal automatskog nastajanja unosa koji referencira zvanični sadržaj izvornih funkcionalnosti.

## 1.2 Obuhvat ovog TS

* tabela i model Obavještenja;
* servis objave;
* događaj koji okida automatsku objavu (pokreće ga izvorna funkcionalnost);
* javne rute i Blade prezentacija panela;
* javna isporuka referenciranog zvaničnog sadržaja van administrativnog interfejsa;
* testovi i uticaj na deploy/migracije.

## 1.3 Van ovog TS (usvojeno FS/BM)

Ručna objava, urednički workflow, arhiva, read-tracking, inbox, kriterijum „odgovarajuće“ zamjene, redoslijed, maksimum stavki, izvor opisa, tačni okidači po izvornoj funkcionalnosti.

## 1.4 Oznaka dokumenta

**TS-013** (globalna numeracija; sljedeći slobodan broj nakon TS-012 rezervisanog za FT-003).

---

# 2. Arhitektonski principi

**Sekcijska sljedivost:** FR-OB-001–FR-OB-017; BM-OB-07; BM-OB-12

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
| Izvorna funkcionalnost | Utvrđuje spremnost/obaveznost objave (FR-OB-011) i emituje događaj; po potrebi prosleđuje `supersedes_notice_id` |
| `NoticePublicationService` | Kreira Obavještenje, postavlja vidljivost u aktivnom panelu, po pozivu vrši zamjenu vidljivosti |
| `HomeController` + Blade partial | Render panela na početnoj stranici za sve posjetioce |
| `PublicNoticeContentController` | Javna isporuka referenciranog zvaničnog sadržaja |
| Obavještenja sloj | **Ne** utvrđuje da li je postupak spreman za objavu (FR-OB-012) |

## 2.3 Jedna arhitektura automatske objave

Usvojena arhitektura (jedina u ovom TS):

1. Izvorna funkcionalnost dispečuje domen događaj `OfficialContentReadyForPublicPublication`.
2. Listener `PublishOfficialContentNotice` sinhrono poziva `NoticePublicationService::publish(...)`.
3. Nema queue job-a za V1 ovog TS (nije potreban za usvojene FR).
4. Nema Laravel Scheduler zadatka za Obavještenja.
5. Nema administratorskog odobrenja u toku (FR-OB-014).

## 2.4 Javni pristup sadržaju

Referenca u panelu vodi na **javnu** rutu koju kontroliše `PublicNoticeContentController`, ne na administrativne rute (FR-OB-010).

## 2.5 Zabrane dizajna

* Ne koristiti postojeću stub tabelu `notifications` ni `NotificationController` (druga semantika; stub).
* Ne uvoditi read/unread kolone (FR-OB-017).
* Ne uvoditi admin CRUD za Obavještenja u V1 ovog TS.

---

# 3. Tehnički model

**Sekcijska sljedivost:** FR-OB-004–FR-OB-016; BM-OB-04; BM-OB-13

## 3.1 Entitet `Notice` (Obavještenje)

Poslovni unos panela. Nije zvanični sadržaj.

### Posmatrana vidljivost (FS §5)

| Tehničko polje | Posmatrano značenje |
|----------------|---------------------|
| `visible_in_active_panel = true` | Vidljivo u aktivnom panelu |
| `visible_in_active_panel = false` | Nije vidljivo u aktivnom panelu |

Nema statusa nacrt / na odobrenju / arhivirano / obrisano / zakazano.

## 3.2 Referenca na zvanični sadržaj

Obavještenje čuva **neprozirnu** vezu ka izvornom sadržaju:

* `source_type` — tip izvora (npr. `competition_decision`);
* `source_id` — identifikator u izvornoj funkcionalnosti;
* `content_delivery` — interni ključ načina isporuke koje `PublicNoticeContentController` razrješava.

Primjer za konkursnu odluku: `source_type = competition_decision`, `source_id = competitions.id`, isporuka renderuje postojeći sadržaj odluke **van** admin middleware grupe.

## 3.3 Zamjena vidljivosti

**Rule-level:** FR-OB-015, FR-OB-016; OFD-OB-001

`NoticePublicationService::publish` prima opcioni `supersedes_notice_id` **isključivo od izvorne funkcionalnosti**.

* Ako je prosleđen: novo Obavještenje `visible_in_active_panel = true`; staro `visible_in_active_panel = false`; zapisi i zvanični sadržaj se **ne** brišu.
* Ako nije prosleđen: kreira se novo vidljivo Obavještenje; TS **ne** automatski bira šta zamijeniti (OFD-OB-001 blocker).

## 3.4 Komponente (pregled)

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

Repositories: projekat ih sistematski ne koristi — **ne uvode se**.

Policies: javni pristup bez Policy klase; admin Policy nije potreban jer nema admin CRUD.

---

# 4. Tokovi

**Sekcijska sljedivost:** FS-OB-FLOW-01–03; UC-OB-001–005

## 4.1 FS-OB-FLOW-01 — Javni pregled

1. Zahtjev `GET /` (bez `auth` middleware).
2. `HomeController@index` učitava `Notice::query()->where('visible_in_active_panel', true)`.
3. **Privremeni tehnički redoslijed** (dok OFD-OB-009 nije riješen): `orderByDesc('published_at')->orderByDesc('id')`. Ovo **nije** usvojeno poslovno pravilo redoslijeda.
4. View renderuje partial panela ispod panela dobrodošlice.
5. Link reference → javna ruta sadržaja.

**Isto ponašanje** za `@guest` i `@auth` (FR-OB-002, FR-OB-003).

## 4.2 FS-OB-FLOW-02 — Automatska objava

1. Izvorna funkcionalnost utvrdi spremnost (van Obavještenja; FR-OB-011).
2. Dispečuje `OfficialContentReadyForPublicPublication` sa payload-om: `title`, opcioni `short_description`, `source_type`, `source_id`, `content_delivery`, opcioni `supersedes_notice_id`.
3. Listener poziva `NoticePublicationService::publish`.
4. Obavještenje je `visible_in_active_panel = true`.
5. Javni sadržaj dostupan preko `PublicNoticeContentController`.

**Konkretni okidač u konkursima/tenderima nije dio ovog TS** (OFD-OB-006). TS definiše ugovor događaja; veza iz konkurasa je blocker dok PO ne usvoji stanje.

## 4.3 FS-OB-FLOW-03 — Zamjena u aktivnom panelu

1. Izvorna funkcionalnost pri objavi prosledi `supersedes_notice_id`.
2. Servis postavi staro `visible_in_active_panel = false`, novo `true`.
3. Redovi i zvanični sadržaj ostaju (FR-OB-016).

Bez `supersedes_notice_id` tok zamjene se **ne** izvršava automatski.

---

# 5. Autorizacija i ovlašćenja

**Sekcijska sljedivost:** FR-OB-002, FR-OB-003, FR-OB-010, FR-OB-014, FR-OB-017

| Radnja | Middleware / pravilo |
|--------|----------------------|
| Pregled panela | Javna ruta `GET /` — **bez** `auth`, **bez** `verified` |
| Pristup zvaničnom sadržaju Obavještenja | Javna ruta — **bez** `auth` |
| Objava Obavještenja | Samo kroz događaj iz izvorne funkcionalnosti; **nema** admin approve rute |
| Admin CRUD Obavještenja | **Nije** u obuhvatu |

`RestrictRoleModuleAccess` i `role:*` **ne** primjenjuju se na javne rute Obavještenja.

Nema read-tracking autorizacije ni inbox dozvola.

---

# 6. Model podataka

**Sekcijska sljedivost:** FR-OB-005–007, 010, 015, 016

## 6.1 Nova tabela `notices`

| Kolona | Tip | Null | Napomena |
|--------|-----|------|----------|
| `id` | BIGINT UNSIGNED PK | ne | |
| `title` | VARCHAR(255) | ne | FR-OB-005 |
| `short_description` | TEXT | da | FR-OB-007; izvor opisa = OFD-OB-008 (kolona prima vrijednost ako je prosleđena) |
| `visible_in_active_panel` | BOOLEAN | ne | default `true` pri objavi; FR-OB-015 |
| `source_type` | VARCHAR(64) | ne | veza ka izvoru |
| `source_id` | BIGINT UNSIGNED | ne | |
| `content_delivery` | VARCHAR(64) | ne | ključ isporuke za public controller |
| `published_at` | TIMESTAMP | ne | tehnički trenutak objave |
| `created_at` / `updated_at` | TIMESTAMP | | |

**Indeksi:**

* `(visible_in_active_panel, published_at)` — učitavanje panela;
* `(source_type, source_id)` — pronalaženje po izvoru.

**FK:** nema obaveznog FK ka `competitions` (izvor može biti više tipova; polimorfna logička veza preko `source_type`/`source_id`).

**Constraints:** `title` NOT NULL.

## 6.2 Izmijenjene tabele

Nema obaveznih izmjena postojećih tabela za V1 ovog TS.

## 6.3 Šta se namjerno ne uvodi

* `is_read`, `read_at`, `acknowledged_at`;
* `archived_at`, soft deletes kao arhiva;
* `approved_by`, `approved_at`;
* `sort_order` / `priority` kao poslovni redoslijed (OFD-OB-009);
* `max_visible` konfiguracija (OFD-OB-010);
* tabela grupe „odgovarajućih“ zamjena (OFD-OB-001).

---

# 7. Validacije

**Sekcijska sljedivost:** FR-OB-005–007, 013

Pri `NoticePublicationService::publish`:

| Pravilo | Validacija |
|---------|------------|
| Naslov | obavezan, string, max 255 |
| Kratak opis | opciono, string |
| `source_type`, `source_id`, `content_delivery` | obavezni |
| `supersedes_notice_id` | opciono; ako postoji mora referencirati postojeći `notices.id` |

Nema validacije „odgovarajuće“ zamjene (OFD-OB-001).

Nema validacije poslovnog stanja konkursa (OFD-OB-006) unutar Obavještenja.

---

# 8. Evidencija aktivnosti (Audit)

**Status sadržaja:** Za ovu funkcionalnu cjelinu trenutno nema primjenjivih zahtjeva iz FS/BM za audit pregleda ili prijema.

Ne uvodi se:

* evidencija otvaranja Obavještenja;
* potvrda prijema;
* immutable audit trail objave kao poslovni zahtjev.

Operativni Laravel log (tehnički) dozvoljen je u implementacionim napomenama, ali nije FR.

---

# 9. Integracije

**Sekcijska sljedivost:** FR-OB-008–011; BM-OB-08

## 9.1 Ugovor događaja (ulaz iz izvornih funkcionalnosti)

`OfficialContentReadyForPublicPublication` payload:

* `title` (string)
* `short_description` (?string)
* `source_type` (string)
* `source_id` (int)
* `content_delivery` (string)
* `supersedes_notice_id` (?int)

## 9.2 Isporuka sadržaja — prvi podržani `content_delivery`

| `content_delivery` | Ponašanje `PublicNoticeContentController` |
|--------------------|-------------------------------------------|
| `competition_decision_html` | Učitava `Competition` po `source_id` i renderuje javni Blade ekvivalent sadržaja odluke (isti poslovni sadržaj kao admin decision view), **bez** `role:` / admin middleware |

Drugi tipovi (tenderi, ostalo) — tek nakon OFD-OB-006 i dokumentacije izvorne funkcionalnosti; do tada blocker za te izvore.

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
* administratorsko odobrenje objave;
* read/unread, acknowledgment, inbox;
* automatsko određivanje „odgovarajuće“ zamjene bez ulaza izvorne funkcionalnosti;
* poslovni algoritam redoslijeda i maksimum stavki;
* objava na `kotor.me`;
* queue/scheduler za Obavještenja;
* admin UI za Obavještenja.

---

# 12. Otvorena pitanja

## 12.1 Tehnički blocker-i zbog neriješenih OFD

| OFD | Uticaj na implementaciju | Blokira / ograničava |
|-----|--------------------------|----------------------|
| OFD-OB-001 | Servis ne može sam izračunati `supersedes_notice_id` | Potpuna automatska zamjena bez ulaza izvora |
| OFD-OB-002 | Nije poznato da li više vidljivih iz iste kategorije smije koegzistirati | Pravila pri objavi bez `supersedes` |
| OFD-OB-003 | Nema ponašanja za otkazan / prazan novi postupak | Exception tok zamjene |
| OFD-OB-004 | Nema arhive / dugoročnog UI | Samo `visible_in_active_panel`; nema archive feature |
| OFD-OB-005 | Nema admin create UI | Namjerno van obuhvata |
| OFD-OB-006 | Nema veze iz konkurasa/tendera ka događaju | Produkcijski end-to-end automatizam iz izvora |
| OFD-OB-007 | Nema toka ispravke sadržaja / ažuriranja Obavještenja | Update semantika |
| OFD-OB-008 | Nije poznat izvor `short_description` | Kolona postoji; popuna zavisi od izvora |
| OFD-OB-009 | Redoslijed u panelu nije poslovno usvojen | Privremeni tehnički `orderBy published_at` |
| OFD-OB-010 | Nema limita broja stavki | Query bez `limit` osim ako PO odluči |

## 12.2 Ostalo

Nema dodatnih tehničkih otvorenih pitanja van OFD blocker-a.

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
| FR-OB-008 | `PublicNoticeContentController@show` |
| FR-OB-009 | Controller grana po `content_delivery` (HTML view ili file response) |
| FR-OB-010 | Javna ruta; zabranjen redirect na admin decision rutu kao jedini mehanizam |
| FR-OB-011 | Odgovornost izvora; događaj emituje **izvor** |
| FR-OB-012 | `NoticePublicationService` ne sadrži logiku „da li je konkurs spreman“ |
| FR-OB-013 | Listener → `publish()` → `visible_in_active_panel = true` |
| FR-OB-014 | Nema approve endpointa / statusa na odobrenju |
| FR-OB-015 | `publish(..., supersedes_notice_id)` postavlja staro `visible_in_active_panel = false` |
| FR-OB-016 | Nema `delete()` na `Notice` ni na izvornom sadržaju u toku zamjene |
| FR-OB-017 | Nema kolona/tabela read-tracking; nema poziva notifikacionog inboxa |

## 13.2 FR → BM → PO (sažetak)

Poklapa se sa FS matricom 11.B; TS ne mijenja taj lanac.

## 13.3 Pokrivenost FR

Svaki FR-OB-001 … FR-OB-017 ima tehničku stavku u §13.1.

Ograničenje: FR-OB-011/013 end-to-end iz konkursa zahtijeva OFD-OB-006; infrastruktura događaja je specificirana, veza izvora je blocker.

---

# 14. Napomene za implementaciju

**Nenormativno.**

## 14.1 Rute (predloženo)

| Metoda | Putanja | Ime | Middleware | Controller |
|--------|---------|-----|------------|------------|
| GET | `/` | `home` | web (postojeće) | `HomeController@index` (proširenje) |
| GET | `/obavjestenja/{notice}/sadrzaj` | `notices.public-content` | web, **bez auth** | `PublicNoticeContentController@show` |

Bez novih admin ruta.

## 14.2 Kontroleri

* `HomeController@index` — prosleđuje `$activeNotices` u `landing`.
* `PublicNoticeContentController@show(Notice $notice)` — abort 404 ako nije smisleno servirati; za V1 dozvoliti isporuku i kada `visible_in_active_panel = false` **nije** usvojeno kao arhiva (OFD-OB-004). Dok OFD-OB-004 nije riješen: isporuka javnog sadržaja zahtijeva da Obavještenje postoji; **ne** implementirati archive browser. Za usklađenost sa FR-OB-010 dok je unos vidljiv, sadržaj mora biti dostupan; ponašanje nakon skidanja s panela ostaje blocker (OFD-OB-004) — implementacija V1: sadržaj ostaje dostupan preko iste javne rute (usklađeno sa FR-OB-016 „zamjena ≠ gubitak dostupnosti“), bez UI arhive.

## 14.3 Servisi

`App\Services\Notices\NoticePublicationService`

* `publish(array $payload): Notice`
* transakcija DB pri zamjeni vidljivosti

## 14.4 Event / Listener

* Event: `OfficialContentReadyForPublicPublication`
* Listener: `PublishOfficialContentNotice` (sync, `ShouldQueue` **ne**)
* Registracija u `EventServiceProvider` / Laravel 11 discovery

## 14.5 Blade

* `resources/views/partials/obavjestenja-panel.blade.php`
* Uključiti u `landing.blade.php` neposredno ispod sekcije dobrodošlice (FR-OB-001)
* Bez novog vizuelnog redesign-a; koristiti postojeće stilove landing stranice

## 14.6 Migracije / deploy

* Nova migracija `create_notices_table`
* `php artisan migrate` na Plesk nakon deploy-a
* Nema promjene cron/queue worker konfiguracije za ovaj feature
* Nema nove env varijable

## 14.7 Testovi (plan)

| Test | Pokriva |
|------|---------|
| Feature: guest vidi panel na `/` | FR-OB-001, 002, 004 |
| Feature: auth i guest vide iste vidljive stavke | FR-OB-003 |
| Feature: panel prikazuje title / description / link | FR-OB-005–007 |
| Feature: guest otvara javni sadržaj bez auth | FR-OB-008, 010 |
| Unit/Feature: `publish` kreira visible notice | FR-OB-013, 014 |
| Feature: `publish` sa supersedes skida staro s panela, ne briše red | FR-OB-015, 016 |
| Feature: nema upisa u read-tracking | FR-OB-017 |
| Negative: Obavještenja sloj ne sadrži competition-status gate | FR-OB-012 |

## 14.8 Administracija

Nema admin UI. Tehnička podrška = događaj + servis + javne rute.

## 14.9 Uticaj na postojeći stub

`NotificationController` i tabela `notifications` ostaju netaknuti (stub „Obavještenja“ u starom smislu ≠ FT-004). Eventualno uklanjanje stub UI-a nije dio ovog TS.

---

# Provjera usklađenosti

* Nova funkcionalnost van FR: **ne**
* Nova poslovna pravila: **ne**
* Riješeni OFD: **ne** (samo blocker lista)
* Protivrečnost BM/UC/FS: **nije identifikovana**
* Svaki FR ima tehničku stavku: **da** (uz zabilježena ograničenja OFD)
* Svaka tehnička komponenta u §3/§13 referencira FR: **da**
