<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Supervisor não informado."));
    exit();
}

// 1. Dados do supervisor com empresa e UREs atendidas pela empresa
$sql = "
    SELECT us.*, e.nome AS empresa_nome, e.cnpj AS empresa_cnpj, e.numero_contrato,
           (SELECT GROUP_CONCAT(u.nome SEPARATOR ', ')
            FROM empresa_ure eu
            JOIN unidades_regionais u ON eu.id_ure = u.id_ure
            WHERE eu.id_empresa = us.id_empresa) AS ures_atendidas
    FROM usuarios_supervisor us
    JOIN empresas e ON us.id_empresa = e.id_empresa
    WHERE us.id_usuario_supervisor = $id
";
$result = mysqli_query($conexao, $sql);
$supervisor = $result ? mysqli_fetch_assoc($result) : null;

if (!$supervisor) {
    header("Location: listar.php?erro=" . urlencode("Supervisor não encontrado."));
    exit();
}

// 2. PAEs supervisionados (da mesma empresa)
$sqlPaes = "
    SELECT * FROM usuarios_pae
    WHERE id_empresa = {$supervisor['id_empresa']}
    ORDER BY ativo DESC, nome ASC
";
$resPaes = mysqli_query($conexao, $sqlPaes);
$paes = $resPaes ? mysqli_fetch_all($resPaes, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Supervisor - <?php echo htmlspecialchars($supervisor['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-supervisores-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Supervisor de Licitações e Contratos</h2>
        <div class="d-flex gap-2">
            <a href="editar.php?id=<?php echo $supervisor['id_usuario_supervisor']; ?>" class="btn btn-warning btn-sm">Editar Supervisor</a>
            <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>

    <div class="row">
        <!-- Dados do Supervisor -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Informações Pessoais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nome</span>
                            <strong><?php echo htmlspecialchars($supervisor['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CPF</span>
                            <strong><?php echo htmlspecialchars(formatarCPF($supervisor['cpf'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Status</span>
                            <span>
                                <?php if ($supervisor['ativo'] == 1): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">E-mail</span>
                            <strong><?php echo htmlspecialchars($supervisor['email'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone</span>
                            <strong><?php echo htmlspecialchars(formatarTelefone($supervisor['telefone']) ?: 'Não informado'); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dados da Empresa Vinculada -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Empresa Contratada</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Razão Social</span>
                            <strong>
                                <a href="../empresas/visualizar.php?id=<?php echo $supervisor['id_empresa']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($supervisor['empresa_nome']); ?>
                                </a>
                            </strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CNPJ</span>
                            <strong><?php echo htmlspecialchars(formatarCNPJ($supervisor['empresa_cnpj'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nº Contrato</span>
                            <strong><?php echo htmlspecialchars($supervisor['numero_contrato'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">UREs Atendidas</span>
                            <span class="text-end"><?php echo htmlspecialchars($supervisor['ures_atendidas'] ?: 'Nenhuma'); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- PAEs da Empresa -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Profissionais de Apoio (PAEs) da Empresa (<?php echo count($paes); ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($paes) > 0): ?>
                                    <?php foreach ($paes as $p): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($p['nome']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(formatarCPF($p['cpf'])); ?></td>
                                            <td><?php echo htmlspecialchars($p['email'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars(formatarTelefone($p['telefone']) ?: '-'); ?></td>
                                            <td>
                                                <?php if ($p['ativo'] == 1): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Nenhum PAE cadastrado para esta empresa.</td>
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

</body>
</html>
