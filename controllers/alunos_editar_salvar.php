<?php
require_once "../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/alunos_listar.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola inválidos.");
}

// Verifica se o aluno pertence à escola do usuário
$stmtAluno = $conexao->prepare("SELECT id_aluno FROM alunos WHERE id_aluno = ? AND id_escola = ?");
$stmtAluno->bind_param("ii", $id_aluno, $id_escola);
$stmtAluno->execute();
$resultAluno = $stmtAluno->get_result();

if ($resultAluno->num_rows == 0) {
    die("Aluno não encontrado ou não pertence à sua escola.");
}

$stmtAluno->close();

$nome = trim($_POST['nome'] ?? '');
$deficiencia = trim($_POST['deficiencia'] ?? '');
$cuidados = trim($_POST['observacoes'] ?? '');
$nomeResponsavel = trim($_POST['nome_responsavel'] ?? '');
$cpfResponsavel = preg_replace('/\D/', '', $_POST['cpf_responsavel'] ?? '');

if ($nome === '' || $deficiencia === '') {
    die("Nome e deficiência são obrigatórios.");
}

$query = "UPDATE alunos SET
    nome = ?,
    descricao_deficiencia = ?,
    descricao_cuidados = ?,
    nome_responsavel = ?,
    cpf_responsavel = ?
    WHERE id_aluno = ? AND id_escola = ?";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    die("Erro ao preparar a consulta: " . $conexao->error);
}

$stmt->bind_param(
    "sssssii",
    $nome,
    $deficiencia,
    $cuidados,
    $nomeResponsavel,
    $cpfResponsavel,
    $id_aluno,
    $id_escola
);

if ($stmt->execute()) {
    header("Location: ../views/alunos_visualizar.php?id=$id_aluno&msg=editado");
    exit();
}

echo "Erro ao editar aluno: " . $stmt->error;
?>
