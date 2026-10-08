<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

// Lista as UREs disponíveis para admin/seduc
$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$todasUres = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Unidade Escolar - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-escolas-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Unidade Escolar</h2>

                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
                        <div class="alert alert-success mb-4">Escola cadastrada com sucesso!</div>
                    <?php endif; ?>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/escolas/salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">

                        <?php if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0): ?>
                            <input type="hidden" name="id_ure" value="<?php echo $idUreUsuario; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Institucionais</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome da Escola <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" maxlength="150" placeholder="Ex.: EE Professor José Alves" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Código CIE <span class="text-danger">*</span></label>
                                <input type="text" name="cie" class="form-control font-monospace" maxlength="10" placeholder="Ex.: 123456" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Código UA</label>
                                <input type="text" name="ua" class="form-control font-monospace" maxlength="20" placeholder="Ex.: 42962">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Modalidade de Ensino <span class="text-danger">*</span></label>
                                <select name="modalidade" class="form-select" required>
                                    <option value="REGULAR">Regular</option>
                                    <option value="PEI">PEI (Programa Ensino Integral)</option>
                                </select>
                            </div>

                            <?php if ($userPerfil === 'ADMIN' || $userPerfil === 'SEDUC'): ?>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Unidade Regional de Ensino (URE) <span class="text-danger">*</span></label>
                                    <select name="id_ure" class="form-select" required>
                                        <option value="">Selecione a URE...</option>
                                        <?php foreach ($todasUres as $ure): ?>
                                            <option value="<?php echo $ure['id_ure']; ?>">
                                                <?php echo ($ure['uge'] ? '[' . $ure['uge'] . '] ' : '') . htmlspecialchars($ure['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Unidade Regional de Ensino (URE)</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(nomeUreUsuario($conexao, $userId)); ?>" readonly>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Horário de Funcionamento</label>
                                <input type="text" name="horario_funcionamento" class="form-control" maxlength="100" placeholder="Ex.: 07:00 às 17:00">
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Localização e Contatos</h5>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Logradouro / Rua</label>
                                <input type="text" name="rua" class="form-control" maxlength="255" placeholder="Ex.: Av. São Paulo">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" maxlength="20" placeholder="100">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" maxlength="100" placeholder="Centro">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">CEP</label>
                                <input type="text" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="cidade" class="form-control" maxlength="100" value="Bragança Paulista">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" maxlength="30" placeholder="(11) 4034-1100">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" maxlength="150" placeholder="escola@educacao.sp.gov.br">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-dark">Cadastrar Escola</button>
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