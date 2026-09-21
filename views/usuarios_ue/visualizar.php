<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$id_usuario_ue = (int) ($_GET['id'] ?? 0);

if ($id_usuario_ue <= 0) {
    die("Usuário não informado.");
}

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$sql = "
    SELECT uue.*, ue.nome AS escola_nome, ue.cie, ue.id_ure, u.nome AS ure_nome
    FROM usuarios_ue uue
    JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    WHERE uue.id_usuario_ue = $id_usuario_ue
";
$result = mysqli_query($conexao, $sql);
$usuario = $result ? mysqli_fetch_assoc($result) : null;

if (!$usuario) {
    die("Usuário não encontrado.");
}

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0 && (int)$usuario['id_ure'] !== $idUreUsuario) {
    die("Acesso negado: este usuário pertence a outra Diretoria Regional.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Usuário — SEFISC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-usuarios-ue-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Usuário da Escola</h2>
        <div class="d-flex gap-2">
            <a href="editar.php?id=<?php echo $usuario['id_usuario_ue']; ?>" class="btn btn-warning">Editar</a>
            <a href="listar.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($usuario['escola_nome']); ?> (CIE: <?php echo htmlspecialchars($usuario['cie']); ?>)</p>
                </div>
                <?php if ($usuario['ativo'] == 1): ?>
                    <span class="badge bg-success">Ativo</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inativo</span>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>CPF:</strong> <?php echo htmlspecialchars(formatarCPF($usuario['cpf'])); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Diretoria Regional (URE):</strong> <?php echo htmlspecialchars($usuario['ure_nome']); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>E-mail Institucional:</strong> <?php echo htmlspecialchars($usuario['email'] ?? '-'); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Telefone:</strong> <?php echo htmlspecialchars($usuario['telefone'] ?? '-'); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Data de Cadastro:</strong> <?php echo !empty($usuario['data_cadastro']) ? date('d/m/Y H:i', strtotime($usuario['data_cadastro'])) : '-'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

