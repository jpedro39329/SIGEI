<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1');
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CIE, 3 = UA, 4 = URE, 5 = Cidade, 6 = Modalidade

$where = array();

// Filtro por regional se for dirigente
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0) {
    $where[] = "ue.id_ure = $idUreUsuario";
}

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "ue.nome = '$termo'" : "ue.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CIE
        $where[] = $isIgual ? "ue.cie = '$termo'" : "ue.cie LIKE '%$termo%'";
    } elseif ($campoFiltro === '3') { // UA
        $where[] = $isIgual ? "ue.ua = '$termo'" : "ue.ua LIKE '%$termo%'";
    } elseif ($campoFiltro === '4' && $userPerfil !== 'DIRIGENTE') { // URE
        $where[] = $isIgual ? "u.nome = '$termo'" : "u.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Cidade
        $where[] = $isIgual ? "ue.municipio = '$termo'" : "ue.municipio LIKE '%$termo%'";
    } elseif ($campoFiltro === '6') { // Modalidade
        $where[] = $isIgual ? "ue.modalidade = '$termo'" : "ue.modalidade LIKE '%$termo%'";
    } else { // 0 = Todos
        if ($isIgual) {
            $conds = ["ue.nome = '$termo'", "ue.cie = '$termo'", "ue.ua = '$termo'", "ue.municipio = '$termo'"];
            if ($userPerfil !== 'DIRIGENTE') $conds[] = "u.nome = '$termo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["ue.nome LIKE '%$termo%'", "ue.cie LIKE '%$termo%'", "ue.ua LIKE '%$termo%'", "ue.municipio LIKE '%$termo%'"];
            if ($userPerfil !== 'DIRIGENTE') $conds[] = "u.nome LIKE '%$termo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT ue.*, u.nome AS ure_nome, u.uge AS ure_uge,
           (SELECT COUNT(*) FROM alunos a WHERE a.id_ue = ue.id_ue) AS total_alunos,
           (SELECT COUNT(*) FROM usuarios_ue uue WHERE uue.id_ue = ue.id_ue AND uue.ativo = 1) AS total_usuarios
    FROM unidades_escolares ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    $whereSql
    ORDER BY ue.nome ASC
";
$result = mysqli_query($conexao, $sql);
$escolas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Escolares</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-escolas">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Unidades Escolares</h2>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'excluido'): ?>
        <div class="alert alert-success">Escola excluída com sucesso!</div>
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
                        <input type="text" name="busca" class="form-control form-control-sm" maxlength="150" placeholder="Digite o termo para filtrar..." value="<?php echo htmlspecialchars($busca); ?>">
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
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Denominação</option>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>UA</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Cód. UGE</option>
                            <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Cidade</option>
                            <option value="6" <?php echo $campoFiltro === '6' ? 'selected' : ''; ?>>Modalidade</option>
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

    <!-- Tabela de Escolas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Unidades Escolares da Rede</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Denominação</th>
                            <th>UA</th>
                            <th>Modalidade</th>
                            <th>Cód. UGE</th>
                            <th>Cidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($escolas) > 0): ?>
                            <?php foreach ($escolas as $esc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($esc['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($esc['ua'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($esc['modalidade'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($esc['ure_uge'] ?: 'N/D'); ?></td>
                                    <td><?php echo htmlspecialchars($esc['municipio'] ?: '-'); ?></td>
                                    <td>
                                        <div class="acoes-cell">
                                            <a href="../../controllers/escolas/excluir.php?id=<?php echo $esc['id_ue']; ?>&csrf_token=<?php echo gerarTokenCSRF(); ?>"
                                               class="btn btn-sm btn-outline-danger btn-confirmar-exclusao"
                                               title="Excluir Escola"
                                               data-msg="Tem certeza que deseja excluir esta escola?">Excluir</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhuma escola encontrada.</td>
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
