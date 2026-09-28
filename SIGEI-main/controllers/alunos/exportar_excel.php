<?php
require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'] ?? 'Usuário';
$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);
$idEmpresaUsuario = (int) ($_SESSION['id_empresa'] ?? 0);

$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = trim($_GET['tp_filtro'] ?? '1'); // 0 = Igual a, 1 = Contém
$campoFiltro = trim($_GET['campo_filtro'] ?? '0'); // 0 = Todos, 1 = Nome, 2 = RA, 3 = CPF, 4 = Escola, 5 = Deficiência, 6 = Status

$where = array();

if ($busca !== '') {
    $termo = mysqli_real_escape_string($conexao, $busca);
    $cpfLimpo = preg_replace('/\D/', '', $busca);
    $isIgual = ($tipoFiltro === '0');

    if ($campoFiltro === '1') { // Nome
        $where[] = $isIgual ? "a.nome = '$termo'" : "a.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '2') { // RA
        $where[] = $isIgual ? "a.ra = '$termo'" : "a.ra LIKE '%$termo%'";
    } elseif ($campoFiltro === '3') { // CPF
        $valCpf = !empty($cpfLimpo) ? $cpfLimpo : $termo;
        $where[] = $isIgual ? "a.cpf = '$valCpf'" : "a.cpf LIKE '%$valCpf%'";
    } elseif ($campoFiltro === '4' && !in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) { // Escola
        $where[] = $isIgual ? "e.nome = '$termo'" : "e.nome LIKE '%$termo%'";
    } elseif ($campoFiltro === '5') { // Deficiência
        $where[] = $isIgual ? "a.descricao_deficiencia = '$termo'" : "a.descricao_deficiencia LIKE '%$termo%'";
    } elseif ($campoFiltro === '6' && !in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) { // Status
        $where[] = $isIgual ? "a.status_aprovacao = '$termo'" : "a.status_aprovacao LIKE '%$termo%'";
    } else { // 0 = Todos os campos
        if ($isIgual) {
            $conds = ["a.nome = '$termo'", "a.ra = '$termo'", "a.descricao_deficiencia = '$termo'"];
            if (!empty($cpfLimpo)) $conds[] = "a.cpf = '$cpfLimpo'";
            if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) $conds[] = "e.nome = '$termo'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        } else {
            $conds = ["a.nome LIKE '%$termo%'", "a.ra LIKE '%$termo%'", "a.descricao_deficiencia LIKE '%$termo%'"];
            if (!empty($cpfLimpo)) $conds[] = "a.cpf LIKE '%$cpfLimpo%'";
            if (!in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) $conds[] = "e.nome LIKE '%$termo%'";
            $where[] = "(" . implode(' OR ', $conds) . ")";
        }
    }
}

// Para empresa, não deve mostrar alunos que ainda não foram aprovados
if (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    $where[] = "a.status_aprovacao = 'APROVADO'";
}

// Filtros por Perfil e Hierarquia
if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    $where[] = "a.id_ue = $idEscola";
} elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'USUARIO_EDUCACAO_ESPECIAL', 'SEFISC', 'ASURE'])) {
    if ($idUreUsuario <= 0) {
        $idUreUsuario = idUreUsuario($conexao, $userId);
    }
    if ($idUreUsuario > 0) {
        $where[] = "e.id_ure = $idUreUsuario";
    }
} elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
    if ($idEmpresaUsuario <= 0) {
        $idEmpresaUsuario = idEmpresaSupervisor($conexao, $userId);
    }
    $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresaUsuario);
    if (!empty($uresAtendidas)) {
        $uresList = implode(',', $uresAtendidas);
        $where[] = "e.id_ure IN ($uresList)";
    } else {
        $where[] = "1=0";
    }
} elseif ($userPerfil === 'PAE') {
    $where[] = "a.id_aluno IN (SELECT id_aluno FROM associacoes WHERE id_pae = $userId AND ativo = 1)";
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
    SELECT a.*,
           TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) AS idade,
           e.nome AS escola_nome,
           e.cie AS escola_cie,
           u.nome AS ure_nome,
           (SELECT p.nome FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_nome,
           (SELECT p.cpf FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_cpf,
           (SELECT p.telefone FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_telefone,
           (SELECT emp.nome FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            JOIN empresas emp ON p.id_empresa = emp.id_empresa
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_empresa
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    LEFT JOIN unidades_regionais u ON e.id_ure = u.id_ure
    $whereSql
    ORDER BY a.nome ASC
";

$result = mysqli_query($conexao, $sql);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Nome do arquivo de download
$dataGeracao = date('Y-m-d_His');
$nomeArquivo = "relatorio_alunos_sigei_{$dataGeracao}.xls";

// Headers HTTP para forçar download em Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Envia UTF-8 BOM para garantir correta acentuação no Microsoft Excel
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #198754;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            border: 1px solid #146c43;
            padding: 8px 10px;
        }
        td {
            border: 1px solid #dee2e6;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .bg-alt {
            background-color: #f8f9fa;
        }
        .text-center {
            text-align: center;
        }
        .badge-sim {
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: bold;
            text-align: center;
        }
        .badge-nao {
            background-color: #f8d7da;
            color: #842029;
            font-weight: bold;
            text-align: center;
        }
        .titulo-relatorio {
            font-size: 14pt;
            font-weight: bold;
            color: #198754;
        }
        .texto-formato {
            mso-number-format: "\@";
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <td colspan="15" class="titulo-relatorio">SIGEI — Sistema de Gestão de Educação Inclusiva</td>
        </tr>
        <tr>
            <td colspan="15"><strong>Relatório Geral de Alunos e Alocação de PAE</strong></td>
        </tr>
        <tr>
            <td colspan="15">
                Gerado em: <?php echo date('d/m/Y H:i:s'); ?> |
                Gerado por: <?php echo htmlspecialchars($userName); ?> (<?php echo htmlspecialchars(nomePerfil($userPerfil)); ?>) |
                Total de Registros: <?php echo count($alunos); ?>
            </td>
        </tr>
        <tr>
            <td colspan="15"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="background-color: #198754; color: #fff;">#</th>
                <th style="background-color: #198754; color: #fff;">Nome do Aluno</th>
                <th style="background-color: #198754; color: #fff;">RA</th>
                <th style="background-color: #198754; color: #fff;">CPF</th>
                <th style="background-color: #198754; color: #fff;">Data Nascimento</th>
                <th style="background-color: #198754; color: #fff;">Idade</th>
                <th style="background-color: #198754; color: #fff;">Gênero</th>
                <th style="background-color: #198754; color: #fff;">Série</th>
                <th style="background-color: #198754; color: #fff;">Turno</th>
                <th style="background-color: #198754; color: #fff;">Escola</th>
                <th style="background-color: #198754; color: #fff;">CIE</th>
                <th style="background-color: #198754; color: #fff;">Diretoria Regional (URE)</th>
                <th style="background-color: #198754; color: #fff;">Deficiência / Diagnóstico</th>
                <th style="background-color: #198754; color: #fff;">Cuidados Necessários</th>
                <th style="background-color: #198754; color: #fff;">Responsável</th>
                <th style="background-color: #198754; color: #fff;">CPF Responsável</th>
                <th style="background-color: #198754; color: #fff;">Status Solicitação</th>
                <th style="background-color: #0d6efd; color: #fff; text-align: center;">Está com PAE?</th>
                <th style="background-color: #0d6efd; color: #fff;">Nome do PAE Vinculado</th>
                <th style="background-color: #0d6efd; color: #fff;">Telefone do PAE</th>
                <th style="background-color: #0d6efd; color: #fff;">Empresa Prestadora</th>
                <th style="background-color: #198754; color: #fff;">Data Cadastro</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($alunos) > 0): ?>
                <?php foreach ($alunos as $idx => $aluno): ?>
                    <?php
                    $temPae = !empty($aluno['pae_nome']);
                    $dtNasc = !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-';
                    $dtCad = !empty($aluno['data_cadastro']) ? date('d/m/Y H:i', strtotime($aluno['data_cadastro'])) : '-';
                    $bgClass = ($idx % 2 === 1) ? 'bg-alt' : '';
                    ?>
                    <tr class="<?php echo $bgClass; ?>">
                        <td class="text-center"><?php echo $idx + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                        <td class="texto-formato"><?php echo htmlspecialchars($aluno['ra'] ?? '-'); ?></td>
                        <td class="texto-formato"><?php echo htmlspecialchars(formatarCPF($aluno['cpf']) ?: '-'); ?></td>
                        <td class="text-center"><?php echo $dtNasc; ?></td>
                        <td class="text-center"><?php echo $aluno['idade'] !== null ? $aluno['idade'] . ' anos' : '-'; ?></td>
                        <td><?php echo htmlspecialchars($aluno['genero'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['serie'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['turno_aula'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                        <td class="texto-formato"><?php echo htmlspecialchars($aluno['escola_cie'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['ure_nome'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['descricao_deficiencia'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['descricao_cuidados'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($aluno['nome_responsavel'] ?? '-'); ?></td>
                        <td class="texto-formato"><?php echo htmlspecialchars(formatarCPF($aluno['cpf_responsavel']) ?: '-'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($aluno['status_aprovacao'] ?? '-'); ?></td>
                        
                        <!-- Coluna PAE -->
                        <?php if ($temPae): ?>
                            <td class="badge-sim">SIM</td>
                            <td><strong><?php echo htmlspecialchars($aluno['pae_nome']); ?></strong></td>
                            <td class="texto-formato"><?php echo htmlspecialchars(formatarTelefone($aluno['pae_telefone']) ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($aluno['pae_empresa'] ?? '-'); ?></td>
                        <?php else: ?>
                            <td class="badge-nao">NÃO</td>
                            <td style="color: #6c757d;">Sem PAE vinculado</td>
                            <td>-</td>
                            <td>-</td>
                        <?php endif; ?>

                        <td class="text-center"><?php echo $dtCad; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="22" style="text-align: center; color: #6c757d; padding: 20px;">
                        Nenhum aluno encontrado para os critérios selecionados.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
