<?php
require_once __DIR__ . '/../Connection/Connection.php';

class StokController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua stok
    public function getAll()
    {
        $sql = 'SELECT id_stock, kategori, nama_stock, jumlah FROM stok ORDER BY id_stock DESC';
        $result = $this->conn->query($sql);

        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->close();
        }

        return ['success' => true, 'data' => $data];
    }

    // Ambil stok berdasarkan id
    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT id_stock, kategori, nama_stock, jumlah FROM stok WHERE id_stock = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => 'Stok tidak ditemukan'];
    }

    // Tambah stok baru
    public function create($kategori, $nama_stock, $jumlah)
    {
        $stmt = $this->conn->prepare('INSERT INTO stok (kategori, nama_stock, jumlah) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $kategori, $nama_stock, $jumlah);

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'message' => 'Stok ditambahkan', 'id_stock' => $insertId];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menambah stok: ' . $err];
        }
    }

    // Update stok
    public function update($id, $kategori, $nama_stock, $jumlah)
    {
        $stmt = $this->conn->prepare('UPDATE stok SET kategori = ?, nama_stock = ?, jumlah = ? WHERE id_stock = ?');
        $stmt->bind_param('ssii', $kategori, $nama_stock, $jumlah, $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Stok diperbarui', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui stok: ' . $err];
        }
    }

    // Hapus stok
    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM stok WHERE id_stock = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Stok dihapus'];
            }
            return ['success' => false, 'message' => 'Stok tidak ditemukan'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menghapus stok: ' . $err];
        }
    }
}

?>
