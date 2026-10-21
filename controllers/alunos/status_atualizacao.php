<?php
require_once "../../config/init.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['autenticado' => false]);
    exit();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userPerfil = $_SESSION['user_perfil'] ?? '';
$perfilNorm = normalizarPerfil($userPerfil);

// Libera o lock da sessão imediatamente para não bloquear outras requisições da mesma sessão
session_write_close();

$whereEscopo = "WHERE 1=1";

if ($perfilNorm === PERFIL_USUARIO_ESCOLA) {
    $idEscola = idEscolaUsuario($conexao, $userId);
    $whereEscopo .= " AND a.id_ue = " . (int) $idEscola;
} elseif (in_array($perfilNorm, [PERFIL_DIRIGENTE, PERFIL_USUARIO_SEFISC, PERFIL_USUARIO_EDUCACAO_ESPECIAL], true)) {
    $idUre = idUreUsuario($conexao, $userId);
    if ($idUre > 0) {
        $whereEscopo .= " AND ue.id_ure = " . (int) $idUre;
    }
} elseif ($perfilNorm === PERFIL_SUPERVISOR) {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
    $ures = uresAtendidasEmpresa($conexao, $idEmpresa);
    if (!empty($ures)) {
        $whereEscopo .= " AND ue.id_ure IN (" . implode(',', array_map('intval', $ures)) . ")";
    } else {
        $whereEscopo .= " AND 1=0";
    }
} elseif ($perfilNorm === PERFIL_PAE) {
    $whereEscopo .= " AND EXISTS (SELECT 1 FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.id_pae = " . (int) $userId . ")";
}

// Calcula um resumo leve de todos os alunos, status, laudos e associações visíveis ao perfil
$sqlAssinatura = "
    SELECT 
        COUNT(a.id_aluno) AS total_alunos,
        SUM(CASE WHEN a.status_aprovacao = 'PENDENTE' THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN a.status_aprovacao = 'APROVADO' THEN 1 ELSE 0 END) AS aprovados,
        SUM(CASE WHEN a.status_aprovacao = 'REPROVADO' THEN 1 ELSE 0 END) AS reprovados,
        SUM(CASE WHEN a.status_aprovacao = 'PENDENTE_CORRECAO' THEN 1 ELSE 0 END) AS correcao,
        SUM(CASE WHEN a.status_aprovacao = 'ARQUIVADO' THEN 1 ELSE 0 END) AS arquivados,
        COALESCE(MAX(a.data_cadastro), '') AS max_cadastro,
        COALESCE(MAX(a.data_deliberacao), '') AS max_deliberacao,
        COALESCE(MAX(a.data_arquivamento), '') AS max_arquivamento,
        (SELECT COALESCE(MAX(aud.id_auditoria), 0) FROM auditoria aud WHERE aud.entidade = 'alunos') AS max_auditoria,
        MD5(COALESCE(GROUP_CONCAT(
            CONCAT_WS(':', a.id_aluno, a.status_aprovacao, COALESCE(a.nome, ''), COALESCE(a.motivo_reprovacao, ''), COALESCE(a.motivo_arquivamento, ''),
                (SELECT COUNT(*) FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1),
                (SELECT COUNT(*) FROM laudos l WHERE l.id_aluno = a.id_aluno)
            ) ORDER BY a.id_aluno SEPARATOR '|'
        ), '')) AS hash_registros
    FROM alunos a
    LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
    $whereEscopo
";

$res = mysqli_query($conexao, $sqlAssinatura);
$row = $res ? mysqli_fetch_assoc($res) : [];

$assinatura = md5(json_encode($row));

echo json_encode([
    'autenticado' => true,
    'hash' => $assinatura,
    'resumo' => [
        'total' => (int) ($row['total_alunos'] ?? 0),
        'pendentes' => (int) ($row['pendentes'] ?? 0),
        'aprovados' => (int) ($row['aprovados'] ?? 0),
        'reprovados' => (int) ($row['reprovados'] ?? 0),
        'correcao' => (int) ($row['correcao'] ?? 0),
        'arquivados' => (int) ($row['arquivados'] ?? 0)
    ],
    'timestamp' => time()
]);
exit();

