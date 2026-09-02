<?php
require_once "../../config/init.php";

exigirPerfil(array('USUARIO_ESCOLA'));

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Enviar Solicitação</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="page-alunos-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card card-form">

                <div class="card-body p-4">

                    <h2 class="mb-2">
                        Enviar Solicitação para Educação Especial
                    </h2>

                    <p class="text-muted mb-4">
                        Olá,
                        <?php echo htmlspecialchars($userName); ?>.
                        Preencha os dados do aluno. O status será PENDENTE.
                    </p>


                    <!-- ================================================= -->
                    <!-- FORMULÁRIO -->
                    <!-- ================================================= -->

                    <form
                        action="../../controllers/alunos/salvar.php"
                        method="POST"
                        enctype="multipart/form-data"
                    >

                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">

                        <div class="row">


                            <!-- ================================================= -->
                            <!-- DADOS DO ALUNO -->
                            <!-- ================================================= -->

                            <div class="col-12">
                                <h5 class="mb-3">
                                    Dados do Aluno
                                </h5>
                            </div>


                            <!-- NOME -->

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Nome Completo
                                </label>

                                <input
                                    type="text"
                                    name="nome"
                                    class="form-control"
                                    placeholder="Digite o nome completo do aluno"
                                    required
                                >

                            </div>


                            <!-- CPF -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    CPF
                                </label>

                                <input
                                    type="text"
                                    name="cpf"
                                    id="cpf"
                                    class="form-control"
                                    maxlength="14"
                                    placeholder="000.000.000-00"
                                    required
                                >

                            </div>


                            <!-- RA -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    RA
                                </label>

                                <input
                                    type="text"
                                    name="ra"
                                    class="form-control"
                                    placeholder="Registro do aluno"
                                >

                            </div>


                            <!-- DATA DE NASCIMENTO -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Data de Nascimento
                                </label>

                                <input
                                    type="date"
                                    name="data_nascimento"
                                    class="form-control"
                                    required
                                >

                            </div>


                            <!-- GÊNERO -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Gênero
                                </label>

                                <select
                                    name="genero"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="FEMININO">
                                        Feminino
                                    </option>

                                    <option value="MASCULINO">
                                        Masculino
                                    </option>

                                    <option value="NAO_BINARIO">
                                        Não binário
                                    </option>

                                    <option value="OUTRO">
                                        Outro
                                    </option>

                                    <option value="NAO_INFORMADO">
                                        Prefiro não informar
                                    </option>

                                </select>

                            </div>


                            <!-- RAÇA/COR -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Raça/Cor
                                </label>

                                <select
                                    name="raca"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="BRANCA">
                                        Branca
                                    </option>

                                    <option value="PRETA">
                                        Preta
                                    </option>

                                    <option value="PARDA">
                                        Parda
                                    </option>

                                    <option value="AMARELA">
                                        Amarela
                                    </option>

                                    <option value="INDIGENA">
                                        Indígena
                                    </option>

                                    <option value="NAO_INFORMADO">
                                        Não informado
                                    </option>

                                </select>

                            </div>


                            <!-- MUNICÍPIO -->

                            <div class="col-md-8 mb-3">

                                <label class="form-label">
                                    Município de Nascimento
                                </label>

                                <input
                                    type="text"
                                    name="municipio_nascimento"
                                    class="form-control"
                                    placeholder="Ex.: Bragança Paulista"
                                    required
                                >

                            </div>


                            <!-- SÉRIE -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Série
                                </label>

                                <select
                                    name="serie"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="6º ANO">
                                        6º Ano
                                    </option>

                                    <option value="7º ANO">
                                        7º Ano
                                    </option>

                                    <option value="8º ANO">
                                        8º Ano
                                    </option>

                                    <option value="9º ANO">
                                        9º Ano
                                    </option>

                                    <option value="1ª SÉRIE">
                                        1ª Série
                                    </option>

                                    <option value="2ª SÉRIE">
                                        2ª Série
                                    </option>

                                    <option value="3ª SÉRIE">
                                        3ª Série
                                    </option>

                                </select>

                            </div>


                            <!-- ================================================= -->
                            <!-- DADOS ESCOLARES -->
                            <!-- ================================================= -->

                            <div class="col-12 mt-3">

                                <h5 class="mb-3">
                                    Dados Escolares
                                </h5>

                            </div>


                            <!-- TURNO -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Turno de Aula
                                </label>

                                <select
                                    name="turno_aula"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="MANHÃ">
                                        Manhã
                                    </option>

                                    <option value="TARDE">
                                        Tarde
                                    </option>

                                    <option value="NOITE">
                                        Noite
                                    </option>

                                    <option value="INTEGRAL">
                                        Integral
                                    </option>

                                </select>

                            </div>


                            <!-- ESCOLA -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Escola Estadual
                                </label>

                                <input
                                    type="text"
                                    name="escola"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars(
                                        $_SESSION['escola_nome'] ?? 'Escola vinculada ao usuário'
                                    ); ?>"
                                    readonly
                                >

                            </div>


                            <!-- ================================================= -->
                            <!-- EDUCAÇÃO ESPECIAL -->
                            <!-- ================================================= -->

                            <div class="col-12 mt-3">

                                <h5 class="mb-3">
                                    Dados da Educação Especial
                                </h5>

                            </div>


                            <!-- DEFICIÊNCIA -->

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Deficiência
                                </label>

                                <input
                                    type="text"
                                    name="deficiencia"
                                    class="form-control"
                                    placeholder="Ex.: TEA - Transtorno do Espectro Autista"
                                    required
                                >

                            </div>


                            <!-- ================================================= -->
                            <!-- RESPONSÁVEL -->
                            <!-- ================================================= -->

                            <div class="col-12 mt-3">

                                <h5 class="mb-3">
                                    Responsável
                                </h5>

                            </div>


                            <!-- NOME RESPONSÁVEL -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Nome do Responsável
                                </label>

                                <input
                                    type="text"
                                    name="nome_responsavel"
                                    class="form-control"
                                    placeholder="Nome completo do responsável"
                                >

                            </div>


                            <!-- CPF RESPONSÁVEL -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    CPF do Responsável
                                </label>

                                <input
                                    type="text"
                                    name="cpf_responsavel"
                                    id="cpf_responsavel"
                                    class="form-control"
                                    maxlength="14"
                                    placeholder="000.000.000-00"
                                >

                            </div>


                            <!-- ================================================= -->
                            <!-- DOCUMENTOS -->
                            <!-- ================================================= -->

                            <div class="col-12 mt-3">

                                <h5 class="mb-3">
                                    Documentação
                                </h5>

                            </div>


                            <!-- LAUDOS -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Laudos
                                </label>

                                <input
                                    type="file"
                                    name="laudos[]"
                                    class="form-control"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    multiple
                                >

                                <small class="text-muted">
                                    Formatos aceitos: PDF, JPG e PNG.
                                </small>

                            </div>


                            <!-- TERMO -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Termo de Responsabilidade
                                </label>

                                <input
                                    type="file"
                                    name="termo_responsabilidade"
                                    class="form-control"
                                    accept=".pdf,.jpg,.jpeg"
                                    required
                                >

                                <small class="text-muted">
                                    Formatos aceitos: PDF e JPG.
                                </small>

                            </div>


                            <!-- FOTO -->

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Foto do Aluno
                                </label>

                                <input
                                    type="file"
                                    name="foto"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png"
                                >

                                <small class="text-muted">
                                    Campo opcional. Formatos: JPG, JPEG ou PNG.
                                </small>

                            </div>


                            <!-- ================================================= -->
                            <!-- OBSERVAÇÕES -->
                            <!-- ================================================= -->

                            <div class="col-md-12 mb-4">

                                <label class="form-label">
                                    Observações / Cuidados Necessários
                                </label>

                                <textarea
                                    name="observacoes"
                                    rows="4"
                                    class="form-control"
                                    placeholder="Informe cuidados, necessidades de acompanhamento, adaptações etc."
                                ></textarea>

                            </div>

                        </div>


                        <!-- ================================================= -->
                        <!-- BOTÕES -->
                        <!-- ================================================= -->

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-dark"
                            >
                                Enviar solicitação
                            </button>

                            <a
                                href="listar.php"
                                class="btn btn-secondary"
                            >
                                Voltar
                            </a>

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

    if (!input) {
        return;
    }

    input.addEventListener('input', function () {

        let value = input.value.replace(/\D/g, '');

        value = value.substring(0, 11);

        value = value.replace(
            /(\d{3})(\d)/,
            '$1.$2'
        );

        value = value.replace(
            /(\d{3})(\d)/,
            '$1.$2'
        );

        value = value.replace(
            /(\d{3})(\d{1,2})$/,
            '$1-$2'
        );

        input.value = value;

    });

}


aplicarMascaraCPF(
    document.getElementById('cpf')
);

aplicarMascaraCPF(
    document.getElementById('cpf_responsavel')
);

</script>


</body>
</html>