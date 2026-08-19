<?php

session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$id_usuario_empresa = $_SESSION['user_id'];

if (!in_array($userPerfil, ['EMPRESA_TERCEIRIZADA', 'ADMIN'])) {
    die("Acesso negado.");
}

if($userPerfil == 'ADMIN'){

    $sql = "

        SELECT
            nome,
            cpf,
            empresa,
            ativo,
            data_cadastro

        FROM usuarios

        WHERE perfil = 'CUIDADOR'

        ORDER BY data_cadastro DESC

    ";

}else{

    $sql = "

        SELECT
            nome,
            cpf,
            empresa,
            ativo,
            data_cadastro

        FROM usuarios

        WHERE perfil = 'CUIDADOR'
        AND id_usuario_empresa = '$id_usuario_empresa'

        ORDER BY data_cadastro DESC

    ";

}

$result = mysqli_query($conexao, $sql);

$cuidadores = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cuidadores</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="page-cuidadores-listar">

<?php require("navbar.php"); ?>

    <!-- CONTEÚDO -->
    <div class="content">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Cuidadores da Empresa
                </h2>

                <p class="text-muted">
                    Olá, <?php echo htmlspecialchars($userName); ?> — gerencie os cuidadores cadastrados.
                </p>

            </div>

            <a href="cuidadores_cadastrar.php" class="btn btn-primary">
                + Cadastrar Cuidador
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
                                <th>Empresa</th>
                                <th>Status</th>
                                <th>Data</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (count($cuidadores) > 0) { ?>

                                <?php foreach ($cuidadores as $cuidador) { ?>

                                    <tr>

                                        <td>
                                            <?php echo htmlspecialchars($cuidador['nome']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($cuidador['cpf']); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars($cuidador['empresa']); ?>
                                        </td>

                                        <td>

                                            <?php if ($cuidador['ativo'] == 1) { ?>

                                                <span class="badge bg-success">
                                                    Ativo
                                                </span>

                                            <?php } else { ?>

                                                <span class="badge bg-danger">
                                                    Inativo
                                                </span>

                                            <?php } ?>

                                        </td>

                                        <td>
                                            <?php echo date('d/m/Y', strtotime($cuidador['data_cadastro'])); ?>
                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>

                                    <td colspan="5" class="text-center text-muted">
                                        Nenhum cuidador cadastrado.
                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</body>

</html>
