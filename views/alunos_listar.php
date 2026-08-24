<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

if (!in_array($userPerfil, ['ADMIN', 'USUARIO_ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC'])) {
    die("Acesso negado.");
}

$nomeFiltro = trim($_GET['nome'] ?? '');
$raFiltro = trim($_GET['ra'] ?? '');
$cpfFiltro = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
$escolaFiltro = trim($_GET['escola'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');

$where = array();

if ($nomeFiltro !== '') {
    $nomeBusca = mysqli_real_escape_string($conexao, $nomeFiltro);
    $where[] = "a.nome LIKE '%$nomeBusca%'";
}

if ($raFiltro !== '') {
    $raBusca = mysqli_real_escape_string($conexao, $raFiltro);
    $where[] = "a.ra LIKE '%$raBusca%'";
}

if ($cpfFiltro !== '') {
    $cpfBusca = mysqli_real_escape_string($conexao, $cpfFiltro);
    $where[] = "a.cpf LIKE '%$cpfBusca%'";
}

if ($escolaFiltro !== '' && $userPerfil !== 'USUARIO_ESCOLA') {
    $escolaBusca = mysqli_real_escape_string($conexao, $escolaFiltro);
    $where[] = "e.nome LIKE '%$escolaBusca%'";
}

if ($statusFiltro !== '') {
    $statusBusca = mysqli_real_escape_string($conexao, $statusFiltro);
    $where[] = "a.status_aprovacao = '$statusBusca'";
}

if ($userPerfil == 'USUARIO_ESCOLA') {
    $idEscola = idEscolaUsuario($conexao, $userId);
    $where[] = "a.id_escola = $idEscola";
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
    SELECT a.id_aluno, a.nome, a.cpf, a.ra, a.descricao_deficiencia,
           a.status_aprovacao, a.data_cadastro,
           e.nome AS escola_nome,
           (SELECT p.nome FROM associacoes ass
            JOIN paes p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_nome
    FROM alunos a
    LEFT JOIN escolas e ON a.id_escola = e.id_escola
    $whereSql
    ORDER BY a.data_cadastro DESC
";

$result = mysqli_query($conexao, $sql);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-alunos-listar">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Alunos</h2>
            <p class="text-muted">Ola, <?php echo htmlspecialchars($userName); ?> - lista de alunos cadastrados.</p>
        </div>

        <?php if ($userPerfil == 'USUARIO_ESCOLA'): ?>
            <a href="alunos_cadastrar.php" class="btn btn-primary">Cadastrar aluno</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
        <div class="alert alert-success">Solicitacao enviada com sucesso!</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($nomeFiltro); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">RA</label>
                    <input type="text" name="ra" class="form-control" value="<?php echo htmlspecialchars($raFiltro); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">CPF</label>
                    <input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($_GET['cpf'] ?? ''); ?>">
                </div>
                <?php if ($userPerfil !== 'USUARIO_ESCOLA'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Escola</label>
                        <input type="text" name="escola" class="form-control" value="<?php echo htmlspecialchars($escolaFiltro); ?>">
                    </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="PENDENTE" <?php echo $statusFiltro == 'PENDENTE' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="APROVADO" <?php echo $statusFiltro == 'APROVADO' ? 'selected' : ''; ?>>Aprovado</option>
                        <option value="REPROVADO" <?php echo $statusFiltro == 'REPROVADO' ? 'selected' : ''; ?>>Reprovado</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-dark">Filtrar</button>
                    <a href="alunos_listar.php" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>RA</th>
                            <th>Deficiencia</th>
                            <?php if ($userPerfil !== 'USUARIO_ESCOLA'): ?>
                                <th>Escola</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>PAE</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alunos) > 0): ?>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                    <?php if ($userPerfil !== 'USUARIO_ESCOLA'): ?>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php
                                            $status = $aluno['status_aprovacao'];
                                            if ($status == 'PENDENTE') {
                                                echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                            } elseif ($status == 'APROVADO') {
                                                echo '<span class="badge bg-success">Aprovado</span>';
                                            } elseif ($status == 'REPROVADO') {
                                                echo '<span class="badge bg-danger">Reprovado</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Arquivado</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($aluno['pae_nome'] ?? 'Sem PAE'); ?></td>
                                    <td>
                                        <a href="alunos_visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>

                                        <?php if ($userPerfil == 'USUARIO_ESCOLA'): ?>
                                            <a href="alunos_editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <?php endif; ?>

                                        <?php if ($userPerfil == 'ADMIN' && $aluno['status_aprovacao'] == 'APROVADO'): ?>
                                            <a href="associacoes.php?aluno=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-primary">Associar PAE</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $userPerfil === 'USUARIO_ESCOLA' ? '7' : '8'; ?>" class="text-center text-muted">Nenhum aluno encontrado.</td>
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
