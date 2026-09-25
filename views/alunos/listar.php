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

// Para empresa, não deve mostrar alunos que ainda não foram aprovados
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $where[] = "a.status_aprovacao = 'APROVADO'";
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
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
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

    <!-- Barra de pesquisa e filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="listar.php">
                <div class="row g-2 align-items-center">
                    <!-- 1. Campo para digitar -->
                    <div class="col-md-5 col-sm-12 col-12">
                        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Digite o termo para filtrar..." value="<?php echo htmlspecialchars($busca); ?>">
                    </div>

                    <!-- 2. Tipo (Contém / Igual a) -->
                    <div class="col-md-2 col-sm-6 col-12">
                        <div class="form-group" id="tpFiltro">
                            <select class="form-select form-select-sm cbTpFiltros" id="cbTpFiltros" name="tp_filtro">
                                <option value="1" <?php echo $tipoFiltro === '1' ? 'selected' : ''; ?>>Contém</option>
                                <option value="0" <?php echo $tipoFiltro === '0' ? 'selected' : ''; ?>>Igual a</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3. Campo de filtro -->
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

                    <!-- 4. Botões de ação -->
                    <div class="col-md-2 col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm flex-grow-1" type="submit" title="Filtrar">
                            Filtrar
                        </button>
                        <?php if ($busca !== '' || $campoFiltro !== '0' || $tipoFiltro !== '1'): ?>
                            <a href="listar.php" class="btn btn-outline-secondary btn-sm" title="Limpar filtros">Limpar</a>
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

</body>
</html>
