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
    <title>SIGEI - Nova Senha</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <h2>Redefinir Senha</h2>
        <form action="../controllers/processa_redefinir_senha.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <label for="codigo">Código de Verificação (6 dígitos):</label>
            <input type="text" name="codigo" id="codigo" maxlength="6" required placeholder="Digite o código recebido">

            <label for="nova_senha">Nova Senha:</label>
            <input type="password" name="nova_senha" id="nova_senha" required>
            
            <button type="submit">Salvar Nova Senha</button>
        </form>
    </div>
</body>
</html>