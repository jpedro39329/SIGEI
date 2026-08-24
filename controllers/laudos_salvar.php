<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");
include("upload.php");

exigirPerfil(array('USUARIO_ESCOLA'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/alunos_listar.php");
    exit();
}

$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola invalidos.");
}

$queryAluno = "SELECT id_aluno FROM alunos WHERE id_aluno = $id_aluno AND id_escola = $id_escola";
$resultAluno = mysqli_query($conexao, $queryAluno);

if (!$resultAluno || mysqli_num_rows($resultAluno) == 0) {
    die("Aluno nao encontrado ou nao pertence a sua escola.");
}

if (!isset($_FILES['laudo']) || $_FILES['laudo']['error'] !== UPLOAD_ERR_OK) {
    die("Envie um arquivo de laudo valido.");
}

$caminhoArquivo = uploadArquivo($_FILES['laudo'], 'laudos');
if ($caminhoArquivo === false) {
    die("Erro ao enviar laudo. Envie um PDF, JPG ou PNG de ate 5MB.");
}

$nomeArquivo = trim($_POST['nome_arquivo'] ?? '');
if ($nomeArquivo === '') {
    $nomeArquivo = $_FILES['laudo']['name'] ?? 'Laudo';
}

$nomeArquivo = mysqli_real_escape_string($conexao, $nomeArquivo);
$descricao = mysqli_real_escape_string($conexao, trim($_POST['descricao'] ?? ''));
$caminhoArquivo = mysqli_real_escape_string($conexao, $caminhoArquivo);

$query = "INSERT INTO laudos (id_aluno, nome_arquivo, caminho_arquivo, descricao)
          VALUES ($id_aluno, '$nomeArquivo', '$caminhoArquivo', '$descricao')";

$result = mysqli_query($conexao, $query);

if ($result) {
    header("Location: ../views/alunos_visualizar.php?id=$id_aluno&msg=laudo_ok");
    exit();
}

echo "Erro ao salvar laudo: " . mysqli_error($conexao);
?>
