<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1');
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CPF, 3 = Empresa

$where = array();

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "us.nome = '$termo'" : "us.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "us.cpf = '$valCpf'" : "us.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '3') { // Empresa
        $where[] = $isIgual ? "e.nome = '$termo'" : "e.nome LIKE '%$termo%'";
    } else { // 0 = Todos
        if ($isIgual) {
            $conds = ["us.nome = '$termo'", "e.nome = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "us.cpf = '$cpfLimpo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["us.nome LIKE '%$termo%'", "e.nome LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "us.cpf LIKE '%$cpfLimpo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlSupervisores = "
    SELECT us.id_usuario_supervisor, us.nome, us.cpf, us.ativo,
           e.nome AS empresa_nome
    FROM usuarios_supervisor us
    JOIN empresas e ON us.id_empresa = e.id_empresa
    $whereSql
    ORDER BY us.nome ASC
";
$result = mysqli_query($conexao, $sqlSupervisores);
$supervisores = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisores de Licitações e Contratos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-supervisores">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Supervisores de Licitações e Contratos</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastro e gestão de supervisores das empresas.</p>
        </div>
        <a href="cadastrar.php" class="btn btn-primary">Novo Supervisor</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'cadastrado'): ?>
            <div class="alert alert-success">Supervisor cadastrado com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'atualizado'): ?>
            <div class="alert alert-success">Supervisor atualizado com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'inativado'): ?>
            <div class="alert alert-warning">Status do supervisor alterado com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'excluido'): ?>
            <div class="alert alert-success">Supervisor excluído com sucesso!</div>
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
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>Empresa</option>
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

    <!-- Tabela de Supervisores -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Supervisores Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Empresa</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($supervisores) > 0): ?>
                            <?php foreach ($supervisores as $sup): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sup['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($sup['cpf'])); ?></td>
                                    <td><?php echo htmlspecialchars($sup['empresa_nome']); ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="visualizar.php?id=<?php echo $sup['id_usuario_supervisor']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            <a href="../../controllers/supervisores/excluir.php?id=<?php echo $sup['id_usuario_supervisor']; ?>&csrf_token=<?php echo gerarTokenCSRF(); ?>"
                                               class="btn btn-sm btn-danger btn-confirmar-exclusao"
                                               data-msg="Tem certeza que deseja excluir este supervisor?">Excluir</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nenhum supervisor encontrado.</td>
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
