<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth; use App\Models\Raffle; use App\Models\Ticket; use App\Models\Notification; use App\Services\NotificationService;

final class RaffleController
{
    public static function index(): void { view('raffles/index',['raffles'=>Raffle::all(true)]); }
    public static function show(): void { $id=(int)($_GET['id']??0); $raffle=Raffle::find($id); if(!$raffle){http_response_code(404);view('errors/404');return;} view('raffles/show',['raffle'=>$raffle,'mine'=>Auth::check()?Raffle::ticketsForUser((int)Auth::user()['id'],$id):[]]); }
    public static function buy(): void {
        if(!Auth::canBuy()){flash('error',Auth::isAdmin()?'La cuenta de administrador no puede comprar boletos.':'Tu cuenta solo permite consultar productos. Para comprar, crea una cuenta con permiso de compra o inicia sesión con ella.'); redirect(route('raffle',['id'=>(int)($_POST['raffle_id']??$_GET['id']??0)]));}
        if($_SERVER['REQUEST_METHOD']!=='POST') redirect(route('raffle',['id'=>(int)($_GET['id']??0)]));
        $id=(int)($_POST['raffle_id']??0); $qty=(int)($_POST['quantity']??0);
        try{
            $purchaseId=Ticket::buy($id,(int)Auth::user()['id'],$qty);
            $raffle=Raffle::find($id);
            if($raffle && (int)$raffle['sold_count'] >= (int)$raffle['tickets_to_target']){
                $result=Raffle::closeAndDraw($id);
                if($result){
                    $closedRaffle=Raffle::find($id);
                    NotificationService::winner($result['user'],$closedRaffle,$result['winner']);
                    $admin=self::adminUser(); if($admin) NotificationService::raffleClosed($admin,$closedRaffle);
                }
            }
            flash('success','Compra registrada. Folio de compra #'.$purchaseId.'.');
        }catch(\Throwable $e){flash('error',$e->getMessage());}
        redirect(route('raffle',['id'=>$id]));
    }
    private static function adminUser(): ?array {
        $stmt=\App\Core\Database::get()->query("SELECT * FROM users WHERE role='admin' ORDER BY id LIMIT 1"); return $stmt->fetch() ?: null;
    }
}
