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

// Busca os dados do aluno
$sqlAluno = "
    SELECT a.*, ue.nome AS escola_nome, ue.id_ure
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
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

// Busca laudos
$sqlLaudos = "
    SELECT id_laudo, nome_arquivo, caminho_arquivo, descricao, data_envio
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="page-alunos-visualizar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Detalhes do Aluno</h2>
        <a href="listar.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'editado'): ?>
        <div class="alert alert-success">Aluno editado com sucesso!</div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'laudo_ok'): ?>
        <div class="alert alert-success">Laudo enviado com sucesso!</div>
    <?php endif; ?>

    <div class="row">
        <!-- Foto e dados básicos -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <?php if (!empty($aluno['foto_arquivo'])): ?>
                        <img src="../../<?php echo $aluno['foto_arquivo']; ?>" class="img-fluid rounded mb-3" style="max-height: 200px;" alt="Foto do aluno">
                    <?php else: ?>
                        <div class="display-1 mb-3">👤</div>
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></p>
                    <p>
                        <?php
                            $status = $aluno['status_aprovacao'];
                            if ($status == 'PENDENTE') {
                                echo '<span class="badge bg-warning text-dark">Pendente</span>';
                            } elseif ($status == 'APROVADO') {
                                echo '<span class="badge bg-success">Aprovado</span>';
                            } elseif ($status == 'REPROVADO') {
                                echo '<span class="badge bg-danger">Reprovado</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Arquivado</span>';
                            }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Dados do aluno -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">📋 Dados do Aluno</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CPF</span>
                            <strong><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">RA</span>
                            <strong><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Data de Nascimento</span>
                            <strong><?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Gênero</span>
                            <strong><?php echo htmlspecialchars($aluno['genero'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Raça/Cor</span>
                            <strong><?php echo htmlspecialchars($aluno['raca'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Município de Nascimento</span>
                            <strong><?php echo htmlspecialchars($aluno['municipio_nascimento'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Série</span>
                            <strong><?php echo htmlspecialchars($aluno['serie'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Turno de Aula</span>
                            <strong><?php echo htmlspecialchars($aluno['turno_aula'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Deficiência</span>
                            <strong><?php echo htmlspecialchars($aluno['descricao_deficiencia'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Cuidados Necessários</span>
                            <strong><?php echo htmlspecialchars($aluno['descricao_cuidados'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Responsável</span>
                            <strong><?php echo htmlspecialchars($aluno['nome_responsavel'] ?? '-'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">CPF do Responsável</span>
                            <strong><?php echo htmlspecialchars(formatarCPF($aluno['cpf_responsavel'] ?? '')); ?></strong>
                        </li>
                        <?php if (!empty($aluno['termo_responsabilidade_arquivo'])): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Termo de Responsabilidade</span>
                            <a href="../../<?php echo htmlspecialchars($aluno['termo_responsabilidade_arquivo']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                📄 Visualizar Termo
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($aluno['motivo_reprovacao'])): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Motivo da Reprovação</span>
                            <strong class="text-danger"><?php echo htmlspecialchars($aluno['motivo_reprovacao']); ?></strong>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- PAE associado -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">👨‍🏫 PAE Associado</h5>
            <?php if ($pae): ?>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($pae['nome']); ?></p>
                <?php if ($userPerfil == 'ADMIN' || $userPerfil == 'USUARIO_ESCOLA'): ?>
                    <p><strong>CPF:</strong> <?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Nenhum PAE associado a este aluno.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Laudos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3">📄 Laudos</h5>
            <?php if ($userPerfil == 'USUARIO_ESCOLA'): ?>
                <form action="../../controllers/alunos/laudos_salvar.php" method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                    <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Nome do laudo</label>
                            <input type="text" name="nome_arquivo" class="form-control" placeholder="Ex.: Laudo medico">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Arquivo</label>
                            <input type="file" name="laudo" class="form-control" accept="application/pdf,image/jpeg,image/png" required>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-dark">Enviar</button>
                        </div>
                    </div>
                    <textarea name="descricao" class="form-control mt-2" rows="2" placeholder="Descricao opcional"></textarea>
                </form>
            <?php endif; ?>

            <?php if (count($laudos) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($laudos as $laudo): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($laudo['nome_arquivo'] ?? 'Laudo'); ?></span>
                            <span class="text-muted small"><?php echo date('d/m/Y', strtotime($laudo['data_envio'])); ?></span>
                            <?php if (!empty($laudo['caminho_arquivo'])): ?>
                                <a href="../../<?php echo $laudo['caminho_arquivo']; ?>" target="_blank" class="btn btn-sm btn-info">Baixar</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">Nenhum laudo cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Relatórios -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="mb-3">📝 Relatórios</h5>
            <?php if (count($relatorios) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>PAE</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorios as $relatorio): ?>
                                <tr>
                                    <td><?php echo $relatorio['tipo'] == 'DIARIO' ? 'Diário' : 'Mensal'; ?></td>
                                    <td><?php echo htmlspecialchars($relatorio['descricao']); ?></td>
                                    <td><?php echo htmlspecialchars($relatorio['pae_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($relatorio['data_cadastro'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Nenhum relatório cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
