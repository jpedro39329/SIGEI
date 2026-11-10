<?php
require_once __DIR__ . "/../config/init.php";

exigirLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userPerfil = $_SESSION['user_perfil'] ?? '';
$key = trim($_POST['key'] ?? ($_GET['key'] ?? ''));
$destino = trim($_POST['destino'] ?? ($_GET['destino'] ?? ''));

$tokenCsrf = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

if ($userId > 0 && $userPerfil !== '' && $key !== '' && validarTokenCSRF($tokenCsrf)) {
    $stmt = $conexao->prepare("
        INSERT INTO notificacoes_lidas (id_usuario, perfil, notificacao_key, data_leitura)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE data_leitura = NOW()
    ");
    if ($stmt) {
        $stmt->bind_param("iss", $userId, $userPerfil, $key);
        $stmt->execute();
        $stmt->close();
    }
}

// Se for requisição AJAX/fetch, responde JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true]);
    exit();
}

// Redireciona apenas para rotas internas seguras da pasta views/ (previne Open Redirect)
if (!empty($destino)) {
    $destinoLimpo = ltrim(preg_replace('#^(\.\./)+#', '', str_replace('\\', '/', $destino)), '/');
    if (
        strpos($destinoLimpo, '://') === false &&
        strpos($destinoLimpo, '//') === false &&
        strpos($destinoLimpo, '..') === false &&
        preg_match('#^views/[a-zA-Z0-9_/-]+\.php(\?[a-zA-Z0-9_=&%-]*)?$#', $destinoLimpo)
    ) {
        header("Location: ../" . $destinoLimpo);
        exit();
    }
}

header("Location: ../views/dashboard.php");
exit();
