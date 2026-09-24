<?php
require_once "../../config/init.php";

exigirLogin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Escola não informada."));
    exit();
}

// 1. Dados da escola
$sql = "
    SELECT ue.*, u.nome AS ure_nome, u.uge AS ure_uge
    FROM unidades_escolares ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    WHERE ue.id_ue = $id
";
$result = mysqli_query($conexao, $sql);
$escola = $result ? mysqli_fetch_assoc($result) : null;

if (!$escola) {
    header("Location: listar.php?erro=" . urlencode("Escola não encontrada."));
    exit();
}

// 2. Indicadores específicos desta escola
$totalAlunos = 0;
$resTot = $conexao->query("SELECT COUNT(*) as total FROM alunos WHERE id_ue = $id");
if ($resTot) $totalAlunos = (int) $resTot->fetch_assoc()['total'];

$alunosAtendidos = 0;
$resAtend = $conexao->query("
    SELECT COUNT(DISTINCT a.id_aluno) as total
    FROM alunos a
    JOIN associacoes ass ON a.id_aluno = ass.id_aluno AND ass.ativo = 1
    WHERE a.id_ue = $id AND a.status_aprovacao = 'APROVADO'
");
if ($resAtend) $alunosAtendidos = (int) $resAtend->fetch_assoc()['total'];

$alunosSemAtendimento = 0;
$resSem = $conexao->query("
    SELECT COUNT(*) as total
    FROM alunos a
    WHERE a.id_ue = $id
      AND a.status_aprovacao = 'APROVADO'
      AND NOT EXISTS (
          SELECT 1 FROM associacoes ass
          WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
      )
");
if ($resSem) $alunosSemAtendimento = (int) $resSem->fetch_assoc()['total'];

$alunosPendentes = 0;
$resPend = $conexao->query("SELECT COUNT(*) as total FROM alunos WHERE id_ue = $id AND status_aprovacao = 'PENDENTE'");
if ($resPend) $alunosPendentes = (int) $resPend->fetch_assoc()['total'];

// Total de PAEs vinculados aos alunos desta escola
$totalPaes = 0;
$resPaes = $conexao->query("
    SELECT COUNT(DISTINCT ass.id_pae) as total
    FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    WHERE a.id_ue = $id AND ass.ativo = 1
");
if ($resPaes) $totalPaes = (int) $resPaes->fetch_assoc()['total'];

// 3. Alunos matriculados na escola
$sqlAlunos = "
    SELECT a.*,
           (SELECT p.nome FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_nome
    FROM alunos a
    WHERE a.id_ue = $id
    ORDER BY a.nome ASC
";
$resAlunos = mysqli_query($conexao, $sqlAlunos);
$alunosEscola = $resAlunos ? mysqli_fetch_all($resAlunos, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Escola - <?php echo htmlspecialchars($escola['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-escolas-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes da Unidade Escolar</h2>
        <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
    </div>

    <div class="row">
        <!-- Indicadores / Gráfico da Escola -->
        <div class="col-md-5 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Indicadores de Inclusão</h5>
                    <canvas id="graficoEscolaAlunos" height="150"></canvas>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Resumo da Escola</h5>
                    <ul class="list-group list-group-flush">
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
                            <span class="text-muted">Solicitações Pendentes</span>
                            <span class="badge bg-warning text-dark"><?php echo $alunosPendentes; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">PAEs Atuando na Escola</span>
                            <strong><?php echo $totalPaes; ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Informações Cadastrais e Alunos -->
        <div class="col-md-7 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Informações Institucionais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Denominação</span>
                            <strong><?php echo htmlspecialchars($escola['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Código CIE</span>
                            <strong><code><?php echo htmlspecialchars($escola['cie']); ?></code></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Código UA</span>
                            <strong><?php echo htmlspecialchars($escola['ua'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Modalidade</span>
                            <strong><?php echo htmlspecialchars($escola['modalidade']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Unidade Regional</span>
                            <strong>
                                <a href="../ures/visualizar.php?id=<?php echo $escola['id_ure']; ?>" class="text-decoration-none">
                                    <?php echo ($escola['ure_uge'] ? htmlspecialchars($escola['ure_uge']) . ' - ' : '') . htmlspecialchars($escola['ure_nome']); ?>
                                </a>
                            </strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Horário</span>
                            <span><?php echo htmlspecialchars($escola['horario_funcionamento'] ?: 'Não informado'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Endereço</span>
                            <span class="text-end">
                                <?php
                                $end = array_filter([
                                    $escola['endereco'],
                                    $escola['numero'],
                                    $escola['bairro'],
                                    $escola['municipio'],
                                    $escola['cep'] ? 'CEP ' . $escola['cep'] : null
                                ]);
                                echo htmlspecialchars(implode(', ', $end) ?: 'Não informado');
                                ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Contatos</span>
                            <span><?php echo htmlspecialchars(formatarTelefone($escola['telefone']) ?: ($escola['email'] ?: 'Não informado')); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Lista de Alunos -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Alunos Registrados nesta Unidade (<?php echo count($alunosEscola); ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>RA</th>
                                    <th>Status</th>
                                    <th>PAE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alunosEscola) > 0): ?>
                                    <?php foreach ($alunosEscola as $a): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($a['nome']); ?></strong></td>
                                            <td><code><?php echo htmlspecialchars($a['ra'] ?: '-'); ?></code></td>
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
                                                    <span class="badge bg-info-subtle text-info-emphasis"><?php echo htmlspecialchars($a['pae_nome']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Sem PAE</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Nenhum aluno registrado.</td>
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
new Chart(document.getElementById('graficoEscolaAlunos'), {
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
