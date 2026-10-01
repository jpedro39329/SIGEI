<?php
require_once "../../config/init.php";

// Apenas USUARIO_EDUCACAO_ESPECIAL pode acessar
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL', 'ADMIN', 'SEDUC'));

$id_aluno = (int) ($_GET['id'] ?? 0);
if ($id_aluno <= 0) {
    header("Location: listar.php");
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-solicitacao-analisar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Analisar Solicitação</h2>
        <a href="listar.php<?php echo ($aluno['status_aprovacao'] === 'REPROVADO') ? '?aba=reprovados' : ''; ?>" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'motivo'): ?>
        <div class="alert alert-danger">É obrigatório informar o motivo da recusa.</div>
    <?php elseif (isset($_GET['erro']) && $_GET['erro'] === 'motivo_correcao'): ?>
        <div class="alert alert-warning">É obrigatório descrever as orientações de correção para a escola.</div>
    <?php endif; ?>

    <div class="row">
        <!-- Foto e dados básicos -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <?php if (!empty($foto) && file_exists("../../$foto")): ?>
                        <img src="../../<?php echo htmlspecialchars($foto); ?>" alt="Foto do aluno" class="img-fluid rounded mb-3" style="max-height:220px;">
                    <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:200px;">
                            <span class="text-muted">Sem foto</span>
                        </div>
                    <?php endif; ?>

                    <h4 class="mb-1"><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></p>
                    <?php if ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO'): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Aguardando Ajustes da Escola</span>
                    <?php elseif ($aluno['status_aprovacao'] === 'PENDENTE'): ?>
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">Pendente de Parecer</span>
                    <?php else: ?>
                        <span class="badge bg-danger px-3 py-2 rounded-pill">Solicitação Recusada</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($aluno['motivo_reprovacao'])): ?>
                    <div class="card-footer bg-light border-0 p-3 text-start">
                        <?php if ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO'): ?>
                            <strong class="text-warning text-dark d-block mb-1"><i class="bi bi-pencil-square me-1"></i>Ajustes Solicitados:</strong>
                        <?php else: ?>
                            <strong class="text-danger d-block mb-1"><i class="bi bi-x-circle me-1"></i>Motivo da Recusa:</strong>
                        <?php endif; ?>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($aluno['motivo_reprovacao']); ?></p>
                    </div>
                <?php endif; ?>
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

            <!-- Laudos e Documentos Anexados -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-medical text-primary"></i> Laudos e Documentos Anexados
                    </h5>
                    <span class="badge bg-light text-dark border"><?php echo count($laudos); ?> arquivo(s)</span>
                </div>
                <div class="card-body p-4">
                    <?php if (count($laudos) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($laudos as $laudo): ?>
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-bottom">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="<?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary'; ?> p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                            <i class="<?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'bi bi-file-earmark-text' : 'bi bi-file-earmark-medical'; ?> fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">
                                                <span class="badge <?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'bg-secondary' : 'bg-primary'; ?> me-1 small">
                                                    <?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'Documento Geral' : 'Laudo Médico'; ?>
                                                </span>
                                                <?php echo htmlspecialchars($laudo['nome_arquivo'] ?? 'Documento Comprobatório'); ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>Enviado em <?php echo date('d/m/Y \à\s H:i', strtotime($laudo['data_envio'])); ?>
                                                <?php if (!empty($laudo['descricao'])): ?>
                                                    — <span class="text-secondary"><?php echo htmlspecialchars($laudo['descricao']); ?></span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($laudo['caminho_arquivo'], '/')); ?>', '<?php echo htmlspecialchars(addslashes($laudo['nome_arquivo'] ?? 'Laudo')); ?>')">
                                            <i class="bi bi-eye"></i> Visualizar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-file-earmark-x d-block fs-3 mb-1 text-secondary opacity-50"></i>
                            Nenhum laudo ou documento anexado a esta solicitação.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Deliberação e Parecer Técnico -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-clipboard-check text-primary"></i> Parecer e Deliberação
                    </h5>
                    <p class="text-muted small mb-3">Selecione a deliberação técnica cabível para esta solicitação de apoio escolar:</p>

                    <div class="d-flex flex-wrap gap-2 pt-1">
                        <!-- 1. Aprovar -->
                        <button type="button" class="btn btn-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 rounded-3 shadow-sm" onclick="aprovar(<?php echo $aluno['id_aluno']; ?>)">
                            <i class="bi bi-check-circle-fill"></i> Aprovar Solicitação
                        </button>

                        <!-- 2. Solicitar Ajuste / Correção -->
                        <button type="button" class="btn btn-warning text-dark px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 rounded-3 shadow-sm" onclick="solicitarAjuste(<?php echo $aluno['id_aluno']; ?>)">
                            <i class="bi bi-arrow-repeat"></i> Solicitar Ajustes
                        </button>

                        <!-- 3. Recusar -->
                        <button type="button" class="btn btn-outline-danger px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 rounded-3" onclick="reprovar(<?php echo $aluno['id_aluno']; ?>)">
                            <i class="bi bi-x-circle-fill"></i> Recusar Solicitação
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal de Visualização de Laudo na Tela -->
<div class="modal fade" id="modalVisualizarLaudo" tabindex="-1" aria-labelledby="modalVisualizarLaudoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90vw;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="height: 88vh;">
            <div class="modal-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalVisualizarLaudoLabel">Visualização do Documento</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="btnBaixarLaudoModal" href="#" target="_blank" download class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-download"></i> Baixar Arquivo
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex justify-content-center align-items-center bg-dark" style="height: calc(88vh - 65px);">
                <iframe id="iframeLaudoModal" src="" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                <img id="imgLaudoModal" src="" alt="Documento" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain; display: none;">
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
            <form action="../../controllers/solicitacoes/processar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="ajuste_id">
                <input type="hidden" name="acao" value="CORRECAO">

                <div class="modal-body p-4 text-center">
                    <!-- Ícone Temático (Warning / Ajuste) -->
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 52px; height: 52px; border-radius: 50%; background: #fef3c7; color: #d97706; font-size: 1.5rem;">
                        <i class="bi bi-pencil-square"></i>
                    </div>

                    <h5 class="fw-bold mb-1 text-dark">Solicitar Ajustes</h5>
                    <p class="text-muted small mb-3">
                        Indique o que a escola precisa complementar ou corrigir nesta solicitação:
                    </p>

                    <div class="text-start mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Orientações de Correção <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control rounded-3" rows="3" required placeholder="Ex.: Anexar laudo médico mais recente com CID legível ou atualizar dados do responsável..."></textarea>
                    </div>

                    <div class="text-start">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Anexar Documento / Modelo (Opcional)</label>
                        <input type="file" name="anexo" class="form-control form-control-sm rounded-3" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">PDF, Imagem ou Documento de até 5MB.</small>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary px-4 fw-semibold rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning text-dark px-4 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-send me-1"></i> Enviar Solicitação de Ajuste
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
            <form action="../../controllers/solicitacoes/processar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="reprovar_id">
                <input type="hidden" name="acao" value="REPROVAR">

                <div class="modal-body p-4 text-center">
                    <!-- Ícone Temático (Danger / Recusa) -->
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" 
                         style="width: 52px; height: 52px; border-radius: 50%; background: #fee2e2; color: #dc3545; font-size: 1.5rem;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>

                    <h5 class="fw-bold mb-1 text-dark">Recusar Solicitação</h5>
                    <p class="text-muted small mb-3">
                        Informe o motivo da recusa desta solicitação:
                    </p>

                    <div class="text-start mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Motivo do Indeferimento <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control rounded-3" rows="3" required placeholder="Informe o parecer técnico que justifica o indeferimento..."></textarea>
                    </div>

                    <div class="text-start">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Anexar Parecer Oficial (Opcional)</label>
                        <input type="file" name="anexo" class="form-control form-control-sm rounded-3" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary px-4 fw-semibold rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger px-4 fw-semibold rounded-3">
                        Confirmar Recusa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalLaudo(caminho, nome) {
        const urlCompleta = '../../' + caminho;
        const extensao = caminho.split('.').pop().toLowerCase();
        
        document.getElementById('modalVisualizarLaudoLabel').innerText = nome || 'Visualização do Laudo';
        document.getElementById('btnBaixarLaudoModal').href = urlCompleta;
        
        const iframe = document.getElementById('iframeLaudoModal');
        const img = document.getElementById('imgLaudoModal');

        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extensao)) {
            iframe.style.display = 'none';
            iframe.src = '';
            img.src = urlCompleta;
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
            img.src = '';
            iframe.src = urlCompleta;
            iframe.style.display = 'block';
        }

        const modal = new bootstrap.Modal(document.getElementById('modalVisualizarLaudo'));
        modal.show();
    }

    document.getElementById('modalVisualizarLaudo')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('iframeLaudoModal').src = '';
        document.getElementById('imgLaudoModal').src = '';
    });

    async function aprovar(id) {
        const confirmado = await confirmarAcao({
            type: 'success',
            title: 'Confirmar Aprovação',
            message: 'Tem certeza de que deseja aprovar o atendimento PAE deste aluno?<br><small class="text-muted">O aluno será habilitado no sistema e ficará disponível para alocação de cuidador.</small>',
            actionText: 'Aprovar'
        });

        if (confirmado) {
            document.getElementById('aprovar_id').value = id;
            document.getElementById('formAprovar').submit();
        }
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