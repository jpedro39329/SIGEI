<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$id_aluno = (int) ($_GET['id'] ?? 0);

if ($id_aluno <= 0) {
    redirecionarAcessoNegado();
}

// Busca os dados completos do aluno, escola e quem cadastrou
$sqlAluno = "
    SELECT a.*, ue.nome AS escola_nome, ue.id_ure, ure.nome AS ure_nome,
           COALESCE(NULLIF(a.cadastrado_por_nome, ''), uue.nome) AS cadastrado_por_nome,
           COALESCE(NULLIF(a.cadastrado_por_cpf, ''), uue.cpf) AS cadastrado_por_cpf
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    LEFT JOIN unidades_regionais ure ON ue.id_ure = ure.id_ure
    LEFT JOIN usuarios_ue uue ON a.id_usuario_ue = uue.id_usuario_ue
    WHERE a.id_aluno = $id_aluno
";
$resultAluno = mysqli_query($conexao, $sqlAluno);
$aluno = $resultAluno ? mysqli_fetch_assoc($resultAluno) : null;

if (!$aluno) {
    redirecionarAcessoNegado();
}

// Valida permissão de visualização
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    if ((int) $aluno['id_ue'] !== $idEscola) {
        redirecionarAcessoNegado();
    }
} elseif ($userPerfil === 'DIRIGENTE' || $userPerfil === 'USUARIO_SEFISC' || $userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') {
    $idUre = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
    if ($idUre > 0 && (int) $aluno['id_ure'] !== $idUre) {
        redirecionarAcessoNegado();
    }
} elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresa);
    if (!in_array((int) $aluno['id_ure'], $uresAtendidas)) {
        redirecionarAcessoNegado();
    }
} elseif ($userPerfil === 'PAE') {
    $sqlAssoc = "SELECT id_associacao FROM associacoes WHERE id_aluno = $id_aluno AND id_pae = $userId AND ativo = 1";
    $resultAssoc = mysqli_query($conexao, $sqlAssoc);
    if (!$resultAssoc || mysqli_num_rows($resultAssoc) == 0) {
        redirecionarAcessoNegado();
    }
}

// Busca PAE associado
$sqlPae = "
    SELECT p.id_pae, p.nome, p.cpf, ass.data_inicio
    FROM associacoes ass
    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
    WHERE ass.id_aluno = $id_aluno AND ass.ativo = 1
    LIMIT 1
";
$resultPae = mysqli_query($conexao, $sqlPae);
$pae = mysqli_fetch_assoc($resultPae);

// Busca laudos e documentos
$sqlLaudos = "
    SELECT id_laudo, tipo, nome_arquivo, caminho_arquivo, descricao, data_envio
    FROM laudos
    WHERE id_aluno = $id_aluno
    ORDER BY data_envio DESC
";
$resultLaudos = mysqli_query($conexao, $sqlLaudos);
$laudos = mysqli_fetch_all($resultLaudos, MYSQLI_ASSOC);

// Busca relatórios
$sqlRelatorios = "
    SELECT r.id_relatorio, r.tipo, r.descricao, r.data_cadastro, p.nome AS pae_nome
    FROM relatorios r
    JOIN associacoes ass ON r.id_associacao = ass.id_associacao
    JOIN usuarios_pae p ON ass.id_pae = p.id_pae
    WHERE ass.id_aluno = $id_aluno
    ORDER BY r.data_cadastro DESC
";
$resultRelatorios = mysqli_query($conexao, $sqlRelatorios);
$relatorios = mysqli_fetch_all($resultRelatorios, MYSQLI_ASSOC);

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

$cpf = formatarCPF($aluno['cpf']);
$cpfResponsavel = formatarCPF($aluno['cpf_responsavel'] ?? '');
$foto = $aluno['foto_arquivo'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Aluno — <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo assetUrl('assets/css/style.css', '../../'); ?>">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-alunos-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content container-fluid px-lg-4 py-4">

    <!-- Cabeçalho Principal -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0 fw-bold text-dark">Detalhes do Aluno</h2>
            <span class="text-muted small">Prontuário e histórico de atendimento especializado</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="abrirModalExportarFicha(<?php echo (int) $aluno['id_aluno']; ?>, '<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')" class="btn btn-danger btn-sm px-3 rounded-3 d-flex align-items-center gap-2" title="Exportar ficha do aluno para PDF">
                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
            </button>
            <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
                <a href="pendentes.php?aba=<?php echo ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO' ? 'correcao' : ($aluno['status_aprovacao'] === 'REPROVADO' ? 'reprovados' : 'analise')); ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                    Voltar
                </a>
            <?php else: ?>
                <a href="listar.php<?php echo ($aluno['status_aprovacao'] === 'ARQUIVADO') ? '?aba=arquivados' : ''; ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-3">
                    Voltar à Lista
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas de Sucesso/Mensagens -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'editado'): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            Cadastro do aluno editado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'laudo_ok'): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            Documento anexado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'laudo_removido'): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
            Documento removido com sucesso.
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

                    <!-- Grid Interno de Dados Pessoais: Label em Negrito em cima, Valor normal embaixo -->
                    <div class="row g-3">
                        <div class="col-md-6 col-sm-6">
                            <strong class="d-block text-dark">CPF</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($cpf ?: 'Não informado'); ?></span>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <strong class="d-block text-dark">RA</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($aluno['ra'] ?: 'Não informado'); ?></span>
                        </div>
                        <?php if ($userPerfil !== 'PAE'): ?>
                            <div class="col-md-4 col-sm-6">
                                <strong class="d-block text-dark">Data de Nascimento</strong>
                                <span class="text-secondary">
                                    <?php if (!empty($aluno['data_nascimento'])): ?>
                                        <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?><?php echo ($idadeAluno !== null) ? ' - ' . $idadeAluno . ' anos' : ''; ?>
                                    <?php else: ?>
                                        Não informada
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <strong class="d-block text-dark">Gênero</strong>
                                <span class="text-secondary"><?php echo htmlspecialchars($aluno['genero'] ?: 'Não informado'); ?></span>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <strong class="d-block text-dark">Raça / Cor</strong>
                                <span class="text-secondary"><?php echo htmlspecialchars($aluno['raca'] ?: 'Não informada'); ?></span>
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <strong class="d-block text-dark">Município de Nascimento</strong>
                                <span class="text-secondary"><?php echo htmlspecialchars($aluno['municipio_nascimento'] ?: 'Não informado'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- 2. Card de Dados Escolares e Necessidades -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Dados Escolares & Necessidades</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-sm-12">
                            <strong class="d-block text-dark">Unidade Escolar</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($aluno['escola_nome'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <strong class="d-block text-dark">Unidade Regional de Ensino</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($aluno['ure_nome'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <strong class="d-block text-dark">Série</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($aluno['serie'] ?: 'Não informada'); ?></span>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <strong class="d-block text-dark">Turno de Aula</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($aluno['turno_aula'] ?: 'Não informado'); ?></span>
                        </div>
                    </div>

                    <!-- Diagnóstico Clínico / CID -->
                    <div class="mt-3">
                        <strong class="d-block text-dark mb-1">Diagnóstico Clínico</strong>
                        <div class="p-3 rounded-3 bg-light border text-secondary">
                            <?php echo nl2br(htmlspecialchars($aluno['descricao_deficiencia'] ?: 'Nenhuma descrição clínica informada')); ?>
                        </div>
                    </div>

                    <!-- Cuidados Necessários -->
                    <div class="mt-3">
                        <strong class="d-block text-dark mb-1">Cuidados Necessários e Apoio Solicitado</strong>
                        <div class="p-3 rounded-3 bg-light border text-secondary">
                            <?php echo nl2br(htmlspecialchars($aluno['descricao_cuidados'] ?: 'Nenhum cuidado especial registrado pela escola.')); ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. Profissional de Apoio Escolar (Exibido se Aprovado) -->
            <?php if ($aluno['status_aprovacao'] === 'APROVADO'): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Profissional de Apoio Escolar</h5>

                        <?php if ($pae): ?>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-5 col-sm-6">
                                    <strong class="d-block text-dark">Profissional Vinculado</strong>
                                    <span class="text-secondary"><?php echo htmlspecialchars($pae['nome']); ?></span>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <strong class="d-block text-dark">CPF do Profissional</strong>
                                    <span class="text-secondary"><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></span>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <strong class="d-block text-dark">Início do Atendimento</strong>
                                    <span class="text-secondary"><?php echo !empty($pae['data_inicio']) ? date('d/m/Y', strtotime($pae['data_inicio'])) : 'Não informado'; ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-3 rounded-3 bg-light border text-muted small text-center">
                                Nenhum profissional de apoio vinculado a este aluno no momento.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($userPerfil !== 'PAE'): ?>
            <!-- 4. Card de Documentos e Laudos Anexos -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold text-dark mb-0">Documentos e Laudos Anexos</h5>
                    </div>

                    <!-- Formulário Retrátil para a Escola Anexar Laudos -->
                    <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA']) && in_array($aluno['status_aprovacao'], ['PENDENTE', 'PENDENTE_CORRECAO'])): ?>
                        <div class="collapse mb-4" id="formAnexoLaudo">
                            <form action="../../controllers/alunos/laudos_salvar.php" method="POST" enctype="multipart/form-data" class="p-3 bg-light rounded-3 border">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-muted">Tipo de Documento</label>
                                        <select name="tipo" class="form-select form-select-sm rounded-2">
                                            <option value="LAUDO">Laudo Médico</option>
                                            <option value="DOCUMENTO">Documento Geral</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold text-muted">Arquivo (PDF, PNG ou JPG)</label>
                                        <input type="file" name="arquivos[]" class="form-control form-control-sm rounded-2" accept="application/pdf,image/jpeg,image/png" multiple required>
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-2 fw-semibold">Enviar Arquivo</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex flex-column gap-2">
                        <!-- Termo de Responsabilidade Obrigatório -->
                        <?php if (!empty($aluno['termo_responsabilidade_arquivo'])): ?>
                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                <span class="text-dark small fw-semibold"><?php echo htmlspecialchars(basename($aluno['termo_responsabilidade_arquivo'])); ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-2" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($aluno['termo_responsabilidade_arquivo'], '/')); ?>', 'Termo de Responsabilidade — <?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')">
                                    Visualizar
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Laudos e Documentos Complementares -->
                        <?php if (count($laudos) > 0): ?>
                            <?php foreach ($laudos as $laudo): ?>
                                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                    <span class="text-dark small fw-semibold"><?php echo htmlspecialchars($laudo['nome_arquivo'] ?: basename($laudo['caminho_arquivo'] ?? 'Documento')); ?></span>
                                    <div class="d-inline-flex gap-2 align-items-center">
                                        <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-2" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($laudo['caminho_arquivo'], '/')); ?>', '<?php echo htmlspecialchars(addslashes($laudo['nome_arquivo'] ?: 'Documento')); ?>')">
                                                Visualizar
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA']) && in_array($aluno['status_aprovacao'], ['PENDENTE', 'PENDENTE_CORRECAO'])): ?>
                                            <form method="POST" action="../../controllers/alunos/remover_laudo.php" class="d-inline"
                                                  data-confirm="true"
                                                  data-confirm-title="Remover Documento"
                                                  data-confirm-message="Deseja realmente remover este documento anexado?">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                                <input type="hidden" name="id_laudo" value="<?php echo $laudo['id_laudo']; ?>">
                                                <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 rounded-2" title="Remover Documento">
                                                    &times;
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (empty($aluno['termo_responsabilidade_arquivo'])): ?>
                            <div class="p-3 rounded-3 bg-light border text-muted small text-center">
                                Nenhum documento ou laudo anexado ao aluno.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endif; ?>

            <!-- 5. Relatórios de Atendimento (Exibido para Escola, Supervisor e Empresa) -->
            <?php if ($aluno['status_aprovacao'] === 'APROVADO' && in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'SUPERVISOR', 'USUARIO_EMPRESA'])): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h5 class="fw-bold text-dark mb-0">Relatórios de Atendimento</h5>
                           
                        </div>

                        <?php if (count($relatorios) > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($relatorios as $relatorio): ?>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2 flex-wrap">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-white text-dark border fw-semibold">
                                                    <?php echo $relatorio['tipo'] == 'DIARIO' ? 'Diário' : 'Mensal'; ?>
                                                </span>
                                                <strong class="text-dark small"><?php echo htmlspecialchars($relatorio['pae_nome']); ?></strong>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted small"><?php echo date('d/m/Y \à\s H:i', strtotime($relatorio['data_cadastro'])); ?></span>
                                                <a href="../relatorios/visualizar.php?id=<?php echo $relatorio['id_relatorio']; ?>" class="btn btn-sm btn-outline-primary px-2 py-0 rounded-2" style="font-size: 0.78rem;">
                                                    Ver Detalhes
                                                </a>
                                            </div>
                                        </div>
                                        <div class="p-3 bg-white rounded-2 border text-secondary" style="line-height: 1.6; font-size: 0.9rem;">
                                            <?php echo nl2br(htmlspecialchars($relatorio['descricao'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-3 rounded-3 bg-light border text-muted small text-center">
                                Nenhum relatório de atendimento registrado até o momento para este aluno.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- ============================================== -->
        <!-- COLUNA LATERAL (DIREITA - 4 COLUNAS)           -->
        <!-- ============================================== -->
        <div class="col-lg-4">

            <!-- 1. Card de Status e Ações do Aluno -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Status & Ações</h5>

                    <!-- Badge de Status -->
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-2">Situação do Atendimento</span>
                        <div>
                            <?php
                                $status = $aluno['status_aprovacao'];
                                if ($status == 'PENDENTE') {
                                    echo '<span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-semibold">Aguardando Análise</span>';
                                } elseif ($status == 'PENDENTE_CORRECAO') {
                                    echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">Ajuste Solicitado</span>';
                                } elseif ($status == 'APROVADO') {
                                    echo '<span class="badge bg-success px-3 py-2 rounded-pill fw-semibold">Atendimento Aprovado</span>';
                                } elseif ($status == 'REPROVADO') {
                                    echo '<span class="badge bg-danger px-3 py-2 rounded-pill fw-semibold">Solicitação Recusada</span>';
                                } else {
                                    echo '<span class="badge bg-secondary px-3 py-2 rounded-pill fw-semibold">Aluno Arquivado</span>';
                                }
                            ?>
                        </div>
                    </div>

                    <!-- Botões de Ação Contextuais: Apenas Escola pode editar e somente se pendente de correção ou reprovado -->
                    <div class="d-grid gap-2 mt-3">
                        <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA']) && in_array($aluno['status_aprovacao'], ['PENDENTE_CORRECAO', 'REPROVADO'])): ?>
                            <a href="editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-warning text-dark py-2 rounded-3 fw-semibold text-center">
                                Ajustar Solicitação
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'SEFISC', 'ADMIN', 'SEDUC']) && $aluno['status_aprovacao'] === 'ARQUIVADO'): ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-success btn-abrir-reativacao"
                                    data-id="<?php echo $aluno['id_aluno']; ?>"
                                    data-nome="<?php echo htmlspecialchars($aluno['nome']); ?>"
                                    title="Reativar Aluno">
                                Reativar
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- 2. Card de Responsável Legal -->
            <?php if ($userPerfil !== 'PAE'): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Responsável Legal</h5>

                    <div>
                        <div class="mb-3">
                            <strong class="d-block text-dark">Nome do Responsável</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($aluno['nome_responsavel'] ?: 'Não informado'); ?></span>
                        </div>
                        <div>
                            <strong class="d-block text-dark">CPF do Responsável</strong>
                            <span class="text-secondary"><?php echo htmlspecialchars($cpfResponsavel ?: 'Não informado'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 3. Card de Histórico da Solicitação -->
            <?php $historicoEventos = obterHistoricoSolicitacaoAluno($conexao, $aluno, ($userPerfil === 'PAE' ? $userId : 0)); ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Histórico da Solicitação</h5>

                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($historicoEventos as $ev): ?>
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small"><?php echo htmlspecialchars($ev['titulo']); ?></strong>
                                    <span class="text-muted" style="font-size: 0.78rem;">
                                        <?php echo htmlspecialchars($ev['data']); ?>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    <div>Nome: <strong class="text-dark"><?php echo htmlspecialchars($ev['nome']); ?></strong></div>
                                    <div class="mt-1">CPF: <?php echo htmlspecialchars($ev['cpf']); ?></div>
                                    <?php if (!empty($ev['pae_nome'])): ?>
                                        <div class="mt-2 pt-2 border-top">
                                            <div>PAE: <strong class="text-dark"><?php echo htmlspecialchars($ev['pae_nome']); ?></strong></div>
                                            <div class="mt-1">CPF do PAE: <?php echo htmlspecialchars($ev['pae_cpf'] ?: 'Não informado'); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($ev['motivo'])): ?>
                                        <div class="mt-2 pt-2 border-top text-dark">
                                            <strong class="d-block mb-1"><?php echo htmlspecialchars($ev['motivo_label'] ?: 'Motivo:'); ?></strong>
                                            <p class="mb-0 text-secondary"><?php echo nl2br(htmlspecialchars($ev['motivo'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($aluno['status_aprovacao'] === 'PENDENTE'): ?>
                            <div class="p-3 rounded-3 bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small">Em Análise Técnica</strong>
                                    <span class="text-muted" style="font-size: 0.78rem;">Etapa atual</span>
                                </div>
                                <div class="small text-muted">
                                    Aguardando deliberação da equipe da Educação Especial.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

<div class="modal fade" id="modalReativarAluno" tabindex="-1" aria-labelledby="modalReativarAlunoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="../../controllers/alunos/reativar.php" method="POST" id="formReativarAluno">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                <input type="hidden" name="id_aluno" id="reativar_id_aluno" value="">

                <div class="modal-body p-4 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width: 48px; height: 48px; border-radius: 50%; background: #ecfdf5; color: #059669; font-size: 1.4rem;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>

                    <h5 class="fw-bold mb-1 text-dark" id="modalReativarAlunoLabel">Reativar Aluno</h5>
                    <p class="text-muted small mb-0">
                        Deseja reativar o cadastro de <strong id="reativar_nome_aluno" class="text-dark">-</strong>? O aluno voltará a constar como ativo no sistema.
                    </p>
                </div>

                <div class="modal-footer border-0 bg-light px-4 py-3 gap-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-2 rounded-2 fw-medium" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-success px-3 py-2 rounded-2 fw-medium shadow-sm">
                        Confirmar Reativação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const btnReativar = e.target.closest('.btn-abrir-reativacao');
        if (!btnReativar) return;

        const modalReativarEl = document.getElementById('modalReativarAluno');
        if (!modalReativarEl) return;

        const modalReativar = bootstrap.Modal.getOrCreateInstance(modalReativarEl);
        const inputReativarId = document.getElementById('reativar_id_aluno');
        const spanReativarNome = document.getElementById('reativar_nome_aluno');
        if (inputReativarId) inputReativarId.value = btnReativar.getAttribute('data-id');
        if (spanReativarNome) spanReativarNome.textContent = btnReativar.getAttribute('data-nome');
        modalReativar.show();
    });
});
</script>

</body>
</html>
