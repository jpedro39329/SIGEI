<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("URE não informada."));
    exit();
}

$stmt = $conexao->prepare("SELECT * FROM unidades_regionais WHERE id_ure = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ure = $stmt->get_result()->fetch_assoc();

if (!$ure) {
    header("Location: listar.php?erro=" . urlencode("URE não encontrada."));
    exit();
}

$totalEscolas = 0;
$resEscolas = $conexao->query("SELECT COUNT(*) as total FROM unidades_escolares WHERE id_ure = $id");
if ($resEscolas) $totalEscolas = (int) $resEscolas->fetch_assoc()['total'];

$totalAlunos = 0;
$resAlunos = $conexao->query("
    SELECT COUNT(*) as total
    FROM alunos a
    JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE ue.id_ure = $id
");
if ($resAlunos) $totalAlunos = (int) $resAlunos->fetch_assoc()['total'];

$alunosAtendidos = 0;
$resAtend = $conexao->query("
    SELECT COUNT(DISTINCT a.id_aluno) as total
    FROM alunos a
    JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    JOIN associacoes ass ON a.id_aluno = ass.id_aluno AND ass.ativo = 1
    WHERE ue.id_ure = $id AND a.status_aprovacao = 'APROVADO'
");
if ($resAtend) $alunosAtendidos = (int) $resAtend->fetch_assoc()['total'];

$alunosSemAtendimento = 0;
$resSemAtend = $conexao->query("
    SELECT COUNT(*) as total
    FROM alunos a
    JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE ue.id_ure = $id
      AND a.status_aprovacao = 'APROVADO'
      AND NOT EXISTS (
          SELECT 1 FROM associacoes ass
          WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
      )
");
if ($resSemAtend) $alunosSemAtendimento = (int) $resSemAtend->fetch_assoc()['total'];

$alunosPendentes = 0;
$resPend = $conexao->query("
    SELECT COUNT(*) as total
    FROM alunos a
    JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE ue.id_ure = $id AND a.status_aprovacao = 'PENDENTE'
");
if ($resPend) $alunosPendentes = (int) $resPend->fetch_assoc()['total'];

$totalPaes = 0;
$resPaes = $conexao->query("
    SELECT COUNT(DISTINCT p.id_pae) as total
    FROM usuarios_pae p
    JOIN empresa_ure eu ON p.id_empresa = eu.id_empresa
    WHERE eu.id_ure = $id AND p.ativo = 1
");
if ($resPaes) $totalPaes = (int) $resPaes->fetch_assoc()['total'];

// Lista de Escolas da URE
$sqlListaEscolas = "
    SELECT ue.*,
           (SELECT COUNT(*) FROM alunos a WHERE a.id_ue = ue.id_ue) AS qtd_alunos
    FROM unidades_escolares ue
    WHERE ue.id_ure = $id
    ORDER BY ue.nome ASC
";
$resListaEscolas = $conexao->query($sqlListaEscolas);
$escolasUre = $resListaEscolas ? $resListaEscolas->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da URE - <?php echo htmlspecialchars($ure['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-ures-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes da Unidade Regional (URE)</h2>
        <div class="d-flex gap-2">
            <a href="editar.php?id=<?php echo $ure['id_ure']; ?>" class="btn btn-warning btn-sm">Editar URE</a>
            <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>

    <div class="row">
        <!-- Indicadores / Gráfico da URE em Barras -->
        <div class="col-md-5 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Indicadores de Inclusão</h5>
                    <canvas id="graficoUreAlunos" height="150"></canvas>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Resumo Geral</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Escolas Vinculadas</span>
                            <strong><?php echo $totalEscolas; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Total de Alunos</span>
                            <strong><?php echo $totalAlunos; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Alunos em Atendimento</span>
                            <span class="badge bg-success"><?php echo $alunosAtendidos; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Alunos Sem Atendimento</span>
                            <span class="badge bg-info text-dark"><?php echo $alunosSemAtendimento; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">PAEs Ativos na Regional</span>
                            <strong><?php echo $totalPaes; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dados Cadastrais e Escolas -->
        <div class="col-md-7 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Dados Cadastrais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Código UGE</span>
                            <strong><span class="badge bg-secondary"><?php echo htmlspecialchars($ure['uge'] ?: 'N/D'); ?></span></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Denominação</span>
                            <strong><?php echo htmlspecialchars($ure['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Endereço</span>
                            <strong><?php echo htmlspecialchars($ure['endereco'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone</span>
                            <strong><?php echo htmlspecialchars($ure['telefone'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">E-mail</span>
                            <strong><?php echo htmlspecialchars($ure['email'] ?: 'Não informado'); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Escolas Vinculadas -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Escolas Jurisdicionadas (<?php echo count($escolasUre); ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>CIE</th>
                                    <th>UA</th>
                                    <th>Denominação</th>
                                    <th>Modalidade</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($escolasUre) > 0): ?>
                                    <?php foreach ($escolasUre as $esc): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($esc['cie']); ?></code></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($esc['ua'] ?: '-'); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($esc['nome']); ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo $esc['modalidade'] === 'PEI' ? 'bg-primary' : 'bg-secondary'; ?>">
                                                    <?php echo htmlspecialchars($esc['modalidade']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../escolas/visualizar.php?id=<?php echo $esc['id_ue']; ?>" class="btn btn-sm btn-info">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Nenhuma escola vinculada.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('graficoUreAlunos'), {
    type: 'bar',
    data: {
        labels: ['Em Atendimento', 'Sem Atendimento', 'Pendentes'],
        datasets: [{
            label: 'Alunos',
            data: [<?php echo $alunosAtendidos; ?>, <?php echo $alunosSemAtendimento; ?>, <?php echo $alunosPendentes; ?>],
            backgroundColor: ['#198754', '#0dcaf0', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>

</body>
</html>
