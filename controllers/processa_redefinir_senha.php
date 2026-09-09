<?php
// controllers/processa_redefinir_senha.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $novaSenha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);

    // Valida o token novamente por segurança
    $sql = "SELECT * FROM recuperacao_senha WHERE token = :token AND usado = 0 AND expira_em >= NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['token' => $token]);
    $recuperacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($recuperacao) {
        $tipoPerfil = $recuperacao['tipo_perfil'];
        $idReferencia = $recuperacao['id_referencia'];

        // Mapeia a tabela e a coluna de ID correspondente para atualizar a senha
        $tabelasPerfis = [
            'ADMIN'      => ['tabela' => 'admin', 'id' => 'id_admin'],
            'SEDUC'      => ['tabela' => 'seduc', 'id' => 'id_seduc'],
            'URE'        => ['tabela' => 'usuarios_ure', 'id' => 'id_usuario_ure'],
            'SUPERVISOR' => ['tabela' => 'usuarios_supervisor', 'id' => 'id_usuario_supervisor'],
            'ESCOLA'     => ['tabela' => 'usuarios_ue', 'id' => 'id_usuario_ue'],
            'PAE'        => ['tabela' => 'usuarios_pae', 'id' => 'id_pae']
        ];

        if (isset($tabelasPerfis[$tipoPerfil])) {
            $info = $tabelasPerfis[$tipoPerfil];
            
            // Atualiza a senha na tabela específica do perfil
            $updateSenha = "UPDATE {$info['tabela']} SET senha = :senha WHERE {$info['id']} = :id";
            $stmtUpdate = $pdo->prepare($updateSenha);
            $stmtUpdate->execute([
                'senha' => $novaSenha,
                'id' => $idReferencia
            ]);

            // Marca o token como usado na tabela recuperacao_senha[cite: 3]
            $updateToken = "UPDATE recuperacao_senha SET usado = 1 WHERE token = :token";
            $stmtToken = $pdo->prepare($updateToken);
            $stmtToken->execute(['token' => $token]);

            echo "Senha alterada com sucesso! <a href='../views/login.php'>Clique aqui para entrar</a>";
        } else {
            echo "Erro: Perfil desconhecido.";
        }
    } else {
        echo "Erro: Token inválido ou expirado.";
    }
}
?>