<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'status',
        'contract_file',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    // Veza: ugovor pripada aplikaciji
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}