<?php
$userName    = $userName ?? ($_SESSION['user_name'] ?? 'Usuario');
$userPerfil  = $userPerfil ?? ($_SESSION['user_perfil'] ?? '');
$currentPage = basename($_SERVER['PHP_SELF']);

$homePage = 'dashboard.php';
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

                <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'ADMIN'])) { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos</a>
                    </li>

                <?php } ?>

                <?php if ($userPerfil === 'USUARIO_ESCOLA') { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_pendentes.php') ? 'active' : ''; ?>" href="alunos_pendentes.php">Pendentes</a>
                    </li>

                <?php } ?>

                <?php if ($userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'solicitacoes.php') ? 'active' : ''; ?>" href="solicitacoes.php">Solicitações</a>
                    </li>

                <?php } ?>

                <?php if (in_array($userPerfil, ['USUARIO_EMPRESA', 'USUARIO_SEFISC', 'ADMIN'])) { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'paes_listar.php') ? 'active' : ''; ?>" href="paes_listar.php">PAEs</a>
                    </li>

                <?php } ?>

                <?php if (in_array($userPerfil, ['ADMIN', 'USUARIO_EMPRESA'])) { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'associacoes.php') ? 'active' : ''; ?>" href="associacoes.php">Associações</a>
                    </li>

                <?php } ?>

                <?php if ($userPerfil === 'PAE') { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'relatorios.php') ? 'active' : ''; ?>" href="relatorios.php">Relatórios</a>
                    </li>

                <?php } ?>

                <?php if ($userPerfil === 'ADMIN') { ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'escolas_cadastrar.php') ? 'active' : ''; ?>" href="escolas_cadastrar.php">Escolas</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'empresas_cadastrar.php') ? 'active' : ''; ?>" href="empresas_cadastrar.php">Empresas</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'usuarios.php') ? 'active' : ''; ?>" href="usuarios.php">Usuários</a>
                    </li>

                <?php } ?>

                <!-- Perfil (todos) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'perfil.php') ? 'active' : ''; ?>" href="perfil.php">Perfil</a>
                </li>

            </ul>

            
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
