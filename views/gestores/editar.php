<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Dirigente não informado."));
    exit();
}

// 1. Dados do dirigente
$stmt = $conexao->prepare("
    SELECT uu.*, u.nome AS ure_nome, u.uge AS ure_uge
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    WHERE uu.id_usuario_ure = ? AND uu.setor IN ('ASURE', 'GABINETE')
");
$stmt->bind_param("i", $id);
$stmt->execute();
$dirigente = $stmt->get_result()->fetch_assoc();

if (!$dirigente) {
    header("Location: listar.php?erro=" . urlencode("Dirigente não encontrado."));
    exit();
}

// Lista de UREs
$sqlUres = "SELECT id_ure, nome, uge FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$ures = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Servidor ASURE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
    <style>
        .uge-result-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body class="page-gestores-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Servidor (ASURE)</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/gestores/editar_salvar.php" method="POST" id="formDirigente">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_usuario_ure" value="<?php echo $dirigente['id_usuario_ure']; ?>">
                        <input type="hidden" name="id_ure" id="id_ure_input" value="<?php echo $dirigente['id_ure']; ?>" required>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($dirigente['nome']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" value="<?php echo htmlspecialchars(formatarCPF($dirigente['cpf'])); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo / Função <span class="text-danger">*</span></label>
                                <select name="cargo" class="form-select" required>
                                    <option value="">-- Selecione o Cargo --</option>
                                    <option value="Coordenador Dirigente Regional de Ensino" <?php echo ($dirigente['cargo'] ?? '') === 'Coordenador Dirigente Regional de Ensino' ? 'selected' : ''; ?>>Coordenador Dirigente Regional de Ensino</option>
                                    <option value="Assistente Técnico II" <?php echo ($dirigente['cargo'] ?? '') === 'Assistente Técnico II' ? 'selected' : ''; ?>>Assistente Técnico II</option>
                                    <option value="Assistente Técnico I" <?php echo ($dirigente['cargo'] ?? '') === 'Assistente Técnico I' ? 'selected' : ''; ?>>Assistente Técnico I</option>
                                    <option value="Assistente Técnico" <?php echo ($dirigente['cargo'] ?? '') === 'Assistente Técnico' ? 'selected' : ''; ?>>Assistente Técnico</option>
                                    <option value="Diretor de Centro de Recursos Humanos" <?php echo ($dirigente['cargo'] ?? '') === 'Diretor de Centro de Recursos Humanos' ? 'selected' : ''; ?>>Diretor de Centro de Recursos Humanos</option>
                                    <option value="Diretor de Administração" <?php echo ($dirigente['cargo'] ?? '') === 'Diretor de Administração' ? 'selected' : ''; ?>>Diretor de Administração</option>
                                    <option value="Outro" <?php echo !in_array($dirigente['cargo'] ?? '', ['Coordenador Dirigente Regional de Ensino', 'Assistente Técnico II', 'Assistente Técnico I', 'Assistente Técnico', 'Diretor de Centro de Recursos Humanos', 'Diretor de Administração']) ? 'selected' : ''; ?>>Outro Servidor Regional</option>
                                </select>
                            </div>

                            <!-- Seleção via Menu Suspenso de URE (UGE) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unidade Regional de Ensino (URE / UGE) <span class="text-danger">*</span></label>
                                <select name="id_ure" id="selectUre" class="form-select" required>
                                    <option value="">-- Selecione a URE pela UGE --</option>
                                    <?php foreach ($ures as $u): ?>
                                        <option value="<?php echo $u['id_ure']; ?>" <?php echo $dirigente['id_ure'] == $u['id_ure'] ? 'selected' : ''; ?>>
                                            <?php echo ($u['uge'] ? '[UGE ' . htmlspecialchars($u['uge']) . '] ' : '') . htmlspecialchars($u['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Contatos Institucionais e Status</h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($dirigente['email'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($dirigente['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status do Usuário</label>
                                <select name="ativo" class="form-select">
                                    <option value="1" <?php echo $dirigente['ativo'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $dirigente['ativo'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-2">Alterar Senha de Acesso (Opcional)</h5>
                                <p class="text-muted small mb-3">Preencha apenas se desejar trocar a senha do servidor.</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="nova_senha" id="senha" class="form-control" placeholder="Deixe em branco para manter a atual">
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
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            v = v.replace(/^(\d{3})(\d)/, '$1.$2');
            v = v.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1-$2');
            e.target.value = v;
        });
    }
});
</script>

</body>
</html>
