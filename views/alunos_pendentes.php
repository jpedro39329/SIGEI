<?php
require_once "../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEscola = idEscolaUsuario($conexao, $userId);

$sql = "
    SELECT id_aluno, nome, cpf, ra, descricao_deficiencia,
           status_aprovacao, motivo_reprovacao, data_cadastro
    FROM alunos
    WHERE id_escola = $idEscola
      AND status_aprovacao IN ('PENDENTE', 'REPROVADO')
    ORDER BY data_cadastro DESC
";

$result = mysqli_query($conexao, $sql);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos Pendentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-alunos-pendentes">

<?php require("navbar.php"); ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Solicitacoes Pendentes</h2>
            <p class="text-muted">Ola, <?php echo htmlspecialchars($userName); ?> - acompanhe solicitacoes pendentes e reprovadas.</p>
        </div>
        <a href="alunos_cadastrar.php" class="btn btn-primary">Nova solicitacao</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'reenviado'): ?>
        <div class="alert alert-success">Solicitacao reenviada para analise.</div>
    <?php endif; ?>

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
                            <th>Status</th>
                            <th>Motivo</th>
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
                                    <td>
                                        <?php if ($aluno['status_aprovacao'] == 'PENDENTE'): ?>
                                            <span class="badge bg-warning text-dark">Pendente</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Reprovado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($aluno['motivo_reprovacao'] ?? '-'); ?></td>
                                    <td>
                                        <a href="alunos_visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Ver</a>

                                        <?php if ($aluno['status_aprovacao'] == 'REPROVADO'): ?>
                                            <form action="../controllers/alunos_reenviar.php" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                                <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Reenviar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhuma solicitacao pendente ou reprovada.</td>
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
