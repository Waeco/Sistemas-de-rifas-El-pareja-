<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;

final class Ticket
{
    public static function buy(int $raffleId,int $userId,int $quantity): int {
        if($quantity < 1 || $quantity > 100) throw new \RuntimeException('Cantidad inválida.');
        $db=Database::get();
        $permission=$db->prepare("SELECT role,purchase_mode FROM users WHERE id=?"); $permission->execute([$userId]); $buyer=$permission->fetch();
        if(!$buyer || $buyer['role']!=='user' || ($buyer['purchase_mode']??'buy')!=='buy') throw new \RuntimeException('Esta cuenta no tiene permiso para comprar boletos.');
        $db->beginTransaction();
        try {
            $stmt=$db->prepare("SELECT * FROM raffles WHERE id=? AND status='open' FOR UPDATE"); $stmt->execute([$raffleId]); $raffle=$stmt->fetch();
            if(!$raffle) throw new \RuntimeException('La rifa ya no está disponible.');
            $available=(int)$raffle['tickets_to_target']-(int)$raffle['sold_count'];
            if($quantity>$available) throw new \RuntimeException('Solo quedan '.$available.' boletos disponibles.');
            $stmt=$db->prepare("SELECT id FROM tickets WHERE raffle_id=? AND status='available' ORDER BY id LIMIT {$quantity} FOR UPDATE"); $stmt->execute([$raffleId]); $tickets=$stmt->fetchAll();
            if(count($tickets)!==$quantity) throw new \RuntimeException('No hay suficientes boletos disponibles.');
            $purchaseStmt=$db->prepare('INSERT INTO purchases(user_id,raffle_id,quantity,total,status,created_at) VALUES(?,?,?,?,\'paid\',NOW())');
            $total=round($quantity*(float)$raffle['ticket_price'],2); $purchaseStmt->execute([$userId,$raffleId,$quantity,$total]); $purchaseId=(int)$db->lastInsertId();
            $upd=$db->prepare("UPDATE tickets SET status='sold',user_id=?,purchase_id=?,sold_at=NOW() WHERE id=?");
            foreach($tickets as $ticket) $upd->execute([$userId,$purchaseId,$ticket['id']]);
            $newSold=(int)$raffle['sold_count']+$quantity;
            $db->prepare('UPDATE raffles SET sold_count=? WHERE id=?')->execute([$newSold,$raffleId]);
            $db->commit();
            return $purchaseId;
        } catch(\Throwable $e){$db->rollBack();throw $e;}
    }

    public static function userTickets(int $userId): array {
        $stmt=Database::get()->prepare('SELECT t.*, r.code, r.status raffle_status, p.name product_name FROM tickets t JOIN raffles r ON r.id=t.raffle_id JOIN products p ON p.id=r.product_id WHERE t.user_id=? ORDER BY t.id DESC'); $stmt->execute([$userId]); return $stmt->fetchAll();
    }
}
