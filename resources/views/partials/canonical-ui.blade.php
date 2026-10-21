<style>
    /*
     * Canonical MMSP visual layer.
     * Scoped exclusively to .canonical-ui-page so Women/Youth remain untouched.
     * Reuses the visual language already used by the existing entrepreneurship modules.
     */
    .canonical-ui-page {
        --canonical-primary: #0B3D91;
        --canonical-primary-dark: #0A347B;
        --canonical-border: #e5e7eb;
        --canonical-muted: #6b7280;
        --canonical-surface: #ffffff;
        color: #111827;
    }

    .canonical-ui-page > h1 {
        margin: 0 !important;
        padding: 24px 24px 10px;
        border-radius: 16px 16px 0 0;
        background: linear-gradient(90deg, var(--canonical-primary), var(--canonical-primary-dark));
        color: #fff;
        font-size: 28px !important;
        line-height: 1.25;
    }

    .canonical-ui-page > h1 + p {
        margin: 0 0 24px !important;
        padding: 0 24px 24px;
        border-radius: 0 0 16px 16px;
        background: linear-gradient(90deg, var(--canonical-primary), var(--canonical-primary-dark));
        color: rgba(255,255,255,.92) !important;
        line-height: 1.55;
    }

    .canonical-ui-page > h1 + p strong,
    .canonical-ui-page > h1 + p a {
        color: #fff;
    }

    .canonical-ui-page h2 {
        color: #111827;
    }

    .canonical-ui-page input[type="text"],
    .canonical-ui-page input[type="email"],
    .canonical-ui-page input[type="number"],
    .canonical-ui-page input[type="date"],
    .canonical-ui-page input[type="datetime-local"],
    .canonical-ui-page input[type="file"],
    .canonical-ui-page select,
    .canonical-ui-page textarea {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 12px;
        background: #fff;
        color: #111827;
        font: inherit;
    }

    .canonical-ui-page input:focus,
    .canonical-ui-page select:focus,
    .canonical-ui-page textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    }

    .canonical-ui-page input:disabled,
    .canonical-ui-page select:disabled,
    .canonical-ui-page textarea:disabled {
        background: #f3f4f6;
        color: #6b7280;
    }

    .canonical-ui-page label {
        color: #111827;
        font-weight: 500;
    }

    .canonical-ui-page small {
        color: var(--canonical-muted);
        line-height: 1.4;
    }

    .canonical-ui-page table {
        width: 100%;
        border-collapse: collapse;
        background: var(--canonical-surface);
        border-radius: 12px;
        overflow: hidden;
    }

    .canonical-ui-page thead tr {
        background: #f8fafc;
        border-bottom: 2px solid var(--canonical-border);
    }

    .canonical-ui-page th {
        color: #111827;
        font-weight: 700;
    }

    .canonical-ui-page th,
    .canonical-ui-page td {
        padding: 11px 10px;
        vertical-align: top;
    }

    .canonical-ui-page tbody tr {
        border-top: 1px solid var(--canonical-border);
    }

    .canonical-ui-page a {
        color: var(--canonical-primary);
        font-weight: 600;
        text-decoration: none;
    }

    .canonical-ui-page a:hover {
        text-decoration: underline;
    }

    .canonical-ui-page .btn,
    .canonical-ui-page .btn-primary,
    .canonical-ui-page .btn-success,
    .canonical-ui-page button[type="submit"] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 9px 16px;
        border: 0;
        border-radius: 8px;
        background: var(--canonical-primary);
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .canonical-ui-page .btn:hover,
    .canonical-ui-page .btn-primary:hover,
    .canonical-ui-page .btn-success:hover,
    .canonical-ui-page button[type="submit"]:hover {
        background: var(--canonical-primary-dark);
        text-decoration: none;
    }

    .canonical-ui-page fieldset {
        background: var(--canonical-surface);
        border-radius: 12px !important;
    }

    .canonical-ui-page code {
        overflow-wrap: anywhere;
    }

    .canonical-ui-page .canonical-section-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
    }

    @media (max-width: 767px) {
        .canonical-ui-page > h1 {
            font-size: 24px !important;
            padding: 20px 18px 8px;
        }
        .canonical-ui-page > h1 + p {
            padding: 0 18px 20px;
        }
        .canonical-ui-page {
            overflow-x: hidden;
        }
        .canonical-ui-page table {
            display: block;
            overflow-x: auto;
        }
    }

    /* Unified MMSP applicant forms — visually mirrors Women/Youth without touching their views. */
    .canonical-ui-page.mmsp-form-page {
        background: #f9fafb;
        min-height: 100vh;
        padding-top: 24px !important;
        padding-bottom: 40px !important;
    }
    .canonical-ui-page .mmsp-page-header {
        background: linear-gradient(90deg, var(--canonical-primary), var(--canonical-primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .canonical-ui-page .mmsp-page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px;
        line-height: 1.25;
    }
    .canonical-ui-page .mmsp-page-header p {
        color: rgba(255,255,255,.9);
        margin: 0;
        line-height: 1.55;
    }
    .canonical-ui-page .mmsp-form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
        margin-bottom: 24px;
    }
    .canonical-ui-page .mmsp-form-section {
        margin-bottom: 32px;
    }
    .canonical-ui-page .mmsp-form-section:last-child {
        margin-bottom: 0;
    }
    .canonical-ui-page .mmsp-form-section h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--canonical-primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--canonical-border);
    }
    .canonical-ui-page .mmsp-form-group {
        margin-bottom: 20px;
    }
    .canonical-ui-page .mmsp-form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .canonical-ui-page .mmsp-required {
        color: #ef4444;
        margin-left: 4px;
    }
    .canonical-ui-page .mmsp-form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        color: #111827;
        box-sizing: border-box;
        font-family: inherit;
    }
    .canonical-ui-page .mmsp-form-control:focus {
        outline: none;
        border-color: var(--canonical-primary);
        box-shadow: 0 0 0 3px rgba(11,61,145,.1);
    }
    .canonical-ui-page .mmsp-form-control.error {
        border-color: #ef4444;
    }
    .canonical-ui-page .mmsp-form-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .canonical-ui-page .mmsp-form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .canonical-ui-page .mmsp-form-row-3 {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    @media (min-width: 768px) {
        .canonical-ui-page .mmsp-form-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .canonical-ui-page .mmsp-form-row-3 {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    .canonical-ui-page .mmsp-alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .canonical-ui-page .mmsp-alert-success { background:#d1fae5; border-color:#10b981; color:#065f46; }
    .canonical-ui-page .mmsp-alert-info { background:#dbeafe; border-color:#3b82f6; color:#1e40af; }
    .canonical-ui-page .mmsp-alert-warning { background:#fef3c7; border-color:#f59e0b; color:#92400e; }
    .canonical-ui-page .mmsp-alert-error { background:#fee2e2; border-color:#ef4444; color:#991b1b; }

    .canonical-ui-page .mmsp-obrazac-zaglavlje {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .canonical-ui-page .mmsp-obrazac-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 16px;
    }
    .canonical-ui-page .mmsp-obrazac-grb { flex-shrink:0; line-height:0; }
    .canonical-ui-page .mmsp-obrazac-grb img { height:2cm; width:auto; display:block; }
    .canonical-ui-page .mmsp-obrazac-org { flex:1; font-size:13px; line-height:1.5; color:#111; }
    .canonical-ui-page .mmsp-obrazac-contact { text-align:right; font-size:13px; line-height:1.5; color:#111; }
    .canonical-ui-page .mmsp-obrazac-org p,
    .canonical-ui-page .mmsp-obrazac-contact p { margin:0 0 2px; }
    .canonical-ui-page .mmsp-obrazac-meta {
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:12px;
        margin-top:12px;
        padding-top:12px;
        border-top:1px solid #e5e7eb;
    }
    .canonical-ui-page .mmsp-obrazac-number,
    .canonical-ui-page .mmsp-obrazac-code { font-size:14px; font-weight:700; color:#111; }
    .canonical-ui-page .mmsp-obrazac-code { color:#374151; }
    .canonical-ui-page .mmsp-obrazac-title {
        width:100%;
        text-align:center;
        font-size:22px;
        font-weight:700;
        letter-spacing:.02em;
        color:#111;
        margin:8px 0 0;
    }
    .canonical-ui-page .mmsp-obrazac-subtitle {
        width:100%;
        text-align:center;
        font-size:14px;
        line-height:1.5;
        color:#374151;
        margin:12px 0 0;
    }
    .canonical-ui-page .mmsp-dynamic-table {
        width:100%;
        border-collapse:collapse;
        margin:16px 0;
    }
    .canonical-ui-page .mmsp-dynamic-table th,
    .canonical-ui-page .mmsp-dynamic-table td {
        padding:12px;
        border:1px solid #e5e7eb;
        text-align:left;
        vertical-align:top;
    }
    .canonical-ui-page .mmsp-dynamic-table th {
        background:#f9fafb;
        font-weight:600;
        color:#374151;
    }
    .canonical-ui-page .mmsp-actions {
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        align-items:center;
        margin-top:20px;
    }
    .canonical-ui-page .mmsp-btn-secondary {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:40px;
        padding:9px 16px;
        border-radius:8px;
        border:1px solid #d1d5db;
        background:#fff;
        color:var(--canonical-primary);
        font-weight:600;
        text-decoration:none;
    }
    .canonical-ui-page .mmsp-btn-secondary:hover {
        border-color:var(--canonical-primary);
        background:#f8fafc;
        text-decoration:none;
    }
    @media (max-width:640px) {
        .canonical-ui-page .mmsp-form-card { padding:20px; }
        .canonical-ui-page .mmsp-obrazac-top { flex-direction:column; }
        .canonical-ui-page .mmsp-obrazac-contact { text-align:left; }
    }

</style>
