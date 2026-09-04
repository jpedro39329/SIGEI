<?php
$userName    = $userName ?? ($_SESSION['user_name'] ?? 'Usuário');
$userPerfil  = $userPerfil ?? ($_SESSION['user_perfil'] ?? '');
$scriptPath  = str_replace('\\', '/', $_SERVER['PHP_SELF']);

// Determina a raiz relativa com base na profundidade do arquivo que incluiu a navbar
if (preg_match('#/views/[^/]+/[^/]+$#', $scriptPath)) {
    $baseUrl = '../../';
} else {
    $baseUrl = '../';
}

$homePage = $baseUrl . 'views/dashboard.php';
?>

<nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm">
    <div class="container-fluid">

        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $homePage; ?>">
            <img src="<?php echo $baseUrl; ?>assets/imgs/logo_nav.png" alt="SIGEI" height="50" class="d-inline-block logo-nav">
            <span class="fw-bold">SIGEI</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSIGEI" aria-controls="navbarSIGEI" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSIGEI">

            <!-- Links principais (esquerda) -->
            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($scriptPath, 'dashboard.php') !== false) ? 'active' : ''; ?>" href="<?php echo $homePage; ?>">Início</a>
                </li>

                <!-- SEDUC / ADMIN -->
                <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'ures/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/ures/cadastrar.php">UREs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'empresas/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/empresas/cadastrar.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'supervisores/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/supervisores/cadastrar.php">Supervisores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'dirigentes/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/dirigentes/cadastrar.php">Dirigentes URE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'escolas/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/escolas/cadastrar.php">Escolas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'usuarios/listar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/usuarios/listar.php">Usuários</a>
                    </li>
                <?php } ?>

                <!-- DIRIGENTE URE -->
                <?php if ($userPerfil === 'DIRIGENTE') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'escolas/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/escolas/cadastrar.php">Escolas (UEs)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'setores/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/setores/cadastrar.php">Setores da URE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                <?php } ?>

                <!-- SEFISC -->
                <?php if ($userPerfil === 'USUARIO_SEFISC') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'usuarios_ue/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/usuarios_ue/cadastrar.php">Usuários das Escolas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'paes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/paes/listar.php">PAEs</a>
                    </li>
                <?php } ?>

                <!-- EDUCAÇÃO ESPECIAL -->
                <?php if ($userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'solicitacoes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/solicitacoes/listar.php">Solicitações</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                <?php } ?>

                <!-- SUPERVISOR DA EMPRESA -->
                <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos Atendidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'paes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/paes/listar.php">PAEs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'associacoes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/associacoes/gerenciar.php">Associações</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'relatorios/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/relatorios/listar.php">Relatórios</a>
                    </li>
                <?php } ?>

                <!-- USUARIO_ESCOLA -->
                <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/listar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Meus Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/cadastrar.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/cadastrar.php">Nova Solicitação</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/pendentes.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/pendentes.php">Pendentes</a>
                    </li>
                <?php } ?>

                <!-- PAE -->
                <?php if ($userPerfil === 'PAE') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'relatorios/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/relatorios/listar.php">Meus Relatórios</a>
                    </li>
                <?php } ?>

                <!-- Perfil (todos) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($scriptPath, 'perfil.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/perfil.php">Perfil</a>
                </li>

            </ul>

            <!-- Direita: dados do usuário e logout -->
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <strong><?php echo htmlspecialchars($userName); ?></strong> (<?php echo htmlspecialchars(nomePerfil($userPerfil)); ?>)
                </span>
                <a href="<?php echo $baseUrl; ?>controllers/auth/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>

        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<?php
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentUserPerfil = $_SESSION['user_perfil'] ?? '';
if ($currentUserId > 0 && !isset($_SESSION['termos_aceitos']) && isset($conexao)) {
    $_SESSION['termos_aceitos'] = verificarTermoAceito($conexao, $currentUserId, $currentUserPerfil);
}
$termoAceito = !empty($_SESSION['termos_aceitos']);
?>
<script>
window.SIGEI_CONFIG = {
    userId: <?php echo json_encode($currentUserId); ?>,
    userPerfil: <?php echo json_encode($currentUserPerfil); ?>,
    termoAceito: <?php echo json_encode($termoAceito); ?>,
    baseUrl: <?php echo json_encode($baseUrl); ?>
};
</script>
<script src="<?php echo $baseUrl; ?>assets/js/termos.js"></script>
