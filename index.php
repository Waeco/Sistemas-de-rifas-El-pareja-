<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
foreach (glob(__DIR__ . '/app/Core/*.php') as $file) require_once $file;
foreach (glob(__DIR__ . '/app/Models/*.php') as $file) require_once $file;
foreach (glob(__DIR__ . '/app/Services/*.php') as $file) require_once $file;
foreach (glob(__DIR__ . '/app/Controllers/*.php') as $file) require_once $file;
require_once __DIR__ . '/views.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;

Auth::start();

function requireLogin(): void { if(!Auth::check()){flash('error','Debes iniciar sesión.'); redirect(route('login'));} }
function requireAdmin(): void { if(!Auth::isAdmin()) abort_403(); }

$route=$_GET['route']??'home';
if($_SERVER['REQUEST_METHOD']==='POST' && !Csrf::check($_POST['_csrf']??null)){http_response_code(419);exit('Sesión expirada o token CSRF inválido.');}

switch($route){
case 'home': view('home',['raffles'=>Raffle::all(true)]); break;
case 'login': \App\Controllers\AuthController::login(); break;
case 'register': \App\Controllers\AuthController::register(); break;
case 'logout': \App\Controllers\AuthController::logout(); break;
case 'dashboard': requireLogin(); view('dashboard',['tickets'=>Ticket::userTickets((int)Auth::user()['id']),'notifications'=>\App\Models\Notification::forUser((int)Auth::user()['id']),'raffles'=>Raffle::all(true)]); break;
case 'raffles': \App\Controllers\RaffleController::index(); break;
case 'raffle': \App\Controllers\RaffleController::show(); break;
case 'buy': \App\Controllers\RaffleController::buy(); break;
case 'notifications': requireLogin(); view('notifications',['notifications'=>\App\Models\Notification::forUser((int)Auth::user()['id'])]); break;
case 'profile': requireLogin();
    if($_SERVER['REQUEST_METHOD']==='POST'){
        User::updateProfile((int)Auth::user()['id'], [
            'name'=>trim($_POST['name']??''),
            'phone'=>trim($_POST['phone']??''),
            'preferred_channel'=>$_POST['preferred_channel']??'email'
        ]);
        Auth::login(User::find((int)Auth::user()['id']));
        flash('success','Perfil actualizado.');
        redirect(route('profile'));
    }
    view('profile',['user'=>User::find((int)Auth::user()['id'])]); break;
case 'admin': requireAdmin(); \App\Controllers\AdminController::dashboard(); break;
case 'admin_products': requireAdmin(); if($_SERVER['REQUEST_METHOD']==='POST') \App\Controllers\AdminController::saveProduct(); else \App\Controllers\AdminController::products(); break;
case 'admin_product_delete': requireAdmin(); \App\Controllers\AdminController::deleteProduct(); break;
case 'admin_raffles': requireAdmin(); \App\Controllers\AdminController::raffles(); break;
case 'admin_draw': requireAdmin(); \App\Controllers\AdminController::draw((int)($_POST['id']??0)); break;
default: http_response_code(404); view('errors/404');
}
