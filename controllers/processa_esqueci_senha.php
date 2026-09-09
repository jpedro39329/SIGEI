<?php
// controllers/processa_esqueci_senha.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $usuarioEncontrado = null;

    // Mapeamento das tabelas, colunas de ID, perfil e colunas de e-mail/senha
    $tabelasPerfis = [
        'ADMIN'      => ['tabela' => 'admin', 'id' => 'id_admin'],
        'SEDUC'      => ['tabela' => 'seduc', 'id' => 'id_seduc'],
        'URE'        => ['tabela' => 'usuarios_ure', 'id' => 'id_usuario_ure'],
        'SUPERVISOR' => ['tabela' => 'usuarios_supervisor', 'id' => 'id_usuario_supervisor'],
        'ESCOLA'     => ['tabela' => 'usuarios_ue', 'id' => 'id_usuario_ue'],
        'PAE'        => ['tabela' => 'usuarios_pae', 'id' => 'id_pae']
    ];

    // Busca em qual tabela o e-mail está cadastrado
    foreach ($tabelasPerfis as $tipoPerfil => $info) {
        $sql = "SELECT {$info['id']} AS id_ref FROM {$info['tabela']} WHERE email = :email AND ativo = 1";
        // Nota: a tabela admin e seduc podem não ter coluna 'ativo' explícita ou ter, ajustamos conforme o esquema
        if ($tipoPerfil === 'ADMIN') {
            $sql = "SELECT id_admin AS id_ref FROM admin WHERE email = :email"; // admin não tem coluna ativo no schema
        } elseif ($tipoPerfil === 'SEDUC') {
            $sql = "SELECT id_seduc AS id_ref FROM seduc WHERE email = :email AND ativo = 1";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $usuarioEncontrado = [
                'tipo_perfil' => $tipoPerfil,
                'id_referencia' => $resultado['id_ref']
            ];
            break;
        }
    }

    if ($usuarioEncontrado) {
        $token = bin2hex(random_bytes(32)); // 64 caracteres max conforme varchar(64) da tabela recuperacao_senha
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // Código de 6 dígitos
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Insere na tabela recuperacao_senha existente no schema[cite: 3]
        $sqlInsert = "INSERT INTO recuperacao_senha (tipo_perfil, id_referencia, token, codigo, tipo_contato, contato, expira_em, usado) 
                      VALUES (:tipo_perfil, :id_referencia, :token, :codigo, 'EMAIL', :contato, :expira_em, 0)";
        
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'tipo_perfil' => $usuarioEncontrado['tipo_perfil'],
            'id_referencia' => $usuarioEncontrado['id_referencia'],
            'token' => $token,
            'codigo' => $codigo,
            'contato' => $email,
            'expira_em' => $expira_em
        ]);

        $link = "http://localhost/sigei/views/redefinir_senha.php?token=" . $token;

        // Simulação de envio (substituir por PHPMailer em produção)
        echo "Link de recuperação gerado com sucesso!<br>";
        echo "Código de verificação: <strong>{$codigo}</strong><br>";
        echo "Clique no link para redefinir: <a href='{$link}'>{$link}</a>";
    } else {
        echo "E-mail não encontrado ou inativo no sistema.";
    }
}
?>