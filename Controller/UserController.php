<?php
require_once __DIR__ . '/../Connection/Connection.php';

class UserController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Fungsi register user baru
    // Sesuai turtel.sql, tabel `user` memiliki kolom: id_user, username, email, password, role, status
    public function register($username, $email, $password, $role = 'staff', $status = 'aktif')
    {
        // Hash password sebelum disimpan
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah username atau email sudah ada
        $stmt = $this->conn->prepare('SELECT id_user FROM user WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Username atau email sudah terdaftar'];
        }

        $stmt->close();

        // Insert user baru dengan role dan status sesuai schema
        $stmt = $this->conn->prepare('INSERT INTO user (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $username, $email, $hashedPassword, $role, $status);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Registrasi berhasil'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Registrasi gagal: ' . $err];
        }
    }

    // Fungsi login user
    // Mencocokkan username dan password. Mengembalikan id_user, email, role, status ketika berhasil
    public function login($username, $password)
    {
        $stmt = $this->conn->prepare('SELECT id_user, email, password, role, status FROM user WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id_user, $email, $hashedPassword, $role, $status);
            $stmt->fetch();

            // Jika status akun nonaktif, tolak login sebelum verifikasi password
            if (strtolower($status) === 'nonaktif') {
                $stmt->close();
                return ['success' => false, 'message' => 'Akun dinonaktifkan'];
            }

            if (password_verify($password, $hashedPassword)) {
                $stmt->close();
                return [
                    'success' => true,
                    'message' => 'Login berhasil',
                    'user_id' => $id_user,
                    'email' => $email,
                    'role' => $role,
                    'status' => $status,
                ];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Password salah'];
            }
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Username tidak ditemukan'];
        }
    }

    public function updateProfile($userId, $newUsername, $oldPassword = null, $newPassword = null)
    {
        // Ambil data user saat ini
        $stmt = $this->conn->prepare('SELECT password FROM user WHERE id_user = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($currentHashedPassword);
        $stmt->fetch();
        $stmt->close();

        // Jika ingin ganti password, cek password lama
        if ($oldPassword && $newPassword) {
            if (!password_verify($oldPassword, $currentHashedPassword)) {
                return ['success' => false, 'message' => 'Password lama salah'];
            }
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare('UPDATE user SET username = ?, password = ? WHERE id_user = ?');
            $stmt->bind_param('ssi', $newUsername, $newHashedPassword, $userId);
        } else {
            // Hanya update username
            $stmt = $this->conn->prepare('UPDATE user SET username = ? WHERE id_user = ?');
            $stmt->bind_param('si', $newUsername, $userId);
        }

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Profil berhasil diperbarui'];
        } else {
            $err = $this->conn->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Gagal memperbarui profil: ' . $err];
        }
    }
}

?>
