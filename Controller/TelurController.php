<?php
require_once __DIR__ . '/../Connection/Connection.php';

class TelurController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua telur
    public function getAll()
    {
        $sql = 'SELECT id_telur, jumlah_telur, berat, layed_at FROM telur ORDER BY id_telur DESC';
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

    // Ambil telur berdasarkan id
    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT id_telur, jumlah_telur, berat, layed_at FROM telur WHERE id_telur = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => 'Data telur tidak ditemukan'];
    }

    // Tambah data telur
    public function create($jumlah_telur, $berat, $layed_at)
    {
        $stmt = $this->conn->prepare('INSERT INTO telur (jumlah_telur, berat, layed_at) VALUES (?, ?, ?)');
        $stmt->bind_param('ids', $jumlah_telur, $berat, $layed_at);

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'message' => 'Data telur ditambahkan', 'id_telur' => $insertId];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menambah data telur: ' . $err];
        }
    }

    // Update data telur
    public function update($id, $jumlah_telur, $berat, $layed_at)
    {
        $stmt = $this->conn->prepare('UPDATE telur SET jumlah_telur = ?, berat = ?, layed_at = ? WHERE id_telur = ?');
        $stmt->bind_param('idsi', $jumlah_telur, $berat, $layed_at, $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Data telur diperbarui', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui data telur: ' . $err];
        }
    }

    // Hapus data telur
    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM telur WHERE id_telur = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Data telur dihapus'];
            }
            return ['success' => false, 'message' => 'Data telur tidak ditemukan'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menghapus data telur: ' . $err];
        }
    }
}

?>
