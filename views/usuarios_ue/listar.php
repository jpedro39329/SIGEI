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

// Filtro de busca padrão
$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CPF, 3 = E-mail, 4 = Escola, 5 = Status

$where = [];

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0) {
    $where[] = "ue.id_ure = $idUreUsuario";
}

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "uue.nome = '$termo'" : "uue.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "uue.cpf = '$valCpf'" : "uue.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '3') { // E-mail
        $where[] = $isIgual ? "uue.email = '$termo'" : "uue.email LIKE '%$termo%'";
    } elseif ($campoFiltro === '4') { // Escola
        $where[] = $isIgual ? "ue.nome = '$termo'" : "ue.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Status
        if (in_array(strtolower($termo), ['ativo', '1'])) {
            $where[] = "uue.ativo = 1";
        } elseif (in_array(strtolower($termo), ['inativo', '0'])) {
            $where[] = "uue.ativo = 0";
        }
    } else { // 0 = Todos os campos
        if ($isIgual) {
            $conds = ["uue.nome = '$termo'", "uue.email = '$termo'", "ue.nome = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "uue.cpf = '$cpfLimpo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["uue.nome LIKE '%$termo%'", "uue.email LIKE '%$termo%'", "ue.nome LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "uue.cpf LIKE '%$cpfLimpo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
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
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>E-mail</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Escola</option>
                            <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Status (Ativo/Inativo)</option>
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
                                        <small><?php echo htmlspecialchars($u['email'] ?? '-'); ?><br><?php echo htmlspecialchars(formatarTelefone($u['telefone']) ?: ''); ?></small>
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

