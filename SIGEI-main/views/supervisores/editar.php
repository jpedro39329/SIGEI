<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: listar.php?erro=" . urlencode("Supervisor não informado."));
    exit();
}

$stmt = $conexao->prepare("SELECT * FROM usuarios_supervisor WHERE id_usuario_supervisor = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$supervisor = $stmt->get_result()->fetch_assoc();

if (!$supervisor) {
    header("Location: listar.php?erro=" . urlencode("Supervisor não encontrado."));
    exit();
}

// Lista as empresas
$sqlEmpresas = "SELECT * FROM empresas ORDER BY nome ASC";
$resultEmpresas = mysqli_query($conexao, $sqlEmpresas);
$empresas = $resultEmpresas ? mysqli_fetch_all($resultEmpresas, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Supervisor de Licitações e Contratos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-supervisores-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Supervisor de Licitações e Contratos</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/supervisores/editar_salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_usuario_supervisor" value="<?php echo $supervisor['id_usuario_supervisor']; ?>">
                        
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais e Vínculo</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($supervisor['nome']); ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" value="<?php echo htmlspecialchars(formatarCPF($supervisor['cpf'])); ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Empresa Contratada <span class="text-danger">*</span></label>
                                <select name="id_empresa" class="form-select" required>
                                    <option value="">Selecione a empresa...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?php echo $emp['id_empresa']; ?>" <?php echo $supervisor['id_empresa'] == $emp['id_empresa'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($supervisor['email'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone de Contato</label>
                                <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($supervisor['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status do Usuário</label>
                                <select name="ativo" class="form-select">
                                    <option value="1" <?php echo $supervisor['ativo'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $supervisor['ativo'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>

                            <div class="col-12 mt-3">
                                <h5 class="mb-2">Redefinição de Senha (Opcional)</h5>
                                <p class="text-muted small mb-3">Preencha os campos abaixo apenas se desejar alterar a senha de acesso.</p>
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
