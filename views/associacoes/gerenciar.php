<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 1 = Contém, 0 = Igual a
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = PAE, 2 = Escola, 3 = Alunos

$whereAssocEmpresa = "";

if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $whereAssocEmpresa = "AND p.id_empresa = $idEmpresaUsuario";
}

// Montagem do filtro dinâmico padrão das listagens
$having = [];
if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Profissional de Apoio (PAE)
        $having[] = $isIgual ? "pae_nome = '$termo'" : "pae_nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // Escola
        $having[] = $isIgual ? "escola_nome = '$termo'" : "escola_nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '3') { // Alunos
        $having[] = $isIgual ? "alunos_nomes = '$termo'" : "alunos_nomes LIKE '%$termo%'";
    } else { // Todos os campos
        if ($isIgual) {
            $having[] = "(pae_nome = '$termo' OR escola_nome = '$termo' OR alunos_nomes = '$termo')";
        } else {
            $having[] = "(pae_nome LIKE '%$termo%' OR escola_nome LIKE '%$termo%' OR alunos_nomes LIKE '%$termo%')";
        }
    }
}

$havingSql = !empty($having) ? 'HAVING ' . implode(' AND ', $having) : '';

// Consulta agrupada por PAE para listar 1 linha por Profissional de Apoio com suas escolas e alunos
$sqlPaesAssociados = "
    SELECT 
        p.id_pae,
        p.nome AS pae_nome,
        p.cpf AS pae_cpf,
        p.telefone AS pae_telefone,
        p.email AS pae_email,
        emp.nome AS empresa_nome,
        COUNT(ass.id_associacao) AS total_alunos,
        GROUP_CONCAT(DISTINCT a.nome ORDER BY a.nome SEPARATOR ', ') AS alunos_nomes,
        GROUP_CONCAT(DISTINCT COALESCE(ue.nome, 'Sem escola') ORDER BY ue.nome SEPARATOR ', ') AS escola_nome
    FROM usuarios_pae p
    JOIN associacoes ass ON p.id_pae = ass.id_pae AND ass.ativo = 1
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    LEFT JOIN empresas emp ON p.id_empresa = emp.id_empresa
    WHERE p.ativo = 1
    $whereAssocEmpresa
    GROUP BY p.id_pae, p.nome, p.cpf, p.telefone, p.email, emp.nome
    $havingSql
    ORDER BY p.nome ASC
";

$resultAssociacoes = mysqli_query($conexao, $sqlPaesAssociados);
$listaPaes = $resultAssociacoes ? mysqli_fetch_all($resultAssociacoes, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associações — SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-associacoes">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Associações</h2>
        </div>

        <a href="cadastrar.php" class="btn btn-primary">Nova Associação</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Associação realizada com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'removido'): ?>
        <div class="alert alert-warning">Associação desativada com sucesso.</div>
    <?php endif; ?>

    <!-- Barra de Filtros Padrão do Sistema -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="gerenciar.php">
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
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Profissional de Apoio Escolar</option>
                            <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>Unidade Escolar</option>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>Alunos</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm flex-grow-1" type="submit" title="Filtrar">
                            Filtrar
                        </button>
                        <?php if ($busca !== '' || $campoFiltro !== '0' || $tipoFiltro !== '1'): ?>
                            <a href="gerenciar.php" class="btn btn-outline-secondary btn-sm" title="Limpar filtros">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Associações por Profissional de Apoio -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Associações Ativas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Profissional de Apoio Escolar</th>
                            <th>Unidade Escolar</th>
                            <th>Alunos atendidos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($listaPaes) > 0): ?>
                            <?php foreach ($listaPaes as $item): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($item['pae_nome']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['escola_nome'] ?: '-'); ?></td>
                                    <td>
                                        <span><?php echo htmlspecialchars($item['alunos_nomes'] ?: '-'); ?></span>
                                    </td>
                                    <td>
                                        <div class="acoes-cell">
                                            <a href="visualizar.php?id=<?php echo $item['id_pae']; ?>" class="btn btn-sm btn-outline-primary" title="Ver Detalhes da Associação">Ver</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nenhuma associação ativa encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>