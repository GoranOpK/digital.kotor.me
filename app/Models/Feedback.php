<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model za povratne informacije korisnika
 * 
 * Omogućava korisnicima da pošalju feedback o portalu,
 * bilo da su prijavljeni ili ne
 */
class Feedback extends Model
{
    use HasFactory;

    /**
     * Naziv tabele
     *
     * @var string
     */
    protected $table = 'feedback';

    /**
     * Polja koja se mogu masovno dodjeljivati
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'admin_response',
        'responded_at',
    ];

    /**
     * Polja koja su datumi
     *
     * @var array<int, string>
     */
    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * Relacija sa korisnikom
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Provjera da li je feedback riješen
     * 
     * @return bool
     */
    public function isResolved()
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Označi feedback kao riješen
     * 
     * @return void
     */
    public function markAsResolved()
    {
        $this->status = 'resolved';
        $this->save();
    }
}
