<?php
// controllers/processa_esqueci_senha.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $usuarioEncontrado = null;

    $tabelasPerfis = [
        'SEDUC'      => ['tabela' => 'seduc', 'id' => 'id_seduc'],
        'URE'        => ['tabela' => 'usuarios_ure', 'id' => 'id_usuario_ure'],
        'SUPERVISOR' => ['tabela' => 'usuarios_supervisor', 'id' => 'id_usuario_supervisor'],
        'ESCOLA'     => ['tabela' => 'usuarios_ue', 'id' => 'id_usuario_ue'],
        'PAE'        => ['tabela' => 'usuarios_pae', 'id' => 'id_pae']
    ];

    foreach ($tabelasPerfis as $tipoPerfil => $info) {
        $sql = "SELECT {$info['id']} AS id_ref FROM {$info['tabela']} WHERE email = ? AND ativo = 1";
        if ($tipoPerfil === 'SEDUC') {
            $sql = "SELECT id_seduc AS id_ref FROM seduc WHERE email = ? AND ativo = 1";
        }

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();

        if ($resultado) {
            $usuarioEncontrado = [
                'tipo_perfil' => $tipoPerfil,
                'id_referencia' => $resultado['id_ref']
            ];
            break;
        }
    }

    if ($usuarioEncontrado) {
        $token = bin2hex(random_bytes(32)); 
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); 
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sqlInsert = "INSERT INTO recuperacao_senha (tipo_perfil, id_referencia, token, codigo, tipo_contato, contato, expira_em, usado) 
                      VALUES (?, ?, ?, ?, 'EMAIL', ?, ?, 0)";
        
        $stmtInsert = $conexao->prepare($sqlInsert);
        $stmtInsert->bind_param("sissss", 
            $usuarioEncontrado['tipo_perfil'], 
            $usuarioEncontrado['id_referencia'], 
            $token, 
            $codigo, 
            $email, 
            $expira_em
        );
        $stmtInsert->execute();

        $link = "http://localhost/sigei/views/redefinir_senha.php?token=" . $token;

        $para = $email;
        $assunto = "SIGEI - Recuperação de Senha";
        $mensagem = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha no SIGEI[cite: 3].\n";
        $mensagem .= "Seu código de verificação de 6 dígitos é: " . $codigo . "\n\n";
        $mensagem .= "Ou acesse o link abaixo para redefinir sua senha:\n" . $link . "\n\n";
        $mensagem .= "Se você não solicitou isso, ignore este e-mail.";
        
        $headers = "From: no-reply@sigei.com\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        if (mail($para, $assunto, $mensagem, $headers)) {
            echo "E-mail de recuperação enviado com sucesso para: " . htmlspecialchars($email);
        } else {
            echo "Erro ao enviar e-mail nativo. Código de verificação para testes: <strong>{$codigo}</strong><br>";
            echo "Link: <a href='{$link}'>{$link}</a>";
        }
    } else {
        echo "E-mail não encontrado ou inativo no sistema.";
    }
}
?>