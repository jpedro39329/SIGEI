<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

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
    <title>Cadastrar Servidor da URE - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-setores-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Servidor</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/setores/salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        
                        <?php if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0): ?>
                            <input type="hidden" name="id_ure" value="<?php echo $idUreUsuario; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais e Setor</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" placeholder="Ex.: Juliana Castro e Silva" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" placeholder="000.000.000-00" required>
                            </div>

                            <?php if ($userPerfil === 'ADMIN' || $userPerfil === 'SEDUC'): ?>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Unidade Regional  <span class="text-danger">*</span></label>
                                    <select name="id_ure" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($todasUres as $ure): ?>
                                            <option value="<?php echo $ure['id_ure']; ?>">
                                                <?php echo ($ure['uge'] ? '[' . $ure['uge'] . '] ' : '') . htmlspecialchars($ure['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Unidade Regional de Ensino</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(nomeUreUsuario($conexao, $userId)); ?>" readonly>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Setor <span class="text-danger">*</span></label>
                                <select name="setor" id="setor" class="form-select" required onchange="atualizarCargos()">
                                    <option value="">Selecione o setor...</option>
                                    <?php if ($userPerfil !== 'DIRIGENTE'): ?>
                                        <option value="ASURE">Assistência Técnica (ASURE)</option>
                                    <?php endif; ?>
                                    <option value="SEFISC">Seção de Fiscalização (SEFISC)</option>
                                    <option value="EDU_ESPECIAL">Educação Especial</option>
                                </select>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Cargo<span class="text-danger">*</span></label>
                                <select name="cargo" id="cargo" class="form-select" required>
                                    <option value="">Selecione o setor primeiro...</option>
                                </select>
                            </div>                            

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Contatos e Credenciais</h5>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" placeholder="servidor@educacao.sp.gov.br">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha de Acesso <span class="text-danger">*</span></label>
                                <input type="password" name="senha" id="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="Repita a senha" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-dark">Cadastrar Servidor</button>
                            <a href="listar.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function atualizarCargos() {
    const setor = document.getElementById('setor').value;
    const cargoSelect = document.getElementById('cargo');
    cargoSelect.innerHTML = '';

    if (setor === 'ASURE') {
        cargoSelect.innerHTML = `
            <option value="Assistente Técnico II">Assistente Técnico II</option>
            <option value="Assistente Técnico I">Assistente Técnico I</option>
            <option value="Coordenador Dirigente Regional de Ensino">Coordenador Dirigente Regional de Ensino</option>
            <option value="Oficial Administrativo">Oficial Administrativo</option>
        `;
    } else if (setor === 'SEFISC') {
        cargoSelect.innerHTML = `
            <option value="Chefe de Seção">Chefe de Seção</option>
            <option value="Oficial Administrativo">Oficial Administrativo</option>
            <option value="Agente Técnico">Agente Técnico</option>
        `;
    } else if (setor === 'EDU_ESPECIAL') {
        cargoSelect.innerHTML = `
            <option value="Professor Especialista em Currículo">Professor Especialista em Currículo</option>
            <option value="Auxiliar Administrativo">Auxiliar Administrativo</option>
            <option value="Supervisor de Ensino">Supervisor de Ensino</option>
        `;
    } else {
        cargoSelect.innerHTML = `<option value="">Selecione o setor primeiro...</option>`;
    }
}

const cpfInput = document.getElementById('cpf');
if (cpfInput) {
    cpfInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        value = value.replace(/^(\d{3})(\d)/, '$1.$2');
        value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1-$2');
        e.target.value = value;
    });
}
</script>

</body>
</html>