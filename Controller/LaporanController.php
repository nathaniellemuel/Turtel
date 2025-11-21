<?php
require_once __DIR__ . '/../Connection/Connection.php';

class LaporanController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua laporan (read-only)
    public function getAll()
    {
        $sql = 'SELECT l.id_laporan, l.isi_laporan, l.tgl_laporan, l.id_user, u.username, l.id_kandang, k.nama_kandang
                FROM laporan l
                LEFT JOIN user u ON l.id_user = u.id_user
                LEFT JOIN kandang k ON l.id_kandang = k.id_kandang
                ORDER BY l.id_laporan DESC';

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

    // Ambil laporan berdasarkan id (read-only)
    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT l.id_laporan, l.isi_laporan, l.tgl_laporan, l.id_user, u.username, l.id_kandang, k.nama_kandang FROM laporan l LEFT JOIN user u ON l.id_user = u.id_user LEFT JOIN kandang k ON l.id_kandang = k.id_kandang WHERE l.id_laporan = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => 'Laporan tidak ditemukan'];
    }
}

?>
