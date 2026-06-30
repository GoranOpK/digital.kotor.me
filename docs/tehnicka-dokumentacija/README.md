# Tehnička dokumentacija — digital.kotor.me

**Poslednje ažuriranje:** 2026-06-30

Centralni folder za svu tehničku dokumentaciju projekta: konvencije, indeks, arhitektura, moduli, deploy i operativni zapisi.

---

## Za nove saradnike i AI agente

1. [handoff-new-chat.md](handoff-new-chat.md) — brzi ulaz
2. [project-conventions.md](project-conventions.md) — pravila održavanja docs
3. [project-status-next-steps.md](project-status-next-steps.md) — indeks i status modula

**Konvencije:** [project-conventions.md](project-conventions.md) · **Otvoreno:** [project-todo.md](project-todo.md) · **Urađeno:** [project-done.md](project-done.md)

---

## Meta-dokumenti

| Fajl | Namjena |
|------|---------|
| [project-conventions.md](project-conventions.md) | Pravila pisanja i održavanja dokumentacije |
| [project-operations.md](project-operations.md) | Tim, Git (`main`), objava cjelina, jezik, nalozi |
| [project-status-next-steps.md](project-status-next-steps.md) | Indeks tematske dokumentacije + status modula |
| [project-todo.md](project-todo.md) | Otvoreni zadaci |
| [project-done.md](project-done.md) | Završeno |
| [handoff-new-chat.md](handoff-new-chat.md) | Uputstvo za novog AI agenta |

---

## Tematska dokumentacija (izvor istine za proizvod)

| Dokument | Sadržaj |
|----------|---------|
| [architecture-overview.md](architecture-overview.md) | Tech stack, **koncept platforma + cjeline**, integracije |
| [modules-and-routes.md](modules-and-routes.md) | Moduli, rute, kontroleri |
| [roles-and-permissions.md](roles-and-permissions.md) | Uloge, middleware, matrica |
| [authentication-and-registration.md](authentication-and-registration.md) | Login, registracija, profil |
| [application-lifecycle.md](application-lifecycle.md) | Tok prijave na konkurs |
| [business-rules.md](business-rules.md) | Adresa, JMB/PIB, bodovi, dokumenti |
| [document-library-and-mega.md](document-library-and-mega.md) | Biblioteka, MEGA, kvota |
| [cultural-calendar.md](cultural-calendar.md) | Kalendar kulture |
| [database-entities.md](database-entities.md) | Modeli, relacije, statusi |
| [environment-variables.md](environment-variables.md) | Env varijable |
| [deployment-and-cron.md](deployment-and-cron.md) | Plesk, Git deploy, Toolkit, cron, backup |
| [stubs-and-future-modules.md](stubs-and-future-modules.md) | Nije implementirano |

---

## Korisnička dokumentacija (izvan ovog foldera)

| Dokument | Namjena |
|----------|---------|
| [../UPUTSTVO_ZENSKO_PREDUZETNISTVO.md](../UPUTSTVO_ZENSKO_PREDUZETNISTVO.md) | Uputstvo za podnosioce prijava |

**PDF:** `public/pdf/uputstvo-zensko-preduzetnistvo.pdf` — ruta `competitions.guide.pdf` na portalu.

---

## Server i Plesk

| Dokument | Opis |
|----------|------|
| [SERVER_SETUP.md](SERVER_SETUP.md) | Osnovno podešavanje servera |
| [PLESK_FINAL_INSTRUCTIONS.md](PLESK_FINAL_INSTRUCTIONS.md) | Finalne Plesk instrukcije |
| [PLESK_FIND_PATH.md](PLESK_FIND_PATH.md) | Pronalaženje putanje projekta |
| [PLESK_FIND_PATH_ALTERNATIVE.md](PLESK_FIND_PATH_ALTERNATIVE.md) | Alternativni način |
| [PLESK_PATH_FROM_LOG_BROWSER.md](PLESK_PATH_FROM_LOG_BROWSER.md) | Putanja iz log browsera |
| [PLESK_TROUBLESHOOTING.md](PLESK_TROUBLESHOOTING.md) | Troubleshooting |
| [PLESK_CRON_COMMAND.md](PLESK_CRON_COMMAND.md) | Cron komande |
| [PLESK_CRON_STYLE_SETUP.md](PLESK_CRON_STYLE_SETUP.md) | Podešavanje cron zadataka |
| [PLESK_DELETE_EXPIRED_CRON.md](PLESK_DELETE_EXPIRED_CRON.md) | Brisanje isteklih dokumenata |

---

## MEGA integracija

| Dokument | Opis |
|----------|------|
| [APPLICATION_DOCUMENTS_MEGA.md](APPLICATION_DOCUMENTS_MEGA.md) | Dokumenti prijava i MEGA |
| [MEGA_BROWSER_UPLOAD_PLAN.md](MEGA_BROWSER_UPLOAD_PLAN.md) | Plan browser uploada |
| [MEGA_BROWSER_UPLOAD_SETUP.md](MEGA_BROWSER_UPLOAD_SETUP.md) | Setup |
| [MEGAJS_SETUP_COMPLETE.md](MEGAJS_SETUP_COMPLETE.md) | Završetak MEGA.js setupa |
| [MEGA_UPLOAD_DEBUG.md](MEGA_UPLOAD_DEBUG.md) | Debug |
| [CLOUD_PATH_EXPLANATION.md](CLOUD_PATH_EXPLANATION.md) | cloud_path polje |
| [PRE_DEPLOY_CHECKLIST_MEGA_DELETE.md](PRE_DEPLOY_CHECKLIST_MEGA_DELETE.md) | Deploy checklist |

---

## Razvoj, Git, održavanje (operativni zapisi)

| Dokument | Opis |
|----------|------|
| [IMPLEMENTATION_STEPS.md](IMPLEMENTATION_STEPS.md) | Koraci implementacije (istorija) |
| [NEXT_STEPS.md](NEXT_STEPS.md) | Sljedeći koraci (istorija) |
| [PRE_GITHUB_CHECKLIST.md](PRE_GITHUB_CHECKLIST.md) | Checklist prije pusha |
| [GIT_COMMIT_INSTRUCTIONS.md](GIT_COMMIT_INSTRUCTIONS.md) | Commit uputstvo |
| [GITHUB_PUSH_INSTRUCTIONS.md](GITHUB_PUSH_INSTRUCTIONS.md) | Push uputstvo |
| [MIGRATION_FIX.md](MIGRATION_FIX.md) | Ispravke migracija |
| [TEST_COMMAND.md](TEST_COMMAND.md) | Test komande |
| [CLEANUP_SUMMARY.md](CLEANUP_SUMMARY.md) | Sažetak čišćenja |
| [CLEANUP_OLD_FILES.md](CLEANUP_OLD_FILES.md) | Stari fajlovi |
| [MANUAL_CLEANUP_INSTRUCTIONS.md](MANUAL_CLEANUP_INSTRUCTIONS.md) | Ručno čišćenje |
| [FIX_STUCK_DOCUMENT.md](FIX_STUCK_DOCUMENT.md) | Zaglavljen dokument |
| [FIX_COMMAND_REGISTRATION.md](FIX_COMMAND_REGISTRATION.md) | Artisan registracija |
| [CODE_REVIEW_REPORT.md](CODE_REVIEW_REPORT.md) | Code review |
| [CODE_REVIEW_SUMMARY.md](CODE_REVIEW_SUMMARY.md) | Sažetak reviewa |

**Napomena:** operativni fajlovi iznad su istorijski / incident zapisi. Za arhitekturu i ponašanje aplikacije koristiti **tematsku** dokumentaciju na vrhu ovog indeksa.
