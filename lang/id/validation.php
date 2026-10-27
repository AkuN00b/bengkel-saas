<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'not_regex' => ':attribute mengandung karakter yang tidak didukung.',
    'min' => ['string' => ':attribute minimal :min karakter.'],
    'max' => ['string' => ':attribute maksimal :max karakter.'],
    'attributes' => [
        'name' => 'Nama',
        'email' => 'Email',
        'password' => 'Kata sandi',
        'remember' => 'Ingat saya',
        'token' => 'Token reset',
    ],
];
