<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth; use App\Models\User;

final class AuthController
{
    public static function login(): void {
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $login=trim($_POST['login']??''); $password=$_POST['password']??'';
            $user=User::findByLogin($login);
            if($user && password_verify($password,$user['password_hash'])) { Auth::login($user); redirect($user['role']==='admin'?route('admin'):route('dashboard')); }
            flash('error','Usuario/correo o contraseña incorrectos.');
        }
        view('auth/login');
    }
    public static function register(): void {
        if($_SERVER['REQUEST_METHOD']==='POST'){
            $data=['username'=>trim($_POST['username']??''),'name'=>trim($_POST['name']??''),'email'=>trim($_POST['email']??''),'phone'=>trim($_POST['phone']??''),'password'=>$_POST['password']??'','preferred_channel'=>$_POST['preferred_channel']??'email','purchase_mode'=>($_POST['purchase_mode']??'buy')==='view'?'view':'buy'];
            if(!$data['username']||!$data['name']||!filter_var($data['email'],FILTER_VALIDATE_EMAIL)||strlen($data['password'])<8){flash('error','Completa los campos. La contraseña debe tener al menos 8 caracteres.');}
            elseif(User::emailExists($data['email'])){flash('error','Ese correo ya está registrado.');}
            else { $id=User::create($data); Auth::login(User::find($id)); flash('success',$data['purchase_mode']==='buy'?'Registro completado. Ya puedes participar en las rifas.':'Registro completado. Tu cuenta permite consultar productos.'); redirect(route('dashboard')); }
        }
        view('auth/register');
    }
    public static function logout(): void { Auth::logout(); redirect(route('home')); }
}
