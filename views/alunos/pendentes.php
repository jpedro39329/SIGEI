<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEscola = idEscolaUsuario($conexao, $userId);

$busca = trim($_GET['busca'] ?? '');
$deficienciaFiltro = trim($_GET['deficiencia'] ?? '');
$abaAtiva = trim($_GET['aba'] ?? 'analise');

$where = array();
$where[] = "id_ue = $idEscola";

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    if (!empty($cpfLimpo)) {
        $where[] = "(nome LIKE '%$termo%' OR ra LIKE '%$termo%' OR cpf LIKE '%$cpfLimpo%')";
    } else {
        $where[] = "(nome LIKE '%$termo%' OR ra LIKE '%$termo%')";
    }
}

if ($deficienciaFiltro !== '') {
    $defBusca = mysqli_real_escape_string($conexao, $deficienciaFiltro);
    $where[] = "descricao_deficiencia LIKE '%$defBusca%'";
}

$whereBase = implode(' AND ', $where);

// Consulta para alunos em análise (PENDENTE)
$sqlPendentes = "
    SELECT id_aluno, nome, cpf, ra, descricao_deficiencia,
           status_aprovacao, motivo_reprovacao, data_cadastro
    FROM alunos
    WHERE $whereBase AND status_aprovacao = 'PENDENTE'
    ORDER BY data_cadastro DESC
";
$resultPendentes = mysqli_query($conexao, $sqlPendentes);
$alunosPendentes = $resultPendentes ? mysqli_fetch_all($resultPendentes, MYSQLI_ASSOC) : [];

// Consulta para alunos com ajustes solicitados (PENDENTE_CORRECAO)
$sqlCorrecao = "
    SELECT id_aluno, nome, cpf, ra, descricao_deficiencia,
           status_aprovacao, motivo_reprovacao, data_cadastro
    FROM alunos
    WHERE $whereBase AND status_aprovacao = 'PENDENTE_CORRECAO'
    ORDER BY data_cadastro DESC
";
$resultCorrecao = mysqli_query($conexao, $sqlCorrecao);
$alunosCorrecao = $resultCorrecao ? mysqli_fetch_all($resultCorrecao, MYSQLI_ASSOC) : [];

// Consulta para alunos reprovados (REPROVADO)
$sqlReprovados = "
    SELECT id_aluno, nome, cpf, ra, descricao_deficiencia,
           status_aprovacao, motivo_reprovacao, data_cadastro
    FROM alunos
    WHERE $whereBase AND status_aprovacao = 'REPROVADO'
    ORDER BY data_cadastro DESC
";
$resultReprovados = mysqli_query($conexao, $sqlReprovados);
$alunosReprovados = $resultReprovados ? mysqli_fetch_all($resultReprovados, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações de Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-alunos-pendentes">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Solicitações</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — acompanhe as solicitações de apoio escolar enviadas pela sua escola.</p>
        </div>
        <a href="cadastrar.php" class="btn btn-primary">Nova solicitação</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'reenviado'): ?>
        <div class="alert alert-success">Solicitação reenviada para análise com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
        <div class="alert alert-success">Solicitação criada com sucesso e enviada para análise!</div>
    <?php endif; ?>

    <!-- Barra de pesquisa com lupa e dropdown de filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="pendentes.php">
                <input type="hidden" name="aba" value="<?php echo htmlspecialchars($abaAtiva); ?>">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7 col-12">
                        <div class="input-group">
                            <input type="text" name="busca" class="form-control form-control-sm" placeholder="Pesquisar solicitação por aluno, RA ou CPF..." value="<?php echo htmlspecialchars($busca); ?>">
                            <button class="btn btn-dark btn-sm" type="submit" title="Pesquisar">
                                Pesquisar
                            </button>
                        </div>
                    </div>

                    <div class="col-md-5 col-12 d-flex gap-2">
                        <div class="dropdown flex-grow-1">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="dropdownFiltrosSol" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <span>Filtros <?php echo ($deficienciaFiltro !== '') ? '<span class="badge bg-primary ms-1">Ativo</span>' : ''; ?></span>
                            </button>
                            <div class="dropdown-menu p-3 shadow-sm" style="min-width: 280px;" aria-labelledby="dropdownFiltrosSol">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">Deficiência</label>
                                    <input type="text" name="deficiencia" class="form-control form-control-sm" placeholder="Ex: Autismo, Visual..." value="<?php echo htmlspecialchars($deficienciaFiltro); ?>">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-dark btn-sm w-100">Aplicar</button>
                                    <a href="pendentes.php?aba=<?php echo htmlspecialchars($abaAtiva); ?>" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
                                </div>
                            </div>
                        </div>
                        <?php if ($busca !== '' || $deficienciaFiltro !== ''): ?>
                            <a href="pendentes.php?aba=<?php echo htmlspecialchars($abaAtiva); ?>" class="btn btn-outline-secondary btn-sm">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Abas de Separação: Em Análise, Ajustes Solicitados e Reprovados -->
    <ul class="nav nav-tabs mb-3" id="solicitacoesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold <?php echo ($abaAtiva === 'analise' || !in_array($abaAtiva, ['correcao', 'reprovados'])) ? 'active' : ''; ?>" id="analise-tab" data-bs-toggle="tab" data-bs-target="#analise" type="button" role="tab" aria-controls="analise" aria-selected="<?php echo ($abaAtiva === 'analise' || !in_array($abaAtiva, ['correcao', 'reprovados'])) ? 'true' : 'false'; ?>">
                Em Análise <span class="badge bg-primary ms-1"><?php echo count($alunosPendentes); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold <?php echo ($abaAtiva === 'correcao') ? 'active' : ''; ?>" id="correcao-tab" data-bs-toggle="tab" data-bs-target="#correcao" type="button" role="tab" aria-controls="correcao" aria-selected="<?php echo ($abaAtiva === 'correcao') ? 'true' : 'false'; ?>">
                Ajustes Solicitados <span class="badge bg-warning text-dark ms-1"><?php echo count($alunosCorrecao); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold <?php echo ($abaAtiva === 'reprovados') ? 'active' : ''; ?>" id="reprovados-tab" data-bs-toggle="tab" data-bs-target="#reprovados" type="button" role="tab" aria-controls="reprovados" aria-selected="<?php echo ($abaAtiva === 'reprovados') ? 'true' : 'false'; ?>">
                Alunos Reprovados <span class="badge bg-danger ms-1"><?php echo count($alunosReprovados); ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="solicitacoesTabsContent">
        <!-- Aba: Em Análise -->
        <div class="tab-pane fade <?php echo ($abaAtiva === 'analise' || !in_array($abaAtiva, ['correcao', 'reprovados'])) ? 'show active' : ''; ?>" id="analise" role="tabpanel" aria-labelledby="analise-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>RA</th>
                                    <th>Deficiência</th>
                                    <th>Status</th>
                                    <th>Data Solicitação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alunosPendentes) > 0): ?>
                                    <?php foreach ($alunosPendentes as $aluno): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                            <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                            <td><span class="badge bg-info text-dark">Em Análise</span></td>
                                            <td><?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y', strtotime($aluno['data_cadastro'])) : '-'; ?></td>
                                            <td>
                                                <a href="visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Nenhuma solicitação em análise no momento.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aba: Ajustes Solicitados -->
        <div class="tab-pane fade <?php echo ($abaAtiva === 'correcao') ? 'show active' : ''; ?>" id="correcao" role="tabpanel" aria-labelledby="correcao-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>RA</th>
                                    <th>Deficiência</th>
                                    <th>Status</th>
                                    <th>Orientação / Ajustes Solicitados</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alunosCorrecao) > 0): ?>
                                    <?php foreach ($alunosCorrecao as $aluno): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                            <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                            <td><span class="badge bg-warning text-dark">Ajuste Solicitado</span></td>
                                            <td><span class="text-warning-emphasis"><?php echo htmlspecialchars($aluno['motivo_reprovacao'] ?? '-'); ?></span></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>
                                                    <a href="editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-warning">Ajustar</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Nenhum aluno com ajustes solicitados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aba: Reprovados -->
        <div class="tab-pane fade <?php echo ($abaAtiva === 'reprovados') ? 'show active' : ''; ?>" id="reprovados" role="tabpanel" aria-labelledby="reprovados-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>RA</th>
                                    <th>Deficiência</th>
                                    <th>Status</th>
                                    <th>Motivo da Reprovação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alunosReprovados) > 0): ?>
                                    <?php foreach ($alunosReprovados as $aluno): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                            <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                            <td><span class="badge bg-danger">Reprovado</span></td>
                                            <td><span class="text-danger"><?php echo htmlspecialchars($aluno['motivo_reprovacao'] ?? '-'); ?></span></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>
                                                    <form action="../../controllers/alunos/reenviar.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                                        <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">Reenviar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Nenhuma solicitação reprovada.</td>
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
