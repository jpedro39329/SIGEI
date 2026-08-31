<?php
require_once "../config/init.php";

// Apenas USUARIO_EDUCACAO_ESPECIAL pode acessar
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL'));

$id_aluno = (int) ($_GET['id'] ?? 0);
if ($id_aluno <= 0) {
    header("Location: solicitacoes.php");
    exit();
}

// Busca os dados completos do aluno
$sql = "
    SELECT a.*, e.nome AS escola_nome
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    WHERE a.id_aluno = $id_aluno
";
$result = mysqli_query($conexao, $sql);
$aluno = mysqli_fetch_assoc($result);

if (!$aluno || $aluno['status_aprovacao'] !== 'PENDENTE') {
    header("Location: solicitacoes.php");
    exit();
}

// Busca os laudos do aluno
$sqlLaudos = "SELECT * FROM laudos WHERE id_aluno = $id_aluno ORDER BY data_envio DESC";
$resultLaudos = mysqli_query($conexao, $sqlLaudos);
$laudos = $resultLaudos ? mysqli_fetch_all($resultLaudos, MYSQLI_ASSOC) : [];

// Foto do aluno
$foto = $aluno['foto_arquivo'] ?? '';

// Formata CPFs para exibição
$cpf = formatarCPF($aluno['cpf']);
$cpfResponsavel = formatarCPF($aluno['cpf_responsavel'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-solicitacao-analisar">

<?php require("navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Analisar Solicitação</h2>
        <a href="solicitacoes.php" class="btn btn-secondary">Voltar</a>
    </div>

    <div class="row">
        <!-- Foto e dados básicos -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <?php if (!empty($foto) && file_exists("../$foto")): ?>
                        <img src="../<?php echo htmlspecialchars($foto); ?>" alt="Foto do aluno" class="img-fluid rounded mb-3" style="max-height:220px;">
                    <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:200px;">
                            <span class="text-muted">Sem foto</span>
                        </div>
                    <?php endif; ?>

                    <h4 class="mb-1"><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></p>
                    <span class="badge bg-warning text-dark">Pendente</span>
                </div>
            </div>
        </div>

        <!-- Dados completos -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Dados do Aluno</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted">Nome Completo</strong>
                            <div><?php echo htmlspecialchars($aluno['nome']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted">CPF</strong>
                            <div><?php echo htmlspecialchars($cpf); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted">RA</strong>
                            <div><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted">Data de Nascimento</strong>
                            <div><?php echo !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong class="text-muted">Deficiência</strong>
                            <div><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong class="text-muted">Cuidados Necessários</strong>
                            <div><?php echo htmlspecialchars($aluno['descricao_cuidados'] ?? 'Não informado'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted">Nome do Responsável</strong>
                            <div><?php echo htmlspecialchars($aluno['nome_responsavel'] ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted">CPF do Responsável</strong>
                            <div><?php echo htmlspecialchars($cpfResponsavel); ?></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong class="text-muted">Data da Solicitação</strong>
                            <div><?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y H:i', strtotime($aluno['data_cadastro'])) : '-'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
<!-- Laudos -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Laudos</h5>
                    <?php if (count($laudos) > 0): ?>
                        <ul class="list-group">
                            <?php foreach ($laudos as $laudo): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($laudo['nome_arquivo'] ?? 'Laudo'); ?></span>
                                    <span class="text-muted small"><?php echo date('d/m/Y', strtotime($laudo['data_envio'])); ?></span>
                                    <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                        <a href="../<?php echo htmlspecialchars($laudo['caminho_arquivo']); ?>" target="_blank" class="btn btn-sm btn-info">Baixar</a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Nenhum laudo enviado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Decisão</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" onclick="aprovar(<?php echo $aluno['id_aluno']; ?>)">APROVAR</button>
                        <button type="button" class="btn btn-danger" onclick="reprovar(<?php echo $aluno['id_aluno']; ?>)">REPROVAR</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Formulário oculto para aprovar -->
<form id="formAprovar" action="../controllers/solicitacao_processar.php" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
    <input type="hidden" name="id_aluno" id="aprovar_id">
    <input type="hidden" name="acao" value="APROVAR">
</form>

<!-- Modal para reprovar com motivo -->
<div class="modal fade" id="modalReprovar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controllers/solicitacao_processar.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reprovar Solicitação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                    <input type="hidden" name="id_aluno" id="reprovar_id">
                    <input type="hidden" name="acao" value="REPROVAR">
                    <label class="form-label">Motivo da Reprovação</label>
                    <textarea name="motivo" class="form-control" rows="3" required placeholder="Informe o motivo da reprovação"></textarea>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmarReprovacao" required>
                        <label class="form-check-label" for="confirmarReprovacao">
                            Confirmo a reprovação desta solicitação.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Reprovar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function aprovar(id) {
        if (!confirm('Deseja realmente aprovar esta solicitação?')) return;
        document.getElementById('aprovar_id').value = id;
        document.getElementById('formAprovar').submit();
    }

    function reprovar(id) {
        document.getElementById('reprovar_id').value = id;
        new bootstrap.Modal(document.getElementById('modalReprovar')).show();
    }
</script>

</body>
</html>