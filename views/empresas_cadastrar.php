<?php
require_once "../config/init.php";

// Apenas ADMIN pode acessar
exigirPerfil(array('ADMIN'));

$userName = $_SESSION['user_name'];

// Lista as empresas cadastradas
$sql = "SELECT * FROM empresas ORDER BY data_cadastro DESC";
$result = mysqli_query($conexao, $sql);
$empresas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-empresas">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Empresas</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastre e gerencie as empresas terceirizadas.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Empresa cadastrada com sucesso!</div>
    <?php endif; ?>

    <!-- Formulário de cadastro -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Nova Empresa</h5>
            <form action="../controllers/empresas_salvar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" id="cnpj" class="form-control" maxlength="18" placeholder="00.000.000/0000-00" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Número do Contrato</label>
                        <input type="text" name="numero_contrato" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Arquivo do Contrato (PDF)</label>
                        <input type="file" name="contrato_arquivo" class="form-control" accept=".pdf">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rua</label>
                        <input type="text" name="rua" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Número</label>
                        <input type="text" name="numero" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="bairro" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" class="form-control" value="Bragança Paulista">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CEP</label>
                        <input type="text" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control">
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Cadastrar Empresa</button>
            </form>
        </div>
    </div>

    <!-- Lista de empresas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Empresas Cadastradas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Contrato</th>
                            <th>Cidade</th>
                            <th>Telefone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empresas) > 0): ?>
                            <?php foreach ($empresas as $empresa): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empresa['nome']); ?></td>
                                    <td><?php echo htmlspecialchars(formatarCNPJ($empresa['cnpj'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($empresa['numero_contrato'] ?? '-'); ?>
                                        <?php if (!empty($empresa['contrato_arquivo'])): ?>
                                            <a href="../<?php echo $empresa['contrato_arquivo']; ?>" target="_blank" class="btn btn-sm btn-info ms-2">PDF</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($empresa['cidade'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($empresa['telefone'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma empresa cadastrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    // Máscara CNPJ
    const cnpjInput = document.getElementById('cnpj');
    cnpjInput.addEventListener('input', function () {
        let value = cnpjInput.value.replace(/\D/g, '');
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
        cnpjInput.value = value;
    });
</script>

</body>
</html>