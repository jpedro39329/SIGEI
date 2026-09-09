<?php
// controllers/processa_esqueci_senha.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $usuarioEncontrado = null;

    // Tabelas e colunas de ID correspondentes no banco
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
        if ($tipoPerfil === 'ADMIN') {
            $sql = "SELECT id_admin AS id_ref FROM admin WHERE email = ?";
        } else {
            $sql = "SELECT {$info['id']} AS id_ref FROM {$info['tabela']} WHERE email = ? AND ativo = 1";
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

        // Insere na tabela recuperacao_senha
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

        echo "Link de recuperação gerado com sucesso!<br>";
        echo "Código de verificação: <strong>{$codigo}</strong><br>";
        echo "Clique no link para redefinir: <a href='{$link}'>{$link}</a>";
    } else {
        echo "E-mail não encontrado ou inativo no sistema.";
    }
}
?>