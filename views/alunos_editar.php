<?php
require_once "../config/init.php";

// Apenas USUARIO_ESCOLA pode editar alunos
exigirPerfil(array('USUARIO_ESCOLA'));

$userId = $_SESSION['user_id'];
$idEscola = idEscolaUsuario($conexao, $userId);
$id_aluno = (int) ($_GET['id'] ?? 0);

// Busca o aluno e verifica que pertence à escola
$sql = "
    SELECT a.*, e.nome AS escola_nome
    FROM alunos a
    LEFT JOIN escolas e ON a.id_escola = e.id_escola
    WHERE a.id_aluno = $id_aluno AND a.id_escola = $idEscola
";
$result = mysqli_query($conexao, $sql);
$aluno = mysqli_fetch_assoc($result);

if (!$aluno) {
    die("Aluno não encontrado ou não pertence à sua escola.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-alunos-editar">

<?php require("navbar.php"); ?>

<div class="content">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-2">Editar Aluno</h2>
                    <p class="text-muted mb-4">Edite os dados cadastrais do aluno. O CPF e o vínculo escolar não podem ser alterados.</p>

                    <!-- ================================================= -->
                    <!-- FORMULÁRIO -->
                    <!-- ================================================= -->
                    <form action="../controllers/alunos_editar_salvar.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_aluno" value="<?php echo $aluno['id_aluno']; ?>">

                        <div class="row">

                            <!-- ================================================= -->
                            <!-- DADOS DO ALUNO -->
                            <!-- ================================================= -->
                            <div class="col-12">
                                <h5 class="mb-3">Dados do Aluno</h5>
                            </div>

                            <!-- NOME -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input
                                    type="text"
                                    name="nome"
                                    class="form-control"
                                    placeholder="Digite o nome completo do aluno"
                                    value="<?php echo htmlspecialchars($aluno['nome'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <!-- CPF -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CPF</label>
                                <input
                                    type="text"
                                    name="cpf"
                                    id="cpf"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars(formatarCPF($aluno['cpf'] ?? '')); ?>"
                                    readonly
                                >
                                <small class="text-muted">O CPF não pode ser alterado.</small>
                            </div>

                            <!-- RA -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">RA</label>
                                <input
                                    type="text"
                                    name="ra"
                                    class="form-control"
                                    placeholder="Registro do aluno"
                                    value="<?php echo htmlspecialchars($aluno['ra'] ?? ''); ?>"
                                >
                            </div>

                            <!-- DATA DE NASCIMENTO -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Data de Nascimento</label>
                                <input
                                    type="date"
                                    name="data_nascimento"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($aluno['data_nascimento'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <!-- GÊNERO -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gênero</label>
                                <select name="genero" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="FEMININO" <?php echo ($aluno['genero'] ?? '') === 'FEMININO' ? 'selected' : ''; ?>>Feminino</option>
                                    <option value="MASCULINO" <?php echo ($aluno['genero'] ?? '') === 'MASCULINO' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="NAO_BINARIO" <?php echo ($aluno['genero'] ?? '') === 'NAO_BINARIO' ? 'selected' : ''; ?>>Não binário</option>
                                    <option value="OUTRO" <?php echo ($aluno['genero'] ?? '') === 'OUTRO' ? 'selected' : ''; ?>>Outro</option>
                                    <option value="NAO_INFORMADO" <?php echo ($aluno['genero'] ?? '') === 'NAO_INFORMADO' ? 'selected' : ''; ?>>Prefiro não informar</option>
                                </select>
                            </div>

                            <!-- RAÇA/COR -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Raça/Cor</label>
                                <select name="raca" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="BRANCA" <?php echo ($aluno['raca'] ?? '') === 'BRANCA' ? 'selected' : ''; ?>>Branca</option>
                                    <option value="PRETA" <?php echo ($aluno['raca'] ?? '') === 'PRETA' ? 'selected' : ''; ?>>Preta</option>
                                    <option value="PARDA" <?php echo ($aluno['raca'] ?? '') === 'PARDA' ? 'selected' : ''; ?>>Parda</option>
                                    <option value="AMARELA" <?php echo ($aluno['raca'] ?? '') === 'AMARELA' ? 'selected' : ''; ?>>Amarela</option>
                                    <option value="INDIGENA" <?php echo ($aluno['raca'] ?? '') === 'INDIGENA' ? 'selected' : ''; ?>>Indígena</option>
                                    <option value="NAO_INFORMADO" <?php echo ($aluno['raca'] ?? '') === 'NAO_INFORMADO' ? 'selected' : ''; ?>>Não informado</option>
                                </select>
                            </div>

                            <!-- MUNICÍPIO -->
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Município de Nascimento</label>
                                <input
                                    type="text"
                                    name="municipio_nascimento"
                                    class="form-control"
                                    placeholder="Ex.: Bragança Paulista"
                                    value="<?php echo htmlspecialchars($aluno['municipio_nascimento'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <!-- SÉRIE -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Série</label>
                                <select name="serie" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="6º ANO" <?php echo ($aluno['serie'] ?? '') === '6º ANO' ? 'selected' : ''; ?>>6º Ano</option>
                                    <option value="7º ANO" <?php echo ($aluno['serie'] ?? '') === '7º ANO' ? 'selected' : ''; ?>>7º Ano</option>
                                    <option value="8º ANO" <?php echo ($aluno['serie'] ?? '') === '8º ANO' ? 'selected' : ''; ?>>8º Ano</option>
                                    <option value="9º ANO" <?php echo ($aluno['serie'] ?? '') === '9º ANO' ? 'selected' : ''; ?>>9º Ano</option>
                                    <option value="1ª SÉRIE" <?php echo ($aluno['serie'] ?? '') === '1ª SÉRIE' ? 'selected' : ''; ?>>1ª Série</option>
                                    <option value="2ª SÉRIE" <?php echo ($aluno['serie'] ?? '') === '2ª SÉRIE' ? 'selected' : ''; ?>>2ª Série</option>
                                    <option value="3ª SÉRIE" <?php echo ($aluno['serie'] ?? '') === '3ª SÉRIE' ? 'selected' : ''; ?>>3ª Série</option>
                                </select>
                            </div>

                            <!-- ================================================= -->
                            <!-- DADOS ESCOLARES -->
                            <!-- ================================================= -->
                            <div class="col-12 mt-3">
                                <h5 class="mb-3">Dados Escolares</h5>
                            </div>

                            <!-- TURNO -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Turno de Aula</label>
                                <select name="turno_aula" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="MANHÃ" <?php echo ($aluno['turno_aula'] ?? '') === 'MANHÃ' ? 'selected' : ''; ?>>Manhã</option>
                                    <option value="TARDE" <?php echo ($aluno['turno_aula'] ?? '') === 'TARDE' ? 'selected' : ''; ?>>Tarde</option>
                                    <option value="NOITE" <?php echo ($aluno['turno_aula'] ?? '') === 'NOITE' ? 'selected' : ''; ?>>Noite</option>
                                    <option value="INTEGRAL" <?php echo ($aluno['turno_aula'] ?? '') === 'INTEGRAL' ? 'selected' : ''; ?>>Integral</option>
                                </select>
                            </div>

                            <!-- ESCOLA -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Escola Estadual</label>
                                <input
                                    type="text"
                                    name="escola"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($aluno['escola_nome'] ?? $_SESSION['escola_nome'] ?? 'Escola vinculada ao usuário'); ?>"
                                    readonly
                                >
                            </div>

                            <!-- ================================================= -->
                            <!-- EDUCAÇÃO ESPECIAL -->
                            <!-- ================================================= -->
                            <div class="col-12 mt-3">
                                <h5 class="mb-3">Dados da Educação Especial</h5>
                            </div>

                            <!-- DEFICIÊNCIA -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Deficiência</label>
                                <input
                                    type="text"
                                    name="deficiencia"
                                    class="form-control"
                                    placeholder="Ex.: TEA - Transtorno do Espectro Autista"
                                    value="<?php echo htmlspecialchars($aluno['descricao_deficiencia'] ?? ''); ?>"
                                    required
                                >
                            </div>

                            <!-- ================================================= -->
                            <!-- RESPONSÁVEL -->
                            <!-- ================================================= -->
                            <div class="col-12 mt-3">
                                <h5 class="mb-3">Responsável</h5>
                            </div>

                            <!-- NOME RESPONSÁVEL -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do Responsável</label>
                                <input
                                    type="text"
                                    name="nome_responsavel"
                                    class="form-control"
                                    placeholder="Nome completo do responsável"
                                    value="<?php echo htmlspecialchars($aluno['nome_responsavel'] ?? ''); ?>"
                                >
                            </div>

                            <!-- CPF RESPONSÁVEL -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF do Responsável</label>
                                <input
                                    type="text"
                                    name="cpf_responsavel"
                                    id="cpf_responsavel"
                                    class="form-control"
                                    maxlength="14"
                                    placeholder="000.000.000-00"
                                    value="<?php echo htmlspecialchars(formatarCPF($aluno['cpf_responsavel'] ?? '')); ?>"
                                >
                            </div>

                            <!-- ================================================= -->
                            <!-- DOCUMENTOS -->
                            <!-- ================================================= -->
                            <div class="col-12 mt-3">
                                <h5 class="mb-3">Documentação</h5>
                            </div>

                            <!-- FOTO -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto do Aluno</label>
                                <?php if (!empty($aluno['foto_arquivo'])): ?>
                                    <div class="mb-2">
                                        <img src="../<?php echo htmlspecialchars($aluno['foto_arquivo']); ?>" alt="Foto atual" style="max-height: 80px;" class="rounded border p-1">
                                        <span class="text-muted small ms-2">Foto atual</span>
                                    </div>
                                <?php endif; ?>
                                <input
                                    type="file"
                                    name="foto"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png"
                                >
                                <small class="text-muted">
                                    Formatos: JPG, JPEG ou PNG. Envie apenas se desejar substituir a foto atual.
                                </small>
                            </div>

                            <!-- TERMO DE RESPONSABILIDADE -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Termo de Responsabilidade</label>
                                <?php if (!empty($aluno['termo_responsabilidade_arquivo'])): ?>
                                    <div class="mb-2">
                                        <a href="../<?php echo htmlspecialchars($aluno['termo_responsabilidade_arquivo']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            📄 Ver termo atual
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <input
                                    type="file"
                                    name="termo_responsabilidade"
                                    class="form-control"
                                    accept=".pdf,.jpg,.jpeg"
                                >
                                <small class="text-muted">
                                    Formatos: PDF, JPG ou JPEG. Envie apenas se desejar substituir o termo atual.
                                </small>
                            </div>

                            <!-- ================================================= -->
                            <!-- OBSERVAÇÕES -->
                            <!-- ================================================= -->
                            <div class="col-md-12 mb-4">
                                <label class="form-label">Observações / Cuidados Necessários</label>
                                <textarea
                                    name="observacoes"
                                    rows="4"
                                    class="form-control"
                                    placeholder="Informe cuidados, necessidades de acompanhamento, adaptações etc."
                                ><?php echo htmlspecialchars($aluno['descricao_cuidados'] ?? ''); ?></textarea>
                            </div>

                        </div>

                        <!-- ================================================= -->
                        <!-- BOTÕES -->
                        <!-- ================================================= -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Salvar Alterações</button>
                            <a href="alunos_visualizar.php?id=<?php echo $aluno['id_aluno']; ?>" class="btn btn-secondary">Voltar</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MÁSCARA CPF -->
<!-- ========================================================= -->
<script>
function aplicarMascaraCPF(input) {
    if (!input) return;
    input.addEventListener('input', function () {
        let value = input.value.replace(/\D/g, '');
        value = value.substring(0, 11);
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    });
}

aplicarMascaraCPF(document.getElementById('cpf_responsavel'));
</script>

</body>
</html>
