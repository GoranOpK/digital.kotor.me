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
        'type',
        'document_type',
        'is_required',
        'user_document_id',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    // Veza: dokument pripada aplikaciji
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // Veza: dokument može biti iz biblioteke dokumenata
    public function userDocument()
    {
        return $this->belongsTo(UserDocument::class);
    }
}