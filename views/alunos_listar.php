<?php
session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = $_SESSION['user_id'];


if (!in_array($userPerfil, ['UNIDADE_ESCOLAR', 'ADMIN'])) {
    die("Acesso negado.");
}
if($userPerfil == 'ADMIN'){
    $sql = "

    SELECT
        nome,
        cpf,
        deficiencia,
        status_aprovacao,
        data_cadastro

    FROM alunos
    ORDER BY data_cadastro DESC

";
}else{
    $sql = "

    SELECT
        nome,
        cpf,
        deficiencia,
        status_aprovacao,
        data_cadastro

    FROM alunos

    WHERE id_usuario_escola = $userId

    ORDER BY data_cadastro DESC

";
}


$result = mysqli_query($conexao, $sql);

$alunos = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Alunos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>
<body class="page-alunos-listar">

<div class="d-flex">

    <!-- SIDEBAR -->     

    <?php require("navbar.php"); ?>    

    <!-- CONTEÚDO -->
    <div class="content">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Alunos da Escola
                </h2>

                <p class="text-muted">
                    Gerencie os alunos cadastrados pela unidade escolar.
                </p>

            </div>

            <a href="alunos_cadastrar.php" class="btn btn-primary">
                + Cadastrar aluno
            </a>

        </div>

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead>

                            <tr>

                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Deficiência</th>
                                <th>Status</th>
                                <th>Data</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if(count($alunos) > 0) { ?>

                                <?php foreach($alunos as $aluno) { ?>

                                    <tr>

                                        <td>
                                            <?php echo htmlspecialchars($aluno['nome']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($aluno['cpf']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($aluno['deficiencia']); ?>
                                        </td>

                                        <td>

                                            <?php
                                                $status = $aluno['status_aprovacao'];

                                                if($status == 'PENDENTE'){
                                                    echo '<span class="badge bg-warning text-dark">Pendente</span>';
                                                }

                                                elseif($status == 'APROVADO'){
                                                    echo '<span class="badge bg-success">Aprovado</span>';
                                                }

                                                elseif($status == 'REPROVADO'){
                                                    echo '<span class="badge bg-danger">Reprovado</span>';
                                                }

                                                else{
                                                    echo '<span class="badge bg-secondary">Arquivado</span>';
                                                }
                                            ?>

                                        </td>

                                        <td>
                                            <?php echo date('d/m/Y', strtotime($aluno['data_cadastro'])); ?>
                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>

                                    <td colspan="5" class="text-center text-muted">

                                        Nenhum aluno cadastrado.

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
