<?php

$conn = pg_connect("
    host=localhost
    port=5432
    dbname=proy2
    user=postgres
    password=HOLA
");



if (!$conn) {
    die("Error de conexion");
}

?>