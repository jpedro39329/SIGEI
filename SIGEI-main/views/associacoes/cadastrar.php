<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$preIdPae = (int) ($_GET['id_pae'] ?? 0);
$preIdAluno = (int) ($_GET['id_aluno'] ?? 0);

$whereAlunoUre = "";
$wherePaeEmpresa = "";

if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresaUsuario);
    if (!empty($uresAtendidas)) {
        $uresList = implode(',', $uresAtendidas);
        $whereAlunoUre = "AND e.id_ure IN ($uresList)";
    } else {
        $whereAlunoUre = "AND 1=0";
    }
    $wherePaeEmpresa = "AND p.id_empresa = $idEmpresaUsuario";
}

// 1. Lista de Alunos Aprovados que ainda NÃO possuem nenhum PAE ativo vinculado
$sqlAlunos = "
    SELECT a.id_aluno, a.nome, a.cpf, a.ra, a.descricao_deficiencia, e.nome AS escola_nome,
           (SELECT COUNT(*) FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1) AS qtd_paes
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    WHERE a.status_aprovacao = 'APROVADO'
    $whereAlunoUre
    HAVING qtd_paes = 0
    ORDER BY a.nome ASC
";
$resultAlunos = mysqli_query($conexao, $sqlAlunos);
$alunosDisponiveis = $resultAlunos ? mysqli_fetch_all($resultAlunos, MYSQLI_ASSOC) : [];

// 2. Lista de PAEs ativos que têm menos de 3 alunos ativos vinculados
$sqlPAEs = "
    SELECT p.id_pae, p.nome, p.cpf, e.nome AS empresa_nome,
           (SELECT COUNT(*) FROM associacoes ass WHERE ass.id_pae = p.id_pae AND ass.ativo = 1) AS qtd_alunos
    FROM usuarios_pae p
    LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
    WHERE p.ativo = 1
    $wherePaeEmpresa
    HAVING qtd_alunos < 3
    ORDER BY p.nome ASC
";
$resultPAEs = mysqli_query($conexao, $sqlPAEs);
$paesDisponiveis = $resultPAEs ? mysqli_fetch_all($resultPAEs, MYSQLI_ASSOC) : [];

$dataHoje = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Associação — SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
    <style>
        .aluno-busca-wrapper {
            position: relative;
        }
        .aluno-dropdown-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            max-height: 260px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #d9e4f2;
            border-radius: 0.5rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            z-index: 1050;
            display: none;
        }
        .aluno-item-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }
        .aluno-item-option:hover {
            background-color: #f4f8ff;
        }
        .aluno-item-option:last-child {
            border-bottom: 0;
        }
        .selected-aluno-card {
            background-color: #f8fafc;
            border: 1px solid #d9e4f2;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }
    </style>
</head>
<body class="page-associacoes-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <div>
                            <h2 class="mb-1">Nova Associação PAE ↔ Aluno</h2>
                            <p class="text-muted mb-0">Vincule um profissional de apoio escolar a um aluno sem atendimento ativo.</p>
                        </div>
                        <a href="gerenciar.php" class="btn btn-secondary btn-sm">Voltar</a>
                    </div>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/associacoes/associar.php" method="POST" id="formAssociacao">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_aluno" id="hiddenIdAluno" value="<?php echo $preIdAluno > 0 ? $preIdAluno : ''; ?>" required>
                        <input type="hidden" name="data_inicio" value="<?php echo $dataHoje; ?>">

                        <!-- 1. Seleção do Profissional de Apoio (PAE) -->
                        <div class="mb-4">
                            <label class="form-label">Profissional de Apoio Escolar (PAE) <span class="text-danger">*</span></label>
                            <select name="id_pae" id="selectPae" class="form-select" required>
                                <option value="">-- Selecione o profissional de apoio --</option>
                                <?php foreach ($paesDisponiveis as $pae): ?>
                                    <option value="<?php echo $pae['id_pae']; ?>" <?php echo ($preIdPae === (int)$pae['id_pae']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pae['nome']); ?> — <?php echo htmlspecialchars($pae['empresa_nome'] ?: 'Sem empresa'); ?> (<?php echo $pae['qtd_alunos']; ?>/3 alunos vinculados)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted d-block mt-1">Limite do cuidador: cada PAE pode atender no máximo 3 alunos simultaneamente.</small>
                        </div>

                        <!-- 2. Pesquisa e Seleção do Aluno -->
                        <div class="mb-4">
                            <label class="form-label">Aluno da Rede Estadual <span class="text-danger">*</span></label>
                            
                            <div class="aluno-busca-wrapper">
                                <input type="text" id="inputBuscaAluno" class="form-control" placeholder="Pesquisar aluno por nome, CPF ou escola..." autocomplete="off">
                                
                                <div id="dropdownAlunos" class="aluno-dropdown-menu">
                                    <?php if (count($alunosDisponiveis) > 0): ?>
                                        <?php foreach ($alunosDisponiveis as $al): ?>
                                            <div class="aluno-item-option" 
                                                 data-id="<?php echo $al['id_aluno']; ?>"
                                                 data-nome="<?php echo htmlspecialchars($al['nome']); ?>"
                                                 data-escola="<?php echo htmlspecialchars($al['escola_nome'] ?: 'Sem escola informada'); ?>"
                                                 data-cpf="<?php echo htmlspecialchars(formatarCPF($al['cpf']) ?: 'Não informado'); ?>"
                                                 data-def="<?php echo htmlspecialchars($al['descricao_deficiencia'] ?: 'Geral'); ?>">
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($al['nome']); ?></div>
                                                <div class="small text-muted">
                                                    Escola: <?php echo htmlspecialchars($al['escola_nome'] ?: '-'); ?> | 
                                                    CPF: <?php echo htmlspecialchars(formatarCPF($al['cpf']) ?: '-'); ?> |
                                                    Necessidade: <?php echo htmlspecialchars($al['descricao_deficiencia'] ?: 'Geral'); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-3 text-center text-muted small">Nenhum aluno aprovado sem PAE disponível no momento.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Cartão de Aluno Confirmado -->
                            <div id="cardAlunoConfirmado" class="selected-aluno-card mt-2" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark fs-6" id="nomeAlunoConf">-</div>
                                        <div class="text-muted small" id="detalhesAlunoConf">-</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnAlterarAluno">Trocar Aluno</button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">Regra: apenas alunos aprovados e sem nenhum PAE vinculado podem ser selecionados.</small>
                        </div>

                        <!-- 3. Data de Início Automática -->
                        <div class="mb-4">
                            <label class="form-label">Data de Início do Atendimento</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?> (Gerada automaticamente para hoje)" disabled>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                            <a href="gerenciar.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary" id="btnConfirmarAssoc">Confirmar Associação</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var inputBusca = document.getElementById('inputBuscaAluno');
    var dropdown = document.getElementById('dropdownAlunos');
    var items = dropdown.querySelectorAll('.aluno-item-option');
    var hiddenId = document.getElementById('hiddenIdAluno');
    var cardConfirmado = document.getElementById('cardAlunoConfirmado');
    var nomeConf = document.getElementById('nomeAlunoConf');
    var detalhesConf = document.getElementById('detalhesAlunoConf');
    var btnAlterar = document.getElementById('btnAlterarAluno');

    function filtrar() {
        var termo = inputBusca.value.toLowerCase().trim();
        var count = 0;
        items.forEach(function(item) {
            var texto = item.innerText.toLowerCase();
            if (texto.indexOf(termo) !== -1) {
                item.style.display = 'block';
                count++;
            } else {
                item.style.display = 'none';
            }
        });
        dropdown.style.display = (count > 0 && termo.length > 0) ? 'block' : 'none';
    }

    inputBusca.addEventListener('input', filtrar);
    inputBusca.addEventListener('focus', function() {
        if (inputBusca.value.trim().length > 0) {
            filtrar();
        } else {
            items.forEach(function(i) { i.style.display = 'block'; });
            dropdown.style.display = 'block';
        }
    });

    document.addEventListener('click', function(e) {
        if (!inputBusca.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    items.forEach(function(item) {
        item.addEventListener('click', function() {
            var id = item.getAttribute('data-id');
            var nome = item.getAttribute('data-nome');
            var escola = item.getAttribute('data-escola');
            var cpf = item.getAttribute('data-cpf');
            var def = item.getAttribute('data-def');

            selecionar(id, nome, escola, cpf, def);
        });
    });

    function selecionar(id, nome, escola, cpf, def) {
        hiddenId.value = id;
        nomeConf.textContent = nome;
        detalhesConf.textContent = "Escola: " + escola + " | CPF: " + cpf + " | Deficiência: " + def;
        cardConfirmado.style.display = 'block';
        inputBusca.style.display = 'none';
        dropdown.style.display = 'none';
    }

    btnAlterar.addEventListener('click', function() {
        hiddenId.value = '';
        cardConfirmado.style.display = 'none';
        inputBusca.style.display = 'block';
        inputBusca.value = '';
        inputBusca.focus();
    });

    // Se houver id_aluno pré-selecionado
    var preAluno = "<?php echo $preIdAluno; ?>";
    if (preAluno && preAluno !== "0") {
        var preItem = dropdown.querySelector('.aluno-item-option[data-id="' + preAluno + '"]');
        if (preItem) {
            selecionar(
                preItem.getAttribute('data-id'),
                preItem.getAttribute('data-nome'),
                preItem.getAttribute('data-escola'),
                preItem.getAttribute('data-cpf'),
                preItem.getAttribute('data-def')
            );
        }
    }

    document.getElementById('formAssociacao').addEventListener('submit', function(e) {
        if (!hiddenId.value) {
            e.preventDefault();
            alert('Por favor, selecione um aluno para associar.');
            inputBusca.focus();
        }
    });
});
</script>

</body>
</html>
