<?php
// ============================================================
// SIGEI - SISTEMA CENTRAL DE AUDITORIA E RASTREABILIDADE
// Grava ações críticas mantendo histórico imutável
// ============================================================

if (!defined('SIGEI_AUDITORIA_HELPER')) {
    define('SIGEI_AUDITORIA_HELPER', true);
}

/**
 * Registra uma operação no log central de auditoria.
 *
 * @param mysqli $conexao Conexão ativa com o banco de dados
 * @param string $modulo USUARIOS, ALUNOS, DOCUMENTOS, PAE, ESCOLAS, EMPRESAS, AUTH, etc.
 * @param string $acao LOGIN, LOGOUT, CRIAR, EDITAR, EXCLUIR, INATIVAR, ATIVAR, ARQUIVAR, REATIVAR, DELIBERAR
 * @param string $entidade Nome da tabela ou entidade afetada (ex: 'alunos', 'usuarios_ue')
 * @param int|null $idRegistro ID do registro afetado (ou null)
 * @param string|array|null $detalhes Texto explicativo ou array com dados antes/depois
 * @return bool Retorna true se gravado com sucesso, false caso contrário
 */
function registrarAuditoria(
    mysqli $conexao,
    string $modulo,
    string $acao,
    string $entidade,
    ?int $idRegistro = null,
    $detalhes = null
): bool {
    try {
        $idUsuario = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        $usuarioNome = trim($_SESSION['user_name'] ?? '');
        $usuarioCpf = preg_replace('/\D/', '', $_SESSION['user_cpf'] ?? '');
        $usuarioPerfil = trim($_SESSION['user_perfil'] ?? 'SISTEMA');

        if ($usuarioNome === '') {
            $usuarioNome = 'Sistema';
        }

        $detalhesStr = null;
        if (is_array($detalhes) || is_object($detalhes)) {
            $detalhesStr = json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($detalhes !== null) {
            $detalhesStr = (string) $detalhes;
        }

        $ipOrigem = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipOrigem = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        $ipOrigem = $ipOrigem ? substr(trim($ipOrigem), 0, 45) : null;

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(trim($_SERVER['HTTP_USER_AGENT']), 0, 255) : null;

        $modulo = strtoupper(trim($modulo));
        $acao = strtoupper(trim($acao));
        $entidade = strtolower(trim($entidade));

        $stmt = $conexao->prepare("
            INSERT INTO auditoria (
                id_usuario,
                usuario_nome,
                usuario_cpf,
                usuario_perfil,
                modulo,
                acao,
                entidade,
                id_registro,
                detalhes,
                ip_origem,
                user_agent,
                criado_em
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            error_log('[SIGEI-AUDIT] Falha ao preparar statement: ' . $conexao->error);
            return false;
        }

        $stmt->bind_param(
            "issssssisss",
            $idUsuario,
            $usuarioNome,
            $usuarioCpf,
            $usuarioPerfil,
            $modulo,
            $acao,
            $entidade,
            $idRegistro,
            $detalhesStr,
            $ipOrigem,
            $userAgent
        );

        $exec = $stmt->execute();
        if (!$exec) {
            error_log('[SIGEI-AUDIT] Falha ao gravar auditoria: ' . $stmt->error);
        }
        $stmt->close();
        return (bool) $exec;
    } catch (Throwable $e) {
        error_log('[SIGEI-AUDIT] Exceção ao gravar auditoria: ' . $e->getMessage());
        return false;
    }
}

