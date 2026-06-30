# Autentikacija i registracija

**Poslednje ažuriranje:** 2026-06-30  
**Izvor u kodu:** `HomeController`, `ProfileController`, `ProfileUpdateRequest`, `routes/auth.php`, `app/Models/User.php`

---

## Tok prijave (login)

1. `GET /login` → forma
2. `POST /login` — validacija email/lozinka
3. Zahtjev: `activation_status = active`
4. Email mora biti verifikovan (`verified` middleware na zaštićenim rutama)
5. Redirect na `dashboard` (role-specific redirect u `HomeController@dashboard`)

---

## Registracija

**Ruta:** `GET/POST /register` — `HomeController@registerForm` / `register`

### Koraci za korisnika

1. Izbor tipa: fizičko lice ili registrovan privredni subjekt
2. Popuna: ime, email, telefon, ulica, **grad**, JMB ili PIB ili pasoš, lozinka
3. Verifikacija emaila (link u poruci — `VerifyEmailNotification`)

### Validacija identiteta

| Polje | Pravilo |
|-------|---------|
| JMB | 13 cifara + algoritam kontrole |
| PIB | 9 cifara, unique |
| Adresa | Kotor opština (v. [business-rules.md](business-rules.md)) |

### Default uloga

`korisnik` (`role_id` iz `RoleSeeder`).

---

## Profil

**Rute:** `profile.edit`, `profile.update`, `profile.password.update`

- Ažuriranje adrese (ulica + grad), PIB/JMB, kontakt podataka
- `ProfileUpdateRequest` — ista Kotor i PIB pravila kao pri registraciji
- `User::formattedAddress()` — prikaz „ulica, grad“

---

## Breeze vs custom auth

**Važeće stanje:**

- `routes/web.php` registruje custom login/register preko `HomeController`
- `routes/auth.php` (Laravel Breeze) takođe učitava auth rute

**Poznati rizik:** duplikat imena ruta `login` i `register`. Pri debug-u provjeriti red registracije i `php artisan route:list`.

---

## Middleware na zaštićenim rutama

```
auth → verified → module_access_restrict
```

Neulogovan → login. Nеверifikovan email → stranica za verifikaciju (Breeze).

---

## Deaktivacija

Admin može deaktivirati korisnika (`activation_status`). Deaktivirani ne mogu login.

---

## Povezani dokumenti

- [roles-and-permissions.md](roles-and-permissions.md)
- [business-rules.md](business-rules.md)
