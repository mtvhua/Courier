<?php

$conn = pg_connect("
    host=localhost
    port=5432
    dbname=cc6
    user=postgres
    password=1477
");



if (!$conn) {
    die("Error de conexion");
}

?>