<?php
require_once "../config/init.php";

exigirPerfil(array('USUARIO_EMPRESA'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/paes_listar.php");
    exit();
}

$idPae = (int) ($_POST['id_pae'] ?? 0);
$idUsuarioEmpresa = (int) $_SESSION['user_id'];
$idEmpresa = idEmpresaUsuario($conexao, $idUsuarioEmpresa);

$nome = trim($_POST['nome'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);

if ($idPae <= 0 || $idEmpresa <= 0 || $nome === '') {
    die("Dados inválidos.");
}

$stmtVerifica = $conexao->prepare("SELECT id_pae FROM paes WHERE id_pae = ? AND id_empresa = ?");
$stmtVerifica->bind_param("ii", $idPae, $idEmpresa);
$stmtVerifica->execute();

if ($stmtVerifica->get_result()->num_rows == 0) {
    die("PAE não encontrado ou não pertence à sua empresa.");
}

$stmtVerifica->close();

$stmt = $conexao->prepare(
    "UPDATE paes
     SET nome = ?, telefone = ?, email = ?, ativo = ?
     WHERE id_pae = ? AND id_empresa = ?"
);
$stmt->bind_param("sssiii", $nome, $telefone, $email, $ativo, $idPae, $idEmpresa);

if ($stmt->execute()) {
    header("Location: ../views/paes_visualizar.php?id=$idPae&msg=editado");
    exit();
}

echo "Erro ao editar PAE: " . $stmt->error;
?>
