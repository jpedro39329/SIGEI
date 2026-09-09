<?php
// views/esqueci_senha.php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEI - Recuperar Senha</title>
    <!-- Inclua o Bootstrap para manter a compatibilidade com as classes utilitárias -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Estilo específico centralizado idêntico ao padrão de autenticação */
        body.page-recuperacao {
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .recuperacao-card {
            width: min(100%, 460px);
            padding: clamp(1.75rem, 4vw, 2.5rem);
            border: 1px solid var(--sigei-border);
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 18px 48px rgba(13, 71, 161, 0.12);
        }
    </style>
</head>
<body class="page-recuperacao">
    <div class="recuperacao-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold" style="color: var(--sigei-blue-900);">Recuperar Senha</h2>
            <p class="text-muted small">Informe seu e-mail cadastrado para receber as instruções de redefinição.</p>
        </div>

        <form action="../controllers/processa_esqueci_senha.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail Cadastrado</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="seu.email@exemplo.com">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">Enviar Instruções</button>
            
            <div class="text-center">
                <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--sigei-blue-700);">← Voltar para o Login</a>
            </div>
        </form>
    </div>
</body>
</html>