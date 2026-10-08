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

if ($userPerfil === 'DIRIGENTE') {
    $idUre = (int) ($_SESSION['id_ure'] ?? 0);
    if ($idUre <= 0) {
        $idUre = idUreUsuario($conexao, $userId);
    }
} else {
    $idUre = (int) ($_POST['id_ure'] ?? 0);
}

$nome = trim($_POST['nome'] ?? '');
$cie = trim($_POST['cie'] ?? '');
$ua = trim($_POST['ua'] ?? '');
$modalidade = $_POST['modalidade'] ?? 'REGULAR';
$endereco = trim($_POST['endereco'] ?? ($_POST['rua'] ?? ''));
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$municipio = trim($_POST['municipio'] ?? ($_POST['cidade'] ?? ''));
$cidade = $municipio;
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nome === '' || $cie === '' || $idUre <= 0 || !in_array($modalidade, array('PEI', 'REGULAR'))) {
    header("Location: ../../views/escolas/cadastrar.php?erro=" . urlencode("Preencha o nome, o CIE, a modalidade e a URE da escola."));
    exit();
}

// Verifica se o CIE já existe
$stmtVerifica = $conexao->prepare("SELECT id_ue FROM unidades_escolares WHERE cie = ?");
$stmtVerifica->bind_param("s", $cie);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/escolas/cadastrar.php?erro=" . urlencode("Já existe uma escola cadastrada com este CIE."));
    exit();
}
$stmtVerifica->close();

$stmt = $conexao->prepare(
    "INSERT INTO unidades_escolares (
        nome, cie, ua, endereco, numero, bairro, municipio, cidade, cep,
        modalidade, id_ure, telefone, email
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: ../../views/escolas/cadastrar.php?erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param(
    "ssssssssssiss",
    $nome, $cie, $ua, $endereco, $numero, $bairro, $municipio, $cidade, $cep,
    $modalidade, $idUre, $telefone, $email
);

if ($stmt->execute()) {
    $idNovaEscola = $stmt->insert_id;
    registrarAuditoria($conexao, 'GESTAO_ESCOLAS', 'CADASTRAR_ESCOLA', 'unidades_escolares', $idNovaEscola, [
        'nome' => $nome,
        'cie' => $cie,
        'id_ure' => $idUre
    ]);
    header("Location: ../../views/escolas/listar.php?msg=cadastrado");
    exit();
}

header("Location: ../../views/escolas/cadastrar.php?erro=" . urlencode("Erro ao cadastrar escola: " . $stmt->error));
exit();