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
    | Node process timeouts (seconds)
    |--------------------------------------------------------------------------
    */
    'diagnose_timeout_seconds' => (int) env('EXTERNAL_ARCHIVE_DIAGNOSE_TIMEOUT', 120),

    'upload_timeout_seconds' => (int) env('EXTERNAL_ARCHIVE_UPLOAD_TIMEOUT', 900),

];
