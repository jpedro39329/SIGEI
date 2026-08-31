<?php
require_once "../config/init.php";

exigirPerfil(array('SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/paes_listar.php");
    exit();
}

$idPae = (int) ($_POST['id_pae'] ?? 0);
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

$nome = trim($_POST['nome'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);

if ($idPae <= 0 || $nome === '') {
    die("Dados inválidos.");
}

if (!in_array($userPerfil, ['ADMIN', 'SEDUC'])) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    $stmtVerifica = $conexao->prepare("SELECT id_pae FROM usuarios_pae WHERE id_pae = ? AND id_empresa = ?");
    $stmtVerifica->bind_param("ii", $idPae, $idEmpresa);
    $stmtVerifica->execute();
    if ($stmtVerifica->get_result()->num_rows == 0) {
        die("PAE não encontrado ou não pertence à sua empresa.");
    }
    $stmtVerifica->close();
}

$stmt = $conexao->prepare(
    "UPDATE usuarios_pae
     SET nome = ?, telefone = ?, email = ?, ativo = ?
     WHERE id_pae = ?"
);
$stmt->bind_param("sssii", $nome, $telefone, $email, $ativo, $idPae);

if ($stmt->execute()) {
    header("Location: ../views/paes_visualizar.php?id=$idPae&msg=editado");
    exit();
}

echo "Erro ao editar PAE: " . $stmt->error;
?>
