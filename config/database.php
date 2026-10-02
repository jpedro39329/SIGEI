<?php
$host = 'localhost';
$user = 'admin';     
$password = '123456';     
$dbname = 'sigei';

$conexao = new mysqli($host, $user, $password, $dbname);

if ($conexao->connect_error) {
    die("Connection failed: " . $conexao->connect_error);
}
?>
