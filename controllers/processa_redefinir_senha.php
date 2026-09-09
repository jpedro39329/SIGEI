<?php
// controllers/processa_redefinir_senha.php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $codigoInformado = trim($_POST['codigo']);
    $novaSenha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);

    $sql = "SELECT * FROM recuperacao_senha WHERE token = ? AND codigo = ? AND usado = 0 AND expira_em >= NOW()";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $token, $codigoInformado);
    $stmt->execute();
    $recuperacao = $stmt->get_result()->fetch_assoc();

    if ($recuperacao) {
        $tipoPerfil = $recuperacao['tipo_perfil'];
        $idReferencia = $recuperacao['id_referencia'];

        $tabelasPerfis = [
            'SEDUC'      => ['tabela' => 'seduc', 'id' => 'id_seduc'],
            'URE'        => ['tabela' => 'usuarios_ure', 'id' => 'id_usuario_ure'],
            'SUPERVISOR' => ['tabela' => 'usuarios_supervisor', 'id' => 'id_usuario_supervisor'],
            'ESCOLA'     => ['tabela' => 'usuarios_ue', 'id' => 'id_usuario_ue'],
            'PAE'        => ['tabela' => 'usuarios_pae', 'id' => 'id_pae']
        ];

        if (isset($tabelasPerfis[$tipoPerfil])) {
            $info = $tabelasPerfis[$tipoPerfil];
            
            $updateSenha = "UPDATE {$info['tabela']} SET senha = ? WHERE {$info['id']} = ?";
            $stmtUpdate = $conexao->prepare($updateSenha);
            $stmtUpdate->bind_param("si", $novaSenha, $idReferencia);
            $stmtUpdate->execute();

            $updateToken = "UPDATE recuperacao_senha SET usado = 1 WHERE token = ?";
            $stmtToken = $conexao->prepare($updateToken);
            $stmtToken->bind_param("s", $token);
            $stmtToken->execute();

            echo "Senha alterada com sucesso! <a href='../views/login.php'>Clique aqui para entrar</a>";
        } else {
            echo "Erro: Perfil desconhecido.";
        }
    } else {
        echo "Erro: Código de verificação incorreto, token inválido ou expirado.";
    }
}
?>