<?php
require_once "../../config/init.php";

exigirPerfil(array('PAE', 'SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$alunos = [];
$relatorios = [];

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Aluno, 2 = PAE, 3 = Tipo, 4 = Descrição, 5 = Empresa

$whereFiltros = [];

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Aluno
        $whereFiltros[] = $isIgual ? "a.nome = '$termo'" : "a.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2' && $userPerfil !== 'PAE') { // PAE
        $whereFiltros[] = $isIgual ? "p.nome = '$termo'" : "p.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '3') { // Tipo
        if (strcasecmp($termo, 'diario') === 0 || strcasecmp($termo, 'diário') === 0) {
            $whereFiltros[] = "r.tipo = 'DIARIO'";
        } elseif (strcasecmp($termo, 'mensal') === 0) {
            $whereFiltros[] = "r.tipo = 'MENSAL'";
        } else {
            $whereFiltros[] = $isIgual ? "r.tipo = '$termo'" : "r.tipo LIKE '%$termo%'";
        }
    } elseif ($campoFiltro === '4') { // Descrição
        $whereFiltros[] = $isIgual ? "r.descricao = '$termo'" : "r.descricao LIKE '%$termo%'";
    } elseif ($campoFiltro === '5' && !in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'PAE'])) { // Empresa
        $whereFiltros[] = $isIgual ? "e.nome = '$termo'" : "e.nome LIKE '%$termo%'";
    } else { // 0 = Todos os campos
        if ($isIgual) {
            $conds = ["a.nome = '$termo'", "r.descricao = '$termo'"];
            if ($userPerfil !== 'PAE') $conds[] = "p.nome = '$termo'";
            if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'PAE'])) $conds[] = "e.nome = '$termo'";
            if (strcasecmp($termo, 'diario') === 0 || strcasecmp($termo, 'diário') === 0) {
                $conds[] = "r.tipo = 'DIARIO'";
            } elseif (strcasecmp($termo, 'mensal') === 0) {
                $conds[] = "r.tipo = 'MENSAL'";
            } else {
                $conds[] = "r.tipo = '$termo'";
            }
            $whereFiltros[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["a.nome LIKE '%$termo%'", "r.descricao LIKE '%$termo%'"];
            if ($userPerfil !== 'PAE') $conds[] = "p.nome LIKE '%$termo%'";
            if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'PAE'])) $conds[] = "e.nome LIKE '%$termo%'";
            if (strcasecmp($termo, 'diario') === 0 || strcasecmp($termo, 'diário') === 0) {
                $conds[] = "r.tipo = 'DIARIO'";
            } elseif (strcasecmp($termo, 'mensal') === 0) {
                $conds[] = "r.tipo = 'MENSAL'";
            } else {
                $conds[] = "r.tipo LIKE '%$termo%'";
            }
            $whereFiltros[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

if ($userPerfil === 'PAE') {
    // Alunos associados ao PAE
    $sqlAlunos = "
        SELECT a.id_aluno, a.nome FROM associacoes ass
        JOIN alunos a ON ass.id_aluno = a.id_aluno
        WHERE ass.id_pae = $userId AND ass.ativo = 1
        ORDER BY a.nome
    ";
    $resultAlunos = mysqli_query($conexao, $sqlAlunos);
    $alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];

    $wherePae = ["ass.id_pae = $userId"];
    $whereTotal = array_merge($wherePae, $whereFiltros);
    $whereSql = 'WHERE ' . implode(' AND ', $whereTotal);

    // Relatórios do PAE logado
    $sqlRelatorios = "
        SELECT r.id_relatorio, r.tipo, r.descricao, r.data_cadastro, a.nome AS aluno_nome, p.nome AS pae_nome, e.nome AS empresa_nome
        FROM relatorios r
        JOIN associacoes ass ON r.id_associacao = ass.id_associacao
        JOIN alunos a ON ass.id_aluno = a.id_aluno
        JOIN usuarios_pae p ON ass.id_pae = p.id_pae
        LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
        $whereSql
        ORDER BY r.data_cadastro DESC
    ";
} else {
    $whereEmpresa = [];
    if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
        if ($idEmpresaUsuario <= 0) {
            $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
        }
        $whereEmpresa[] = "p.id_empresa = $idEmpresaUsuario";
    }

    $whereTotal = array_merge($whereEmpresa, $whereFiltros);
    $whereSql = !empty($whereTotal) ? 'WHERE ' . implode(' AND ', $whereTotal) : '';

    $sqlRelatorios = "
        SELECT r.id_relatorio, r.tipo, r.descricao, r.data_cadastro, a.nome AS aluno_nome, p.nome AS pae_nome, e.nome AS empresa_nome
        FROM relatorios r
        JOIN associacoes ass ON r.id_associacao = ass.id_associacao
        JOIN alunos a ON ass.id_aluno = a.id_aluno
        JOIN usuarios_pae p ON ass.id_pae = p.id_pae
        LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
        $whereSql
        ORDER BY r.data_cadastro DESC
    ";
}

$resultRelatorios = mysqli_query($conexao, $sqlRelatorios);
$relatorios = $resultRelatorios ? mysqli_fetch_all($resultRelatorios, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios dos PAEs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-relatorios">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><?php echo ($userPerfil === 'PAE') ? 'Meus Relatórios' : 'Relatórios dos Profissionais de Apoio'; ?></h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — <?php echo ($userPerfil === 'PAE') ? 'registre e acompanhe seus relatórios.' : 'acompanhe os atendimentos realizados pelos PAEs.'; ?></p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Relatório salvo com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de novo relatório (apenas PAE) -->
    <?php if ($userPerfil === 'PAE'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Novo Relatório de Atendimento</h5>
                <form action="../../controllers/relatorios/salvar.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aluno Atendido</label>
                            <select name="id_aluno" class="form-select" required>
                                <option value="">Selecione o aluno...</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id_aluno']; ?>"><?php echo htmlspecialchars($aluno['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Relatório</label>
                            <select name="tipo" class="form-select" required>
                                <option value="DIARIO">Diário (Atividades do Dia)</option>
                                <option value="MENSAL">Mensal (Evolução / Fechamento)</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descrição do Atendimento / Observações</label>
                            <textarea name="descricao" rows="4" class="form-control" placeholder="Descreva as atividades, alimentação, suporte e interação do aluno..." required></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark">Registrar Relatório</button>
                </form>
            </div>
        </div>
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
                        <select class="form-select form-select-sm" name="tp_filtro">
                            <option value="1" <?php echo $tipoFiltro === '1' ? 'selected' : ''; ?>>Contém</option>
                            <option value="0" <?php echo $tipoFiltro === '0' ? 'selected' : ''; ?>>Igual a</option>
                        </select>
                    </div>

                    <!-- 3. Campo de filtro -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <select class="form-select form-select-sm" name="campo_filtro">
                            <option value="0" <?php echo $campoFiltro === '0' ? 'selected' : ''; ?>>Todos os campos...</option>
                            <option value="1" <?php echo $campoFiltro === '1' ? 'selected' : ''; ?>>Aluno</option>
                            <?php if ($userPerfil !== 'PAE'): ?>
                                <option value="2" <?php echo $campoFiltro === '2' ? 'selected' : ''; ?>>PAE</option>
                            <?php endif; ?>
                            <option value="3" <?php echo $campoFiltro === '3' ? 'selected' : ''; ?>>Tipo (Diário/Mensal)</option>
                            <option value="4" <?php echo $campoFiltro === '4' ? 'selected' : ''; ?>>Descrição</option>
                            <?php if (!in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'PAE'])): ?>
                                <option value="5" <?php echo $campoFiltro === '5' ? 'selected' : ''; ?>>Empresa</option>
                            <?php endif; ?>
                        </select>
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

    <!-- Lista de relatórios -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Relatórios Registrados</h5>
                <span class="badge bg-light text-dark border"><?php echo count($relatorios); ?> registro(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <?php if ($userPerfil !== 'PAE'): ?>
                                <th>PAE</th>
                            <?php endif; ?>
                            <th>Aluno</th>
                            <th>Tipo</th>
                            <th>Prévia da Descrição</th>
                            <th>Data de Envio</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($relatorios) > 0): ?>
                            <?php foreach ($relatorios as $relatorio): ?>
                                <tr>
                                    <?php if ($userPerfil !== 'PAE'): ?>
                                        <td><strong><?php echo htmlspecialchars($relatorio['pae_nome'] ?? '-'); ?></strong></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($relatorio['aluno_nome']); ?></td>
                                    <td>
                                        <?php if ($relatorio['tipo'] == 'DIARIO'): ?>
                                            <span class="badge bg-info text-dark">Diário</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Mensal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php
                                                $desc = htmlspecialchars($relatorio['descricao']);
                                                echo (mb_strlen($desc) > 80) ? mb_substr($desc, 0, 80) . '...' : $desc;
                                            ?>
                                        </small>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($relatorio['data_cadastro'])); ?></td>
                                    <td>
                                        <a href="visualizar.php?id=<?php echo $relatorio['id_relatorio']; ?>" class="btn btn-sm btn-info">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo ($userPerfil !== 'PAE') ? '6' : '5'; ?>" class="text-center text-muted">Nenhum relatório encontrado.</td>
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