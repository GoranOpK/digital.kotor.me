<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpNumber extends Model
{
    use HasFactory;

    protected $table = 'up_number';

    protected $fillable = [
        'competition_id',
        'number',
    ];

    /**
     * Veza: UP broj pripada jednom konkursu
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
