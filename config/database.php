<?php
$host = 'localhost';
$user = 'root';     
$password = '';     
$dbname = 'wmshpicv_SIGEI';

$conexao = new mysqli($host, $user, $password, $dbname);

if ($conexao->connect_error) {
    die("Connection failed: " . $conexao->connect_error);
}
?>
