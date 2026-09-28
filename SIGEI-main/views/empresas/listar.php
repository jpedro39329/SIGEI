<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SEFISC', 'USUARIO_SEFISC', 'DIRIGENTE'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = CNPJ, 3 = Contrato, 4 = Cidade, 5 = Status

$where = array();

// Restringe visualização para a URE da SEFISC / DIRIGENTE quando aplicável
if (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'SEFISC'])) {
    if ($idUreUsuario <= 0) {
        $idUreUsuario = idUreUsuario($conexao, $userId);
    }
    if ($idUreUsuario > 0) {
        $where[] = "(e.id_empresa IN (SELECT id_empresa FROM empresa_ure WHERE id_ure = $idUreUsuario) OR NOT EXISTS (SELECT 1 FROM empresa_ure WHERE id_ure = $idUreUsuario))";
    }
}

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cnpjLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "e.nome = '$termo'" : "e.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // CNPJ
        $valCnpj = !empty($cnpjLimpo) ? $cnpjLimpo : $termo;
        $where[] = $isIgual ? "e.cnpj = '$valCnpj'" : "e.cnpj LIKE '%$valCnpj%'";
    } elseif ($campoFiltro === '3') { // Contrato
        $where[] = $isIgual ? "e.numero_contrato = '$termo'" : "e.numero_contrato LIKE '%$termo%'";
    } elseif ($campoFiltro === '4') { // Cidade
        $where[] = $isIgual ? "e.municipio = '$termo'" : "e.municipio LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Status
        if (in_array(strtolower($termo), ['ativo', '1', 'ativa'])) {
            $where[] = "e.ativo = 1";
        } elseif (in_array(strtolower($termo), ['inativo', '0', 'inativa'])) {
            $where[] = "e.ativo = 0";
        }
    } else { // 0 = Todos
        if ($isIgual) {
            $conds = ["e.nome = '$termo'", "e.numero_contrato = '$termo'", "e.municipio = '$termo'"];
            if (!empty($cnpjLimpo)) $conds[] = "e.cnpj = '$cnpjLimpo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["e.nome LIKE '%$termo%'", "e.numero_contrato LIKE '%$termo%'", "e.municipio LIKE '%$termo%'"];
            if (!empty($cnpjLimpo)) $conds[] = "e.cnpj LIKE '%$cnpjLimpo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlEmpresas = "
    SELECT e.*,
           (SELECT COUNT(*) FROM usuarios_pae p WHERE p.id_empresa = e.id_empresa AND p.ativo = 1) AS total_paes
    FROM empresas e
    $whereSql
    ORDER BY e.ativo DESC, e.nome ASC
";
$result = mysqli_query($conexao, $sqlEmpresas);
$empresas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas Contratadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-empresas">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Empresas Contratadas</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — acompanhe as empresas prestadoras e seus contratos.</p>
        </div>
        <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
            <a href="cadastrar.php" class="btn btn-primary">Nova Empresa</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'cadastrado'): ?>
            <div class="alert alert-success">Empresa cadastrada com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'atualizado'): ?>
            <div class="alert alert-success">Empresa atualizada com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'inativado'): ?>
            <div class="alert alert-warning">Status da empresa alterado com sucesso!</div>
        <?php elseif ($_GET['msg'] === 'excluido'): ?>
            <div class="alert alert-success">Empresa excluída com sucesso!</div>
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
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Razão Social</option>
                            <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>CNPJ</option>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>Nº Contrato</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Cidade</option>
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

    <!-- Tabela de Empresas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Empresas Cadastradas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Razão Social / Nome Fantasia</th>
                            <th>CNPJ</th>
                            <th>Nº Contrato</th>
                            <th>Vigência do Contrato</th>
                            <th>Cidade</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empresas) > 0): ?>
                            <?php foreach ($empresas as $emp): ?>
                                <?php
                                $ini = !empty($emp['data_inicio_contrato']) ? date('d/m/Y', strtotime($emp['data_inicio_contrato'])) : '';
                                $fim = !empty($emp['data_fim_contrato']) ? date('d/m/Y', strtotime($emp['data_fim_contrato'])) : '';
                                $vigencia = ($ini || $fim) ? ($ini ?: 'Início não inf.') . ' até ' . ($fim ?: 'indeterminado') : '-';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($emp['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCNPJ($emp['cnpj'])); ?></td>
                                    <td><?php echo htmlspecialchars($emp['numero_contrato'] ?: '-'); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($vigencia); ?></small></td>
                                    <td><?php echo htmlspecialchars($emp['municipio'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars(formatarTelefone($emp['telefone']) ?: '-'); ?></td>
                                    <td>
                                        <?php if ($emp['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="visualizar.php?id=<?php echo $emp['id_empresa']; ?>" class="btn btn-sm btn-info" title="Ver detalhes da empresa e contrato">Ver</a>
                                            <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
                                                <a href="editar.php?id=<?php echo $emp['id_empresa']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                                <a href="../../controllers/empresas/inativar.php?id=<?php echo $emp['id_empresa']; ?>&csrf_token=<?php echo gerarTokenCSRF(); ?>"
                                                   class="btn btn-sm btn-secondary"
                                                   title="<?php echo $emp['ativo'] == 1 ? 'Inativar' : 'Ativar'; ?>">
                                                    <?php echo $emp['ativo'] == 1 ? 'Inativar' : 'Ativar'; ?>
                                                </a>
                                                <a href="../../controllers/empresas/excluir.php?id=<?php echo $emp['id_empresa']; ?>&csrf_token=<?php echo gerarTokenCSRF(); ?>"
                                                   class="btn btn-sm btn-danger btn-confirmar-exclusao"
                                                   data-msg="Tem certeza que deseja excluir esta empresa?">Excluir</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Nenhuma empresa encontrada.</td>
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
