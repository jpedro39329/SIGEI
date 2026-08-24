<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas ADMIN pode acessar
exigirPerfil(array('ADMIN'));

$userName = $_SESSION['user_name'];

// Lista as escolas cadastradas
$sql = "SELECT * FROM escolas ORDER BY data_cadastro DESC";
$result = mysqli_query($conexao, $sql);
$escolas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-escolas">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Escolas</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastre e gerencie as escolas.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Escola cadastrada com sucesso!</div>
    <?php endif; ?>

    <!-- Formulário de cadastro -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Nova Escola</h5>
            <form action="../controllers/escolas_salvar.php" method="POST">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CIE</label>
                        <input type="text" name="cie" class="form-control" maxlength="6" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modalidade</label>
                        <select name="modalidade" class="form-select" required>
                            <option value="REGULAR">Regular</option>
                            <option value="PEI">PEI</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control">
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
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Horário de Funcionamento</label>
                        <input type="text" name="horario_funcionamento" class="form-control" placeholder="Ex: 07:00 às 17:00">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Cadastrar Escola</button>
            </form>
        </div>
    </div>

    <!-- Lista de escolas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Escolas Cadastradas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CIE</th>
                            <th>Modalidade</th>
                            <th>Cidade</th>
                            <th>Telefone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($escolas) > 0): ?>
                            <?php foreach ($escolas as $escola): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($escola['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($escola['cie']); ?></td>
                                    <td><?php echo $escola['modalidade'] == 'PEI' ? 'PEI' : 'Regular'; ?></td>
                                    <td><?php echo htmlspecialchars($escola['cidade'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($escola['telefone'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma escola cadastrada.</td>
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