<?php
session_start();
include("../config/database.php");

if (!isset($_POST['cpf']) || !isset($_POST['senha'])) {
    die("Acesso inválido.");
}

$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$senha = $_POST['senha'];

// O sistema procura o CPF em todas as tabelas de login:
// admin, unidade escolar, empresa, PAE, SEFISC e Educação Especial
$tabelas = array(
    'admin'                        => 'id_admin',
    'usuarios_escola'              => 'id_usuario_escola',
    'usuarios_empresa'             => 'id_usuario_empresa',
    'paes'                         => 'id_pae',
    'usuarios_sefisc'              => 'id_usuario_sefisc',
    'usuarios_educacao_especial'   => 'id_usuario_edu'
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

foreach ($tabelas as $tabela => $campoId) {
    $cpfEscapado = mysqli_real_escape_string($conexao, $cpf);
    $query = "SELECT * FROM $tabela WHERE cpf = '$cpfEscapado'";
    $result = mysqli_query($conexao, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // A senha no banco está armazenada como hash (password_hash)
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id']     = $user[$campoId];
            $_SESSION['user_name']   = $user['nome'];
            $_SESSION['user_perfil'] = $nomesPerfis[$tabela];

            if ($_SESSION['user_perfil'] === 'ADMIN') {
                header("Location: ../views/dashboard_admin.php");
            } else {
                header("Location: ../views/dashboard.php");
            }

            exit();
        }
    }
}

echo "CPF ou senha incorretos.";
?>