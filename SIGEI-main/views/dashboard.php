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
    <link rel="icon" type="image/png" href="../assets/imgs/favicon.png">
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
    <!-- 2. DIRIGENTE REGIONAL (URE ASURE) -->
    <!-- ============================================================ -->
    <?php elseif ($userPerfil === 'DIRIGENTE'): ?>

        <?php
        if ($idUreUsuario <= 0) $idUreUsuario = idUreUsuario($conexao, $userId);

        // 1. Status de Atendimento dos Alunos na Regional
        $alunosEmAtendimentoUre = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'APROVADO'
        ");
        $alunosSemAtendimentoUre = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario
              AND a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass
                  WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ");
        $alunosPendentesUre = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario AND a.status_aprovacao = 'PENDENTE'
        ");

        // 2. Alunos por Escola na Regional
        $sqlEscolasUre = "
            SELECT ue.nome, COUNT(a.id_aluno) AS total_alunos
            FROM unidades_escolares ue
            LEFT JOIN alunos a ON ue.id_ue = a.id_ue
            WHERE ue.id_ure = $idUreUsuario
            GROUP BY ue.id_ue
            ORDER BY total_alunos DESC, ue.nome ASC
            LIMIT 6
        ";
        $resEscolasUre = mysqli_query($conexao, $sqlEscolasUre);
        $dadosEscolasUre = $resEscolasUre ? mysqli_fetch_all($resEscolasUre, MYSQLI_ASSOC) : [];
        $labelsEscolasUre = array_map(function($e) { return mb_strimwidth($e['nome'], 0, 20, '...'); }, $dadosEscolasUre);
        $valoresEscolasUre = array_map(function($e) { return (int) $e['total_alunos']; }, $dadosEscolasUre);

        // 3. Distribuição de Deficiências na Regional
        $sqlDeficiencias = "
            SELECT a.descricao_deficiencia, COUNT(*) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario AND a.descricao_deficiencia IS NOT NULL AND a.descricao_deficiencia != ''
            GROUP BY a.descricao_deficiencia
            ORDER BY total DESC
            LIMIT 5
        ";
        $resDef = mysqli_query($conexao, $sqlDeficiencias);
        $dadosDef = $resDef ? mysqli_fetch_all($resDef, MYSQLI_ASSOC) : [];
        $labelsDef = array_map(function($d) { return mb_strimwidth($d['descricao_deficiencia'], 0, 20, '...'); }, $dadosDef);
        $valoresDef = array_map(function($d) { return (int) $d['total']; }, $dadosDef);

        // 4. Histórico / Evolução de Alunos na Regional
        $sqlHistUre = "
            SELECT DATE_FORMAT(a.data_cadastro, '%m/%Y') AS mes_ano, COUNT(*) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario
            GROUP BY mes_ano
            ORDER BY MIN(a.data_cadastro) ASC
            LIMIT 6
        ";
        $resHistUre = mysqli_query($conexao, $sqlHistUre);
        $dadosHistUre = $resHistUre ? mysqli_fetch_all($resHistUre, MYSQLI_ASSOC) : [];
        if (empty($dadosHistUre)) {
            $dadosHistUre = [['mes_ano' => date('m/Y'), 'total' => ($alunosEmAtendimentoUre + $alunosSemAtendimentoUre + $alunosPendentesUre)]];
        }
        $labelsHistUre = array_map(function($h) { return $h['mes_ano']; }, $dadosHistUre);
        $valoresHistUre = array_map(function($h) { return (int) $h['total']; }, $dadosHistUre);

        // 5. Lista de Alunos Recentes da Regional
        $sqlAlunosUre = "
            SELECT a.id_aluno, a.nome, a.ra, a.descricao_deficiencia, a.status_aprovacao, ue.nome AS escola_nome,
                   (SELECT p.nome FROM associacoes ass
                    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
                    WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
                    LIMIT 1) AS pae_nome
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario
            ORDER BY a.data_cadastro DESC
            LIMIT 8
        ";
        $resAlunosUre = mysqli_query($conexao, $sqlAlunosUre);
        $alunosRecentesUre = $resAlunosUre ? mysqli_fetch_all($resAlunosUre, MYSQLI_ASSOC) : [];
        ?>

        <!-- Painel de Gráficos da Regional (ASURE) -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Status de Atendimento dos Alunos na Regional</h5>
                        <canvas id="graficoDirigenteStatus" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Alunos por Escola na Regional</h5>
                        <canvas id="graficoDirigenteEscolas" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Principais Necessidades / Deficiências</h5>
                        <canvas id="graficoDirigenteDeficiencias" height="120"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Evolução de Alunos na Regional</h5>
                        <canvas id="graficoDirigenteEvolucao" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Alunos da Regional -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Alunos Registrados na Regional</h5>
                    <a href="alunos/listar.php" class="btn btn-primary btn-sm">Ver Todos os Alunos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Escola</th>
                                <th>Deficiência</th>
                                <th>Status</th>
                                <th>PAE Vinculado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($alunosRecentesUre) > 0): ?>
                                <?php foreach ($alunosRecentesUre as $a): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($a['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($a['escola_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($a['descricao_deficiencia'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                            $st = $a['status_aprovacao'];
                                            if ($st === 'APROVADO') echo '<span class="badge bg-success">Aprovado</span>';
                                            elseif ($st === 'PENDENTE') echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                            elseif ($st === 'REPROVADO') echo '<span class="badge bg-danger">Reprovado</span>';
                                            else echo '<span class="badge bg-secondary">Arquivado</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($a['pae_nome'])): ?>
                                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($a['pae_nome']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">Sem PAE</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum aluno cadastrado na regional.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

        <?php
        // Lista dos últimos PAEs da empresa para a tabela
        $sqlUltimosPaes = "
            SELECT p.id_pae, p.nome, p.cpf, p.email, p.telefone, p.ativo, p.data_cadastro
            FROM usuarios_pae p
            WHERE p.id_empresa = $idEmpresaUsuario
            ORDER BY p.data_cadastro DESC
            LIMIT 8
        ";
        $resPaes = mysqli_query($conexao, $sqlUltimosPaes);
        $ultimosPaes = $resPaes ? mysqli_fetch_all($resPaes, MYSQLI_ASSOC) : [];
        ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Panorama Geral da Empresa</h5>
                    <a href="paes/listar.php" class="btn btn-outline-primary btn-sm">Gerenciar PAEs</a>
                </div>
                <canvas id="graficoSupervisor" height="100"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Profissionais de Apoio Escolar (PAEs) Cadastrados</h5>
                    <a href="paes/cadastrar.php" class="btn btn-primary btn-sm">Novo PAE</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nome</th><th>CPF</th><th>E-mail</th><th>Status</th><th>Data Cadastro</th></tr></thead>
                        <tbody>
                            <?php if (count($ultimosPaes) > 0): ?>
                                <?php foreach ($ultimosPaes as $pae): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($pae['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></td>
                                        <td><?php echo htmlspecialchars($pae['email'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($pae['ativo'] == 1): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo !empty($pae['data_cadastro']) ? date('d/m/Y', strtotime($pae['data_cadastro'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Nenhum PAE cadastrado na empresa.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

        // Busca a empresa e contrato vinculados à URE
        $empresaContratoUre = null;
        if ($idUreUsuario > 0) {
            $resEmpUre = mysqli_query($conexao, "
                SELECT e.*
                FROM empresas e
                JOIN empresa_ure eu ON e.id_empresa = eu.id_empresa
                WHERE eu.id_ure = $idUreUsuario AND e.ativo = 1
                LIMIT 1
            ");
            $empresaContratoUre = $resEmpUre ? mysqli_fetch_assoc($resEmpUre) : null;
        }

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

        // Lista de alunos da URE com dados do PAE vinculado
        $sqlAlunosSefisc = "
            SELECT a.id_aluno, a.nome, a.ra, a.descricao_deficiencia, a.status_aprovacao,
                   (SELECT p.nome FROM associacoes ass
                    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
                    WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
                    LIMIT 1) AS pae_nome
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE ue.id_ure = $idUreUsuario
            ORDER BY a.data_cadastro DESC
            LIMIT 10
        ";
        $resAlunosSefisc = mysqli_query($conexao, $sqlAlunosSefisc);
        $alunosSefisc = $resAlunosSefisc ? mysqli_fetch_all($resAlunosSefisc, MYSQLI_ASSOC) : [];
        ?>



        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Panorama Geral da URE (Fiscalização)</h5>
                    <a href="alunos/listar.php" class="btn btn-outline-primary btn-sm">Ver Todos os Alunos</a>
                </div>
                <canvas id="graficoSefisc" height="100"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Alunos da Unidade Regional</h5>
                    <a href="alunos/listar.php" class="btn btn-primary btn-sm">Gerenciar Alunos</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>RA</th>
                                <th>Deficiência</th>
                                <th>Status</th>
                                <th>PAE Vinculado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($alunosSefisc) > 0): ?>
                                <?php foreach ($alunosSefisc as $aluno): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
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
                                        <td>
                                            <?php if (!empty($aluno['pae_nome'])): ?>
                                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($aluno['pae_nome']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Sem PAE</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum aluno encontrado na regional.</td>
                                </tr>
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
        // 1. Escolas por URE
        $sqlEscolasPorUre = "
            SELECT u.nome, u.uge, COUNT(ue.id_ue) AS total_escolas
            FROM unidades_regionais u
            LEFT JOIN unidades_escolares ue ON u.id_ure = ue.id_ure
            GROUP BY u.id_ure
            ORDER BY u.nome ASC
        ";
        $resEscolasPorUre = mysqli_query($conexao, $sqlEscolasPorUre);
        $dadosEscolasUre = $resEscolasPorUre ? mysqli_fetch_all($resEscolasPorUre, MYSQLI_ASSOC) : [];
        $labelsUres = array_map(function($item) {
            return ($item['uge'] ? '[' . $item['uge'] . '] ' : '') . $item['nome'];
        }, $dadosEscolasUre);
        $valoresEscolasUre = array_map(function($item) { return (int) $item['total_escolas']; }, $dadosEscolasUre);

        // 2. Alunos em Atendimento x Sem Atendimento x Pendentes (Geral)
        $alunosEmAtendimentoGeral = totalDashboard($conexao, "
            SELECT COUNT(DISTINCT a.id_aluno) AS total
            FROM alunos a
            JOIN associacoes ass ON ass.id_aluno = a.id_aluno AND ass.ativo = 1
            WHERE a.status_aprovacao = 'APROVADO'
        ");
        $alunosSemAtendimentoGeral = totalDashboard($conexao, "
            SELECT COUNT(*) AS total
            FROM alunos a
            WHERE a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass
                  WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ");
        $alunosPendentesGeral = totalDashboard($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");

        // 3. PAEs por URE (calculado pelas empresas contratadas atendendo as UREs)
        $sqlPaesPorUre = "
            SELECT u.nome, u.uge, COUNT(DISTINCT p.id_pae) AS total_paes
            FROM unidades_regionais u
            LEFT JOIN empresa_ure eu ON u.id_ure = eu.id_ure
            LEFT JOIN usuarios_pae p ON eu.id_empresa = p.id_empresa AND p.ativo = 1
            GROUP BY u.id_ure
            ORDER BY u.nome ASC
        ";
        $resPaesPorUre = mysqli_query($conexao, $sqlPaesPorUre);
        $dadosPaesUre = $resPaesPorUre ? mysqli_fetch_all($resPaesPorUre, MYSQLI_ASSOC) : [];
        $valoresPaesUre = array_map(function($item) { return (int) $item['total_paes']; }, $dadosPaesUre);

        // 4. Indicadores de Atendimentos / Associações recentes
        $sqlHistorico = "
            SELECT DATE_FORMAT(data_cadastro, '%m/%Y') as mes_ano, COUNT(*) as total
            FROM alunos
            GROUP BY mes_ano
            ORDER BY MIN(data_cadastro) ASC
            LIMIT 6
        ";
        $resHistorico = mysqli_query($conexao, $sqlHistorico);
        $dadosHistorico = $resHistorico ? mysqli_fetch_all($resHistorico, MYSQLI_ASSOC) : [];
        if (empty($dadosHistorico)) {
            $dadosHistorico = [['mes_ano' => date('m/Y'), 'total' => ($alunosEmAtendimentoGeral + $alunosSemAtendimentoGeral + $alunosPendentesGeral)]];
        }
        $labelsHistorico = array_map(function($item) { return $item['mes_ano']; }, $dadosHistorico);
        $valoresHistorico = array_map(function($item) { return (int) $item['total']; }, $dadosHistorico);
        ?>

        <!-- Painel Geral SEDUC -->
        <div class="row g-4 mb-4">
            <!-- Gráfico 1: Status de Atendimento dos Alunos (Barras) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Status de Atendimento dos Alunos</h5>
                        <canvas id="graficoStatusAlunos" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico 2: Escolas por UGE (Barras) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Número de Escolas por UGE</h5>
                        <canvas id="graficoEscolasUre" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico 3: Quantidade de PAEs Ativos por URE (Barras) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Número de PAEs Ativos por Regional</h5>
                        <canvas id="graficoPaesUre" height="120"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico 4: Evolução de Alunos Cadastrados (Barras) -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Evolução de Alunos Cadastrados</h5>
                        <canvas id="graficoEvolucao" height="120"></canvas>
                    </div>
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
<?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
// 1. Status de Atendimento dos Alunos (Barras)
new Chart(document.getElementById('graficoStatusAlunos'), {
    type: 'bar',
    data: {
        labels: ['Em Atendimento', 'Sem Atendimento', 'Pendentes'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosEmAtendimentoGeral; ?>, <?php echo $alunosSemAtendimentoGeral; ?>, <?php echo $alunosPendentesGeral; ?>],
            backgroundColor: ['#0038bb', '#2d6df0', '#1aa78d']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 2. Escolas por UGE (Barras)
new Chart(document.getElementById('graficoEscolasUre'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsUres); ?>,
        datasets: [{
            label: 'Escolas',
            data: <?php echo json_encode($valoresEscolasUre); ?>,
            backgroundColor: ['#0038bb', '#2d6df0', '#1aa78d', '#7ab8ff', '#92e4d1']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 3. PAEs por URE (Barras)
new Chart(document.getElementById('graficoPaesUre'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsUres); ?>,
        datasets: [{
            label: 'PAEs Ativos',
            data: <?php echo json_encode($valoresPaesUre); ?>,
            backgroundColor: ['#0038bb', '#2d6df0', '#1aa78d', '#7ab8ff', '#92e4d1']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 4. Evolução de Alunos Cadastrados (Barras)
new Chart(document.getElementById('graficoEvolucao'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsHistorico); ?>,
        datasets: [{
            label: 'Alunos Cadastrados',
            data: <?php echo json_encode($valoresHistorico); ?>,
            backgroundColor: '#0038bb'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
<?php endif; ?>

<?php if ($userPerfil === 'DIRIGENTE'): ?>
// 1. Status de Atendimento dos Alunos na Regional (Barras)
new Chart(document.getElementById('graficoDirigenteStatus'), {
    type: 'bar',
    data: {
        labels: ['Em Atendimento', 'Sem Atendimento', 'Pendentes'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosEmAtendimentoUre; ?>, <?php echo $alunosSemAtendimentoUre; ?>, <?php echo $alunosPendentesUre; ?>],
            backgroundColor: ['#0038bb', '#1aa78d', '#7ab8ff']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 2. Alunos por Escola na Regional (Barras)
new Chart(document.getElementById('graficoDirigenteEscolas'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsEscolasUre); ?>,
        datasets: [{
            label: 'Alunos',
            data: <?php echo json_encode($valoresEscolasUre); ?>,
            backgroundColor: ['#0038bb', '#2d6df0', '#1aa78d', '#7ab8ff', '#92e4d1', '#d7f5ef']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 3. Distribuição de Deficiências na Regional (Barras)
new Chart(document.getElementById('graficoDirigenteDeficiencias'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsDef); ?>,
        datasets: [{
            label: 'Alunos',
            data: <?php echo json_encode($valoresDef); ?>,
            backgroundColor: ['#0038bb', '#2d6df0', '#1aa78d', '#7ab8ff', '#92e4d1']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

// 4. Evolução de Alunos na Regional (Barras)
new Chart(document.getElementById('graficoDirigenteEvolucao'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labelsHistUre); ?>,
        datasets: [{
            label: 'Alunos',
            data: <?php echo json_encode($valoresHistUre); ?>,
            backgroundColor: '#0038bb'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
<?php endif; ?>

<?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
new Chart(document.getElementById('graficoEscola'), {
    type: 'bar',
    data: {
        labels: ['Em atendimento', 'Sem atendimento', 'Pendentes'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosEmAtendimento; ?>, <?php echo $alunosSemAtendimento; ?>, <?php echo $alunosPendentes; ?>],
            backgroundColor: ['#0038bb', '#1aa78d', '#7ab8ff']
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
            backgroundColor: ['#7ab8ff', '#1aa78d', '#0038bb']
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
            backgroundColor: ['#0038bb', '#1aa78d', '#2d6df0', '#7ab8ff']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
new Chart(document.getElementById('graficoSupervisor'), {
    type: 'bar',
    data: {
        labels: ['Total de PAEs', 'PAEs em Atendimento', 'Associações Ativas', 'Relatórios Enviados'],
        datasets: [{
            label: 'Total',
            data: [<?php echo $totalPAEs; ?>, <?php echo $paesAtendendo; ?>, <?php echo $totalAssocAtivas; ?>, <?php echo $totalRelatorios; ?>],
            backgroundColor: ['#0038bb', '#1aa78d', '#2d6df0', '#7ab8ff']
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>

</body>
</html>
