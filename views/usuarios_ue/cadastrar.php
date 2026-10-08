<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_SEFISC', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}

// Filtra as escolas pertencentes à URE da SEFISC
if ($userPerfil === 'USUARIO_SEFISC' && $idUreUsuario > 0) {
    $sqlEscolas = "SELECT * FROM unidades_escolares WHERE id_ure = $idUreUsuario ORDER BY nome ASC";
    $whereLista = "WHERE ue.id_ure = $idUreUsuario";
} else {
    $sqlEscolas = "SELECT * FROM unidades_escolares ORDER BY nome ASC";
    $whereLista = "";
}

$resultEscolas = mysqli_query($conexao, $sqlEscolas);
$escolas = $resultEscolas ? mysqli_fetch_all($resultEscolas, MYSQLI_ASSOC) : [];

// Lista os usuários de escola
$sqlUsuarios = "
    SELECT uue.*, ue.nome AS escola_nome, ue.cie, u.nome AS ure_nome
    FROM usuarios_ue uue
    JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
    JOIN unidades_regionais u ON ue.id_ure = u.id_ure
    $whereLista
    ORDER BY uue.data_cadastro DESC
";
$result = mysqli_query($conexao, $sqlUsuarios);
$usuariosEscola = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários das Escolas — SEFISC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-usuarios-ue">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Cadastrar Usuário da Escola</h2>
        </div>
        <a href="listar.php" class="btn btn-secondary">Voltar para a lista</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Usuário da escola cadastrado com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de cadastro de Usuário da UE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Dados do Usuário</h5>
            <form action="../../controllers/usuarios_ue/salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" maxlength="150" placeholder="Ex.: Carlos da Silva" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CPF</label>
                        <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" placeholder="000.000.000-00" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Unidade Escolar</label>
                        <select name="id_ue" class="form-select" required>
                            <option value="">Selecione a escola...</option>
                            <?php foreach ($escolas as $esc): ?>
                                <option value="<?php echo $esc['id_ue']; ?>">
                                    <?php echo htmlspecialchars($esc['nome']); ?> (CIE: <?php echo htmlspecialchars($esc['cie']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Institucional</label>
                        <input type="email" name="email" class="form-control" maxlength="150" placeholder="usuario@escola.sp.gov.br">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" maxlength="30" placeholder="(11) 9999-1000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" class="form-control" maxlength="255" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" maxlength="255" required>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">Cadastrar Usuário da Escola</button>
                    <a href="listar.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function () {
            let value = cpfInput.value.replace(/\D/g, '');
            value = value.substring(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            cpfInput.value = value;
        });
    }

    document.querySelector('form').addEventListener('submit', function (e) {
        if (document.getElementById('senha').value !== document.getElementById('confirmar_senha').value) {
            e.preventDefault();
            alert('As senhas não coincidem.');
        }
    });
</script>

</body>
</html>