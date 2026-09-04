<?php
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM recuperacao_senha WHERE token = ? AND data_expiracao > NOW()");
$stmt->execute([$token]);
$solicitacao = $stmt->fetch();

if (!$solicitacao) {
    die("Token inválido ou expirado. <a href='index.php'>Voltar</a>");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>SIGEI — Nova Senha</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; background-color: #ddeeff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
    .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    h2 { color: #0d47a1; margin-top: 0; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; color: #0d47a1; font-weight: bold; }
    input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    button { width: 100%; padding: 10px; background: #0d47a1; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Criar Nova Senha</h2>
    <form action="index.php?control=auth&action=salvar_nova_senha" method="POST">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="form-group">
        <label for="nova_senha">Digite a Nova Senha:</label>
        <input type="password" name="nova_senha" id="nova_senha" required minlength="6">
      </div>
      <button type="submit">Salvar Senha</button>
    </form>
  </div>
</body>
</html>