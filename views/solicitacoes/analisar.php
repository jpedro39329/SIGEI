<?php
require_once "../../config/init.php";

// Apenas USUARIO_EDUCACAO_ESPECIAL, ADMIN ou SEDUC podem acessar
exigirPerfil(array('USUARIO_EDUCACAO_ESPECIAL', 'ADMIN', 'SEDUC'));

$id_aluno = (int) ($_GET['id'] ?? 0);
if ($id_aluno <= 0) {
    header("Location: listar.php");
    exit();
}

// Busca os dados completos do aluno incluindo escola, URE e quem cadastrou
$sql = "
    SELECT a.*, e.nome AS escola_nome, e.cie AS escola_cie, e.ua AS escola_ua, e.cidade AS escola_cidade,
           ure.nome AS ure_nome, ure.uge AS ure_uge,
           uue.nome AS cadastrado_por_nome, uue.cpf AS cadastrado_por_cpf
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    LEFT JOIN unidades_regionais ure ON e.id_ure = ure.id_ure
    LEFT JOIN usuarios_ue uue ON a.id_usuario_ue = uue.id_usuario_ue
    WHERE a.id_aluno = $id_aluno
";
$result = mysqli_query($conexao, $sql);
$aluno = $result ? mysqli_fetch_assoc($result) : null;

if (!$aluno || !in_array($aluno['status_aprovacao'], ['PENDENTE', 'REPROVADO', 'PENDENTE_CORRECAO'])) {
    header("Location: listar.php");
    exit();
}

// Busca os laudos e documentos do aluno
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

// Iniciais para avatar fallback
$nomesPartes = explode(' ', trim($aluno['nome']));
$iniciais = mb_strtoupper(mb_substr($nomesPartes[0] ?? 'A', 0, 1) . (isset($nomesPartes[1]) ? mb_substr($nomesPartes[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar Solicitação — <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-solicitacao-analisar">

<?php require("../../includes/navbar.php"); ?>

<div class="content container-fluid px-lg-4 py-4">

    <!-- Cabeçalho Principal -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0 fw-bold text-dark">Analisar Solicitação</h2>
            <span class="text-muted small">Processo de deliberação técnica para atendimento especializado</span>
        </div>
        <div>
            <a href="listar.php<?php echo ($aluno['status_aprovacao'] === 'REPROVADO') ? '?aba=reprovados' : ''; ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                Voltar à Lista
            </a>
        </div>
    </div>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'motivo'): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            É obrigatório informar o parecer técnico justificando a recusa.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['erro']) && $_GET['erro'] === 'motivo_correcao'): ?>
        <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            É obrigatório descrever as orientações de correção para a escola.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Grid Assimétrico Responsivo (8 colunas principal / 4 colunas lateral) -->
    <div class="row g-4">

        <!-- ============================================== -->
        <!-- COLUNA PRINCIPAL (ESQUERDA - 8 COLUNAS)        -->
        <!-- ============================================== -->
        <div class="col-lg-8">

            <!-- 1. Card de Informações Pessoais do Aluno -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    
                    <!-- Avatar e Nome do Aluno -->
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <?php if (!empty($foto) && file_exists("../../$foto")): ?>
                            <img src="../../<?php echo htmlspecialchars($foto); ?>" alt="Foto do aluno" class="rounded-3 object-fit-cover shadow-sm flex-shrink-0" style="width: 64px; height: 64px;">
                        <?php else: ?>
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-5 text-primary" style="width: 64px; height: 64px; background-color: #e8f1ff;">
                                <?php echo htmlspecialchars($iniciais); ?>
                            </div>
                        <?php endif; ?>

                        <div class="overflow-hidden">
                            <h4 class="fw-bold text-dark mb-1 text-truncate"><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                            <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                                <span>RA: <strong class="text-dark"><?php echo htmlspecialchars($aluno['ra'] ?: 'Não informado'); ?></strong></span>
                                <span>•</span>
                                <span>CPF: <strong class="text-dark"><?php echo htmlspecialchars($cpf ?: 'Não informado'); ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Interno de Dados Pessoais -->
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">CPF</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($cpf ?: 'Não informado'); ?></span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">RA do Aluno</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['ra'] ?: 'Não informado'); ?></span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">Data de Nascimento</span>
                            <span class="fw-medium text-dark">
                                <?php if (!empty($aluno['data_nascimento'])): ?>
                                    <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?>
                                    <?php if ($idadeAluno !== null): ?>
                                        • <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1"><?php echo $idadeAluno; ?> anos</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Não informada
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">Gênero</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['genero'] ?: 'Não informado'); ?></span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">Raça / Cor</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['raca'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">Município de Nascimento</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['municipio_nascimento'] ?: 'Não informado'); ?></span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 2. Card de Dados Escolares e Necessidades -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Dados Escolares & Necessidades</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-sm-12">
                            <span class="text-muted small d-block">Unidade Escolar</span>
                            <strong class="text-dark"><?php echo htmlspecialchars($aluno['escola_nome'] ?: 'Não informada'); ?></strong>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <span class="text-muted small d-block">Unidade Regional de Ensino</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['ure_nome'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <span class="text-muted small d-block">Série</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['serie'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <span class="text-muted small d-block">Turno de Aula</span>
                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($aluno['turno_aula'] ?: 'Não informado'); ?></span>
                        </div>
                    </div>

                    <!-- Diagnóstico Clínico / CID -->
                    <div class="mt-3">
                        <span class="text-muted small d-block mb-1">Diagnóstico Clínico / Deficiência</span>
                        <div class="p-3 rounded-3 bg-light border text-dark">
                            <?php echo nl2br(htmlspecialchars($aluno['descricao_deficiencia'] ?: 'Nenhuma descrição clínica informada')); ?>
                        </div>
                    </div>

                    <!-- Cuidados Necessários -->
                    <div class="mt-3">
                        <span class="text-muted small d-block mb-1">Cuidados Necessários e Apoio Solicitado</span>
                        <div class="p-3 rounded-3 bg-light border text-dark">
                            <?php echo nl2br(htmlspecialchars($aluno['descricao_cuidados'] ?: 'Nenhum cuidado especial registrado pela escola.')); ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. Card de Documentos e Laudos Anexos -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Documentação e Laudos Anexos</h5>

                    <div class="d-flex flex-column gap-2">
                        <!-- Termo de Responsabilidade Obrigatório -->
                        <?php if (!empty($aluno['termo_responsabilidade_arquivo'])): ?>
                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <strong class="text-dark small">Termo de Responsabilidade</strong>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-0 px-2" style="font-size: 0.72rem;">Obrigatório</span>
                                    </div>
                                    <span class="text-muted d-block" style="font-size: 0.78rem;">Documento anexado pela unidade escolar</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-2" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($aluno['termo_responsabilidade_arquivo'], '/')); ?>', 'Termo de Responsabilidade — <?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')">
                                    Visualizar
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="p-3 rounded-3 bg-danger-subtle border border-danger-subtle text-danger small">
                                <strong>Atenção:</strong> Termo de responsabilidade obrigatório não foi anexado pela escola.
                            </div>
                        <?php endif; ?>

                        <!-- Laudos e Documentos Complementares -->
                        <?php if (count($laudos) > 0): ?>
                            <?php foreach ($laudos as $laudo): ?>
                                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-dark small"><?php echo htmlspecialchars($laudo['nome_arquivo'] ?: 'Documento Anexo'); ?></strong>
                                            <span class="badge bg-light text-secondary border rounded-pill py-0 px-2" style="font-size: 0.72rem;">
                                                <?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'Documento' : 'Laudo Médico'; ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($laudo['data_envio'])): ?>
                                            <span class="text-muted d-block" style="font-size: 0.78rem;">Enviado em <?php echo date('d/m/Y \à\s H:i', strtotime($laudo['data_envio'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-2" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($laudo['caminho_arquivo'], '/')); ?>', '<?php echo htmlspecialchars(addslashes($laudo['nome_arquivo'] ?: 'Documento')); ?>')">
                                            Visualizar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 rounded-3 bg-light border text-muted small text-center">
                                Nenhum laudo médico complementar anexado a esta solicitação.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>

        <!-- ============================================== -->
        <!-- COLUNA LATERAL (DIREITA - 4 COLUNAS)           -->
        <!-- ============================================== -->
        <div class="col-lg-4">

            <!-- 1. Card de Deliberação Técnica & Ações -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Deliberação Técnica</h5>

                    <!-- Status Atual da Solicitação -->
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-2">Status Atual</span>
                        <div>
                            <?php if ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO'): ?>
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">Aguardando Ajustes da Escola</span>
                            <?php elseif ($aluno['status_aprovacao'] === 'PENDENTE'): ?>
                                <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-semibold">Pendente de Análise</span>
                            <?php else: ?>
                                <span class="badge bg-danger px-3 py-2 rounded-pill fw-semibold">Solicitação Recusada</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Botões de Ação com Abertura Direta dos Modais -->
                    <div class="d-grid gap-2 mt-3">
                        <button type="button" class="btn btn-success py-2 rounded-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAprovar">
                            Aprovar Atendimento
                        </button>
                        <button type="button" class="btn btn-outline-warning text-dark py-2 rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAjuste">
                            Solicitar Ajustes
                        </button>
                        <button type="button" class="btn btn-outline-danger py-2 rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalReprovar">
                            Recusar Solicitação
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Card de Responsável Legal -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Responsável Legal</h5>

                    <div>
                        <span class="text-muted small d-block">Nome do Responsável</span>
                        <strong class="text-dark d-block mb-2"><?php echo htmlspecialchars($aluno['nome_responsavel'] ?: 'Não informado'); ?></strong>

                        <span class="text-muted small d-block">CPF do Responsável</span>
                        <span class="text-dark"><?php echo htmlspecialchars($cpfResponsavel ?: 'Não informado'); ?></span>
                    </div>
                </div>
            </div>

            <!-- 3. Card de Histórico da Solicitação -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Histórico da Solicitação</h5>

                    <div class="d-flex flex-column gap-3">
                        <!-- Envio da Solicitação -->
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-dark small">Solicitação Cadastrada</strong>
                                <span class="text-muted" style="font-size: 0.78rem;">
                                    <?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y \à\s H:i', strtotime($aluno['data_cadastro'])) : '-'; ?>
                                </span>
                            </div>
                            <div class="small text-muted">
                                Enviado por: <strong class="text-dark"><?php echo htmlspecialchars($aluno['cadastrado_por_nome'] ?: 'Equipe Escolar'); ?></strong>
                                <?php if (!empty($aluno['cadastrado_por_cpf'])): ?>
                                    <div class="mt-1">CPF: <?php echo htmlspecialchars(formatarCPF($aluno['cadastrado_por_cpf'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Deliberação / Ajuste / Recusa -->
                        <?php if (!empty($aluno['data_deliberacao'])): ?>
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small">
                                        <?php 
                                            if ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO') echo 'Ajustes Solicitados';
                                            elseif ($aluno['status_aprovacao'] === 'REPROVADO') echo 'Solicitação Recusada';
                                            else echo 'Atendimento Aprovado';
                                        ?>
                                    </strong>
                                    <span class="text-muted" style="font-size: 0.78rem;">
                                        <?php echo date('d/m/Y \à\s H:i', strtotime($aluno['data_deliberacao'])); ?>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    Por: <strong class="text-dark"><?php echo htmlspecialchars($aluno['deliberado_por_nome'] ?: 'Educação Especial'); ?></strong>
                                    <?php if (!empty($aluno['deliberado_por_cpf'])): ?>
                                        <div class="mt-1">CPF: <?php echo htmlspecialchars(formatarCPF($aluno['deliberado_por_cpf'])); ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($aluno['motivo_reprovacao'])): ?>
                                        <div class="mt-2 pt-2 border-top text-dark">
                                            <strong class="d-block mb-1"><?php echo ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO') ? 'Orientações de Correção:' : 'Motivo:'; ?></strong>
                                            <p class="mb-0 text-secondary"><?php echo nl2br(htmlspecialchars($aluno['motivo_reprovacao'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small">Em Análise Técnica</strong>
                                    <span class="text-muted" style="font-size: 0.78rem;">Etapa atual</span>
                                </div>
                                <div class="small text-muted">
                                    Aguardando posicionamento da equipe técnica da Educação Especial.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

<!-- ============================================================== -->
<!-- MODAIS DE CONFIRMAÇÃO E DELIBERAÇÃO (RENDERIZADOS NO DOM)      -->
<!-- ============================================================== -->

<!-- 1. Modal Padronizado para Aprovar Atendimento -->
<div class="modal fade" id="modalAprovar" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/solicitacoes/processar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                <input type="hidden" name="acao" value="APROVAR">

                <div class="modal-body p-4 text-center">
                    <h5 class="fw-bold mb-2 text-dark">Confirmar Aprovação</h5>
                    <p class="text-muted small mb-0" style="line-height: 1.5;">
                        Tem certeza de que deseja aprovar o atendimento especializado (PAE) para o aluno <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>? O cadastro será validado e ficará apto para vinculação de profissional de apoio.
                    </p>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success btn-sm px-4 rounded-3 fw-semibold">
                        Aprovar Atendimento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Modal Padronizado para Solicitar Ajustes / Correção -->
<div class="modal fade" id="modalAjuste" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/solicitacoes/processar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                <input type="hidden" name="acao" value="CORRECAO">

                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <h5 class="fw-bold mb-1 text-dark">Solicitar Ajustes à Escola</h5>
                        <p class="text-muted small mb-0">
                            Indique as pendências ou complementações necessárias para a reavaliação:
                        </p>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Orientações de Correção <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control rounded-3" rows="4" maxlength="5000" required placeholder="Ex.: Anexar laudo médico mais recente com CID legível ou atualizar dados do responsável..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm px-4 rounded-3 fw-semibold">
                        Enviar Solicitação de Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Modal Padronizado para Reprovar / Recusar -->
<div class="modal fade" id="modalReprovar" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/solicitacoes/processar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                <input type="hidden" name="acao" value="REPROVAR">

                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <h5 class="fw-bold mb-1 text-dark">Recusar Solicitação</h5>
                        <p class="text-muted small mb-0">
                            Informe o parecer técnico fundamentado para o indeferimento:
                        </p>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Parecer Técnico / Motivo <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control rounded-3" rows="4" maxlength="5000" required placeholder="Informe as razões técnicas que motivaram a recusa da solicitação..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 rounded-3 fw-semibold">
                        Confirmar Recusa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>