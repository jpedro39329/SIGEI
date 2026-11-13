<?php
$host = 'localhost';
$user = 'admin';     
$password = '123456';     
$dbname = 'wmshpicv_SIGEI';

$conexao = new mysqli($host, $user, $password, $dbname);

if ($conexao->connect_error) {
    error_log("SIGEI: falha de conexão com o banco: " . $conexao->connect_error);
    http_response_code(503);
    die("Serviço temporariamente indisponível. Tente novamente em instantes.");
}
?>
