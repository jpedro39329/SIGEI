<?php
require_once "../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$whereAlunoUre = "";
$wherePaeEmpresa = "";
$whereAssocEmpresa = "";

if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresaUsuario);
    if (!empty($uresAtendidas)) {
        $uresList = implode(',', $uresAtendidas);
        $whereAlunoUre = "AND e.id_ure IN ($uresList)";
    } else {
        $whereAlunoUre = "AND 1=0";
    }
    $wherePaeEmpresa = "AND p.id_empresa = $idEmpresaUsuario";
    $whereAssocEmpresa = "AND p.id_empresa = $idEmpresaUsuario";
}

// Lista alunos APROVADOS que ainda podem receber PAE (menos de 3)
$sqlAlunos = "
    SELECT a.id_aluno, a.nome, e.nome AS escola_nome,
           (SELECT COUNT(*) FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1) AS qtd_paes
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    WHERE a.status_aprovacao = 'APROVADO'
    $whereAlunoUre
    ORDER BY a.nome
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];

// Lista PAEs ativos com menos de 3 alunos
$sqlPAEs = "
    SELECT p.id_pae, p.nome, e.nome AS empresa_nome,
           (SELECT COUNT(*) FROM associacoes ass WHERE ass.id_pae = p.id_pae AND ass.ativo = 1) AS qtd_alunos
    FROM usuarios_pae p
    LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
    WHERE p.ativo = 1
    $wherePaeEmpresa
    ORDER BY p.nome
";
$resultPAEs = mysqli_query($conexao, $sqlPAEs);
$paes = $resultPAEs ? mysqli_fetch_all($resultPAEs, MYSQLI_ASSOC) : [];

// Lista associações ativas
$sqlAssociacoes = "
    SELECT ass.id_associacao, a.nome AS aluno_nome, p.nome AS pae_nome,
           ass.data_inicio, e.nome AS escola_nome
    FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    WHERE ass.ativo = 1
    $whereAssocEmpresa
    ORDER BY ass.data_inicio DESC
";
$resultAssociacoes = mysqli_query($conexao, $sqlAssociacoes);
$associacoes = $resultAssociacoes ? mysqli_fetch_all($resultAssociacoes, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-associacoes">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Associações PAE ↔ Aluno</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — associe PAEs a alunos aprovados (máximo 3 alunos por PAE).</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Associação criada com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'removido'): ?>
        <div class="alert alert-warning">Associação desativada.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Nova Associação</h5>
            <form action="../controllers/alunos_associar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Aluno (aprovado, sem 3 PAEs)</label>
                        <select name="id_aluno" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <?php if ($aluno['qtd_paes'] < 3): ?>
                                    <option value="<?php echo $aluno['id_aluno']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome']); ?> (<?php echo $aluno['qtd_paes']; ?>/3 PAEs)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">PAE (ativo, com menos de 3 alunos)</label>
                        <select name="id_pae" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($paes as $pae): ?>
                                <?php if ($pae['qtd_alunos'] < 3): ?>
                                    <option value="<?php echo $pae['id_pae']; ?>">
                                        <?php echo htmlspecialchars($pae['nome']); ?> (<?php echo $pae['qtd_alunos']; ?>/3 alunos)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Data de Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-dark">Associar</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Associações Ativas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Escola</th>
                            <th>PAE</th>
                            <th>Data Início</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($associacoes) > 0): ?>
                            <?php foreach ($associacoes as $associacao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($associacao['aluno_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($associacao['escola_nome'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($associacao['pae_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($associacao['data_inicio'])); ?></td>
                                    <td>
                                        <form action="../controllers/alunos_desassociar.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja desassociar este PAE do aluno?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                            <input type="hidden" name="id_associacao" value="<?php echo $associacao['id_associacao']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Desassociar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma associação ativa.</td>
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