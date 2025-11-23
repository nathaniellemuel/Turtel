<?php
require_once __DIR__ . '/../Connection/Connection.php';

class TugasController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua tugas dengan info user dan pakan
    public function getAll()
    {
        $sql = 'SELECT t.id_tugas, t.created_at, t.deskripsi_tugas, t.status, t.id_user, u.username, t.id_pakan, p.jumlah_digunakan, s.nama_stock AS nama_pakan FROM tugas t LEFT JOIN user u ON t.id_user = u.id_user LEFT JOIN pakan p ON t.id_pakan = p.id_pakan LEFT JOIN stok s ON p.id_stock = s.id_stock ORDER BY t.id_tugas DESC';

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

    // Ambil tugas berdasarkan id
    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT t.id_tugas, t.created_at, t.deskripsi_tugas, t.status, t.id_user, u.username, t.id_pakan, p.jumlah_digunakan, s.nama_stock AS nama_pakan FROM tugas t LEFT JOIN user u ON t.id_user = u.id_user LEFT JOIN pakan p ON t.id_pakan = p.id_pakan LEFT JOIN stok s ON p.id_stock = s.id_stock WHERE t.id_tugas = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => 'Tugas tidak ditemukan'];
    }

    // Tambah tugas
    public function create($created_at, $deskripsi_tugas, $status, $id_user = null, $id_pakan = null)
    {
        $stmt = $this->conn->prepare('INSERT INTO tugas (created_at, deskripsi_tugas, status, id_user, id_pakan) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssii', $created_at, $deskripsi_tugas, $status, $id_user, $id_pakan);

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'message' => 'Tugas ditambahkan', 'id_tugas' => $insertId];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menambah tugas: ' . $err];
        }
    }

    // Update tugas
    public function update($id, $created_at, $deskripsi_tugas, $status, $id_user = null, $id_pakan = null)
    {
        $stmt = $this->conn->prepare('UPDATE tugas SET created_at = ?, deskripsi_tugas = ?, status = ?, id_user = ?, id_pakan = ? WHERE id_tugas = ?');
        $stmt->bind_param('sssiii', $created_at, $deskripsi_tugas, $status, $id_user, $id_pakan, $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Tugas diperbarui', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui tugas: ' . $err];
        }
    }

    // Hapus tugas
    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM tugas WHERE id_tugas = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Tugas dihapus'];
            }
            return ['success' => false, 'message' => 'Tugas tidak ditemukan'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menghapus tugas: ' . $err];
        }
    }

    // Set status saja (mis. selesai)
    public function setStatus($id, $status)
    {
        $stmt = $this->conn->prepare('UPDATE tugas SET status = ? WHERE id_tugas = ?');
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Status tugas diperbarui', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui status tugas: ' . $err];
        }
    }
}

?>
