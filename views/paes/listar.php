<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$empresaFiltro = trim($_GET['empresa'] ?? '');

$where = array();

if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $where[] = "p.id_empresa = $idEmpresaUsuario";
} elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC'])) {
    if ($idUreUsuario <= 0) {
        $idUreUsuario = idUreUsuario($conexao, $userId);
    }
    if ($idUreUsuario > 0) {
        $where[] = "p.id_empresa IN (SELECT id_empresa FROM empresa_ure WHERE id_ure = $idUreUsuario)";
    }
}

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    if (!empty($cpfLimpo)) {
        $where[] = "(p.nome LIKE '%$termo%' OR p.cpf LIKE '%$cpfLimpo%' OR p.email LIKE '%$termo%')";
    } else {
        $where[] = "(p.nome LIKE '%$termo%' OR p.email LIKE '%$termo%')";
    }
}

if ($statusFiltro !== '') {
    $stVal = (int) $statusFiltro;
    $where[] = "p.ativo = $stVal";
}

if ($empresaFiltro !== '' && !in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $empBusca = mysqli_real_escape_string($conexao, $empresaFiltro);
    $where[] = "e.nome LIKE '%$empBusca%'";
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT p.id_pae, p.nome, p.cpf, p.email, p.telefone, p.ativo, p.data_cadastro, e.nome AS empresa_nome
    FROM usuarios_pae p
    LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
    $whereSql
    ORDER BY p.data_cadastro DESC
";
$result = mysqli_query($conexao, $sql);
$paes = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profissionais de Apoio Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-paes-listar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Profissionais de Apoio Escolar (PAEs)</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — acompanhe os PAEs cadastrados.</p>
        </div>

        <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'])) { ?>
            <a href="cadastrar.php" class="btn btn-primary">Cadastrar PAE</a>
        <?php } ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'salvo'): ?>
        <div class="alert alert-success">PAE salvo com sucesso.</div>
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
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="dropdownFiltrosPaes" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <span>Filtros <?php echo ($statusFiltro !== '' || $empresaFiltro !== '') ? '<span class="badge bg-primary ms-1">Ativo</span>' : ''; ?></span>
                            </button>
                            <div class="dropdown-menu p-3 shadow-sm" style="min-width: 280px;" aria-labelledby="dropdownFiltrosPaes">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-1">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                        <option value="1" <?php echo $statusFiltro === '1' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="0" <?php echo $statusFiltro === '0' ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                                <?php if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1">Empresa</label>
                                        <input type="text" name="empresa" class="form-control form-control-sm" placeholder="Nome da empresa..." value="<?php echo htmlspecialchars($empresaFiltro); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-dark btn-sm w-100">Aplicar</button>
                                    <a href="listar.php" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
                                </div>
                            </div>
                        </div>
                        <?php if ($busca !== '' || $statusFiltro !== '' || $empresaFiltro !== ''): ?>
                            <a href="listar.php" class="btn btn-outline-secondary btn-sm">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de PAEs -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <?php if ($userPerfil != 'USUARIO_EMPRESA') { ?>
                                <th>Empresa</th>
                            <?php } ?>
                            <th>Status</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($paes) > 0): ?>
                            <?php foreach ($paes as $pae): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($pae['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></td>
                                    <?php if ($userPerfil != 'USUARIO_EMPRESA') { ?>
                                        <td><?php echo htmlspecialchars($pae['empresa_nome'] ?? '-'); ?></td>
                                    <?php } ?>
                                    <td>
                                        <?php if ($pae['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($pae['data_cadastro']) ? date('d/m/Y', strtotime($pae['data_cadastro'])) : '-'; ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="visualizar.php?id=<?php echo $pae['id_pae']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                                                <a href="editar.php?id=<?php echo $pae['id_pae']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $userPerfil == 'USUARIO_EMPRESA' ? '5' : '6'; ?>" class="text-center text-muted">Nenhum PAE encontrado.</td>
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
