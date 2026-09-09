<?php
// views/redefinir_senha.php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token inválido.");
}

$sql = "SELECT * FROM recuperacao_senha WHERE token = ? AND usado = 0 AND expira_em >= NOW()";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$recuperacao = $stmt->get_result()->fetch_assoc();

if (!$recuperacao) {
    die("Este link de recuperação é inválido ou já expirou.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEI - Redefinir Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body.page-redefinir {
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .redefinir-card {
            width: min(100%, 460px);
            padding: clamp(1.75rem, 4vw, 2.5rem);
            border: 1px solid var(--sigei-border);
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 18px 48px rgba(13, 71, 161, 0.12);
        }
    </style>
</head>
<body class="page-redefinir">
    <div class="redefinir-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold" style="color: var(--sigei-blue-900);">Nova Senha</h2>
            <p class="text-muted small">Digite o código de 6 dígitos enviado e defina sua nova senha de acesso.</p>
        </div>

        <form action="../controllers/processa_redefinir_senha.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="mb-3">
                <label for="codigo" class="form-label">Código de Verificação (6 dígitos)</label>
                <input type="text" name="codigo" id="codigo" class="form-control text-center fs-4 tracking-widest" maxlength="6" required placeholder="000000">
            </div>

            <div class="mb-4">
                <label for="nova_senha" class="form-label">Nova Senha</label>
                <input type="password" name="nova_senha" id="nova_senha" class="form-control" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Salvar Nova Senha</button>
        </form>
    </div>
</body>
</html>