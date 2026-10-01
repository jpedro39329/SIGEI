<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'USUARIO_SEFISC', 'SEFISC', 'DIRIGENTE', 'SUPERVISOR', 'USUARIO_EMPRESA'));

$id = (int) ($_GET['id'] ?? 0);
$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $idEmpresaSupervisor = (int) ($_SESSION['id_empresa'] ?? idEmpresaSupervisor($conexao, $userId));
    if ($id <= 0) {
        $id = $idEmpresaSupervisor;
    } elseif ($id !== $idEmpresaSupervisor) {
        die("Acesso negado.");
    }
}

if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Empresa não informada."));
    exit();
}

// 1. Dados da empresa
$stmt = $conexao->prepare("SELECT * FROM empresas WHERE id_empresa = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) {
    header("Location: listar.php?erro=" . urlencode("Empresa não encontrada."));
    exit();
}

// Verifica se existe arquivo de contrato (com fallback para o arquivo padrão gerado)
$contratoArquivo = $empresa['contrato_arquivo'] ?? '';
if (empty($contratoArquivo)) {
    if (file_exists(dirname(__DIR__, 2) . '/uploads/contratos/contrato_plena_servicos_ctr014_2026.pdf')) {
        $contratoArquivo = 'uploads/contratos/contrato_plena_servicos_ctr014_2026.pdf';
        $conexao->query("UPDATE empresas SET contrato_arquivo = '$contratoArquivo' WHERE id_empresa = " . (int)$empresa['id_empresa']);
    }
}

// 2. UREs atendidas por esta empresa
$sqlUres = "
    SELECT u.*
    FROM empresa_ure eu
    JOIN unidades_regionais u ON eu.id_ure = u.id_ure
    WHERE eu.id_empresa = $id
    ORDER BY u.nome ASC
";
$resUres = mysqli_query($conexao, $sqlUres);
$uresAtendidas = $resUres ? mysqli_fetch_all($resUres, MYSQLI_ASSOC) : [];

// 3. Supervisores desta empresa
$sqlSupervisores = "
    SELECT * FROM usuarios_supervisor
    WHERE id_empresa = $id
    ORDER BY ativo DESC, nome ASC
";
$resSupervisores = mysqli_query($conexao, $sqlSupervisores);
$supervisores = $resSupervisores ? mysqli_fetch_all($resSupervisores, MYSQLI_ASSOC) : [];

// 4. PAEs vinculados a esta empresa
$sqlPaes = "
    SELECT * FROM usuarios_pae
    WHERE id_empresa = $id
    ORDER BY ativo DESC, nome ASC
";
$resPaes = mysqli_query($conexao, $sqlPaes);
$paes = $resPaes ? mysqli_fetch_all($resPaes, MYSQLI_ASSOC) : [];

// Indicadores
$totalPaesAtivos = count(array_filter($paes, function($p) { return $p['ativo'] == 1; }));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Empresa - <?php echo htmlspecialchars($empresa['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-empresas-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes da Empresa</h2>
        <div class="d-flex gap-2">
            <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                <a href="../dashboard.php" class="btn btn-secondary btn-sm">Voltar</a>
            <?php else: ?>
                <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Informações Cadastrais e Contrato -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Informações Cadastrais e Contrato</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Razão Social</span>
                            <strong><?php echo htmlspecialchars($empresa['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CNPJ</span>
                            <strong><?php echo htmlspecialchars(formatarCNPJ($empresa['cnpj'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Status</span>
                            <span>
                                <?php if ($empresa['ativo'] == 1): ?>
                                    <span class="badge bg-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativa</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nº Contrato</span>
                            <strong><?php echo htmlspecialchars($empresa['numero_contrato'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Vigência</span>
                            <span>
                                <?php
                                $ini = !empty($empresa['data_inicio_contrato']) ? date('d/m/Y', strtotime($empresa['data_inicio_contrato'])) : '-';
                                $fim = !empty($empresa['data_fim_contrato']) ? date('d/m/Y', strtotime($empresa['data_fim_contrato'])) : '-';
                                echo "$ini até $fim";
                                ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Documento do Contrato</span>
                            <span>
                                <?php if (!empty($contratoArquivo)): ?>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-3 d-flex align-items-center gap-1" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($contratoArquivo, '/')); ?>', 'Contrato - <?php echo htmlspecialchars(addslashes($empresa['numero_contrato'] ?: $empresa['nome'])); ?>')">
                                        <i class="bi bi-file-earmark-pdf-fill"></i> Ver Documento do Contrato
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">Nenhum arquivo anexado</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone</span>
                            <strong><?php echo htmlspecialchars(formatarTelefone($empresa['telefone']) ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">E-mail</span>
                            <strong><?php echo htmlspecialchars($empresa['email'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Endereço Sede</span>
                            <span class="text-end">
                                <?php
                                $endCompleto = array_filter([
                                    $empresa['endereco'] ?? null,
                                    $empresa['numero'] ?? null,
                                    $empresa['bairro'] ?? null,
                                    $empresa['municipio'] ?? null,
                                    !empty($empresa['cep']) ? 'CEP ' . formatarCEP($empresa['cep']) : null
                                ]);
                                echo htmlspecialchars(implode(', ', $endCompleto) ?: 'Não informado');
                                ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- UREs Atendidas e Supervisores -->
        <div class="col-md-6 mb-4">
            <!-- UREs Atendidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">UREs Atendidas pelo Contrato</h5>
                    <div>
                        <?php if (count($uresAtendidas) > 0): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($uresAtendidas as $u): ?>
                                    <li class="py-1">
                                        <?php echo ((!empty($u['uge'])) ? htmlspecialchars($u['uge']) . ' - ' : '') . htmlspecialchars($u['nome']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Nenhuma URE associada a esta empresa.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Supervisores da Empresa -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Supervisores de Licitações e Contratos</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($supervisores) > 0): ?>
                                    <?php foreach ($supervisores as $sup): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sup['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(formatarCPF($sup['cpf'])); ?></td>
                                            <td>
                                                <?php if ($sup['ativo'] == 1): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Nenhum supervisor cadastrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>