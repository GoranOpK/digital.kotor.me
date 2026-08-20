# Baza — entiteti i relacije

**Poslednje ažuriranje:** 2026-08-20
**Izvor u kodu:** `app/Models/`, `database/migrations/`

---

## Dijagram relacija (sažetak)

```
User ──belongsTo──> Role
User ──hasMany──> UserDocument, Application

Competition ──hasMany──> Application, EvaluationCriteria, Priority
Competition ──belongsTo──> Commission
Competition ──hasOne──> UpNumber

Application ──belongsTo──> User, Competition
Application ──hasOne──> BusinessPlan, Contract
Application ──hasMany──> ApplicationDocument, EvaluationScore, Report

Commission ──hasMany──> CommissionMember, Competition
CommissionMember ──belongsTo──> User, Commission

CulturalEvent ──belongsTo──> User (created_by)
```

---

## Ključni modeli

| Model | Tabela | Namjena |
|-------|--------|---------|
| `User` | `users` | Nalog, profil, adresa, JMB/PIB |
| `Role` | `roles` | Uloge |
| `Competition` | `competitions` | Konkurs |
| `Application` | `applications` | Prijava |
| `BusinessPlan` | `business_plans` | Obrazac 2 |
| `ApplicationDocument` | `application_documents` | Prilozi prijave |
| `UserDocument` | `user_documents` | Biblioteka |
| `EvaluationScore` | `evaluation_scores` | Bodovi člana komisije |
| `Commission` | `commissions` | Komisija po godini |
| `CommissionMember` | `commission_members` | Članovi |
| `Contract` | `contracts` | Ugovor |
| `Report` | `reports` | Izvještaj realizacije |
| `CulturalEvent` | `cultural_events` | Događaj |
| `NewsletterSubscriber` | `newsletter_subscribers` | Newsletter |
| `Tender`, `Payment` | `tenders`, `payments` | Stub — minimalna upotreba |
| `Notice` | `notices` | FT-004 Obavještenja — javni panel zvaničnog sadržaja (nije `/notifications` stub) |

---

## Važne kolone po entitetu

### `users`

`role_id`, `activation_status`, `user_type`, `residential_status`, `jmb`, `pib`, `passport_number`, `address`, `city`, `phone`, `company_name`

Nema kolone `business_type`. Identifikaciono polje fizičkog lica je `jmb`, ne `jmbg`.

`user_type` (ENUM, safe expansion): 8 kanonskih vrijednosti + 4 zadržane legacy vrijednosti. Nove registracije pišu samo kanonski skup. SSOT: `App\Support\UserType`.

`residential_status`: `resident` / `non-resident` / `NULL`. Novi zapisi pravnih lica = `NULL`.

### `competitions`

`type` (enum: zensko, omladinsko, ostalo), `status`, `year`, `budget`, `deadline_days`, `start_date`, `published_at`, `closed_at`, `commission_id`

### `applications`

`status`, `applicant_type`, `business_stage`, `is_registered`, `redni_broj`, `submitted_at`, `final_score`, bonus polja, `rejection_reason`

### `user_documents` / `application_documents`

`status` / processing polja, `cloud_path`, `mega_node_id`, `mega_file_name`, `expires_at`

---

## Statusi (string kolone)

Detalji po domenu — v. [application-lifecycle.md](application-lifecycle.md), [business-rules.md](business-rules.md), [cultural-calendar.md](cultural-calendar.md).

---

## Migracije

~71 migracija u `database/migrations/`. Seeders: `RoleSeeder`, ostali po potrebi.

Incident note: [MIGRATION_FIX.md](MIGRATION_FIX.md).

---

## Povezani dokumenti

- [architecture-overview.md](architecture-overview.md)
- [business-rules.md](business-rules.md)
