<?php
require_once "../../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Servidor não informado."));
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$stmt = $conexao->prepare("
    SELECT uu.*, u.nome AS ure_nome, u.uge AS ure_uge
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    WHERE uu.id_usuario_ure = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$servidor = $stmt->get_result()->fetch_assoc();

if (!$servidor) {
    header("Location: listar.php?erro=" . urlencode("Servidor não encontrado."));
    exit();
}

if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0 && (int)$servidor['id_ure'] !== $idUreUsuario) {
    header("Location: listar.php?erro=" . urlencode("Você não tem permissão para editar servidores de outra regional."));
    exit();
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
    <title>Editar Servidor da URE - SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-setores-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Servidor da URE</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/setores/editar_salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_usuario_ure" value="<?php echo $servidor['id_usuario_ure']; ?>">
                        
                        <?php if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0): ?>
                            <input type="hidden" name="id_ure" value="<?php echo $idUreUsuario; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais e Setor</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($servidor['nome']); ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" value="<?php echo htmlspecialchars(formatarCPF($servidor['cpf'])); ?>" required>
                            </div>

                            <?php if ($userPerfil === 'ADMIN' || $userPerfil === 'SEDUC'): ?>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Unidade Regional (URE) <span class="text-danger">*</span></label>
                                    <select name="id_ure" class="form-select" required>
                                        <?php foreach ($todasUres as $ure): ?>
                                            <option value="<?php echo $ure['id_ure']; ?>" <?php echo $servidor['id_ure'] == $ure['id_ure'] ? 'selected' : ''; ?>>
                                                <?php echo ($ure['uge'] ? '[' . $ure['uge'] . '] ' : '') . htmlspecialchars($ure['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Unidade Regional (URE)</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($servidor['ure_nome']); ?>" readonly>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Setor da URE <span class="text-danger">*</span></label>
                                <select name="setor" id="setor" class="form-select" required onchange="atualizarCargos()">
                                    <option value="ASURE" <?php echo ($servidor['setor'] === 'ASURE' || $servidor['setor'] === 'GABINETE') ? 'selected' : ''; ?>>Assistência Técnica (ASURE)</option>
                                    <option value="SEFISC" <?php echo $servidor['setor'] === 'SEFISC' ? 'selected' : ''; ?>>Seção de Fiscalização (SEFISC)</option>
                                    <option value="EDU_ESPECIAL" <?php echo $servidor['setor'] === 'EDU_ESPECIAL' ? 'selected' : ''; ?>>Educação Especial</option>
                                </select>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Cargo / Função <span class="text-danger">*</span></label>
                                <select name="cargo" id="cargo" class="form-select" required>
                                    <!-- Preenchido via JS -->
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select">
                                    <option value="1" <?php echo $servidor['ativo'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $servidor['ativo'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Contatos</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($servidor['email'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($servidor['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-2">Redefinição de Senha (Opcional)</h5>
                                <p class="text-muted small mb-3">Preencha apenas se desejar trocar a senha de acesso do servidor.</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="nova_senha" id="nova_senha" class="form-control" placeholder="Deixe em branco para manter a atual">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="Deixe em branco para manter a atual">
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

<script>
const cargoAtual = <?php echo json_encode($servidor['cargo'] ?? ''); ?>;

function atualizarCargos() {
    const setor = document.getElementById('setor').value;
    const cargoSelect = document.getElementById('cargo');
    cargoSelect.innerHTML = '';

    let opcoes = [];
    if (setor === 'ASURE' || setor === 'GABINETE') {
        opcoes = [
            'Assistente Técnico II',
            'Assistente Técnico I',
            'Coordenador Dirigente Regional de Ensino',
            'Oficial Administrativo'
        ];
    } else if (setor === 'SEFISC') {
        opcoes = [
            'Chefe de Seção',
            'Oficial Administrativo',
            'Agente Técnico'
        ];
    } else if (setor === 'EDU_ESPECIAL') {
        opcoes = [
            'Professor Especialista em Currículo',
            'Auxiliar Administrativo',
            'Supervisor de Ensino'
        ];
    }

    if (cargoAtual && !opcoes.includes(cargoAtual)) {
        opcoes.push(cargoAtual);
    }

    opcoes.forEach(op => {
        const opt = document.createElement('option');
        opt.value = op;
        opt.textContent = op;
        if (op === cargoAtual) opt.selected = true;
        cargoSelect.appendChild(opt);
    });
}

document.addEventListener('DOMContentLoaded', atualizarCargos);

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

