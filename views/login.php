<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light page-login">

    <div class="container d-flex justify-content-center align-items-center vh-100">

        <div class="card shadow-sm p-4 login-card">
            
            <h2 class="text-center mb-4">Login</h2>

            <form action="../controllers/login.php" method="post">

                <div class="mb-3">
                    <label for="cpf" class="form-label">CPF</label>
                    <input 
                        type="text" 
                        id="cpf" 
                        name="cpf" 
                        class="form-control"
                        placeholder="000.000.000-00"
                     maxlength="14"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        class="form-control"
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">
                        Entrar
                    </button>
                </div>

                <div class="text-center">
                    <button 
                        type="button" 
                        class="btn btn-link text-decoration-none p-0"
                    >
                        Esqueci minha senha
                    </button>
                </div>

                <div class="text-center">
                    <button 
                        type="button" 
                        class="btn btn-link text-decoration-none p-0"
                        onclick="window.location.href='../painelperfis.html';"
                    >
                        Voltar
                    </button>
                </div>

            </form>

        </div>

    </div>
 
<script>
    const cpfInput = document.getElementById('cpf');

    cpfInput.addEventListener('input', function () {
        let value = this.value.replace(/\D/g, '');

        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

        this.value = value;
    });
</script>
</body>
</html>
