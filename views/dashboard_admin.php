<?php
session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id']) || $_SESSION['user_perfil'] !== 'ADMIN') {
    header('Location: login.php');
    exit();
}

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];

function buscarTotal($conexao, $sql) {
    $result = mysqli_query($conexao, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

$totalUsuarios = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM usuarios");
$totalAlunos = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM alunos");
$totalCuidadores = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM usuarios WHERE perfil = 'CUIDADOR'");
$totalPendentes = buscarTotal($conexao, "SELECT COUNT(*) AS total FROM alunos WHERE status_aprovacao = 'PENDENTE'");

$sqlAlunos = "
    SELECT
        a.nome,
        a.cpf,
        a.deficiencia,
        a.status_aprovacao,
        a.data_cadastro,
        escola.nome AS escola_nome
    FROM alunos a
    LEFT JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
    ORDER BY a.data_cadastro DESC
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunos = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];

$sqlCuidadores = "
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
$resultCuidadores = mysqli_query($conexao, $sqlCuidadores);
$cuidadores = $resultCuidadores ? mysqli_fetch_all($resultCuidadores, MYSQLI_ASSOC) : [];

$sqlUsuarios = "
    SELECT
        nome,
        cpf,
        perfil,
        ativo,
        data_cadastro
    FROM usuarios
    ORDER BY data_cadastro DESC
";
$resultUsuarios = mysqli_query($conexao, $sqlUsuarios);
$usuarios = $resultUsuarios ? mysqli_fetch_all($resultUsuarios, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-dashboard-admin">

<div class="d-flex">
    <?php require("navbar.php"); ?>

    <main class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Painel Administrativo</h2>
                <p class="text-muted mb-0">Visao geral completa do SIGEI.</p>
            </div>
        </div>

        <div class="cards mb-4">
            <div class="card">
                <span class="text-muted">Usuarios</span>
                <strong><?php echo $totalUsuarios; ?></strong>
            </div>

            <div class="card">
                <span class="text-muted">Alunos</span>
                <strong><?php echo $totalAlunos; ?></strong>
            </div>

            <div class="card">
                <span class="text-muted">Cuidadores</span>
                <strong><?php echo $totalCuidadores; ?></strong>
            </div>

            <div class="card">
                <span class="text-muted">Pendentes</span>
                <strong><?php echo $totalPendentes; ?></strong>
            </div>
        </div>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Todos os alunos</h5>
                    <a href="alunos_cadastrar.php" class="btn btn-primary btn-sm">+ Cadastrar aluno</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Deficiencia</th>
                                <th>Escola</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($alunos) > 0) { ?>
                                <?php foreach ($alunos as $aluno) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['deficiencia']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['status_aprovacao']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($aluno['data_cadastro'])); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Nenhum aluno cadastrado.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Todos os cuidadores</h5>
                    <a href="cuidadores_cadastrar.php" class="btn btn-primary btn-sm">+ Cadastrar cuidador</a>
                </div>

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
                                        <td><?php echo htmlspecialchars($cuidador['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($cuidador['cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($cuidador['empresa']); ?></td>
                                        <td><?php echo $cuidador['ativo'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($cuidador['data_cadastro'])); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum cuidador cadastrado.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Todos os usuarios</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0) { ?>
                                <?php foreach ($usuarios as $usuario) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['perfil']); ?></td>
                                        <td><?php echo $usuario['ativo'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum usuario cadastrado.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
