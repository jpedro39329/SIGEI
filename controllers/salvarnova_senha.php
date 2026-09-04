<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';

    if (empty($token) || empty($novaSenha)) {
        die("Requisição inválida.");
    }

    // Busca o registro de recuperação válido
    $stmt = $pdo->prepare("SELECT email, tipo_usuario FROM recuperacao_senha WHERE token = ? AND data_expiracao > NOW()");
    $stmt->execute([$token]);
    $solicitacao = $stmt->fetch();

    if ($solicitacao) {
        $email = $solicitacao['email'];
        $tabelaTarget = $solicitacao['tipo_usuario'];

        // Criptografa a nova senha no mesmo padrão hash do seu banco
        $senhaCriptografada = password_hash($novaSenha, PASSWORD_BCRYPT);

        // Atualiza a senha na tabela correspondente ao tipo de usuário
        $stmtUpdate = $pdo->prepare("UPDATE $tabelaTarget SET senha = ? WHERE email = ?");
        $stmtUpdate->execute([$senhaCriptografada, $email]);

        // Invalida o token após a alteração
        $stmtDelete = $pdo->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
        $stmtDelete->execute([$email]);

        echo "<script>alert('Senha alterada com sucesso! Faça login com a sua nova senha.'); window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Token expirado ou inválido. Solicite novamente.'); window.location.href='index.php?control=auth&action=esqueci_senha';</script>";
        exit;
    }
}