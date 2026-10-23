<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'] ?? 'Usuário';
$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$id_aluno = (int) ($_GET['id'] ?? 0);

if ($id_aluno <= 0) {
    redirecionarAcessoNegado();
}

// Busca os dados completos do aluno, escola e quem cadastrou
$sqlAluno = "
    SELECT a.*, ue.nome AS escola_nome, ue.cie AS escola_cie, ue.id_ure, ure.nome AS ure_nome,
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
} elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'USUARIO_EDUCACAO_ESPECIAL'])) {
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
$pae = $resultPae ? mysqli_fetch_assoc($resultPae) : null;

$historicoEventos = obterHistoricoSolicitacaoAluno($conexao, $aluno);

$idadeAluno = null;
if (!empty($aluno['data_nascimento'])) {
    $nasc = new DateTime($aluno['data_nascimento']);
    $hoje = new DateTime();
    $idadeAluno = $nasc->diff($hoje)->y;
}

$cpf = formatarCPF($aluno['cpf']);
$cpfResponsavel = formatarCPF($aluno['cpf_responsavel'] ?? '');

$statusMap = [
    'PENDENTE' => 'Aguardando Análise',
    'PENDENTE_CORRECAO' => 'Ajuste Solicitado',
    'APROVADO' => 'Atendimento Aprovado',
    'REPROVADO' => 'Solicitação Recusada',
    'ARQUIVADO' => 'Aluno Arquivado'
];
$statusTexto = $statusMap[$aluno['status_aprovacao']] ?? ($aluno['status_aprovacao'] ?? '-');
$nomeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(trim($aluno['nome'])));
$nomeArquivoPdf = "ficha_aluno_" . ($nomeSlug ?: $id_aluno) . ".pdf";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ficha do Aluno — <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            background: #e2e8f0;
            display: flex;
            justify-content: center;
        }
        #folha-ficha {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 14mm 15mm;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.12);
            border-radius: 4px;
            transition: width 0.2s ease, min-height 0.2s ease;
        }
        #folha-ficha.formato-a4-landscape {
            width: 297mm;
            min-height: 210mm;
        }
        #folha-ficha.formato-letter-portrait {
            width: 216mm;
            min-height: 279mm;
        }
        #folha-ficha.formato-letter-landscape {
            width: 279mm;
            min-height: 216mm;
        }
        .header-ficha {
            border-bottom: 2px solid #0038bb;
            padding-bottom: 10px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .header-ficha h1 {
            margin: 0;
            font-size: 14.5pt;
            color: #0038bb;
        }
        .header-ficha p {
            margin: 3px 0 0 0;
            font-size: 9pt;
            color: #475569;
        }
        .secao-titulo {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0038bb;
            background: #f1f5f9;
            padding: 5px 10px;
            border-left: 4px solid #0038bb;
            margin: 14px 0 8px 0;
        }
        table.grid-dados {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.grid-dados th,
        table.grid-dados td {
            border: 1px solid #cbd5e1;
            padding: 6px 9px;
            text-align: left;
            vertical-align: top;
            font-size: 9.5pt;
        }
        table.grid-dados th {
            background: #f8fafc;
            font-weight: bold;
            color: #334155;
            width: 24%;
        }
        .box-texto {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
            font-size: 9.5pt;
            background: #ffffff;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .historico-item {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
            margin-bottom: 6px;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        .historico-topo {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 3px;
            color: #0f172a;
        }
        @media print {
            body {
                padding: 0;
                background: #ffffff;
                display: block;
            }
            #folha-ficha {
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
    <style id="page-print-style">
        @page { size: A4 portrait; margin: 12mm; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

    <div id="folha-ficha" data-filename="<?php echo htmlspecialchars($nomeArquivoPdf); ?>">
        <div class="header-ficha">
            <div>
                <h1>SIGEI — Ficha Cadastral do Aluno</h1>
                <p>Sistema de Gestão de Educação Inclusiva</p>
            </div>
            <div style="text-align: right; font-size: 9pt; color: #475569;">
                <div>Emitido em: <?php echo date('d/m/Y'); ?></div>
                <div>Situação: <strong><?php echo htmlspecialchars($statusTexto); ?></strong></div>
            </div>
        </div>

        <div class="secao-titulo">1. Dados Pessoais do Aluno</div>
        <table class="grid-dados">
            <tr>
                <th>Nome Completo</th>
                <td colspan="3"><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
            </tr>
            <tr>
                <th>RA</th>
                <td><?php echo htmlspecialchars($aluno['ra'] ?: 'Não informado'); ?></td>
                <th>CPF</th>
                <td><?php echo htmlspecialchars($cpf ?: 'Não informado'); ?></td>
            </tr>
            <tr>
                <th>Data de Nascimento</th>
                <td>
                    <?php if (!empty($aluno['data_nascimento'])): ?>
                        <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?><?php echo ($idadeAluno !== null) ? ' (' . $idadeAluno . ' anos)' : ''; ?>
                    <?php else: ?>
                        Não informada
                    <?php endif; ?>
                </td>
                <th>Município de Nascimento</th>
                <td><?php echo htmlspecialchars($aluno['municipio_nascimento'] ?: 'Não informado'); ?></td>
            </tr>
            <tr>
                <th>Gênero</th>
                <td><?php echo htmlspecialchars($aluno['genero'] ?: 'Não informado'); ?></td>
                <th>Raça / Cor</th>
                <td><?php echo htmlspecialchars($aluno['raca'] ?: 'Não informada'); ?></td>
            </tr>
        </table>

        <div class="secao-titulo">2. Responsável Legal</div>
        <table class="grid-dados">
            <tr>
                <th>Nome do Responsável</th>
                <td><?php echo htmlspecialchars($aluno['nome_responsavel'] ?: 'Não informado'); ?></td>
                <th>CPF do Responsável</th>
                <td><?php echo htmlspecialchars($cpfResponsavel ?: 'Não informado'); ?></td>
            </tr>
        </table>

        <div class="secao-titulo">3. Dados Escolares e Necessidades</div>
        <table class="grid-dados">
            <tr>
                <th>Unidade Escolar</th>
                <td><?php echo htmlspecialchars($aluno['escola_nome'] ?: 'Não informada'); ?></td>
                <th>Unidade Regional (URE)</th>
                <td><?php echo htmlspecialchars($aluno['ure_nome'] ?: 'Não informada'); ?></td>
            </tr>
            <tr>
                <th>Série</th>
                <td><?php echo htmlspecialchars($aluno['serie'] ?: 'Não informada'); ?></td>
                <th>Turno de Aula</th>
                <td><?php echo htmlspecialchars($aluno['turno_aula'] ?: 'Não informado'); ?></td>
            </tr>
        </table>

        <div style="font-weight: bold; font-size: 9.5pt; margin-bottom: 3px;">Diagnóstico Clínico:</div>
        <div class="box-texto">
            <?php echo nl2br(htmlspecialchars($aluno['descricao_deficiencia'] ?: 'Nenhuma descrição clínica informada.')); ?>
        </div>

        <div style="font-weight: bold; font-size: 9.5pt; margin-bottom: 3px;">Cuidados Necessários e Apoio Solicitado:</div>
        <div class="box-texto">
            <?php echo nl2br(htmlspecialchars($aluno['descricao_cuidados'] ?: 'Nenhum cuidado especial registrado pela escola.')); ?>
        </div>

        <?php if ($aluno['status_aprovacao'] === 'APROVADO'): ?>
            <div class="secao-titulo">4. Profissional de Apoio Escolar (PAE)</div>
            <table class="grid-dados">
                <?php if ($pae): ?>
                    <tr>
                        <th>Profissional Vinculado</th>
                        <td><?php echo htmlspecialchars($pae['nome']); ?></td>
                        <th>CPF do Profissional</th>
                        <td><?php echo htmlspecialchars(formatarCPF($pae['cpf'])); ?></td>
                    </tr>
                    <tr>
                        <th>Início do Atendimento</th>
                        <td colspan="3"><?php echo !empty($pae['data_inicio']) ? date('d/m/Y', strtotime($pae['data_inicio'])) : 'Não informado'; ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhum profissional de apoio vinculado a este aluno no momento.</td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>

        <div class="secao-titulo">Histórico da Solicitação</div>
        <?php foreach ($historicoEventos as $ev): ?>
            <div class="historico-item">
                <div class="historico-topo">
                    <span><?php echo htmlspecialchars($ev['titulo']); ?></span>
                    <span><?php echo htmlspecialchars($ev['data']); ?></span>
                </div>
                <div>Nome: <strong><?php echo htmlspecialchars($ev['nome']); ?></strong> | CPF: <?php echo htmlspecialchars($ev['cpf']); ?></div>
                <?php if (!empty($ev['pae_nome'])): ?>
                    <div style="margin-top: 3px;">PAE: <strong><?php echo htmlspecialchars($ev['pae_nome']); ?></strong> | CPF do PAE: <?php echo htmlspecialchars($ev['pae_cpf'] ?: 'Não informado'); ?></div>
                <?php endif; ?>
                <?php if (!empty($ev['motivo'])): ?>
                    <div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #cbd5e1;">
                        <strong><?php echo htmlspecialchars($ev['motivo_label'] ?: 'Motivo:'); ?></strong>
                        <?php echo nl2br(htmlspecialchars($ev['motivo'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        window.configurarFormatoFicha = function(formato, orientacao) {
            const folha = document.getElementById('folha-ficha');
            const styleEl = document.getElementById('page-print-style');
            if (!folha) return;

            folha.className = '';
            const fmt = (formato || 'a4').toLowerCase();
            const ori = (orientacao || 'portrait').toLowerCase();

            if (fmt === 'a4' && ori === 'landscape') {
                folha.classList.add('formato-a4-landscape');
            } else if (fmt === 'letter' && ori === 'portrait') {
                folha.classList.add('formato-letter-portrait');
            } else if (fmt === 'letter' && ori === 'landscape') {
                folha.classList.add('formato-letter-landscape');
            }

            if (styleEl) {
                const cssSize = (fmt === 'letter' ? 'letter' : 'A4') + ' ' + (ori === 'landscape' ? 'landscape' : 'portrait');
                styleEl.innerHTML = '@page { size: ' + cssSize + '; margin: 12mm; }';
            }
        };

        window.baixarFichaPDF = function(formato, orientacao) {
            const folha = document.getElementById('folha-ficha');
            if (!folha) return Promise.resolve();

            window.configurarFormatoFicha(formato, orientacao);
            const nomeArquivo = folha.getAttribute('data-filename') || 'ficha_aluno.pdf';
            const fmt = (formato || 'a4').toLowerCase();
            const ori = (orientacao || 'portrait').toLowerCase();

            if (typeof html2pdf !== 'undefined') {
                const opt = {
                    margin:       8,
                    filename:     nomeArquivo,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
                    jsPDF:        { unit: 'mm', format: fmt, orientation: ori }
                };
                return html2pdf().set(opt).from(folha).save();
            } else {
                window.print();
                return Promise.resolve();
            }
        };

        window.imprimirFichaDireto = function(formato, orientacao) {
            window.configurarFormatoFicha(formato, orientacao);
            window.focus();
            window.print();
        };
    </script>
</body>
</html>
