-- ==========================
--  CREATE DATABASE
-- ==========================
CREATE DATABASE IF NOT EXISTS turtel;
USE turtel;

-- ==========================
--  USER TABLE
-- ==========================
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL
);


-- ==========================
--  PEGAWAI TABLE
-- ==========================
CREATE TABLE pegawai (
    id_pegawai INT AUTO_INCREMENT PRIMARY KEY,
    nama_pegawai VARCHAR(100) NOT NULL,
    tgl_lahir DATE,
    tgl_masuk DATE,
    deskripsi_tugas_pegawai TEXT
);

-- ==========================
--  KANDANG TABLE
-- ==========================
CREATE TABLE kandang (
    id_kandang INT AUTO_INCREMENT PRIMARY KEY,
    nama_kandang VARCHAR(100) NOT NULL,
    jenis_ayam ENUM('negeri', 'kampung') NOT NULL,
    jumlah_ayam INT,
    tgl_perolehan DATE
);

-- ==========================
--  TELUR TABLE
-- ==========================
CREATE TABLE telur (
    id_telur INT AUTO_INCREMENT PRIMARY KEY,
    id_kandang INT,
    jumlah_telur INT,
    berat INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kandang) REFERENCES kandang(id_kandang)
);

-- ==========================
--  STOK TABLE
-- ==========================
CREATE TABLE stok (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('pakan', 'vitamin', 'obat') NOT NULL,
    nama_item VARCHAR(100),
    jumlah_total_item INT
);

-- ==========================
--  PAKAN TABLE
-- ==========================
CREATE TABLE pakan (
    id_pakan INT AUTO_INCREMENT PRIMARY KEY,
    id_stok INT,
    nama_kandang VARCHAR(100),
    jumlah_digunakan INT,
    id_item INT,
    FOREIGN KEY (id_item) REFERENCES stok(id_item)
);

-- ==========================
--  LAPORAN TABLE
-- ==========================
CREATE TABLE laporan (
    id_laporan INT AUTO_INCREMENT PRIMARY KEY,
    id_kandang INT,
    jumlah_kandang INT,
    id_telur INT,
    jumlah_telur INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_telur) REFERENCES telur(id_telur),
    FOREIGN KEY (id_kandang) REFERENCES kandang(id_kandang)
);
