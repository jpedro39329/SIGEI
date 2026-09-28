<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Servidor não informado."));
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

$stmt = $conexao->prepare("
    SELECT uu.*, u.nome AS ure_nome, u.uge AS ure_uge, u.endereco AS ure_endereco, u.telefone AS ure_telefone
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    WHERE uu.id_usuario_ure = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$servidor = $stmt->get_result()->fetch_assoc();

if (!$servidor) {
    header("Location: listar.php?erro=" . urlencode("Servidor não encontrado."));
    exit();
}

if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0 && (int)$servidor['id_ure'] !== $idUreUsuario) {
    header("Location: listar.php?erro=" . urlencode("Você não tem permissão para visualizar servidores de outra regional."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Servidor - <?php echo htmlspecialchars($servidor['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-setores-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Servidor da Regional</h2>
        <div class="d-flex gap-2">
            <a href="editar.php?id=<?php echo $servidor['id_usuario_ure']; ?>" class="btn btn-warning btn-sm">Editar Servidor</a>
            <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>

    <div class="row">
        <!-- Informações Pessoais e Cargo -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Informações Pessoais e Funcionais</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Nome Completo</span>
                            <strong><?php echo htmlspecialchars($servidor['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CPF</span>
                            <strong class="font-monospace"><?php echo htmlspecialchars(formatarCPF($servidor['cpf'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Setor de Atuação</span>
                            <span>
                                <?php if ($servidor['setor'] === 'ASURE' || $servidor['setor'] === 'GABINETE'): ?>
                                    Assistência Técnica (ASURE)
                                <?php elseif ($servidor['setor'] === 'SEFISC'): ?>
                                    Seção de Fiscalização (SEFISC)
                                <?php elseif ($servidor['setor'] === 'EDU_ESPECIAL'): ?>
                                    Educação Especial
                                <?php else: ?>
                                    <?php echo htmlspecialchars($servidor['setor']); ?>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Cargo / Função</span>
                            <strong><?php echo htmlspecialchars($servidor['cargo'] ?: '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Status</span>
                            <span>
                                <?php if ($servidor['ativo'] == 1): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">E-mail</span>
                            <span><?php echo htmlspecialchars($servidor['email'] ?: 'Não informado'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone</span>
                            <span><?php echo htmlspecialchars(formatarTelefone($servidor['telefone']) ?: 'Não informado'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Data de Cadastro</span>
                            <span><?php echo !empty($servidor['data_cadastro']) ? date('d/m/Y H:i', strtotime($servidor['data_cadastro'])) : '-'; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dados da URE Vinculada -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Unidade Regional Vinculada</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Código UGE</span>
                            <strong><?php echo htmlspecialchars($servidor['ure_uge'] ?: 'N/D'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Denominação</span>
                            <strong><?php echo htmlspecialchars($servidor['ure_nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Endereço da Regional</span>
                            <span class="text-end"><?php echo htmlspecialchars($servidor['ure_endereco'] ?: 'Não informado'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Telefone URE</span>
                            <span><?php echo htmlspecialchars(formatarTelefone($servidor['ure_telefone']) ?: 'Não informado'); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>

