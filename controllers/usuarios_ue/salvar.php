<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/usuarios_ue/cadastrar.php");
    exit();
}

$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$idUe = (int) ($_POST['id_ue'] ?? 0);
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || $cpf === '' || $idUe <= 0 || $senha === '') {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

// Se for USUARIO_SEFISC, valida que a escola pertence à URE do usuário logado
if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0) {
    $stmtUe = $conexao->prepare("SELECT id_ue FROM unidades_escolares WHERE id_ue = ? AND id_ure = ? LIMIT 1");
    $stmtUe->bind_param("ii", $idUe, $idUreUsuario);
    $stmtUe->execute();
    if ($stmtUe->get_result()->num_rows === 0) {
        $stmtUe->close();
        redirecionarAcessoNegado();
    }
    $stmtUe->close();
}

if (!validarCPF($cpf, true)) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("CPF inválido. Informe 11 dígitos numéricos válidos."));
    exit();
}

if ($email !== '' && !validarEmail($email, false)) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("Endereço de e-mail inválido."));
    exit();
}

if ($telefone !== '' && !validarTelefone($telefone, false)) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("Telefone inválido. Informe com DDD (10 ou 11 dígitos)."));
    exit();
}

if (strlen($senha) < 6) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("A senha deve ter pelo menos 6 caracteres."));
    exit();
}

if (mb_strlen($senha, 'UTF-8') > 20) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("A senha deve ter no máximo 20 caracteres."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("As senhas não coincidem."));
    exit();
}

// Verifica se o CPF já existe em usuarios_ue
$stmtVerifica = $conexao->prepare("SELECT id_usuario_ue FROM usuarios_ue WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("Já existe um usuário da escola cadastrado com este CPF."));
    exit();
}
$stmtVerifica->close();

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conexao->prepare(
    "INSERT INTO usuarios_ue (id_ue, nome, cpf, senha, email, telefone, ativo)
     VALUES (?, ?, ?, ?, ?, ?, 1)"
);

if (!$stmt) {
    error_log("SIGEI: Erro ao preparar consulta: " . $conexao->error);
    die("Erro ao preparar consulta. Tente novamente ou contate o suporte.");
}

$stmt->bind_param("isssss", $idUe, $nome, $cpf, $senhaHash, $email, $telefone);

if ($stmt->execute()) {
    // Senha criada pelo administrador é provisória: o usuário deverá trocá-la no primeiro acesso
    marcarSenhaProvisoria($conexao, 'usuarios_ue', 'id_usuario_ue', (int) $stmt->insert_id);
    $novoId = $stmt->insert_id;
    registrarAuditoria($conexao, 'USUARIOS', 'CRIAR', 'usuarios_ue', $novoId, [
        'nome' => $nome,
        'cpf' => $cpf,
        'id_ue' => $idUe,
        'email' => $email
    ]);

    header("Location: ../../views/usuarios_ue/listar.php?msg=ok");
    exit();
}

header("Location: ../../views/usuarios_ue/cadastrar.php?erro=" . urlencode("Erro ao cadastrar usuário da escola: " . $stmt->error));
exit();
?>