<?php
require_once __DIR__ . "/../config/init.php";

exigirLogin();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userPerfil = $_SESSION['user_perfil'] ?? '';
$key = trim($_POST['key'] ?? ($_GET['key'] ?? ''));
$destino = trim($_POST['destino'] ?? ($_GET['destino'] ?? ''));

if ($userId > 0 && $userPerfil !== '' && $key !== '') {
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
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// Redireciona para o destino ou dashboard
if (!empty($destino)) {
    // Normaliza destino removendo eventuais '../' do início para redirecionar a partir da pasta raiz
    $destinoLimpo = preg_replace('#^(\.\./)+#', '', $destino);
    header("Location: ../" . $destinoLimpo);
    exit();
}

header("Location: ../views/dashboard.php");
exit();
