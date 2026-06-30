# Konvencije projekta (digital.kotor.me)

**Poslednje ažuriranje:** 2026-06-30

**Za AI i ljude:** držati se ovoga pri novim izmenama da ostane konzistentno.

**Otvoreni zadaci:** [project-todo.md](project-todo.md) · **Urađeno:** [project-done.md](project-done.md) · **Indeks docs:** [README.md](README.md)

---

## 0. Princip: dokumentacija = izvor istine, ne zabune

**Cilj:** ono što piše u `docs/tehnicka-dokumentacija/` treba da bude proverljivo i usklađeno sa stvarnim ponašanjem aplikacije. Dokument koji kontradiktuje kod ili drugi doc je greška dok se ne ispravi.

**Hijerarhija:** za tačan tehnički detalj (ruta, status u bazi, redosled jobova) **kod u repou je presudan**. Tematski fajlovi u `docs/tehnicka-dokumentacija/` su „izvor istine“ za ljude i AI samo ako redovno prate taj kod. Kad promeniš kod — u istom PR-u ili odmah posle ažuriraj pogođeni `.md` (ili eksplicitno napiši u `project-todo.md` da doc kasni, ako mora).

**Kontradikcija doc ↔ kod:** ne ostavljati oba stanja; ili ispravi dokumentaciju ili vrati/popravi kod. Izbegavati nejasno mešanje starog i novog u istoj rečenici bez oznake šta još važi. Ako je potrebno menjati kod obavezno tražiti dozvolu pre promene koda. Tom prilikom naglasiti koji se deo dokumentacije ne slaže sa kodom.

**Sumnja:** ako nisi siguran šta važi, proveri kod (`routes/`, kontroler, model). Ne nagađaj u doc-u. Ako nešto nije implementirano, u doc-u jasno napiši „nije implementirano / stub“ i na šta se odnosi (npr. ime klase).

**Meta-dokumenti** (`README.md`, `handoff-new-chat.md`, `project-todo.md`, `project-done.md`, ovaj fajl, `project-status-next-steps.md`) opisuju proces i konvencije; ne dupliraju dugačke tehničke specifikacije — za to služe tematski fajlovi iz indeksa u `project-status-next-steps.md`.

---

## 0.1 Evolucija u dokumentu („bilo → sada“) — dozvoljena notacija

Ponekad je korisno u istom tematskom `.md` fajlu zabeležiti i istoriju odluke, ne samo trenutno stanje. To nije zabuna ako je struktura jasna.

**Preporučeni oblik** (naslovi ili bold oznake moraju biti eksplicitni):

- **Rešenje je bilo ovako (zastarelo / pre promene):** … kratko šta je važilo ranije (ruta, klasa, pravilo).
- **Nakon** (pravila, zahteva, PR-a, datuma — šta je pokrenulo promenu): … jedna rečenica konteksta.
- **Rešenje sada izgleda ovako (važeće):** … šta trenutno važi i mora da se slaže sa kodom.

**Pravila za ovu notaciju:**

- Blok „sada / važeće“ je ono što AI i novi saradnik tretiraju kao operativnu istinu; mora da odgovara kodu.
- Blok „bilo / zastarelo“ služi samo za uvid u razvoj i odbacivanje loših koncepata — ne implementirati po njemu.
- Kad zastareli opis više niko ne koristi, može se skratiti (npr. jedna rečenica + „v. git istoriju“) da doc ne raste bez kontrole.
- U `project-done.md` često je dovoljna jedna rečenica po promeni; duboki „pre/posle“ zapis ostaje u tematskom fajlu gde ima smisla.

---

## 1. Struktura dokumentacije

| Lokacija | Namjena |
|----------|---------|
| `docs/tehnicka-dokumentacija/project-conventions.md` | Ovaj fajl — pravila pisanja i održavanja docs |
| `docs/tehnicka-dokumentacija/project-status-next-steps.md` | Indeks tematske dokumentacije + status modula |
| `docs/tehnicka-dokumentacija/project-todo.md` | Otvoreni zadaci (doc i proizvod) |
| `docs/tehnicka-dokumentacija/project-done.md` | Završeni zadaci (kratko) |
| `docs/tehnicka-dokumentacija/handoff-new-chat.md` | Brzi ulaz za novog AI agenta ili saradnika |
| `docs/tehnicka-dokumentacija/project-operations.md` | Tim, Git, objava, jezik, backup, nalozi |
| `docs/tehnicka-dokumentacija/README.md` | Indeks cijele tehničke dokumentacije |
| `docs/UPUTSTVO_ZENSKO_PREDUZETNISTVO.md` | Korisničko uputstvo (podnosioci) |

**PDF za korisnike:** `public/pdf/uputstvo-zensko-preduzetnistvo.pdf` — servira se rutom `competitions.guide.pdf` (`GET /competitions/guide/pdf`).

**Konceptualni model projekta** (platforma + cjeline): [architecture-overview.md](architecture-overview.md#konceptualni-model-projekta-važeće). Nove cjeline na platformu unositi u dokumentaciju tek nakon eksplicitne najave.

**Razvoj i deploy:** lokalni računari → GitHub (`main`, `git status` prije pusha) → Plesk pull/deploy po potrebi. Operativa na serveru: **Laravel Toolkit**. Cjeline u izradi skrivene; objava = dashboard link. V. [project-operations.md](project-operations.md), [deployment-and-cron.md](deployment-and-cron.md).

**Jezik:** službena ijekavica, latinica; kontekst **Opštine Kotor**.

---

## 2. Jezik i ton

- Tehnička dokumentacija: srpski/hrvatski/crnogorski mješavina kako u kodu (komentari, UI poruke) — konzistentno unutar jednog fajla.
- Korisnička dokumentacija: **službena ijekavica**, **latinica**; portal isključivo za **Opštinu Kotor** (ne više opština).
- Identifikatori iz koda (rute, klase, kolone) ostaju na engleskom kao u repou.

Detalji: [project-operations.md](project-operations.md#jezik-i-terminologija).

---

## 3. Šta ažurirati kad se menja kod

| Promjena | Dokument |
|----------|----------|
| Nova ruta / middleware | `modules-and-routes.md`, `roles-and-permissions.md` |
| Status prijave, tok prijave | `application-lifecycle.md`, `business-rules.md` |
| Validacija adrese, JMB, PIB | `business-rules.md`, `authentication-and-registration.md` |
| MEGA / biblioteka dokumenata | `document-library-and-mega.md` + postojeći MEGA setup fajlovi |
| Kalendar kulture | `cultural-calendar.md` |
| Nova env varijabla | `environment-variables.md` |
| Cron / deploy | `deployment-and-cron.md` |
| Novi model / tabela | `database-entities.md` |
| Stub postao funkcionalan | `stubs-and-future-modules.md`, `architecture-overview.md` |

---

## 4. Konvencije su žive

Ove konvencije mogu se prilagođavati i dopunjavati prema potrebama projekta. Svaka izmjena ovog fajla: ažurirati datum na vrhu i jednu rečenicu u `project-done.md`.
