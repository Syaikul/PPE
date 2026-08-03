<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gudang Tujuan Transfer Barang
    |--------------------------------------------------------------------------
    |
    | Satu-satunya gudang yang boleh jadi tujuan transfer.
    | Nilai dicocokkan dengan namagudang di master data API (partial, tidak case-sensitive).
    |
    | Ganti tujuan transfer — ubah nilai di bawah atau lewat .env:
    |
    |   TRANSFER_GUDANG_TUJUAN=Workshop   → transfer ke Workshop
    |   TRANSFER_GUDANG_TUJUAN=OSES        → transfer ke OSES
    |   TRANSFER_GUDANG_TUJUAN=ONWJ       → transfer ke ONWJ
    |
    */
    'gudang_tujuan_nama' => env('TRANSFER_GUDANG_TUJUAN', 'Workshop'),

];
