<?php
require_once "../config/init.php";

if (!isset($_POST['cpf']) || !isset($_POST['senha'])) {
    die("Acesso inválido.");
}

exigirTokenCSRF();

$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$senha = $_POST['senha'];

// O sistema procura o CPF em todas as tabelas de login.
// A segunda posição do array indica se a tabela possui coluna `ativo`
// (a tabela admin não possui).
$tabelas = array(
    'admin'                      => array('id_admin', false),
    'usuarios_escola'            => array('id_usuario_escola', true),
    'usuarios_empresa'           => array('id_usuario_empresa', true),
    'paes'                       => array('id_pae', true),
    'usuarios_sefisc'            => array('id_usuario_sefisc', true),
    'usuarios_educacao_especial' => array('id_usuario_edu', true)
);

// Nome do perfil usado no sistema
$nomesPerfis = array(
    'admin'                        => 'ADMIN',
    'usuarios_escola'              => 'USUARIO_ESCOLA',
    'usuarios_empresa'             => 'USUARIO_EMPRESA',
    'paes'                         => 'PAE',
    'usuarios_sefisc'              => 'USUARIO_SEFISC',
    'usuarios_educacao_especial'   => 'USUARIO_EDUCACAO_ESPECIAL'
);

foreach ($tabelas as $tabela => $info) {
    list($campoId, $temColunaAtivo) = $info;

    if ($temColunaAtivo) {
        $query = "SELECT * FROM `$tabela` WHERE cpf = ? AND ativo = 1 LIMIT 1";
    } else {
        $query = "SELECT * FROM `$tabela` WHERE cpf = ? LIMIT 1";
    }

    $stmt = $conexao->prepare($query);

    if (!$stmt) {
        continue;
    }

    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // A senha no banco está armazenada como hash (password_hash)
        if (password_verify($senha, $user['senha'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']     = $user[$campoId];
            $_SESSION['user_name']   = $user['nome'];
            $_SESSION['user_perfil'] = $nomesPerfis[$tabela];

            header("Location: ../views/dashboard.php");
            exit();
        }
    }

    $stmt->close();
}

echo "CPF ou senha incorretos.";
?>