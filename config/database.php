<?php
$host = 'localhost';
$user = 'root';     // Mude de 'fatec' para 'root'
$password = '';     // Deixe vazio entre as aspas
$dbname = 'sigei';

$conexao = new mysqli($host, $user, $password, $dbname);

if ($conexao->connect_error) {
    die("Connection failed: " . $conexao->connect_error);
}
?>
