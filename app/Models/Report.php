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
        'entrepreneur_name',
        'legal_status',
        'business_plan_name',
        'approved_amount',
        'contract_number',
        'report_period_start',
        'report_period_end',
        'description',
        'activities_description',
        'problems_description',
        'successes_description',
        'new_employees',
        'new_product_service',
        'purchases_description',
        'deviations_description',
        'satisfaction_with_cooperation',
        'recommendations',
        'will_apply_again',
        'document_file',
        'financial_report_file',
        'invoices_file',
        'bank_statement_file',
        'status',
        'evaluation_notes',
        'evaluated_at',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'report_period_start' => 'date',
        'report_period_end' => 'date',
        'evaluated_at' => 'datetime',
    ];

    // Veza: izvještaj pripada aplikaciji
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}