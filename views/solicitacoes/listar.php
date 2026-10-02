<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

if ($idUreUsuario <= 0 && $userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CPF, 3 = RA, 4 = Escola, 5 = Deficiência
$abaAtiva = trim($_GET['aba'] ?? 'analise');

$where = array();

// Filtro por URE
if ($idUreUsuario > 0) {
    $where[] = "ue.id_ure = $idUreUsuario";
}

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "a.nome = '$termo'" : "a.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "a.cpf = '$valCpf'" : "a.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '3') { // RA
        $where[] = $isIgual ? "a.ra = '$termo'" : "a.ra LIKE '%$termo%'";
    } elseif ($campoFiltro === '4') { // Escola
        $where[] = $isIgual ? "ue.nome = '$termo'" : "ue.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Deficiência
        $where[] = $isIgual ? "a.descricao_deficiencia = '$termo'" : "a.descricao_deficiencia LIKE '%$termo%'";
    } else { // 0 = Todos
        if ($isIgual) {
            $conds = ["a.nome = '$termo'", "a.ra = '$termo'", "ue.nome = '$termo'", "a.descricao_deficiencia = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "a.cpf = '$cpfLimpo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["a.nome LIKE '%$termo%'", "a.ra LIKE '%$termo%'", "ue.nome LIKE '%$termo%'", "a.descricao_deficiencia LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "a.cpf LIKE '%$cpfLimpo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

$whereBase = !empty($where) ? implode(' AND ', $where) : '1=1';

// Consulta para alunos em análise (PENDENTE)
$sqlPendentes = "
    SELECT a.*, ue.nome AS escola_nome
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE $whereBase AND a.status_aprovacao = 'PENDENTE'
    ORDER BY a.data_cadastro ASC
";
$resultPendentes = mysqli_query($conexao, $sqlPendentes);
$alunosPendentes = $resultPendentes ? mysqli_fetch_all($resultPendentes, MYSQLI_ASSOC) : [];

// Consulta para alunos em correção (PENDENTE_CORRECAO)
$sqlCorrecao = "
    SELECT a.*, ue.nome AS escola_nome
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE $whereBase AND a.status_aprovacao = 'PENDENTE_CORRECAO'
    ORDER BY a.data_cadastro DESC
";
$resultCorrecao = mysqli_query($conexao, $sqlCorrecao);
$alunosCorrecao = $resultCorrecao ? mysqli_fetch_all($resultCorrecao, MYSQLI_ASSOC) : [];

// Consulta para alunos reprovados (REPROVADO)
$sqlReprovados = "
    SELECT a.*, ue.nome AS escola_nome
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE $whereBase AND a.status_aprovacao = 'REPROVADO'
    ORDER BY a.data_cadastro DESC
";
$resultReprovados = mysqli_query($conexao, $sqlReprovados);
$alunosReprovados = $resultReprovados ? mysqli_fetch_all($resultReprovados, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-solicitacoes">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Solicitações</h2>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
        <div class="alert alert-success">Status do aluno atualizado com sucesso!</div>
    <?php endif; ?>

    <!-- Barra de pesquisa e filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="listar.php">
                <input type="hidden" name="aba" value="<?php echo htmlspecialchars($abaAtiva); ?>">
                <div class="row g-2 align-items-center">
                    <!-- 1. Campo para digitar -->
                    <div class="col-md-5 col-sm-12 col-12">
                        <input type="text" name="busca" class="form-control form-control-sm" maxlength="150" placeholder="Digite o termo para filtrar..." value="<?php echo htmlspecialchars($busca); ?>">
                    </div>

                    <!-- 2. Tipo (Contém / Igual a) -->
                    <div class="col-md-2 col-sm-6 col-12">
                        <select class="form-select form-select-sm" name="tp_filtro">
                            <option value="1" <?php echo $tipoFiltro === '1' ? 'selected' : ''; ?>>Contém</option>
                            <option value="0" <?php echo $tipoFiltro === '0' ? 'selected' : ''; ?>>Igual a</option>
                        </select>
                    </div>

                    <!-- 3. Campo de filtro -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <select class="form-select form-select-sm" name="campo_filtro">
                            <option value="0" <?php echo $campoFiltro === '0' ? 'selected' : ''; ?>>Todos os campos...</option>
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Nome</option>
                            <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>CPF</option>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>RA</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Escola</option>
                            <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Deficiência</option>
                        </select>
                    </div>

                    <!-- 4. Botões de ação -->
                    <div class="col-md-2 col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm flex-grow-1" type="submit" title="Filtrar">
                            Filtrar
                        </button>
                        <?php if ($busca !== '' || $campoFiltro !== '0' || $tipoFiltro !== '1'): ?>
                            <a href="listar.php?aba=<?php echo htmlspecialchars($abaAtiva); ?>" class="btn btn-outline-secondary btn-sm" title="Limpar filtros">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Abas de Separação: Em Análise, Em Correção e Recusados -->
    <ul class="nav nav-tabs mb-3" id="solicitacoesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold <?php echo ($abaAtiva === 'analise' || !in_array($abaAtiva, ['correcao', 'reprovados'])) ? 'active' : ''; ?>" id="analise-tab" data-bs-toggle="tab" data-bs-target="#analise" type="button" role="tab" aria-controls="analise" aria-selected="<?php echo ($abaAtiva === 'analise' || !in_array($abaAtiva, ['correcao', 'reprovados'])) ? 'true' : 'false'; ?>">
                Para Análise <span class="badge bg-primary ms-1"><?php echo count($alunosPendentes); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold <?php echo ($abaAtiva === 'correcao') ? 'active' : ''; ?>" id="correcao-tab" data-bs-toggle="tab" data-bs-target="#correcao" type="button" role="tab" aria-controls="correcao" aria-selected="<?php echo ($abaAtiva === 'correcao') ? 'true' : 'false'; ?>">
                Em Correção <span class="badge bg-warning text-dark ms-1"><?php echo count($alunosCorrecao); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold <?php echo ($abaAtiva === 'reprovados') ? 'active' : ''; ?>" id="reprovados-tab" data-bs-toggle="tab" data-bs-target="#reprovados" type="button" role="tab" aria-controls="reprovados" aria-selected="<?php echo ($abaAtiva === 'reprovados') ? 'true' : 'false'; ?>">
                Recusados <span class="badge bg-danger ms-1"><?php echo count($alunosReprovados); ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="solicitacoesTabsContent">
        <!-- Aba: Para Análise -->
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
                                    <th>Escola</th>
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
                                            <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                            <td><span class="badge bg-info text-dark">Pendente</span></td>
                                            <td><?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y', strtotime($aluno['data_cadastro'])) : '-'; ?></td>
                                            <td>
                                                <a href="analisar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-outline-primary" title="Analisar Solicitação">Analisar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Nenhum aluno pendente de análise.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aba: Em Correção -->
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
                                    <th>Escola</th>
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
                                            <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                            <td><span class="badge bg-warning text-dark">Em Correção</span></td>
                                            <td><span class="text-warning-emphasis"><?php echo htmlspecialchars($aluno['motivo_reprovacao'] ?? '-'); ?></span></td>
                                            <td>
                                                <a href="analisar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-outline-primary" title="Ver / Acompanhar Solicitação">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Nenhum aluno em correção no momento.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aba: Recusados -->
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
                                    <th>Escola</th>
                                    <th>Status</th>
                                    <th>Motivo da Recusa</th>
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
                                            <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                            <td><span class="badge bg-danger">Recusado</span></td>
                                            <td><span class="text-danger"><?php echo htmlspecialchars($aluno['motivo_reprovacao'] ?? '-'); ?></span></td>
                                            <td>
                                                <a href="analisar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-outline-primary" title="Ver Solicitação Recusada">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Nenhum aluno recusado.</td>
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