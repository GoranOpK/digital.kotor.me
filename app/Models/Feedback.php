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
     * Statusi povratnih informacija
     */
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

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
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Označi feedback kao riješen
     * 
     * @return void
     */
    public function markAsResolved()
    {
        $this->status = self::STATUS_RESOLVED;
        $this->save();
    }

    /**
     * Vrati ime pošiljaoca feedbacka
     * 
     * @return string
     */
    public function getSenderName()
    {
        if ($this->user) {
            return $this->user->name;
        }
        
        return $this->name ?? $this->email ?? 'Anonimno';
    }

    /**
     * Vrati sve moguće statuse
     * 
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NEW => 'Novo',
            self::STATUS_IN_PROGRESS => 'U toku',
            self::STATUS_RESOLVED => 'Riješeno',
            self::STATUS_CLOSED => 'Zatvoreno',
        ];
    }
}
