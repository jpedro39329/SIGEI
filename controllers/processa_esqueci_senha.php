<?php
require_once "../config/init.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        die("Informe seu e-mail.");
    }

    $tabelas = [
        'admin'               => 'id_admin',
        'seduc'               => 'id_seduc',
        'usuarios_pae'        => 'id_pae',
        'usuarios_supervisor' => 'id_usuario_supervisor',
        'usuarios_ue'         => 'id_usuario_ue',
        'usuarios_ure'        => 'id_usuario_ure'
    ];

    $usuarioEncontrado = false;
    $tipoUsuario = null;

    foreach ($tabelas as $tabela => $campoId) {
        $stmt = $pdo->prepare("SELECT $campoId, email FROM $tabela WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $res = $stmt->fetch();

        if ($res && !empty($res['email'])) {
            $usuarioEncontrado = true;
            $tipoUsuario = $tabela;
            break;
        }
    }

    if ($usuarioEncontrado) {
        $token = bin2hex(random_bytes(32));
        $dataExpiracao = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmtDel = $pdo->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
        $stmtDel->execute([$email]);

        $stmtIns = $pdo->prepare("INSERT INTO recuperacao_senha (email, token, tipo_usuario, data_expiracao) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$email, $token, $tipoUsuario, $dataExpiracao]);

        $link = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?control=auth&action=redefinir_senha&token=" . $token;

        $assunto = "Redefinicao de Senha - SIGEI";
        $mensagem = "Acesse o link para cadastrar uma nova senha: " . $link;
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];

        @mail($email, $assunto, $mensagem, $headers);
    }

    echo "<script>alert('Se o e-mail estiver cadastrado, você receberá o link.'); window.location.href='index.php';</script>";
    exit;
}