<?php
$userName = $userName ?? ($_SESSION['user_name'] ?? 'Usuario');
$userPerfil = $userPerfil ?? ($_SESSION['user_perfil'] ?? '');
?>

<div class="sidebar p-3">

    <h3 class="mb-4">
        SIGEI
    </h3>

    <p>
        Olá,
        <strong>
            <?php echo htmlspecialchars($userName); ?>
        </strong>
    </p>

    <hr>

    <div class="nav flex-column">

        <a href="<?php echo $userPerfil === 'ADMIN' ? 'dashboard_admin.php' : 'dashboard.php'; ?>" class="nav-link">
            Início
        </a>

        <?php if(in_array($userPerfil, ['UNIDADE_ESCOLAR', 'EDUCACAO_ESPECIAL', 'SETOR_FISCALIZACAO', 'ADMIN'])) { ?>

            <a href="alunos_listar.php" class="nav-link">
                Alunos
            </a>

        <?php } ?>

        <?php if(in_array($userPerfil, ['EMPRESA_TERCEIRIZADA', 'SETOR_FISCALIZACAO', 'ADMIN'])) { ?>

            <a href="cuidadores_listar.php" class="nav-link">
                Cuidadores
            </a>

        <?php } ?>

        <?php if(in_array($userPerfil, ['SETOR_FISCALIZACAO', 'ADMIN'])) { ?>

            <a href="empresa.php" class="nav-link">
                Empresas
            </a>

        <?php } ?>

        <?php if($userPerfil == 'ADMIN') { ?>

            <a href="usuarios.php" class="nav-link">
                Usuários
            </a>

        <?php } ?>

    </div>

    <hr>

    <a href="../controllers/logout.php" class="nav-link text-danger">
        Sair
    </a>

</div>
