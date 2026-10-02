<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar URE - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-ures-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Unidade Regional (URE)</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/ures/salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nome da URE <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" maxlength="150" placeholder="Ex.: Diretoria de Ensino de Bragança Paulista" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código UGE <span class="text-danger">*</span></label>
                                <input type="text" name="uge" class="form-control font-monospace" maxlength="20" placeholder="Ex.: 081240" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Endereço Completo / Sede</label>
                                <input type="text" name="endereco" class="form-control" maxlength="255" placeholder="Ex.: Avenida José Gomes da Rocha Leão, 450 - Centro">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Telefone Institucional</label>
                                <input type="text" name="telefone" class="form-control" maxlength="30" placeholder="Ex.: (11) 4034-7100">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" maxlength="150" placeholder="Ex.: debraganca@educacao.sp.gov.br">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Cadastrar URE</button>
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
