<?php
// Language Configuration
// This file contains all translations for the application

function getLang() {
    return $_SESSION['language'] ?? 'en';
}

function getTranslations() {
    $lang = getLang();
    
    $translations = [
        'en' => [
            // Common
            'profile' => 'Profile',
            'logout' => 'Logout',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'edit' => 'Edit',
            'add' => 'Add',
            'search' => 'Search',
            'action' => 'Action',
            'submit' => 'Submit',
            'close' => 'Close',
            'history' => 'HISTORY',
            'language' => 'Language',
            'no' => 'No',
            'yes' => 'Yes',
            'total' => 'Total',
            'date' => 'Date',
            'note' => 'NOTE',
            'description' => 'Description',
            'today' => 'Today',
            'welcome' => 'Welcome',
            'your_contribution_on' => 'Your contribution on',
            'total_contribution' => 'Total Contribution',
            'data_last_7_days' => 'Data obtained over the last 7 days',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
            
            // Profile
            'username' => 'Username',
            'old_password' => 'Old Password',
            'email' => 'Email',
            'new_password' => 'New Password',
            'save_changes' => 'Save Changes',
            
            // Dashboard
            'dashboard' => 'Dashboard',
            'admin_dashboard' => 'Admin Dashboard',
            'staff_dashboard' => 'Staff Dashboard',
            
            // Barn/Kandang
            'barn' => 'Barn',
            'barn_name' => 'Barn Name',
            'chicken_type' => 'Chicken Type',
            'chicken_count' => 'Chicken Count',
            'add_barn' => 'Add Barn',
            'edit_barn' => 'Edit Barn',
            'delete_barn' => 'Delete Barn',
            'type_negeri' => 'Broiler',
            'type_kampung' => 'Free Range',
            
            // Employee/User
            'employee' => 'Employee',
            'add_employee' => 'Add Employee',
            'edit_employee' => 'Edit Employee',
            'role' => 'Role',
            'status' => 'Status',
            'admin' => 'Admin',
            'staff' => 'Staff',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'password' => 'Password',
            
            // Stock
            'stock' => 'Stock',
            'add_stock' => 'Add Stock',
            'edit_stock' => 'Edit Stock',
            'stock_name' => 'Stock Name',
            'category' => 'Category',
            'quantity' => 'Quantity',
            'cat_feed' => 'Feed',
            'cat_vitamin' => 'Vitamin',
            'cat_medicine' => 'Medicine',
            
            // Feed Stock
            'feed_stock' => 'Feed Stock',
            'add_feed_stock' => 'Add Feed Stock',
            'edit_feed_stock' => 'Edit Feed Stock',
            'for_barn' => 'For Barn',
            'amount_used' => 'Amount Used',
            
            // Egg Production
            'egg_production' => 'EGG PRODUCTION',
            'good' => 'Good',
            'bad' => 'Bad',
            'sold' => 'Sold',
            'available' => 'Available',
            'sell' => 'SELL',
            'sell_eggs' => 'Sell Eggs',
            'quantity_sold' => 'Quantity to Sell',
            'sale_price' => 'Sale Price',
            'sale_date' => 'Sale Date',
            'add_production' => 'Add Production',
            'edit_production' => 'Edit Production',
            'weight' => 'Weight',
            'laid_date' => 'Laid Date',
            'enter_quantity' => 'Enter quantity',
            'enter_price' => 'Enter price',
            'kg' => 'KG',
            
            // Tasks
            'my_tasks' => 'MY TASKS',
            'your_job_for' => 'YOUR JOB FOR',
            'task' => 'Task',
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'mark_progress' => 'In Progress',
            'mark_done' => 'Done',
            'no_tasks' => 'No tasks assigned yet.',
            
            // Messages
            'success' => 'Success',
            'failed' => 'Failed',
            'error' => 'Error',
            'warning' => 'Warning',
            'not_logged_in' => 'Not Logged In',
            'please_login' => 'Please login to access this feature.',
            'are_you_sure' => 'Are you sure?',
            'cannot_undo' => 'You won\'t be able to revert this!',
            'yes_delete' => 'Yes, delete it!',
            'deleted' => 'Deleted!',
            'updated' => 'Updated!',
            'added' => 'Added!',
            
            // Admin specific
            'total_barns' => 'Total Barns',
            'total_chickens' => 'Total Chickens',
            'stock_categories' => 'Stock Categories',
            'recent_tasks' => 'Recent Tasks',
            'assign_task' => 'Assign Task',
            'assign_to' => 'Assign to',
            'feed_type' => 'Feed Type',
            'refill_stock' => 'Refill Stock',
            'refill_amount' => 'Refill Amount',
            'enter_name' => 'Enter name',
            'enter_email' => 'Enter email',
            'enter_password' => 'Enter password',
            'enter_amount' => 'Enter amount',
            'select_barn' => 'Select barn',
            'select_employee' => 'Select employee',
            'select_stock' => 'Select stock',
            'select_category' => 'Select category',
            'created_at' => 'Created At',
            'assigned_to' => 'Assigned to',
            'feed_amount' => 'Feed Amount',
            'note_feed_stock' => 'Note : Please add stock first before using this feature. The feed is for today\'s supply.',
            'note_stock_entry' => 'Note : This is the stock entry. Input your stock here.',
        ],
        'id' => [
            // Common
            'profile' => 'Profil',
            'logout' => 'Keluar',
            'save' => 'Simpan',
            'cancel' => 'Batal',
            'delete' => 'Hapus',
            'edit' => 'Ubah',
            'add' => 'Tambah',
            'search' => 'Cari',
            'action' => 'Aksi',
            'submit' => 'Kirim',
            'close' => 'Tutup',
            'history' => 'RIWAYAT',
            'language' => 'Bahasa',
            'no' => 'Tidak',
            'yes' => 'Ya',
            'total' => 'Total',
            'date' => 'Tanggal',
            'note' => 'CATATAN',
            'description' => 'Deskripsi',
            'today' => 'Hari Ini',
            'welcome' => 'Selamat Datang',
            'your_contribution_on' => 'Kontribusi Anda pada',
            'total_contribution' => 'Total Kontribusi',
            'data_last_7_days' => 'Data diperoleh selama 7 hari terakhir',
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
            
            // Profile
            'username' => 'Nama Pengguna',
            'old_password' => 'Kata Sandi Lama',
            'email' => 'Email',
            'new_password' => 'Kata Sandi Baru',
            'save_changes' => 'Simpan Perubahan',
            
            // Dashboard
            'dashboard' => 'Dasbor',
            'admin_dashboard' => 'Dasbor Admin',
            'staff_dashboard' => 'Dasbor Staff',
            
            // Barn/Kandang
            'barn' => 'Kandang',
            'barn_name' => 'Nama Kandang',
            'chicken_type' => 'Jenis Ayam',
            'chicken_count' => 'Jumlah Ayam',
            'add_barn' => 'Tambah Kandang',
            'edit_barn' => 'Ubah Kandang',
            'delete_barn' => 'Hapus Kandang',
            'type_negeri' => 'Negeri',
            'type_kampung' => 'Kampung',
            
            // Employee/User
            'employee' => 'Karyawan',
            'add_employee' => 'Tambah Karyawan',
            'edit_employee' => 'Ubah Karyawan',
            'role' => 'Peran',
            'status' => 'Status',
            'admin' => 'Admin',
            'staff' => 'Staff',
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            'password' => 'Kata Sandi',
            
            // Stock
            'stock' => 'Stok',
            'add_stock' => 'Tambah Stok',
            'edit_stock' => 'Ubah Stok',
            'stock_name' => 'Nama Stok',
            'category' => 'Kategori',
            'quantity' => 'Jumlah',
            'cat_feed' => 'Pakan',
            'cat_vitamin' => 'Vitamin',
            'cat_medicine' => 'Obat',
            
            // Feed Stock
            'feed_stock' => 'Stok Pakan',
            'add_feed_stock' => 'Tambah Stok Pakan',
            'edit_feed_stock' => 'Ubah Stok Pakan',
            'for_barn' => 'Untuk Kandang',
            'amount_used' => 'Jumlah Digunakan',
            
            // Egg Production
            'egg_production' => 'PRODUKSI TELUR',
            'good' => 'Bagus',
            'bad' => 'Buruk',
            'sold' => 'Terjual',
            'available' => 'Tersedia',
            'sell' => 'JUAL',
            'sell_eggs' => 'Jual Telur',
            'quantity_sold' => 'Jumlah Terjual',
            'sale_price' => 'Harga Jual',
            'sale_date' => 'Tanggal Jual',
            'add_production' => 'Tambah Produksi',
            'edit_production' => 'Ubah Produksi',
            'weight' => 'Berat',
            'laid_date' => 'Tanggal Bertelur',
            'enter_quantity' => 'Masukkan jumlah',
            'enter_price' => 'Masukkan harga',
            'kg' => 'KG',
            
            // Tasks
            'my_tasks' => 'TUGAS SAYA',
            'your_job_for' => 'TUGAS ANDA UNTUK',
            'task' => 'Tugas',
            'pending' => 'Menunggu',
            'in_progress' => 'Dikerjakan',
            'completed' => 'Selesai',
            'mark_progress' => 'Kerjakan',
            'mark_done' => 'Selesai',
            'no_tasks' => 'Belum ada tugas.',
            
            // Messages
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'error' => 'Error',
            'warning' => 'Peringatan',
            'not_logged_in' => 'Belum Login',
            'please_login' => 'Silakan login untuk mengakses fitur ini.',
            'are_you_sure' => 'Apakah Anda yakin?',
            'cannot_undo' => 'Anda tidak dapat mengembalikan ini!',
            'yes_delete' => 'Ya, hapus!',
            'deleted' => 'Terhapus!',
            'updated' => 'Diperbarui!',
            'added' => 'Ditambahkan!',
            
            // Admin specific
            'total_barns' => 'Total Kandang',
            'total_chickens' => 'Total Ayam',
            'stock_categories' => 'Kategori Stok',
            'recent_tasks' => 'Tugas Terbaru',
            'assign_task' => 'Tugaskan',
            'assign_to' => 'Tugaskan ke',
            'feed_type' => 'Jenis Pakan',
            'refill_stock' => 'Isi Ulang Stok',
            'refill_amount' => 'Jumlah Isi Ulang',
            'enter_name' => 'Masukkan nama',
            'enter_email' => 'Masukkan email',
            'enter_password' => 'Masukkan kata sandi',
            'enter_amount' => 'Masukkan jumlah',
            'select_barn' => 'Pilih kandang',
            'select_employee' => 'Pilih karyawan',
            'select_stock' => 'Pilih stok',
            'select_category' => 'Pilih kategori',
            'created_at' => 'Dibuat pada',
            'assigned_to' => 'Ditugaskan ke',
            'feed_amount' => 'Jumlah Pakan',
            'note_feed_stock' => 'Catatan : Harap tambahkan stok terlebih dahulu sebelum menggunakan fitur ini. Pakan untuk pasokan hari ini.',
            'note_stock_entry' => 'Catatan : Ini adalah entri stok. Masukkan stok Anda di sini.',
        ]
    ];
    
    return $translations[$lang];
}

function t($key) {
    $translations = getTranslations();
    return $translations[$key] ?? $key;
}
