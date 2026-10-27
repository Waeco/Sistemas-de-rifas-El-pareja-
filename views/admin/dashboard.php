<?php $title='Administración'; ?>
<div class="level">
    <div><h1 class="title">Panel de administración</h1><p class="subtitle is-6">Resumen de ventas, usuarios registrados y ganadores.</p></div>
    <div class="buttons"><a class="button is-link" href="<?= route('admin_products') ?>">Productos</a><a class="button is-info" href="<?= route('admin_raffles') ?>">Rifas</a></div>
</div>

<div class="columns">
    <div class="column"><div class="stat-card"><small>Hoy</small><strong><?= money($stats['today']) ?></strong></div></div>
    <div class="column"><div class="stat-card"><small>Semana</small><strong><?= money($stats['week']) ?></strong></div></div>
    <div class="column"><div class="stat-card"><small>Mes</small><strong><?= money($stats['month']) ?></strong></div></div>
</div>
<div class="columns">
    <div class="column"><div class="stat-card"><small>Ganancia estimada hoy</small><strong><?= money($stats['profit_today']) ?></strong></div></div>
    <div class="column"><div class="stat-card"><small>Ganancia estimada semana</small><strong><?= money($stats['profit_week']) ?></strong></div></div>
    <div class="column"><div class="stat-card"><small>Ganancia estimada mes</small><strong><?= money($stats['profit_month']) ?></strong></div></div>
</div>
<div class="columns">
    <div class="column"><div class="box"><p>Boletos vendidos</p><p class="title is-4"><?= $stats['tickets'] ?></p></div></div>
    <div class="column"><div class="box"><p>Rifas abiertas</p><p class="title is-4"><?= $stats['open'] ?></p></div></div>
    <div class="column"><div class="box"><p>Rifas cerradas</p><p class="title is-4"><?= $stats['closed'] ?></p></div></div>
    <div class="column"><div class="box"><p>Usuarios registrados</p><p class="title is-4"><?= count($registeredUsers) ?><?= count($registeredUsers)===100?'+':'' ?></p></div></div>
</div>

<div class="box">
    <h2 class="title is-5">Ganadores de rifas</h2>
    <div class="table-container"><table class="table is-fullwidth is-hoverable">
        <thead><tr><th>Fecha de sorteo</th><th>Rifa</th><th>Producto</th><th>Ganador</th><th>Correo</th><th>Teléfono</th><th>Boleto ganador</th></tr></thead>
        <tbody><?php if(!$winners): ?><tr><td colspan="7" class="has-text-grey">Todavía no hay rifas sorteadas.</td></tr><?php else: foreach($winners as $winner): ?>
        <tr><td><?= e($winner['closed_at']??'—') ?></td><td><?= e($winner['code']) ?></td><td><?= e($winner['product_name']) ?></td><td><?= e($winner['winner_name']??'Ganador no disponible') ?></td><td><?= e($winner['winner_email']??'—') ?></td><td><?= e($winner['winner_phone']??'—') ?></td><td><?= $winner['winner_folio'] ? '#'.e($winner['winner_folio']) : '—' ?></td></tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>

<div class="box">
    <h2 class="title is-5">Usuarios registrados</h2>
    <div class="table-container"><table class="table is-fullwidth is-hoverable">
        <thead><tr><th>Fecha de alta</th><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Permiso</th></tr></thead>
        <tbody><?php if(!$registeredUsers): ?><tr><td colspan="6" class="has-text-grey">Todavía no hay usuarios registrados.</td></tr><?php else: foreach($registeredUsers as $registeredUser): ?>
        <tr><td><?= e($registeredUser['created_at']) ?></td><td><?= e($registeredUser['username']) ?></td><td><?= e($registeredUser['name']) ?></td><td><?= e($registeredUser['email']) ?></td><td><?= e($registeredUser['phone']??'—') ?></td><td><?= ($registeredUser['purchase_mode']??'buy')==='buy'?'Ver y comprar':'Solo consultar' ?></td></tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
    <p class="help">Se muestran los últimos 100 registros.</p>
</div>

<div class="box">
    <h2 class="title is-5">Ventas recientes</h2>
    <div class="table-container"><table class="table is-fullwidth">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Rifa</th><th>Boletos</th><th>Total</th></tr></thead>
        <tbody><?php if(!$recent): ?><tr><td colspan="5" class="has-text-grey">Todavía no hay ventas.</td></tr><?php else: foreach($recent as $sale): ?>
        <tr><td><?= e($sale['created_at']) ?></td><td><?= e($sale['user_name']) ?></td><td><?= e($sale['product_name']) ?> (<?= e($sale['code']) ?>)</td><td><?= (int)$sale['quantity'] ?></td><td><?= money($sale['total']) ?></td></tr>
        <?php endforeach; endif; ?></tbody>
    </table></div>
</div>
