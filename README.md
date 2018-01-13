# lib-doku

doku.com library. Library ini menambahkan satu service dengan nama `doku` yang bisa
diakses dari kontroler dengan perintah `$this->doku->{method}`.

Agar library ini bisa berjalan dengan baik, pastikan menambahkan konfigurasi seperti
dibawah pada konfigurasi aplikasi:

```php
<?php

return [
    'name' => 'Phun',
    ...,
    'doku' => [
        'mallid'        => '99999999',
        'sharedkey'     => 'e4B9c4L8q5K1',
        'chainmerchant' => 'NA'
    ]
];
```

Silahkan mengacu pada wiki untuk informasi lebih lengkap tetang cara penggunaan,
konfigurasi, dan daftar event dan cara penggunaannya yang dikenali oleh library
ini.

Sebagai catatan, pada saat environment development, library doku akan memanggil
API sandbox doku.