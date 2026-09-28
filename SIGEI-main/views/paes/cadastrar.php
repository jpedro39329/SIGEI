<?php
require_once "../../config/init.php";

exigirPerfil(array('SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

// Se for ADMIN ou SEDUC, lista empresas para escolha
$empresas = [];
if (in_array($userPerfil, ['ADMIN', 'SEDUC'])) {
    $res = mysqli_query($conexao, "SELECT * FROM empresas WHERE ativo = 1 ORDER BY nome ASC");
    $empresas = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar PAE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-paes-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-2">Cadastrar Profissional de Apoio Escolar (PAE)</h2>
                    <p class="text-muted mb-4">Olá, <?php echo htmlspecialchars($userName); ?> — preencha os dados do cuidador/PAE.</p>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/paes/salvar.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" placeholder="Ex.: Ana Paula da Silva" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF</label>
                                <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" placeholder="000.000.000-00" required>
                            </div>

                            <?php if (in_array($userPerfil, ['ADMIN', 'SEDUC'])): ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Empresa Terceirizada</label>
                                    <select name="id_empresa" class="form-select" required>
                                        <option value="">Selecione a empresa...</option>
                                        <?php foreach ($empresas as $emp): ?>
                                            <option value="<?php echo $emp['id_empresa']; ?>">
                                                <?php echo htmlspecialchars($emp['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Empresa</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(nomeEmpresaSupervisor($conexao, $userId)); ?>" readonly>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="pae@empresa.com">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" placeholder="(11) 9999-3000">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select" required>
                                    <option value="1" selected>Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Arquivo do Contrato de Trabalho (PDF, JPG, PNG)</label>
                                <input type="file" name="contrato_arquivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark">Cadastrar PAE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            let value = cpfInput.value.replace(/\D/g, '');
            value = value.substring(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            cpfInput.value = value;
        });
    }

    document.querySelector('form').addEventListener('submit', function (e) {
        if (document.getElementById('senha').value !== document.getElementById('confirmar_senha').value) {
            e.preventDefault();
            alert('As senhas não coincidem.');
        }
    });
</script>

</body>
</html>
