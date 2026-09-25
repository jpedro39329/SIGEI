<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "sigei";

$conexao = new mysqli($host, $user, $password, $dbname);

if ($conexao->connect_error) {
    die("Connection failed: " . $conexao->connect_error);
}
?>