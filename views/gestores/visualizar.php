<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Dirigente não informado."));
    exit();
}

// 1. Dados do dirigente e da regional
$sql = "
    SELECT uu.*, u.nome AS ure_nome, u.uge AS ure_uge, u.endereco AS ure_endereco,
           u.telefone AS ure_telefone, u.email AS ure_email,
           (SELECT COUNT(*) FROM unidades_escolares ue WHERE ue.id_ure = u.id_ure) AS total_escolas
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    WHERE uu.id_usuario_ure = $id AND uu.setor IN ('ASURE', 'GABINETE')
";
$result = mysqli_query($conexao, $sql);
$dirigente = $result ? mysqli_fetch_assoc($result) : null;

if (!$dirigente) {
    header("Location: listar.php?erro=" . urlencode("Dirigente não encontrado."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Servidor - <?php echo htmlspecialchars($dirigente['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-gestores-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Servidor (ASURE)</h2>
        <div class="d-flex gap-2">
            <a href="editar.php?id=<?php echo $dirigente['id_usuario_ure']; ?>" class="btn btn-warning btn-sm">Editar Servidor</a>
            <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>

    <div class="row">
        <!-- Dados do Dirigente -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Informações Pessoais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nome</span>
                            <strong><?php echo htmlspecialchars($dirigente['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CPF</span>
                            <strong><?php echo htmlspecialchars(formatarCPF($dirigente['cpf'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Status</span>
                            <span>
                                <?php if ($dirigente['ativo'] == 1): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">E-mail</span>
                            <strong><?php echo htmlspecialchars($dirigente['email'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone</span>
                            <strong><?php echo htmlspecialchars(formatarTelefone($dirigente['telefone']) ?: 'Não informado'); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dados da URE -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Unidade Regional Jurisdicionada</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Código UGE</span>
                            <strong><?php echo htmlspecialchars($dirigente['ure_uge'] ?: 'N/D'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Regional</span>
                            <strong>
                                <a href="../ures/visualizar.php?id=<?php echo $dirigente['id_ure']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($dirigente['ure_nome']); ?>
                                </a>
                            </strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Endereço URE</span>
                            <strong><?php echo htmlspecialchars($dirigente['ure_endereco'] ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone URE</span>
                            <strong><?php echo htmlspecialchars(formatarTelefone($dirigente['ure_telefone']) ?: 'Não informado'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Escolas na Rede</span>
                            <strong><?php echo (int) $dirigente['total_escolas']; ?> escola(s)</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>
