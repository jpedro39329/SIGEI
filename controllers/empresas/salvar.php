<?php
require_once "../../config/init.php";
require_once "../upload.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/empresas/cadastrar.php");
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
$numeroContrato = trim($_POST['numero_contrato'] ?? '');
$rua = trim($_POST['rua'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$ativo = (int) ($_POST['ativo'] ?? 1);
$uresSelecionadas = $_POST['ures'] ?? [];

if ($nome === '' || $cnpj === '') {
    header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Preencha o nome e o CNPJ da empresa."));
    exit();
}

// Upload do arquivo do contrato (opcional)
$contratoArquivo = null;
if (isset($_FILES['contrato_arquivo']) && $_FILES['contrato_arquivo']['error'] === UPLOAD_ERR_OK) {
    $contratoArquivo = uploadArquivo($_FILES['contrato_arquivo'], 'contratos');
    if ($contratoArquivo === false) {
        header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Erro ao enviar o contrato. Verifique o tipo e tamanho do arquivo."));
        exit();
    }
}

// Verifica se o CNPJ já está cadastrado
$stmtVerifica = $conexao->prepare("SELECT id_empresa FROM empresas WHERE cnpj = ?");
$stmtVerifica->bind_param("s", $cnpj);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Já existe uma empresa cadastrada com este CNPJ."));
    exit();
}
$stmtVerifica->close();

$stmt = $conexao->prepare(
    "INSERT INTO empresas (
        nome, cnpj, rua, numero, bairro, cidade, cep,
        numero_contrato, contrato_arquivo, telefone, email, ativo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Erro ao preparar consulta: " . $conexao->error);
}

$stmt->bind_param(
    "sssssssssssi",
    $nome, $cnpj, $rua, $numero, $bairro, $cidade, $cep,
    $numeroContrato, $contratoArquivo, $telefone, $email, $ativo
);

if ($stmt->execute()) {
    $idEmpresa = $stmt->insert_id;

    // Associa as UREs selecionadas
    if (!empty($uresSelecionadas) && is_array($uresSelecionadas)) {
        $stmtUre = $conexao->prepare("INSERT INTO empresa_ure (id_empresa, id_ure) VALUES (?, ?)");
        if ($stmtUre) {
            foreach ($uresSelecionadas as $idUre) {
                $idUreInt = (int) $idUre;
                if ($idUreInt > 0) {
                    $stmtUre->bind_param("ii", $idEmpresa, $idUreInt);
                    $stmtUre->execute();
                }
            }
            $stmtUre->close();
        }
    }

    header("Location: ../../views/empresas/cadastrar.php?msg=ok");
    exit();
}

header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Erro ao cadastrar empresa: " . $stmt->error));
exit();
?>