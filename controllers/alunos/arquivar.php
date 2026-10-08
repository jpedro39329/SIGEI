<?php
require_once "../../config/init.php";

// Apenas perfis autorizados (Escola, Educação Especial, SEDUC, ADMIN) podem arquivar
exigirLogin();
exigirTokenCSRF();

$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'ADMIN'])) {
    die("Você não tem permissão para arquivar alunos. Esta ação é restrita à escola responsável.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/alunos/listar.php");
    exit();
}

$idAluno = (int) ($_POST['id_aluno'] ?? 0);
$motivo = trim($_POST['motivo_arquivamento'] ?? '');

if ($idAluno <= 0 || $motivo === '') {
    header("Location: ../../views/alunos/listar.php?erro=" . urlencode("Informe o aluno e o motivo do arquivamento."));
    exit();
}

// Verifica restrição de escola se o perfil for escolar
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    $check = $conexao->prepare("SELECT id_aluno FROM alunos WHERE id_aluno = ? AND id_ue = ?");
    $check->bind_param("ii", $idAluno, $idEscola);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $check->close();
        die("Aluno não pertence à sua unidade escolar.");
    }
    $check->close();
}

// Desativa associações ativas com PAE deste aluno ao arquivar
$stmtAssoc = $conexao->prepare("UPDATE associacoes SET ativo = 0, data_fim = NOW() WHERE id_aluno = ? AND ativo = 1");
if ($stmtAssoc) {
    $stmtAssoc->bind_param("i", $idAluno);
    $stmtAssoc->execute();
    $stmtAssoc->close();
}

$userName = $_SESSION['user_name'] ?? 'Usuário do Sistema';
$userCpf = preg_replace('/\D/', '', $_SESSION['user_cpf'] ?? '');

// Se CPF não estiver na sessão, busca do banco pelo perfil e id
if ($userCpf === '' && $userId > 0) {
    if ($userPerfil === 'ADMIN') {
        $q = $conexao->query("SELECT cpf FROM admin WHERE id_admin = $userId");
    } elseif ($userPerfil === 'SEDUC') {
        $q = $conexao->query("SELECT cpf FROM seduc WHERE id_seduc = $userId");
    } elseif (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
        $q = $conexao->query("SELECT cpf FROM usuarios_ue WHERE id_usuario_ue = $userId");
    } else {
        $q = $conexao->query("SELECT cpf FROM usuarios_ure WHERE id_usuario_ure = $userId");
    }
    if ($q && $r = $q->fetch_assoc()) {
        $userCpf = preg_replace('/\D/', '', $r['cpf'] ?? '');
    }
}

// Atualiza o aluno para ARQUIVADO com motivo, data e identificação de quem inativou
$stmt = $conexao->prepare("
    UPDATE alunos 
    SET status_aprovacao = 'ARQUIVADO', 
        motivo_arquivamento = ?, 
        data_arquivamento = NOW(),
        arquivado_por_nome = ?,
        arquivado_por_cpf = ?,
        arquivado_por_perfil = ?
    WHERE id_aluno = ?
");

if ($stmt) {
    $stmt->bind_param("ssssi", $motivo, $userName, $userCpf, $userPerfil, $idAluno);
    if ($stmt->execute()) {
        $stmt->close();
        registrarAuditoria($conexao, 'ALUNOS', 'ARQUIVAR', 'alunos', $idAluno, [
            'motivo' => $motivo,
            'arquivado_por' => $userName,
            'perfil' => $userPerfil
        ]);
        header("Location: ../../views/alunos/listar.php?aba=arquivados&msg=arquivado");
        exit();
    }
    $stmt->close();
}

header("Location: ../../views/alunos/listar.php?erro=" . urlencode("Erro ao arquivar aluno: " . $conexao->error));
exit();
