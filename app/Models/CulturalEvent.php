<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CulturalEvent extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Koncerti',
        'Predstave',
        'Izložbe',
        'Sportski događaji',
        'Književne večeri',
        'Filmske projekcije',
        'Radionice',
        'Promocije publikacija',
        'Performansi',
        'Filmski festivali',
        'Likovne manifestacije',
        'Prezentacije',
        'Paneli o kulturi',
        'Manifestacije u organizaciji Mjesnih zajednica',
        'Manifestacije u organizaciji NVU',
        'Nešto drugo',
    ];

    /**
     * Reserved default images under public/img/kalendar-kulture/categories/
     * when an event has no uploaded slika.
     */
    public const CATEGORY_DEFAULT_IMAGES = [
        'Koncerti' => 'koncerti.jpg',
        'Predstave' => 'predstave.jpg',
        'Izložbe' => 'izlozbe.jpg',
        'Sportski događaji' => 'sportski-dogadjaji.jpg',
        'Književne večeri' => 'knjizevne-veceri.jpg',
        'Filmske projekcije' => 'filmske-projekcije.jpg',
        'Radionice' => 'radionice.jpg',
        'Promocije publikacija' => 'promocije-publikacija.jpg',
        'Performansi' => 'performansi.jpg',
        'Filmski festivali' => 'filmski-festivali.jpg',
        'Likovne manifestacije' => 'likovne-manifestacije.jpg',
        'Prezentacije' => 'prezentacije.jpg',
        'Paneli o kulturi' => 'paneli-o-kulturi.jpg',
        'Manifestacije u organizaciji Mjesnih zajednica' => 'manifestacije-mjesne-zajednice.jpg',
        'Manifestacije u organizaciji NVU' => 'manifestacije-nvu.jpg',
    ];

    public const FALLBACK_DEFAULT_IMAGE = 'img/kalendar-kulture-default-event.png';

    public const STATUSES = [
        'draft',
        'published',
        'archived',
        'cancelled',
    ];

    protected $fillable = [
        'naslov',
        'opis',
        'datum_od',
        'datum_do',
        'vrijeme',
        'vrijeme_do',
        'lokacija',
        'kategorija',
        'slika',
        'status',
        'featured',
        'created_by',
    ];

    protected $casts = [
        'datum_od' => 'date',
        'datum_do' => 'date',
        'featured' => 'boolean',
        'created_by' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Public URL for the event image: uploaded file, else category default.
     */
    public function imageUrl(): string
    {
        if ($this->slika) {
            return asset('storage/' . $this->slika);
        }

        return static::defaultImageUrlForCategory($this->kategorija);
    }

    public static function defaultImageUrlForCategory(?string $category): string
    {
        $relative = static::defaultImagePathForCategory($category);

        return asset($relative);
    }

    /**
     * Relative path under public/ for the reserved category image.
     */
    public static function defaultImagePathForCategory(?string $category): string
    {
        $filename = static::CATEGORY_DEFAULT_IMAGES[$category] ?? null;

        if ($filename) {
            $relative = 'img/kalendar-kulture/categories/' . $filename;
            if (is_file(public_path($relative))) {
                return $relative;
            }
        }

        return static::FALLBACK_DEFAULT_IMAGE;
    }
}
