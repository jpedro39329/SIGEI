<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));
exigirTokenCSRF();

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_pae = (int) ($_POST['id_pae'] ?? 0);
$data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

if ($id_aluno <= 0 || $id_pae <= 0) {
    die("Dados inválidos.");
}

// Verifica se o aluno existe e está APROVADO
$stmtAluno = $conexao->prepare(
    "SELECT a.id_aluno, ue.id_ure FROM alunos a
     JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
     WHERE a.id_aluno = ? AND a.status_aprovacao = 'APROVADO'"
);
$stmtAluno->bind_param("i", $id_aluno);
$stmtAluno->execute();
$resAluno = $stmtAluno->get_result();

if ($resAluno->num_rows == 0) {
    die("Aluno não encontrado ou não está aprovado.");
}
$alunoData = $resAluno->fetch_assoc();
$stmtAluno->close();

// Verifica se o PAE existe e está ativo
$stmtPae = $conexao->prepare("SELECT id_pae, id_empresa FROM usuarios_pae WHERE id_pae = ? AND ativo = 1");
$stmtPae->bind_param("i", $id_pae);
$stmtPae->execute();
$resPae = $stmtPae->get_result();

if ($resPae->num_rows == 0) {
    die("PAE não encontrado ou está inativo.");
}
$paeData = $resPae->fetch_assoc();
$stmtPae->close();

// Se for supervisor, valida se o PAE é de sua empresa e se o aluno está em uma URE atendida por ela
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    if ((int) $paeData['id_empresa'] !== $idEmpresa) {
        die("Você só pode associar PAEs da sua empresa.");
    }
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresa);
    if (!in_array((int) $alunoData['id_ure'], $uresAtendidas)) {
        die("Você só pode associar alunos de UREs atendidas pela sua empresa.");
    }
}

// RN01: PAE não pode ter mais de 3 alunos ativos
$totalAlunosPae = contarAlunosPAE($conexao, $id_pae);
if ($totalAlunosPae >= 3) {
    die("Este PAE já possui 3 alunos ativos associados.");
}

// RN02: Aluno que já possui PAE ativo não pode ser associado a outro
$totalPaesAluno = contarPAEsAluno($conexao, $id_aluno);
if ($totalPaesAluno >= 1) {
    die("Este aluno já possui um Profissional de Apoio Escolar (PAE) ativo associado.");
}

// Verifica se o aluno já está associado a este PAE
$stmtExiste = $conexao->prepare(
    "SELECT id_associacao FROM associacoes WHERE id_aluno = ? AND id_pae = ? AND ativo = 1"
);
$stmtExiste->bind_param("ii", $id_aluno, $id_pae);
$stmtExiste->execute();

if ($stmtExiste->get_result()->num_rows > 0) {
    die("Este aluno já está associado a este PAE.");
}

$stmtExiste->close();

// Cria a associação
$stmt = $conexao->prepare(
    "INSERT INTO associacoes (id_aluno, id_pae, ativo, data_inicio) VALUES (?, ?, 1, ?)"
);
$stmt->bind_param("iis", $id_aluno, $id_pae, $data_inicio);

if ($stmt->execute()) {
    header("Location: ../../views/associacoes/gerenciar.php?msg=ok");
    exit();
}

echo "Erro ao associar: " . $stmt->error;
?>