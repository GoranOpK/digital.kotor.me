@if(($competition ?? null)?->isCanonicalTestPreview())
    <div class="mmsp-test-notice" role="status" style="background:#fffbeb;border:1px solid #f59e0b;color:#92400e;border-radius:12px;padding:14px 16px;margin:0 0 16px;">
        <strong>Testiranje.</strong>
        Ovo je testni MMSP konkurs. Prijava nije zvanični zahtjev za subvenciju i ne stvara pravo na dodjelu sredstava.
    </div>
@endif
