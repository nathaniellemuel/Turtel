<?php
require_once __DIR__ . '/../Connection/Connection.php';

class PakanController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua pakan (dengan info stok terkait)
    public function getAll()
    {
        $sql = 'SELECT p.id_pakan, p.jumlah_digunakan, p.created_at, p.id_stock, s.nama_stock, s.kategori
                FROM pakan p
                LEFT JOIN stok s ON p.id_stock = s.id_stock
                ORDER BY p.id_pakan DESC';

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

    // Ambil pakan berdasarkan id
    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT p.id_pakan, p.jumlah_digunakan, p.created_at, p.id_stock, s.nama_stock, s.kategori FROM pakan p LEFT JOIN stok s ON p.id_stock = s.id_stock WHERE p.id_pakan = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => 'Data pakan tidak ditemukan'];
    }

    // Tambah pakan
    public function create($jumlah_digunakan, $created_at, $id_stock = null)
    {
        $stmt = $this->conn->prepare('INSERT INTO pakan (jumlah_digunakan, created_at, id_stock) VALUES (?, ?, ?)');
        $stmt->bind_param('isi', $jumlah_digunakan, $created_at, $id_stock);

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'message' => 'Pakan ditambahkan', 'id_pakan' => $insertId];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menambah pakan: ' . $err];
        }
    }

    // Update pakan
    public function update($id, $jumlah_digunakan, $created_at, $id_stock = null)
    {
        $stmt = $this->conn->prepare('UPDATE pakan SET jumlah_digunakan = ?, created_at = ?, id_stock = ? WHERE id_pakan = ?');
        $stmt->bind_param('isii', $jumlah_digunakan, $created_at, $id_stock, $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Pakan diperbarui', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui pakan: ' . $err];
        }
    }

    // Hapus pakan
    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM pakan WHERE id_pakan = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Pakan dihapus'];
            }
            return ['success' => false, 'message' => 'Data pakan tidak ditemukan'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menghapus pakan: ' . $err];
        }
    }
}

?>
