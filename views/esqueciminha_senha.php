<?php
// views/esqueci_senha.php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SIGEI - Recuperar Senha</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <h2>Recuperação de Senha</h2>
        <form action="../controllers/processa_esqueci_senha.php" method="POST">
            <label for="email">Informe seu e-mail cadastrado:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Enviar Instruções</button>
        </form>
        <p><a href="../views/login.php">Voltar para o Login</a></p>
    </div>
</body>
</html>