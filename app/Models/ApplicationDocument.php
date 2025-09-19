<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'name',
        'file_path',
        'type'
    ];

    // Veza: dokument pripada aplikaciji
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}