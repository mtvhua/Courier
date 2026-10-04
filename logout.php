<?php
require_once __DIR__ . '/lib/auth.php';

$_SESSION = [];
session_destroy();

redirigir('index.php');
