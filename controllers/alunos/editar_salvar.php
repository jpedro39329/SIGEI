<?php
require_once "../../config/init.php";
require_once "../upload.php";
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/alunos/listar.php");
    exit();
}
$id_aluno = (int) ($_POST['id_aluno'] ?? 0);
$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_aluno <= 0 || $id_escola <= 0) {
    die("Aluno ou escola inválidos.");
}

// Verifica se o aluno pertence à escola do usuário
$stmtAluno = $conexao->prepare("SELECT id_aluno FROM alunos WHERE id_aluno = ? AND id_ue = ?");
$stmtAluno->bind_param("ii", $id_aluno, $id_escola);
$stmtAluno->execute();
$resultAluno = $stmtAluno->get_result();

if ($resultAluno->num_rows == 0) {
    die("Aluno não encontrado ou não pertence à sua escola.");
}

$stmtAluno->close();

// Coleta e sanitiza os campos do formulário
$nome = trim($_POST['nome'] ?? '');
$ra = trim($_POST['ra'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$genero = trim($_POST['genero'] ?? '');
$raca = trim($_POST['raca'] ?? '');
$municipioNascimento = trim($_POST['municipio_nascimento'] ?? '');
$serie = trim($_POST['serie'] ?? '');
$turnoAula = trim($_POST['turno_aula'] ?? '');
$deficiencia = trim($_POST['deficiencia'] ?? '');
$cuidados = trim($_POST['observacoes'] ?? '');
$nomeResponsavel = trim($_POST['nome_responsavel'] ?? '');
$cpfResponsavel = preg_replace('/\D/', '', $_POST['cpf_responsavel'] ?? '');

if ($nome === '' || $dataNascimento === '' || $deficiencia === '' || $municipioNascimento === '' || $serie === '' || $turnoAula === '') {
    die("Preencha os campos obrigatórios do aluno.");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascimento)) {
    die("Data de nascimento inválida.");
}

// Upload opcional de nova foto
$fotoArquivo = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fotoArquivo = uploadArquivo($_FILES['foto'], 'fotos');

    if ($fotoArquivo === false) {
        die("Erro ao enviar a foto. Verifique o tipo e tamanho do arquivo.");
    }
}

// Upload opcional de novo termo de responsabilidade
$termoArquivo = null;
if (isset($_FILES['termo_responsabilidade']) && $_FILES['termo_responsabilidade']['error'] === UPLOAD_ERR_OK) {
    $termoArquivo = uploadArquivo($_FILES['termo_responsabilidade'], 'documentos');

    if ($termoArquivo === false) {
        die("Erro ao enviar o termo de responsabilidade. Envie um PDF, JPG ou PNG de até 5MB.");
    }
}

$query = "UPDATE alunos SET
    nome = ?,
    ra = ?,
    data_nascimento = ?,
    genero = ?,
    raca = ?,
    municipio_nascimento = ?,
    serie = ?,
    turno_aula = ?,
    descricao_deficiencia = ?,
    descricao_cuidados = ?,
    nome_responsavel = ?,
    cpf_responsavel = ?" .
    ($fotoArquivo !== null ? ", foto_arquivo = ?" : "") .
    ($termoArquivo !== null ? ", termo_responsabilidade_arquivo = ?" : "") .
    " WHERE id_aluno = ? AND id_ue = ?";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    die("Erro ao preparar a consulta: " . $conexao->error);
}

$params = [
    $nome,
    $ra,
    $dataNascimento,
    $genero,
    $raca,
    $municipioNascimento,
    $serie,
    $turnoAula,
    $deficiencia,
    $cuidados,
    $nomeResponsavel,
    $cpfResponsavel
];
$types = "ssssssssssss";

if ($fotoArquivo !== null) {
    $params[] = $fotoArquivo;
    $types .= "s";
}

if ($termoArquivo !== null) {
    $params[] = $termoArquivo;
    $types .= "s";
}

$params[] = $id_aluno;
$params[] = $id_escola;
$types .= "ii";

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    header("Location: ../../views/alunos/visualizar.php?id=$id_aluno&msg=editado");
    exit();
}

?>
