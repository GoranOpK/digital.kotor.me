@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .report-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    @media (min-width: 768px) {
        .form-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-secondary {
        background: #6b7280;
        color: #fff;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .btn-secondary:hover {
        background: #4b5563;
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .info-box {
        background: #f0f9ff;
        border-left: 4px solid var(--primary);
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        color: #374151;
    }
    .info-box ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    .info-box li {
        margin: 4px 0;
    }
    .dynamic-table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;
        font-size: 14px;
    }
    .dynamic-table th,
    .dynamic-table td {
        padding: 12px;
        border: 1px solid #e5e7eb;
        text-align: left;
    }
    .dynamic-table th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
        text-align: center;
    }
    .dynamic-table td {
        padding: 8px;
    }
    .dynamic-table input,
    .dynamic-table input[type="date"] {
        width: 100%;
        padding: 6px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 13px;
    }
    .dynamic-table .row-number {
        text-align: center;
        font-weight: 600;
        width: 50px;
    }
    .total-row {
        background: #f0f9ff;
        font-weight: 600;
    }
    .total-row td {
        text-align: right;
        padding: 12px;
    }
</style>

<div class="report-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Obrazac 4a - FINANSIJSKI IZVJEŠTAJ</h1>
        </div>

        <div class="info-box">
            <p><strong>Napomene:</strong></p>
            <ul>
                <li>Izvještaj prilagoditi proširivanjem tabele po potrebi.</li>
                <li>Potpis i pečat su obavezni.</li>
                <li>Finansijski izvještaj (Obrazac 4a), fakture, izvodi sa banke ili nalozi za plaćanje (žute uplatnice), prilažu se uz Izvještaj o realizaciji biznis plana (Obrazac 4)</li>
                <li>Sve nabavke se moraju odnositi na izvještajni period.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('reports.store-financial', $application) }}" enctype="multipart/form-data" id="financialReportForm">
            @csrf

            <!-- Osnovni podaci -->
            <div class="form-card">
                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                    Osnovni podaci
                </h2>

                <div class="form-group">
                    <label class="form-label">Ime i prezime preduzetnice/nosioca biznisa: <span class="required">*</span></label>
                    <input type="text" name="entrepreneur_name" class="form-control @error('entrepreneur_name') error @enderror" value="{{ old('entrepreneur_name', $report->entrepreneur_name ?? $application->user->name ?? '') }}" required>
                    @error('entrepreneur_name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Pravni status i naziv biznisa: <span class="required">*</span></label>
                    <input type="text" name="legal_status" class="form-control @error('legal_status') error @enderror" value="{{ old('legal_status', $report->legal_status ?? '') }}" required placeholder="npr. Preduzetnik - Ime Prezime / DOO - Naziv društva">
                    @error('legal_status')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Naziv biznis plana: <span class="required">*</span></label>
                    <input type="text" name="business_plan_name" class="form-control @error('business_plan_name') error @enderror" value="{{ old('business_plan_name', $report->business_plan_name ?? $application->business_plan_name ?? '') }}" required>
                    @error('business_plan_name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Iznos odobrenih sredstava: <span class="required">*</span></label>
                        <input type="number" name="approved_amount" class="form-control @error('approved_amount') error @enderror" value="{{ old('approved_amount', $report->approved_amount ?? $application->approved_amount ?? '') }}" step="0.01" min="0" required>
                        @error('approved_amount')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Iznos ukupnih sredstava za realizaciju biznis plana: <span class="required">*</span></label>
                        <input type="number" name="total_amount" class="form-control @error('total_amount') error @enderror" value="{{ old('total_amount', $report->total_amount ?? '') }}" step="0.01" min="0" required>
                        @error('total_amount')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum popunjavanja ovog izvještaja: <span class="required">*</span></label>
                    <input type="date" name="report_date" class="form-control @error('report_date') error @enderror" value="{{ old('report_date', $report->report_date ? $report->report_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                    @error('report_date')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Tabela sa nabavkama -->
            <div class="form-card">
                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                    Tabela sa nabavkama
                </h2>

                <table class="dynamic-table" id="purchasesTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">r.br.</th>
                            <th>Vrsta nabavke</th>
                            <th>Iznos računa<br>(sa PDV-om i ostalim troškovima)</th>
                            <th>Dobavljač</th>
                            <th>Broj fakture</th>
                            <th>Datum izdavanja</th>
                            <th>Broj izvoda i datum plaćanja</th>
                            <th style="width: 80px;">Akcije</th>
                        </tr>
                    </thead>
                    <tbody id="purchasesTableBody">
                        @php
                            $purchases = old('purchases_table', $report->purchases_table ?? [['purchase_type' => '', 'amount' => '', 'supplier' => '', 'invoice_number' => '', 'invoice_date' => '', 'payment_info' => '']]);
                        @endphp
                        @foreach($purchases as $index => $item)
                            <tr>
                                <td class="row-number">{{ $index + 1 }}</td>
                                <td><input type="text" name="purchases_table[{{ $index }}][purchase_type]" class="form-control" value="{{ $item['purchase_type'] ?? '' }}"></td>
                                <td><input type="number" name="purchases_table[{{ $index }}][amount]" class="form-control" value="{{ $item['amount'] ?? '' }}" step="0.01" min="0"></td>
                                <td><input type="text" name="purchases_table[{{ $index }}][supplier]" class="form-control" value="{{ $item['supplier'] ?? '' }}"></td>
                                <td><input type="text" name="purchases_table[{{ $index }}][invoice_number]" class="form-control" value="{{ $item['invoice_number'] ?? '' }}"></td>
                                <td><input type="date" name="purchases_table[{{ $index }}][invoice_date]" class="form-control" value="{{ $item['invoice_date'] ?? '' }}"></td>
                                <td><input type="text" name="purchases_table[{{ $index }}][payment_info]" class="form-control" value="{{ $item['payment_info'] ?? '' }}" placeholder="Broj izvoda i datum"></td>
                                <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2" style="text-align: right; font-weight: 700;">UKUPNO:</td>
                            <td id="totalAmount" style="text-align: right; font-weight: 700;">0.00</td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                </table>
                <button type="button" class="btn-secondary" onclick="addPurchaseRow()" style="margin-top: 12px;">+ Dodaj red</button>
            </div>

            <div class="form-card" style="text-align: center;">
                <button type="submit" class="btn-primary">
                    Sačuvaj finansijski izvještaj
                </button>
                <p style="color: #6b7280; font-size: 14px; margin-top: 16px;">
                    U Kotoru, {{ date('d.m.Y') }} god.
                </p>
                <p style="color: #6b7280; font-size: 14px; margin-top: 8px;">
                    Potpis: _______________________
                </p>
                <a href="{{ route('applications.show', $application) }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
            </div>
        </form>
    </div>
</div>

<script>
function addPurchaseRow() {
    const tbody = document.getElementById('purchasesTableBody');
    const rowIndex = tbody.children.length;
    const row = document.createElement('tr');
    
    row.innerHTML = `
        <td class="row-number">${rowIndex + 1}</td>
        <td><input type="text" name="purchases_table[${rowIndex}][purchase_type]" class="form-control"></td>
        <td><input type="number" name="purchases_table[${rowIndex}][amount]" class="form-control" step="0.01" min="0" onchange="updateTotal()"></td>
        <td><input type="text" name="purchases_table[${rowIndex}][supplier]" class="form-control"></td>
        <td><input type="text" name="purchases_table[${rowIndex}][invoice_number]" class="form-control"></td>
        <td><input type="date" name="purchases_table[${rowIndex}][invoice_date]" class="form-control"></td>
        <td><input type="text" name="purchases_table[${rowIndex}][payment_info]" class="form-control" placeholder="Broj izvoda i datum"></td>
        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
    `;
    
    tbody.appendChild(row);
    updateRowNumbers();
}

function removeTableRow(button) {
    button.closest('tr').remove();
    updateRowNumbers();
    updateTotal();
}

function updateRowNumbers() {
    const tbody = document.getElementById('purchasesTableBody');
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, index) => {
        row.querySelector('.row-number').textContent = index + 1;
    });
}

function updateTotal() {
    const tbody = document.getElementById('purchasesTableBody');
    const amountInputs = tbody.querySelectorAll('input[name*="[amount]"]');
    let total = 0;
    
    amountInputs.forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    
    document.getElementById('totalAmount').textContent = total.toFixed(2);
}

// Inicijalizuj total na učitavanju
document.addEventListener('DOMContentLoaded', function() {
    updateTotal();
    
    // Dodaj event listener-e na sva polja za iznos
    const tbody = document.getElementById('purchasesTableBody');
    tbody.addEventListener('change', function(e) {
        if (e.target.name && e.target.name.includes('[amount]')) {
            updateTotal();
        }
    });
});
</script>
@endsection
