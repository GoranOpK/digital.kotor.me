# Status projekta i indeks dokumentacije

**Poslednje ažuriranje:** 2026-06-30

Portal **digital.kotor.me** — Laravel 12 aplikacija Opštine Kotor. **Konceptualni model:** [platforma + cjeline](architecture-overview.md#konceptualni-model-projekta-važeće) — trenutno produkcija: biblioteka dokumenata (platforma), kalendar kulture, Podrška ženskom preduzetništvu. Hosting: Plesk.

---

## Status modula (važeće)

Pregled po **cjelinama** (v. [architecture-overview.md](architecture-overview.md#konceptualni-model-projekta-važeće)):

| Cjelina / sloj | Komponenta | Status | Dokumentacija |
|----------------|------------|--------|---------------|
| **Platforma** | Autentikacija i registracija | **Produkcija** | [authentication-and-registration.md](authentication-and-registration.md) |
| **Platforma** | Biblioteka dokumenata + MEGA | **Produkcija** | [document-library-and-mega.md](document-library-and-mega.md) |
| **Kalendar kulture** | Pregled, admin, newsletter | **Produkcija** | [cultural-calendar.md](cultural-calendar.md) |
| **Podrška ženskom preduzetništvu** | Prijave (1a/1b, biznis plan) | **Produkcija** | [application-lifecycle.md](application-lifecycle.md) |
| **Podrška ženskom preduzetništvu** | Evaluacija i komisija | **Produkcija** | [application-lifecycle.md](application-lifecycle.md) |
| **Podrška ženskom preduzetništvu** | Ugovori i izvještaji | **Produkcija** (osnovni tok) | [application-lifecycle.md](application-lifecycle.md) |
| **Podrška ženskom preduzetništvu** | Admin (konkursi, komisije) | **Produkcija** | [roles-and-permissions.md](roles-and-permissions.md) |
| **Konkursi** | Mladi u preduzetništvu (`omladinsko`) | **Sljedeći** | [stubs-and-future-modules.md](stubs-and-future-modules.md) |
| Platforma (stub) | Online plaćanja | **Stub** (vidljiv u UI) | [stubs-and-future-modules.md](stubs-and-future-modules.md) |
| Platforma (stub) | Tenderi | **Stub** (vidljiv u UI) | [stubs-and-future-modules.md](stubs-and-future-modules.md) |
| Platforma (stub) | Obavještenja | **Stub** (vidljiv u UI) | [stubs-and-future-modules.md](stubs-and-future-modules.md) |

*Nove cjeline na platformu dokumentovati tek nakon eksplicitne najave.*

---

## Tematska dokumentacija (počni ovdje)

### Arhitektura i pregled

| Dokument | Sadržaj |
|----------|---------|
| [architecture-overview.md](architecture-overview.md) | Tech stack, slojevi, integracije |
| [modules-and-routes.md](modules-and-routes.md) | Moduli, rute, kontroleri |
| [database-entities.md](database-entities.md) | Modeli, relacije, statusi |
| [business-rules.md](business-rules.md) | Poslovna pravila (adresa, bodovi, dokumenti) |

### Sigurnost i pristup

| Dokument | Sadržaj |
|----------|---------|
| [roles-and-permissions.md](roles-and-permissions.md) | Uloge, middleware, matrica pristupa |
| [authentication-and-registration.md](authentication-and-registration.md) | Login, registracija, verifikacija |

### Moduli

| Dokument | Sadržaj |
|----------|---------|
| [application-lifecycle.md](application-lifecycle.md) | Cijeli tok prijave na konkurs |
| [document-library-and-mega.md](document-library-and-mega.md) | Upload, MEGA, kvota, istek |
| [cultural-calendar.md](cultural-calendar.md) | Događaji, newsletter, kk_admin |
| [cultural-calendar-test-checklist.md](cultural-calendar-test-checklist.md) | Checklista prije objave |

### Operativa

| Dokument | Sadržaj |
|----------|---------|
| [environment-variables.md](environment-variables.md) | Env varijable |
| [deployment-and-cron.md](deployment-and-cron.md) | Plesk, cron, Artisan komande |
| [stubs-and-future-modules.md](stubs-and-future-modules.md) | Nije implementirano / planirano |

### Korisnici (ne-tehnički)

| Dokument | Sadržaj |
|----------|---------|
| [../UPUTSTVO_ZENSKO_PREDUZETNISTVO.md](../UPUTSTVO_ZENSKO_PREDUZETNISTVO.md) | Uputstvo za podnosioce |

### Operativni zapisi (istorija deploya / incidenti)

Indeks: [README.md](README.md) — Plesk, MEGA setup, cleanup, code review izvještaji.

---

## Meta-dokumenti

| Fajl | Namjena |
|------|---------|
| [project-conventions.md](project-conventions.md) | Pravila održavanja dokumentacije |
| [project-todo.md](project-todo.md) | Otvoreni zadaci |
| [project-done.md](project-done.md) | Završeno |
| [handoff-new-chat.md](handoff-new-chat.md) | Uputstvo za novog agenta |

---

## Sljedeći koraci (prioritet)

Vidi [project-todo.md](project-todo.md) za detaljan spisak. **Redoslijed tima:**

1. **Konkurs za mlade u preduzetništvu** (`omladinsko`) — sljedeća cjelina
2. **Online plaćanja** — implementacija
3. **Tenderi** — implementacija
4. Uputstva za Predsjednika i članove komisije (dokumentacija)
5. Dogovor sa drugim administratorom o **feature branch** strategiji
6. Čišćenje `evaluator` / `/evaluations` **nakon** završetka tekućeg konkursa
7. Dopuniti `.env.example` sa `MEGA_*` i `NODE_BINARY` (kad se traži izmjena koda)
