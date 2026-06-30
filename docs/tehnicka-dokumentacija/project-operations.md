# Operativa tima i projekta

**Poslednje ažuriranje:** 2026-06-30  
**Namjena:** dogovoreni način rada koji nije uvijek vidljiv iz koda — za developere i AI agente.

Povezano: [deployment-and-cron.md](deployment-and-cron.md), [project-conventions.md](project-conventions.md).

---

## Tim i odluke

| Tema | Pravilo |
|------|---------|
| Razvoj | Dva administratora na projektu (vlasnik + kolega) |
| Deploy na server | **Kolega ili vlasnik** pokreće Plesk pull/deploy **kad se ukaže potreba** |
| Objava cjeline | Kad su **zadovoljni** da je cjelina gotova — zajednička odluka |
| Poslovna pravila (Odluka o konkursu, službeni PDF) | **Kolega** — izvor za pitanja kad kod i pravilnik nisu usklađeni; dokumentovati kad bude dostupno |

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
| Feature branch-evi | Mogu postojati u istoriji repoa; **ne mijenjati** branch strategiju dok se vlasnik ne posavjetuje sa kolegom; merge rade sami kad se dogovore |
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

**Rezervni plan** ako MEGA ili mail padnu — nije definisan; v. [project-todo.md](project-todo.md).

---

## Backup

Plesk automatski radi **dnevni backup baze i cijelog sajta** — bez ručne intervencije tima.

---

## Prioritet budućih cjelina

Planirano na platformi (među ostalim): plaćanja, tenderi, dodatni konkursi — **redoslijed nije određen**. Ne pretpostavljati prioritet u implementaciji dok tim eksplicitno ne kaže.

V. [stubs-and-future-modules.md](stubs-and-future-modules.md), [project-status-next-steps.md](project-status-next-steps.md).

---

## Otvoreno (čeka input)

| Tema | Status |
|------|--------|
| Zvanična Odluka o konkursu / PDF pravilnika | Čeka kolegu |
| Feature branch strategija | Čeka dogovor tima |
| Rezervni plan MEGA / mail | U [project-todo.md](project-todo.md) |
