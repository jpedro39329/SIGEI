<?php
require_once "../../config/init.php";
require_once "../upload.php";

exigirPerfil(array('USUARIO_ESCOLA'));
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

$stmtAluno = $conexao->prepare("SELECT id_aluno FROM alunos WHERE id_aluno = ? AND id_ue = ?");
$stmtAluno->bind_param("ii", $id_aluno, $id_escola);
$stmtAluno->execute();

if ($stmtAluno->get_result()->num_rows == 0) {
    die("Aluno não encontrado ou não pertence à sua escola.");
}

$stmtAluno->close();

$tipo = trim($_POST['tipo'] ?? 'LAUDO');
if (!in_array($tipo, ['LAUDO', 'DOCUMENTO'])) {
    $tipo = 'LAUDO';
}

$pastaDestino = ($tipo === 'DOCUMENTO') ? 'documentos' : 'laudos';
$descricao = trim($_POST['descricao'] ?? '');
$nomeInformado = trim($_POST['nome_arquivo'] ?? '');

$enviados = 0;

// Trata múltiplos arquivos enviados pelo campo 'arquivos[]'
if (isset($_FILES['arquivos']) && is_array($_FILES['arquivos']['name'])) {
    $stmt = $conexao->prepare(
        "INSERT INTO laudos (id_aluno, tipo, nome_arquivo, caminho_arquivo, descricao)
         VALUES (?, ?, ?, ?, ?)"
    );

    for ($i = 0; $i < count($_FILES['arquivos']['name']); $i++) {
        if (empty($_FILES['arquivos']['name'][$i]) || $_FILES['arquivos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $arq = array(
            'name' => $_FILES['arquivos']['name'][$i],
            'type' => $_FILES['arquivos']['type'][$i],
            'tmp_name' => $_FILES['arquivos']['tmp_name'][$i],
            'error' => $_FILES['arquivos']['error'][$i],
            'size' => $_FILES['arquivos']['size'][$i]
        );

        $caminhoArquivo = uploadArquivo($arq, $pastaDestino);
        if ($caminhoArquivo !== false) {
            $nomeFinal = $nomeInformado !== '' && count($_FILES['arquivos']['name']) === 1 ? $nomeInformado : $arq['name'];
            $stmt->bind_param("issss", $id_aluno, $tipo, $nomeFinal, $caminhoArquivo, $descricao);
            $stmt->execute();
            $enviados++;
        }
    }

    if ($stmt) {
        $stmt->close();
    }
}
// Compatibilidade com envio individual por 'laudo'
elseif (isset($_FILES['laudo']) && $_FILES['laudo']['error'] === UPLOAD_ERR_OK) {
    $caminhoArquivo = uploadArquivo($_FILES['laudo'], $pastaDestino);
    if ($caminhoArquivo !== false) {
        $nomeArquivo = $nomeInformado !== '' ? $nomeInformado : ($_FILES['laudo']['name'] ?? 'Arquivo');
        $stmt = $conexao->prepare(
            "INSERT INTO laudos (id_aluno, tipo, nome_arquivo, caminho_arquivo, descricao)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issss", $id_aluno, $tipo, $nomeArquivo, $caminhoArquivo, $descricao);
        $stmt->execute();
        $stmt->close();
        $enviados++;
    }
}

if ($enviados > 0) {
    header("Location: ../../views/alunos/visualizar.php?id=$id_aluno&msg=laudo_ok");
    exit();
}

die("Nenhum arquivo válido foi enviado. Verifique se os arquivos são PDF, JPG ou PNG de até 5MB.");
?>
