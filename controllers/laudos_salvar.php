<?php
require_once "../config/init.php";
require_once "upload.php";

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

$stmtAluno = $conexao->prepare("SELECT id_aluno FROM alunos WHERE id_aluno = ? AND id_ue = ?");
$stmtAluno->bind_param("ii", $id_aluno, $id_escola);
$stmtAluno->execute();

if ($stmtAluno->get_result()->num_rows == 0) {
    die("Aluno não encontrado ou não pertence à sua escola.");
}

$stmtAluno->close();

if (!isset($_FILES['laudo']) || $_FILES['laudo']['error'] !== UPLOAD_ERR_OK) {
    die("Envie um arquivo de laudo válido.");
}

$caminhoArquivo = uploadArquivo($_FILES['laudo'], 'laudos');
if ($caminhoArquivo === false) {
    die("Erro ao enviar laudo. Envie um PDF, JPG ou PNG de até 5MB.");
}

$nomeArquivo = trim($_POST['nome_arquivo'] ?? '');
if ($nomeArquivo === '') {
    $nomeArquivo = $_FILES['laudo']['name'] ?? 'Laudo';
}

$descricao = trim($_POST['descricao'] ?? '');

$stmt = $conexao->prepare(
    "INSERT INTO laudos (id_aluno, nome_arquivo, caminho_arquivo, descricao)
     VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("isss", $id_aluno, $nomeArquivo, $caminhoArquivo, $descricao);

if ($stmt->execute()) {
    header("Location: ../views/alunos_visualizar.php?id=$id_aluno&msg=laudo_ok");
    exit();
}

echo "Erro ao salvar laudo: " . $stmt->error;
?>
