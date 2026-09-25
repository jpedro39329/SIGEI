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

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
                        <a class="nav-link <?php echo (strpos($scriptPath, 'ures/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/ures/listar.php">Unidades Regionais</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'empresas/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/empresas/listar.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'supervisores/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/supervisores/listar.php">Supervisores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'dirigentes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/dirigentes/listar.php">Dirigentes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'escolas/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/escolas/listar.php">Unidades Escolares</a>
                    </li>
                <?php } ?>

                <!-- DIRIGENTE / ASURE URE -->
                <?php if ($userPerfil === 'DIRIGENTE') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'escolas/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/escolas/listar.php">Unidades Escolares</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'setores/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/setores/listar.php">Servidores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                <?php } ?>

                <!-- SEFISC -->
                <?php if ($userPerfil === 'USUARIO_SEFISC') { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'empresas/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/empresas/listar.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'usuarios_ue/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/usuarios_ue/listar.php">Usuários das Escolas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'paes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/paes/listar.php">Profissionais de Apoio</a>
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
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/listar.php">Alunos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($scriptPath, 'paes/') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/paes/listar.php">Profissionais de Apoio</a>
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
                        <a class="nav-link <?php echo (strpos($scriptPath, 'alunos/pendentes.php') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>views/alunos/pendentes.php">Solicitações</a>
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

            <!-- Direita: Notificações + Avatar / Menu do Usuário -->
            <div class="d-flex align-items-center gap-3 ms-lg-auto mt-2 mt-lg-0 navbar-right-actions">

                <!-- 1. Sino de Notificações -->
                <?php
                $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
                $listaNotificacoes = [];
                if (isset($conexao) && $currentUserId > 0 && function_exists('obterNotificacoesUsuario')) {
                    $listaNotificacoes = obterNotificacoesUsuario($conexao, $userPerfil, $currentUserId, $baseUrl);
                }
                $totalNotificacoes = count($listaNotificacoes);
                ?>
                <div class="dropdown">
                    <button
                        class="btn p-0 border-0 text-dark position-relative navbar-bell-btn"
                        type="button"
                        id="dropdownNotificacoes"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        title="<?php echo $totalNotificacoes > 0 ? $totalNotificacoes . ' notificações' : 'Notificações'; ?>"
                    >
                        <i class="bi bi-bell navbar-bell-icon"></i>
                        <?php if ($totalNotificacoes > 0): ?>
                            <span class="position-absolute navbar-bell-badge">
                                <?php echo $totalNotificacoes > 99 ? '99+' : $totalNotificacoes; ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end navbar-custom-dropdown shadow-lg p-0" aria-labelledby="dropdownNotificacoes" style="width: min(340px, 92vw);">
                        <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light rounded-top">
                            <span class="fw-bold small text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-bell-fill text-primary"></i> Notificações
                            </span>
                            <?php if ($totalNotificacoes > 0): ?>
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1"><?php echo $totalNotificacoes; ?> nova(s)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-2 py-1">0 novas</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($totalNotificacoes > 0): ?>
                            <div class="navbar-notifications-list" style="max-height: 360px; overflow-y: auto;">
                                <?php foreach ($listaNotificacoes as $notif): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2 text-wrap navbar-notification-item">
                                        <div class="flex-shrink-0 mt-1">
                                            <span class="navbar-notif-icon-badge bg-<?php echo $notif['tipo']; ?>-subtle text-<?php echo $notif['tipo']; ?>">
                                                <i class="bi <?php echo htmlspecialchars($notif['icone']); ?>"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="text-dark small text-truncate" style="font-size: 0.85rem;">
                                                    <?php echo htmlspecialchars($notif['titulo']); ?>
                                                </strong>
                                                <small class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars($notif['tempo']); ?></small>
                                            </div>
                                            <p class="mb-0 text-muted small lh-sm" style="font-size: 0.8rem;">
                                                <?php echo $notif['mensagem']; ?>
                                            </p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-bell-slash d-block mb-2 text-secondary" style="font-size: 1.75rem; opacity: 0.5;"></i>
                                <p class="mb-0 small fw-medium">Nenhuma notificação nova</p>
                                <small class="text-muted opacity-75">Tudo em dia com os registros do seu perfil.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                // Cálculo das iniciais (Ex: "Gabriel Ronaldo Lima" -> "GR", "João Pedro" -> "JP")
                $userFoto = $_SESSION['user_foto'] ?? ($usuario['foto_arquivo'] ?? '');
                $temFoto = !empty($userFoto) && file_exists(dirname(__DIR__) . '/' . ltrim($userFoto, '/'));

                $partesNome = array_values(array_filter(explode(' ', trim($userName))));
                if (count($partesNome) >= 2) {
                    $iniciais = mb_substr($partesNome[0], 0, 1, 'UTF-8') . mb_substr($partesNome[1], 0, 1, 'UTF-8');
                } else {
                    $iniciais = mb_substr($userName, 0, min(2, mb_strlen($userName, 'UTF-8')), 'UTF-8');
                }
                $iniciais = strtoupper($iniciais ?: 'U');
                ?>

                <!-- 2. Avatar / Menu do Usuário -->
                <div class="dropdown">
                    <button
                        class="btn p-0 border-0 d-flex align-items-center gap-2 navbar-user-trigger"
                        type="button"
                        id="dropdownUsuario"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        title="<?php echo htmlspecialchars($userName); ?>"
                    >
                        <?php if ($temFoto): ?>
                            <img
                                src="<?php echo $baseUrl . ltrim($userFoto, '/'); ?>"
                                alt="<?php echo htmlspecialchars($userName); ?>"
                                class="navbar-avatar-circle navbar-avatar-img"
                            >
                        <?php else: ?>
                            <div class="navbar-avatar-circle">
                                <?php echo htmlspecialchars($iniciais); ?>
                            </div>
                        <?php endif; ?>

                        <i class="bi bi-chevron-down navbar-arrow-icon"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end navbar-custom-dropdown shadow-lg p-0 mt-2" aria-labelledby="dropdownUsuario" style="min-width: 290px;">
                        
                        <!-- Cabeçalho do usuário no dropdown -->
                        <div class="p-3 d-flex align-items-center gap-3 border-bottom">
                            <?php if ($temFoto): ?>
                                <img
                                    src="<?php echo $baseUrl . ltrim($userFoto, '/'); ?>"
                                    alt="<?php echo htmlspecialchars($userName); ?>"
                                    class="navbar-avatar-circle navbar-avatar-img"
                                >
                            <?php else: ?>
                                <div class="navbar-avatar-circle flex-shrink-0">
                                    <?php echo htmlspecialchars($iniciais); ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex flex-column text-start overflow-hidden">
                                <strong class="text-dark text-truncate fs-6" style="font-weight: 700; line-height: 1.25;">
                                    <?php echo htmlspecialchars($userName); ?>
                                </strong>
                                <span class="text-muted text-truncate small mt-1" style="font-size: 0.8rem;">
                                    <?php echo htmlspecialchars(nomePerfil($userPerfil)); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Links de Ação -->
                        <div class="py-2">
                            <a class="dropdown-item navbar-menu-link d-flex align-items-center gap-3 py-2 px-3" href="<?php echo $baseUrl; ?>views/perfil.php">
                                <i class="bi bi-person fs-5"></i>
                                <span class="fw-medium">Perfil</span>
                            </a>

                            <div class="dropdown-divider my-1"></div>

                            <a class="dropdown-item navbar-menu-link text-danger d-flex align-items-center gap-3 py-2 px-3" href="<?php echo $baseUrl; ?>controllers/auth/logout.php">
                                <i class="bi bi-box-arrow-right fs-5"></i>
                                <span class="fw-medium">Sair</span>
                            </a>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</nav>

<!-- Modal Global de Visualização de Documentos e Laudos (SIGEI) -->
<div class="modal fade" id="modalVisualizarDocumentoGlobal" tabindex="-1" aria-labelledby="modalVisualizarDocumentoGlobalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90vw;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 88vh;">
            <div class="modal-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalVisualizarDocumentoGlobalLabel">Visualização do Documento</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="btnBaixarDocumentoGlobal" href="#" target="_blank" download class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-download"></i> Baixar Arquivo
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex justify-content-center align-items-center bg-dark" style="height: calc(88vh - 65px);">
                <iframe id="iframeDocumentoGlobal" src="" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                <img id="imgDocumentoGlobal" src="" alt="Documento" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain; display: none;">
            </div>
        </div>
    </div>
</div>

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

window.visualizarDocumento = function (caminho, nome) {
    if (!caminho) return;
    const baseUrl = window.SIGEI_CONFIG?.baseUrl || '../../';
    const caminhoLimpo = caminho.startsWith('/') ? caminho.substring(1) : caminho;
    const urlCompleta = (caminho.startsWith('http://') || caminho.startsWith('https://') || caminho.startsWith('../')) ? caminho : (baseUrl + caminhoLimpo);
    const extensao = caminho.split('.').pop().toLowerCase();
    
    const label = document.getElementById('modalVisualizarDocumentoGlobalLabel');
    const btnBaixar = document.getElementById('btnBaixarDocumentoGlobal');
    const iframe = document.getElementById('iframeDocumentoGlobal');
    const img = document.getElementById('imgDocumentoGlobal');

    if (label) label.innerText = nome || 'Visualização do Documento';
    if (btnBaixar) btnBaixar.href = urlCompleta;

    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extensao)) {
        if (iframe) { iframe.style.display = 'none'; iframe.src = ''; }
        if (img) { img.src = urlCompleta; img.style.display = 'block'; }
    } else {
        if (img) { img.style.display = 'none'; img.src = ''; }
        if (iframe) { iframe.src = urlCompleta; iframe.style.display = 'block'; }
    }

    const modalEl = document.getElementById('modalVisualizarDocumentoGlobal');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
};

window.abrirModalLaudo = window.visualizarDocumento;

document.getElementById('modalVisualizarDocumentoGlobal')?.addEventListener('hidden.bs.modal', function () {
    const iframe = document.getElementById('iframeDocumentoGlobal');
    const img = document.getElementById('imgDocumentoGlobal');
    if (iframe) iframe.src = '';
    if (img) img.src = '';
});
</script>
<script src="<?php echo $baseUrl; ?>assets/js/termos.js"></script>
<script src="<?php echo $baseUrl; ?>assets/js/confirmacao.js"></script>
<script src="<?php echo $baseUrl; ?>assets/js/mascaras.js"></script>

