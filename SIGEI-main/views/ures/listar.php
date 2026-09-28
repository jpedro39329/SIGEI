<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = UGE, 2 = Nome, 3 = Endereço, 4 = E-mail

$where = array();

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // UGE
        $where[] = $isIgual ? "u.uge = '$termo'" : "u.uge LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // Nome
        $where[] = $isIgual ? "u.nome = '$termo'" : "u.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '3') { // Endereço
        $where[] = $isIgual ? "u.endereco = '$termo'" : "u.endereco LIKE '%$termo%'";
    } elseif ($campoFiltro === '4') { // E-mail
        $where[] = $isIgual ? "u.email = '$termo'" : "u.email LIKE '%$termo%'";
    } else { // 0 = Todos
        if ($isIgual) {
            $where[] = "(u.uge = '$termo' OR u.nome = '$termo' OR u.endereco = '$termo' OR u.email = '$termo')";
        } else {
            $where[] = "(u.uge LIKE '%$termo%' OR u.nome LIKE '%$termo%' OR u.endereco LIKE '%$termo%' OR u.email LIKE '%$termo%')";
        }
    }
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM unidades_escolares ue WHERE ue.id_ure = u.id_ure) AS total_escolas,
           (SELECT COUNT(*) FROM usuarios_ure uu WHERE uu.id_ure = u.id_ure AND uu.ativo = 1) AS total_usuarios
    FROM unidades_regionais u
    $whereSql
    ORDER BY u.id_ure ASC
";
$result = mysqli_query($conexao, $sql);
$ures = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Regionais de Ensino</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-ures">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Unidades Regionais de Ensino</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — acompanhe e gerencie as UREs cadastradas.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'atualizado'): ?>
        <div class="alert alert-success">URE atualizada com sucesso!</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
        <div class="alert alert-success">URE excluída com sucesso!</div>
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
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Código UGE</option>
                            <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>Nome da URE</option>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>Endereço</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>E-mail</option>
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

    <!-- Tabela de UREs -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">UREs Cadastradas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>UGE</th>
                            <th>Denominação da URE</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Escolas</th>
                            <th>Servidores</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ures) > 0): ?>
                            <?php foreach ($ures as $ure): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ure['uge'] ?: 'N/D'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($ure['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ure['endereco'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(formatarTelefone($ure['telefone']) ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($ure['email'] ?? '-'); ?></td>
                                    <td><?php echo $ure['total_escolas']; ?></td>
                                    <td><?php echo $ure['total_usuarios']; ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="visualizar.php?id=<?php echo $ure['id_ure']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            <a href="editar.php?id=<?php echo $ure['id_ure']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <a href="../../controllers/ures/excluir.php?id=<?php echo $ure['id_ure']; ?>&csrf_token=<?php echo gerarTokenCSRF(); ?>"
                                               class="btn btn-sm btn-danger btn-confirmar-exclusao"
                                               data-msg="Tem certeza que deseja excluir esta URE?">Excluir</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Nenhuma URE cadastrada.</td>
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
