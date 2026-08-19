<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Cuidador</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="page-cuidadores-cadastrar">

    <?php require("navbar.php"); ?>

        <!-- CONTEÚDO -->
        <div class="content">

            <div class="row justify-content-center">

                <div class="col-lg-8">

                    <div class="card card-form">

                        <div class="card-body p-4">

                            <h2 class="mb-4">
                                Cadastro de Cuidador
                            </h2>

                            <p class="text-muted mb-4">
                                Olá, <?php echo htmlspecialchars($userName); ?> — preencha os dados do novo cuidador.
                            </p>

                            <?php if (isset($_GET['erro'])): ?>

                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($_GET['erro']); ?>
                                </div>

                            <?php endif; ?>

                            <form action="../controllers/cuidadores_salvar.php" method="POST">

                                <div class="row">

                                    <!-- Nome -->
                                    <div class="col-md-12 mb-3">

                                        <label class="form-label">
                                            Nome Completo
                                        </label>

                                        <input
                                            type="text"
                                            name="nome"
                                            class="form-control"
                                            placeholder="Nome completo do cuidador"
                                            required
                                        >

                                    </div>

                                    <!-- CPF -->
                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">
                                            CPF
                                        </label>

                                        <input
                                            type="text"
                                            name="cpf"
                                            id="cpf"
                                            class="form-control"
                                            maxlength="14"
                                            placeholder="000.000.000-00"
                                            required
                                        >

                                    </div>

                                    <!-- Empresa -->
                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">
                                            Empresa Terceirizada
                                        </label>

                                        <input
                                            type="text"
                                            name="empresa"
                                            class="form-control"
                                            value="<?php echo htmlspecialchars($userName); ?>"
                                            readonly
                                            required
                                        >

                                    </div>

                                    <!-- Senha -->
                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">
                                            Senha
                                        </label>

                                        <div class="password-wrapper">

                                            <input
                                                type="password"
                                                name="senha"
                                                id="senha"
                                                class="form-control"
                                                placeholder="Senha de acesso"
                                                required
                                            >

                                            <button
                                                type="button"
                                                class="toggle-senha"
                                                onclick="toggleSenha('senha')"
                                            >
                                                Mostrar
                                            </button>

                                        </div>

                                    </div>

                                    <!-- Confirmar Senha -->
                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">
                                            Confirmar Senha
                                        </label>

                                        <div class="password-wrapper">

                                            <input
                                                type="password"
                                                name="confirmar_senha"
                                                id="confirmar_senha"
                                                class="form-control"
                                                placeholder="Confirme a senha"
                                                required
                                            >

                                            <button
                                                type="button"
                                                class="toggle-senha"
                                                onclick="toggleSenha('confirmar_senha')"
                                            >
                                                Mostrar
                                            </button>

                                        </div>

                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-12 mb-4">

                                        <label class="form-label">
                                            Status
                                        </label>

                                        <select name="ativo" class="form-select" required>
                                            <option value="1" selected>
                                                Ativo
                                            </option>

                                            <option value="0">
                                                Inativo
                                            </option>
                                        </select>

                                    </div>

                                </div>

                                <div class="d-flex gap-2">

                                    <button
                                        type="submit"
                                        class="btn btn-dark"
                                    >
                                        Cadastrar
                                    </button>

                                    <a
                                        href="cuidadores_listar.php"
                                        class="btn btn-secondary"
                                    >
                                        Voltar
                                    </a>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Máscara CPF
        const cpfInput = document.getElementById('cpf');

        cpfInput.addEventListener('input', function () {

            let value = cpfInput.value;

            value = value.replace(/\D/g, '');

            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

            cpfInput.value = value;

        });

        // Mostrar/Ocultar senha
        function toggleSenha(id) {

            const input = document.getElementById(id);
            const btn = input.nextElementSibling;

            if (input.type === 'password') {

                input.type = 'text';
                btn.textContent = 'Ocultar';

            } else {

                input.type = 'password';
                btn.textContent = 'Mostrar';

            }

        }

        // Validação das senhas
        document.querySelector('form').addEventListener('submit', function (e) {

            const senha = document.getElementById('senha').value;
            const confirmar = document.getElementById('confirmar_senha').value;

            if (senha !== confirmar) {

                e.preventDefault();

                alert('As senhas não coincidem. Por favor, verifique.');

            }

        });

    </script>

</body>

</html>
