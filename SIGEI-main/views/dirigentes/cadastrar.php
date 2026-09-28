<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

// Lista de UREs para consulta rápida via JS ou fallback
$sqlUres = "SELECT id_ure, nome, uge FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$ures = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Dirigente Regional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
    <style>
        .uge-result-card {
            display: none;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 8px;
        }
        .uge-result-card.active {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body class="page-dirigentes-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Coordenador Dirigente Regional</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/dirigentes/salvar.php" method="POST" id="formDirigente">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        <input type="hidden" name="id_ure" id="id_ure_input" value="" required>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados Pessoais</h5>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" placeholder="Ex.: Roberto Alves" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" name="cpf" id="cpf" class="form-control font-monospace" maxlength="14" placeholder="000.000.000-00" required>
                            </div>

                            <!-- Seleção Prática de URE por Código UGE -->
                            <div class="col-12 mt-2">
                                <h5 class="mb-2">Vincular Unidade Regional de Ensino (URE)</h5>
                                <p class="text-muted small mb-3">Digite o código <strong>UGE</strong> da regional ou selecione na lista:</p>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Digite o Código UGE <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" id="campoUgeBusca" class="form-control font-monospace" placeholder="Ex.: 080300" maxlength="20" autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="btnBuscarUge">Localizar</button>
                                </div>
                            </div>

                            <div class="col-md-7 mb-3">
                                <label class="form-label">Ou selecione na lista:</label>
                                <select id="selectUreFallback" class="form-select">
                                    <option value="">-- Selecione uma URE --</option>
                                    <?php foreach ($ures as $u): ?>
                                        <option value="<?php echo $u['id_ure']; ?>" data-uge="<?php echo htmlspecialchars($u['uge'] ?? ''); ?>" data-nome="<?php echo htmlspecialchars($u['nome']); ?>">
                                            <?php echo ($u['uge'] ? '[' . $u['uge'] . '] ' : '') . htmlspecialchars($u['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <div id="cardUreConfirmada" class="uge-result-card">
                                    <div>
                                        <div class="small text-muted">URE Selecionada:</div>
                                        <strong id="nomeUreConfirmada">-</strong>
                                        <div class="small text-primary font-monospace" id="codigoUgeConfirmada">-</div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnDesvincularUre">Alterar</button>
                                    </div>
                                </div>
                                <div id="alertaUgeNaoEncontrada" class="alert alert-warning py-2 small mt-2 d-none">
                                    Nenhuma URE encontrada com o código UGE digitado.
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Contatos Institucionais</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail Institucional</label>
                                <input type="email" name="email" class="form-control" placeholder="dirigente@educacao.sp.gov.br">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" placeholder="(11) 4034-0001">
                            </div>

                            <div class="col-12 mt-2">
                                <h5 class="mb-3">Credenciais de Acesso</h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha de Acesso <span class="text-danger">*</span></label>
                                <input type="password" name="senha" id="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="Repita a senha" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-dark" id="btnSalvarDirigente">Cadastrar Dirigente</button>
                            <a href="listar.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listaUres = <?php echo json_encode($ures); ?>;
    const inputIdUre = document.getElementById('id_ure_input');
    const campoUge = document.getElementById('campoUgeBusca');
    const btnBuscar = document.getElementById('btnBuscarUge');
    const selectUre = document.getElementById('selectUreFallback');
    const cardConfirmada = document.getElementById('cardUreConfirmada');
    const nomeUreText = document.getElementById('nomeUreConfirmada');
    const codigoUgeText = document.getElementById('codigoUgeConfirmada');
    const btnDesvincular = document.getElementById('btnDesvincularUre');
    const alertaNaoEncontrada = document.getElementById('alertaUgeNaoEncontrada');

    function selecionarUre(id, nome, uge) {
        inputIdUre.value = id;
        nomeUreText.textContent = nome;
        codigoUgeText.textContent = 'Código UGE: ' + (uge || 'Não informado');
        cardConfirmada.classList.add('active');
        alertaNaoEncontrada.classList.add('d-none');
        campoUge.value = uge || '';
        selectUre.value = id;
    }

    function desmarcarUre() {
        inputIdUre.value = '';
        cardConfirmada.classList.remove('active');
        campoUge.value = '';
        selectUre.value = '';
        campoUge.focus();
    }

    function buscarPorUge() {
        const query = campoUge.value.trim().toLowerCase();
        if (!query) return;

        const encontrada = listaUres.find(u => (u.uge && u.uge.toLowerCase() === query) || (u.uge && u.uge.toLowerCase().includes(query)));
        if (encontrada) {
            selecionarUre(encontrada.id_ure, encontrada.nome, encontrada.uge);
        } else {
            alertaNaoEncontrada.classList.remove('d-none');
            cardConfirmada.classList.remove('active');
            inputIdUre.value = '';
        }
    }

    campoUge.addEventListener('input', function() {
        if (this.value.trim().length >= 3) {
            buscarPorUge();
        }
    });

    btnBuscar.addEventListener('click', buscarPorUge);

    selectUre.addEventListener('change', function() {
        const id = this.value;
        if (id) {
            const opt = this.options[this.selectedIndex];
            selecionarUre(id, opt.getAttribute('data-nome'), opt.getAttribute('data-uge'));
        } else {
            desmarcarUre();
        }
    });

    btnDesvincular.addEventListener('click', desmarcarUre);

    document.getElementById('formDirigente').addEventListener('submit', function(e) {
        if (!inputIdUre.value) {
            e.preventDefault();
            alert('Por favor, selecione ou busque a URE correspondente antes de salvar.');
            campoUge.focus();
        }
    });

    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            v = v.replace(/^(\d{3})(\d)/, '$1.$2');
            v = v.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1-$2');
            e.target.value = v;
        });
    }
});
</script>

</body>
</html>