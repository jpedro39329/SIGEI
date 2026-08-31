<?php
require_once "../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

// Lista as empresas ativas
$sqlEmpresas = "SELECT * FROM empresas ORDER BY nome ASC";
$resultEmpresas = mysqli_query($conexao, $sqlEmpresas);
$empresas = $resultEmpresas ? mysqli_fetch_all($resultEmpresas, MYSQLI_ASSOC) : [];

// Lista os supervisores cadastrados
$sqlSupervisores = "
    SELECT us.*, e.nome AS empresa_nome,
           (SELECT GROUP_CONCAT(u.nome SEPARATOR ', ')
            FROM empresa_ure eu
            JOIN unidades_regionais u ON eu.id_ure = u.id_ure
            WHERE eu.id_empresa = us.id_empresa) AS ures_atendidas
    FROM usuarios_supervisor us
    JOIN empresas e ON us.id_empresa = e.id_empresa
    ORDER BY us.data_cadastro DESC
";
$result = mysqli_query($conexao, $sqlSupervisores);
$supervisores = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisores de Empresas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-supervisores">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Supervisores de Empresas Contratadas</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastro de supervisores vinculados às empresas (SEDUC-SP).</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Supervisor cadastrado com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de cadastro de Supervisor -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Novo Supervisor de Empresa</h5>
            <form action="../controllers/supervisores_salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex.: Roberto Supervisor" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CPF</label>
                        <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" placeholder="000.000.000-00" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Empresa Contratada</label>
                        <select name="id_empresa" class="form-select" required>
                            <option value="">Selecione a empresa...</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?php echo $emp['id_empresa']; ?>">
                                    <?php echo htmlspecialchars($emp['nome']); ?> (CNPJ: <?php echo htmlspecialchars(formatarCNPJ($emp['cnpj'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="supervisor@empresa.com">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 9999-3000">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="ativo" class="form-select">
                            <option value="1" selected>Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
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
                <button type="submit" class="btn btn-dark">Cadastrar Supervisor</button>
            </form>
        </div>
    </div>

    <!-- Lista de Supervisores -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Supervisores Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Empresa</th>
                            <th>UREs Atendidas pela Empresa</th>
                            <th>Contato</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($supervisores) > 0): ?>
                            <?php foreach ($supervisores as $sup): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sup['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($sup['cpf'])); ?></td>
                                    <td><?php echo htmlspecialchars($sup['empresa_nome']); ?></td>
                                    <td>
                                        <?php if (!empty($sup['ures_atendidas'])): ?>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($sup['ures_atendidas']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Nenhuma URE associada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($sup['email'] ?? '-'); ?><br><?php echo htmlspecialchars($sup['telefone'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($sup['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum supervisor cadastrado.</td>
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