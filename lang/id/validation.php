<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'exists' => ':attribute tidak tersedia.',
    'in' => ':attribute tidak valid.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'regex' => 'Format :attribute tidak valid.',
    'not_regex' => ':attribute mengandung karakter yang tidak didukung.',
    'min' => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.'],
    'max' => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.'],
    'attributes' => [
        'name' => 'Nama',
        'workshop_name' => 'Nama bengkel',
        'workshop_id' => 'Bengkel',
        'email' => 'Email',
        'password' => 'Kata sandi',
        'remember' => 'Ingat saya',
        'token' => 'Token reset',
    ],
];
