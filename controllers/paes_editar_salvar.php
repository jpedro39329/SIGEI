<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

exigirPerfil(array('USUARIO_EMPRESA'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/paes_listar.php");
    exit();
}

$idPae = (int) ($_POST['id_pae'] ?? 0);
$idUsuarioEmpresa = (int) $_SESSION['user_id'];
$idEmpresa = idEmpresaUsuario($conexao, $idUsuarioEmpresa);

$nome = mysqli_real_escape_string($conexao, trim($_POST['nome'] ?? ''));
$telefone = mysqli_real_escape_string($conexao, trim($_POST['telefone'] ?? ''));
$email = mysqli_real_escape_string($conexao, trim($_POST['email'] ?? ''));
$ativo = (int) ($_POST['ativo'] ?? 1);

if ($idPae <= 0 || $idEmpresa <= 0 || $nome === '') {
    die("Dados invalidos.");
}

$queryVerifica = "SELECT id_pae FROM paes WHERE id_pae = $idPae AND id_empresa = $idEmpresa";
$resultVerifica = mysqli_query($conexao, $queryVerifica);

if (!$resultVerifica || mysqli_num_rows($resultVerifica) == 0) {
    die("PAE nao encontrado ou nao pertence a sua empresa.");
}

$query = "
    UPDATE paes
    SET nome = '$nome',
        telefone = '$telefone',
        email = '$email',
        ativo = $ativo
    WHERE id_pae = $idPae
      AND id_empresa = $idEmpresa
";
$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/paes_visualizar.php?id=$idPae&msg=editado");
    exit();
}

echo "Erro ao editar PAE: " . mysqli_error($conexao);
?>
