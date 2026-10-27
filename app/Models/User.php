<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

final class User
{
    public static function findByLogin(string $login): ?array
    {
        $db = Database::get();
        // PDO con ATTR_EMULATE_PREPARES=false no permite reutilizar
        // el mismo placeholder nombrado en dos posiciones de la consulta.
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email_login OR username = :username_login LIMIT 1');
        $stmt->execute([
            'email_login' => $login,
            'username_login' => $login,
        ]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare('INSERT INTO users (username,name,email,phone,password_hash,role,preferred_channel,purchase_mode) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['username'], $data['name'], $data['email'], $data['phone'] ?: null,
            password_hash($data['password'], PASSWORD_DEFAULT), 'user', $data['preferred_channel'] ?: 'email',
            ($data['purchase_mode'] ?? 'buy') === 'view' ? 'view' : 'buy'
        ]);
        return (int)Database::get()->lastInsertId();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::get()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (bool)$stmt->fetchColumn();
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = Database::get()->prepare('UPDATE users SET name=?, phone=?, preferred_channel=? WHERE id=?');
        $stmt->execute([$data['name'], $data['phone'] ?: null, $data['preferred_channel'], $id]);
    }
}
