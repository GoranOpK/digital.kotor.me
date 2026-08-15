@php
    $coverLocked = $coverLocked ?? false;
    $coverMedia = $coverMedia ?? null;
    $liveCoverMedia = $liveCoverMedia ?? null;
    $coverMode = $coverMode ?? 'event';
    $removeCoverOld = old('remove_cover');
    $removeRequested = $removeCoverOld === true
        || $removeCoverOld === 1
        || $removeCoverOld === '1'
        || $removeCoverOld === 'on';
@endphp

<div
    class="kk-event-cover"
    data-kk-event-cover
    data-max-bytes="{{ \App\Services\CulturalMedia\CulturalMediaFileValidator::MAX_BYTES }}"
    data-min-long-side="800"
    data-locked="{{ $coverLocked ? '1' : '0' }}"
>
    <p class="block text-sm font-medium text-gray-700 mb-1">Naslovna fotografija</p>
    <p class="text-sm text-gray-500 mb-2">Opciono. JPEG, PNG ili WebP, najviše 2 MB. Duža strana veća od 1920 px se smanjuje bez crop-a.</p>

    @if($coverMode === 'proposal' && $liveCoverMedia)
        <p class="text-sm text-gray-600 mb-2">Važeća naslovna na objavljenom događaju ostaje dok se prijedlog ne odobri.</p>
        <img src="{{ $liveCoverMedia->publicUrl() }}" alt="{{ $liveCoverMedia->alt_tekst }}" class="mb-3 max-h-40 rounded-md border border-gray-200 object-contain bg-gray-50">
    @endif

    @if($coverLocked)
        @if($coverMedia)
            <img src="{{ $coverMedia->publicUrl() }}" alt="{{ $coverMedia->alt_tekst }}" class="max-h-48 rounded-md border border-gray-200 object-contain bg-gray-50">
        @else
            <p class="text-sm text-gray-500">Nema naslovne fotografije.</p>
        @endif
        <p class="mt-2 text-sm text-gray-500">Događaj je zaključan; naslovna fotografija se ne može mijenjati.</p>
    @else
        <input type="hidden" name="remove_cover" value="{{ $removeRequested ? '1' : '0' }}" data-kk-cover-remove>

        <div
            class="rounded-md border border-dashed border-gray-300 bg-gray-50 p-4 text-center"
            data-kk-cover-dropzone
        >
            @if($coverMedia && ! $removeRequested)
                <img
                    src="{{ $coverMedia->publicUrl() }}"
                    alt="{{ $coverMedia->alt_tekst }}"
                    class="mx-auto mb-3 max-h-48 rounded-md object-contain bg-white"
                    data-kk-cover-current
                >
            @endif
            <img alt="" class="mx-auto mb-3 max-h-48 rounded-md object-contain bg-white hidden" data-kk-cover-preview>
            <p class="text-sm text-gray-600 mb-2" data-kk-cover-empty @if($coverMedia && ! $removeRequested) hidden @endif>
                Prevucite fotografiju ovdje ili izaberite fajl.
            </p>
            <input
                type="file"
                id="cover_file"
                name="cover_file"
                accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                class="mx-auto block text-sm"
                data-kk-cover-input
            >
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <button type="button" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm" data-kk-cover-pick>
                    Izaberi fotografiju
                </button>
                <button type="button" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm" data-kk-cover-replace @if(! $coverMedia || $removeRequested) hidden @endif>
                    Zamijeni
                </button>
                <button type="button" class="px-3 py-1.5 border border-red-200 rounded-md text-sm text-red-800" data-kk-cover-remove-btn @if(! $coverMedia || $removeRequested) hidden @endif>
                    Ukloni
                </button>
            </div>
            <p class="mt-2 text-sm text-amber-800 hidden" data-kk-cover-warning>
                Duža strana fotografije je manja od 800 px. Možete je sačuvati, ali preporučujemo veću rezoluciju.
            </p>
            @error('cover_file')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>

@once
<script>
(function () {
    function initCover(root) {
        if (root.getAttribute('data-locked') === '1') {
            return;
        }
        var input = root.querySelector('[data-kk-cover-input]');
        var dropzone = root.querySelector('[data-kk-cover-dropzone]');
        var preview = root.querySelector('[data-kk-cover-preview]');
        var current = root.querySelector('[data-kk-cover-current]');
        var empty = root.querySelector('[data-kk-cover-empty]');
        var warning = root.querySelector('[data-kk-cover-warning]');
        var removeField = root.querySelector('[data-kk-cover-remove]');
        var pickBtn = root.querySelector('[data-kk-cover-pick]');
        var replaceBtn = root.querySelector('[data-kk-cover-replace]');
        var removeBtn = root.querySelector('[data-kk-cover-remove-btn]');
        var maxBytes = parseInt(root.getAttribute('data-max-bytes') || '2097152', 10);
        var minLong = parseInt(root.getAttribute('data-min-long-side') || '800', 10);

        function setRemove(on) {
            if (removeField) {
                removeField.value = on ? '1' : '0';
            }
        }

        function showWarning(show) {
            if (!warning) {
                return;
            }
            warning.classList.toggle('hidden', !show);
        }

        function showPreview(url) {
            if (preview) {
                preview.src = url;
                preview.classList.remove('hidden');
            }
            if (current) {
                current.classList.add('hidden');
            }
            if (empty) {
                empty.hidden = true;
            }
            if (replaceBtn) {
                replaceBtn.hidden = false;
            }
            if (removeBtn) {
                removeBtn.hidden = false;
            }
        }

        function handleFile(file) {
            if (!file) {
                return;
            }
            setRemove(false);
            showWarning(false);
            if (file.size > maxBytes) {
                showWarning(false);
            }
            var url = URL.createObjectURL(file);
            showPreview(url);
            var img = new Image();
            img.onload = function () {
                var longSide = Math.max(img.naturalWidth || 0, img.naturalHeight || 0);
                showWarning(longSide > 0 && longSide < minLong);
            };
            img.src = url;
        }

        if (pickBtn && input) {
            pickBtn.addEventListener('click', function () {
                input.click();
            });
        }
        if (replaceBtn && input) {
            replaceBtn.addEventListener('click', function () {
                input.click();
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (input) {
                    input.value = '';
                }
                setRemove(true);
                showWarning(false);
                if (preview) {
                    preview.classList.add('hidden');
                    preview.removeAttribute('src');
                }
                if (current) {
                    current.classList.add('hidden');
                }
                if (empty) {
                    empty.hidden = false;
                }
                removeBtn.hidden = true;
                if (replaceBtn) {
                    replaceBtn.hidden = true;
                }
            });
        }
        if (input) {
            input.addEventListener('change', function () {
                handleFile(input.files && input.files[0]);
            });
        }
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (name) {
                dropzone.addEventListener(name, function (event) {
                    event.preventDefault();
                    dropzone.classList.add('ring-2');
                });
            });
            ['dragleave', 'drop'].forEach(function (name) {
                dropzone.addEventListener(name, function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('ring-2');
                });
            });
            dropzone.addEventListener('drop', function (event) {
                var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
                if (!file || !input) {
                    return;
                }
                var dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                handleFile(file);
            });
        }
    }

    document.querySelectorAll('[data-kk-event-cover]').forEach(initCover);
})();
</script>
@endonce
