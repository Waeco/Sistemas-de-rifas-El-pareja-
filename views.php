<?php
declare(strict_types=1);

use App\Core\Auth;

function view(string $name,array $data=[]): void {
    extract($data, EXTR_SKIP);
    $viewPath=__DIR__.'/views/'.$name.'.php';
    if(!is_file($viewPath)){http_response_code(500);echo 'Vista no encontrada';return;}
    require __DIR__.'/views/layout.php';
}
