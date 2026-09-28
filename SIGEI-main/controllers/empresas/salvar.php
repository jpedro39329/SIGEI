<?php
require_once "../../config/init.php";
require_once "../upload.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/empresas/listar.php");
    exit();
}

// Garante que a coluna contrato_arquivo exista na tabela empresas
$chk = $conexao->query("SHOW COLUMNS FROM empresas LIKE 'contrato_arquivo'");
if ($chk && $chk->num_rows === 0) {
    $conexao->query("ALTER TABLE empresas ADD COLUMN contrato_arquivo VARCHAR(255) NULL AFTER data_fim_contrato");
}

$nome = trim($_POST['nome'] ?? '');
$cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
$numeroContrato = trim($_POST['numero_contrato'] ?? '');
$dataInicio = !empty($_POST['data_inicio_contrato']) ? $_POST['data_inicio_contrato'] : null;
$dataFim = !empty($_POST['data_fim_contrato']) ? $_POST['data_fim_contrato'] : null;
$endereco = trim($_POST['endereco'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$bairro = trim($_POST['bairro'] ?? '');
$municipio = trim($_POST['municipio'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$ativo = 1; // Cadastro sempre automaticamente ATIVO conforme solicitado
$uresSelecionadas = $_POST['ures'] ?? [];

$contratoArquivo = null;
if (isset($_FILES['contrato_arquivo']) && $_FILES['contrato_arquivo']['error'] === UPLOAD_ERR_OK) {
    $contratoArquivo = uploadArquivo($_FILES['contrato_arquivo'], 'contratos');
    if ($contratoArquivo === false) {
        header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Erro ao enviar contrato. Envie PDF, JPG ou PNG de até 5MB."));
        exit();
    }
}

if ($nome === '' || $cnpj === '') {
    header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Preencha a razão social e o CNPJ da empresa."));
    exit();
}

// Verifica duplicidade de CNPJ
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
        nome, cnpj, endereco, numero, bairro, municipio, cep,
        telefone, email, numero_contrato, data_inicio_contrato, data_fim_contrato, contrato_arquivo, ativo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param(
    "sssssssssssssi",
    $nome, $cnpj, $endereco, $numero, $bairro, $municipio, $cep,
    $telefone, $email, $numeroContrato, $dataInicio, $dataFim, $contratoArquivo, $ativo
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

    header("Location: ../../views/empresas/listar.php?msg=cadastrado");
    exit();
}

header("Location: ../../views/empresas/cadastrar.php?erro=" . urlencode("Erro ao cadastrar empresa: " . $stmt->error));
exit();