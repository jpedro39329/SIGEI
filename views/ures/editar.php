<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("URE não informada."));
    exit();
}

$stmt = $conexao->prepare("SELECT * FROM unidades_regionais WHERE id_ure = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$ure = $res->fetch_assoc();

if (!$ure) {
    header("Location: listar.php?erro=" . urlencode("URE não encontrada."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar URE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-ures-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Unidade Regional (URE)</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/ures/editar_salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_ure" value="<?php echo $ure['id_ure']; ?>">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nome da URE <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($ure['nome']); ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código UGE <span class="text-danger">*</span></label>
                                <input type="text" name="uge" class="form-control" value="<?php echo htmlspecialchars($ure['uge'] ?? ''); ?>" maxlength="20" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Endereço Completo</label>
                                <input type="text" name="endereco" class="form-control" value="<?php echo htmlspecialchars($ure['endereco'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($ure['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($ure['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Salvar Alterações</button>
                            <a href="listar.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>
