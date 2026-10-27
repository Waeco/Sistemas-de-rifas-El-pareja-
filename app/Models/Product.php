<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

final class Product
{
    public static function all(): array {
        return Database::get()->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt=Database::get()->prepare('SELECT * FROM products WHERE id=?'); $stmt->execute([$id]); return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $stmt=Database::get()->prepare('INSERT INTO products(name,description,price,image_path,active) VALUES(?,?,?,?,1)');
        $stmt->execute([$data['name'],$data['description'],$data['price'],$data['image_path']]); return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id,array $data): void {
        $stmt=Database::get()->prepare('UPDATE products SET name=?,description=?,price=?,image_path=?,active=? WHERE id=?');
        $stmt->execute([$data['name'],$data['description'],$data['price'],$data['image_path'],$data['active'] ?? 1,$id]);
    }

    public static function delete(int $id): void {
        $stmt=Database::get()->prepare('DELETE FROM products WHERE id=?'); $stmt->execute([$id]);
    }
}
