<?php

session_start();
include("../config/database.php");

if (!isset($_POST['cpf']) || !isset($_POST['senha'])) {
    die("Acesso inválido.");
}

$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$password = $_POST['senha'];

$query = "SELECT * FROM usuarios WHERE cpf = '$cpf' AND senha = '$password'";
$result = mysqli_query($conexao, $query);

if (mysqli_num_rows($result) == 1) {

    $user = mysqli_fetch_assoc($result);

    $_SESSION['user_id'] = $user['id_usuario'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['user_perfil'] = $user['perfil'];

    if ($user['perfil'] === 'ADMIN') {
        header("Location: ../views/dashboard_admin.php");
    } else {
        header("Location: ../views/dashboard.php");
    }

    exit();

} else {

    echo "CPF ou senha incorretos.";

}
?>
