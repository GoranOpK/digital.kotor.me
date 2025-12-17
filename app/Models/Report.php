<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'type',
        'description',
        'document_file',
        'status',
        'evaluation_notes',
        'evaluated_at',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

    // Veza: izvještaj pripada aplikaciji
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}