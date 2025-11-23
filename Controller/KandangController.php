<?php
require_once __DIR__ . '/../Connection/Connection.php';

class KandangController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Ambil semua kandang beserta info telur terkait (jika ada)
    public function getAll()
    {
        $sql = 'SELECT k.id_kandang, k.nama_kandang, k.jenis_ayam, k.jumlah_ayam, k.created_at, k.id_telur, t.jumlah_telur, t.berat, t.layed_at
                FROM kandang k
                LEFT JOIN telur t ON k.id_telur = t.id_telur
                ORDER BY k.id_kandang DESC';

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

    // Ambil kandang berdasarkan id
    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT k.id_kandang, k.nama_kandang, k.jenis_ayam, k.jumlah_ayam, k.created_at, k.id_telur, t.jumlah_telur, t.berat, t.layed_at FROM kandang k LEFT JOIN telur t ON k.id_telur = t.id_telur WHERE k.id_kandang = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data) {
            return ['success' => true, 'data' => $data];
        }
        return ['success' => false, 'message' => 'Data kandang tidak ditemukan'];
    }

    // Tambah kandang
    public function create($nama_kandang, $jenis_ayam, $jumlah_ayam, $id_telur = null, $created_at = null)
    {
        // Jika created_at tidak diberikan, gunakan waktu sekarang
        if ($created_at === null) {
            $created_at = date('Y-m-d H:i:s');
        }
        
        $stmt = $this->conn->prepare('INSERT INTO kandang (nama_kandang, jenis_ayam, jumlah_ayam, created_at, id_telur) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('ssisi', $nama_kandang, $jenis_ayam, $jumlah_ayam, $created_at, $id_telur);

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['success' => true, 'message' => 'Kandang ditambahkan', 'id_kandang' => $insertId];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menambah kandang: ' . $err];
        }
    }

    // Update kandang
    public function update($id, $nama_kandang, $jenis_ayam, $jumlah_ayam, $created_at, $id_telur = null)
    {
        $stmt = $this->conn->prepare('UPDATE kandang SET nama_kandang = ?, jenis_ayam = ?, jumlah_ayam = ?, created_at = ?, id_telur = ? WHERE id_kandang = ?');
        $stmt->bind_param('ssisii', $nama_kandang, $jenis_ayam, $jumlah_ayam, $created_at, $id_telur, $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Data kandang diperbarui', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui data kandang: ' . $err];
        }
    }

    // Hapus kandang
    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM kandang WHERE id_kandang = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Kandang dihapus'];
            }
            return ['success' => false, 'message' => 'Data kandang tidak ditemukan'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal menghapus data kandang: ' . $err];
        }
    }

    // Set id_telur pada kandang (saat staff menginput telur hari ini)
    public function setTelur($id_kandang, $id_telur)
    {
        $stmt = $this->conn->prepare('UPDATE kandang SET id_telur = ? WHERE id_kandang = ?');
        $stmt->bind_param('ii', $id_telur, $id_kandang);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return ['success' => true, 'message' => 'Kandang diperbarui dengan id_telur', 'affected_rows' => $affected];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui kandang: ' . $err];
        }
    }
}

?>
