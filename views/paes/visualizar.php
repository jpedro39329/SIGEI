<?php
require_once "../../config/init.php";

exigirLogin();

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$id_pae = (int) ($_GET['id'] ?? 0);

$sql = "
    SELECT p.*, e.nome AS empresa_nome
    FROM usuarios_pae p
    LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
    WHERE p.id_pae = $id_pae
";
$result = mysqli_query($conexao, $sql);
$pae = $result ? mysqli_fetch_assoc($result) : null;

if (!$pae) {
    die("PAE não encontrado.");
}

if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    if ((int) $pae['id_empresa'] !== $idEmpresa) {
        die("Acesso negado.");
    }
}

$sqlAlunos = "
    SELECT a.nome AS aluno_nome, a.cpf AS aluno_cpf, e.nome AS escola_nome
    FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    WHERE ass.id_pae = $id_pae AND ass.ativo = 1
    ORDER BY a.nome
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do PAE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-paes-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Profissional de Apoio Escolar</h2>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </div>

    <!-- Informações do PAE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Informações do Profissional</h5>
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Nome</span>
                    <span><?php echo htmlspecialchars($pae['nome']); ?></span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">CPF</span>
                    <span><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Empresa</span>
                    <span><?php echo htmlspecialchars($pae['empresa_nome'] ?? '-'); ?></span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Data de Cadastro</span>
                    <span><?php echo !empty($pae['data_cadastro']) ? date('d/m/Y', strtotime($pae['data_cadastro'])) : '-'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alunos associados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Alunos Associados</h5>
            <?php if (count($alunos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Escola</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($aluno['aluno_cpf'])); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Nenhum aluno associado a este PAE.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>

