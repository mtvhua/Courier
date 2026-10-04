# Envíos Expresso — Courier (Proyecto 1, CC6)

PHP + PostgreSQL + Tailwind. No usa librerías externas: el JSON y el XML del WebService se arman y se leen con funciones propias (`lib/formato.php`).

## Instalación (XAMPP / Apache)

1. Copia la carpeta `Courier` dentro de `htdocs`.
2. En `php.ini`, activa `extension=pgsql` y reinicia Apache.
3. Crea la base y carga el script:
   ```
   psql -U postgres -c "CREATE DATABASE cc6"
   psql -U postgres -d cc6 -f database.sql
   ```
4. Revisa usuario y contraseña en `config/config.php`.
5. Abre `http://localhost/Courier/`.

Administrador inicial: **PerrY / PerrY**.

## Estructura

```
config/config.php     ← lo único que se edita (BD, ID del courier, origen)
lib/db.php            conexión y consultas con parámetros ($1, $2…)
lib/formato.php       JSON y XML hechos a mano + lectura de argumentos (GET, POST o cuerpo JSON)
lib/auth.php          sesión, permisos, mensajes flash
lib/paquetes.php      reglas del negocio: tarifas, crear paquete, cambiar estado
includes/             header y footer comunes (Tailwind configurado una sola vez)
api/                  WebService (consulta, envio, status) + APIs internas (tarifas, rastreo)
admin/                CRUD de envíos (con pestañas por estado), destinos y tiendas
index.php  rastrear.php  enviar.php  mis_envios.php  login.php  registro.php  logout.php
```

## WebService

Gracias a `.htaccess`, las URLs quedan como en el enunciado (si no hay mod_rewrite, usa `api/consulta.php`, etc.):

| URL | Respuesta |
|---|---|
| `/consulta?destino=02001&formato=json` | `{"consultaprecio": {"courrier", "destino", "cobertura": "TRUE/FALSE", "costo"}}` |
| `/envio?orden=&destinatario=&destino=&direccion=&tienda=` | `{"envio": {"courrier", "orden", "tracking", "status", "costo"}}` (o `status: "ERROR"` + `mensaje`) |
| `/status?orden=&tienda=&formato=xml` | `<orden><courrier/><orden/><status/></orden>` |

- `formato=xml` devuelve XML; si no se manda, devuelve JSON.
- Los argumentos también se pueden mandar como JSON en el cuerpo (`Content-Type: application/json`).
- `/envio` solo acepta tiendas registradas en **Admin → Tiendas**. Si se repite la misma orden de la misma tienda, devuelve el envío existente en lugar de duplicarlo.
