<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = RA, 3 = CPF, 4 = Escola, 5 = Deficiência, 6 = Status
$abaAtiva = trim($_GET['aba'] ?? 'todos'); // 'todos', 'aprovados', 'pendentes', 'arquivados'

$where = array();

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "a.nome = '$termo'" : "a.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // RA
        $where[] = $isIgual ? "a.ra = '$termo'" : "a.ra LIKE '%$termo%'";
    } elseif ($campoFiltro === '3') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "a.cpf = '$valCpf'" : "a.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '4' && !in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) { // Escola
        $where[] = $isIgual ? "e.nome = '$termo'" : "e.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Deficiência
        $where[] = $isIgual ? "a.descricao_deficiencia = '$termo'" : "a.descricao_deficiencia LIKE '%$termo%'";
    } elseif ($campoFiltro === '6' && !in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) { // Status
        $where[] = $isIgual ? "a.status_aprovacao = '$termo'" : "a.status_aprovacao LIKE '%$termo%'";
    } else { // 0 = Todos os campos
        if ($isIgual) {
            $conds = ["a.nome = '$termo'", "a.ra = '$termo'", "a.descricao_deficiencia = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "a.cpf = '$cpfLimpo'";
            if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) $conds[] = "e.nome = '$termo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["a.nome LIKE '%$termo%'", "a.ra LIKE '%$termo%'", "a.descricao_deficiencia LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "a.cpf LIKE '%$cpfLimpo%'";
            if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) $conds[] = "e.nome LIKE '%$termo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

// Filtros por Perfil e Hierarquia
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    $where[] = "a.id_ue = $idEscola";
} elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'USUARIO_EDUCACAO_ESPECIAL'])) {
    if ($idUreUsuario <= 0) {
        $idUreUsuario = idUreUsuario($conexao, $userId);
    }
    if ($idUreUsuario > 0) {
        $where[] = "e.id_ure = $idUreUsuario";
    }
} elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresaUsuario);
    if (!empty($uresAtendidas)) {
        $uresList = implode(',', $uresAtendidas);
        $where[] = "e.id_ure IN ($uresList)";
    } else {
        $where[] = "1=0"; // Empresa sem UREs vinculadas
    }
    $where[] = "a.status_aprovacao = 'APROVADO'";
} elseif ($userPerfil === 'PAE') {
    $where[] = "a.id_aluno IN (SELECT id_aluno FROM associacoes WHERE id_pae = $userId AND ativo = 1)";
}

$whereBaseSql = (count($where) > 0) ? 'WHERE ' . implode(' AND ', $where) : '';

// Função auxiliar para carregar contagens e dados
$baseSelect = "
    SELECT a.id_aluno, a.nome, a.cpf, a.ra, a.descricao_deficiencia, a.data_nascimento,
           TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) AS idade,
           a.status_aprovacao, a.motivo_arquivamento, a.data_arquivamento, a.arquivado_por_nome, a.arquivado_por_cpf, a.arquivado_por_perfil, a.data_cadastro,
           e.nome AS escola_nome,
           (SELECT p.nome FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_nome
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
";

// Contagens por aba
$sqlCounts = "
    SELECT 
        COUNT(*) AS total_todos,
        SUM(CASE WHEN a.status_aprovacao = 'APROVADO' THEN 1 ELSE 0 END) AS total_aprovados,
        SUM(CASE WHEN a.status_aprovacao IN ('PENDENTE', 'PENDENTE_CORRECAO') THEN 1 ELSE 0 END) AS total_pendentes,
        SUM(CASE WHEN a.status_aprovacao = 'ARQUIVADO' THEN 1 ELSE 0 END) AS total_arquivados
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    $whereBaseSql
";
$resCounts = mysqli_query($conexao, $sqlCounts);
$counts = $resCounts ? mysqli_fetch_assoc($resCounts) : ['total_todos' => 0, 'total_aprovados' => 0, 'total_pendentes' => 0, 'total_arquivados' => 0];

// Cláusula da aba selecionada
$whereAba = $where;
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $whereAba[] = "a.status_aprovacao = 'APROVADO'";
} elseif ($abaAtiva === 'aprovados') {
    $whereAba[] = "a.status_aprovacao = 'APROVADO'";
} elseif ($abaAtiva === 'pendentes') {
    $whereAba[] = "a.status_aprovacao IN ('PENDENTE', 'PENDENTE_CORRECAO')";
} elseif ($abaAtiva === 'arquivados') {
    $whereAba[] = "a.status_aprovacao = 'ARQUIVADO'";
}

$whereAbaSql = (count($whereAba) > 0) ? 'WHERE ' . implode(' AND ', $whereAba) : '';
$sqlFinal = "$baseSelect $whereAbaSql ORDER BY a.data_cadastro DESC";
$result = mysqli_query($conexao, $sqlFinal);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

$podeArquivar = in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'SEFISC', 'ADMIN', 'SEDUC']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos - Educação Especial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-alunos-listar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Alunos da Educação Especial</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — consulta, acompanhamento e histórico de registros dos alunos.</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="../../controllers/alunos/exportar_excel.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" class="btn btn-success d-flex align-items-center gap-2" title="Exportar lista de alunos para Excel">
                <i class="bi bi-file-earmark-excel-fill"></i> Excel
            </a>

            <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
                <a href="cadastrar.php" class="btn btn-primary">Cadastrar aluno</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'sucesso'): ?>
            <div class="alert alert-success">Solicitação enviada com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'arquivado'): ?>
            <div class="alert alert-success">Aluno arquivado com sucesso no sistema. O histórico do registro foi preservado.</div>
        <?php elseif ($_GET['msg'] === 'reativado'): ?>
            <div class="alert alert-success">Aluno reativado com sucesso! O cadastro voltou a constar na lista de ativos.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Barra de pesquisa e filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="listar.php">
                <input type="hidden" name="aba" value="<?php echo htmlspecialchars($abaAtiva); ?>">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5 col-sm-12 col-12">
                        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Digite o termo para filtrar..." value="<?php echo htmlspecialchars($busca); ?>">
                    </div>

                    <div class="col-md-2 col-sm-6 col-12">
                        <div class="form-group" id="tpFiltro">
                            <select class="form-select form-select-sm cbTpFiltros" id="cbTpFiltros" name="tp_filtro">
                                <option value="1" <?php echo $tipoFiltro === '1' ? 'selected' : ''; ?>>Contém</option>
                                <option value="0" <?php echo $tipoFiltro === '0' ? 'selected' : ''; ?>>Igual a</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="form-group">
                            <select class="form-select form-select-sm cbFiltros" id="cbFiltros" name="campo_filtro">
                                <option value="0" <?php echo $campoFiltro === '0' ? 'selected' : ''; ?>>Todos os campos...</option>
                                <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Nome</option>
                                <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>RA</option>
                                <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>CPF</option>
                                <?php if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
                                    <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Escola</option>
                                <?php endif; ?>
                                <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Deficiência</option>
                                <?php if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                                    <option value="6" <?php echo $campoFiltro === '6' ? 'selected' : ''; ?>>Status</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

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

    <!-- Abas de Alunos -->
    <?php if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
    <ul class="nav nav-tabs mb-3" id="alunosTabs">
        <li class="nav-item">
            <a class="nav-link fw-bold <?php echo ($abaAtiva === 'todos') ? 'active' : ''; ?>" href="listar.php?aba=todos<?php echo $busca !== '' ? '&busca=' . urlencode($busca) . '&tp_filtro=' . $tipoFiltro . '&campo_filtro=' . $campoFiltro : ''; ?>">
                Todos <span class="badge bg-secondary ms-1"><?php echo (int) $counts['total_todos']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold <?php echo ($abaAtiva === 'aprovados') ? 'active' : ''; ?>" href="listar.php?aba=aprovados<?php echo $busca !== '' ? '&busca=' . urlencode($busca) . '&tp_filtro=' . $tipoFiltro . '&campo_filtro=' . $campoFiltro : ''; ?>">
                Aprovados <span class="badge bg-success ms-1"><?php echo (int) $counts['total_aprovados']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold <?php echo ($abaAtiva === 'pendentes') ? 'active' : ''; ?>" href="listar.php?aba=pendentes<?php echo $busca !== '' ? '&busca=' . urlencode($busca) . '&tp_filtro=' . $tipoFiltro . '&campo_filtro=' . $campoFiltro : ''; ?>">
                Pendentes de Aprovação <span class="badge bg-warning text-dark ms-1"><?php echo (int) $counts['total_pendentes']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold <?php echo ($abaAtiva === 'arquivados') ? 'active' : ''; ?>" href="listar.php?aba=arquivados<?php echo $busca !== '' ? '&busca=' . urlencode($busca) . '&tp_filtro=' . $tipoFiltro . '&campo_filtro=' . $campoFiltro : ''; ?>">
                Arquivados <span class="badge bg-dark ms-1"><?php echo (int) $counts['total_arquivados']; ?></span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <!-- Tabela de Alunos -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>RA</th>
                            <th>CPF</th>
                            <?php if ($userPerfil !== 'USUARIO_ESCOLA'): ?>
                                <th>Escola</th>
                            <?php endif; ?>
                            <th>Deficiência</th>
                            <?php if ($abaAtiva === 'arquivados'): ?>
                                <th>Data Inativação</th>
                                <th>Inativado Por (CPF)</th>
                                <th>Motivo</th>
                            <?php else: ?>
                                <th>Status</th>
                                <th>Profissional de Apoio Escolar</th>
                            <?php endif; ?>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alunos) > 0): ?>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                    <?php if ($userPerfil !== 'USUARIO_ESCOLA'): ?>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>

                                    <?php if ($abaAtiva === 'arquivados'): ?>
                                        <td>
                                            <?php echo !empty($aluno['data_arquivamento']) ? date('d/m/Y H:i', strtotime($aluno['data_arquivamento'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($aluno['arquivado_por_cpf']) || !empty($aluno['arquivado_por_nome'])): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-light border py-1 px-2 text-dark btn-detalhes-inativacao font-monospace small d-inline-flex align-items-center gap-1"
                                                        data-nome-aluno="<?php echo htmlspecialchars($aluno['nome']); ?>"
                                                        data-resp-nome="<?php echo htmlspecialchars($aluno['arquivado_por_nome'] ?? 'Não informado'); ?>"
                                                        data-resp-cpf="<?php echo htmlspecialchars(!empty($aluno['arquivado_por_cpf']) ? formatarCPF($aluno['arquivado_por_cpf']) : 'Não informado'); ?>"
                                                        data-resp-perfil="<?php echo htmlspecialchars($aluno['arquivado_por_perfil'] ?? '-'); ?>"
                                                        data-data="<?php echo !empty($aluno['data_arquivamento']) ? date('d/m/Y \à\s H:i', strtotime($aluno['data_arquivamento'])) : '-'; ?>"
                                                        data-motivo="<?php echo htmlspecialchars($aluno['motivo_arquivamento'] ?? 'Não informado'); ?>"
                                                        title="Clique para ver os dados de quem inativou">
                                                    <i class="bi bi-person-badge"></i>
                                                    <?php echo !empty($aluno['arquivado_por_cpf']) ? htmlspecialchars(formatarCPF($aluno['arquivado_por_cpf'])) : htmlspecialchars($aluno['arquivado_por_nome']); ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($aluno['motivo_arquivamento'])): ?>
                                                <span class="text-muted small" title="<?php echo htmlspecialchars($aluno['motivo_arquivamento']); ?>">
                                                    <?php echo htmlspecialchars(mb_strimwidth($aluno['motivo_arquivamento'], 0, 40, '...')); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            <?php
                                                if ($aluno['status_aprovacao'] == 'PENDENTE') {
                                                    echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                                } elseif ($aluno['status_aprovacao'] == 'PENDENTE_CORRECAO') {
                                                    echo '<span class="badge bg-warning text-dark">Em Correção</span>';
                                                } elseif ($aluno['status_aprovacao'] == 'APROVADO') {
                                                    echo '<span class="badge bg-success">Aprovado</span>';
                                                } elseif ($aluno['status_aprovacao'] == 'REPROVADO') {
                                                    echo '<span class="badge bg-danger">Reprovado</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Arquivado</span>';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($aluno['pae_nome'] ?? 'Sem Profissional'); ?></td>
                                    <?php endif; ?>

                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>

                                            <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA']) && $aluno['status_aprovacao'] !== 'ARQUIVADO'): ?>
                                                <a href="editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <?php endif; ?>

                                            <?php if ($userPerfil == 'ADMIN' && $aluno['status_aprovacao'] == 'APROVADO'): ?>
                                                <a href="../associacoes/gerenciar.php?aluno=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-primary">Associar Profissional</a>
                                            <?php endif; ?>

                                            <?php if ($podeArquivar && $aluno['status_aprovacao'] === 'ARQUIVADO'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success btn-abrir-reativacao"
                                                        data-id="<?php echo $aluno['id_aluno']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($aluno['nome']); ?>"
                                                        title="Reativar aluno no sistema">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Reativar
                                                </button>
                                            <?php elseif ($podeArquivar && $aluno['status_aprovacao'] !== 'ARQUIVADO'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-abrir-arquivamento"
                                                        data-id="<?php echo $aluno['id_aluno']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($aluno['nome']); ?>"
                                                        title="Arquivar aluno sem excluir histórico">
                                                    Arquivar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($userPerfil === 'USUARIO_ESCOLA') ? ($abaAtiva === 'arquivados' ? '7' : '7') : ($abaAtiva === 'arquivados' ? '8' : '8'); ?>" class="text-center text-muted">Nenhum aluno encontrado nesta aba.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal de Confirmação com Motivo de Arquivamento (Clean) -->
<div class="modal fade" id="modalArquivarAluno" tabindex="-1" aria-labelledby="modalArquivarAlunoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/alunos/arquivar.php" method="POST" id="formArquivarAluno">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="arquivar_id_aluno" value="">
                
                <div class="modal-body p-4 text-center">
                    <!-- Ícone Clean -->
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; border-radius: 50%; background: #fef2f2; color: #dc2626; font-size: 1.4rem;">
                        <i class="bi bi-archive"></i>
                    </div>

                    <h5 class="fw-bold mb-1 text-dark" id="modalArquivarAlunoLabel">Arquivar Aluno</h5>
                    <p class="text-muted small mb-3">
                        Tem certeza que deseja arquivar <strong id="arquivar_nome_aluno" class="text-dark">-</strong>?
                    </p>

                    <div class="text-start mb-2">
                        <label for="motivo_arquivamento" class="form-label small fw-semibold text-muted text-uppercase mb-1">
                            Motivo do arquivamento <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control rounded-3" name="motivo_arquivamento" id="motivo_arquivamento" rows="3" placeholder="Ex.: Transferência de escola, conclusão de etapa ou encerramento..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light px-4 py-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-2 rounded-2 fw-medium" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-danger px-3 py-2 rounded-2 fw-medium shadow-sm">
                        Arquivar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Reativação (Clean) -->
<div class="modal fade" id="modalReativarAluno" tabindex="-1" aria-labelledby="modalReativarAlunoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/alunos/reativar.php" method="POST" id="formReativarAluno">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="reativar_id_aluno" value="">
                
                <div class="modal-body p-4 text-center">
                    <!-- Ícone Clean Sucesso / Reativar -->
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; border-radius: 50%; background: #ecfdf5; color: #059669; font-size: 1.4rem;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>

                    <h5 class="fw-bold mb-1 text-dark" id="modalReativarAlunoLabel">Reativar Aluno</h5>
                    <p class="text-muted small mb-0">
                        Deseja reativar o cadastro de <strong id="reativar_nome_aluno" class="text-dark">-</strong>? O aluno voltará a constar como ativo no sistema.
                    </p>
                </div>

                <div class="modal-footer border-0 bg-light px-4 py-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-2 rounded-2 fw-medium" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-success px-3 py-2 rounded-2 fw-medium shadow-sm">
                        Confirmar Reativação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Detalhes da Inativação (Card Clean) -->
<div class="modal fade" id="modalDetalhesInativacao" tabindex="-1" aria-labelledby="modalDetalhesInativacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-4">
                <!-- Topo com ícone e título clean -->
                <div class="text-center mb-3">
                    <div class="mx-auto mb-2 d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 1.3rem;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark" id="modalDetalhesInativacaoLabel">Registro de Inativação</h5>
                    <p class="text-muted small mb-0" id="detalhe_aluno_nome">-</p>
                </div>

                <!-- Card de informações do responsável e data -->
                <div class="bg-light rounded-3 p-3 mb-3 border">
                    <div class="mb-2 pb-2 border-bottom">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Responsável pela Inativação</span>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <strong class="text-dark" id="detalhe_resp_nome">-</strong>
                            <span class="badge bg-secondary" id="detalhe_resp_perfil">-</span>
                        </div>
                    </div>
                    <div class="mb-2 pb-2 border-bottom">
                        <span class="text-muted small text-uppercase fw-semibold d-block">CPF do Usuário</span>
                        <strong class="font-monospace text-dark" id="detalhe_resp_cpf">-</strong>
                    </div>
                    <div class="mb-2 pb-2 border-bottom">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Data e Hora</span>
                        <strong class="text-dark" id="detalhe_data">-</strong>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold d-block">Motivo Declarado</span>
                        <span class="text-dark small" id="detalhe_motivo" style="white-space: pre-wrap;">-</span>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-2 fw-medium" data-bs-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Modal Arquivar
    const modalArquivarEl = document.getElementById('modalArquivarAluno');
    if (modalArquivarEl) {
        const modalArquivar = new bootstrap.Modal(modalArquivarEl);
        const inputId = document.getElementById('arquivar_id_aluno');
        const spanNome = document.getElementById('arquivar_nome_aluno');
        const txtMotivo = document.getElementById('motivo_arquivamento');

        document.querySelectorAll('.btn-abrir-arquivamento').forEach(btn => {
            btn.addEventListener('click', function () {
                inputId.value = this.getAttribute('data-id');
                spanNome.textContent = this.getAttribute('data-nome');
                if (txtMotivo) txtMotivo.value = '';
                modalArquivar.show();
            });
        });
    }

    // Modal Reativar
    const modalReativarEl = document.getElementById('modalReativarAluno');
    if (modalReativarEl) {
        const modalReativar = new bootstrap.Modal(modalReativarEl);
        const inputReativarId = document.getElementById('reativar_id_aluno');
        const spanReativarNome = document.getElementById('reativar_nome_aluno');

        document.querySelectorAll('.btn-abrir-reativacao').forEach(btn => {
            btn.addEventListener('click', function () {
                inputReativarId.value = this.getAttribute('data-id');
                spanReativarNome.textContent = this.getAttribute('data-nome');
                modalReativar.show();
            });
        });
    }

    // Modal Detalhes da Inativação (Card Clean)
    const modalDetalhesEl = document.getElementById('modalDetalhesInativacao');
    if (modalDetalhesEl) {
        const modalDetalhes = new bootstrap.Modal(modalDetalhesEl);
        const elAluno = document.getElementById('detalhe_aluno_nome');
        const elNome = document.getElementById('detalhe_resp_nome');
        const elCpf = document.getElementById('detalhe_resp_cpf');
        const elPerfil = document.getElementById('detalhe_resp_perfil');
        const elData = document.getElementById('detalhe_data');
        const elMotivo = document.getElementById('detalhe_motivo');

        document.querySelectorAll('.btn-detalhes-inativacao').forEach(btn => {
            btn.addEventListener('click', function () {
                elAluno.textContent = this.getAttribute('data-nome-aluno');
                elNome.textContent = this.getAttribute('data-resp-nome');
                elCpf.textContent = this.getAttribute('data-resp-cpf');
                elPerfil.textContent = this.getAttribute('data-resp-perfil');
                elData.textContent = this.getAttribute('data-data');
                elMotivo.textContent = this.getAttribute('data-motivo');
                modalDetalhes.show();
            });
        });
    }
});
</script>

</body>
</html>
