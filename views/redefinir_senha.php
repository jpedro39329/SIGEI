<?php
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        die("Por favor, informe seu e-mail.");
    }

    // Mapeamento das tabelas e suas respectivas chaves primárias/campos
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

    // Procura o e-mail entre as 6 tabelas de usuários do SIGEI
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
        // Gera um token seguro de 64 caracteres hexadecimais
        $token = bin2hex(random_bytes(32));
        $dataExpiracao = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Limpa tokens antigos do mesmo e-mail
        $stmtDel = $pdo->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
        $stmtDel->execute([$email]);

        // Insere a nova solicitação de redefinição
        $stmtIns = $pdo->prepare("INSERT INTO recuperacao_senha (email, token, tipo_usuario, data_expiracao) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$email, $token, $tipoUsuario, $dataExpiracao]);

        // Monta o link para redefinição
        $link = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?control=auth&action=redefinir_senha&token=" . $token;

        // Envio do e-mail
        $assunto = "Redefinição de Senha - SIGEI";
        $mensagem = "Olá!\n\nVocê solicitou a redefinição de sua senha no sistema SIGEI.\n";
        $mensagem .= "Clique no link a seguir para cadastrar uma nova senha (válido por 30 minutos):\n\n";
        $mensagem .= $link . "\n\nSe você não solicitou este e-mail, desconsidere-o.";
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];

        @mail($email, $assunto, $mensagem, $headers);
    }

    // Mensagem padronizada por questão de segurança (evitar enumeração de usuários)
    echo "<script>alert('Se o e-mail estiver cadastrado no sistema, você receberá o link para redefinição de senha.'); window.location.href='index.php';</script>";
    exit;
}