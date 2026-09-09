<?php
// views/redefinir_senha.php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token inválido.");
}

// Verifica se o token existe, não foi usado e está dentro da validade[cite: 3]
$sql = "SELECT * FROM recuperacao_senha WHERE token = :token AND usado = 0 AND expira_em >= NOW()";
$stmt = $pdo->prepare($sql);
$stmt->execute(['token' => $token]);
$recuperacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recuperacao) {
    die("Este link de recuperação é inválido ou já expirou.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SIGEI - Nova Senha</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <h2>Redefinir Senha</h2>
        <form action="../controllers/processa_redefinir_senha.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <label for="nova_senha">Nova Senha:</label>
            <input type="password" name="nova_senha" id="nova_senha" required>
            
            <button type="submit">Salvar Nova Senha</button>
        </form>
    </div>
</body>
</html>