<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC'));

$userName = $_SESSION['user_name'];

$sqlUres = "SELECT * FROM unidades_regionais ORDER BY nome ASC";
$resultUres = mysqli_query($conexao, $sqlUres);
$todasUres = $resultUres ? mysqli_fetch_all($resultUres, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Empresa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
    <style>
        .ure-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background-color: #edf5ff;
            color: #0d47a1;
            border: 1px solid #c8d9ef;
            border-radius: 50rem;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .ure-tag-remove {
            cursor: pointer;
            color: #0d47a1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            font-weight: bold;
        }
        .ure-tag-remove:hover {
            color: #d32f2f;
        }
    </style>
</head>
<body class="page-empresas-cadastrar">

<?php require("../../includes/navbar.php"); ?>

<div class="content">

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-form">
                <div class="card-body p-4">
                    <h2 class="mb-4">Cadastrar Empresa Prestadora de Serviços</h2>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($_GET['erro']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/empresas/salvar.php" method="POST" enctype="multipart/form-data" id="formEmpresa">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(gerarTokenCSRF()); ?>">
                        
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Dados da Empresa e Contrato</h5>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">Razão Social / Nome Fantasia <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" placeholder="Ex.: Apoio Inclusivo Serviços Educacionais Ltda." required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">CNPJ <span class="text-danger">*</span></label>
                                <input type="text" name="cnpj" id="cnpj" class="form-control" maxlength="18" placeholder="00.000.000/0000-00" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Número do Contrato / Edital</label>
                                <input type="text" name="numero_contrato" class="form-control" placeholder="Ex.: CTR-001/2026">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Início do Contrato</label>
                                <input type="date" name="data_inicio_contrato" class="form-control">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fim do Contrato (Vigência)</label>
                                <input type="date" name="data_fim_contrato" class="form-control">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Arquivo do Contrato (PDF, JPG ou PNG)</label>
                                <input type="file" name="contrato_arquivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Anexe o termo de contrato assinado (máx. 5MB).</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" placeholder="(11) 4034-2001">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="contato@empresa.com.br">
                            </div>

                            <div class="col-12 mt-3">
                                <h5 class="mb-3">Endereço da Sede</h5>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="form-label">Logradouro / Rua</label>
                                <input type="text" name="endereco" class="form-control" placeholder="Ex.: Rua Comercial">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" placeholder="100">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" placeholder="Centro">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">CEP</label>
                                <input type="text" name="cep" class="form-control" maxlength="9" placeholder="00000-000">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="municipio" class="form-control" placeholder="Ex.: Bragança Paulista">
                            </div>

                            <div class="col-12 mt-3">
                                <h5 class="mb-2">UREs Atendidas pelo Contrato</h5>
                                <p class="text-muted small mb-3">Selecione as Unidades Regionais atendidas por esta empresa:</p>
                                
                                <div class="p-3 border rounded bg-light mb-3">
                                    <div class="mb-3">
                                        <input type="text" id="filtroUre" class="form-control form-control-sm" placeholder="Pesquisar URE pelo nome ou código UGE...">
                                    </div>

                                    <div class="mb-3">
                                        <div class="fw-bold small text-muted mb-2">UREs Selecionadas:</div>
                                        <div id="containerTagsUre" class="d-flex flex-wrap gap-2">
                                            <span class="text-muted small" id="nenhumaUreSelecionada">Nenhuma URE selecionada. Clique nas opções abaixo.</span>
                                        </div>
                                    </div>

                                    <div class="fw-bold small text-muted mb-2">Clique para selecionar / desmarcar:</div>
                                    <div id="listaOpcoesUre" class="row g-2" style="max-height: 180px; overflow-y: auto;">
                                        <?php foreach ($todasUres as $ure): ?>
                                            <div class="col-md-6 item-ure-opcao" data-nome="<?php echo strtolower($ure['nome']); ?>" data-uge="<?php echo strtolower($ure['uge'] ?? ''); ?>">
                                                <div class="card p-2 border bg-white h-100 d-flex flex-row justify-content-between align-items-center card-ure-selectable"
                                                     style="cursor: pointer;"
                                                     data-id="<?php echo $ure['id_ure']; ?>"
                                                     data-nome="<?php echo htmlspecialchars($ure['nome']); ?>"
                                                     data-uge="<?php echo htmlspecialchars($ure['uge'] ?? ''); ?>">
                                                    <div>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($ure['uge'] ?: 'S/ UGE'); ?></span>
                                                        <span class="small fw-bold ms-1"><?php echo htmlspecialchars($ure['nome']); ?></span>
                                                    </div>
                                                    <span class="check-indicator small text-muted">○</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div id="inputsOcultosUres"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark">Cadastrar Empresa</button>
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
    const selectedUres = new Map();
    const containerTags = document.getElementById('containerTagsUre');
    const inputsOcultos = document.getElementById('inputsOcultosUres');
    const msgNenhuma = document.getElementById('nenhumaUreSelecionada');
    const filtroInput = document.getElementById('filtroUre');

    function renderTags() {
        containerTags.innerHTML = '';
        inputsOcultos.innerHTML = '';

        if (selectedUres.size === 0) {
            containerTags.appendChild(msgNenhuma);
        } else {
            selectedUres.forEach(item => {
                const tag = document.createElement('div');
                tag.className = 'ure-tag';
                tag.innerHTML = `
                    <span><strong>[${item.uge || 'URE'}]</strong> ${item.nome}</span>
                    <span class="ure-tag-remove" data-id="${item.id}" title="Remover">&times;</span>
                `;
                containerTags.appendChild(tag);

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'ures[]';
                hiddenInput.value = item.id;
                inputsOcultos.appendChild(hiddenInput);
            });
        }

        document.querySelectorAll('.card-ure-selectable').forEach(card => {
            const id = card.getAttribute('data-id');
            const indicator = card.querySelector('.check-indicator');
            if (selectedUres.has(id)) {
                card.style.backgroundColor = '#edf5ff';
                card.style.borderColor = '#0d47a1';
                indicator.textContent = '●';
                indicator.className = 'check-indicator small text-primary fw-bold';
            } else {
                card.style.backgroundColor = '#fff';
                card.style.borderColor = '';
                indicator.textContent = '○';
                indicator.className = 'check-indicator small text-muted';
            }
        });
    }

    document.querySelectorAll('.card-ure-selectable').forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const uge = this.getAttribute('data-uge');

            if (selectedUres.has(id)) {
                selectedUres.delete(id);
            } else {
                selectedUres.set(id, { id, nome, uge });
            }
            renderTags();
        });
    });

    containerTags.addEventListener('click', function(e) {
        if (e.target.classList.contains('ure-tag-remove')) {
            const id = e.target.getAttribute('data-id');
            selectedUres.delete(id);
            renderTags();
        }
    });

    filtroInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.item-ure-opcao').forEach(item => {
            const nome = item.getAttribute('data-nome');
            const uge = item.getAttribute('data-uge');
            if (nome.includes(query) || uge.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 14) v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = v;
        });
    }
});
</script>

</body>
</html>