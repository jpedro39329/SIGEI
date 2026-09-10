<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$escolaFiltro = trim($_GET['escola'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$deficienciaFiltro = trim($_GET['deficiencia'] ?? '');

$where = array();

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    if (!empty($cpfLimpo)) {
        $where[] = "(a.nome LIKE '%$termo%' OR a.ra LIKE '%$termo%' OR a.cpf LIKE '%$cpfLimpo%')";
    } else {
        $where[] = "(a.nome LIKE '%$termo%' OR a.ra LIKE '%$termo%')";
    }
}

if ($escolaFiltro !== '' && !in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $escolaBusca = mysqli_real_escape_string($conexao, $escolaFiltro);
    $where[] = "e.nome LIKE '%$escolaBusca%'";
}

if ($deficienciaFiltro !== '') {
    $defBusca = mysqli_real_escape_string($conexao, $deficienciaFiltro);
    $where[] = "a.descricao_deficiencia LIKE '%$defBusca%'";
}

// Para empresa, não deve mostrar alunos que ainda não foram aprovados
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $where[] = "a.status_aprovacao = 'APROVADO'";
} elseif ($statusFiltro !== '') {
    $statusBusca = mysqli_real_escape_string($conexao, $statusFiltro);
    $where[] = "a.status_aprovacao = '$statusBusca'";
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
} elseif ($userPerfil === 'PAE') {
    $where[] = "a.id_aluno IN (SELECT id_aluno FROM associacoes WHERE id_pae = $userId AND ativo = 1)";
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
    SELECT a.id_aluno, a.nome, a.cpf, a.ra, a.descricao_deficiencia, a.data_nascimento,
           TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) AS idade,
           a.status_aprovacao, a.data_cadastro,
           e.nome AS escola_nome,
           (SELECT p.nome FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_nome
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    $whereSql
    ORDER BY a.data_cadastro DESC
";

$result = mysqli_query($conexao, $sql);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-alunos-listar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Alunos</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — consulta e acompanhamento de alunos.</p>
        </div>

        <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
            <a href="cadastrar.php" class="btn btn-primary">Cadastrar aluno</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
        <div class="alert alert-success">Solicitação enviada com sucesso!</div>
    <?php endif; ?>

    <!-- Barra de pesquisa com lupa e dropdown de filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="listar.php">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7 col-12">
                        <div class="input-group">
                            <input type="text" name="busca" class="form-control form-control-sm" placeholder="Pesquisar por nome, RA ou CPF..." value="<?php echo htmlspecialchars($busca); ?>">
                            <button class="btn btn-dark btn-sm" type="submit" title="Pesquisar">
                                Pesquisar
                            </button>
                        </div>
                    </div>

                    <div class="col-md-5 col-12 d-flex gap-2">
                        <div class="dropdown flex-grow-1">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="dropdownFiltrosAlunos" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <span>Filtros <?php echo ($statusFiltro !== '' || $escolaFiltro !== '' || $deficienciaFiltro !== '') ? '<span class="badge bg-primary ms-1">Ativo</span>' : ''; ?></span>
                            </button>
                            <div class="dropdown-menu p-3 shadow-sm" style="min-width: 290px;" aria-labelledby="dropdownFiltrosAlunos">
                                <?php if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold mb-1">Escola</label>
                                        <input type="text" name="escola" class="form-control form-control-sm" placeholder="Nome da escola..." value="<?php echo htmlspecialchars($escolaFiltro); ?>">
                                    </div>
                                <?php endif; ?>

                                <?php if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold mb-1">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="">Todos</option>
                                            <option value="PENDENTE" <?php echo $statusFiltro == 'PENDENTE' ? 'selected' : ''; ?>>Pendente</option>
                                            <option value="APROVADO" <?php echo $statusFiltro == 'APROVADO' ? 'selected' : ''; ?>>Aprovado</option>
                                            <option value="REPROVADO" <?php echo $statusFiltro == 'REPROVADO' ? 'selected' : ''; ?>>Reprovado</option>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">Deficiência</label>
                                    <input type="text" name="deficiencia" class="form-control form-control-sm" placeholder="Ex: Autismo, Física..." value="<?php echo htmlspecialchars($deficienciaFiltro); ?>">
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-dark btn-sm w-100">Aplicar</button>
                                    <a href="listar.php" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
                                </div>
                            </div>
                        </div>
                        <?php if ($busca !== '' || $statusFiltro !== '' || $escolaFiltro !== '' || $deficienciaFiltro !== ''): ?>
                            <a href="listar.php" class="btn btn-outline-secondary btn-sm">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

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
                            <th>Status</th>
                            <th>PAE</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alunos) > 0): ?>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                    <?php if ($userPerfil !== 'USUARIO_ESCOLA'): ?>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                    <td>
                                        <?php
                                            if ($aluno['status_aprovacao'] == 'PENDENTE') {
                                                echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                            } elseif ($aluno['status_aprovacao'] == 'APROVADO') {
                                                echo '<span class="badge bg-success">Aprovado</span>';
                                            } elseif ($aluno['status_aprovacao'] == 'REPROVADO') {
                                                echo '<span class="badge bg-danger">Reprovado</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Arquivado</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($aluno['pae_nome'] ?? 'Sem PAE'); ?></td>
                                    <td>
                                        <a href="visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>

                                        <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
                                            <a href="editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <?php endif; ?>

                                        <?php if ($userPerfil == 'ADMIN' && $aluno['status_aprovacao'] == 'APROVADO'): ?>
                                            <a href="../associacoes/gerenciar.php?aluno=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-primary">Associar PAE</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $userPerfil === 'USUARIO_ESCOLA' ? '7' : '8'; ?>" class="text-center text-muted">Nenhum aluno encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
