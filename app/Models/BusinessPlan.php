<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'business_idea_name',
        'applicant_data',
        'registered_activity_data',
        'summary',
        'product_service',
        'location',
        'pricing',
        'promotion',
        'people_marketing',
        'business_analysis',
        'supply_market',
        'required_funds',
        'revenue_expense_projection',
        'entrepreneur_data',
        'job_schedule',
        'risk_matrix',
    ];

    protected $casts = [
        'applicant_data' => 'array',
        'registered_activity_data' => 'array',
    ];

    /**
     * Veza sa prijavom
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}

