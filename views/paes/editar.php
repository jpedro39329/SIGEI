<?php
require_once "../../config/init.php";

exigirPerfil(array('SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'));

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$id_pae = (int) ($_GET['id'] ?? 0);

if (in_array($userPerfil, ['ADMIN', 'SEDUC'])) {
    $sql = "SELECT * FROM usuarios_pae WHERE id_pae = $id_pae";
} else {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    $sql = "SELECT * FROM usuarios_pae WHERE id_pae = $id_pae AND id_empresa = $idEmpresa";
}

$result = mysqli_query($conexao, $sql);
$pae = $result ? mysqli_fetch_assoc($result) : null;

if (!$pae) {
    die("PAE não encontrado ou não pertence à sua empresa.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar PAE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-paes-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar PAE</h2>

                    <form action="../../controllers/paes/editar_salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_pae" value="<?php echo $pae['id_pae']; ?>">

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($pae['nome']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select" required>
                                    <option value="1" <?php echo $pae['ativo'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $pae['ativo'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($pae['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($pae['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Salvar</button>
                            <a href="visualizar.php?id=<?php echo $pae['id_pae']; ?>" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
