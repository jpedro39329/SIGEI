<?php
require_once "../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

// Lista as UREs cadastradas
$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM unidades_escolares ue WHERE ue.id_ure = u.id_ure) AS total_escolas,
           (SELECT COUNT(*) FROM usuarios_ure uu WHERE uu.id_ure = u.id_ure AND uu.ativo = 1) AS total_usuarios
    FROM unidades_regionais u
    ORDER BY u.id_ure ASC
";
$result = mysqli_query($conexao, $sql);
$ures = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Regionais de Ensino (UREs)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-ures">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Unidades Regionais de Ensino (UREs)</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastro e gestão das UREs (SEDUC-SP).</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">URE cadastrada com sucesso!</div>
    <?php endif; ?>

    <!-- Formulário de cadastro de URE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Cadastrar Nova URE</h5>
            <form action="../controllers/ures_salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome da URE</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex.: Unidade Regional de Ensino de Bragança Paulista" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco" class="form-control" placeholder="Ex.: Rua Cel. Teófilo Leme, 100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 4034-0001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Institucional</label>
                        <input type="email" name="email" class="form-control" placeholder="ure.exemplo@educacao.sp.gov.br">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Cadastrar URE</button>
            </form>
        </div>
    </div>

    <!-- Lista de UREs -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">UREs Cadastradas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Escolas</th>
                            <th>Servidores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ures) > 0): ?>
                            <?php foreach ($ures as $ure): ?>
                                <tr>
                                    <td><strong>#<?php echo $ure['id_ure']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($ure['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($ure['endereco'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($ure['telefone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($ure['email'] ?? '-'); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $ure['total_escolas']; ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo $ure['total_usuarios']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhuma URE cadastrada.</td>
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