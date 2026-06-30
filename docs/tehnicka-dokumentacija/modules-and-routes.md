# Moduli i rute

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** `routes/web.php`, `routes/auth.php`

Sve rute u nastavku (osim `/`, login, register) zahtijevaju middleware: `auth`, `verified`, `module_access_restrict`, osim ako nije drugačije navedeno.

---

## Javne rute

| Metoda | Putanja | Ime rute | Kontroler |
|--------|---------|----------|-----------|
| GET | `/` | `home` | `HomeController@index` |
| GET | `/login` | `login` | `HomeController@loginForm` |
| POST | `/login` | — | `HomeController@login` |
| GET | `/register` | `register` | `HomeController@registerForm` |
| POST | `/register` | — | `HomeController@register` |

Breeze rute iz `auth.php` takođe učitavaju login/register — **duplikat imena ruta** (v. [project-todo.md](project-todo.md)).

---

## Dashboard i profil

| Metoda | Putanja | Ime | Kontroler |
|--------|---------|-----|-----------|
| GET | `/dashboard` | `dashboard` | `HomeController@dashboard` |
| GET | `/profile` | `profile.edit` | `ProfileController@edit` |
| PUT | `/profile` | `profile.update` | `ProfileController@update` |
| PUT | `/profile/password` | `profile.password.update` | `ProfileController@updatePassword` |

---

## Konkursi

| Metoda | Putanja | Ime | Kontroler | Napomena |
|--------|---------|-----|-----------|----------|
| GET | `/competitions` | `competitions.index` | `CompetitionsController@index` | Filtar `type=zensko`, published |
| GET | `/competitions/guide/pdf` | `competitions.guide.pdf` | closure | PDF uputstvo |
| GET | `/competitions/archive` | `competitions.archive` | `AdminController` | `role:admin,konkurs_admin,komisija` |
| GET | `/competitions/{competition}` | `competitions.show` | `CompetitionsController@show` | |
| GET | `/competitions/{competition}/apply` | `applications.create` | `ApplicationController@create` | |
| POST | `/competitions/{competition}/apply` | `applications.store` | `ApplicationController@store` | |

---

## Prijave (applications)

| Metoda | Putanja | Ime | Kontroler |
|--------|---------|-----|-----------|
| GET | `/applications/{application}` | `applications.show` | `ApplicationController@show` |
| DELETE | `/applications/{application}` | `applications.destroy` | `ApplicationController@destroy` |
| POST | `/applications/{application}/submit` | `applications.final-submit` | `ApplicationController@submit` |
| POST | `/applications/{application}/upload` | `applications.upload` | `ApplicationController@uploadDocument` |
| GET | `/applications/{application}/documents/{document}/view` | `applications.document.view` | |
| GET | `/applications/{application}/documents/{document}/download` | `applications.document.download` | |
| DELETE | `/applications/{application}/documents/{document}` | `applications.document.destroy` | |
| GET | `/applications/{application}/status` | `applications.status` | |
| GET/POST | `/applications/{application}/business-plan` | `applications.business-plan.*` | `BusinessPlanController` |
| GET/POST | `/applications/{application}/report*` | `reports.*` | `ReportController` |

---

## Evaluacija

| Prefix | Middleware | Kontroler |
|--------|------------|-----------|
| `/evaluation/*` | `role:komisija` | `EvaluationController` |
| GET `/evaluation/applications/{application}` | — | `EvaluationController@create` (i podnosilac ako odbijen) |
| `/evaluations/*` | `role:evaluator` | Legacy — uloga nije u seederu |

---

## Ugovori

| Putanja | Ime | Napomena |
|---------|-----|----------|
| `/contracts/{application}/generate` | `contracts.generate` | |
| `/contracts/{contract}/download` | `contracts.download` | |
| POST `/contracts/{contract}/approve` | `contracts.approve` | `role:admin` |

---

## Biblioteka dokumenata

| Metoda | Putanja | Ime |
|--------|---------|-----|
| GET/POST | `/documents` | `documents.index`, `documents.store` |
| POST | `/documents/process-for-mega` | `documents.process-for-mega` |
| POST | `/documents/store-mega` | `documents.store-mega` |
| GET | `/documents/{document}/download` | `documents.download` |
| DELETE | `/documents/{document}` | `documents.destroy` |
| POST | `/api/mega/session` | `mega.session` |

**Napomena:** `DELETE /documents/{document}` — zato direktan URL `/documents/ime.pdf` nije statički fajl.

---

## Kalendar kulture

| Putanja | Ime | Kontroler |
|---------|-----|-----------|
| `/kalendar-kulture` | `cultural-calendar.index` | `CulturalCalendarController` |
| `/kalendar-kulture/pregled-dogadjaja` | `cultural-calendar.events` | |
| `/kalendar-kulture/arhiva-dogadjaja` | `cultural-calendar.archive` | |
| `/kalendar-kulture/dan/{date}` | `cultural-calendar.day` | |
| POST `/kalendar-kulture/newsletter` | `cultural-calendar.newsletter.store` | |
| resource `/kalendar-kulture/dogadjaji` | `cultural-events.*` | `CulturalEventController`, `role:kk_admin` |

---

## Admin (`/admin/*`)

Zaštićeno `role:admin`, `role:admin,konkurs_admin`, ili `role:admin,konkurs_admin,komisija` zavisno od rute.

Glavne grupe: korisnici, prijave, konkursi (CRUD, publish, close, ranking, winners), komisije (članovi, izjave).

Kontroler: `AdminController`.

---

## Stub moduli

| Putanja | Status |
|---------|--------|
| `/payments`, `/payments/pay` | Stub — `PaymentsController` |
| `/tenders`, `/tenders/{id}`, `/tenders/purchase` | Stub — `TendersController` |
| `/notifications`, `/notifications/send` | Stub — `NotificationController` |

V. [stubs-and-future-modules.md](stubs-and-future-modules.md).

---

## Povezani dokumenti

- [roles-and-permissions.md](roles-and-permissions.md)
- [application-lifecycle.md](application-lifecycle.md)
