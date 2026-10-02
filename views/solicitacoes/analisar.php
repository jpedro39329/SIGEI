<?php
require_once "../../config/init.php";

// Apenas USUARIO_EDUCACAO_ESPECIAL pode acessar
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL', 'ADMIN', 'SEDUC'));

$id_aluno = (int) ($_GET['id'] ?? 0);
if ($id_aluno <= 0) {
    header("Location: listar.php");
    exit();
}

// Busca os dados completos do aluno incluindo escola e URE
$sql = "
    SELECT a.*, e.nome AS escola_nome, e.cie AS escola_cie, e.ua AS escola_ua, e.cidade AS escola_cidade,
           ure.nome AS ure_nome, ure.uge AS ure_uge
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    LEFT JOIN unidades_regionais ure ON e.id_ure = ure.id_ure
    WHERE a.id_aluno = $id_aluno
";
$result = mysqli_query($conexao, $sql);
$aluno = $result ? mysqli_fetch_assoc($result) : null;

if (!$aluno || !in_array($aluno['status_aprovacao'], ['PENDENTE', 'REPROVADO', 'PENDENTE_CORRECAO'])) {
    header("Location: listar.php");
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

function calcularIdade($dataNasc) {
    if (empty($dataNasc)) return null;
    $nasc = new DateTime($dataNasc);
    $hoje = new DateTime();
    return $nasc->diff($hoje)->y;
}
$idadeAluno = calcularIdade($aluno['data_nascimento']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitação — SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-solicitacao-analisar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Analisar Solicitação</h2>
        <a href="listar.php<?php echo ($aluno['status_aprovacao'] === 'REPROVADO') ? '?aba=reprovados' : ''; ?>" class="btn btn-secondary btn-sm">Voltar</a>
    </div>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'motivo'): ?>
        <div class="alert alert-danger">É obrigatório informar o motivo da recusa.</div>
    <?php elseif (isset($_GET['erro']) && $_GET['erro'] === 'motivo_correcao'): ?>
        <div class="alert alert-warning">É obrigatório descrever as orientações de correção para a escola.</div>
    <?php endif; ?>

    <div class="row">
        <!-- Coluna Esquerda: Foto, Status e Deliberação -->
        <div class="col-lg-4 col-md-5 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 text-center">
                    <?php if (!empty($foto) && file_exists("../../$foto")): ?>
                        <img src="../../<?php echo htmlspecialchars($foto); ?>" alt="Foto do aluno" class="img-fluid rounded mb-3" style="max-height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3 text-muted small" style="height: 160px;">
                            Sem foto cadastrada
                        </div>
                    <?php endif; ?>

                    <h4 class="mb-1 text-dark"><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></p>

                    <div class="mb-3">
                        <?php if ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO'): ?>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Aguardando Ajustes da Escola</span>
                        <?php elseif ($aluno['status_aprovacao'] === 'PENDENTE'): ?>
                            <span class="badge bg-info text-dark px-3 py-2 rounded-pill">Pendente de Análise</span>
                        <?php else: ?>
                            <span class="badge bg-danger px-3 py-2 rounded-pill">Solicitação Recusada</span>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3 text-start small">
                        <div class="mb-1"><span class="text-muted">CPF:</span> <strong><?php echo htmlspecialchars($cpf); ?></strong></div>
                        <div class="mb-1"><span class="text-muted">RA:</span> <strong><?php echo htmlspecialchars($aluno['ra'] ?: '-'); ?></strong></div>
                        <div class="mb-1"><span class="text-muted">Data Solicitação:</span> <strong><?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y H:i', strtotime($aluno['data_cadastro'])) : '-'; ?></strong></div>
                    </div>
                </div>

                <?php if (!empty($aluno['motivo_reprovacao'])): ?>
                    <div class="card-footer bg-light border-0 p-3 text-start">
                        <strong class="d-block mb-1 <?php echo ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO') ? 'text-warning text-dark' : 'text-danger'; ?>">
                            <?php echo ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO') ? 'Ajustes Solicitados:' : 'Motivo da Recusa:'; ?>
                        </strong>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($aluno['motivo_reprovacao']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Deliberação e Parecer Técnico -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Deliberação Técnica</h5>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" onclick="aprovar(<?php echo $aluno['id_aluno']; ?>)">
                            Aprovar Atendimento
                        </button>
                        <button type="button" class="btn btn-outline-warning text-dark" onclick="solicitarAjuste(<?php echo $aluno['id_aluno']; ?>)">
                            Solicitar Ajustes
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="reprovar(<?php echo $aluno['id_aluno']; ?>)">
                            Recusar Solicitação
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Todos os Dados da Solicitação -->
        <div class="col-lg-8 col-md-7 mb-4">
            <!-- 1. Dados Pessoais -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Dados Pessoais do Aluno</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Nome Completo</span>
                            <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">CPF</span>
                            <span><?php echo htmlspecialchars($cpf); ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">RA</span>
                            <span><?php echo htmlspecialchars($aluno['ra'] ?: '-'); ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Data de Nascimento</span>
                            <span>
                                <?php echo !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?>
                                <?php echo $idadeAluno !== null ? ' (' . $idadeAluno . ' anos)' : ''; ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Gênero</span>
                            <span><?php echo htmlspecialchars($aluno['genero'] ?: '-'); ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Raça / Cor</span>
                            <span><?php echo htmlspecialchars($aluno['raca'] ?: '-'); ?></span>
                        </div>
                        <div class="col-md-12">
                            <span class="text-muted small d-block">Município de Nascimento</span>
                            <span><?php echo htmlspecialchars($aluno['municipio_nascimento'] ?: '-'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Dados Escolares e URE -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Unidade Escolar & Matrícula</h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <span class="text-muted small d-block">Escola Estadual</span>
                            <strong><?php echo htmlspecialchars($aluno['escola_nome'] ?: '-'); ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Regional (URE)</span>
                            <span><?php echo htmlspecialchars($aluno['ure_nome'] ?: '-'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Série / Ano</span>
                            <span><?php echo htmlspecialchars($aluno['serie'] ?: '-'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Turno de Aula</span>
                            <span><?php echo htmlspecialchars($aluno['turno_aula'] ?: '-'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Educação Especial e Diagnóstico -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Necessidades e Cuidados de Apoio</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <span class="text-muted small d-block">Deficiência / Diagnóstico Clínico</span>
                            <div class="p-2 rounded bg-light border text-dark mt-1">
                                <?php echo htmlspecialchars($aluno['descricao_deficiencia'] ?: 'Não informada'); ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <span class="text-muted small d-block">Cuidados Necessários / Observações de Atendimento</span>
                            <div class="p-2 rounded bg-light border text-dark mt-1" style="white-space: pre-line;">
                                <?php echo htmlspecialchars($aluno['descricao_cuidados'] ?: 'Nenhuma observação ou cuidado especial registrado.'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Responsável Legal -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Responsável Legal</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Nome do Responsável</span>
                            <span><?php echo htmlspecialchars($aluno['nome_responsavel'] ?: 'Não informado'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">CPF do Responsável</span>
                            <span><?php echo htmlspecialchars($cpfResponsavel ?: 'Não informado'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Documentos e Anexos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Documentação e Anexos</h5>

                    <div class="list-group list-group-flush">
                        <!-- Termo de Responsabilidade Obrigatório -->
                        <?php if (!empty($aluno['termo_responsabilidade_arquivo'])): ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-semibold text-dark">Termo de Responsabilidade</span>
                                    <small class="text-muted ms-2">(Documento Obrigatório)</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($aluno['termo_responsabilidade_arquivo'], '/')); ?>', 'Termo de Responsabilidade - <?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')">
                                    Visualizar Termo
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="list-group-item px-0 py-2 text-danger small">
                                Termo de responsabilidade não anexado.
                            </div>
                        <?php endif; ?>

                        <!-- Laudos e Documentos Complementares -->
                        <?php if (count($laudos) > 0): ?>
                            <?php foreach ($laudos as $laudo): ?>
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-dark"><?php echo htmlspecialchars($laudo['nome_arquivo'] ?: 'Anexo'); ?></span>
                                        <small class="text-muted ms-2">(<?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'Documento' : 'Laudo Médico'; ?>)</small>
                                    </div>
                                    <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($laudo['caminho_arquivo'], '/')); ?>', '<?php echo htmlspecialchars(addslashes($laudo['nome_arquivo'] ?: 'Documento')); ?>')">
                                            Visualizar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item px-0 py-2 text-muted small">
                                Nenhum laudo médico complementar anexado.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Formulário oculto para aprovar -->
<form id="formAprovar" action="../../controllers/solicitacoes/processar.php" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
    <input type="hidden" name="id_aluno" id="aprovar_id">
    <input type="hidden" name="acao" value="APROVAR">
</form>

<!-- Modal Padronizado para Solicitar Ajustes / Correção -->
<div class="modal fade" id="modalAjuste" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/solicitacoes/processar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="ajuste_id">
                <input type="hidden" name="acao" value="CORRECAO">

                <div class="modal-body p-4 text-center">
                    <h5 class="fw-bold mb-2 text-dark">Solicitar Ajustes</h5>
                    <p class="text-muted small mb-3">
                        Indique o que a escola precisa complementar ou corrigir nesta solicitação:
                    </p>

                    <div class="text-start mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Orientações de Correção <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control rounded-3" rows="4" maxlength="5000" required placeholder="Ex.: Anexar laudo médico mais recente com CID legível ou atualizar dados do responsável..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm">
                        Enviar Solicitação de Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Padronizado para Reprovar / Recusar -->
<div class="modal fade" id="modalReprovar" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/solicitacoes/processar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="reprovar_id">
                <input type="hidden" name="acao" value="REPROVAR">

                <div class="modal-body p-4 text-center">
                    <h5 class="fw-bold mb-2 text-dark">Recusar Solicitação</h5>
                    <p class="text-muted small mb-3">
                        Informe o parecer técnico que justifica o indeferimento:
                    </p>

                    <div class="text-start mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Motivo do Indeferimento <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control rounded-3" rows="4" maxlength="5000" required placeholder="Informe o parecer técnico que justifica o indeferimento..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        Confirmar Recusa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    async function aprovar(id) {
        const confirmado = await confirmingModal({
            title: 'Confirmar Aprovação',
            message: 'Tem certeza de que deseja aprovar o atendimento PAE deste aluno? O aluno ficará disponível para alocação de cuidador.',
            actionText: 'Aprovar'
        });

        if (confirmado) {
            document.getElementById('aprovar_id').value = id;
            document.getElementById('formAprovar').submit();
        }
    }

    function confirmingModal(options) {
        return new Promise((resolve) => {
            if (confirm(options.message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }

    function solicitarAjuste(id) {
        document.getElementById('ajuste_id').value = id;
        new bootstrap.Modal(document.getElementById('modalAjuste')).show();
    }

    function reprovar(id) {
        document.getElementById('reprovar_id').value = id;
        new bootstrap.Modal(document.getElementById('modalReprovar')).show();
    }
</script>

</body>
</html>