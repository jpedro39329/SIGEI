<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas ADMIN pode acessar
exigirPerfil(array('ADMIN'));

$userName = $_SESSION['user_name'];

function buscarTotal($conexao, $sql) {
    $result = mysqli_query($conexao, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

$totalAlunos = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM alunos");
$totalPAEs = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM paes");
$totalEscolas = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM escolas");
$totalEmpresas = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM empresas");
$totalPendentes = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");
$totalAssociacoes = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM associacoes WHERE ativo = 1");

// Alunos recentes
$sqlAlunos = "
    SELECT a.id_aluno, a.nome, a.cpf, a.descricao_deficiencia, a.status_aprovacao, a.data_cadastro,
           e.nome AS escola_nome
    FROM alunos a
    LEFT JOIN escolas e ON a.id_escola = e.id_escola
    ORDER BY a.data_cadastro DESC
    LIMIT 10
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];

// PAEs recentes
$sqlPAEs = "SELECT p.nome, p.cpf, p.ativo, emp.nome AS empresa_nome
            FROM paes p
            LEFT JOIN empresas emp ON p.id_empresa = emp.id_empresa
            ORDER BY p.data_cadastro DESC
            LIMIT 10";
$resultPAEs = mysqli_query($conexao, $sqlPAEs);
$paes = $resultPAEs ? mysqli_fetch_all($resultPAEs, MYSQLI_ASSOC) : [];

// Associações recentes
$sqlAssociacoes = "SELECT ass.id_associacao, a.nome AS aluno_nome, p.nome AS pae_nome, ass.data_inicio
    FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    JOIN paes p ON ass.id_pae = p.id_pae
    WHERE ass.ativo = 1
    ORDER BY ass.data_inicio DESC
    LIMIT 10";
$resultAssociacoes = mysqli_query($conexao, $sqlAssociacoes);
$associacoes = $resultAssociacoes ? mysqli_fetch_all($resultAssociacoes, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-dashboard-admin">

<?php require("navbar.php"); ?>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Painel Administrativo</h2>
                <p class="text-muted mb-0">Olá, <?php echo htmlspecialchars($userName); ?> — visão geral completa do SIGEI.</p>
            </div>
        </div>

        <!-- Cards -->
        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Alunos</span><strong><?php echo $totalAlunos; ?></strong></div>
            <div class="card"><span class="text-muted">PAEs</span><strong><?php echo $totalPAEs; ?></strong></div>
            <div class="card"><span class="text-muted">Escolas</span><strong><?php echo $totalEscolas; ?></strong></div>
            <div class="card"><span class="text-muted">Empresas</span><strong><?php echo $totalEmpresas; ?></strong></div>
            <div class="card"><span class="text-muted">Pendentes</span><strong><?php echo $totalPendentes; ?></strong></div>
            <div class="card"><span class="text-muted">Associações</span><strong><?php echo $totalAssociacoes; ?></strong></div>
        </div>

        <!-- Alunos recentes -->
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Alunos recentes</h5>
                    <a href="alunos_listar.php" class="btn btn-primary btn-sm">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Deficiência</th>
                                <th>Escola</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($alunos) > 0): ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['status_aprovacao']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Nenhum aluno cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- PAEs recentes -->
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">PAEs recentes</h5>
                    <a href="cuidadores_listar.php" class="btn btn-primary btn-sm">Ver todos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Empresa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($paes) > 0): ?>
                                <?php foreach ($paes as $pae): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pae['nome']); ?></td>
                                        <td><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></td>
                                        <td><?php echo htmlspecialchars($pae['empresa_nome'] ?? '-'); ?></td>
                                        <td><?php echo $pae['ativo'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">Nenhum PAE cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Associações recentes -->
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Associações recentes</h5>
                    <a href="associacoes.php" class="btn btn-primary btn-sm">Gerenciar</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>PAE</th>
                                <th>Data Início</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($associacoes) > 0): ?>
                                <?php foreach ($associacoes as $assoc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assoc['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($assoc['pae_nome']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($assoc['data_inicio'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted">Nenhuma associação.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Ações rápidas -->
        <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Ações rápidas</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="escolas_cadastrar.php" class="btn btn-outline-primary">Cadastrar Escola</a>
                    <a href="empresas_cadastrar.php" class="btn btn-outline-primary">Cadastrar Empresa</a>
                    <a href="associacoes.php" class="btn btn-outline-primary">Associar PAE a Aluno</a>
                    <a href="usuarios.php" class="btn btn-outline-primary">Ver Usuários</a>
                </div>
            </div>
        </section>
    </main>

</body>
</html>