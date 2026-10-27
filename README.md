# Rifas el Pareja

Sistema de rifas web en **PHP puro + MySQL + Bulma CSS**, con interfaz pensada para 90% web y 10% móvil (notificaciones).

## Funcionalidades

- Catálogo público de productos sin mostrar precios ni ofrecer compra.
- Registro con cuenta de consulta o cuenta habilitada para comprar.
- El administrador puede consultar el catálogo y administrar rifas, pero no comprar boletos.
- Compra de cualquier cantidad de boletos y participación en distintas rifas.
- Precio automático del boleto = 7% del costo del producto.
- Cálculo de boletos para cubrir el costo y de la meta de 2×.
- Generación de folios secuenciales por rifa.
- Cierre automático al venderse la meta configurada.
- Sorteo con `random_int()` entre boletos vendidos.
- Registro del boleto ganador.
- Notificación interna y canal preferido por correo/SMS/WhatsApp.
- Administración de productos e imágenes.
- Estadísticas de ingresos por día, semana y mes.
- Diseño responsive con Bulma.

## Regla económica implementada

El boleto cuesta exactamente el 7% del valor del producto, redondeado a 2 decimales.
La aplicación calcula:

- `boletos_to_cover_cost = ceil(costo / boleto)`
- `boletos_to_target = ceil((costo * 2) / boleto)`

La rifa se cierra al alcanzar `boletos_to_target`. Esto evita fracciones de boletos y hace explícita la diferencia entre **cubrir el costo** y alcanzar la **meta de ingresos equivalente a 2×**.

## Instalación local

1. Tener PHP 8.2+ con extensiones `pdo_mysql`, `fileinfo`, `curl` y `mbstring`.
2. Tener MySQL/MariaDB.
3. Crear la base/importar `database/schema.sql`.
4. Importar `database/seed.sql`.
   Si la base ya existe, ejecutar una vez `database/migrations/20261001_purchase_mode.sql` para agregar la modalidad de cuenta.
5. Ejecutar `php database/seed.php` para generar las 10 rifas demo.
6. Para una prueba rápida, desde la carpeta del proyecto ejecuta `# No es necesario usar php -S en XAMPP. Coloca la carpeta dentro de htdocs.` y abre `http://localhost/rifas_el_pareja/`. En XAMPP, coloca la carpeta dentro de `htdocs` y usa `public/` como document root o ajusta el alias.
7. Verificar `config/config.php`.

### Credenciales MySQL solicitadas

- host: `localhost`
- usuario: `root`
- base: `sistema_rifas`
- contraseña: `72fbeb2fg`

### Acceso administrador de la aplicación

- usuario: `admin`
- contraseña: `Admin123!`

Cámbiala antes de usar el sistema en producción.

## Notificaciones móviles

En `config/config.php` están los campos para Twilio. El sistema guarda siempre la notificación en la base de datos. Correo usa `mail()` de PHP; SMS/WhatsApp usan Twilio cuando las credenciales están configuradas. Se admiten Account SID + Auth Token o API Key SID + API Key Secret. Si no hay credenciales, quedan como `pending` y siguen apareciendo dentro del sistema.

## Compra y pagos

La compra incluida es un **registro de venta local**, no un cobro real con tarjeta. Para producción debe conectarse una pasarela de pago antes de marcar la compra como `paid`.


### XAMPP / Apache

Este paquete está preparado para abrirse directamente en `http://localhost/rifas_el_pareja/` sin configurar un VirtualHost. El `index.php`, la carpeta `assets/` y `.htaccess` están en la raíz del proyecto para evitar el error `403 Forbidden` causado por no tener un archivo índice en la carpeta servida por Apache.

Si Apache usa HTTPS, la misma ruta puede abrirse como `https://localhost/rifas_el_pareja/`. El certificado local puede mostrar el aviso de sitio no seguro, pero eso es independiente del funcionamiento de PHP.
### Corrección incluida en esta versión
Se corrigió el error `SQLSTATE[HY093]: Invalid parameter number` en el inicio de sesión. La consulta que busca por correo o usuario utiliza ahora placeholders PDO diferentes (`:email_login` y `:username_login`), compatible con `PDO::ATTR_EMULATE_PREPARES=false`.
