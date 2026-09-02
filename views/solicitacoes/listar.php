<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

if ($idUreUsuario <= 0 && $userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

// Filtra alunos pendentes da URE do usuário
if ($idUreUsuario > 0) {
    $where = "WHERE a.status_aprovacao = 'PENDENTE' AND ue.id_ure = $idUreUsuario";
} else {
    $where = "WHERE a.status_aprovacao = 'PENDENTE'";
}

$sql = "
    SELECT a.*, ue.nome AS escola_nome
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    $where
    ORDER BY a.data_cadastro ASC
";
$result = mysqli_query($conexao, $sql);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-solicitacoes">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Solicitações</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — alunos aguardando análise.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Status do aluno atualizado com sucesso!</div>
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
                            <th>Deficiência</th>
                            <th>Escola</th>
                            <th>Data da Solicitação</th>
                            <th>Ações</th>
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
                                    <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                    <td><?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y', strtotime($aluno['data_cadastro'])) : '-'; ?></td>
                                    <td>
                                        <a href="analisar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info">Analisar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhum aluno pendente de análise.</td>
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