<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/empresas/listar.php");
    exit();
}

$id_empresa = (int) ($_POST['id_empresa'] ?? 0);
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
$ativo = (int) ($_POST['ativo'] ?? 1);
$uresSelecionadas = $_POST['ures'] ?? [];

if ($id_empresa <= 0) {
    header("Location: ../../views/empresas/listar.php?erro=" . urlencode("Empresa inválida."));
    exit();
}

if ($nome === '' || $cnpj === '') {
    header("Location: ../../views/empresas/editar.php?id=$id_empresa&erro=" . urlencode("Preencha a razão social e o CNPJ da empresa."));
    exit();
}

// Verifica duplicidade de CNPJ em outras empresas
$stmtVerifica = $conexao->prepare("SELECT id_empresa FROM empresas WHERE cnpj = ? AND id_empresa != ?");
$stmtVerifica->bind_param("si", $cnpj, $id_empresa);
$stmtVerifica->execute();
if ($stmtVerifica->get_result()->num_rows > 0) {
    header("Location: ../../views/empresas/editar.php?id=$id_empresa&erro=" . urlencode("Já existe outra empresa cadastrada com este CNPJ."));
    exit();
}
$stmtVerifica->close();

$stmt = $conexao->prepare(
    "UPDATE empresas SET
        nome = ?, cnpj = ?, endereco = ?, numero = ?, bairro = ?, municipio = ?, cep = ?,
        telefone = ?, email = ?, numero_contrato = ?, data_inicio_contrato = ?, data_fim_contrato = ?, ativo = ?
     WHERE id_empresa = ?"
);

if (!$stmt) {
    header("Location: ../../views/empresas/editar.php?id=$id_empresa&erro=" . urlencode("Erro ao preparar consulta: " . $conexao->error));
    exit();
}

$stmt->bind_param(
    "ssssssssssssii",
    $nome, $cnpj, $endereco, $numero, $bairro, $municipio, $cep,
    $telefone, $email, $numeroContrato, $dataInicio, $dataFim, $ativo,
    $id_empresa
);

if ($stmt->execute()) {
    // Atualiza vínculos com UREs
    $conexao->query("DELETE FROM empresa_ure WHERE id_empresa = $id_empresa");

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

    header("Location: ../../views/empresas/listar.php?msg=atualizado");
    exit();
}

header("Location: ../../views/empresas/editar.php?id=$id_empresa&erro=" . urlencode("Erro ao atualizar empresa: " . $stmt->error));
exit();

