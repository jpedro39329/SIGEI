<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

// Filtro de busca
$busca = trim($_GET['busca'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$escolaFiltro = trim($_GET['escola'] ?? '');

$where = [];

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0) {
    $where[] = "ue.id_ure = $idUreUsuario";
}

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    if (!empty($cpfLimpo)) {
        $where[] = "(uue.nome LIKE '%$termo%' OR uue.cpf LIKE '%$cpfLimpo%' OR uue.email LIKE '%$termo%')";
    } else {
        $where[] = "(uue.nome LIKE '%$termo%' OR uue.email LIKE '%$termo%')";
    }
}

if ($statusFiltro !== '') {
    $stVal = (int) $statusFiltro;
    $where[] = "uue.ativo = $stVal";
}

if ($escolaFiltro !== '') {
    $escTermo = mysqli_real_escape_string($conexao, $escolaFiltro);
    $where[] = "ue.nome LIKE '%$escTermo%'";
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlUsuarios = "
    SELECT uue.*, ue.nome AS escola_nome, ue.cie, u.nome AS ure_nome
    FROM usuarios_ue uue
    JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    $whereSql
    ORDER BY uue.data_cadastro DESC
";
$result = mysqli_query($conexao, $sqlUsuarios);
$usuariosEscola = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários das Escolas — SEFISC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-usuarios-ue-listar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Usuários das Escolas</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — listagem e gestão dos usuários das unidades escolares.</p>
        </div>
        <a href="cadastrar.php" class="btn btn-primary">Cadastrar usuário</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Usuário cadastrado com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'editado'): ?>
        <div class="alert alert-success">Dados do usuário atualizados com sucesso!</div>
    <?php endif; ?>

    <!-- Barra de pesquisa com lupa e dropdown de filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="listar.php">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7 col-12">
                        <div class="input-group">
                            <input type="text" name="busca" class="form-control form-control-sm" placeholder="Pesquisar por nome, CPF ou e-mail..." value="<?php echo htmlspecialchars($busca); ?>">
                            <button class="btn btn-dark btn-sm" type="submit" title="Pesquisar">
                                Pesquisar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5 col-12 d-flex gap-2">
                        <div class="dropdown flex-grow-1">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="dropdownFiltros" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <span>Filtros <?php echo ($statusFiltro !== '' || $escolaFiltro !== '') ? '<span class="badge bg-primary ms-1">Ativo</span>' : ''; ?></span>
                            </button>
                            <div class="dropdown-menu p-3 shadow-sm" style="min-width: 280px;" aria-labelledby="dropdownFiltros">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-1">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $statusFiltro === '1' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="0" <?php echo $statusFiltro === '0' ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold mb-1">Escola</label>
                                    <input type="text" name="escola" class="form-control form-control-sm" placeholder="Nome da escola..." value="<?php echo htmlspecialchars($escolaFiltro); ?>">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-dark btn-sm w-100">Aplicar</button>
                                    <a href="listar.php" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
                                </div>
                            </div>
                        </div>
                        <?php if ($busca !== '' || $statusFiltro !== '' || $escolaFiltro !== ''): ?>
                            <a href="listar.php" class="btn btn-outline-secondary btn-sm">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Usuários das Escolas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Escola</th>
                            <th>CIE</th>
                            <th>URE</th>
                            <th>Contato</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuariosEscola) > 0): ?>
                            <?php foreach ($usuariosEscola as $u): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($u['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($u['cpf'])); ?></td>
                                    <td><?php echo htmlspecialchars($u['escola_nome']); ?></td>
                                    <td><code><?php echo htmlspecialchars($u['cie']); ?></code></td>
                                    <td><?php echo htmlspecialchars($u['ure_nome']); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($u['email'] ?? '-'); ?><br><?php echo htmlspecialchars($u['telefone'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($u['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="visualizar.php?id=<?php echo $u['id_usuario_ue']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            <a href="editar.php?id=<?php echo $u['id_usuario_ue']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Nenhum usuário de escola encontrado.</td>
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

