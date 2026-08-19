<?php
$userName    = $userName ?? ($_SESSION['user_name'] ?? 'Usuario');
$userPerfil  = $userPerfil ?? ($_SESSION['user_perfil'] ?? '');
$currentPage = basename($_SERVER['PHP_SELF']);

$homePage = ($userPerfil === 'ADMIN') ? 'dashboard_admin.php' : 'dashboard.php';
?>

<nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm">
    <div class="container-fluid">

        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $homePage; ?>">
            <img src="../assets/imgs/logo_nav.png" alt="SIGEI" height="50" class="d-inline-block logo-nav">
            <span class="fw-bold">SIGEI</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSIGEI" aria-controls="navbarSIGEI" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSIGEI">

            <!-- Links principais (esquerda) -->
            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === $homePage) ? 'active' : ''; ?>" href="<?php echo $homePage; ?>">Início</a>
                </li>

                <?php if (in_array($userPerfil, ['UNIDADE_ESCOLAR', 'EDUCACAO_ESPECIAL', 'SETOR_FISCALIZACAO', 'ADMIN'])) { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos</a>
                    </li>

                <?php } ?>

                <?php if (in_array($userPerfil, ['EMPRESA_TERCEIRIZADA', 'SETOR_FISCALIZACAO', 'ADMIN'])) { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'cuidadores_listar.php') ? 'active' : ''; ?>" href="cuidadores_listar.php">Cuidadores</a>
                    </li>

                <?php } ?>

                <?php if (in_array($userPerfil, ['SETOR_FISCALIZACAO', 'ADMIN'])) { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'empresa.php') ? 'active' : ''; ?>" href="empresa.php">Empresas</a>
                    </li>

                <?php } ?>

                <?php if ($userPerfil === 'ADMIN') { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'usuarios.php') ? 'active' : ''; ?>" href="usuarios.php">Usuários</a>
                    </li>

                <?php } ?>

                <!-- Perfil junto com os demais links (esquerda) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'perfil.php') ? 'active' : ''; ?>" href="perfil.php">Perfil</a>
                </li>

            </ul>

            
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
