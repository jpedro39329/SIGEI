<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

$usuarios = array();

// 1. Admin
$res = mysqli_query($conexao, "SELECT nome, cpf, 1 AS ativo, data_cadastro, 'ADMIN' AS perfil, 'Administração Geral' AS vinculo FROM admin");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $usuarios[] = $r;
}

// 2. SEDUC
$res = mysqli_query($conexao, "SELECT nome, cpf, ativo, data_cadastro, 'SEDUC' AS perfil, CONCAT(setor, ' - ', cargo) AS vinculo FROM seduc");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $usuarios[] = $r;
}

// 3. Usuarios URE
$res = mysqli_query($conexao, "
    SELECT uu.nome, uu.cpf, uu.ativo, uu.data_cadastro,
           CASE
               WHEN uu.setor = 'GABINETE' THEN 'DIRIGENTE'
               WHEN uu.setor = 'SEFISC' THEN 'USUARIO_SEFISC'
               WHEN uu.setor = 'EDU_ESPECIAL' THEN 'USUARIO_EDUCACAO_ESPECIAL'
               ELSE 'DIRIGENTE'
           END AS perfil,
           CONCAT(u.nome, ' (', uu.cargo, ')') AS vinculo
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $usuarios[] = $r;
}

// 4. Supervisores
$res = mysqli_query($conexao, "
    SELECT us.nome, us.cpf, us.ativo, us.data_cadastro, 'SUPERVISOR' AS perfil, e.nome AS vinculo
    FROM usuarios_supervisor us
    JOIN empresas e ON us.id_empresa = e.id_empresa
");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $usuarios[] = $r;
}

// 5. Usuarios UE
$res = mysqli_query($conexao, "
    SELECT uue.nome, uue.cpf, uue.ativo, uue.data_cadastro, 'USUARIO_ESCOLA' AS perfil, ue.nome AS vinculo
    FROM usuarios_ue uue
    JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $usuarios[] = $r;
}

// 6. PAEs
$res = mysqli_query($conexao, "
    SELECT p.nome, p.cpf, p.ativo, p.data_cadastro, 'PAE' AS perfil, e.nome AS vinculo
    FROM usuarios_pae p
    JOIN empresas e ON p.id_empresa = e.id_empresa
");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $usuarios[] = $r;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-usuarios">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Usuários do Sistema</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — todos os usuários de todos os perfis.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Perfil</th>
                            <th>Vínculo / Lotação</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($usuario['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($usuario['cpf'] ?? '')); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(nomePerfil($usuario['perfil'])); ?></span></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($usuario['vinculo'] ?? '-'); ?></small></td>
                                    <td>
                                        <?php if (($usuario['ativo'] ?? 1) == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum usuário cadastrado.</td>
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