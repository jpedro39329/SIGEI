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
    SELECT a.nome AS aluno_nome, e.nome AS escola_nome
    FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    WHERE ass.id_pae = $id_pae AND ass.ativo = 1
    ORDER BY a.nome
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];

$sqlRelatorios = "
    SELECT r.tipo, r.descricao, r.data_cadastro, a.nome AS aluno_nome
    FROM relatorios r
    JOIN associacoes ass ON r.id_associacao = ass.id_associacao
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    WHERE ass.id_pae = $id_pae
    ORDER BY r.data_cadastro DESC
";
$resultRelatorios = mysqli_query($conexao, $sqlRelatorios);
$relatorios = $resultRelatorios ? mysqli_fetch_all($resultRelatorios, MYSQLI_ASSOC) : [];
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
        <h2 class="mb-0">Detalhes do PAE</h2>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($pae['nome']); ?></h4>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($pae['empresa_nome'] ?? '-'); ?></p>
                </div>
                <?php if ($pae['ativo'] == 1): ?>
                    <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                    <span class="badge bg-danger">Inativo</span>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-2"><strong>CPF:</strong> <?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></div>
                <div class="col-md-6 mb-2"><strong>Telefone:</strong> <?php echo htmlspecialchars(formatarTelefone($pae['telefone']) ?: '-'); ?></div>
                <div class="col-md-6 mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($pae['email'] ?? '-'); ?></div>
                <div class="col-md-6 mb-2">
                   
                    
                       
                </div>
            </div>

            
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Alunos associados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nome do aluno</th><th>Escola onde esta</th></tr></thead>
                    <tbody>
                        <?php if (count($alunos) > 0): ?>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
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

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Relatorios do PAE</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Aluno</th><th>Tipo</th><th>Descricao</th><th>Data</th></tr></thead>
                    <tbody>
                        <?php if (count($relatorios) > 0): ?>
                            <?php foreach ($relatorios as $relatorio): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($relatorio['aluno_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($relatorio['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($relatorio['descricao']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($relatorio['data_cadastro'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">Nenhum relatorio cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
