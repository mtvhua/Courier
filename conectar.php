<?php

$conn = pg_connect("
    host=localhost
    port=5432
    dbname=CC6
    user=postgres
    password=HOLA
");



if (!$conn) {
    die("Error de conexion");
}

?>