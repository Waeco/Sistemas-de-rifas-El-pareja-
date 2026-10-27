<?php
declare(strict_types=1);
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../app/Core/Database.php';
require_once __DIR__.'/../app/Models/Product.php';
require_once __DIR__.'/../app/Models/Raffle.php';
use App\Core\Database; use App\Models\Raffle;
$db=Database::get();
$has=(int)$db->query('SELECT COUNT(*) FROM raffles')->fetchColumn();
if($has===0){ foreach($db->query('SELECT id FROM products WHERE active=1 ORDER BY id')->fetchAll() as $p){ Raffle::createFromProduct((int)$p['id']); } echo "Rifas generadas.\n"; } else echo "Ya existen rifas; no se modificaron.\n";
