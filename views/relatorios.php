<?php
require_once "../config/init.php";

// Apenas PAE pode acessar
exigirPerfil(array('PAE'));

$userName = $_SESSION['user_name'];
$id_pae = $_SESSION['user_id'];

// Alunos associados ao PAE
$sqlAlunos = "
    SELECT a.id_aluno, a.nome FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    WHERE ass.id_pae = $id_pae AND ass.ativo = 1
    ORDER BY a.nome
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunos = mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC);

// Relatórios do PAE logado
$sqlRelatorios = "
    SELECT r.id_relatorio, r.tipo, r.descricao, r.data_cadastro, a.nome AS aluno_nome
    FROM relatorios r
    JOIN associacoes ass ON r.id_associacao = ass.id_associacao
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    WHERE ass.id_pae = $id_pae
    ORDER BY r.data_cadastro DESC
";
$resultRelatorios = mysqli_query($conexao, $sqlRelatorios);
$relatorios = mysqli_fetch_all($resultRelatorios, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-relatorios">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Meus Relatórios</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — registre relatórios dos seus alunos.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Relatório salvo com sucesso!</div>
    <?php endif; ?>

    <!-- Formulário de novo relatório -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Novo Relatório</h5>
            <form action="../controllers/relatorios_salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Aluno</label>
                        <select name="id_aluno" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id_aluno']; ?>"><?php echo htmlspecialchars($aluno['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="DIARIO">Diário</option>
                            <option value="MENSAL">Mensal</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" rows="4" class="form-control" required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Registrar</button>
            </form>
        </div>
    </div>

    <!-- Lista de relatórios -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Relatórios Registrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($relatorios) > 0): ?>
                            <?php foreach ($relatorios as $relatorio): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($relatorio['aluno_nome']); ?></td>
                                    <td><?php echo $relatorio['tipo'] == 'DIARIO' ? 'Diário' : 'Mensal'; ?></td>
                                    <td><?php echo htmlspecialchars($relatorio['descricao']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($relatorio['data_cadastro'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nenhum relatório registrado.</td>
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