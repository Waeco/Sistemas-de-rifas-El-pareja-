<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Notification;

final class NotificationService
{
    public static function winner(array $user, array $raffle, array $ticket): void {
        $message='¡Felicidades! Ganaste la rifa '.$raffle['product_name'].' con el boleto #'.$ticket['folio'].'.';
        self::send($user,$raffle['id'],'winner','¡Ganaste una rifa!',$message);
    }

    public static function raffleClosed(array $admin, array $raffle): void {
        $message='La rifa '.$raffle['code'].' de '.$raffle['product_name'].' se cerró y el sistema realizó el sorteo.';
        self::send($admin,$raffle['id'],'closed','Rifa cerrada',$message);
    }

    private static function send(array $user,int $raffleId,string $type,string $title,string $message): void {
        $channel=$user['preferred_channel'] ?? 'email';
        $status='pending';
        if($channel==='email') $status=self::sendEmail($user['email'],$title,$message) ? 'sent':'pending';
        elseif($channel==='sms') $status=self::sendTwilio($user['phone'] ?? '',$message,false) ? 'sent':'pending';
        elseif($channel==='whatsapp') $status=self::sendTwilio($user['phone'] ?? '',$message,true) ? 'sent':'pending';
        Notification::create((int)$user['id'],$raffleId,$channel,$title,$message,$status);
    }

    private static function sendEmail(string $to,string $subject,string $message): bool {
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)) return false;
        $headers='From: '.MAIL_FROM."\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        return @mail($to,$subject,$message,$headers);
    }

    private static function sendTwilio(string $to,string $message,bool $whatsapp): bool {
        if((!(TWILIO_ACCOUNT_SID && TWILIO_AUTH_TOKEN) && !(TWILIO_API_KEY_SID && TWILIO_API_KEY_SECRET)) || !$to) return false;
        $from=$whatsapp ? TWILIO_WHATSAPP_FROM : TWILIO_SMS_FROM;
        $toFormatted=$whatsapp ? 'whatsapp:'.$to : $to;
        $url='https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode(TWILIO_ACCOUNT_SID).'/Messages.json';
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_USERPWD=>(TWILIO_API_KEY_SID && TWILIO_API_KEY_SECRET) ? TWILIO_API_KEY_SID.':'.TWILIO_API_KEY_SECRET : TWILIO_ACCOUNT_SID.':'.TWILIO_AUTH_TOKEN,
            CURLOPT_POSTFIELDS=>http_build_query(['To'=>$toFormatted,'From'=>$from,'Body'=>$message]),
            CURLOPT_TIMEOUT=>15,
        ]);
        $response=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
        return $response!==false && $code>=200 && $code<300;
    }
}
