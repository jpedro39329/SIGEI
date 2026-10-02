<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

// Lista de UREs para consulta rápida via JS ou fallback
$sqlUres = "SELECT id_ure, nome, uge FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$ures = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Servidor ASURE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
    <style>
        .uge-result-card {
            display: none;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 8px;
        }
        .uge-result-card.active {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body class="page-gestores-cadastrar">

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

                    <form action="../../controllers/gestores/salvar.php" method="POST" id="formDirigente">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" maxlength="150" placeholder="Ex.: Roberto Alves" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" placeholder="000.000.000-00" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo / Função <span class="text-danger">*</span></label>
                                <select name="cargo" class="form-select" required>
                                    <option value="">-- Selecione o Cargo --</option>
                                    <option value="Coordenador Dirigente Regional de Ensino">Coordenador Dirigente Regional de Ensino</option>
                                    <option value="Assistente Técnico II">Assistente Técnico II</option>
                                    <option value="Assistente Técnico I">Assistente Técnico I</option>
                                    <option value="Assistente Técnico">Assistente Técnico</option>
                                    <option value="Diretor de Centro de Recursos Humanos">Diretor de Centro de Recursos Humanos</option>
                                    <option value="Diretor de Administração">Diretor de Administração</option>
                                    <option value="Outro">Outro Servidor Regional</option>
                                </select>
                            </div>

                            <!-- Seleção via Menu Suspenso de URE (UGE) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unidade Regional de Ensino (URE / UGE) <span class="text-danger">*</span></label>
                                <select name="id_ure" id="selectUre" class="form-select" required>
                                    <option value="">-- Selecione a URE pela UGE --</option>
                                    <?php foreach ($ures as $u): ?>
                                        <option value="<?php echo $u['id_ure']; ?>">
                                            <?php echo ($u['uge'] ? '[UGE ' . htmlspecialchars($u['uge']) . '] ' : '') . htmlspecialchars($u['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Contatos Institucionais</h5>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" maxlength="150" placeholder="servidor@educacao.sp.gov.br">
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Credenciais de Acesso</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha de Acesso <span class="text-danger">*</span></label>
                                <input type="password" name="senha" id="senha" class="form-control" maxlength="255" placeholder="Mínimo 6 caracteres" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" maxlength="255" placeholder="Repita a senha" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-dark" id="btnSalvarDirigente">Cadastrar Servidor</button>
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