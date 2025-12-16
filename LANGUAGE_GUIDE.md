# Multi-Language System - Turtel

## Cara Kerja

Sistem bahasa menggunakan file terpusat `Config/Language.php` yang menyimpan semua terjemahan untuk English dan Indonesia.

### Penggunaan

1. **Include Language.php** di setiap halaman:
```php
require_once __DIR__ . '/../../../Config/Language.php';
```

2. **Gunakan fungsi `t()`** untuk menampilkan teks:
```php
<?= t('egg_production') ?>  // Output: "EGG PRODUCTION" atau "PRODUKSI TELUR"
```

3. **Ganti bahasa** melalui halaman Profile dengan klik tombol "English" atau "Indonesia"

### Status Implementasi

#### ✅ Sudah Diimplementasi:
- `View/pages/Staff/profile.php` - Fully translated
- `View/pages/Admin/profile.php` - Fully translated  
- `View/pages/Staff/egg.php` - Partially translated (top bar, labels, buttons)
- `View/pages/Staff/task.php` - Partially translated (top bar)

#### ⏳ Dalam Proses:
- Halaman lain perlu ditambahkan `require_once Language.php` dan update text dengan `t('key')`

### Menambah Terjemahan Baru

Edit file `Config/Language.php` dan tambahkan key baru:

```php
'en' => [
    'new_text' => 'New Text',
],
'id' => [
    'new_text' => 'Teks Baru',
]
```

Lalu gunakan di halaman PHP:
```php
<?= t('new_text') ?>
```

### Keys Tersedia

Lihat file `Config/Language.php` untuk daftar lengkap keys yang tersedia seperti:
- `profile`, `logout`, `save`, `cancel`, `delete`, `edit`, `add`
- `egg_production`, `good_eggs`, `bad_eggs`, `sell`
- `my_tasks`, `your_job_for`, `pending`, `completed`
- Dan lainnya...

### Bahasa Default

Bahasa default adalah **English**. Ketika user belum memilih bahasa, sistem akan menggunakan English.
