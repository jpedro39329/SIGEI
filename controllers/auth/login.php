<?php
require_once "../../config/init.php";

exigirTokenCSRF();

$perfil = trim($_POST['perfil'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($cpf === '' || $senha === '') {
    $redirectUrl = "../../views/login.php" . ($perfil ? "?perfil=" . urlencode($perfil) . "&erro=dados_invalidos" : "?erro=dados_invalidos");
    header("Location: $redirectUrl");
    exit();
}

$tabelas = [];

switch ($perfil) {
    case 'pae':
        $tabelas['usuarios_pae'] = ['id_pae', true, 'PAE', null];
        break;

    case 'escola':
    case 'ue':
        $tabelas['usuarios_ue'] = ['id_usuario_ue', true, 'USUARIO_ESCOLA', null];
        break;

    case 'supervisor':
    case 'empresa':
        $tabelas['usuarios_supervisor'] = ['id_usuario_supervisor', true, 'SUPERVISOR', null];
        break;

    case 'educacao_especial':
    case 'eec':
    case 'aee':
        $tabelas['usuarios_ure'] = ['id_usuario_ure', true, 'USUARIO_EDUCACAO_ESPECIAL', 'EDU_ESPECIAL'];
        break;

    case 'sefisc':
        $tabelas['usuarios_ure'] = ['id_usuario_ure', true, 'USUARIO_SEFISC', 'SEFISC'];
        break;

    case 'dirigente':
        $tabelas['usuarios_ure'] = ['id_usuario_ure', true, 'DIRIGENTE', 'GABINETE'];
        break;

    case 'seduc':
    case 'admin':
        $tabelas['admin'] = ['id_admin', false, 'ADMIN', null];
        $tabelas['seduc'] = ['id_seduc', true, 'SEDUC', null];
        break;

    default:
        $tabelas = [
            'admin'               => ['id_admin', false, 'ADMIN', null],
            'seduc'               => ['id_seduc', true, 'SEDUC', null],
            'usuarios_ure'        => ['id_usuario_ure', true, 'URE', null],
            'usuarios_supervisor' => ['id_usuario_supervisor', true, 'SUPERVISOR', null],
            'usuarios_ue'         => ['id_usuario_ue', true, 'USUARIO_ESCOLA', null],
            'usuarios_pae'        => ['id_pae', true, 'PAE', null]
        ];
        break;
}

foreach ($tabelas as $tabela => $info) {
    list($campoId, $temColunaAtivo, $perfilBase, $filtroSetor) = $info;

    if ($tabela === 'usuarios_ure' && $filtroSetor !== null) {
        $query = "SELECT * FROM `$tabela` WHERE cpf = ? AND setor = ? AND ativo = 1 LIMIT 1";
        $stmt = $conexao->prepare($query);
        if (!$stmt) continue;
        $stmt->bind_param("ss", $cpf, $filtroSetor);
    } elseif ($temColunaAtivo) {
        $query = "SELECT * FROM `$tabela` WHERE cpf = ? AND ativo = 1 LIMIT 1";
        $stmt = $conexao->prepare($query);
        if (!$stmt) continue;
        $stmt->bind_param("s", $cpf);
    } else {
        $query = "SELECT * FROM `$tabela` WHERE cpf = ? LIMIT 1";
        $stmt = $conexao->prepare($query);
        if (!$stmt) continue;
        $stmt->bind_param("s", $cpf);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($senha, $user['senha'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int) $user[$campoId];
            $_SESSION['user_name'] = $user['nome'];

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
            header("Location: ../../views/dashboard.php");
            exit();
        }
    }

    $stmt->close();
}

$redirectUrl = "../../views/login.php" . ($perfil ? "?perfil=" . urlencode($perfil) . "&erro=usuario_nao_encontrado" : "?erro=usuario_nao_encontrado");
header("Location: $redirectUrl");
exit();
?>