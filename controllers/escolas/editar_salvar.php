<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/escolas/listar.php");
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$idUe = (int) ($_POST['id_ue'] ?? 0);
if ($idUe <= 0) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Escola inválida."));
    exit();
}

// Verifica se a escola existe
$stmtCheck = $conexao->prepare("SELECT id_ue, id_ure FROM unidades_escolares WHERE id_ue = ?");
$stmtCheck->bind_param("i", $idUe);
$stmtCheck->execute();
$escolaAtual = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$escolaAtual) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Escola não encontrada."));
    exit();
}

if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0 && (int)$escolaAtual['id_ure'] !== $idUreUsuario) {
    header("Location: ../../views/escolas/listar.php?erro=" . urlencode("Você não tem permissão para editar esta escola."));
    exit();
}

if ($userPerfil === 'DIRIGENTE') {
    $idUre = (int) ($escolaAtual['id_ure']);
} else {
    $idUre = (int) ($_POST['id_ure'] ?? $escolaAtual['id_ure']);
}

$nome = trim($_POST['nome'] ?? '');
$cie = trim($_POST['cie'] ?? '');
$ua = trim($_POST['ua'] ?? '');
$modalidade = $_POST['modalidade'] ?? 'REGULAR';
$endereco = trim($_POST['endereco'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$municipio = trim($_POST['municipio'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$horario = trim($_POST['horario_funcionamento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nome === '' || $cie === '' || $idUre <= 0 || !in_array($modalidade, array('PEI', 'REGULAR'))) {
    header("Location: ../../views/escolas/editar.php?id=$idUe&erro=" . urlencode("Preencha o nome, o CIE, a modalidade e a URE da escola."));
    exit();
}

// Verifica se o CIE já pertence a outra escola
$stmtVerifica = $conexao->prepare("SELECT id_ue FROM unidades_escolares WHERE cie = ? AND id_ue != ?");
$stmtVerifica->bind_param("si", $cie, $idUe);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/escolas/editar.php?id=$idUe&erro=" . urlencode("Já existe outra escola cadastrada com este CIE."));
    exit();
}
$stmtVerifica->close();

$stmt = $conexao->prepare(
    "UPDATE unidades_escolares SET
        nome = ?, cie = ?, ua = ?, endereco = ?, numero = ?, bairro = ?, municipio = ?, cep = ?,
        modalidade = ?, id_ure = ?, horario_funcionamento = ?, telefone = ?, email = ?
    WHERE id_ue = ?"
);

if (!$stmt) {
    header("Location: ../../views/escolas/editar.php?id=$idUe&erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param(
    "sssssssssisssi",
    $nome,
    $cie,
    $ua,
    $endereco,
    $numero,
    $bairro,
    $municipio,
    $cep,
    $modalidade,
    $idUre,
    $horario,
    $telefone,
    $email,
    $idUe
);

if ($stmt->execute()) {
    registrarAuditoria($conexao, 'GESTAO_ESCOLAS', 'ATUALIZAR_ESCOLA', 'unidades_escolares', $idUe, [
        'nome' => $nome,
        'cie' => $cie,
        'id_ure' => $idUre
    ]);
    header("Location: ../../views/escolas/listar.php?msg=atualizado");
} else {
    header("Location: ../../views/escolas/editar.php?id=$idUe&erro=" . urlencode("Erro ao atualizar escola: " . $stmt->error));
}

$stmt->close();
exit();

