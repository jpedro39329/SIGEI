<?php
require_once "../config/init.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$userId     = $_SESSION['user_id'];
$userName   = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];

// Escolhe a tabela correta conforme o perfil
$tabelasPerfil = array(
    'ADMIN'                     => array('admin',               'id_admin'),
    'SEDUC'                     => array('seduc',               'id_seduc'),
    'DIRIGENTE'                 => array('usuarios_ure',        'id_usuario_ure'),
    'USUARIO_SEFISC'            => array('usuarios_ure',        'id_usuario_ure'),
    'USUARIO_EDUCACAO_ESPECIAL' => array('usuarios_ure',        'id_usuario_ure'),
    'SUPERVISOR'                => array('usuarios_supervisor', 'id_usuario_supervisor'),
    'USUARIO_EMPRESA'           => array('usuarios_supervisor', 'id_usuario_supervisor'),
    'USUARIO_ESCOLA'            => array('usuarios_ue',         'id_usuario_ue'),
    'USUARIO_UE'                => array('usuarios_ue',         'id_usuario_ue'),
    'ESCOLA'                    => array('usuarios_ue',         'id_usuario_ue'),
    'PAE'                       => array('usuarios_pae',        'id_pae')
);

$tabela = $tabelasPerfil[$userPerfil][0] ?? 'admin';
$campoId = $tabelasPerfil[$userPerfil][1] ?? 'id_admin';

$sql = "SELECT * FROM $tabela WHERE $campoId = $userId";
$result = mysqli_query($conexao, $sql);
$usuario = mysqli_fetch_assoc($result);

if (!$usuario) {
    die("Usuário não encontrado.");
}

// Formata o CPF (gravado sem separadores)
$cpf = preg_replace('/\D/', '', $usuario['cpf'] ?? '');
if (strlen($cpf) === 11) {
    $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Determina cargo, setor e vínculo UGE
$cargo = '';
$setor = '';
$ugeFormatada = '';

if ($tabela === 'usuarios_ure') {
    $cargo = !empty($usuario['cargo']) ? $usuario['cargo'] : 'Assistente Técnico';
    $setor = $usuario['setor'] ?? '';
    if (!empty($usuario['id_ure'])) {
        $rUre = mysqli_query($conexao, "SELECT nome, uge FROM unidades_regionais WHERE id_ure = " . (int)$usuario['id_ure']);
        if ($rUre && $ureRow = mysqli_fetch_assoc($rUre)) {
            $ugeFormatada = ($ureRow['uge'] ? $ureRow['uge'] . ' - ' : '') . $ureRow['nome'];
        }
    }
} elseif ($tabela === 'usuarios_ue') {
    $cargo = !empty($usuario['cargo']) ? $usuario['cargo'] : 'Gestor Escolar';
    if (!empty($usuario['id_ue'])) {
        $rUe = mysqli_query($conexao, "
            SELECT ue.nome, ue.cie, u.nome AS ure_nome, u.uge AS ure_uge
            FROM unidades_escolares ue
            JOIN unidades_regionais u ON ue.id_ure = u.id_ure
            WHERE ue.id_ue = " . (int)$usuario['id_ue']
        );
        if ($rUe && $ueRow = mysqli_fetch_assoc($rUe)) {
            $ugeFormatada = ($ueRow['ure_uge'] ? $ueRow['ure_uge'] . ' - ' : '') . $ueRow['ure_nome'];
        }
    }
} elseif ($tabela === 'usuarios_supervisor' || $tabela === 'usuarios_pae') {
    $cargo = $tabela === 'usuarios_supervisor' ? 'Supervisor de Licitações e Contratos' : 'Profissional de Apoio Escolar';
    if (!empty($usuario['id_empresa'])) {
        $rEmp = mysqli_query($conexao, "SELECT nome FROM empresas WHERE id_empresa = " . (int)$usuario['id_empresa']);
        if ($rEmp && $empRow = mysqli_fetch_assoc($rEmp)) {
            $ugeFormatada = $empRow['nome'];
        }
    }
} elseif ($tabela === 'seduc') {
    $cargo = !empty($usuario['cargo']) ? $usuario['cargo'] : 'Servidor Central';
    $setor = $usuario['setor'] ?? 'Órgão Central';
    $ugeFormatada = 'SEDUC - Secretaria da Educação do Estado de São Paulo';
} elseif ($tabela === 'admin') {
    $cargo = 'Administrador do Sistema';
    $ugeFormatada = 'Administração Geral';
}

$homePage = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/imgs/favicon.png">
</head>
<body class="page-perfil">

<?php require("../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card card-form border-0 shadow-sm">
                <div class="card-body p-4">

                    <h2 class="mb-3">Meu Perfil</h2>

                    <p class="text-muted mb-4">
                        Olá, <strong><?php echo htmlspecialchars($userName); ?></strong> — estas são as informações cadastradas da sua conta no SIGEI.
                    </p>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">Nome</span>
                            <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">CPF</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($cpf); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">E-mail</span>
                            <span><?php echo htmlspecialchars($usuario['email'] ?? 'Não informado'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">Cargo</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($cargo); ?></span>
                        </li>
                        <?php if ($setor): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">Setor</span>
                            <span><?php echo htmlspecialchars($setor); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if ($ugeFormatada): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">UGE</span>
                            <span class="text-end fw-semibold"><?php echo htmlspecialchars($ugeFormatada); ?></span>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted">Telefone</span>
                            <span><?php echo htmlspecialchars(formatarTelefone($usuario['telefone'] ?? '') ?: 'Não informado'); ?></span>
                        </li>
                    </ul>

                    <div class="d-flex gap-2 mt-4">
                        <a href="<?php echo $homePage; ?>" class="btn btn-secondary flex-grow-1">Voltar ao Painel</a>
                        <a href="../controllers/auth/logout.php" class="btn btn-danger">Sair</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>