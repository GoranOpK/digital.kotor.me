# Operativa tima i projekta

**Poslednje ažuriranje:** 2026-06-30 (dopuna: Odluka, prioriteti, MEGA, evaluator)  
**Namjena:** dogovoreni način rada koji nije uvijek vidljiv iz koda — za developere i AI agente.

Povezano: [deployment-and-cron.md](deployment-and-cron.md), [project-conventions.md](project-conventions.md).

---

## Tim i odluke

| Tema | Pravilo |
|------|---------|
| Razvoj | Dva administratora na projektu (vlasnik + kolega) |
| Deploy na server | **Kolega ili vlasnik** pokreće Plesk pull/deploy **kad se ukaže potreba** |
| Objava cjeline | Kad su **zadovoljni** da je cjelina gotova — zajednička odluka |
| Poslovna pravila (Odluka o konkursu) | Zvaničan izvor: **katalog propisa** ili **Službeni list**; sve u aplikaciji po Odluci — nema pravila mimo nje |
| Korisničko uputstvo (PDF) | `public/pdf/uputstvo-zensko-preduzetnistvo.pdf` — potvrđena putanja |

---

## Git i grane (važeće)

```
Lokalni računari (različiti) — razvoj
        ↓  git status → razriješiti → push
GitHub — samo grana **main** se diže na server
        ↓  Plesk Git pull/deploy (po potrebi)
digital.kotor.me
```

| Pravilo | Detalj |
|---------|--------|
| **Na server ide samo `main`** | Produkcijski deploy uvijek sa `main` |
| Prije pusha | **Obavezno** `git status`, razriješiti stanje, zatim push |
| Feature branch-evi | Tim želi **bolju organizaciju** (samo `main` će postati prevelik za ogroman projekat) — **čeka dogovor** sa drugim administratorom; do tada ne mijenjati strategiju u repou bez dogovora |
| AI agent | **Ne brisati/merge-ovati** feature branch-eve bez eksplicitnog zahtjeva |

---

## Objava završene cjeline

Kad je cjelina spremna za korisnike (odluka tima):

1. Cjelina postaje **dostupna korisnicima** (nije više „u izradi“).
2. **Generalno:** na korisničkom panelu / **Dashboard-u** pojavi se **polje, ikonica ili link** koji vodi na tu cjelinu.
3. Detalji variraju od slučaja do slučaja (npr. konkurs: dodatno `published` u adminu).

Dok link/polje na dashboardu **nije** uključeno, tretirati cjelinu kao **nije objavljena** za krajnje korisnike — čak i ako dio koda već postoji na serveru.

---

## Jezik i terminologija

| Pravilo | Primjer |
|---------|---------|
| **Službena ijekavica** | pisanje u skladu sa praksom CG |
| **Latinica** | sav UI i dokumentacija za građane |
| **Opština Kotor** | sve na `digital.kotor.me` odnosi se na **jednu** opštinu — Opštinu Kotor; nije portal za više opština |

U kodu i postojećim tekstovima pratiti uspostavljeni stil; pri novim stringovima — ijekavica, latinica, kontekst Opštine Kotor.

---

## Vanjski nalozi i odgovornost

| Servis | Vlasnik naloga | Pristup tima |
|--------|----------------|--------------|
| **MEGA.nz** | Opština Kotor | Administratori projekta — pun pristup |
| **Mail (SMTP)** | Opština Kotor | Administratori projekta — pun pristup |

**Rezervni plan** ako MEGA ili mail padnu:

- **MEGA:** u slučaju pada — konsultacija unutar tima; u najgorem slučaju **ručno** rješavanje (nema automatizovanog fallback-a u dokumentaciji).
- **Mail:** isti princip — ručna intervencija po potrebi.

Formalna procedura nije zapisana u detalj; v. [project-todo.md](project-todo.md) ako treba proširiti.

---

## Backup

Plesk automatski radi **dnevni backup baze i cijelog sajta** — bez ručne intervencije tima.

---

## Prioritet budućih cjelina

**Odluka tima (važeće):** prioritet cjelina na platformi:

| Prioritet | Cjelina | Status |
|-----------|---------|--------|
| — | **Kalendar kulture** | Završeno |
| — | **Konkursi** — žensko preduzetništvo (`zensko`) | Završeno |
| 1 | **Konkursi** — mladi u preduzetništvu (`omladinsko`) | Sljedeći konkretni zadatak |
| 2 | **Online plaćanja** | Nakon mladih konkursa |
| 3 | **Tenderi** | Nakon plaćanja |

Planirano je **nekoliko vrsta konkursa** na zajedničkom modulu; `omladinsko` je sljedeći tip koji se aktivira u UI.

Stub moduli (plaćanja, tenderi, obavještenja) **ostaju vidljivi** u portalu — namjerno, da korisnici vide da se radi na projektu.

V. [stubs-and-future-modules.md](stubs-and-future-modules.md), [project-status-next-steps.md](project-status-next-steps.md), [business-rules.md](business-rules.md#tipovi-i-prioritet-važeće--odluka-tima).

---

## Tehnički dug — namjerno zadržano (privremeno)

| Tema | Status |
|------|--------|
| Uloga `evaluator` i rute `/evaluations` | Namjerno zadržan stari kod dok ne prođe tekući konkurs; čišćenje **nakon** završetka konkursa |
| Stub moduli u UI | Ostaju vidljivi — nije greška |
| `APP_ENV` / `APP_DEBUG` na produkciji | **Prilagođeno** na serveru (2026-06-30) |

---

## Otvoreno (čeka input)

| Tema | Status |
|------|--------|
| Feature branch strategija | Čeka dogovor sa **drugim administratorom** — želja za bolju organizaciju grana |
| Uputstva za Predsjednika i članove komisije | Predloženo; manje razlike u odnosu na podnosioca — v. [project-todo.md](project-todo.md) |
| Formalni security / contingency doc (MEGA) | Sažetak u `document-library-and-mega.md`; detaljna procedura po potrebi |
