<?php
require_once "../../config/init.php";

exigirPerfil(array('PAE', 'SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);
$id_relatorio = (int) ($_GET['id'] ?? 0);

if ($id_relatorio <= 0) {
    die("Relatório não informado.");
}

$sql = "
    SELECT r.*, a.nome AS aluno_nome, a.cpf AS aluno_cpf, a.ra AS aluno_ra,
           p.id_pae, p.nome AS pae_nome, p.cpf AS pae_cpf, p.id_empresa,
           e.nome AS empresa_nome, ue.nome AS escola_nome
    FROM relatorios r
    JOIN associacoes ass ON r.id_associacao = ass.id_associacao
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
    LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    WHERE r.id_relatorio = $id_relatorio
";
$result = mysqli_query($conexao, $sql);
$relatorio = $result ? mysqli_fetch_assoc($result) : null;

if (!$relatorio) {
    die("Relatório não encontrado.");
}

// Permissões
if ($userPerfil === 'PAE') {
    if ((int)$relatorio['id_pae'] !== $userId) {
        die("Acesso negado.");
    }
} elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    if ((int)$relatorio['id_empresa'] !== $idEmpresaUsuario) {
        die("Acesso negado: este relatório pertence a outra empresa.");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Relatório — SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-relatorios-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Detalhes do Relatório</h2>
        </div>
        <a href="listar.php" class="btn btn-secondary btn-sm">Voltar</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="mb-1 text-dark">
                        Relatório <?php echo ($relatorio['tipo'] == 'DIARIO') ? 'Diário de Atendimento' : 'Mensal de Acompanhamento'; ?>
                    </h4>
                    <p class="text-muted small mb-0">Registrado em <?php echo date('d/m/Y \à\s H:i', strtotime($relatorio['data_cadastro'])); ?></p>
                </div>
                <div>
                    <?php if ($relatorio['tipo'] == 'DIARIO'): ?>
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">Diário</span>
                    <?php else: ?>
                        <span class="badge bg-primary px-3 py-2 rounded-pill">Mensal</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Estudante Atendido</h6>
                        <div class="mb-2"><span class="text-muted small d-block">Nome:</span> <strong><?php echo htmlspecialchars($relatorio['aluno_nome']); ?></strong></div>
                        <div class="mb-2"><span class="text-muted small d-block">Escola:</span> <span><?php echo htmlspecialchars($relatorio['escola_nome'] ?? '-'); ?></span></div>
                        <div class="mb-2"><span class="text-muted small d-block">RA:</span> <span><?php echo htmlspecialchars($relatorio['aluno_ra'] ?? '-'); ?></span></div>
                        <div class="mb-0"><span class="text-muted small d-block">CPF:</span> <span><?php echo htmlspecialchars(formatarCPF($relatorio['aluno_cpf'])); ?></span></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Profissional & Empresa</h6>
                        <div class="mb-2"><span class="text-muted small d-block">Profissional (PAE):</span> <strong><?php echo htmlspecialchars($relatorio['pae_nome']); ?></strong></div>
                        <div class="mb-2"><span class="text-muted small d-block">CPF:</span> <span><?php echo htmlspecialchars(formatarCPF($relatorio['pae_cpf'])); ?></span></div>
                        <div class="mb-0"><span class="text-muted small d-block">Empresa:</span> <span><?php echo htmlspecialchars($relatorio['empresa_nome'] ?? '-'); ?></span></div>
                    </div>
                </div>
            </div>

            <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Descrição e Observações do Atendimento</h5>
            <div class="p-3 border rounded bg-white mb-4" style="line-height: 1.8; white-space: pre-line;">
                <?php echo htmlspecialchars($relatorio['descricao']); ?>
            </div>

            <div class="d-flex justify-content-end">
                <a href="listar.php" class="btn btn-secondary">Voltar para a Lista</a>
            </div>
        </div>
    </div>

</div>

</body>
</html>
