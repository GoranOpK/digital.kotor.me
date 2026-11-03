@extends('layouts.app')

@section('content')
@include('partials.obrazac-form-styles')
<style>
    /*
     * Obrazac 4a — report-specific chrome only.
     * Base form/card/table/control styles come from .obrazac-form-page (M1b standard).
     * Width override: same document width as Obrazac M1b / Obrazac 2 (see Obrazac 4).
     */
    .report-page.obrazac-form-page > .container {
        width: 100%;
        max-width: 80rem;
    }
    @media (max-width: 767px) {
        .report-page.obrazac-form-page > .container {
            max-width: 100%;
        }
    }
    .report-page .info-box {
        background: #f0f9ff;
        border-left: 4px solid var(--primary);
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        color: var(--obrazac-label, #374151);
    }
    .report-page .info-box ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    .report-page .info-box li {
        margin: 4px 0;
    }
    .report-page .form-card > h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--obrazac-border, #e5e7eb);
    }
    .report-page .dynamic-table {
        table-layout: auto;
        min-width: 100%;
    }
    .report-page .dynamic-table th {
        text-align: center;
    }
    .report-page .dynamic-table .row-number {
        text-align: center;
        font-weight: 600;
        width: 50px;
    }
    .report-page .total-row {
        background: #f0f9ff;
        font-weight: 600;
    }
    .report-page .total-row td {
        text-align: right;
        padding: 12px;
    }
    /*
     * Print: fit entire Obrazac 4a on one A4 page by tightening vertical rhythm
     * (not by cropping, overflow:hidden, or aggressive font scaling).
     */
    @media print {
        @page {
            size: A4;
            margin: 10mm 9mm;
        }
        .report-page.obrazac-form-page {
            padding: 0 !important;
        }
        .report-page.obrazac-form-page > .container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
        }
        .report-page .obrazac-zaglavlje {
            margin-bottom: 8px;
            padding: 10px 12px;
        }
        .report-page .obrazac-zaglavlje-top {
            margin-bottom: 8px;
            gap: 12px;
        }
        .report-page .obrazac-broj-i-naslov {
            margin-top: 6px;
            padding-top: 6px;
        }
        .report-page .obrazac-naslov-prijava {
            margin: 4px 0 0;
            font-size: 16pt;
        }
        .report-page .info-box {
            padding: 8px 10px;
            margin-bottom: 8px;
            font-size: 9.5pt;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .report-page .info-box ul {
            margin: 4px 0 0 18px;
        }
        .report-page .info-box li {
            margin: 2px 0;
        }
        .report-page .form-card {
            margin-bottom: 6px !important;
            padding: 0 !important;
        }
        .report-page .form-card > h2 {
            font-size: 11pt;
            margin: 0 0 6px;
            padding-bottom: 4px;
        }
        .report-page .form-group {
            margin-bottom: 4px !important;
        }
        .report-page .form-label {
            margin-bottom: 1px !important;
            font-size: 9pt;
        }
        .report-page .form-control {
            padding: 2px 4px !important;
            font-size: 9pt !important;
            min-height: 0 !important;
            height: auto !important;
        }
        .report-page .form-row {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px !important;
        }
        .report-page .dynamic-table {
            width: 100%;
            font-size: 8.5pt;
            margin: 6px 0 !important;
        }
        .report-page .dynamic-table th,
        .report-page .dynamic-table td {
            padding: 3px 4px !important;
        }
        .report-page .total-row td {
            padding: 4px !important;
        }
        .report-page .form-card.print-signature {
            margin-top: 8px !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .report-page .form-card.print-signature p {
            margin: 4px 0 !important;
            font-size: 9.5pt !important;
        }
        .report-page button,
        .report-page .btn-primary,
        .report-page .btn-secondary,
        .report-page a[href] {
            display: none !important;
        }
    }
</style>

<div class="report-page obrazac-form-page obrazac-4a">
    <div class="container mx-auto px-4">
        @include('partials.obrazac-zaglavlje', [
            'obrazacLabel' => 'Obrazac 4a',
            'obrazacTitle' => 'FINANSIJSKI IZVJEŠTAJ',
        ])

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
                <h2>Osnovni podaci</h2>

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
                <h2>Tabela sa nabavkama</h2>

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
                            <th class="col-actions">Akcije</th>
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
                                <td class="col-actions"><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2" style="text-align: right; font-weight: 700;">UKUPNO:</td>
                            <td id="totalAmount" style="text-align: right; font-weight: 700;">0.00</td>
                            <td colspan="4"></td>
                            <td class="col-actions"></td>
                        </tr>
                    </tfoot>
                </table>
                <button type="button" class="btn-secondary no-print" onclick="addPurchaseRow()" style="margin-top: 12px;">+ Dodaj red</button>
            </div>

            <div class="form-card print-signature" style="text-align: center;">
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
        <td class="col-actions"><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
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
