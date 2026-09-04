<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($conexao) || $conexao->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';

    if (empty($token) || empty($novaSenha)) {
        echo "<script>alert('Dados inválidos.'); window.history.back();</script>";
        exit;
    }

    // Busca a solicitação válida no banco usando MySQLi
    $stmt = $conexao->prepare("SELECT email, tipo_usuario FROM recuperacao_senha WHERE token = ? AND data_expiracao > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($solicitacao = $result->fetch_assoc()) {
        $email = $solicitacao['email'];
        $tabelaTarget = $solicitacao['tipo_usuario'];
        $stmt->close();

        // Criptografa a nova senha com BCRYPT
        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);

        // Atualiza na tabela correspondente ao perfil
        $stmtUpdate = $conexao->prepare("UPDATE $tabelaTarget SET senha = ? WHERE email = ?");
        $stmtUpdate->bind_param("ss", $senhaHash, $email);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Remove o token após a redefinição
        $stmtDelete = $conexao->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
        $stmtDelete->bind_param("s", $email);
        $stmtDelete->execute();
        $stmtDelete->close();

        echo "<script>alert('Senha alterada com sucesso! Você já pode fazer login.'); window.location.href='../views/login.php';</script>";
        exit;
    } else {
        echo "<script>alert('Link de recuperação inválido ou expirado. Solicite um novo.'); window.location.href='../views/esqueci_senha.php';</script>";
        exit;
    }
}