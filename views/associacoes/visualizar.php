<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));

$idPae = (int) ($_GET['id'] ?? 0);
if ($idPae <= 0) {
    header("Location: gerenciar.php?erro=" . urlencode("Profissional de apoio não informado."));
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

// Busca dados do PAE
$stmtPae = $conexao->prepare("
    SELECT p.*, emp.nome AS empresa_nome, emp.cnpj AS empresa_cnpj
    FROM usuarios_pae p
    LEFT JOIN empresas emp ON p.id_empresa = emp.id_empresa
    WHERE p.id_pae = ? AND p.ativo = 1
");
$stmtPae->bind_param("i", $idPae);
$stmtPae->execute();
$pae = $stmtPae->get_result()->fetch_assoc();

if (!$pae) {
    header("Location: gerenciar.php?erro=" . urlencode("Profissional de apoio não encontrado."));
    exit();
}

// Permissão por empresa
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    if ((int)$pae['id_empresa'] !== $idEmpresaUsuario) {
        die("Acesso negado: este PAE pertence a outra empresa.");
    }
}

// Busca os alunos ativos associados a este PAE
$sqlAlunosAssoc = "
    SELECT 
        ass.id_associacao,
        ass.data_inicio,
        a.id_aluno,
        a.nome AS aluno_nome,
        a.cpf AS aluno_cpf,
        a.ra AS aluno_ra,
        a.data_nascimento,
        a.descricao_deficiencia,
        ue.nome AS escola_nome,
        ue.cie AS escola_cie,
        ure.nome AS ure_nome
    FROM associacoes ass
    JOIN alunos a ON ass.id_aluno = a.id_aluno
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    LEFT JOIN unidades_regionais ure ON ue.id_ure = ure.id_ure
    WHERE ass.id_pae = ? AND ass.ativo = 1
    ORDER BY a.nome ASC
";
$stmtAlunos = $conexao->prepare($sqlAlunosAssoc);
$stmtAlunos->bind_param("i", $idPae);
$stmtAlunos->execute();
$alunosAssociados = $stmtAlunos->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associações do PAE — <?php echo htmlspecialchars($pae['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-associacoes-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Alunos Vinculados ao Profissional</h2>
            <p class="text-muted">Acompanhamento e gestão de atendimentos do cuidador escolar.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (count($alunosAssociados) < 3): ?>
                <a href="cadastrar.php?id_pae=<?php echo $pae['id_pae']; ?>" class="btn btn-primary btn-sm">Vincular Novo Aluno</a>
            <?php endif; ?>
            <a href="gerenciar.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'removido'): ?>
        <div class="alert alert-warning">Aluno desassociado com sucesso.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
        <div class="alert alert-success">Aluno vinculado com sucesso!</div>
    <?php endif; ?>

    <!-- Informações do PAE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Informações do Profissional de Apoio Escolar (PAE)</h5>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <span class="text-muted">Nome do Profissional:</span>
                    <strong><?php echo htmlspecialchars($pae['nome']); ?></strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">CPF:</span>
                    <strong class="font-monospace"><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">Empresa Contratada:</span>
                    <strong><?php echo htmlspecialchars($pae['empresa_nome'] ?: 'Não informada'); ?></strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">Telefone:</span>
                    <strong><?php echo htmlspecialchars(formatarTelefone($pae['telefone']) ?: 'Não informado'); ?></strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">E-mail:</span>
                    <strong><?php echo htmlspecialchars($pae['email'] ?: 'Não informado'); ?></strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">Carga Atual de Atendimento:</span>
                    <strong><?php echo count($alunosAssociados); ?> de 3 aluno(s)</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Alunos Associados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Alunos Atualmente em Atendimento</h5>
                <span class="text-muted small"><?php echo count($alunosAssociados); ?> vínculo(s) ativo(s)</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome do Aluno</th>
                            <th>CPF</th>
                            <th>Necessidade / Deficiência</th>
                            <th>Unidade Escolar</th>
                            <th>Início do Vínculo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($alunosAssociados) > 0): ?>
                            <?php foreach ($alunosAssociados as $aluno): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aluno['aluno_nome']); ?></strong>
                                        <?php if (!empty($aluno['aluno_ra'])): ?>
                                            <small class="text-muted d-block">RA: <?php echo htmlspecialchars($aluno['aluno_ra']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(formatarCPF($aluno['aluno_cpf']) ?: '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($aluno['descricao_deficiencia'] ?: 'Não informada'); ?>
                                    </td>
                                    <td>
                                        <span><?php echo htmlspecialchars($aluno['escola_nome'] ?: '-'); ?></span>
                                    </td>
                                    <td>
                                        <?php echo !empty($aluno['data_inicio']) ? date('d/m/Y', strtotime($aluno['data_inicio'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="../alunos/visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-sm btn-info" title="Abrir ficha cadastral do aluno">
                                                Ver Aluno
                                            </a>

                                            <form action="../../controllers/associacoes/desassociar.php" method="POST" style="display:inline;"
                                                  data-confirm="true"
                                                  data-confirm-type="danger"
                                                  data-confirm-title="Desassociar Aluno"
                                                  data-confirm-message="Deseja desassociar <strong><?php echo htmlspecialchars($aluno['aluno_nome']); ?></strong> deste PAE?<br><small class='text-muted'>O atendimento será finalizado.</small>"
                                                  data-confirm-action="Desassociar">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                                <input type="hidden" name="id_associacao" value="<?php echo $aluno['id_associacao']; ?>">
                                                <input type="hidden" name="redirect_pae" value="<?php echo $pae['id_pae']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Desassociar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum aluno vinculado a este profissional no momento.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

</body>
</html>

