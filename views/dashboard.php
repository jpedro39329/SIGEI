<?php
require_once "../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

function totalDashboard($conexao, $sql) {
    $result = mysqli_query($conexao, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-dashboard">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="mb-4">
        <h4 class="mb-1">Olá, <?php echo htmlspecialchars($userName); ?></h4>
        <p class="text-muted mb-0"><?php echo htmlspecialchars(nomePerfil($userPerfil)); ?> - bem-vindo(a) ao painel do SIGEI.</p>
    </div>

    <?php if ($userPerfil == 'USUARIO_ESCOLA'): ?>

        <?php
        $idEscola = idEscolaUsuario($conexao, $userId);

        $alunosEmAtendimento = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE a.id_escola = $idEscola AND a.status_aprovacao = 'APROVADO'
        ");
        $alunosSemAtendimento = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            WHERE a.id_escola = $idEscola
              AND a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass
                  WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ");
        $alunosPendentes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE id_escola = $idEscola AND status_aprovacao = 'PENDENTE'");

        $sqlAlunos = "
            SELECT a.id_aluno, a.nome, a.status_aprovacao,
                   (SELECT p.nome FROM associacoes ass
                    JOIN paes p ON ass.id_pae = p.id_pae
                    WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
                    LIMIT 1) AS pae_nome
            FROM alunos a
            WHERE a.id_escola = $idEscola
            ORDER BY a.data_cadastro DESC
            LIMIT 10
        ";
        $resultadoAlunos = mysqli_query($conexao, $sqlAlunos);
        $alunos = $resultadoAlunos ? mysqli_fetch_all($resultadoAlunos, MYSQLI_ASSOC) : [];
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Em atendimento</span><strong><?php echo $alunosEmAtendimento; ?></strong></div>
            <div class="card"><span class="text-muted">Sem atendimento</span><strong><?php echo $alunosSemAtendimento; ?></strong></div>
            <div class="card"><span class="text-muted">Pendentes</span><strong><?php echo $alunosPendentes; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Resumo dos alunos</h5>
                <canvas id="graficoEscola" height="120"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Alunos da minha escola</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nome</th><th>Status</th><th>PAE</th></tr></thead>
                        <tbody>
                            <?php if (count($alunos) > 0): ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['status_aprovacao']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['pae_nome'] ?? 'Sem PAE'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted">Nenhum aluno cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($userPerfil == 'USUARIO_EDUCACAO_ESPECIAL'): ?>

        <?php
        $alunosEmAtendimento = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE a.status_aprovacao = 'APROVADO'
        ");
        $alunosSemAtendimento = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            WHERE a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass
                  WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ");
        $alunosPendentes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Em atendimento</span><strong><?php echo $alunosEmAtendimento; ?></strong></div>
            <div class="card"><span class="text-muted">Sem atendimento</span><strong><?php echo $alunosSemAtendimento; ?></strong></div>
            <div class="card"><span class="text-muted">Pendentes</span><strong><?php echo $alunosPendentes; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Resumo da Educacao Especial</h5>
                <canvas id="graficoEducacao" height="120"></canvas>
            </div>
        </div>

    <?php elseif ($userPerfil == 'USUARIO_EMPRESA'): ?>

        <?php
        $idEmpresa = idEmpresaUsuario($conexao, $userId);
        $totalPAEs = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM paes WHERE id_empresa = $idEmpresa");
        $paesAtendendo = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT p.id_pae) AS total
            FROM paes p
            JOIN associacoes ass ON ass.id_pae = p.id_pae AND ass.ativo = 1
            WHERE p.id_empresa = $idEmpresa
        ");
        $paesSemAlunos = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM paes p
            WHERE p.id_empresa = $idEmpresa
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass
                  WHERE ass.id_pae = p.id_pae AND ass.ativo = 1
              )
        ");
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Total de PAEs</span><strong><?php echo $totalPAEs; ?></strong></div>
            <div class="card"><span class="text-muted">PAEs atendendo alunos</span><strong><?php echo $paesAtendendo; ?></strong></div>
            <div class="card"><span class="text-muted">PAEs sem alunos</span><strong><?php echo $paesSemAlunos; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Resumo dos PAEs</h5>
                <canvas id="graficoEmpresa" height="120"></canvas>
            </div>
        </div>

    <?php elseif ($userPerfil == 'PAE'): ?>

        <?php
        $sqlAlunos = "
            SELECT a.id_aluno, a.nome, a.descricao_deficiencia
            FROM associacoes ass
            JOIN alunos a ON ass.id_aluno = a.id_aluno
            WHERE ass.id_pae = $userId AND ass.ativo = 1 AND a.status_aprovacao = 'APROVADO'
            ORDER BY a.nome
        ";
        $alunos = mysqli_fetch_all(mysqli_query($conexao, $sqlAlunos), MYSQLI_ASSOC);
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Meus alunos</span><strong><?php echo count($alunos); ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Meus alunos</h5>
                    <a href="relatorios.php" class="btn btn-primary btn-sm">Registrar Relatorio</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nome</th><th>Deficiencia</th></tr></thead>
                        <tbody>
                            <?php if (count($alunos) > 0): ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted">Nenhum aluno associado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($userPerfil == 'USUARIO_SEFISC'): ?>

        <?php
        $alunosEmAtendimento = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE a.status_aprovacao = 'APROVADO'
        ");
        $profissionaisAtivos = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM paes WHERE ativo = 1");
        $alunosPendentes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Alunos em atendimento</span><strong><?php echo $alunosEmAtendimento; ?></strong></div>
            <div class="card"><span class="text-muted">Profissionais ativos</span><strong><?php echo $profissionaisAtivos; ?></strong></div>
            <div class="card"><span class="text-muted">Alunos pendentes</span><strong><?php echo $alunosPendentes; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Resumo da fiscalizacao</h5>
                <canvas id="graficoSefisc" height="120"></canvas>
            </div>
        </div>

        <p class="text-muted">Acesse os menus acima para ver alunos, PAEs e empresas.</p>

    <?php elseif ($userPerfil == 'ADMIN'): ?>

        <?php
        $totalAlunos = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos");
        $totalPAEs = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM paes");
        $totalEscolas = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM escolas");
        $totalEmpresas = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM empresas");
        $totalPendentes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");
        $totalAssociacoes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM associacoes WHERE ativo = 1");

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

        $sqlPAEs = "SELECT p.nome, p.cpf, p.ativo, emp.nome AS empresa_nome
                    FROM paes p
                    LEFT JOIN empresas emp ON p.id_empresa = emp.id_empresa
                    ORDER BY p.data_cadastro DESC
                    LIMIT 10";
        $resultPAEs = mysqli_query($conexao, $sqlPAEs);
        $paes = $resultPAEs ? mysqli_fetch_all($resultPAEs, MYSQLI_ASSOC) : [];

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

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Alunos</span><strong><?php echo $totalAlunos; ?></strong></div>
            <div class="card"><span class="text-muted">PAEs</span><strong><?php echo $totalPAEs; ?></strong></div>
            <div class="card"><span class="text-muted">Escolas</span><strong><?php echo $totalEscolas; ?></strong></div>
            <div class="card"><span class="text-muted">Empresas</span><strong><?php echo $totalEmpresas; ?></strong></div>
            <div class="card"><span class="text-muted">Pendentes</span><strong><?php echo $totalPendentes; ?></strong></div>
            <div class="card"><span class="text-muted">Associações ativas</span><strong><?php echo $totalAssociacoes; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
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
                                        <td>
                                            <?php
                                                $st = $aluno['status_aprovacao'];
                                                if ($st == 'PENDENTE') {
                                                    echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                                } elseif ($st == 'APROVADO') {
                                                    echo '<span class="badge bg-success">Aprovado</span>';
                                                } elseif ($st == 'REPROVADO') {
                                                    echo '<span class="badge bg-danger">Reprovado</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">Arquivado</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Nenhum aluno cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">PAEs recentes</h5>
                    <a href="paes_listar.php" class="btn btn-primary btn-sm">Ver todos</a>
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
        </div>

        <div class="card border-0 shadow-sm mb-4">
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
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Ações rápidas</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="escolas_cadastrar.php" class="btn btn-outline-primary">Cadastrar Escola</a>
                    <a href="empresas_cadastrar.php" class="btn btn-outline-primary">Cadastrar Empresa</a>
                    <a href="associacoes.php" class="btn btn-outline-primary">Associar PAE a Aluno</a>
                    <a href="usuarios.php" class="btn btn-outline-primary">Ver Usuários</a>
                </div>
            </div>
        </div>

    <?php else: ?>

        <div class="alert alert-info">Bem-vindo(a) ao sistema SIGEI.</div>

    <?php endif; ?>

    <footer>
        SIGEI - Sistema de Gestao Integrada | Painel colaborativo
    </footer>

</div>

<?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_SEFISC', 'USUARIO_EMPRESA', 'USUARIO_EDUCACAO_ESPECIAL'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($userPerfil == 'USUARIO_ESCOLA'): ?>
new Chart(document.getElementById('graficoEscola'), {
    type: 'bar',
    data: {
        labels: ['Em atendimento', 'Sem atendimento', 'Pendentes'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosEmAtendimento; ?>, <?php echo $alunosSemAtendimento; ?>, <?php echo $alunosPendentes; ?>],
            backgroundColor: ['#198754', '#0dcaf0', '#ffc107']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($userPerfil == 'USUARIO_SEFISC'): ?>
new Chart(document.getElementById('graficoSefisc'), {
    type: 'bar',
    data: {
        labels: ['Alunos em atendimento', 'Profissionais ativos', 'Alunos pendentes'],
        datasets: [{
            label: 'Total',
            data: [<?php echo $alunosEmAtendimento; ?>, <?php echo $profissionaisAtivos; ?>, <?php echo $alunosPendentes; ?>],
            backgroundColor: ['#198754', '#0d6efd', '#ffc107']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($userPerfil == 'USUARIO_EMPRESA'): ?>
new Chart(document.getElementById('graficoEmpresa'), {
    type: 'bar',
    data: {
        labels: ['Total de PAEs', 'PAEs atendendo', 'PAEs sem alunos'],
        datasets: [{
            label: 'PAEs',
            data: [<?php echo $totalPAEs; ?>, <?php echo $paesAtendendo; ?>, <?php echo $paesSemAlunos; ?>],
            backgroundColor: ['#0d47a1', '#1565c0', '#90caf9']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($userPerfil == 'USUARIO_EDUCACAO_ESPECIAL'): ?>
new Chart(document.getElementById('graficoEducacao'), {
    type: 'bar',
    data: {
        labels: ['Em atendimento', 'Sem atendimento', 'Pendentes'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosEmAtendimento; ?>, <?php echo $alunosSemAtendimento; ?>, <?php echo $alunosPendentes; ?>],
            backgroundColor: ['#0d47a1', '#1565c0', '#90caf9']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>
