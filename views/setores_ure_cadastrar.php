<?php
require_once "../config/init.php";

exigirPerfil(array('DIRIGENTE', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

// Lista as UREs disponíveis para admin/seduc
$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$todasUres = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];

// Filtro de listagem
if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0) {
    $whereUre = "WHERE uu.id_ure = $idUreUsuario AND uu.setor IN ('SEFISC', 'EDU_ESPECIAL')";
} else {
    $whereUre = "WHERE uu.setor IN ('SEFISC', 'EDU_ESPECIAL')";
}

$sqlUsuarios = "
    SELECT uu.*, u.nome AS ure_nome
    FROM usuarios_ure uu
    JOIN unidades_regionais u ON uu.id_ure = u.id_ure
    $whereUre
    ORDER BY uu.setor ASC, uu.data_cadastro DESC
";
$result = mysqli_query($conexao, $sqlUsuarios);
$usuariosSetores = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setores da URE — SEFISC e Educação Especial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-setores-ure">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Servidores dos Setores da URE</h2>
            <p class="text-muted">Olá, <?php echo htmlspecialchars($userName); ?> — cadastro de servidores da SEFISC e Educação Especial.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <div class="alert alert-success">Servidor cadastrado com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário de cadastro de servidor de setor -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Novo Servidor da URE</h5>
            <form action="../controllers/setores_ure_salvar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                
                <?php if ($userPerfil === 'DIRIGENTE' && $idUreUsuario > 0): ?>
                    <input type="hidden" name="id_ure" value="<?php echo $idUreUsuario; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex.: Juliana Castro e Silva" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CPF</label>
                        <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" placeholder="000.000.000-00" required>
                    </div>

                    <?php if ($userPerfil === 'ADMIN' || $userPerfil === 'SEDUC'): ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">URE</label>
                            <select name="id_ure" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($todasUres as $ure): ?>
                                    <option value="<?php echo $ure['id_ure']; ?>">
                                        <?php echo htmlspecialchars($ure['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">URE</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(nomeUreUsuario($conexao, $userId)); ?>" readonly>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Setor da URE</label>
                        <select name="setor" id="setor" class="form-select" required onchange="atualizarCargos()">
                            <option value="">Selecione o setor...</option>
                            <option value="SEFISC">Seção de Fiscalização (SEFISC)</option>
                            <option value="EDU_ESPECIAL">Educação Especial</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cargo / Função</label>
                        <select name="cargo" id="cargo" class="form-select" required>
                            <option value="">Selecione o setor primeiro...</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="ativo" class="form-select">
                            <option value="1" selected>Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Institucional</label>
                        <input type="email" name="email" class="form-control" placeholder="servidor@educacao.sp.gov.br">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 9999-4000">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Cadastrar Servidor</button>
            </form>
        </div>
    </div>

    <!-- Lista de Servidores dos Setores -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">Servidores de Setores Cadastrados</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Setor</th>
                            <th>Cargo</th>
                            <th>URE</th>
                            <th>Contato</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuariosSetores) > 0): ?>
                            <?php foreach ($usuariosSetores as $u): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($u['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(formatarCPF($u['cpf'])); ?></td>
                                    <td>
                                        <?php if ($u['setor'] === 'SEFISC'): ?>
                                            <span class="badge bg-warning text-dark">SEFISC</span>
                                        <?php elseif ($u['setor'] === 'EDU_ESPECIAL'): ?>
                                            <span class="badge bg-info text-dark">Educação Especial</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($u['setor']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['cargo']); ?></td>
                                    <td><?php echo htmlspecialchars($u['ure_nome']); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($u['email'] ?? '-'); ?><br><?php echo htmlspecialchars($u['telefone'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($u['ativo'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhum servidor cadastrado nos setores.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    function atualizarCargos() {
        const setor = document.getElementById('setor').value;
        const cargoSelect = document.getElementById('cargo');
        cargoSelect.innerHTML = '';

        if (setor === 'SEFISC') {
            cargoSelect.innerHTML = `
                <option value="Chefe de Seção">Chefe de Seção</option>
                <option value="Subordinado">Subordinado</option>
            `;
        } else if (setor === 'EDU_ESPECIAL') {
            cargoSelect.innerHTML = `
                <option value="Professor Especialista em Currículo (PEC)">Professor Especialista em Currículo (PEC)</option>
                <option value="Subordinado">Subordinado</option>
            `;
        } else {
            cargoSelect.innerHTML = `<option value="">Selecione o setor primeiro...</option>`;
        }
    }

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