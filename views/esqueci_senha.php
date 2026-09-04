<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SIGEI - Recuperar Senha</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Recuperar Senha</h2>
        <form action="index.php?control=auth&action=esqueci_senha" method="POST">
            <div class="form-group">
                <label for="email">Digite o e-mail cadastrado no SIGEI:</label>
                <input type="email" name="email" id="email" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Enviar Link</button>
        </form>
    </div>
</body>
</html>