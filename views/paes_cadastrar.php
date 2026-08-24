<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

exigirPerfil(array('USUARIO_EMPRESA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar PAE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-paes-cadastrar">

<?php require("navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Profissional de Apoio Escolar</h2>
                    <p class="text-muted mb-4">Ola, <?php echo htmlspecialchars($userName); ?> - preencha os dados do PAE.</p>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../controllers/paes_salvar.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF</label>
                                <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select" required>
                                    <option value="1" selected>Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label">Contrato</label>
                                <input type="file" name="contrato_arquivo" class="form-control" accept="application/pdf,image/jpeg,image/png">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Cadastrar</button>
                            <a href="paes_listar.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('cpf').addEventListener('input', function () {
    let value = this.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    this.value = value;
});

document.querySelector('form').addEventListener('submit', function (e) {
    if (document.getElementById('senha').value !== document.getElementById('confirmar_senha').value) {
        e.preventDefault();
        alert('As senhas nao coincidem.');
    }
});
</script>

</body>
</html>
