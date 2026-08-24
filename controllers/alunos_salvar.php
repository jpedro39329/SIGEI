<?php
session_start();
include("../config/database.php");
include("upload.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

// Verifica se o usuário logado é do tipo USUARIO_ESCOLA (apenas eles podem cadastrar alunos)
$userPerfil = $_SESSION['user_perfil'];
if ($userPerfil !== 'USUARIO_ESCOLA') {
    die("Apenas unidades escolares podem cadastrar alunos.");
}

$id_usuario_escola = $_SESSION['user_id'];

// Busca o id_escola do usuário logado
$queryEscola = "SELECT id_escola FROM usuarios_escola WHERE id_usuario_escola = $id_usuario_escola";
$resultEscola = mysqli_query($conexao, $queryEscola);
$rowEscola = mysqli_fetch_assoc($resultEscola);

if (!$rowEscola || empty($rowEscola['id_escola'])) {
    die("Usuário não possui escola associada. Contate o administrador.");
}
$id_escola = $rowEscola['id_escola'];

// Coleta e sanitiza os campos do formulário
$nome = mysqli_real_escape_string($conexao, $_POST['nome']);
$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$ra = mysqli_real_escape_string($conexao, $_POST['ra'] ?? '');
$dataNascimento = mysqli_real_escape_string($conexao, $_POST['data_nascimento']);
$deficiencia = mysqli_real_escape_string($conexao, $_POST['deficiencia']);
$cuidados = mysqli_real_escape_string($conexao, $_POST['observacoes'] ?? '');
$nomeResponsavel = mysqli_real_escape_string($conexao, $_POST['nome_responsavel'] ?? '');
$cpfResponsavel = preg_replace('/\D/', '', $_POST['cpf_responsavel'] ?? '');

$fotoArquivo = '';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fotoArquivo = uploadArquivo($_FILES['foto'], 'fotos');

    if ($fotoArquivo === false) {
        die("Erro ao enviar a foto. Verifique o tipo e tamanho do arquivo.");
    }
}

// Query de inserção com os campos da nova estrutura
$query = "INSERT INTO alunos (
    nome,
    cpf,
    ra,
    data_nascimento,
    descricao_deficiencia,
    descricao_cuidados,
    nome_responsavel,
    cpf_responsavel,
    foto_arquivo,
    status_aprovacao,
    id_escola,
    id_usuario_escola
) VALUES (
    '$nome',
    '$cpf',
    '$ra',
    '$dataNascimento',
    '$deficiencia',
    '$cuidados',
    '$nomeResponsavel',
    '$cpfResponsavel',
    '$fotoArquivo',
    'PENDENTE',
    $id_escola,
    $id_usuario_escola
)";

$result = mysqli_query($conexao, $query);

if ($result) {
    $id_aluno = mysqli_insert_id($conexao);

    if (isset($_FILES['laudos']) && is_array($_FILES['laudos']['name'])) {
        for ($i = 0; $i < count($_FILES['laudos']['name']); $i++) {
            if ($_FILES['laudos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $laudo = array(
                'name' => $_FILES['laudos']['name'][$i],
                'type' => $_FILES['laudos']['type'][$i],
                'tmp_name' => $_FILES['laudos']['tmp_name'][$i],
                'error' => $_FILES['laudos']['error'][$i],
                'size' => $_FILES['laudos']['size'][$i]
            );

            $caminhoLaudo = uploadArquivo($laudo, 'laudos');

            if ($caminhoLaudo === false) {
                die("Aluno cadastrado, mas houve erro ao enviar um laudo. Envie apenas PDF, JPG ou PNG de ate 5MB.");
            }

            $nomeLaudo = mysqli_real_escape_string($conexao, $laudo['name']);
            $caminhoLaudo = mysqli_real_escape_string($conexao, $caminhoLaudo);

            $queryLaudo = "INSERT INTO laudos (id_aluno, nome_arquivo, caminho_arquivo, descricao)
                           VALUES ($id_aluno, '$nomeLaudo', '$caminhoLaudo', 'Laudo enviado pela escola')";
            mysqli_query($conexao, $queryLaudo);
        }
    }

    header("Location: ../views/alunos_listar.php?msg=sucesso");
    exit();
} else {
    echo "Erro ao cadastrar aluno: " . mysqli_error($conexao);
}
?>
