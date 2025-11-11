<?php
require_once __DIR__ . '/../Connection/Connection.php';

function seedUsers($conn) {
    $users = [
        [
            'username' => 'angler1',
            'email' => 'angler1@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'username' => 'angler2',
            'email' => 'angler2@example.com',
            'password' => password_hash('fishyfish', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'username' => 'angler3',
            'email' => 'angler3@example.com',
            'password' => password_hash('letsgofish', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];

    foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO user (username, email, password, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user['username'], $user['email'], $user['password'], $user['created_at']);
        if ($stmt->execute()) {
            echo "User {$user['username']} berhasil di-seed.<br>";
        } else {
            echo "Gagal seed user {$user['username']}: " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
}

// Jalankan seeder
seedUsers($conn);

$conn->close();

?>