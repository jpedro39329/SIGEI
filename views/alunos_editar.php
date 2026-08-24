<?php
session_start();
include("../config/database.php");
include("../config/perfil_functions.php");

// Apenas USUARIO_ESCOLA pode editar alunos
exigirPerfil(array('USUARIO_ESCOLA'));

$userId = $_SESSION['user_id'];
$idEscola = idEscolaUsuario($conexao, $userId);
$id_aluno = (int) ($_GET['id'] ?? 0);

// Busca o aluno e verifica que pertence à escola
$sql = "SELECT * FROM alunos WHERE id_aluno = $id_aluno AND id_escola = $idEscola";
$result = mysqli_query($conexao, $sql);
$aluno = mysqli_fetch_assoc($result);

if (!$aluno) {
    die("Aluno não encontrado ou não pertence à sua escola.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-alunos-editar">

<?php require("navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Editar Aluno</h2>
                    <p class="text-muted mb-4">Edite os dados do aluno. Não é possível modificar CPF, RA, data de nascimento ou status.</p>

                    <form action="../controllers/alunos_editar_salvar.php" method="POST">
                        <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Deficiência</label>
                                <input type="text" name="deficiencia" class="form-control" value="<?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?>" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Cuidados Necessários</label>
                                <textarea name="observacoes" rows="4" class="form-control"><?php echo htmlspecialchars($aluno['descricao_cuidados'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do Responsável</label>
                                <input type="text" name="nome_responsavel" class="form-control" value="<?php echo htmlspecialchars($aluno['nome_responsavel'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF do Responsável</label>
                                <input type="text" name="cpf_responsavel" id="cpf_responsavel" class="form-control" maxlength="14" value="<?php echo htmlspecialchars(formatarCPF($aluno['cpf_responsavel'] ?? '')); ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Guardar</button>
                            <a href="alunos_visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Máscara CPF do responsável
    const cpfRespInput = document.getElementById('cpf_responsavel');
    cpfRespInput.addEventListener('input', function () {
        let value = cpfRespInput.value;
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        cpfRespInput.value = value;
    });
</script>

</body>
</html>
