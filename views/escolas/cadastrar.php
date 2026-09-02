<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

// Lista as UREs disponíveis
$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$todasUres = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];

// Filtro de listagem
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0) {
    $whereEscola = "WHERE ue.id_ure = $idUreUsuario";
} else {
    $whereEscola = "";
}

$sql = "
    SELECT ue.*, u.nome AS ure_nome,
           (SELECT COUNT(*) FROM alunos a WHERE a.id_ue = ue.id_ue) AS total_alunos,
           (SELECT COUNT(*) FROM usuarios_ue uue WHERE uue.id_ue = ue.id_ue AND uue.ativo = 1) AS total_usuarios
    FROM unidades_escolares ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    $whereEscola
    ORDER BY ue.nome ASC
";
$result = mysqli_query($conexao, $sql);
$escolas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Escolares</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-escolas">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Unidades Escolares (Escolas Estaduais)</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastre e gerencie as escolas da regional.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Escola cadastrada com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de cadastro de escola -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Nova Unidade Escolar</h5>
            <form action="../../controllers/escolas/salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">

                <?php if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0): ?>
                    <input type="hidden" name="id_ure" value="<?php echo $idUreUsuario; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome da Escola</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex.: EE Professor José Alves" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CIE (Código da Escola)</label>
                        <input type="text" name="cie" class="form-control" maxlength="6" placeholder="123456" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Modalidade de Ensino</label>
                        <select name="modalidade" class="form-select" required>
                            <option value="REGULAR">Regular</option>
                            <option value="PEI">PEI (Programa Ensino Integral)</option>
                        </select>
                    </div>

                    <?php if ($userPerfil === 'ADMIN' || $userPerfil === 'SEDUC'): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unidade Regional de Ensino (URE)</label>
                            <select name="id_ure" class="form-select" required>
                                <option value="">Selecione a URE...</option>
                                <?php foreach ($todasUres as $ure): ?>
                                    <option value="<?php echo $ure['id_ure']; ?>">
                                        <?php echo htmlspecialchars($ure['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unidade Regional de Ensino (URE)</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(nomeUreUsuario($conexao, $userId)); ?>" readonly>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Horário de Funcionamento</label>
                        <input type="text" name="horario_funcionamento" class="form-control" placeholder="Ex.: 07:00 às 17:00">
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
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" class="form-control" value="Bragança Paulista">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 4034-1100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email Institucional</label>
                        <input type="email" name="email" class="form-control" placeholder="escola@educacao.sp.gov.br">
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
                            <th>URE</th>
                            <th>Cidade</th>
                            <th>Alunos</th>
                            <th>Usuários</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($escolas) > 0): ?>
                            <?php foreach ($escolas as $escola): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($escola['nome']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($escola['cie']); ?></code></td>
                                    <td>
                                        <?php if ($escola['modalidade'] === 'PEI'): ?>
                                            <span class="badge bg-primary">PEI</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($escola['ure_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($escola['cidade'] ?? '-'); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo $escola['total_alunos']; ?></span></td>
                                    <td><span class="badge bg-dark"><?php echo $escola['total_usuarios']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhuma escola cadastrada.</td>
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