<?php
require_once "../config/init.php";
require_once "upload.php";

// Apenas USUARIO_ESCOLA pode cadastrar alunos
exigirPerfil(array('USUARIO_ESCOLA'));
exigirTokenCSRF();

$id_usuario_escola = (int) $_SESSION['user_id'];
$id_escola = idEscolaUsuario($conexao, $id_usuario_escola);

if ($id_escola <= 0) {
    die("Usuário não possui escola associada. Contate o administrador.");
}

// Coleta e sanitiza os campos do formulário
$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
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

if ($nome === '' || $cpf === '' || $dataNascimento === '' || $deficiencia === '') {
    die("Preencha os campos obrigatórios do aluno.");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascimento)) {
    die("Data de nascimento inválida.");
}

// Upload da foto (opcional)
$fotoArquivo = '';
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fotoArquivo = uploadArquivo($_FILES['foto'], 'fotos');

    if ($fotoArquivo === false) {
        die("Erro ao enviar a foto. Verifique o tipo e tamanho do arquivo.");
    }
}

// Upload do termo de responsabilidade (obrigatório)
$termoArquivo = '';
if (isset($_FILES['termo_responsabilidade']) && $_FILES['termo_responsabilidade']['error'] === UPLOAD_ERR_OK) {
    $termoArquivo = uploadArquivo($_FILES['termo_responsabilidade'], 'documentos');

    if ($termoArquivo === false) {
        die("Erro ao enviar o termo de responsabilidade. Envie um PDF, JPG ou PNG de até 5MB.");
    }
}

if ($termoArquivo === '') {
    die("O termo de responsabilidade é obrigatório.");
}

// Verifica se o CPF já está cadastrado
$stmtVerifica = $conexao->prepare("SELECT id_aluno FROM alunos WHERE cpf = ?");
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();

if ($resultVerifica->num_rows > 0) {
    die("Já existe um aluno cadastrado com este CPF.");
}

$stmtVerifica->close();

// Query de inserção com todos os campos da ficha do aluno
$query = "INSERT INTO alunos (
    nome,
    cpf,
    ra,
    data_nascimento,
    genero,
    raca,
    municipio_nascimento,
    serie,
    turno_aula,
    descricao_deficiencia,
    descricao_cuidados,
    nome_responsavel,
    cpf_responsavel,
    foto_arquivo,
    termo_responsabilidade_arquivo,
    status_aprovacao,
    id_ue,
    id_usuario_ue
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDENTE', ?, ?)";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    die("Erro ao preparar a consulta: " . $conexao->error);
}

$stmt->bind_param(
    "sssssssssssssssii",
    $nome,
    $cpf,
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
    $cpfResponsavel,
    $fotoArquivo,
    $termoArquivo,
    $id_escola,
    $id_usuario_escola
);

if ($stmt->execute()) {
    $id_aluno = $stmt->insert_id;

    // Upload dos laudos (múltiplos, opcional)
    if (isset($_FILES['laudos']) && is_array($_FILES['laudos']['name'])) {
        $stmtLaudo = $conexao->prepare(
            "INSERT INTO laudos (id_aluno, nome_arquivo, caminho_arquivo, descricao)
             VALUES (?, ?, ?, 'Laudo enviado pela escola')"
        );

        if ($stmtLaudo) {
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
                    die("Aluno cadastrado, mas houve erro ao enviar um laudo. Envie apenas PDF, JPG ou PNG de até 5MB.");
                }

                $nomeLaudo = $laudo['name'];

                $stmtLaudo->bind_param("iss", $id_aluno, $nomeLaudo, $caminhoLaudo);
                $stmtLaudo->execute();
            }

            $stmtLaudo->close();
        }
    }

    header("Location: ../views/alunos_listar.php?msg=sucesso");
    exit();
}

echo "Erro ao cadastrar aluno: " . $stmt->error;
?>
