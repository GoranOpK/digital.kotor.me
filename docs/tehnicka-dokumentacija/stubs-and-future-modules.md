# Stub moduli i budući razvoj

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** kontroleri, rute, migracije

Ovi moduli imaju **rute, view-ove i/ili tabele**, ali **nisu funkcionalno implementirani** i **nisu objavljene cjeline** — korisnicima ne služe kao gotova usluga. Ne tretirati ih kao produkciju.

**Napomena:** pojedine stub rute mogu još postojati u kodu ili na landing stranici; poslovno pravilo je da **cjeline u razvoju nisu dostupne korisnicima** dok se eksplicitno ne objave (v. [deployment-and-cron.md](deployment-and-cron.md#objavljivanje-cjeline-razvoj--produkcija-za-korisnike)).

---

## Buduće cjeline na platformi

**digital.kotor.me** je zamišljen kao platforma (auth + biblioteka dokumenata) na kojoj stoje poslovne **cjeline**. Trenutno u produkciji:

- Kalendar kulturnih događaja
- Podrška ženskom preduzetništvu (konkurs, `type=zensko`)

**Pravilo:** nove cjeline se dodaju na platformu tek kad vlasnik projekta **eksplicitno obavijesti**. Dok su u razvoju na serveru — **nisu dostupne korisnicima**; po završetku se **objave**.

Pregled koncepta: [architecture-overview.md](architecture-overview.md#konceptualni-model-projekta-važeće). Razvoj i objava: [deployment-and-cron.md](deployment-and-cron.md).

---

## Online plaćanja (`/payments`)

| Stavka | Stanje |
|--------|--------|
| Kontroler | `PaymentsController` |
| Rute | `payments.index`, `payments.pay` |
| View | `resources/views/payments/index.blade.php` |
| Implementacija | `pay()` prazan / placeholder |
| Baza | `payments` tabela (migracija postoji) |

**Status:** stub — nema payment gateway integracije.

---

## Tenderi (`/tenders`)

| Stavka | Stanje |
|--------|--------|
| Kontroler | `TendersController` |
| Rute | `tenders.index`, `tenders.show`, `tenders.purchase` |
| Implementacija | `purchase()` prazan |
| Baza | `tenders`, `tender_purchases` |

**Status:** stub — UI pregled bez poslovne logike otkupa.

---

## Obavještenja (`/notifications`)

| Stavka | Stanje |
|--------|--------|
| Kontroler | `NotificationController` |
| Rute | `notifications.index`, `notifications.send` |
| Implementacija | metode prazne |
| Baza | `notifications` tabela |

**Status:** stub.

---

## Legacy evaluator (`/evaluations`)

| Stavka | Stanje |
|--------|--------|
| Middleware | `role:evaluator` |
| Uloga | **Nije** u `RoleSeeder` |
| Zamjena | `EvaluationController` + uloga `komisija` |

**Status:** vjerovatno mrtav kod — ne koristiti bez revizije.

---

## Konkurs tip `omladinsko`

Baza i model podržavaju `competition.type = omladinsko`, ali javna lista (`CompetitionsController@index`) filtrira samo `zensko`.

**Status:** pripremljeno za proširenje, nije aktivno u UI.

---

## Pri izmjenama

Kad modul postane funkcionalan:

1. Ažurirati [project-status-next-steps.md](project-status-next-steps.md)
2. Premjestiti opis iz ovog fajla u novi tematski `.md`
3. Označiti u [project-done.md](project-done.md)
