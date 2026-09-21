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
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CPF, 3 = E-mail, 4 = Empresa, 5 = Status

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
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "p.nome = '$termo'" : "p.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "p.cpf = '$valCpf'" : "p.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '3') { // E-mail
        $where[] = $isIgual ? "p.email = '$termo'" : "p.email LIKE '%$termo%'";
    } elseif ($campoFiltro === '4' && !in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) { // Empresa
        $where[] = $isIgual ? "e.nome = '$termo'" : "e.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Status
        if (in_array(strtolower($termo), ['ativo', '1'])) {
            $where[] = "p.ativo = 1";
        } elseif (in_array(strtolower($termo), ['inativo', '0'])) {
            $where[] = "p.ativo = 0";
        }
    } else { // 0 = Todos os campos
        if ($isIgual) {
            $conds = ["p.nome = '$termo'", "p.email = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "p.cpf = '$cpfLimpo'";
            if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) $conds[] = "e.nome = '$termo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["p.nome LIKE '%$termo%'", "p.email LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "p.cpf LIKE '%$cpfLimpo%'";
            if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) $conds[] = "e.nome LIKE '%$empBusca%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
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
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
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
                                <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>CPF</option>
                                <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>E-mail</option>
                                <?php if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                                    <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Empresa</option>
                                <?php endif; ?>
                                <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Status</option>
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

</body>
</html>
