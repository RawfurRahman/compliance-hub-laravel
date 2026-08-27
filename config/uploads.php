<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Whitelists
    |--------------------------------------------------------------------------
    |
    | Every file-upload request boundary references these lists so the accepted
    | formats and size caps live in a single place instead of being duplicated
    | across controllers and FormRequests.
    |
    | The "evidence" list is the extension/MIME whitelist in front of the
    | ClamAV scan. It is an additional layer, not a replacement for scanning.
    | The size cap matches DirectEvidenceAnalysisService::MAX_FILE_SIZE
    | (20 MiB = 20 * 1024 * 1024 bytes = 20480 KB).
    |
    */

    'evidence' => [
        // 20 MiB — must stay consistent with DirectEvidenceAnalysisService::MAX_FILE_SIZE.
        'max_size_kb' => 20 * 1024,

        'extensions' => [
            'pdf',
            'png',
            'jpg',
            'jpeg',
            'txt',
            'log',
            'csv',
            'docx',
            'xlsx',
        ],

        // Sniffed content types (finfo/libmagic) that may accompany the
        // extensions above. The mimetypes rule rejects files whose declared
        // extension does not agree with their actual content (e.g. PHP
        // renamed to .pdf).
        'mimetypes' => [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'text/plain',                                                          // .txt, .log, some .csv
            'text/csv',
            'text/x-csv',
            'application/csv',
            'application/vnd.ms-excel',                                            // some .csv/.xls platforms
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',   // .xlsx
            'application/zip',                                                     // OOXML container reported for .docx/.xlsx
        ],
    ],

    'imports' => [
        // Structured data imports (gap assessments, framework controls,
        // required-document lists). These are NOT evidence files and do not go
        // through ClamAV, but they still get a config-driven whitelist at the
        // request boundary.
        'max_size_kb' => 20 * 1024,

        'extensions' => [
            'xlsx',
            'xls',
            'csv',
            'docx',
        ],

        'mimetypes' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel',                                           // .xls / some .csv
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/zip',                                                     // OOXML container
            'text/csv',
            'text/x-csv',
            'application/csv',
            'text/plain',
        ],
    ],

];
