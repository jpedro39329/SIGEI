<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$id_aluno = (int) ($_GET['id'] ?? 0);

if ($id_aluno <= 0) {
    die("Aluno não informado.");
}

// Busca os dados completos do aluno, escola e quem cadastrou
$sqlAluno = "
    SELECT a.*, ue.nome AS escola_nome, ue.id_ure, ure.nome AS ure_nome,
           uue.nome AS cadastrado_por_nome, uue.cpf AS cadastrado_por_cpf
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    LEFT JOIN unidades_regionais ure ON ue.id_ure = ure.id_ure
    LEFT JOIN usuarios_ue uue ON a.id_usuario_ue = uue.id_usuario_ue
    WHERE a.id_aluno = $id_aluno
";
$resultAluno = mysqli_query($conexao, $sqlAluno);
$aluno = mysqli_fetch_assoc($resultAluno);

if (!$aluno) {
    die("Aluno não encontrado.");
}

// Valida permissão de visualização
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    if ((int) $aluno['id_ue'] !== $idEscola) {
        die("Você não tem permissão para visualizar este aluno.");
    }
} elseif ($userPerfil === 'DIRIGENTE' || $userPerfil === 'USUARIO_SEFISC' || $userPerfil === 'USUARIO_EDUCACAO_ESPECIAL') {
    $idUre = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
    if ($idUre > 0 && (int) $aluno['id_ure'] !== $idUre) {
        die("Você não tem permissão para visualizar este aluno.");
    }
} elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresa);
    if (!in_array((int) $aluno['id_ure'], $uresAtendidas)) {
        die("Você não tem permissão para visualizar este aluno.");
    }
} elseif ($userPerfil === 'PAE') {
    $sqlAssoc = "SELECT id_associacao FROM associacoes WHERE id_aluno = $id_aluno AND id_pae = $userId AND ativo = 1";
    $resultAssoc = mysqli_query($conexao, $sqlAssoc);
    if (mysqli_num_rows($resultAssoc) == 0) {
        die("Você não tem permissão para visualizar este aluno.");
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

$dataNascimentoTexto = '-';
if (!empty($aluno['data_nascimento'])) {
    $dataNascFormatada = date('d/m/Y', strtotime($aluno['data_nascimento']));
    $idadeAluno = calcularIdade($aluno['data_nascimento']);
    if ($idadeAluno !== null) {
        $dataNascimentoTexto = $dataNascFormatada . ' - ' . $idadeAluno . ' anos';
    } else {
        $dataNascimentoTexto = $dataNascFormatada;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Aluno — <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-alunos-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <!-- Cabeçalho e Ações -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Visualizar Aluno</h2>
        </div>
        <div class="d-flex gap-2">
            <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA', 'USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_SEFISC', 'SEFISC', 'ADMIN', 'SEDUC']) && $aluno['status_aprovacao'] === 'ARQUIVADO'): ?>
                <form action="../../controllers/alunos/reativar.php" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente reativar este aluno?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                    <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                    <button type="submit" class="btn btn-success btn-sm">
                        Reativar Aluno
                    </button>
                </form>
            <?php endif; ?>

            <?php if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])): ?>
                <?php if (in_array($aluno['status_aprovacao'], ['PENDENTE_CORRECAO', 'REPROVADO'])): ?>
                    <a href="editar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-warning btn-sm text-dark">Ajustar Solicitação</a>
                <?php endif; ?>
                <a href="pendentes.php?aba=<?php echo ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO' ? 'correcao' : ($aluno['status_aprovacao'] === 'REPROVADO' ? 'reprovados' : 'analise')); ?>" class="btn btn-secondary btn-sm">Voltar</a>
            <?php else: ?>
                <a href="listar.php<?php echo ($aluno['status_aprovacao'] === 'ARQUIVADO') ? '?aba=arquivados' : ''; ?>" class="btn btn-secondary btn-sm">Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'editado'): ?>
        <div class="alert alert-success">Aluno editado com sucesso!</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'laudo_ok'): ?>
        <div class="alert alert-success">Documento anexado com sucesso!</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'laudo_removido'): ?>
        <div class="alert alert-success">Documento removido com sucesso.</div>
    <?php endif; ?>

    <!-- 1. Informações Pessoais do Aluno -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Informações do Aluno</h5>
                <div>
                    <?php
                        $status = $aluno['status_aprovacao'];
                        if ($status == 'PENDENTE') {
                            echo '<span class="badge bg-warning text-dark">Aguardando Análise</span>';
                        } elseif ($status == 'PENDENTE_CORRECAO') {
                            echo '<span class="badge bg-warning text-dark">Ajuste Solicitado</span>';
                        } elseif ($status == 'APROVADO') {
                            echo '<span class="badge bg-success">Aprovado</span>';
                        } elseif ($status == 'REPROVADO') {
                            echo '<span class="badge bg-danger">Reprovado</span>';
                        } else {
                            echo '<span class="badge bg-secondary">Arquivado</span>';
                        }
                    ?>
                </div>
            </div>

            <?php if (!empty($aluno['foto_arquivo']) && file_exists("../../" . $aluno['foto_arquivo'])): ?>
                <div class="mb-3">
                    <img src="../../<?php echo htmlspecialchars($aluno['foto_arquivo']); ?>" class="rounded" style="max-height: 140px; object-fit: cover;" alt="Foto do aluno">
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">Nome</span>
                    <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                </div>
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">CPF</span>
                    <span><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></span>
                </div>
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">RA</span>
                    <span><?php echo htmlspecialchars($aluno['ra'] ?: '-'); ?></span>
                </div>
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">Data de Nascimento</span>
                    <span><?php echo htmlspecialchars($dataNascimentoTexto); ?></span>
                </div>
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">Gênero</span>
                    <span><?php echo htmlspecialchars($aluno['genero'] ?: '-'); ?></span>
                </div>
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">Raça</span>
                    <span><?php echo htmlspecialchars($aluno['raca'] ?: '-'); ?></span>
                </div>
                <div class="col-md-4 col-sm-6">
                    <span class="text-muted small d-block">Município de Nascimento</span>
                    <span><?php echo htmlspecialchars($aluno['municipio_nascimento'] ?: '-'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Dados Escolares e Cuidados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Dados Escolares</h5>
            <div class="row g-3">
                <div class="col-md-6 col-sm-12">
                    <span class="text-muted small d-block">Unidade Escolar</span>
                    <strong><?php echo htmlspecialchars($aluno['escola_nome'] ?: '-'); ?></strong>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Série</span>
                    <span><?php echo htmlspecialchars($aluno['serie'] ?: '-'); ?></span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-muted small d-block">Turno</span>
                    <span><?php echo htmlspecialchars($aluno['turno_aula'] ?: '-'); ?></span>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">Deficiência</span>
                    <span><?php echo htmlspecialchars($aluno['descricao_deficiencia'] ?: '-'); ?></span>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">Cuidados Necessários</span>
                    <span><?php echo htmlspecialchars($aluno['descricao_cuidados'] ?: 'Não informado'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Responsável -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Responsável</h5>
            <div class="row g-3">
                <div class="col-md-6 col-sm-6">
                    <span class="text-muted small d-block">Nome do Responsável</span>
                    <span><?php echo htmlspecialchars($aluno['nome_responsavel'] ?: 'Não informado'); ?></span>
                </div>
                <div class="col-md-6 col-sm-6">
                    <span class="text-muted small d-block">CPF do Responsável</span>
                    <span><?php echo htmlspecialchars(formatarCPF($aluno['cpf_responsavel']) ?: 'Não informado'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Histórico da Solicitação e Acompanhamento -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Histórico da Solicitação</h5>
            <div class="row g-3">
                <!-- Envio -->
                <div class="col-md-6 col-12">
                    <span class="text-muted small d-block">Enviado por</span>
                    <span>
                        <?php if (!empty($aluno['cadastrado_por_nome'])): ?>
                            <?php echo htmlspecialchars($aluno['cadastrado_por_nome']); ?>
                            <?php if (!empty($aluno['cadastrado_por_cpf'])): ?>
                                - CPF: <?php echo htmlspecialchars(formatarCPF($aluno['cadastrado_por_cpf'])); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            Usuário da Escola
                        <?php endif; ?>
                        <?php if (!empty($aluno['data_cadastro'])): ?>
                            <small class="text-muted d-block"><?php echo date('d/m/Y \à\s H:i', strtotime($aluno['data_cadastro'])); ?></small>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Deliberação (Aprovado / Reprovado / Ajustes) -->
                <?php if ($aluno['status_aprovacao'] === 'APROVADO'): ?>
                    <div class="col-md-6 col-12">
                        <span class="text-muted small d-block">Aprovado por</span>
                        <span>
                            <?php echo htmlspecialchars($aluno['deliberado_por_nome'] ?: 'Equipe Educação Especial'); ?>
                            <?php if (!empty($aluno['deliberado_por_cpf'])): ?>
                                - CPF: <?php echo htmlspecialchars(formatarCPF($aluno['deliberado_por_cpf'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($aluno['data_deliberacao'])): ?>
                                <small class="text-muted d-block"><?php echo date('d/m/Y \à\s H:i', strtotime($aluno['data_deliberacao'])); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>

                <?php elseif ($aluno['status_aprovacao'] === 'PENDENTE_CORRECAO'): ?>
                    <div class="col-md-6 col-12">
                        <span class="text-muted small d-block">Ajuste solicitado por</span>
                        <span>
                            <?php echo htmlspecialchars($aluno['deliberado_por_nome'] ?: 'Equipe Educação Especial'); ?>
                            <?php if (!empty($aluno['deliberado_por_cpf'])): ?>
                                - CPF: <?php echo htmlspecialchars(formatarCPF($aluno['deliberado_por_cpf'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($aluno['data_deliberacao'])): ?>
                                <small class="text-muted d-block"><?php echo date('d/m/Y \à\s H:i', strtotime($aluno['data_deliberacao'])); ?></small>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($aluno['motivo_reprovacao'])): ?>
                            <div class="mt-2 text-dark">
                                <span class="text-muted small d-block">Orientação</span>
                                <?php echo htmlspecialchars($aluno['motivo_reprovacao']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($aluno['status_aprovacao'] === 'REPROVADO'): ?>
                    <div class="col-md-6 col-12">
                        <span class="text-muted small d-block">Reprovado por</span>
                        <span>
                            <?php echo htmlspecialchars($aluno['deliberado_por_nome'] ?: 'Equipe Educação Especial'); ?>
                            <?php if (!empty($aluno['deliberado_por_cpf'])): ?>
                                - CPF: <?php echo htmlspecialchars(formatarCPF($aluno['deliberado_por_cpf'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($aluno['data_deliberacao'])): ?>
                                <small class="text-muted d-block"><?php echo date('d/m/Y \à\s H:i', strtotime($aluno['data_deliberacao'])); ?></small>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($aluno['motivo_reprovacao'])): ?>
                            <div class="mt-2 text-dark">
                                <span class="text-muted small d-block">Motivo</span>
                                <?php echo htmlspecialchars($aluno['motivo_reprovacao']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($aluno['status_aprovacao'] === 'ARQUIVADO'): ?>
                    <div class="col-md-6 col-12">
                        <span class="text-muted small d-block">Arquivado por</span>
                        <span>
                            <?php echo htmlspecialchars($aluno['arquivado_por_nome'] ?: 'Usuário'); ?>
                            <?php if (!empty($aluno['arquivado_por_cpf'])): ?>
                                - CPF: <?php echo htmlspecialchars(formatarCPF($aluno['arquivado_por_cpf'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($aluno['data_arquivamento'])): ?>
                                <small class="text-muted d-block"><?php echo date('d/m/Y \à\s H:i', strtotime($aluno['data_arquivamento'])); ?></small>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($aluno['motivo_arquivamento'])): ?>
                            <div class="mt-2 text-dark">
                                <span class="text-muted small d-block">Motivo</span>
                                <?php echo htmlspecialchars($aluno['motivo_arquivamento']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="col-md-6 col-12">
                        <span class="text-muted small d-block">Situação</span>
                        <span>Aguardando análise da equipe da Educação Especial</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 5. Profissional de Apoio Escolar (Apenas se Aprovado) -->
    <?php if ($aluno['status_aprovacao'] === 'APROVADO'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Profissional de Apoio Escolar</h5>
                <?php if ($pae): ?>
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">Nome</span>
                            <strong><?php echo htmlspecialchars($pae['nome']); ?></strong>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">CPF</span>
                            <span><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <span class="text-muted small d-block">Data de Início</span>
                            <span><?php echo !empty($pae['data_inicio']) ? date('d/m/Y', strtotime($pae['data_inicio'])) : '-'; ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhum profissional de apoio vinculado no momento.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 6. Documentos e Laudos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Documentos e Laudos</h5>
                <?php if ($userPerfil == 'USUARIO_ESCOLA' && in_array($aluno['status_aprovacao'], ['PENDENTE', 'PENDENTE_CORRECAO'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#formAnexoLaudo">
                        Anexar Documento
                    </button>
                <?php endif; ?>
            </div>

            <!-- Formulário de Anexo Retrátil (Escola) -->
            <?php if ($userPerfil == 'USUARIO_ESCOLA' && in_array($aluno['status_aprovacao'], ['PENDENTE', 'PENDENTE_CORRECAO'])): ?>
                <div class="collapse mb-4" id="formAnexoLaudo">
                    <form action="../../controllers/alunos/laudos_salvar.php" method="POST" enctype="multipart/form-data" class="p-3 bg-light rounded border">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tipo</label>
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="LAUDO">Laudo Médico</option>
                                    <option value="DOCUMENTO">Documento Geral</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Arquivo</label>
                                <input type="file" name="arquivos[]" class="form-control form-control-sm" accept="application/pdf,image/jpeg,image/png" multiple required>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="btn btn-dark btn-sm">Enviar Arquivo</button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                        <!-- Termo de Responsabilidade -->
                        <?php if (!empty($aluno['termo_responsabilidade_arquivo'])): ?>
                            <tr>
                                <td>
                                    <strong>Termo de Responsabilidade</strong>
                                    <small class="text-muted d-block">Documento Obrigatório</small>
                                </td>
                                <td class="text-end">
                                    <button type="button" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($aluno['termo_responsabilidade_arquivo'], '/')); ?>', 'Termo de Responsabilidade - <?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <!-- Laudos e Documentos Complementares -->
                        <?php if (count($laudos) > 0): ?>
                            <?php foreach ($laudos as $laudo): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($laudo['nome_arquivo'] ?: 'Documento'); ?></strong>
                                        <small class="text-muted d-block"><?php echo ($laudo['tipo'] ?? 'LAUDO') === 'DOCUMENTO' ? 'Documento' : 'Laudo Médico'; ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                                <button type="button" onclick="visualizarDocumento('../../<?php echo htmlspecialchars(ltrim($laudo['caminho_arquivo'], '/')); ?>', '<?php echo htmlspecialchars(addslashes($laudo['nome_arquivo'] ?: 'Documento')); ?>')" class="btn btn-sm btn-outline-primary">
                                                    Ver
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($userPerfil == 'USUARIO_ESCOLA' && in_array($aluno['status_aprovacao'], ['PENDENTE', 'PENDENTE_CORRECAO'])): ?>
                                                <form method="POST" action="../../controllers/alunos/remover_laudo.php" class="d-inline" onsubmit="return confirm('Deseja realmente remover este documento?')">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                                                    <input type="hidden" name="id_laudo" value="<?php echo $laudo['id_laudo']; ?>">
                                                    <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover">&times;</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php elseif (empty($aluno['termo_responsabilidade_arquivo'])): ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center py-3">Nenhum documento ou laudo anexado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 7. Relatórios de Atendimento (Apenas se Aprovado) -->
    <?php if ($aluno['status_aprovacao'] === 'APROVADO'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Relatórios de Atendimento</h5>
                <?php if (count($relatorios) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Profissional</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatorios as $relatorio): ?>
                                    <tr>
                                        <td><?php echo $relatorio['tipo'] == 'DIARIO' ? 'Diário' : 'Mensal'; ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars(mb_substr($relatorio['descricao'], 0, 90)) . (mb_strlen($relatorio['descricao']) > 90 ? '...' : ''); ?></small></td>
                                        <td><?php echo htmlspecialchars($relatorio['pae_nome']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($relatorio['data_cadastro'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhum relatório registrado até o momento.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
