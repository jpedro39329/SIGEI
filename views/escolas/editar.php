<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Escola não informada."));
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$stmt = $conexao->prepare("
    SELECT ue.*, u.nome AS ure_nome, u.uge AS ure_uge
    FROM unidades_escolares ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    WHERE ue.id_ue = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$escola = $stmt->get_result()->fetch_assoc();

if (!$escola) {
    header("Location: listar.php?erro=" . urlencode("Escola não encontrada."));
    exit();
}

if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0 && (int)$escola['id_ure'] !== $idUreUsuario) {
    header("Location: listar.php?erro=" . urlencode("Você não tem permissão para editar escolas de outra regional."));
    exit();
}

// Lista UREs se for admin
$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$todasUres = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Unidade Escolar - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-escolas-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Unidade Escolar</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/escolas/editar_salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_ue" value="<?php echo $escola['id_ue']; ?>">

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Institucionais</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome da Escola <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($escola['nome']); ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Código CIE <span class="text-danger">*</span></label>
                                <input type="text" name="cie" class="form-control font-monospace" maxlength="10" value="<?php echo htmlspecialchars($escola['cie']); ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Código UA</label>
                                <input type="text" name="ua" class="form-control font-monospace" maxlength="20" value="<?php echo htmlspecialchars($escola['ua'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modalidade de Ensino <span class="text-danger">*</span></label>
                                <select name="modalidade" class="form-select" required>
                                    <option value="REGULAR" <?php echo ($escola['modalidade'] === 'REGULAR') ? 'selected' : ''; ?>>Regular</option>
                                    <option value="PEI" <?php echo ($escola['modalidade'] === 'PEI') ? 'selected' : ''; ?>>PEI (Programa Ensino Integral)</option>
                                </select>
                            </div>

                            <?php if ($userPerfil === 'ADMIN'): ?>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Unidade Regional de Ensino (URE) <span class="text-danger">*</span></label>
                                    <select name="id_ure" class="form-select" required>
                                        <?php foreach ($todasUres as $ure): ?>
                                            <option value="<?php echo $ure['id_ure']; ?>" <?php echo ($escola['id_ure'] == $ure['id_ure']) ? 'selected' : ''; ?>>
                                                <?php echo ($ure['uge'] ? '[' . $ure['uge'] . '] ' : '') . htmlspecialchars($ure['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Unidade Regional de Ensino (URE)</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($escola['ure_nome']); ?>" readonly>
                                    <input type="hidden" name="id_ure" value="<?php echo $escola['id_ure']; ?>">
                                </div>
                            <?php endif; ?>



                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Localização e Contatos</h5>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Logradouro / Rua</label>
                                <input type="text" name="endereco" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($escola['endereco'] ?? ''); ?>">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($escola['numero'] ?? ''); ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($escola['bairro'] ?? ''); ?>">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">CEP</label>
                                <input type="text" name="cep" class="form-control" maxlength="9" value="<?php echo htmlspecialchars($escola['cep'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="municipio" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($escola['municipio'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" maxlength="30" value="<?php echo htmlspecialchars($escola['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($escola['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-dark">Salvar Alterações</button>
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

