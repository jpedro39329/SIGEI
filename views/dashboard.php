<?php
require_once "../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

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

<?php require("../includes/navbar.php"); ?>

<div class="content">

    <div class="mb-4">
        <h4 class="mb-1">Olá, <?php echo htmlspecialchars($userName); ?></h4>
        <p class="text-muted mb-0"><strong><?php echo htmlspecialchars(nomePerfil($userPerfil)); ?></strong> — bem-vindo(a) ao painel do SIGEI.</p>
    </div>

    <!-- ============================================================ -->
    <!-- 1. USUARIO ESCOLA -->
    <!-- ============================================================ -->
    <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>

        <?php
        $idEscola = idEscolaUsuario($conexao, $userId);

        $alunosEmAtendimento = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE a.id_ue = $idEscola AND a.status_aprovacao = 'APROVADO'
        ");
        $alunosSemAtendimento = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            WHERE a.id_ue = $idEscola
              AND a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass
                  WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ");
        $alunosPendentes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE id_ue = $idEscola AND status_aprovacao = 'PENDENTE'");

        $sqlAlunos = "
            SELECT a.id_aluno, a.nome, a.status_aprovacao,
                   (SELECT p.nome FROM associacoes ass
                    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
                    WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
                    LIMIT 1) AS pae_nome
            FROM alunos a
            WHERE a.id_ue = $idEscola
            ORDER BY a.data_cadastro DESC
            LIMIT 10
        ";
        $resultadoAlunos = mysqli_query($conexao, $sqlAlunos);
        $alunos = $resultadoAlunos ? mysqli_fetch_all($resultadoAlunos, MYSQLI_ASSOC) : [];
        ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Resumo dos alunos da escola</h5>
                <canvas id="graficoEscola" height="100"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Alunos da minha escola</h5>
                    <a href="alunos/pendentes.php" class="btn btn-primary btn-sm">Ver Solicitações</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nome</th><th>Status</th><th>PAE Vinculado</th></tr></thead>
                        <tbody>
                            <?php if (count($alunos) > 0): ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <td>
                                            <?php
                                                $st = $aluno['status_aprovacao'];
                                                if ($st == 'PENDENTE') echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                                elseif ($st == 'APROVADO') echo '<span class="badge bg-success">Aprovado</span>';
                                                elseif ($st == 'REPROVADO') echo '<span class="badge bg-danger">Reprovado</span>';
                                                else echo '<span class="badge bg-secondary">Arquivado</span>';
                                            ?>
                                        </td>
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

    <!-- ============================================================ -->
    <!-- 2. DIRIGENTE REGIONAL (URE GABINETE) -->
    <!-- ============================================================ -->
    <?php elseif ($userPerfil === 'DIRIGENTE'): ?>

        <?php
        if ($idUreUsuario <= 0) $idUreUsuario = idUreUsuario($conexao, $userId);

        $totalEscolasUre = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM unidades_escolares WHERE id_ure = $idUreUsuario");
        $totalServidoresUre = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM usuarios_ure WHERE id_ure = $idUreUsuario AND ativo = 1");
        $totalAlunosUre = totalDashboard($conexao, "
            SELECT COUNT(*) AS total FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario
        ");
        $alunosAtendidosUre = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'APROVADO'
        ");
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Escolas na Regional</span><strong><?php echo $totalEscolasUre; ?></strong></div>
            <div class="card"><span class="text-muted">Servidores (URE)</span><strong><?php echo $totalServidoresUre; ?></strong></div>
            <div class="card"><span class="text-muted">Alunos Cadastrados</span><strong><?php echo $totalAlunosUre; ?></strong></div>
            <div class="card"><span class="text-muted">Alunos c/ PAE Ativo</span><strong><?php echo $alunosAtendidosUre; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Ações da Diretoria Regional</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="escolas/cadastrar.php" class="btn btn-outline-primary">Cadastrar Escolas</a>
                    <a href="setores/cadastrar.php" class="btn btn-outline-primary">Cadastrar SEFISC / Educação Especial</a>
                    <a href="alunos/listar.php" class="btn btn-outline-secondary">Ver Alunos da Regional</a>
                </div>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- 3. EDUCAÇÃO ESPECIAL (URE) -->
    <!-- ============================================================ -->
    <?php elseif ($userPerfil === 'USUARIO_EDUCACAO_ESPECIAL'): ?>

        <?php
        if ($idUreUsuario <= 0) $idUreUsuario = idUreUsuario($conexao, $userId);
        $whereUre = ($idUreUsuario > 0) ? "JOIN unidades_escolares ue ON a.id_ue = ue.id_ue WHERE ue.id_ure = $idUreUsuario" : "";

        $alunosEmAtendimento = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            " . (($idUreUsuario > 0) ? "JOIN unidades_escolares ue ON a.id_ue = ue.id_ue WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'APROVADO'" : "WHERE a.status_aprovacao = 'APROVADO'") . "
        ");
        $alunosPendentes = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            " . (($idUreUsuario > 0) ? "JOIN unidades_escolares ue ON a.id_ue = ue.id_ue WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'PENDENTE'" : "WHERE a.status_aprovacao = 'PENDENTE'") . "
        ");
        $alunosAprovados = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            " . (($idUreUsuario > 0) ? "JOIN unidades_escolares ue ON a.id_ue = ue.id_ue WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'APROVADO'" : "WHERE a.status_aprovacao = 'APROVADO'") . "
        ");

        $sqlSolicitacoesRecentes = "
            SELECT a.id_aluno, a.nome, a.descricao_deficiencia, a.data_cadastro, ue.nome AS escola_nome
            FROM alunos a
            LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE a.status_aprovacao = 'PENDENTE'
            " . (($idUreUsuario > 0) ? "AND ue.id_ure = $idUreUsuario" : "") . "
            ORDER BY a.data_cadastro ASC
            LIMIT 10
        ";
        $resSol = mysqli_query($conexao, $sqlSolicitacoesRecentes);
        $solicitacoesPendentesLista = $resSol ? mysqli_fetch_all($resSol, MYSQLI_ASSOC) : [];
        ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Análise de Solicitações da URE</h5>
                    <a href="solicitacoes/listar.php" class="btn btn-warning btn-sm">Ver Todas as Solicitações</a>
                </div>
                <canvas id="graficoEducacao" height="100"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Solicitações Aguardando Análise</h5>
                    <a href="solicitacoes/listar.php" class="btn btn-outline-primary btn-sm">Ver Fila Completa</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Aluno</th><th>Escola</th><th>Deficiência</th><th>Data</th><th>Ação</th></tr></thead>
                        <tbody>
                            <?php if (count($solicitacoesPendentesLista) > 0): ?>
                                <?php foreach ($solicitacoesPendentesLista as $sol): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($sol['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($sol['escola_nome'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($sol['descricao_deficiencia']); ?></td>
                                        <td><?php echo !empty($sol['data_cadastro']) ? date('d/m/Y', strtotime($sol['data_cadastro'])) : '-'; ?></td>
                                        <td>
                                            <a href="solicitacoes/analisar.php?id=<?php echo $sol['id_aluno']; ?>" class="btn btn-sm btn-info">Analisar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Nenhuma solicitação pendente no momento.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- 4. SUPERVISOR DA EMPRESA -->
    <!-- ============================================================ -->
    <?php elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>

        <?php
        if ($idEmpresaUsuario <= 0) $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);

        $totalPAEs = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM usuarios_pae WHERE id_empresa = $idEmpresaUsuario");
        $paesAtendendo = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT p.id_pae) AS total
            FROM usuarios_pae p
            JOIN associacoes ass ON ass.id_pae = p.id_pae AND ass.ativo = 1
            WHERE p.id_empresa = $idEmpresaUsuario
        ");
        $totalAssocAtivas = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE p.id_empresa = $idEmpresaUsuario AND ass.ativo = 1
        ");
        $totalRelatorios = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM relatorios r
            JOIN associacoes ass ON r.id_associacao = ass.id_associacao
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE p.id_empresa = $idEmpresaUsuario
        ");
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Total de PAEs</span><strong><?php echo $totalPAEs; ?></strong></div>
            <div class="card"><span class="text-muted">PAEs em Atendimento</span><strong><?php echo $paesAtendendo; ?></strong></div>
            <div class="card"><span class="text-muted">Associações Ativas</span><strong><?php echo $totalAssocAtivas; ?></strong></div>
            <div class="card"><span class="text-muted">Relatórios Enviados</span><strong><?php echo $totalRelatorios; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Ações Rápidas da Empresa</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="paes/cadastrar.php" class="btn btn-outline-primary">Cadastrar Novo PAE</a>
                    <a href="associacoes/gerenciar.php" class="btn btn-outline-primary">Associar PAE a Aluno</a>
                    <a href="relatorios/listar.php" class="btn btn-outline-secondary">Visualizar Relatórios</a>
                </div>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- 5. PAE (CUIDADOR) -->
    <!-- ============================================================ -->
    <?php elseif ($userPerfil === 'PAE'): ?>

        <?php
        $sqlAlunos = "
            SELECT a.id_aluno, a.nome, a.descricao_deficiencia, ue.nome AS escola_nome
            FROM associacoes ass
            JOIN alunos a ON ass.id_aluno = a.id_aluno
            LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ass.id_pae = $userId AND ass.ativo = 1 AND a.status_aprovacao = 'APROVADO'
            ORDER BY a.nome
        ";
        $resultAlunos = mysqli_query($conexao, $sqlAlunos);
        $alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">Meus Alunos Atendidos</span><strong><?php echo count($alunos); ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Meus Alunos Vinculados</h5>
                    <a href="relatorios/listar.php" class="btn btn-primary btn-sm">Registrar Relatório</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nome</th><th>Deficiência</th><th>Escola</th></tr></thead>
                        <tbody>
                            <?php if (count($alunos) > 0): ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted">Nenhum aluno associado no momento.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- 6. SEFISC (URE) -->
    <!-- ============================================================ -->
    <?php elseif ($userPerfil === 'USUARIO_SEFISC'): ?>

        <?php
        if ($idUreUsuario <= 0) $idUreUsuario = idUreUsuario($conexao, $userId);

        $totalUsuariosEscola = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM usuarios_ue uue
            JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario AND uue.ativo = 1
        ");
        $totalEscolasUre = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM unidades_escolares WHERE id_ure = $idUreUsuario");
        $totalPaesUre = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT p.id_pae) AS total
            FROM usuarios_pae p
            JOIN empresa_ure eu ON p.id_empresa = eu.id_empresa
            WHERE eu.id_ure = $idUreUsuario AND p.ativo = 1
        ");

        // Alunos aprovados e pendentes da URE para o gráfico SEFISC
        $alunosAprovadosUre = totalDashboard($conexao, "
            SELECT COUNT(*) AS total FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'APROVADO'
        ");
        $alunosPendentesUre = totalDashboard($conexao, "
            SELECT COUNT(*) AS total FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'PENDENTE'
        ");

        // Lista relevante: Usuários de escolas recém cadastrados na URE
        $sqlUltimosUsuariosUe = "
            SELECT uue.nome, uue.cpf, ue.nome AS escola_nome, uue.data_cadastro, uue.ativo
            FROM usuarios_ue uue
            JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario
            ORDER BY uue.data_cadastro DESC
            LIMIT 8
        ";
        $resUe = mysqli_query($conexao, $sqlUltimosUsuariosUe);
        $ultimosUsuariosUe = $resUe ? mysqli_fetch_all($resUe, MYSQLI_ASSOC) : [];
        ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Panorama Geral da URE (Fiscalização)</h5>
                    <a href="usuarios_ue/listar.php" class="btn btn-outline-primary btn-sm">Gerenciar Usuários</a>
                </div>
                <canvas id="graficoSefisc" height="100"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Usuários de Escolas Cadastrados na Regional</h5>
                    <a href="usuarios_ue/cadastrar.php" class="btn btn-primary btn-sm">Novo Usuário</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nome</th><th>CPF</th><th>Escola</th><th>Status</th><th>Data Cadastro</th></tr></thead>
                        <tbody>
                            <?php if (count($ultimosUsuariosUe) > 0): ?>
                                <?php foreach ($ultimosUsuariosUe as $uu): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($uu['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(formatarCPF($uu['cpf'])); ?></td>
                                        <td><?php echo htmlspecialchars($uu['escola_nome']); ?></td>
                                        <td>
                                            <?php if ($uu['ativo'] == 1): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo !empty($uu['data_cadastro']) ? date('d/m/Y', strtotime($uu['data_cadastro'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Nenhum usuário de escola cadastrado na URE.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ============================================================ -->
    <!-- 7. ADMIN GERAL & SEDUC-SP -->
    <!-- ============================================================ -->
    <?php elseif (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>

        <?php
        $totalUres = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM unidades_regionais");
        $totalEscolas = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM unidades_escolares");
        $totalEmpresas = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM empresas");
        $totalSupervisores = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM usuarios_supervisor WHERE ativo = 1");
        $totalPAEs = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM usuarios_pae WHERE ativo = 1");
        $totalAlunos = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos");
        $totalPendentes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");
        $totalAssociacoes = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM associacoes WHERE ativo = 1");
        ?>

        <div class="cards mb-4">
            <div class="card"><span class="text-muted">UREs</span><strong><?php echo $totalUres; ?></strong></div>
            <div class="card"><span class="text-muted">Escolas</span><strong><?php echo $totalEscolas; ?></strong></div>
            <div class="card"><span class="text-muted">Empresas</span><strong><?php echo $totalEmpresas; ?></strong></div>
            <div class="card"><span class="text-muted">Supervisores</span><strong><?php echo $totalSupervisores; ?></strong></div>
            <div class="card"><span class="text-muted">PAEs Ativos</span><strong><?php echo $totalPAEs; ?></strong></div>
            <div class="card"><span class="text-muted">Total Alunos</span><strong><?php echo $totalAlunos; ?></strong></div>
            <div class="card"><span class="text-muted">Pendentes</span><strong><?php echo $totalPendentes; ?></strong></div>
            <div class="card"><span class="text-muted">Associações Ativas</span><strong><?php echo $totalAssociacoes; ?></strong></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Ações de Gestão Central (SEDUC-SP / Administrador Geral)</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="ures/cadastrar.php" class="btn btn-outline-primary">Cadastrar URE</a>
                    <a href="empresas/cadastrar.php" class="btn btn-outline-primary">Cadastrar Empresa Licitada</a>
                    <a href="supervisores/cadastrar.php" class="btn btn-outline-primary">Cadastrar Supervisor</a>
                    <a href="dirigentes/cadastrar.php" class="btn btn-outline-primary">Cadastrar Dirigente Regional</a>
                    <a href="escolas/cadastrar.php" class="btn btn-outline-secondary">Cadastrar Escola</a>
                    <a href="usuarios/listar.php" class="btn btn-outline-secondary">Todos os Usuários</a>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <footer class="text-center text-muted py-3 small">
        SIGEI — Sistema Integrado de Gestão da Educação Inclusiva &copy; <?php echo date('Y'); ?>
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
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

<?php if ($userPerfil === 'USUARIO_EDUCACAO_ESPECIAL'): ?>
new Chart(document.getElementById('graficoEducacao'), {
    type: 'bar',
    data: {
        labels: ['Pendentes', 'Aprovados', 'Em Atendimento'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosPendentes; ?>, <?php echo $alunosAprovados; ?>, <?php echo $alunosEmAtendimento; ?>],
            backgroundColor: ['#ffc107', '#198754', '#0d6efd']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($userPerfil === 'USUARIO_SEFISC'): ?>
new Chart(document.getElementById('graficoSefisc'), {
    type: 'bar',
    data: {
        labels: ['Alunos Aprovados', 'Solicitações Pendentes', 'Usuários de UE', 'PAEs na URE'],
        datasets: [{
            label: 'Total',
            data: [<?php echo $alunosAprovadosUre; ?>, <?php echo $alunosPendentesUre; ?>, <?php echo $totalUsuariosEscola; ?>, <?php echo $totalPaesUre; ?>],
            backgroundColor: ['#198754', '#ffc107', '#0d6efd', '#0dcaf0']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>

</body>
</html>
