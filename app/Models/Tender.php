<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'reference_number',
        'documentation_price',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'documentation_price' => 'decimal:2',
    ];
}

