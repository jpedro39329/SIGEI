<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/ures/listar.php");
    exit();
}

$id_ure = (int) ($_POST['id_ure'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$uge = trim($_POST['uge'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($id_ure <= 0) {
    header("Location: ../../views/ures/listar.php?erro=" . urlencode("URE inválida."));
    exit();
}

if ($nome === '') {
    header("Location: ../../views/ures/editar.php?id=$id_ure&erro=" . urlencode("O nome da URE é obrigatório."));
    exit();
}

if ($uge === '') {
    header("Location: ../../views/ures/editar.php?id=$id_ure&erro=" . urlencode("O código UGE é obrigatório."));
    exit();
}

$stmt = $conexao->prepare(
    "UPDATE unidades_regionais SET nome = ?, uge = ?, endereco = ?, telefone = ?, email = ? WHERE id_ure = ?"
);

if (!$stmt) {
    header("Location: ../../views/ures/editar.php?id=$id_ure&erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param("sssssi", $nome, $uge, $endereco, $telefone, $email, $id_ure);

if ($stmt->execute()) {
    header("Location: ../../views/ures/listar.php?msg=atualizado");
    exit();
}

header("Location: ../../views/ures/editar.php?id=$id_ure&erro=" . urlencode("Erro ao atualizar URE: " . $stmt->error));
exit();

