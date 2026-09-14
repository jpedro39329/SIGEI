<?php
require_once "../../config/init.php";

$tabela = trim($_GET['tabela'] ?? $_POST['tabela'] ?? '');
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$tabela || $id <= 0) {
    header("Location: ../../views/acesso_rapido.php?erro=parametros_invalidos");
    exit();
}

$campoId = '';
$perfilBase = '';

switch ($tabela) {
    case 'admin':
        $campoId = 'id_admin';
        $perfilBase = 'ADMIN';
        $query = "SELECT * FROM `admin` WHERE id_admin = ? LIMIT 1";
        break;

    case 'seduc':
        $campoId = 'id_seduc';
        $perfilBase = 'SEDUC';
        $query = "SELECT * FROM `seduc` WHERE id_seduc = ? AND ativo = 1 LIMIT 1";
        break;

    case 'usuarios_ure':
        $campoId = 'id_usuario_ure';
        $query = "SELECT * FROM `usuarios_ure` WHERE id_usuario_ure = ? AND ativo = 1 LIMIT 1";
        break;

    case 'usuarios_supervisor':
        $campoId = 'id_usuario_supervisor';
        $perfilBase = 'SUPERVISOR';
        $query = "SELECT * FROM `usuarios_supervisor` WHERE id_usuario_supervisor = ? AND ativo = 1 LIMIT 1";
        break;

    case 'usuarios_ue':
        $campoId = 'id_usuario_ue';
        $perfilBase = 'USUARIO_ESCOLA';
        $query = "SELECT * FROM `usuarios_ue` WHERE id_usuario_ue = ? AND ativo = 1 LIMIT 1";
        break;

    case 'usuarios_pae':
        $campoId = 'id_pae';
        $perfilBase = 'PAE';
        $query = "SELECT * FROM `usuarios_pae` WHERE id_pae = ? AND ativo = 1 LIMIT 1";
        break;

    default:
        header("Location: ../../views/acesso_rapido.php?erro=tabela_invalida");
        exit();
}

$stmt = $conexao->prepare($query);
if (!$stmt) {
    header("Location: ../../views/acesso_rapido.php?erro=erro_consulta");
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

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

    $_SESSION['termos_aceitos'] = verificarTermoAceito($conexao, $_SESSION['user_id'], $_SESSION['user_perfil']);

    $stmt->close();
    header("Location: ../../views/dashboard.php");
    exit();
}

$stmt->close();
header("Location: ../../views/acesso_rapido.php?erro=usuario_nao_encontrado");
exit();

