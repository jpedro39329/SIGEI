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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-empresas-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Detalhes da Empresa</h2>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($empresa['nome']); ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                <a href="../dashboard.php" class="btn btn-secondary">Voltar</a>
            <?php else: ?>
                <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
                    <a href="editar.php?id=<?php echo $empresa['id_empresa']; ?>" class="btn btn-primary">Editar</a>
                <?php endif; ?>
                <a href="listar.php" class="btn btn-secondary">Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Informações Cadastrais e Contrato -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Informações Cadastrais</h5>
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
                            <span class="text-muted">Telefone</span>
                            <strong><?php echo htmlspecialchars(formatarTelefone($empresa['telefone']) ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">E-mail</span>
                            <strong><?php echo htmlspecialchars($empresa['email'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Endereço</span>
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

            <!-- Contrato -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Contrato</h5>
                    <ul class="list-group list-group-flush">
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
                        <?php if (!empty($contratoArquivo)): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Arquivo do Contrato</span>
                            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                    onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($contratoArquivo, '/')); ?>', 'Contrato - <?php echo htmlspecialchars(addslashes($empresa['numero_contrato'] ?: $empresa['nome'])); ?>')">
                                <i class="bi bi-file-earmark-pdf"></i> Visualizar Contrato
                            </button>
                        </li>
                        <?php else: ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Arquivo do Contrato</span>
                            <span class="text-muted small">Nenhum arquivo anexado</span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- UREs Atendidas e Supervisores -->
        <div class="col-md-6 mb-4">
            <!-- UREs Atendidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Unidades Regionais de Ensino Atendidas</h5>
                    <?php if (count($uresAtendidas) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Cód. UGE</th>
                                        <th>Denominação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uresAtendidas as $u): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['uge'] ?: 'N/D'); ?></td>
                                            <td><?php echo htmlspecialchars($u['nome']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma URE associada a esta empresa.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Supervisores da Empresa -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Supervisores</h5>
                    <?php if (count($supervisores) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>CPF</th>
                                        <th>Nome</th>
                                        <th>E-mail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supervisores as $sup): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(formatarCPF($sup['cpf'])); ?></td>
                                            <td><?php echo htmlspecialchars($sup['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($sup['email'] ?: '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhum supervisor cadastrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>