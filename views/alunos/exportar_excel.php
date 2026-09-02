<?php
// Desativa exibição de erros na tela para não sujar o arquivo Excel
ini_set('display_errors', 0);
error_reporting(0);

require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'] ?? '';
$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

// Validação de acesso para o perfil SEFISC
if ($userPerfil !== 'USUARIO_SEFISC') {
    http_response_code(403);
    die("Acesso negado.");
}

// Filtros recebidos via URL (GET)
$nomeFiltro = trim($_GET['nome'] ?? '');
$raFiltro = trim($_GET['ra'] ?? '');
$cpfFiltro = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
$escolaFiltro = trim($_GET['escola'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');

$where = array();

if ($nomeFiltro !== '') {
    $nomeBusca = mysqli_real_escape_string($conexao, $nomeFiltro);
    $where[] = "a.nome LIKE '%$nomeBusca%'";
}

if ($raFiltro !== '') {
    $raBusca = mysqli_real_escape_string($conexao, $raFiltro);
    $where[] = "a.ra LIKE '%$raBusca%'";
}

if ($cpfFiltro !== '') {
    $cpfBusca = mysqli_real_escape_string($conexao, $cpfFiltro);
    $where[] = "a.cpf LIKE '%$cpfBusca%'";
}

if ($escolaFiltro !== '') {
    $escolaBusca = mysqli_real_escape_string($conexao, $escolaFiltro);
    $where[] = "e.nome LIKE '%$escolaBusca%'";
}

if ($statusFiltro !== '') {
    $statusBusca = mysqli_real_escape_string($conexao, $statusFiltro);
    $where[] = "a.status_aprovacao = '$statusBusca'";
}

// Filtro por URE para o perfil SEFISC
if ($idUreUsuario <= 0 && function_exists('idUreUsuario')) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}
if ($idUreUsuario > 0) {
    $where[] = "e.id_ure = $idUreUsuario";
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Consulta no banco de dados
$sql = "
    SELECT a.id_aluno, a.nome, a.cpf, a.ra, a.descricao_deficiencia,
           a.status_aprovacao, a.data_cadastro,
           e.nome AS escola_nome,
           (SELECT p.nome FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
            LIMIT 1) AS pae_nome
    FROM alunos a
    LEFT JOIN unidades_escolares e ON a.id_ue = e.id_ue
    $whereSql
    ORDER BY a.data_cadastro DESC
";

$result = mysqli_query($conexao, $sql);
$alunos = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Função auxiliar para evitar erros com valores nulos no PHP 8.1+
function tratarTexto($valor) {
    return htmlspecialchars((string) ($valor ?? ''));
}

// Configuração de cabeçalhos do Excel
$fileName = "relatorio_alunos_sefisc_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8 BOM para os caracteres acentuados
echo "\xEF\xBB\xBF";
?>
<table border="1">
    <thead>
        <tr style="background-color: #212529; color: #ffffff;">
            <th>Nome</th>
            <th>RA</th>
            <th>CPF</th>
            <th>Escola</th>
            <th>Deficiência</th>
            <th>Status</th>
            <th>PAE</th>
            <th>Data de Cadastro</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($alunos)): ?>
            <?php foreach ($alunos as $aluno): ?>
                <tr>
                    <td><?php echo tratarTexto($aluno['nome']); ?></td>
                    <td><?php echo tratarTexto($aluno['ra']); ?></td>
                    <td>
                        <?php 
                            $cpf = $aluno['cpf'] ?? '';
                            if (function_exists('formatarCPF')) {
                                echo tratarTexto(formatarCPF($cpf));
                            } else {
                                echo tratarTexto($cpf);
                            }
                        ?>
                    </td>
                    <td><?php echo tratarTexto($aluno['escola_nome'] ?? '-'); ?></td>
                    <td><?php echo tratarTexto($aluno['descricao_deficiencia']); ?></td>
                    <td><?php echo tratarTexto($aluno['status_aprovacao']); ?></td>
                    <td><?php echo tratarTexto($aluno['pae_nome'] ?? 'Sem PAE'); ?></td>
                    <td><?php echo !empty($aluno['data_cadastro']) ? date('d/m/Y H:i', strtotime($aluno['data_cadastro'])) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center;">Nenhum aluno encontrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php exit; ?>