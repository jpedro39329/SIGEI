<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1');
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CPF, 3 = URE, 4 = E-mail, 5 = Status

$where = ["uu.setor = 'GABINETE'"];

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "uu.nome = '$termo'" : "uu.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "uu.cpf = '$valCpf'" : "uu.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '3') { // URE
        $where[] = $isIgual ? "u.nome = '$termo'" : "u.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '4') { // E-mail
        $where[] = $isIgual ? "uu.email = '$termo'" : "uu.email LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Status
        if (in_array(strtolower($termo), ['ativo', '1'])) {
            $where[] = "uu.ativo = 1";
        } elseif (in_array(strtolower($termo), ['inativo', '0'])) {
            $where[] = "uu.ativo = 0";
        }
    } else { // 0 = Todos
        if ($isIgual) {
            $conds = ["uu.nome = '$termo'", "u.nome = '$termo'", "uu.email = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "uu.cpf = '$cpfLimpo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["uu.nome LIKE '%$termo%'", "u.nome LIKE '%$termo%'", "uu.email LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "uu.cpf LIKE '%$cpfLimpo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

$whereSql = "WHERE " . implode(" AND ", $where);

$sqlDirigentes = "
    SELECT uu.*, u.nome AS ure_nome, u.uge AS ure_uge
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    $whereSql
    ORDER BY uu.nome ASC
";
$result = mysqli_query($conexao, $sqlDirigentes);
$dirigentes = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordenadores Dirigentes Regionais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-dirigentes">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Coordenadores Dirigentes Regionais</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastro e acompanhamento dos dirigentes regionais de ensino.</p>
        </div>
        <a href="cadastrar.php" class="btn btn-primary">Novo Dirigente</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'cadastrado'): ?>
            <div class="alert alert-success">Dirigente Regional cadastrado com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'atualizado'): ?>
            <div class="alert alert-success">Dirigente Regional atualizado com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'excluido'): ?>
            <div class="alert alert-success">Dirigente Regional excluído com sucesso!</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Barra de pesquisa e filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="listar.php">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5 col-sm-12 col-12">
                        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Digite o termo para filtrar..." value="<?php echo htmlspecialchars($busca); ?>">
                    </div>

                    <div class="col-md-2 col-sm-6 col-12">
                        <select class="form-select form-select-sm" name="tp_filtro">
                            <option value="1" <?php echo $tipoFiltro === '1' ? 'selected' : ''; ?>>Contém</option>
                            <option value="0" <?php echo $tipoFiltro === '0' ? 'selected' : ''; ?>>Igual a</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12">
                        <select class="form-select form-select-sm" name="campo_filtro">
                            <option value="0" <?php echo $campoFiltro === '0' ? 'selected' : ''; ?>>Todos os campos...</option>
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Nome</option>
                            <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>CPF</option>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>URE</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>E-mail</option>
                            <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>

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

    <!-- Tabela de Dirigentes -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Dirigentes Regionais Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>URE Vinculada</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($dirigentes) > 0): ?>
                            <?php foreach ($dirigentes as $dir): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($dir['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($dir['cpf'])); ?></td>
                                    <td><?php echo htmlspecialchars($dir['ure_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($dir['email'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($dir['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="visualizar.php?id=<?php echo $dir['id_usuario_ure']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            <a href="editar.php?id=<?php echo $dir['id_usuario_ure']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <a href="../../controllers/dirigentes/excluir.php?id=<?php echo $dir['id_usuario_ure']; ?>&csrf_token=<?php echo gerarTokenCSRF(); ?>"
                                               class="btn btn-sm btn-danger btn-confirmar-exclusao"
                                               data-msg="Tem certeza que deseja excluir este dirigente regional?">Excluir</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum dirigente cadastrado.</td>
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
