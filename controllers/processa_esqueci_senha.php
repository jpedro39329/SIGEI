<?php
// controllers/processa_esqueci_senha.php
require_once '../config/database.php';

// Caminhos corretos para a estrutura manual do PHPMailer
require_once '../phpmailer/src/Exception.php';
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

        $mail = new PHPMailer(true);

        try {
            // Configurações do Servidor SMTP (Exemplo com Gmail)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'seu_email@gmail.com'; // SEU E-MAIL REAL
            $mail->Password   = 'sua_senha_de_app';    // SUA SENHA DE APLICATIVO DO GMAIL
         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Em vez de STARTTLS
            $mail->Port       = 465;                        // Porta 465

            // Remetente e Destinatário
            $mail->setFrom('seu_email@gmail.com', 'Sistema SIGEI');
            $mail->addAddress($email);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = 'SIGEI - Código de Recuperação de Senha';
            $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha no SIGEI.<br>" .
                             "Seu código de verificação de 6 dígitos é: <b>{$codigo}</b><br><br>" .
                             "Ou clique no link abaixo para redefinir:<br><a href='{$link}'>{$link}</a><br><br>" .
                             "Se você não solicitou isso, ignore este e-mail.";

            $mail->send();
            echo "E-mail de recuperação enviado com sucesso para: " . htmlspecialchars($email);
        } catch (Exception $e) {
            echo "Erro ao enviar o e-mail: {$mail->ErrorInfo}. <br>";
            echo "Código de verificação para testes: <strong>{$codigo}</strong><br>";
            echo "Link: <a href='{$link}'>{$link}</a>";
        }
    } else {
        echo "E-mail não encontrado ou inativo no sistema.";
    }
}
?>