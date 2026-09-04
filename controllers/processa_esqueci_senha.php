<?php
// Inclui o arquivo de conexão
require_once __DIR__ . '/../config/database.php';

// Verifica se a conexão com MySQLi está disponível
if (!isset($conexao) || $conexao->connect_error) {
    die("Erro na conexão com o banco de dados.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo "<script>alert('Por favor, digite um e-mail válido.'); window.history.back();</script>";
        exit;
    }

    // Mapeamento das 6 tabelas de usuários do SIGEI
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

    // Busca em qual tabela o e-mail está cadastrado usando MySQLi
    foreach ($tabelas as $tabela => $campoId) {
        $stmt = $conexao->prepare("SELECT $campoId, email FROM $tabela WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (!empty($row['email'])) {
                    $usuarioEncontrado = true;
                    $tipoUsuario = $tabela;
                    $stmt->close();
                    break;
                }
            }
            $stmt->close();
        }
    }

    if ($usuarioEncontrado) {
        // Gera token de segurança e expiração (30 minutos)
        $token = bin2hex(random_bytes(32));
        $dataExpiracao = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Delete tokens antigos para este e-mail
        $stmtDel = $conexao->prepare("DELETE FROM recuperacao_senha WHERE email = ?");
        $stmtDel->bind_param("s", $email);
        $stmtDel->execute();
        $stmtDel->close();

        // Insere o novo token
        $stmtIns = $conexao->prepare("INSERT INTO recuperacao_senha (email, token, tipo_usuario, data_expiracao) VALUES (?, ?, ?, ?)");
        $stmtIns->bind_param("ssss", $email, $token, $tipoUsuario, $dataExpiracao);
        $stmtIns->execute();
        $stmtIns->close();

        // Link de redefinição
        $link = "http://" . $_SERVER['HTTP_HOST'] . "/SIGEI/views/redefinir_senha.php?token=" . $token;

        $assunto = "Redefinicao de Senha - SIGEI";
        $mensagem = "Acesse o link a seguir para cadastrar sua nova senha (válido por 30 minutos):\n\n" . $link;
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];

        @mail($email, $assunto, $mensagem, $headers);
    }

    echo "<script>alert('Se o e-mail estiver cadastrado no sistema, você receberá as instruções para redefinição.'); window.location.href='../views/esqueci_senha.php';</script>";
    exit;
}