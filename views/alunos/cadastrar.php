<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$escolaNome = $_SESSION['escola_nome'] ?? 'Escola vinculada ao usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Solicitação — Educação Especial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
</head>
<body class="page-alunos-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Nova Solicitação de Apoio Escolar</h2>
                </div>
                <a href="pendentes.php" class="btn btn-secondary btn-sm">Voltar</a>
            </div>

            <form action="../../controllers/alunos/salvar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">

                <!-- 1. Dados do Aluno -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Dados Pessoais do Aluno</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" maxlength="150" placeholder="Nome completo do estudante" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" placeholder="000.000.000-00" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">RA</label>
                                <input type="text" name="ra" class="form-control" maxlength="30" placeholder="Registro do aluno">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Data de Nascimento <span class="text-danger">*</span></label>
                                <input type="date" name="data_nascimento" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Gênero <span class="text-danger">*</span></label>
                                <select name="genero" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="FEMININO">Feminino</option>
                                    <option value="MASCULINO">Masculino</option>
                                    <option value="NAO_BINARIO">Não binário</option>
                                    <option value="OUTRO">Outro</option>
                                    <option value="NAO_INFORMADO">Prefiro não informar</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Raça / Cor <span class="text-danger">*</span></label>
                                <select name="raca" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="BRANCA">Branca</option>
                                    <option value="PRETA">Preta</option>
                                    <option value="PARDA">Parda</option>
                                    <option value="AMARELA">Amarela</option>
                                    <option value="INDIGENA">Indígena</option>
                                    <option value="NAO_INFORMADO">Não informado</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Município de Nascimento <span class="text-danger">*</span></label>
                                <input type="text" name="municipio_nascimento" class="form-control" maxlength="100" placeholder="Ex.: Bragança Paulista" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Informações Escolares e Inclusão -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Informações Escolares e Inclusão</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Escola Estadual</label>
                                <input type="text" name="escola" class="form-control bg-light" value="<?php echo htmlspecialchars($escolaNome); ?>" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Série <span class="text-danger">*</span></label>
                                <select name="serie" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="6º ANO">6º Ano</option>
                                    <option value="7º ANO">7º Ano</option>
                                    <option value="8º ANO">8º Ano</option>
                                    <option value="9º ANO">9º Ano</option>
                                    <option value="1ª SÉRIE">1ª Série</option>
                                    <option value="2ª SÉRIE">2ª Série</option>
                                    <option value="3ª SÉRIE">3ª Série</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Turno de Aula <span class="text-danger">*</span></label>
                                <select name="turno_aula" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="MANHÃ">Manhã</option>
                                    <option value="TARDE">Tarde</option>
                                    <option value="NOITE">Noite</option>
                                    <option value="INTEGRAL">Integral</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Deficiência / Diagnóstico Clínico <span class="text-danger">*</span></label>
                                <input type="text" name="deficiencia" class="form-control" maxlength="5000" placeholder="Ex.: TEA - Transtorno do Espectro Autista" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Responsável Legal -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Responsável Legal</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Responsável</label>
                                <input type="text" name="nome_responsavel" class="form-control" maxlength="150" placeholder="Nome completo do responsável">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">CPF do Responsável</label>
                                <input type="text" name="cpf_responsavel" id="cpf_responsavel" class="form-control" maxlength="14" placeholder="000.000.000-00">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Documentação e Anexos -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold text-dark border-bottom pb-2 mb-3">Documentação e Anexos</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Foto do Aluno</label>
                                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Opcional. Formatos: JPG, JPEG ou PNG.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Termo de Responsabilidade <span class="text-danger">*</span></label>
                                <input type="file" name="termo_responsabilidade" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                <small class="text-muted">Obrigatório. Formatos: PDF, JPG, PNG.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Laudos Médicos</label>
                                <input type="file" name="laudos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                <small class="text-muted">Selecione um ou mais laudos (PDF, JPG, PNG).</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Documentos Gerais (RG, Certidão, etc.)</label>
                                <input type="file" name="documentos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                <small class="text-muted">Documentos complementares do aluno.</small>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Observações / Cuidados Necessários</label>
                                <textarea name="observacoes" rows="3" class="form-control" maxlength="5000" placeholder="Informe cuidados, necessidades de acompanhamento ou adaptações específicas."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="pendentes.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                </div>

            </form>

        </div>
    </div>

</div>

<!-- Máscara de CPF -->
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
aplicarMascaraCPF(document.getElementById('cpf'));
aplicarMascaraCPF(document.getElementById('cpf_responsavel'));
</script>

</body>
</html>