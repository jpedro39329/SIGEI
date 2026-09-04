<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';

    $stmt = $pdo->prepare("SELECT email, tipo_usuario FROM recuperacao_senha WHERE token = ? AND data_expiracao > NOW()");
    $stmt->execute([$token]);
    $solicitacao = $stmt->fetch();

    if ($solicitacao) {
        $email = $solicitacao['email'];
        $tabelaTarget = $solicitacao['tipo_usuario'];

        $senhaCriptografada = password_hash($novaSenha, PASSWORD_BCRYPT);

        $stmtUpdate = $pdo->prepare("UPDATE $tabelaTarget SET senha = ? WHERE email = ?");
        $stmtUpdate->execute([$senhaCriptografada, $email]);

        $stmtDelete = $pdo->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
        $stmtDelete->execute([$email]);

        echo "<script>alert('Senha alterada com sucesso!'); window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Token inválido ou expirado.'); window.location.href='index.php';</script>";
        exit;
    }
}