<?php
// controllers/auth/processa_esqueci_senha.php
// Etapa 1 da recuperação de senha: gera e envia um código de 6 dígitos por e-mail.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Caminhos corretos para a estrutura do PHPMailer
require_once '../../phpmailer/src/Exception.php';
require_once '../../phpmailer/src/PHPMailer.php';
require_once '../../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validade do código (minutos)
const RECUPERACAO_VALIDADE_MIN = 15;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['recuperacao_msg_erro'] = 'Informe um e-mail válido.';
    header('Location: ../../views/auth/esqueciminha_senha.php');
    exit;
}

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
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado) {
        $usuarioEncontrado = [
            'tipo_perfil'   => $tipoPerfil,
            'id_referencia' => $resultado['id_ref']
        ];
        break;
    }
}

// Reinicia o estado da recuperação na sessão
unset($_SESSION['recuperacao_id'], $_SESSION['recuperacao_verificada'], $_SESSION['recuperacao_codigo_teste']);
$_SESSION['recuperacao_email'] = $email;
$_SESSION['recuperacao_tentativas'] = 0;

if ($usuarioEncontrado) {
    // Invalida códigos anteriores ainda abertos para este e-mail
    $stmtInv = $conexao->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE contato = ? AND usado = 0");
    $stmtInv->bind_param("s", $email);
    $stmtInv->execute();

    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    // Valor interno apenas para manter a coluna "token" preenchida; nunca é enviado ao usuário.
    $tokenInterno = bin2hex(random_bytes(32));
    $expira_em = date('Y-m-d H:i:s', strtotime('+' . RECUPERACAO_VALIDADE_MIN . ' minutes'));

    $sqlInsert = "INSERT INTO recuperacao_senha (tipo_perfil, id_referencia, token, codigo, tipo_contato, contato, expira_em, usado)
                  VALUES (?, ?, ?, ?, 'EMAIL', ?, ?, 0)";
    $stmtInsert = $conexao->prepare($sqlInsert);
    $stmtInsert->bind_param("sissss",
        $usuarioEncontrado['tipo_perfil'],
        $usuarioEncontrado['id_referencia'],
        $tokenInterno,
        $codigo,
        $email,
        $expira_em
    );
    $stmtInsert->execute();

    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP (Exemplo com Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seu_email@gmail.com'; // Seu e-mail do Gmail
        $mail->Password   = 'sua_senha_de_aplicativo'; // A senha de 16 dígitos gerada no Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Alterado para SMTPS
        $mail->Port       = 465;                        // Alterado para a porta 465
        $mail->CharSet    = 'UTF-8';                   // Porta 465

        // Remetente e Destinatário
        $mail->setFrom('seu_email@gmail.com', 'Sistema SIGEI');
        $mail->addAddress($email);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'SIGEI - Código de Recuperação de Senha';
        $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha no SIGEI.<br>" .
                         "Seu código de verificação é: <b style='font-size:20px;letter-spacing:3px;'>{$codigo}</b><br><br>" .
                         "Este código é válido por " . RECUPERACAO_VALIDADE_MIN . " minutos e só pode ser usado uma vez.<br><br>" .
                         "Se você não solicitou isso, ignore este e-mail.";
        $mail->AltBody = "Seu código de verificação do SIGEI é: {$codigo} (válido por " . RECUPERACAO_VALIDADE_MIN . " minutos).";

        $mail->send();
    } catch (Exception $e) {
        // Não expõe detalhes ao usuário; registra apenas no log do servidor.
        error_log('[SIGEI] Falha ao enviar código de recuperação: ' . $mail->ErrorInfo);
    }

    // Somente em ambiente local (XAMPP) o código fica disponível na tela para testes.
    $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        $_SESSION['recuperacao_codigo_teste'] = $codigo;
    }
}

// Mesma resposta para e-mail cadastrado ou não (evita enumeração de usuários)
$_SESSION['recuperacao_msg_info'] = 'Se o e-mail informado estiver cadastrado, você receberá um código de verificação em instantes.';
header('Location: ../../views/auth/verificar_codigo.php');
exit;
