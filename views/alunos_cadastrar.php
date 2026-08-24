<?php
session_start();
include("../config/perfil_functions.php");

exigirPerfil(array('USUARIO_ESCOLA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Solicitacao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-alunos-cadastrar">

<?php require("navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Enviar Solicitação para Educação Especial</h2>
                    <p class="text-muted mb-4">
                        Ola, <?php echo htmlspecialchars($userName); ?> - preencha os dados do aluno. O status sera PENDENTE.
                    </p>

                    <form action="../controllers/alunos_salvar.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">CPF</label>
                                <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">RA</label>
                                <input type="text" name="ra" class="form-control" placeholder="Registro do aluno">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Data de Nascimento</label>
                                <input type="date" name="data_nascimento" class="form-control" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Deficiencia</label>
                                <input type="text" name="deficiencia" class="form-control" placeholder="Ex.: TEA - Transtorno do Espectro Autista" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do Responsavel</label>
                                <input type="text" name="nome_responsavel" class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF do Responsavel</label>
                                <input type="text" name="cpf_responsavel" id="cpf_responsavel" class="form-control" maxlength="14" placeholder="000.000.000-00">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Laudos (PDF, JPG ou PNG)</label>
                                <input type="file" name="laudos[]" class="form-control" accept="application/pdf,image/jpeg,image/png" multiple>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Foto do Aluno (opcional)</label>
                                <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png">
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label">Observacoes / Cuidados Necessarios</label>
                                <textarea name="observacoes" rows="4" class="form-control"></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Enviar solicitacao</button>
                            <a href="alunos_listar.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function aplicarMascaraCPF(input) {
    input.addEventListener('input', function() {
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    });
}

aplicarMascaraCPF(document.getElementById('cpf'));
aplicarMascaraCPF(document.getElementById('cpf_responsavel'));
</script>

</body>
</html>
