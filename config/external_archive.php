<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delete local file after successful MEGA archive upload
    |--------------------------------------------------------------------------
    |
    | First Digital Kotor deploy keeps the local private file by default.
    | Local deletion is allowed only after MEGA upload succeeds, the DB row
    | is updated to "uploaded", and this flag is true.
    |
    */
    'delete_local_after_upload' => (bool) env('EXTERNAL_ARCHIVE_DELETE_LOCAL_AFTER_UPLOAD', false),

    /*
    |--------------------------------------------------------------------------
    | Document Library server-side MEGA archive (Paket 2A)
    |--------------------------------------------------------------------------
    |
    | When false (default), DocumentController::store and ProcessDocumentJob
    | behave as before (no ExternalFileArchiveService). Browser MEGA upload
    | remains the primary UI path until a later package switches the frontend.
    |
    | When true, store() queues ProcessDocumentJob which archives the processed
    | PDF via ExternalFileArchiveService after local PDF processing.
    |
    | Status note: "processed" = local PDF ready for download
    | (see DocumentController::download). Archive runs after that.
    | Archive failure is recorded on external_file_archives; UserDocument
    | stays "processed" when the local PDF remains usable.
    |
    */
    'library_upload' => (bool) env('EXTERNAL_ARCHIVE_LIBRARY_UPLOAD', false),

    /*
    |--------------------------------------------------------------------------
    | Node process timeouts (seconds)
    |--------------------------------------------------------------------------
    */
    'diagnose_timeout_seconds' => (int) env('EXTERNAL_ARCHIVE_DIAGNOSE_TIMEOUT', 120),

    'upload_timeout_seconds' => (int) env('EXTERNAL_ARCHIVE_UPLOAD_TIMEOUT', 900),

];
