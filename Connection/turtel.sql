-- ==========================
--  CREATE DATABASE
-- ==========================
CREATE DATABASE IF NOT EXISTS Turtel;
USE Turtel;

-- ==========================
--  USER TABLE
-- ==========================
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
--  STOK TABLE
-- ==========================
CREATE TABLE stok (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('pakan', 'vitamin', 'obat') NOT NULL,
    nama_stock VARCHAR(100) NOT NULL,
    jumlah INT NOT NULL
);

-- ==========================
--  PAKAN TABLE
-- ==========================
CREATE TABLE pakan (
    id_pakan INT AUTO_INCREMENT PRIMARY KEY,
    jumlah_digunakan INT NOT NULL,
    created_at DATETIME NOT NULL,
    id_stock INT,
    FOREIGN KEY (id_stock) REFERENCES stok(id_stock)
);

-- ==========================
--  TELUR TABLE
-- ==========================
CREATE TABLE telur (
    id_telur INT AUTO_INCREMENT PRIMARY KEY,
    jumlah_telur INT NOT NULL,
    berat FLOAT NOT NULL,
    layed_at DATETIME NOT NULL
);

-- ==========================
--  KANDANG TABLE
-- ==========================
CREATE TABLE kandang (
    id_kandang INT AUTO_INCREMENT PRIMARY KEY,
    nama_kandang VARCHAR(100) NOT NULL,
    jenis_ayam ENUM('negeri', 'kampung') NOT NULL,
    jumlah_ayam INT NOT NULL,
    created_at DATETIME NOT NULL,
    id_telur INT,
    FOREIGN KEY (id_telur) REFERENCES telur(id_telur)
);

-- ==========================
--  LAPORAN TABLE
-- ==========================
CREATE TABLE laporan (
    id_laporan INT AUTO_INCREMENT PRIMARY KEY,
    isi_laporan TEXT NOT NULL,
    tgl_laporan DATE NOT NULL,
    id_user INT,
    id_kandang INT,
    FOREIGN KEY (id_user) REFERENCES user(id_user),
    FOREIGN KEY (id_kandang) REFERENCES kandang(id_kandang)
);

-- ==========================
--  TUGAS TABLE
-- ==========================
CREATE TABLE tugas (
    id_tugas INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL,
    deskripsi_tugas TEXT NOT NULL,
    status ENUM('pending', 'proses', 'selesai') NOT NULL,
    id_user INT,
    id_pakan INT,
    FOREIGN KEY (id_user) REFERENCES user(id_user),
    FOREIGN KEY (id_pakan) REFERENCES pakan(id_pakan)
);