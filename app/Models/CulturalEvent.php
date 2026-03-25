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
    ];

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
}
