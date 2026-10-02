<?php
require_once "../../config/init.php";

exigirLogin();

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/alunos/listar.php");
    exit();
}

if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    die("Token inválido.");
}

// Apenas USUARIO_ESCOLA pode remover laudos
if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'ADMIN', 'SEDUC'])) {
    die("Acesso negado.");
}

$id_laudo = (int) ($_POST['id_laudo'] ?? 0);
$id_aluno = (int) ($_POST['id_aluno'] ?? 0);

if ($id_laudo <= 0 || $id_aluno <= 0) {
    header("Location: ../../views/alunos/visualizar.php?id=$id_aluno&erro=Laudo+inválido");
    exit();
}

// Busca o laudo para verificar se pertence ao aluno e pegar o caminho
$sqlBusca = "SELECT id_laudo, caminho_arquivo, id_aluno FROM laudos WHERE id_laudo = $id_laudo AND id_aluno = $id_aluno";
$resBusca = mysqli_query($conexao, $sqlBusca);
$laudo = mysqli_fetch_assoc($resBusca);

if (!$laudo) {
    header("Location: ../../views/alunos/visualizar.php?id=$id_aluno&erro=Laudo+não+encontrado");
    exit();
}

// Valida que o USUARIO_ESCOLA é da escola do aluno
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    $sqlAluno = "SELECT id_ue FROM alunos WHERE id_aluno = $id_aluno";
    $resAluno = mysqli_query($conexao, $sqlAluno);
    $dadosAluno = mysqli_fetch_assoc($resAluno);
    if (!$dadosAluno || (int)$dadosAluno['id_ue'] !== $idEscola) {
        die("Acesso negado.");
    }
}

// Remove o arquivo físico
$caminhoArquivo = $laudo['caminho_arquivo'];
if (!empty($caminhoArquivo)) {
    $caminhoFisico = dirname(__DIR__, 2) . '/' . ltrim($caminhoArquivo, '/');
    if (file_exists($caminhoFisico)) {
        unlink($caminhoFisico);
    }
}

// Remove do banco
$sqlDelete = "DELETE FROM laudos WHERE id_laudo = $id_laudo AND id_aluno = $id_aluno";
mysqli_query($conexao, $sqlDelete);

header("Location: ../../views/alunos/visualizar.php?id=$id_aluno&msg=laudo_removido");
exit();
