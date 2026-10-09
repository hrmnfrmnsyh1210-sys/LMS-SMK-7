<?php

return [
    // Batas ukuran upload file materi/tugas dalam kilobyte (default 20 MB).
    'upload_max_kb' => (int) env('UPLOAD_MAX_KB', 20480),

    // Jumlah baris per halaman pada tabel.
    'per_page' => 15,
];
