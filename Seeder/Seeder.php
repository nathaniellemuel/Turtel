<?php
require_once __DIR__ . '/../Connection/Connection.php';

function seedUsers($conn) {
    $users = [
        ['username' => 'admin', 'email' => 'admin@example.com', 'password' => password_hash('adminpass', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'aktif'],
        ['username' => 'staff1', 'email' => 'staff1@example.com', 'password' => password_hash('staffpass', PASSWORD_DEFAULT), 'role' => 'staff', 'status' => 'aktif'],
        ['username' => 'staff2', 'email' => 'staff2@example.com', 'password' => password_hash('staffpass2', PASSWORD_DEFAULT), 'role' => 'staff', 'status' => 'aktif']
    ];

    foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO user (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user['username'], $user['email'], $user['password'], $user['role'], $user['status']);
        if ($stmt->execute()) {
            echo "User {$user['username']} berhasil di-seed.\n";
        } else {
            echo "Gagal seed user {$user['username']}: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

function seedStok($conn) {
    $items = [
        ['kategori' => 'pakan', 'nama_stock' => 'Pakan A', 'jumlah' => 100],
        ['kategori' => 'vitamin', 'nama_stock' => 'Vitamin B', 'jumlah' => 50],
        ['kategori' => 'obat', 'nama_stock' => 'Obat C', 'jumlah' => 30]
    ];

    foreach ($items as $it) {
        $stmt = $conn->prepare("INSERT INTO stok (kategori, nama_stock, jumlah) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $it['kategori'], $it['nama_stock'], $it['jumlah']);
        if ($stmt->execute()) {
            echo "Stok {$it['nama_stock']} berhasil di-seed.\n";
        } else {
            echo "Gagal seed stok {$it['nama_stock']}: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

function seedPakan($conn) {
    // Ambil salah satu stok id untuk relasi
    $res = $conn->query("SELECT id_stock FROM stok WHERE kategori = 'pakan' LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    $stockId = $row ? $row['id_stock'] : null;

    $items = [
        ['jumlah_digunakan' => 10, 'created_at' => date('Y-m-d H:i:s'), 'id_stock' => $stockId],
        ['jumlah_digunakan' => 5, 'created_at' => date('Y-m-d H:i:s'), 'id_stock' => $stockId]
    ];

    foreach ($items as $it) {
        $stmt = $conn->prepare("INSERT INTO pakan (jumlah_digunakan, created_at, id_stock) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $it['jumlah_digunakan'], $it['created_at'], $it['id_stock']);
        if ($stmt->execute()) {
            echo "Pakan (jumlah {$it['jumlah_digunakan']}) berhasil di-seed.\n";
        } else {
            echo "Gagal seed pakan: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

function seedTelur($conn) {
    $items = [
        ['jumlah_telur' => 30, 'berat' => 2.4, 'layed_at' => date('Y-m-d H:i:s')],
        ['jumlah_telur' => 25, 'berat' => 2.1, 'layed_at' => date('Y-m-d H:i:s')]
    ];

    foreach ($items as $it) {
        $stmt = $conn->prepare("INSERT INTO telur (jumlah_telur, berat, layed_at) VALUES (?, ?, ?)");
        $stmt->bind_param('ids', $it['jumlah_telur'], $it['berat'], $it['layed_at']);
        if ($stmt->execute()) {
            echo "Telur (jumlah {$it['jumlah_telur']}) berhasil di-seed.\n";
        } else {
            echo "Gagal seed telur: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

function seedKandang($conn) {
    // Ambil salah satu telur id untuk relasi
    $res = $conn->query("SELECT id_telur FROM telur LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    $telurId = $row ? $row['id_telur'] : null;

    $items = [
        ['nama_kandang' => 'Kandang A', 'jenis_ayam' => 'negeri', 'jumlah_ayam' => 50, 'created_at' => date('Y-m-d H:i:s'), 'id_telur' => $telurId],
        ['nama_kandang' => 'Kandang B', 'jenis_ayam' => 'kampung', 'jumlah_ayam' => 30, 'created_at' => date('Y-m-d H:i:s'), 'id_telur' => $telurId]
    ];

    foreach ($items as $it) {
        $stmt = $conn->prepare("INSERT INTO kandang (nama_kandang, jenis_ayam, jumlah_ayam, created_at, id_telur) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssisi', $it['nama_kandang'], $it['jenis_ayam'], $it['jumlah_ayam'], $it['created_at'], $it['id_telur']);
        if ($stmt->execute()) {
            echo "Kandang {$it['nama_kandang']} berhasil di-seed.\n";
        } else {
            echo "Gagal seed kandang {$it['nama_kandang']}: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

function seedLaporan($conn) {
    // Ambil user dan kandang untuk relasi
    $u = $conn->query("SELECT id_user FROM user LIMIT 1");
    $userRow = $u ? $u->fetch_assoc() : null;
    $userId = $userRow ? $userRow['id_user'] : null;

    $k = $conn->query("SELECT id_kandang FROM kandang LIMIT 1");
    $kRow = $k ? $k->fetch_assoc() : null;
    $kandangId = $kRow ? $kRow['id_kandang'] : null;

    $items = [
        ['isi_laporan' => 'Pekerjaan harian selesai', 'tgl_laporan' => date('Y-m-d'), 'id_user' => $userId, 'id_kandang' => $kandangId],
        ['isi_laporan' => 'Pemberian pakan ekstra', 'tgl_laporan' => date('Y-m-d'), 'id_user' => $userId, 'id_kandang' => $kandangId]
    ];

    foreach ($items as $it) {
        $stmt = $conn->prepare("INSERT INTO laporan (isi_laporan, tgl_laporan, id_user, id_kandang) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssii', $it['isi_laporan'], $it['tgl_laporan'], $it['id_user'], $it['id_kandang']);
        if ($stmt->execute()) {
            echo "Laporan ('{$it['isi_laporan']}') berhasil di-seed.\n";
        } else {
            echo "Gagal seed laporan: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

function seedTugas($conn) {
    $u = $conn->query("SELECT id_user FROM user LIMIT 1");
    $userRow = $u ? $u->fetch_assoc() : null;
    $userId = $userRow ? $userRow['id_user'] : null;

    $p = $conn->query("SELECT id_pakan FROM pakan LIMIT 1");
    $pRow = $p ? $p->fetch_assoc() : null;
    $pakanId = $pRow ? $pRow['id_pakan'] : null;

    $items = [
        ['created_at' => date('Y-m-d H:i:s'), 'deskripsi_tugas' => 'Beri pakan ke kandang A', 'status' => 'pending', 'id_user' => $userId, 'id_pakan' => $pakanId],
        ['created_at' => date('Y-m-d H:i:s'), 'deskripsi_tugas' => 'Bersihkan kandang B', 'status' => 'proses', 'id_user' => $userId, 'id_pakan' => $pakanId]
    ];

    foreach ($items as $it) {
        $stmt = $conn->prepare("INSERT INTO tugas (created_at, deskripsi_tugas, status, id_user, id_pakan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssii', $it['created_at'], $it['deskripsi_tugas'], $it['status'], $it['id_user'], $it['id_pakan']);
        if ($stmt->execute()) {
            echo "Tugas ('{$it['deskripsi_tugas']}') berhasil di-seed.\n";
        } else {
            echo "Gagal seed tugas: " . $stmt->error . "\n";
        }
        $stmt->close();
    }
}

// Jalankan semua seeder sesuai urutan untuk memenuhi FK
seedUsers($conn);
seedStok($conn);
seedPakan($conn);
seedTelur($conn);
seedKandang($conn);
seedLaporan($conn);
seedTugas($conn);

$conn->close();

?>