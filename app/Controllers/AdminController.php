<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth; use App\Core\Database; use App\Models\Product; use App\Models\Raffle; use App\Models\User; use App\Services\NotificationService;

final class AdminController
{
    public static function dashboard(): void {
        $db=Database::get();
        $stats=[
            'today'=>(float)$db->query("SELECT COALESCE(SUM(p.total),0) FROM purchases p WHERE p.status='paid' AND DATE(p.created_at)=CURDATE()")->fetchColumn(),
            'week'=>(float)$db->query("SELECT COALESCE(SUM(p.total),0) FROM purchases p WHERE p.status='paid' AND YEARWEEK(p.created_at,1)=YEARWEEK(CURDATE(),1)")->fetchColumn(),
            'month'=>(float)$db->query("SELECT COALESCE(SUM(p.total),0) FROM purchases p WHERE p.status='paid' AND YEAR(p.created_at)=YEAR(CURDATE()) AND MONTH(p.created_at)=MONTH(CURDATE())")->fetchColumn(),
            'profit_today'=>(float)$db->query("SELECT COALESCE(SUM(p.total - (pr.price / NULLIF(r.tickets_to_target,0) * p.quantity)),0) FROM purchases p JOIN raffles r ON r.id=p.raffle_id JOIN products pr ON pr.id=r.product_id WHERE p.status='paid' AND DATE(p.created_at)=CURDATE()")->fetchColumn(),
            'profit_week'=>(float)$db->query("SELECT COALESCE(SUM(p.total - (pr.price / NULLIF(r.tickets_to_target,0) * p.quantity)),0) FROM purchases p JOIN raffles r ON r.id=p.raffle_id JOIN products pr ON pr.id=r.product_id WHERE p.status='paid' AND YEARWEEK(p.created_at,1)=YEARWEEK(CURDATE(),1)")->fetchColumn(),
            'profit_month'=>(float)$db->query("SELECT COALESCE(SUM(p.total - (pr.price / NULLIF(r.tickets_to_target,0) * p.quantity)),0) FROM purchases p JOIN raffles r ON r.id=p.raffle_id JOIN products pr ON pr.id=r.product_id WHERE p.status='paid' AND YEAR(p.created_at)=YEAR(CURDATE()) AND MONTH(p.created_at)=MONTH(CURDATE())")->fetchColumn(),
            'tickets'=>(int)$db->query("SELECT COUNT(*) FROM tickets WHERE status='sold'")->fetchColumn(),
            'closed'=>(int)$db->query("SELECT COUNT(*) FROM raffles WHERE status='closed'")->fetchColumn(),
            'open'=>(int)$db->query("SELECT COUNT(*) FROM raffles WHERE status='open'")->fetchColumn(),
        ];
        $recent=$db->query("SELECT p.*,u.name user_name,r.code,pr.name product_name FROM purchases p JOIN users u ON u.id=p.user_id JOIN raffles r ON r.id=p.raffle_id JOIN products pr ON pr.id=r.product_id ORDER BY p.id DESC LIMIT 10")->fetchAll();
        $registeredUsers=$db->query("SELECT id,username,name,email,phone,purchase_mode,created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 100")->fetchAll();
        $winners=$db->query("SELECT r.id,r.code,r.closed_at,pr.name product_name,t.folio winner_folio,u.name winner_name,u.email winner_email,u.phone winner_phone FROM raffles r JOIN products pr ON pr.id=r.product_id LEFT JOIN tickets t ON t.id=r.winner_ticket_id LEFT JOIN users u ON u.id=t.user_id WHERE r.status='closed' ORDER BY r.closed_at DESC,r.id DESC")->fetchAll();
        view('admin/dashboard',['stats'=>$stats,'recent'=>$recent,'registeredUsers'=>$registeredUsers,'winners'=>$winners]);
    }
    public static function products(): void { $edit=isset($_GET['edit'])?Product::find((int)$_GET['edit']):null; view('admin/products',['products'=>Product::all(),'edit'=>$edit]); }
    public static function saveProduct(): void {
        $id=(int)($_POST['id']??0); $name=trim($_POST['name']??''); $desc=trim($_POST['description']??''); $price=(float)($_POST['price']??0); $active=isset($_POST['active'])?1:0;
        if($name===''||$price<=0){flash('error','Nombre y precio son obligatorios.');redirect(route('admin_products'));}
        $image=self::uploadImage($_FILES['image']??null);
        if($id){$current=Product::find($id); Product::update($id,['name'=>$name,'description'=>$desc,'price'=>$price,'image_path'=>$image ?: ($current['image_path']??null),'active'=>$active]); \App\Models\Raffle::refreshPricingForProduct($id); flash('success','Producto actualizado. La rifa abierta se recalculó si aún no tenía boletos vendidos.');}
        else { $pid=Product::create(['name'=>$name,'description'=>$desc,'price'=>$price,'image_path'=>$image]); Raffle::createFromProduct($pid); flash('success','Producto y rifa creados automáticamente.'); }
        redirect(route('admin_products'));
    }
    public static function deleteProduct(): void { $id=(int)($_POST['id']??0); try{Product::delete($id);flash('success','Producto eliminado.');}catch(\Throwable $e){flash('error','No se puede eliminar porque tiene una rifa relacionada.');} redirect(route('admin_products')); }
    public static function raffles(): void { view('admin/raffles',['raffles'=>Raffle::all(false)]); }
    public static function draw(int $id): void { $result=Raffle::closeAndDraw($id); if($result){$raffle=Raffle::find($id); NotificationService::winner($result['user'],$raffle,$result['winner']); $admin=User::find((int)Auth::user()['id']); if($admin) NotificationService::raffleClosed($admin,$raffle); flash('success','Sorteo realizado.');} else flash('error','La rifa no está lista para cerrarse o ya fue cerrada.'); redirect(route('admin_raffles')); }
    private static function uploadImage(?array $file): ?string {
        if(!$file || ($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
        if(($file['error']??1)!==UPLOAD_ERR_OK || ($file['size']??0)>MAX_UPLOAD_BYTES) throw new \RuntimeException('Imagen inválida o mayor a 2 MB.');
        $finfo=new \finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']); $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!isset($map[$mime])) throw new \RuntimeException('Solo JPG, PNG o WEBP.');
        if(!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0775,true); $name=bin2hex(random_bytes(10)).'.'.$map[$mime]; move_uploaded_file($file['tmp_name'],UPLOAD_DIR.'/'.$name); return 'assets/uploads/'.$name;
    }
}
