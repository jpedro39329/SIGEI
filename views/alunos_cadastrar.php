<?php

session_start();

if(!isset($_SESSION['user_id'])){
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

    <title>Cadastrar Aluno</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>
<body class="page-alunos-cadastrar">

<div class="d-flex">

    <!-- SIDEBAR -->   

    <?php require("navbar.php"); ?>

    <!-- CONTEÚDO -->
    <div class="content">

        <div class="row justify-content-center">

            <div class="col-lg-8">

                <div class="card card-form">

                    <div class="card-body p-4">

                        <h2 class="mb-4">
                            Cadastro de Aluno
                        </h2>

                        <form action="../controllers/alunos_salvar.php" method="POST">

                            <div class="row">

                                <div class="col-md-12 mb-3">

                                    <label class="form-label">
                                        Nome Completo
                                    </label>

                                    <input 
                                        type="text" 
                                        name="nome"
                                        class="form-control"
                                        required
                                    >

                                </div>





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
                                        required
                                    >

                                </div>





                                <div class="col-md-6 mb-3">

                                    <label class="form-label">
                                        Data de Nascimento
                                    </label>

                                    <input 
                                        type="date"
                                        name="data_nascimento"
                                        class="form-control"
                                        required
                                    >

                                </div>





                                <div class="col-md-12 mb-3">

                                    <label class="form-label">
                                        Deficiência
                                    </label>

                                    <input 
                                        type="text"
                                        name="deficiencia"
                                        class="form-control"
                                        required
                                    >

                                </div>





                                <div class="col-md-12 mb-4">

                                    <label class="form-label">
                                        Observações
                                    </label>

                                    <textarea 
                                        name="observacoes"
                                        rows="4"
                                        class="form-control"
                                    ></textarea>

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
                                    href="alunos_listar.php"
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

</div>





<script>

    const cpfInput = document.getElementById('cpf');

    cpfInput.addEventListener('input', function(){

        let value = cpfInput.value;

        value = value.replace(/\D/g, '');

        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

        cpfInput.value = value;

    });

</script>

</body>
</html>
