<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$id_usuario_ue = (int) ($_GET['id'] ?? 0);

if ($id_usuario_ue <= 0) {
    die("Usuário não informado.");
}

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

$sql = "
    SELECT uue.*, ue.id_ure
    FROM usuarios_ue uue
    JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
    WHERE uue.id_usuario_ue = $id_usuario_ue
";
$result = mysqli_query($conexao, $sql);
$usuario = $result ? mysqli_fetch_assoc($result) : null;

if (!$usuario) {
    die("Usuário não encontrado.");
}

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0 && (int)$usuario['id_ure'] !== $idUreUsuario) {
    die("Acesso negado: este usuário pertence a outra Diretoria Regional.");
}

// Lista escolas disponíveis para vinculação
if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0) {
    $sqlEscolas = "SELECT * FROM unidades_escolares WHERE id_ure = $idUreUsuario ORDER BY nome ASC";
} else {
    $sqlEscolas = "SELECT * FROM unidades_escolares ORDER BY nome ASC";
}
$resultEscolas = mysqli_query($conexao, $sqlEscolas);
$escolas = $resultEscolas ? mysqli_fetch_all($resultEscolas, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário da Escola — SEFISC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-usuarios-ue-editar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Usuário de Escola</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/usuarios_ue/editar_salvar.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_usuario_ue" value="<?php echo $usuario['id_usuario_ue']; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(formatarCPF($usuario['cpf'])); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unidade Escolar</label>
                                <select name="id_ue" class="form-select" required>
                                    <option value="">Selecione a escola...</option>
                                    <?php foreach ($escolas as $esc): ?>
                                        <option value="<?php echo $esc['id_ue']; ?>" <?php echo ((int)$usuario['id_ue'] === (int)$esc['id_ue']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($esc['nome']); ?> (CIE: <?php echo htmlspecialchars($esc['cie']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!--
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select" required>
                                    <option value="1" <?php echo $usuario['ativo'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $usuario['ativo'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                            -->

                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" maxlength="30" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nova Senha <small class="text-muted">(deixe em branco para não alterar)</small></label>
                                <input type="password" name="senha" id="senha" class="form-control" maxlength="255" placeholder="Mínimo 6 caracteres">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" maxlength="255">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Salvar Alterações</button>
                            <a href="listar.php?id=<?php echo $usuario['id_usuario_ue']; ?>" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function (e) {
        const s = document.getElementById('senha').value;
        const cs = document.getElementById('confirmar_senha').value;
        if (s !== '' && s !== cs) {
            e.preventDefault();
            alert('As senhas não coincidem.');
        }
    });
</script>
</body>
</html>

