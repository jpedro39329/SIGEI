<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

// Lista as UREs
$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$ures = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];

// Lista os dirigentes cadastrados
$sqlDirigentes = "
    SELECT uu.*, u.nome AS ure_nome
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    WHERE uu.setor = 'GABINETE'
    ORDER BY uu.data_cadastro DESC
";
$result = mysqli_query($conexao, $sqlDirigentes);
$dirigentes = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordenadores Dirigentes Regionais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-dirigentes">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Coordenadores Dirigentes Regionais de Ensino</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastro de Dirigentes e associação às suas respectivas UREs (SEDUC-SP).</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Dirigente Regional cadastrado e associado à URE com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de cadastro de Dirigente -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Novo Coordenador Dirigente Regional de Ensino</h5>
            <form action="../../controllers/dirigentes/salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex.: Roberto Alves Figueiredo" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CPF</label>
                        <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" placeholder="000.000.000-00" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Unidade Regional de Ensino (URE)</label>
                        <select name="id_ure" class="form-select" required>
                            <option value="">Selecione a URE...</option>
                            <?php foreach ($ures as $ure): ?>
                                <option value="<?php echo $ure['id_ure']; ?>">
                                    <?php echo htmlspecialchars($ure['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email Institucional</label>
                        <input type="email" name="email" class="form-control" placeholder="dirigente@educacao.sp.gov.br">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 4034-0001">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="ativo" class="form-select">
                            <option value="1" selected>Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Senha de Acesso</label>
                        <input type="password" name="senha" id="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Cadastrar Dirigente</button>
            </form>
        </div>
    </div>

    <!-- Lista de Dirigentes -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Dirigentes Regionais Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Cargo</th>
                            <th>URE Vinculada</th>
                            <th>Contato</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($dirigentes) > 0): ?>
                            <?php foreach ($dirigentes as $dir): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($dir['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($dir['cpf'])); ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($dir['cargo']); ?></span></td>
                                    <td><?php echo htmlspecialchars($dir['ure_nome']); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($dir['email'] ?? '-'); ?><br><?php echo htmlspecialchars($dir['telefone'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($dir['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum dirigente cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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