<?php
$userName    = $userName ?? ($_SESSION['user_name'] ?? 'Usuário');
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

                <!-- SEDUC / ADMIN -->
                <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'ures_cadastrar.php') ? 'active' : ''; ?>" href="ures_cadastrar.php">UREs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'empresas_cadastrar.php') ? 'active' : ''; ?>" href="empresas_cadastrar.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'supervisores_cadastrar.php') ? 'active' : ''; ?>" href="supervisores_cadastrar.php">Supervisores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'dirigentes_cadastrar.php') ? 'active' : ''; ?>" href="dirigentes_cadastrar.php">Dirigentes URE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'escolas_cadastrar.php') ? 'active' : ''; ?>" href="escolas_cadastrar.php">Escolas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'usuarios.php') ? 'active' : ''; ?>" href="usuarios.php">Usuários</a>
                    </li>
                <?php } ?>

                <!-- DIRIGENTE URE -->
                <?php if ($userPerfil === 'DIRIGENTE') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'escolas_cadastrar.php') ? 'active' : ''; ?>" href="escolas_cadastrar.php">Escolas (UEs)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'setores_ure_cadastrar.php') ? 'active' : ''; ?>" href="setores_ure_cadastrar.php">Setores da URE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos</a>
                    </li>
                <?php } ?>

                <!-- SEFISC -->
                <?php if ($userPerfil === 'USUARIO_SEFISC') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'usuarios_ue_cadastrar.php') ? 'active' : ''; ?>" href="usuarios_ue_cadastrar.php">Usuários das Escolas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'paes_listar.php') ? 'active' : ''; ?>" href="paes_listar.php">PAEs</a>
                    </li>
                <?php } ?>

                <!-- EDUCAÇÃO ESPECIAL -->
                <?php if ($userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'solicitacoes.php') ? 'active' : ''; ?>" href="solicitacoes.php">Solicitações</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos</a>
                    </li>
                <?php } ?>

                <!-- SUPERVISOR DA EMPRESA -->
                <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Alunos Atendidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'paes_listar.php') ? 'active' : ''; ?>" href="paes_listar.php">PAEs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'associacoes.php') ? 'active' : ''; ?>" href="associacoes.php">Associações</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'relatorios.php') ? 'active' : ''; ?>" href="relatorios.php">Relatórios</a>
                    </li>
                <?php } ?>

                <!-- USUARIO_ESCOLA -->
                <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_listar.php') ? 'active' : ''; ?>" href="alunos_listar.php">Meus Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_cadastrar.php') ? 'active' : ''; ?>" href="alunos_cadastrar.php">Nova Solicitação</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'alunos_pendentes.php') ? 'active' : ''; ?>" href="alunos_pendentes.php">Pendentes</a>
                    </li>
                <?php } ?>

                <!-- PAE -->
                <?php if ($userPerfil === 'PAE') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'relatorios.php') ? 'active' : ''; ?>" href="relatorios.php">Meus Relatórios</a>
                    </li>
                <?php } ?>

                <!-- Perfil (todos) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'perfil.php') ? 'active' : ''; ?>" href="perfil.php">Perfil</a>
                </li>

            </ul>

            <!-- Direita: dados do usuário e logout -->
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <strong><?php echo htmlspecialchars($userName); ?></strong> (<?php echo htmlspecialchars(nomePerfil($userPerfil)); ?>)
                </span>
                <a href="../controllers/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>

        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
