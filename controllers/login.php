<?php
require_once "../config/init.php";

if (!isset($_POST['cpf']) || !isset($_POST['senha'])) {
    die("Acesso inválido.");
}

exigirTokenCSRF();

$cpf = preg_replace('/\D/', '', $_POST['cpf']);
$senha = $_POST['senha'];

// Configuração das tabelas de autenticação do sistema
$tabelas = array(
    'admin'               => array('id_admin', false, 'ADMIN'),
    'seduc'               => array('id_seduc', true, 'SEDUC'),
    'usuarios_ure'        => array('id_usuario_ure', true, 'URE'),
    'usuarios_supervisor' => array('id_usuario_supervisor', true, 'SUPERVISOR'),
    'usuarios_ue'         => array('id_usuario_ue', true, 'USUARIO_ESCOLA'),
    'usuarios_pae'        => array('id_pae', true, 'PAE')
);

foreach ($tabelas as $tabela => $info) {
    list($campoId, $temColunaAtivo, $perfilBase) = $info;

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

        if (password_verify($senha, $user['senha'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int) $user[$campoId];
            $_SESSION['user_name'] = $user['nome'];

            // Define o perfil exato conforme tabela / setor
            if ($tabela === 'usuarios_ure') {
                $setor = $user['setor'] ?? '';
                if ($setor === 'GABINETE') {
                    $_SESSION['user_perfil'] = 'DIRIGENTE';
                } elseif ($setor === 'SEFISC') {
                    $_SESSION['user_perfil'] = 'USUARIO_SEFISC';
                } elseif ($setor === 'EDU_ESPECIAL') {
                    $_SESSION['user_perfil'] = 'USUARIO_EDUCACAO_ESPECIAL';
                } else {
                    $_SESSION['user_perfil'] = 'DIRIGENTE';
                }
                $_SESSION['id_ure']       = (int) ($user['id_ure'] ?? 0);
                $_SESSION['user_setor']   = $user['setor'] ?? '';
                $_SESSION['user_cargo']   = $user['cargo'] ?? '';
                $_SESSION['nivel_acesso'] = (int) ($user['nivel_acesso'] ?? 1);
            } elseif ($tabela === 'seduc') {
                $_SESSION['user_perfil'] = 'SEDUC';
                $_SESSION['user_setor']  = $user['setor'] ?? '';
                $_SESSION['user_cargo']  = $user['cargo'] ?? '';
            } elseif ($tabela === 'usuarios_supervisor') {
                $_SESSION['user_perfil'] = 'SUPERVISOR';
                $_SESSION['id_empresa']  = (int) ($user['id_empresa'] ?? 0);
            } elseif ($tabela === 'usuarios_ue') {
                $_SESSION['user_perfil'] = 'USUARIO_ESCOLA';
                $_SESSION['id_ue']       = (int) ($user['id_ue'] ?? 0);
            } elseif ($tabela === 'usuarios_pae') {
                $_SESSION['user_perfil'] = 'PAE';
                $_SESSION['id_empresa']  = (int) ($user['id_empresa'] ?? 0);
            } else {
                $_SESSION['user_perfil'] = $perfilBase;
            }

            $stmt->close();
            header("Location: ../views/dashboard.php");
            exit();
        }
    }

    $stmt->close();
}

echo "CPF ou senha incorretos.";
?>