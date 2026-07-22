<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Library — upload limits & PDF optimization (Paket 2D)
    |--------------------------------------------------------------------------
    */

    'image_max_kb' => (int) env('DOCUMENT_LIBRARY_IMAGE_MAX_KB', 2048),

    'pdf_max_kb' => (int) env('DOCUMENT_LIBRARY_PDF_MAX_KB', 20480),

    'user_quota_bytes' => (int) env(
        'DOCUMENT_LIBRARY_USER_QUOTA_BYTES',
        20 * 1024 * 1024
    ),

    /** PDFs strictly below this size are stored without rasterization; equal/above are optimized. */
    'pdf_optimization_threshold_bytes' => (int) env(
        'DOCUMENT_LIBRARY_PDF_OPTIMIZATION_THRESHOLD_BYTES',
        3 * 1024 * 1024
    ),

    'pdf_target_dpi' => (int) env('DOCUMENT_LIBRARY_PDF_TARGET_DPI', 200),

    'pdf_grayscale' => filter_var(
        env('DOCUMENT_LIBRARY_PDF_GRAYSCALE', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    'pdf_jpeg_quality' => (int) env('DOCUMENT_LIBRARY_PDF_JPEG_QUALITY', 82),

];
