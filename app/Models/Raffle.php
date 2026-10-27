<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

final class Raffle
{
    public static function all(bool $onlyOpen=false): array {
        $sql = 'SELECT r.*, p.name product_name, p.image_path, p.description, p.price product_price FROM raffles r JOIN products p ON p.id=r.product_id';
        if ($onlyOpen) $sql .= " WHERE r.status='open'";
        $sql .= ' ORDER BY r.id DESC';
        return Database::get()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt=Database::get()->prepare('SELECT r.*, p.name product_name,p.description,p.image_path,p.price product_price FROM raffles r JOIN products p ON p.id=r.product_id WHERE r.id=?');
        $stmt->execute([$id]); return $stmt->fetch() ?: null;
    }

    public static function createFromProduct(int $productId): int {
        $product = Product::find($productId); if (!$product) throw new \RuntimeException('Producto no encontrado.');
        $ticketPrice = round((float)$product['price'] * TICKET_PERCENT, 2);
        $toCost = max(1,(int)ceil((float)$product['price'] / $ticketPrice));
        $toTarget = max($toCost,(int)ceil(((float)$product['price'] * TARGET_MULTIPLIER) / $ticketPrice));
        $code = 'R-' . strtoupper(bin2hex(random_bytes(3)));
        $db=Database::get(); $db->beginTransaction();
        try {
            $stmt=$db->prepare('INSERT INTO raffles(product_id,code,ticket_price,tickets_to_cover_cost,tickets_to_target,sold_count,status,created_at) VALUES(?,?,?,?,?,0,\'open\',NOW())');
            $stmt->execute([$productId,$code,$ticketPrice,$toCost,$toTarget]);
            $raffleId=(int)$db->lastInsertId();
            $ticketStmt=$db->prepare('INSERT INTO tickets(raffle_id,folio,status) VALUES(?,?,\'available\')');
            for($i=1;$i<=$toTarget;$i++) { $ticketStmt->execute([$raffleId,str_pad((string)$i,4,'0',STR_PAD_LEFT)]); }
            $db->commit(); return $raffleId;
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }

    public static function closeAndDraw(int $raffleId): ?array {
        $db=Database::get();
        $db->beginTransaction();
        try {
            $stmt=$db->prepare("SELECT * FROM raffles WHERE id=? FOR UPDATE"); $stmt->execute([$raffleId]); $raffle=$stmt->fetch();
            if(!$raffle || $raffle['status']!=='open') { $db->rollBack(); return null; }
            if((int)$raffle['sold_count'] < (int)$raffle['tickets_to_target']) { $db->rollBack(); return null; }
            $stmt=$db->prepare("SELECT * FROM tickets WHERE raffle_id=? AND status='sold'"); $stmt->execute([$raffleId]); $tickets=$stmt->fetchAll();
            if(!$tickets) { $db->rollBack(); return null; }
            $winner=$tickets[random_int(0,count($tickets)-1)];
            $stmt=$db->prepare("UPDATE raffles SET status='closed',closed_at=NOW(),winner_ticket_id=? WHERE id=?"); $stmt->execute([$winner['id'],$raffleId]);
            $stmt=$db->prepare("UPDATE tickets SET is_winner=1 WHERE id=?"); $stmt->execute([$winner['id']]);
            $stmt=$db->prepare('SELECT u.* FROM users u WHERE u.id=?'); $stmt->execute([(int)$winner['user_id']]); $user=$stmt->fetch();
            $db->commit();
            return ['raffle'=>$raffle,'winner'=>$winner,'user'=>$user];
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }

    public static function refreshPricingForProduct(int $productId): void {
        $product = Product::find($productId); if (!$product) return;
        $ticketPrice = round((float)$product['price'] * TICKET_PERCENT, 2);
        $toCost = max(1,(int)ceil((float)$product['price'] / $ticketPrice));
        $toTarget = max($toCost,(int)ceil(((float)$product['price'] * TARGET_MULTIPLIER) / $ticketPrice));
        $db=Database::get();
        $stmt=$db->prepare("SELECT id FROM raffles WHERE product_id=? AND status='open' AND sold_count=0");
        $stmt->execute([$productId]);
        foreach($stmt->fetchAll() as $raffle){
            $raffleId=(int)$raffle['id'];
            $db->prepare('UPDATE raffles SET ticket_price=?,tickets_to_cover_cost=?,tickets_to_target=? WHERE id=?')->execute([$ticketPrice,$toCost,$toTarget,$raffleId]);
            $db->prepare("DELETE FROM tickets WHERE raffle_id=? AND status='available'")->execute([$raffleId]);
            $ticketStmt=$db->prepare("INSERT INTO tickets(raffle_id,folio,status) VALUES(?,?, 'available')");
            for($i=1;$i<=$toTarget;$i++) $ticketStmt->execute([$raffleId,str_pad((string)$i,4,'0',STR_PAD_LEFT)]);
        }
    }

    public static function ticketsForUser(int $userId,int $raffleId): array {
        $stmt=Database::get()->prepare('SELECT * FROM tickets WHERE user_id=? AND raffle_id=? ORDER BY id'); $stmt->execute([$userId,$raffleId]); return $stmt->fetchAll();
    }
}
