<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

// Lista as empresas ativas
$sqlEmpresas = "SELECT * FROM empresas WHERE ativo = 1 ORDER BY nome ASC";
$resultEmpresas = mysqli_query($conexao, $sqlEmpresas);
$empresas = $resultEmpresas ? mysqli_fetch_all($resultEmpresas, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Supervisor de Licitações e Contratos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-supervisores-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Supervisor de Licitações e Contratos</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/supervisores/salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais e Vínculo</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" maxlength="150" placeholder="Ex.: Roberto Supervisor" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" placeholder="000.000.000-00" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Empresa Contratada <span class="text-danger">*</span></label>
                                <select name="id_empresa" class="form-select" required>
                                    <option value="">Selecione a empresa...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id_empresa']; ?>">
                                            <?php echo htmlspecialchars($emp['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" maxlength="150" placeholder="supervisor@empresa.com.br">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone de Contato</label>
                                <input type="text" name="telefone" class="form-control" maxlength="30" placeholder="(11) 9999-3000">
                            </div>

                            <div class="col-12 mt-3">
                                <h5 class="mb-3">Credenciais de Acesso</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha Inicial <span class="text-danger">*</span></label>
                                <input type="password" name="senha" id="senha" class="form-control" maxlength="255" placeholder="Mínimo 6 caracteres" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" maxlength="255" placeholder="Repita a senha" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-dark">Cadastrar Supervisor</button>
                            <a href="listar.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            v = v.replace(/^(\d{3})(\d)/, '$1.$2');
            v = v.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1-$2');
            e.target.value = v;
        });
    }
});
</script>

</body>
</html>