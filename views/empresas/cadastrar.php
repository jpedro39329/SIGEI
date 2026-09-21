<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

// Lista as UREs disponíveis
$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$todasUres = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];

// Lista as empresas cadastradas com suas UREs associadas
$sqlEmpresas = "
    SELECT e.*,
           GROUP_CONCAT(u.nome SEPARATOR ', ') AS ures_atendidas
    FROM empresas e
    LEFT JOIN empresa_ure eu ON e.id_empresa = eu.id_empresa
    LEFT JOIN unidades_regionais u ON eu.id_ure = u.id_ure
    GROUP BY e.id_empresa
    ORDER BY e.data_cadastro DESC
";
$result = mysqli_query($conexao, $sqlEmpresas);
$empresas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas Contratadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-empresas">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Empresas Contratadas (Licitações)</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastre as empresas prestadoras e vincule as UREs atendidas.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Empresa e vínculos com UREs cadastrados com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de cadastro -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Nova Empresa Contratada</h5>
            <form action="../../controllers/empresas/salvar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Razão Social / Nome Fantasia</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex.: Apoio Inclusivo Ltda" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" id="cnpj" class="form-control" maxlength="18" placeholder="00.000.000/0000-00" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status da Empresa</label>
                        <select name="ativo" class="form-select">
                            <option value="1" selected>Ativa (Contrato Vigente)</option>
                            <option value="0">Inativa (Histórico / Encerrado)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Número do Contrato / Edital</label>
                        <input type="text" name="numero_contrato" class="form-control" placeholder="Ex.: CT-2026-001">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Arquivo do Contrato (PDF)</label>
                        <input type="file" name="contrato_arquivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 9999-2000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email de Contato</label>
                        <input type="email" name="email" class="form-control" placeholder="contato@empresa.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" class="form-control" value="Bragança Paulista">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Rua / Logradouro</label>
                        <input type="text" name="rua" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Número</label>
                        <input type="text" name="numero" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="bairro" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">CEP</label>
                        <input type="text" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
                    </div>

                    <!-- Seleção de UREs atendidas -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">UREs Atendidas por esta Empresa (Lote de Licitação):</label>
                        <div class="row">
                            <?php if (count($todasUres) > 0): ?>
                                <?php foreach ($todasUres as $ure): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="ures[]" value="<?php echo $ure['id_ure']; ?>" id="ure_<?php echo $ure['id_ure']; ?>">
                                            <label class="form-check-label" for="ure_<?php echo $ure['id_ure']; ?>">
                                                <strong><?php echo htmlspecialchars($ure['nome']); ?></strong>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small">Nenhuma URE cadastrada ainda.</p>
                            <?php endif; ?>
                        </div>
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
                            <th>Razão Social</th>
                            <th>CNPJ</th>
                            <th>Contrato</th>
                            <th>UREs Atendidas</th>
                            <th>Status</th>
                            <th>Contato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empresas) > 0): ?>
                            <?php foreach ($empresas as $empresa): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($empresa['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCNPJ($empresa['cnpj'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($empresa['numero_contrato'] ?? '-'); ?>
                                        <?php if (!empty($empresa['contrato_arquivo'])): ?>
                                            <a href="../../<?php echo htmlspecialchars($empresa['contrato_arquivo']); ?>" target="_blank" class="btn btn-sm btn-outline-info ms-1">PDF</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($empresa['ures_atendidas'])): ?>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($empresa['ures_atendidas']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Nenhuma vinculada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($empresa['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($empresa['email'] ?? '-'); ?><br><?php echo htmlspecialchars($empresa['telefone'] ?? ''); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhuma empresa cadastrada.</td>
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
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function () {
            let value = cnpjInput.value.replace(/\D/g, '');
            value = value.substring(0, 14);
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            cnpjInput.value = value;
        });
    }
</script>

</body>
</html>