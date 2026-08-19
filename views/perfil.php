<?php
session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId     = $_SESSION['user_id'];
$userName   = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];

// Dados completos do usuário logado
$sql = "SELECT * FROM usuarios WHERE id_usuario = $userId";
$result = mysqli_query($conexao, $sql);
$usuario = mysqli_fetch_assoc($result);

if (!$usuario) {
    die("Usuário não encontrado.");
}

// Formata o CPF (gravado sem separadores)
$cpf = preg_replace('/\D/', '', $usuario['cpf'] ?? '');
if (strlen($cpf) === 11) {
    $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

$homePage = ($userPerfil === 'ADMIN') ? 'dashboard_admin.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-perfil">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="card profile-card">
        <div class="card-body p-4">

            <h2 class="mb-4">👤 Meu Perfil</h2>

            <p class="text-muted">
                Olá, <?php echo htmlspecialchars($userName); ?> — estas são as informações do seu usuário.
            </p>

            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Nome</span>
                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">CPF</span>
                    <strong><?php echo htmlspecialchars($cpf); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Perfil</span>
                    <strong><?php echo htmlspecialchars($usuario['perfil']); ?></strong>
                </li>
                <?php if (!empty($usuario['empresa'])): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Empresa</span>
                    <strong><?php echo htmlspecialchars($usuario['empresa']); ?></strong>
                </li>
                <?php endif; ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Usuário desde</span>
                    <strong><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></strong>
                </li>
            </ul>

            <div class="d-grid gap-2 mt-4">
                <a href="<?php echo $homePage; ?>" class="btn btn-secondary">Voltar ao painel</a>
                <a href="../controllers/logout.php" class="btn btn-danger">Sair</a>
            </div>

        </div>
    </div>

</div>

</body>
</html>
