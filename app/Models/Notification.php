<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

final class Notification
{
    public static function create(int $userId, ?int $raffleId, string $channel, string $title, string $message, string $status='pending'): int {
        $stmt=Database::get()->prepare('INSERT INTO notifications(user_id,raffle_id,channel,title,message,status,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $stmt->execute([$userId,$raffleId,$channel,$title,$message,$status]); return (int)Database::get()->lastInsertId();
    }
    public static function forUser(int $userId): array {
        $stmt=Database::get()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC'); $stmt->execute([$userId]); return $stmt->fetchAll();
    }
}
