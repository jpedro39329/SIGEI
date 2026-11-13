<?php
// ============================================================
// SIGEI - CONTROLADOR DE ENTREGA SEGURA DE ARQUIVOS E DOCUMENTOS
// Valida autenticação, perfil e abrangência (escola / URE / empresa / PAE)
// antes de servir arquivos da pasta uploads/
// ============================================================
require_once __DIR__ . '/../config/init.php';

if (!estaLogado()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Acesso restrito. Faça login no SIGEI para visualizar este documento.';
    exit();
}

$pasta = strtolower(trim((string) ($_GET['pasta'] ?? '')));
$arquivo = basename((string) ($_GET['arquivo'] ?? ''));

$pastasPermitidas = ['contratos', 'documentos', 'fotos', 'laudos'];
if (!in_array($pasta, $pastasPermitidas, true) || $arquivo === '' || !preg_match('/^[a-zA-Z0-9._-]+\.(pdf|jpg|jpeg|png)$/i', $arquivo)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Solicitação de arquivo inválida.';
    exit();
}

$caminhoRelativo = 'uploads/' . $pasta . '/' . $arquivo;
$raizUploads = realpath(__DIR__ . '/../uploads/' . $pasta);
$caminhoFisico = $raizUploads ? realpath($raizUploads . DIRECTORY_SEPARATOR . $arquivo) : false;

if (!$raizUploads || !$caminhoFisico || strpos($caminhoFisico, $raizUploads) !== 0 || !is_file($caminhoFisico)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Arquivo não encontrado.';
    exit();
}

$userPerfil = $_SESSION['user_perfil'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
$autorizado = false;

if (in_array($userPerfil, ['ADMIN', 'SEDUC'], true)) {
    $autorizado = true;
} elseif ($pasta === 'contratos') {
    // Verifica se é contrato de empresa ou contrato de PAE
    $stmtEmp = $conexao->prepare("SELECT id_empresa FROM empresas WHERE contrato_arquivo = ? OR contrato_arquivo LIKE ? LIMIT 1");
    $likeArq = '%' . $arquivo;
    $stmtEmp->bind_param("ss", $caminhoRelativo, $likeArq);
    $stmtEmp->execute();
    $resEmp = $stmtEmp->get_result()->fetch_assoc();
    $stmtEmp->close();

    if ($resEmp) {
        $idEmpresaDoc = (int) $resEmp['id_empresa'];
        if (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'SEFISC', 'ASURE'], true)) {
            $autorizado = true;
        } elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'], true)) {
            $idEmpUser = (int) ($_SESSION['id_empresa'] ?? idEmpresaSupervisor($conexao, $userId));
            $autorizado = ($idEmpUser === $idEmpresaDoc);
        }
    } else {
        // Verifica contrato de PAE
        $stmtPae = $conexao->prepare("SELECT id_pae, id_empresa FROM usuarios_pae WHERE contrato_arquivo = ? OR contrato_arquivo LIKE ? LIMIT 1");
        $stmtPae->bind_param("ss", $caminhoRelativo, $likeArq);
        $stmtPae->execute();
        $resPae = $stmtPae->get_result()->fetch_assoc();
        $stmtPae->close();

        if ($resPae) {
            if ($userPerfil === 'PAE' && (int) $resPae['id_pae'] === $userId) {
                $autorizado = true;
            } elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'], true)) {
                $idEmpUser = (int) ($_SESSION['id_empresa'] ?? idEmpresaSupervisor($conexao, $userId));
                $autorizado = ($idEmpUser === (int) $resPae['id_empresa']);
            } elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'SEFISC', 'ASURE'], true)) {
                $idUreUser = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
                $uresEmp = uresAtendidasEmpresa($conexao, (int) $resPae['id_empresa']);
                $autorizado = ($idUreUser > 0 && in_array($idUreUser, $uresEmp, true));
            }
        }
    }
} else {
    // Fotos, documentos (termo de responsabilidade) e laudos vinculados a alunos
    $likeArq = '%' . $arquivo;
    $stmtAl = $conexao->prepare("
        SELECT a.id_aluno, a.id_ue, a.status_aprovacao, ue.id_ure
        FROM alunos a
        LEFT JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
        LEFT JOIN laudos l ON l.id_aluno = a.id_aluno
        WHERE a.foto_arquivo = ?
           OR a.termo_responsabilidade_arquivo = ?
           OR l.caminho_arquivo = ?
           OR a.foto_arquivo LIKE ?
           OR a.termo_responsabilidade_arquivo LIKE ?
           OR l.caminho_arquivo LIKE ?
        LIMIT 1
    ");
    $stmtAl->bind_param("ssssss", $caminhoRelativo, $caminhoRelativo, $caminhoRelativo, $likeArq, $likeArq, $likeArq);
    $stmtAl->execute();
    $alunoDoc = $stmtAl->get_result()->fetch_assoc();
    $stmtAl->close();

    if ($alunoDoc) {
        $idAlunoDoc = (int) $alunoDoc['id_aluno'];
        $idUeDoc = (int) $alunoDoc['id_ue'];
        $idUreDoc = (int) ($alunoDoc['id_ure'] ?? 0);

        if (in_array($userPerfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'], true)) {
            $idEscolaUser = idEscolaUsuario($conexao, $userId);
            $autorizado = ($idUeDoc === $idEscolaUser);
        } elseif (in_array($userPerfil, ['DIRIGENTE', 'USUARIO_SEFISC', 'USUARIO_EDUCACAO_ESPECIAL', 'SEFISC', 'ASURE'], true)) {
            $idUreUser = (int) ($_SESSION['id_ure'] ?? idUreUsuario($conexao, $userId));
            $autorizado = ($idUreUser > 0 && $idUreDoc === $idUreUser);
        } elseif (in_array($userPerfil, ['SUPERVISOR', 'USUARIO_EMPRESA'], true)) {
            $idEmpUser = (int) ($_SESSION['id_empresa'] ?? idEmpresaSupervisor($conexao, $userId));
            $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpUser);
            $autorizado = in_array($idUreDoc, $uresAtendidas, true);
        } elseif ($userPerfil === 'PAE') {
            // PAE pode visualizar a foto do aluno ao qual está ativamente vinculado (laudos/documentos médicos são restritos)
            if ($pasta === 'fotos' && ($alunoDoc['status_aprovacao'] ?? '') === 'APROVADO') {
                $stmtAss = $conexao->prepare("SELECT id_associacao FROM associacoes WHERE id_aluno = ? AND id_pae = ? AND ativo = 1 LIMIT 1");
                $stmtAss->bind_param("ii", $idAlunoDoc, $userId);
                $stmtAss->execute();
                $autorizado = ($stmtAss->get_result()->num_rows > 0);
                $stmtAss->close();
            }
        }
    }
}

if (!$autorizado) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Acesso negado. Você não tem permissão para visualizar este arquivo.';
    exit();
}

$ext = strtolower(pathinfo($caminhoFisico, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$contentType = $mimeMap[$ext] ?? 'application/octet-stream';

// Libera o lock da sessão antes de transmitir o binário
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$fileSize = (int) @filesize($caminhoFisico);
$fileMtime = (int) @filemtime($caminhoFisico);
$etag = '"' . md5($userId . ':' . $userPerfil . ':' . $caminhoRelativo . ':' . $fileSize . ':' . $fileMtime) . '"';

header('Cache-Control: private, must-revalidate, max-age=300');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');

$ifNoneMatch = trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '');
if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
    http_response_code(304);
    exit();
}

header('Content-Type: ' . $contentType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: inline; filename="' . addslashes($arquivo) . '"');

readfile($caminhoFisico);
exit();
