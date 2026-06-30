# Plesk Scheduled Tasks - Cron Style Setup

## Konfiguracija za Queue Worker

### Opcija 1: Svaki minut (preporučeno)

1. **Task Type:** `Run a PHP script` ✅
2. **Script path:** `digital.kotor.me/queue-worker.php` (odaberi kroz dijalog box)
3. **Use PHP version:** `8.3.27`
4. **Run:** `Cron style`
5. **Cron expression:** 
   ```
   * * * * *
   ```
   Ovo znači: svaki minut, svaki sat, svaki dan, svaki mesec, svaki dan u nedelji

### Opcija 2: Svakih 30 sekundi (ako želite brže)

**Napomena:** Cron ne podržava direktno sekunde, ali možete kreirati 2 task-a:

**Task 1:**
- **Cron expression:** `* * * * *` (svaki minut)
- **Script path:** `digital.kotor.me/queue-worker.php`

**Task 2:**
- **Cron expression:** `* * * * *` (svaki minut)
- **Script path:** `digital.kotor.me/queue-worker.php`
- **Arguments:** (ostavite prazno)

Međutim, ovo nije idealno jer će se pokrenuti 2 puta u minutu, ne tačno svakih 30 sekundi.

**Bolje rešenje:** Koristite `--stop-when-empty` i pokrenite svaki minut. Ako ima job-ova, obrađiće ih brzo. Ako nema, završiće odmah.

### Opcija 3: Svakih 5 minuta (manje opterećenje)

**Cron expression:**
```
*/5 * * * *
```

Ovo znači: svakih 5 minuta.

## Cron Expression Format

Format: `minute hour day month weekday`

Primeri:
- `* * * * *` - svaki minut
- `*/5 * * * *` - svakih 5 minuta
- `*/2 * * * *` - svakih 2 minuta
- `0 * * * *` - svakog sata (na 0 minuta)
- `*/30 * * * *` - svakih 30 minuta

## Preporuka

**Za queue worker, preporučujem:**

**Cron expression:** `* * * * *` (svaki minut)

**Razlog:**
- `--stop-when-empty` znači da će se završiti odmah ako nema job-ova
- Neće opteretiti server ako nema posla
- Brzo obrađuje job-ove kada se pojave
- Standardna praksa za queue workere

**Ako želite manje opterećenje:**
- `*/5 * * * *` - svakih 5 minuta (dovoljno za većinu slučajeva)

---

**Napomena:** Svakih 30 sekundi nije potrebno jer queue worker sa `--stop-when-empty` završava brzo i ne troši resurse kada nema posla.

