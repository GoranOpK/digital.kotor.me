<style>
    .mmsp-p3 {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
        color: #111;
    }
    .mmsp-p3 .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .mmsp-p3 .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .mmsp-p3 .page-header .subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        margin: 0;
    }
    .mmsp-p3 .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        max-width: 1200px;
        margin: 0 auto 24px;
    }
    .mmsp-p3 .form-title {
        text-align: center;
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .mmsp-p3 .form-subtitle {
        text-align: center;
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 32px;
        font-style: italic;
    }
    .mmsp-p3 .form-section { margin-bottom: 32px; }
    .mmsp-p3 .form-section-compact { margin-bottom: 20px; }
    .mmsp-p3 .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .mmsp-p3 .form-label-large {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 12px;
    }
    .mmsp-p3 .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
    }
    .mmsp-p3 .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .mmsp-p3 .form-control-readonly {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #6b7280;
    }
    .mmsp-p3 .radio-group {
        display: flex;
        gap: 24px;
        margin-top: 12px;
    }
    .mmsp-p3 .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .mmsp-p3 .radio-option input[type="radio"] { cursor: pointer; }
    .mmsp-p3 .evaluation-table {
        width: 100%;
        border-collapse: collapse;
        margin: 24px 0;
        font-size: 13px;
        table-layout: fixed;
    }
    .mmsp-p3 .evaluation-table th,
    .mmsp-p3 .evaluation-table td {
        border: 1px solid #e5e7eb !important;
        padding: 6px 4px;
        text-align: center;
    }
    .mmsp-p3 .evaluation-table th {
        background: #f9fafb;
        font-weight: bold !important;
        color: #111827;
        font-size: 12px;
    }
    .mmsp-p3 .evaluation-table td { background: #fff; }
    .mmsp-p3 .evaluation-table .criterion-col {
        text-align: left;
        width: 46%;
        padding: 6px;
    }
    .mmsp-p3 .evaluation-table thead .criterion-col { font-size: 12px; }
    .mmsp-p3 .evaluation-table tbody .criterion-col {
        font-size: 15.6px;
        line-height: 1.35;
    }
    .mmsp-p3 .evaluation-table .score-input {
        width: 60px;
        padding: 6px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        text-align: center;
        font-size: 13px;
    }
    .mmsp-p3 .evaluation-table .score-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(11, 61, 145, 0.1);
    }
    .mmsp-p3 .evaluation-table .score-display {
        color: #6b7280;
        font-weight: 500;
    }
    .mmsp-p3 .evaluation-table .average-col {
        background: #f0f9ff;
        font-weight: 600;
        color: var(--primary);
    }
    .mmsp-p3 .evaluation-table .final-score-row {
        background: var(--primary);
        color: #fff !important;
        font-weight: bold !important;
    }
    .mmsp-p3 .evaluation-table .final-score-row .average-col {
        font-weight: bold !important;
        color: #fff !important;
    }
    .mmsp-p3 .evaluation-table .final-score-row td {
        background: var(--primary);
        color: #fff !important;
        font-weight: bold !important;
    }
    .mmsp-p3 .kn-prigovor-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin: 24px auto 0;
        max-width: 1200px;
        box-sizing: border-box;
    }
    .mmsp-p3 .kn-prigovor-card h2 {
        margin: 0 0 12px;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }
    .mmsp-p3 .kn-prigovor-card p,
    .mmsp-p3 .kn-prigovor-card li {
        font-size: 14px;
        color: #374151;
        line-height: 1.5;
    }
    .mmsp-p3 .kn-prigovor-card textarea.kn-prigovor-textarea {
        width: 100%;
        min-height: 120px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .mmsp-p3 .kn-prigovor-actions {
        margin-top: 16px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .mmsp-p3 .kn-prigovor-btn {
        background: #0B3D91;
        color: #fff;
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
    }
    .mmsp-p3 .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
    }
    .mmsp-p3 .btn-primary:hover { background: var(--primary-dark); }
    .mmsp-p3 .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
    .mmsp-p3 .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .mmsp-p3 .mmsp-p3-article20-heading {
        margin-top: 20px;
        margin-bottom: 4px;
        padding-top: 14px;
        border-top: 2px solid #e5e7eb;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        line-height: 1.4;
    }
    @media print {
        @page {
            size: A4;
            margin: 10mm 8mm;
        }
        .mmsp-p3 nav,
        .mmsp-p3 .page-header,
        .mmsp-p3 .btn-primary,
        .mmsp-p3 button,
        .mmsp-p3 a[href],
        .mmsp-p3 .no-print {
            display: none !important;
        }
        .mmsp-p3 { padding: 0; background: #fff; }
        .mmsp-p3 .container {
            padding: 0;
            max-width: 100%;
        }
        .mmsp-p3-ranking { display: none !important; }
        body.mmsp-print-ranking .mmsp-p3-form { display: none !important; }
        body.mmsp-print-ranking .mmsp-p3-ranking { display: block !important; }
        .mmsp-p3 .form-card {
            padding: 0;
            box-shadow: none;
            max-width: 100%;
        }
        .mmsp-p3 .form-title {
            font-size: 12px !important;
            margin-top: 0 !important;
            margin-bottom: 1px !important;
        }
        .mmsp-p3 .form-subtitle {
            font-size: 8px;
            margin-bottom: 0;
        }
        .mmsp-p3 .print-document-header {
            margin-bottom: 2.5em;
            padding-bottom: 0.5em;
        }
        .mmsp-p3 .print-segment-intro {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 10px;
            margin-bottom: 4px;
            margin-top: 0.25em;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .mmsp-p3 .print-segment-intro .form-section-compact { margin-bottom: 0; }
        .mmsp-p3 .print-segment-intro .form-section-compact:last-child { grid-column: 1 / -1; }
        .mmsp-p3 .form-section {
            margin-bottom: 0;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .mmsp-p3 .form-section-compact .radio-group {
            margin-top: 4px;
            gap: 12px;
        }
        .mmsp-p3 .form-section-compact .radio-option,
        .mmsp-p3 .form-section-compact .radio-option label {
            font-size: 9pt;
        }
        .mmsp-p3 .form-section-notes {
            padding-top: 0 !important;
            margin-top: 6px;
        }
        .mmsp-p3 .form-section textarea {
            text-align: left !important;
            width: 100% !important;
            font-size: 9pt;
        }
        .mmsp-p3 .form-section-notes textarea {
            min-height: 2em;
            max-height: 3.5em;
        }
        .mmsp-p3 .mmsp-p3-article20-heading {
        font-size: 9.5pt;
        font-weight: bold;
        border-top: 1px solid #000;
        margin-top: 8px;
        padding-top: 6px;
        page-break-after: avoid;
    }
    .mmsp-p3 .form-label-large { page-break-after: avoid; }
        .mmsp-p3 .form-section-compact .form-label-large,
        .mmsp-p3 .form-section-scores > .form-label-large,
        .mmsp-p3 .form-section-notes > .form-label-large {
            font-size: 9.5pt;
            margin-bottom: 2px;
        }
        .mmsp-p3 .form-section-compact .form-control,
        .mmsp-p3 .form-section-compact .form-control-readonly {
            font-size: 9pt;
            padding: 2px 4px;
            border: none !important;
            background: transparent !important;
        }
        .mmsp-p3 .evaluation-table {
            page-break-inside: auto;
            font-size: 7.5pt;
            margin: 4px 0;
            width: 100%;
            table-layout: fixed;
        }
        .mmsp-p3 .evaluation-table thead { display: table-header-group; }
        .mmsp-p3 .evaluation-table tbody { display: table-row-group; }
        .mmsp-p3 .evaluation-table tr {
            page-break-inside: auto;
            break-inside: auto;
        }
        .mmsp-p3 .evaluation-table .final-score-row {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .mmsp-p3 .evaluation-table th,
        .mmsp-p3 .evaluation-table td {
            border: 1px solid #000 !important;
            padding: 2px 2px;
            line-height: 1.44;
        }
        .mmsp-p3 .evaluation-table th {
            font-size: 7pt;
            font-weight: bold !important;
            padding-top: 3px;
            padding-bottom: 3px;
        }
        .mmsp-p3 .evaluation-table thead .criterion-col {
            font-size: 7pt;
            padding: 2px 4px;
        }
        .mmsp-p3 .evaluation-table tbody .criterion-col {
            font-size: 9.1pt;
            width: 46%;
            padding: 3px 5px;
            line-height: 1.35;
        }
        .mmsp-p3 .evaluation-table .score-input,
        .mmsp-p3 .evaluation-table .score-display {
            font-size: 7.5pt;
            line-height: 1.44;
        }
        .mmsp-p3 .evaluation-table .score-input { padding: 3px 2px; }
        .mmsp-p3 .evaluation-table .average-col {
            font-weight: bold !important;
            color: #000 !important;
        }
        .mmsp-p3 .evaluation-table .final-score-row td,
        .mmsp-p3 .evaluation-table .final-score-row .average-col {
            font-weight: bold !important;
            color: #000 !important;
        }
    }
</style>
