<?php
require_once "../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$where = "";
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $where = "WHERE p.id_empresa = $idEmpresaUsuario";
} elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC'])) {
    if ($idUreUsuario <= 0) {
        $idUreUsuario = idUreUsuario($conexao, $userId);
    }
    if ($idUreUsuario > 0) {
        $where = "WHERE p.id_empresa IN (SELECT id_empresa FROM empresa_ure WHERE id_ure = $idUreUsuario)";
    }
}

$sql = "
    SELECT p.id_pae, p.nome, p.cpf, p.email, p.telefone, p.ativo, p.data_cadastro, e.nome AS empresa_nome
    FROM usuarios_pae p
    LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
    $where
    ORDER BY p.data_cadastro DESC
";
$result = mysqli_query($conexao, $sql);
$paes = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profissionais de Apoio Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-paes-listar">

<?php require("navbar.php"); ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Profissionais de Apoio Escolar (PAEs)</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — acompanhe os PAEs cadastrados.</p>
        </div>

        <?php if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'])) { ?>
            <a href="paes_cadastrar.php" class="btn btn-primary">Cadastrar PAE</a>
        <?php } ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'salvo'): ?>
        <div class="alert alert-success">PAE salvo com sucesso.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <?php if ($userPerfil != 'USUARIO_EMPRESA') { ?>
                                <th>Empresa</th>
                            <?php } ?>
                            <th>Status</th>
                            <th>Data de Cadastro</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($paes) > 0): ?>
                            <?php foreach ($paes as $pae): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pae['nome']); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></td>
                                    <?php if ($userPerfil != 'USUARIO_EMPRESA') { ?>
                                        <td><?php echo htmlspecialchars($pae['empresa_nome'] ?? '-'); ?></td>
                                    <?php } ?>
                                    <td>
                                        <?php if ($pae['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($pae['data_cadastro']) ? date('d/m/Y', strtotime($pae['data_cadastro'])) : '-'; ?></td>
                                    <td>
                                        <a href="paes_visualizar.php?id=<?php echo $pae['id_pae']; ?>" class="btn btn-sm btn-info">Ver detalhes</a>
                                        <?php if ($userPerfil == 'USUARIO_EMPRESA'): ?>
                                            <a href="paes_editar.php?id=<?php echo $pae['id_pae']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $userPerfil == 'USUARIO_EMPRESA' ? '5' : '6'; ?>" class="text-center text-muted">Nenhum PAE cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
