<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'name',
        'description'
    ];

    // Veza: prioritet pripada konkursu
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}