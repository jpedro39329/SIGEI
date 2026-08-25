<?php
require_once "../config/init.php";

// Apenas ADMIN pode acessar
exigirPerfil(array('ADMIN'));

$userName = $_SESSION['user_name'];

// Reúne todos os usuários de todas as tabelas
$perfis = array(
    'admin' => array('tabela' => 'admin', 'campo' => 'id_admin', 'perfil' => 'ADMIN'),
    'usuarios_escola' => array('tabela' => 'usuarios_escola', 'campo' => 'id_usuario_escola', 'perfil' => 'USUARIO_ESCOLA'),
    'usuarios_empresa' => array('tabela' => 'usuarios_empresa', 'campo' => 'id_usuario_empresa', 'perfil' => 'USUARIO_EMPRESA'),
    'paes' => array('tabela' => 'paes', 'campo' => 'id_pae', 'perfil' => 'PAE'),
    'usuarios_sefisc' => array('tabela' => 'usuarios_sefisc', 'campo' => 'id_usuario_sefisc', 'perfil' => 'USUARIO_SEFISC'),
    'usuarios_educacao_especial' => array('tabela' => 'usuarios_educacao_especial', 'campo' => 'id_usuario_edu', 'perfil' => 'USUARIO_EDUCACAO_ESPECIAL')
);

$usuarios = array();

foreach ($perfis as $info) {
    $tabela = $info['tabela'];
    $campo = $info['campo'];
    $perfil = $info['perfil'];

    // A tabela `admin` não possui a coluna `ativo`
    if ($tabela === 'admin') {
        $sql = "SELECT nome, cpf, data_cadastro FROM `$tabela`";
    } else {
        $sql = "SELECT nome, cpf, ativo, data_cadastro FROM `$tabela`";
    }

    $result = mysqli_query($conexao, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['perfil'] = $perfil;
            $usuarios[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-usuarios">

<?php require("navbar.php"); ?>

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
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($usuario['cpf'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars(nomePerfil($usuario['perfil'])); ?></td>
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
                                <td colspan="4" class="text-center text-muted">Nenhum usuário cadastrado.</td>
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