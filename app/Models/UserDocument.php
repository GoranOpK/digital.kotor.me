<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'name',
        'file_path',
        'cloud_path',
        'original_file_path',
        'original_filename',
        'file_size',
        'expires_at',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'processed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Relacija sa korisnikom
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Proverava da li je dokument istekao
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Formatira veličinu fajla za prikaz
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

