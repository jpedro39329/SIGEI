<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../config/init.php";

exigirLogin();

$userName = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];
$idUreUsuario = (int) ($_SESSION['id_ure'] ?? 0);

// Trava de segurança: apenas USUARIO_SEFISC acessa este script
if ($userPerfil !== 'USUARIO_SEFISC') {
    http_response_code(403);
    die("Acesso negado. Perfil não autorizado.");
}

// Recebe os filtros atuais da tela
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

// Filtra os alunos de acordo com a URE do usuário SEFISC
if ($idUreUsuario <= 0) {
    $idUreUsuario = idUreUsuario($conexao, $userId);
}
if ($idUreUsuario > 0) {
    $where[] = "e.id_ure = $idUreUsuario";
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Busca os alunos no banco de dados
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

// Força o navegador a baixar como um arquivo do Excel
$fileName = "relatorio_alunos_sefisc_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");

// Garante a acentuação correta no Excel
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
        <?php if (count($alunos) > 0): ?>
            <?php foreach ($alunos as $aluno): ?>
                <tr>
                    <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['ra']); ?></td>
                    <td><?php echo htmlspecialchars(formatarCPF($aluno['cpf'])); ?></td>
                    <td><?php echo htmlspecialchars($aluno['escola_nome'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($aluno['descricao_deficiencia']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['status_aprovacao']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['pae_nome'] ?? 'Sem PAE'); ?></td>
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


// ... restante do código
<?php exit; ?>