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
</style>
