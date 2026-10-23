<?php

// ============================================================
// CONSTANTES CANÔNICAS DE PERFIS DE USUÁRIO (SIGEI)
// ============================================================
define('PERFIL_ADMIN', 'ADMIN');
define('PERFIL_SEDUC', 'SEDUC');
define('PERFIL_DIRIGENTE', 'DIRIGENTE');
define('PERFIL_USUARIO_SEFISC', 'USUARIO_SEFISC');
define('PERFIL_USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_EDUCACAO_ESPECIAL');
define('PERFIL_SUPERVISOR', 'SUPERVISOR');
define('PERFIL_USUARIO_ESCOLA', 'USUARIO_ESCOLA');
define('PERFIL_PAE', 'PAE');

// Lista oficial canônica de perfis do sistema
const PERFIS_CANONICOS = array(
    PERFIL_ADMIN,
    PERFIL_SEDUC,
    PERFIL_DIRIGENTE,
    PERFIL_USUARIO_SEFISC,
    PERFIL_USUARIO_EDUCACAO_ESPECIAL,
    PERFIL_SUPERVISOR,
    PERFIL_USUARIO_ESCOLA,
    PERFIL_PAE
);

// Mapeamento de aliases e sinônimos legados para normalização canônica
const MAPA_ALIASES_PERFIS = array(
    'ESCOLA'                    => PERFIL_USUARIO_ESCOLA,
    'USUARIO_UE'                => PERFIL_USUARIO_ESCOLA,
    'UE'                        => PERFIL_USUARIO_ESCOLA,
    'USUARIO_EMPRESA'           => PERFIL_SUPERVISOR,
    'EMPRESA'                   => PERFIL_SUPERVISOR,
    'SEFISC'                    => PERFIL_USUARIO_SEFISC,
    'ASURE'                     => PERFIL_DIRIGENTE,
    'GABINETE'                  => PERFIL_DIRIGENTE,
    'DIRIGENTE_REGIONAL'        => PERFIL_DIRIGENTE,
    'EDU_ESPECIAL'              => PERFIL_USUARIO_EDUCACAO_ESPECIAL,
    'EDUCACAO_ESPECIAL'         => PERFIL_USUARIO_EDUCACAO_ESPECIAL,
    'AEE'                       => PERFIL_USUARIO_EDUCACAO_ESPECIAL,
    'EEC'                       => PERFIL_USUARIO_EDUCACAO_ESPECIAL,
);

// Normaliza qualquer string de perfil para a constante canônica oficial
function normalizarPerfil($perfil) {
    $p = strtoupper(trim((string) $perfil));
    return MAPA_ALIASES_PERFIS[$p] ?? $p;
}

// ============================================================
// FUNÇÕES AUXILIARES DE PERMISSÃO
// ============================================================

// Verifica se o usuário está logado
function estaLogado() {
    return isset($_SESSION['user_id']);
}

// Verifica se o usuário tem um dos perfis permitidos (com suporte canônico e aliases legados)
function temPermissao($perfil, $perfisPermitidos) {
    if (empty($perfil)) {
        return false;
    }
    
    $perfilNorm = normalizarPerfil($perfil);
    $perfisPermitidosArray = is_array($perfisPermitidos) ? $perfisPermitidos : array($perfisPermitidos);
    $permitidosNorm = array_map('normalizarPerfil', $perfisPermitidosArray);

    return in_array($perfilNorm, $permitidosNorm, true);
}

// Retorna o caminho correto da página de login conforme a localização do script
function urlLogin() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    // Se estiver dentro de subpasta de views (ex: views/alunos/listar.php)
    if (preg_match('#/views/[^/]+/[^/]+$#', $script)) {
        return '../auth/login.php';
    }
    // Se estiver diretamente em views (ex: views/dashboard.php)
    if (preg_match('#/views/[^/]+$#', $script)) {
        return 'auth/login.php';
    }
    // Se estiver em subpasta de controllers
    if (preg_match('#/controllers/[^/]+/[^/]+$#', $script)) {
        return '../../views/auth/login.php';
    }
    // Se estiver diretamente em controllers
    if (preg_match('#/controllers/[^/]+$#', $script)) {
        return '../views/auth/login.php';
    }

    return 'views/auth/login.php';
}

// Retorna o caminho correto para o dashboard conforme a localização do script
function urlDashboard() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    // Se estiver dentro de subpasta de views (ex: views/alunos/listar.php)
    if (preg_match('#/views/[^/]+/[^/]+$#', $script)) {
        return '../dashboard.php';
    }
    // Se estiver diretamente em views (ex: views/dashboard.php)
    if (preg_match('#/views/[^/]+$#', $script)) {
        return 'dashboard.php';
    }
    // Se estiver em subpasta de controllers
    if (preg_match('#/controllers/[^/]+/[^/]+$#', $script)) {
        return '../../views/dashboard.php';
    }
    // Se estiver diretamente em controllers
    if (preg_match('#/controllers/[^/]+$#', $script)) {
        return '../views/dashboard.php';
    }

    return 'views/dashboard.php';
}

// Redireciona para o login se não estiver logado
function exigirLogin() {
    if (!estaLogado()) {
        header("Location: " . urlLogin());
        exit();
    }
}

// Redireciona usuário em caso de acesso não autorizado (para dashboard se logado, para login se deslogado)
function redirecionarAcessoNegado() {
    if (!estaLogado()) {
        header("Location: " . urlLogin());
        exit();
    }
    header("Location: " . urlDashboard() . "?erro=acesso_negado");
    exit();
}

// ============================================================
// PROTEÇÃO CSRF
// ============================================================

// Gera (ou reaproveita) o token CSRF da sessão
function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Valida o token CSRF recebido no formulário
function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token'], $token)
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Alias para compatibilidade
function verificarTokenCSRF($token) {
    return validarTokenCSRF($token);
}

// Exige que a requisição POST contenha um token CSRF válido
function exigirTokenCSRF() {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarTokenCSRF($token)) {
        http_response_code(403);
        die("Token de segurança inválido. Recarregue a página e tente novamente.");
    }
}

// Bloqueia acesso se o perfil não estiver na lista permitida
function exigirPerfil($perfisPermitidos) {
    exigirLogin();
    if (!temPermissao($_SESSION['user_perfil'], $perfisPermitidos)) {
        redirecionarAcessoNegado();
    }
}

// Formata CPF para exibição (000.000.000-00)
function formatarCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf ?? '');
    if (strlen($cpf) === 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

// Formata CNPJ para exibição (00.000.000/0000-00)
function formatarCNPJ($cnpj) {
    $cnpj = preg_replace('/\D/', '', $cnpj ?? '');
    if (strlen($cnpj) === 14) {
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
    return $cnpj;
}

// Formata CEP para exibição (00000-000)
function formatarCEP($cep) {
    $cep = preg_replace('/\D/', '', $cep ?? '');
    if (strlen($cep) === 8) {
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
    return $cep;
}

// Formata Telefone para exibição ((00) 9 0000-0000 ou (00) 0000-0000)
function formatarTelefone($tel) {
    $tel = preg_replace('/\D/', '', $tel ?? '');
    $len = strlen($tel);
    if ($len === 11) {
        // Formato com 9 dígitos no celular: (DD) 9 XXXX-XXXX
        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 1) . ' ' . substr($tel, 3, 4) . '-' . substr($tel, 7, 4);
    } elseif ($len === 10) {
        // Formato fixo ou celular antigo: (DD) XXXX-XXXX
        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6, 4);
    } elseif ($len === 9) {
        return substr($tel, 0, 1) . ' ' . substr($tel, 1, 4) . '-' . substr($tel, 5, 4);
    } elseif ($len === 8) {
        return substr($tel, 0, 4) . '-' . substr($tel, 4, 4);
    }
    return $tel;
}

// ============================================================
// FUNÇÕES CENTRALIZADAS DE VALIDAÇÃO DE CAMPOS (BACK-END)
// ============================================================

/**
 * Valida formato e consistência de CPF (11 dígitos numéricos, não todos iguais).
 */
function validarCPF($cpf, $obrigatorio = true) {
    $limpo = preg_replace('/\D/', '', (string) $cpf);
    if ($limpo === '') {
        return !$obrigatorio;
    }
    if (strlen($limpo) !== 11) {
        return false;
    }
    if (preg_match('/^(\d)\1{10}$/', $limpo)) {
        return false;
    }
    return true;
}

/**
 * Valida formato e consistência de CNPJ (14 dígitos numéricos, não todos iguais).
 */
function validarCNPJ($cnpj, $obrigatorio = true) {
    $limpo = preg_replace('/\D/', '', (string) $cnpj);
    if ($limpo === '') {
        return !$obrigatorio;
    }
    if (strlen($limpo) !== 14) {
        return false;
    }
    if (preg_match('/^(\d)\1{13}$/', $limpo)) {
        return false;
    }
    return true;
}

/**
 * Valida telefone (10 ou 11 dígitos numéricos com DDD).
 */
function validarTelefone($telefone, $obrigatorio = false) {
    $limpo = preg_replace('/\D/', '', (string) $telefone);
    if ($limpo === '') {
        return !$obrigatorio;
    }
    $len = strlen($limpo);
    if ($len < 10 || $len > 11) {
        return false;
    }
    if (preg_match('/^(\d)\1+$/', $limpo)) {
        return false;
    }
    return true;
}

/**
 * Valida CEP (8 dígitos numéricos).
 */
function validarCEP($cep, $obrigatorio = false) {
    $limpo = preg_replace('/\D/', '', (string) $cep);
    if ($limpo === '') {
        return !$obrigatorio;
    }
    if (strlen($limpo) !== 8 || preg_match('/^(\d)\1{7}$/', $limpo)) {
        return false;
    }
    return true;
}

/**
 * Valida endereço de e-mail e limite de tamanho.
 */
function validarEmail($email, $obrigatorio = false, $maxLen = 150) {
    $email = trim((string) $email);
    if ($email === '') {
        return !$obrigatorio;
    }
    if (mb_strlen($email, 'UTF-8') > $maxLen) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida data no formato YYYY-MM-DD, verificando se existe no calendário e limites opcionais.
 */
function validarDataISO($data, $obrigatorio = false, $naoFutura = false, $anoMinimo = 1900) {
    $data = trim((string) $data);
    if ($data === '') {
        return !$obrigatorio;
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $m)) {
        return false;
    }
    $ano = (int) $m[1];
    $mes = (int) $m[2];
    $dia = (int) $m[3];
    if ($ano < $anoMinimo || $ano > 2100 || !checkdate($mes, $dia, $ano)) {
        return false;
    }
    if ($naoFutura && $data > date('Y-m-d')) {
        return false;
    }
    return true;
}

/**
 * Valida campo estritamente numérico com limites de quantidade de dígitos.
 */
function validarCampoNumerico($valor, $obrigatorio = false, $minDigitos = 1, $maxDigitos = 20) {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return !$obrigatorio;
    }
    if (!preg_match('/^\d+$/', $valor)) {
        return false;
    }
    $len = strlen($valor);
    return ($len >= $minDigitos && $len <= $maxDigitos);
}

// Retorna o nome amigável do perfil
function nomePerfil($perfil) {
    $nomes = array(
        'ADMIN'                     => 'Administrador',
        'SEDUC'                     => 'Secretaria de Educação do Estado de São Paulo',
        'DIRIGENTE'                 => 'Assistência Técnica - ASURE',
        'ASURE'                     => 'Assistência Técnica - ASURE',
        'USUARIO_SEFISC'            => 'Seção de Fiscalização',
        'USUARIO_EDUCACAO_ESPECIAL' => 'Educação Especial',
        'SUPERVISOR'                => 'Supervisor de Licitações e Contratos',
        'USUARIO_EMPRESA'           => 'Supervisor de Licitações e Contratos',
        'USUARIO_ESCOLA'            => 'Unidade Escolar',
        'USUARIO_UE'                => 'Unidade Escolar',
        'ESCOLA'                    => 'Unidade Escolar',
        'PAE'                       => 'Profissional de Apoio Escolar'
    );
    return $nomes[$perfil] ?? $perfil;
}

// Retorna o id_ue do usuário logado (perfil USUARIO_ESCOLA)
function idUeUsuario($conexao, $userId) {
    $userId = (int) $userId;
    $query = "SELECT id_ue FROM usuarios_ue WHERE id_usuario_ue = $userId";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int) ($row['id_ue'] ?? 0);
}

// Retorna o nome da escola do usuário logado (perfil USUARIO_ESCOLA)
function nomeUeUsuario($conexao, $userId) {
    $userId = (int) $userId;
    $query = "SELECT ue.nome FROM usuarios_ue u
              JOIN unidades_escolares ue ON u.id_ue = ue.id_ue
              WHERE u.id_usuario_ue = $userId";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row['nome'] ?? '';
}

// Alias para compatibilidade
function idEscolaUsuario($conexao, $userId) {
    return idUeUsuario($conexao, $userId);
}

function nomeEscolaUsuario($conexao, $userId) {
    return nomeUeUsuario($conexao, $userId);
}

// Retorna o id_empresa do supervisor logado (perfil SUPERVISOR)
function idEmpresaSupervisor($conexao, $userId) {
    $userId = (int) $userId;
    $query = "SELECT id_empresa FROM usuarios_supervisor WHERE id_usuario_supervisor = $userId";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int) ($row['id_empresa'] ?? 0);
}

// Retorna o nome da empresa do supervisor logado
function nomeEmpresaSupervisor($conexao, $userId) {
    $userId = (int) $userId;
    $query = "SELECT e.nome FROM usuarios_supervisor u
              JOIN empresas e ON u.id_empresa = e.id_empresa
              WHERE u.id_usuario_supervisor = $userId";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row['nome'] ?? '';
}

// Alias para compatibilidade
function idEmpresaUsuario($conexao, $userId) {
    return idEmpresaSupervisor($conexao, $userId);
}

function nomeEmpresaUsuario($conexao, $userId) {
    return nomeEmpresaSupervisor($conexao, $userId);
}

// Retorna o id_ure do usuário URE logado
function idUreUsuario($conexao, $userId) {
    $userId = (int) $userId;
    $query = "SELECT id_ure FROM usuarios_ure WHERE id_usuario_ure = $userId";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int) ($row['id_ure'] ?? 0);
}

// Retorna o nome da URE do usuário logado
function nomeUreUsuario($conexao, $userId) {
    $userId = (int) $userId;
    $query = "SELECT u.nome FROM usuarios_ure uu
              JOIN unidades_regionais u ON uu.id_ure = u.id_ure
              WHERE uu.id_usuario_ure = $userId";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row['nome'] ?? '';
}

// Retorna as UREs atendidas por uma empresa (array de ids)
function uresAtendidasEmpresa($conexao, $idEmpresa) {
    $idEmpresa = (int) $idEmpresa;
    $query = "SELECT id_ure FROM empresa_ure WHERE id_empresa = $idEmpresa";
    $result = mysqli_query($conexao, $query);
    $ures = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ures[] = (int) $row['id_ure'];
        }
    }
    return $ures;
}

// Conta quantos alunos ativos um PAE possui
function contarAlunosPAE($conexao, $idPae) {
    $idPae = (int) $idPae;
    $query = "SELECT COUNT(*) AS total FROM associacoes WHERE id_pae = $idPae AND ativo = 1";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int) ($row['total'] ?? 0);
}

// Conta quantos PAEs ativos um aluno possui
function contarPAEsAluno($conexao, $idAluno) {
    $idAluno = (int) $idAluno;
    $query = "SELECT COUNT(*) AS total FROM associacoes WHERE id_aluno = $idAluno AND ativo = 1";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int) ($row['total'] ?? 0);
}

// Busca o PAE associado a um aluno (nome)
function nomePAEAluno($conexao, $idAluno) {
    $idAluno = (int) $idAluno;
    $query = "SELECT p.nome FROM associacoes ass
              JOIN usuarios_pae p ON ass.id_pae = p.id_pae
              WHERE ass.id_aluno = $idAluno AND ass.ativo = 1
              LIMIT 1";
    $result = mysqli_query($conexao, $query);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row['nome'] ?? 'Sem PAE';
}

// ============================================================
// GESTÃO DE TERMOS DE USO (LGPD)
// ============================================================

// Garante a existência da tabela termos_aceite e verifica se o usuário aceitou os termos
function verificarTermoAceito($conexao, $idUsuario, $perfil) {
    $idUsuario = (int) $idUsuario;
    $perfil = trim((string) $perfil);
    if ($idUsuario <= 0 || $perfil === '') {
        return false;
    }

    $tabelaQuery = "CREATE TABLE IF NOT EXISTS `termos_aceite` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_usuario` int(11) NOT NULL,
        `perfil` varchar(50) NOT NULL,
        `data_aceite` datetime DEFAULT current_timestamp(),
        `ip` varchar(45) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_usuario_perfil` (`id_usuario`, `perfil`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conexao->query($tabelaQuery);

    $stmt = $conexao->prepare("SELECT id FROM termos_aceite WHERE id_usuario = ? AND perfil = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("is", $idUsuario, $perfil);
    $stmt->execute();
    $res = $stmt->get_result();
    $aceito = ($res && $res->num_rows > 0);
    $stmt->close();

    return $aceito;
}

// Registra o aceite do termo de uso para o usuário logado
function registrarAceiteTermo($conexao, $idUsuario, $perfil) {
    $idUsuario = (int) $idUsuario;
    $perfil = trim((string) $perfil);
    if ($idUsuario <= 0 || $perfil === '') {
        return false;
    }

    $tabelaQuery = "CREATE TABLE IF NOT EXISTS `termos_aceite` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_usuario` int(11) NOT NULL,
        `perfil` varchar(50) NOT NULL,
        `data_aceite` datetime DEFAULT current_timestamp(),
        `ip` varchar(45) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_usuario_perfil` (`id_usuario`, `perfil`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conexao->query($tabelaQuery);

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conexao->prepare("INSERT INTO termos_aceite (id_usuario, perfil, ip, data_aceite)
                               VALUES (?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE data_aceite = NOW(), ip = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("isss", $idUsuario, $perfil, $ip, $ip);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

// ============================================================
// NOTIFICAÇÕES INTELIGENTES POR PERFIL
// ============================================================

/**
 * Retorna as notificações reais calculadas para o usuário logado
 * @param mysqli $conexao
 * @param string $perfil
 * @param int $userId
 * @param string $baseUrl
 * @return array
 */
function obterNotificacoesUsuario($conexao, $perfil, $userId, $baseUrl = '../') {
    $notificacoes = [];
    $userId = (int) $userId;

    if (!$conexao || $userId <= 0) {
        return $notificacoes;
    }

    // 1. EDUCAÇÃO ESPECIAL (URE)
    if ($perfil === 'USUARIO_EDUCACAO_ESPECIAL') {
        $idUre = idUreUsuario($conexao, $userId);
        $whereUre = ($idUre > 0) ? "AND ue.id_ure = $idUre" : "";

        // Solicitações devolvidas / reenviadas para reanálise pela escola
        $sqlReanalise = "
            SELECT a.id_aluno, a.nome,
                   COALESCE(
                       (SELECT MAX(aud.criado_em)
                        FROM auditoria aud
                        WHERE aud.entidade = 'alunos'
                          AND aud.id_registro = a.id_aluno
                          AND (aud.acao = 'REENVIAR' OR (aud.acao = 'EDITAR' AND aud.detalhes LIKE '%\"status_pos_edicao\":\"PENDENTE\"%'))),
                       a.data_deliberacao,
                       a.data_cadastro
                   ) AS ref_data
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE a.status_aprovacao = 'PENDENTE'
              AND (
                  a.data_deliberacao IS NOT NULL
                  OR EXISTS (
                      SELECT 1 FROM auditoria aud2
                      WHERE aud2.entidade = 'alunos'
                        AND aud2.id_registro = a.id_aluno
                        AND (aud2.acao = 'REENVIAR' OR (aud2.acao = 'EDITAR' AND aud2.detalhes LIKE '%\"status_pos_edicao\":\"PENDENTE\"%'))
                  )
              )
              $whereUre
            ORDER BY ref_data DESC LIMIT 5
        ";
        $resReanalise = mysqli_query($conexao, $sqlReanalise);
        if ($resReanalise) {
            while ($row = mysqli_fetch_assoc($resReanalise)) {
                $notificacoes[] = [
                    'tipo' => 'info',
                    'icone' => 'bi-arrow-repeat',
                    'titulo' => 'Devolvido para Reanálise',
                    'mensagem' => "A solicitação do aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> foi devolvida pela escola para reanálise.",
                    'link' => $baseUrl . "views/solicitacoes/analisar.php?id=" . $row['id_aluno'],
                    'tempo' => !empty($row['ref_data']) ? date('d/m/Y', strtotime($row['ref_data'])) : "Reanálise",
                    'ref_time' => $row['ref_data'] ?? ''
                ];
            }
        }

        // Solicitações novas pendentes (ainda não deliberadas/reenviadas)
        $sqlPendentesNovos = "
            SELECT a.id_aluno, a.nome, a.data_cadastro, DATEDIFF(NOW(), a.data_cadastro) AS dias_pendente
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE a.status_aprovacao = 'PENDENTE'
              AND a.data_deliberacao IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM auditoria aud2
                  WHERE aud2.entidade = 'alunos'
                    AND aud2.id_registro = a.id_aluno
                    AND (aud2.acao = 'REENVIAR' OR (aud2.acao = 'EDITAR' AND aud2.detalhes LIKE '%\"status_pos_edicao\":\"PENDENTE\"%'))
              )
              $whereUre
            ORDER BY a.data_cadastro DESC LIMIT 5
        ";
        $resNovos = mysqli_query($conexao, $sqlPendentesNovos);
        if ($resNovos) {
            while ($row = mysqli_fetch_assoc($resNovos)) {
                $dias = (int) $row['dias_pendente'];
                if ($dias >= 3) {
                    $notificacoes[] = [
                        'tipo' => 'danger',
                        'icone' => 'bi-clock-history',
                        'titulo' => 'Solicitação Pendente Crítica',
                        'mensagem' => "Aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> aguarda análise há {$dias} dias.",
                        'link' => $baseUrl . "views/solicitacoes/analisar.php?id=" . $row['id_aluno'],
                        'tempo' => "Há {$dias} dias",
                        'ref_time' => $row['data_cadastro'] ?? ''
                    ];
                } else {
                    $notificacoes[] = [
                        'tipo' => 'warning',
                        'icone' => 'bi-inbox-fill',
                        'titulo' => 'Nova Solicitação para Análise',
                        'mensagem' => "O aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> aguarda análise técnica.",
                        'link' => $baseUrl . "views/solicitacoes/analisar.php?id=" . $row['id_aluno'],
                        'tempo' => !empty($row['data_cadastro']) ? date('d/m/Y', strtotime($row['data_cadastro'])) : "Nova",
                        'ref_time' => $row['data_cadastro'] ?? ''
                    ];
                }
            }
        }
        // Eventos recentes de alunos na URE (edição de dados, documentos anexados, arquivamento, reativação)
        $sqlEventosUre = "
            SELECT aud.id_auditoria, aud.acao, aud.id_registro, aud.detalhes, aud.criado_em, a.nome AS aluno_nome
            FROM auditoria aud
            JOIN alunos a ON aud.id_registro = a.id_aluno
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE aud.entidade = 'alunos'
              AND aud.id_usuario != $userId
              AND aud.acao IN ('EDITAR', 'ANEXAR_DOCUMENTO', 'ARQUIVAR', 'REATIVAR')
              $whereUre
            ORDER BY aud.criado_em DESC LIMIT 4
        ";
        $resEvUre = mysqli_query($conexao, $sqlEventosUre);
        if ($resEvUre) {
            while ($ev = mysqli_fetch_assoc($resEvUre)) {
                $acaoEv = $ev['acao'];
                $nomeAl = htmlspecialchars($ev['aluno_nome'] ?? 'Aluno');
                $detEv = json_decode($ev['detalhes'] ?? '', true) ?: [];
                if ($acaoEv === 'ANEXAR_DOCUMENTO') {
                    $notificacoes[] = [
                        'tipo' => 'info',
                        'icone' => 'bi-paperclip',
                        'titulo' => 'Novo Documento Anexado',
                        'mensagem' => "Novo documento anexado na ficha do aluno <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'EDITAR' && ($detEv['status_pos_edicao'] ?? '') !== 'PENDENTE') {
                    $notificacoes[] = [
                        'tipo' => 'info',
                        'icone' => 'bi-pencil-square',
                        'titulo' => 'Ficha de Aluno Atualizada',
                        'mensagem' => "Dados cadastrais do aluno <strong>{$nomeAl}</strong> foram atualizados pela escola.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'ARQUIVAR') {
                    $notificacoes[] = [
                        'tipo' => 'secondary',
                        'icone' => 'bi-archive-fill',
                        'titulo' => 'Aluno Arquivado',
                        'mensagem' => "O cadastro do aluno <strong>{$nomeAl}</strong> foi arquivado.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'REATIVAR') {
                    $notificacoes[] = [
                        'tipo' => 'success',
                        'icone' => 'bi-arrow-counterclockwise',
                        'titulo' => 'Aluno Reativado',
                        'mensagem' => "O cadastro do aluno <strong>{$nomeAl}</strong> foi reativado.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                }
            }
        }
    }

    // 2. UNIDADE ESCOLAR (USUARIO_ESCOLA / UE)
    elseif (in_array($perfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
        $idEscola = idEscolaUsuario($conexao, $userId);

        // Eventos recentes de PAE, relatórios, documentos e status dos alunos da escola (via auditoria)
        $sqlEventosUe = "
            SELECT aud.id_auditoria, aud.acao, aud.id_registro, aud.detalhes, aud.criado_em, a.nome AS aluno_nome
            FROM auditoria aud
            JOIN alunos a ON aud.id_registro = a.id_aluno
            WHERE aud.entidade = 'alunos'
              AND a.id_ue = $idEscola
              AND aud.id_usuario != $userId
              AND aud.acao IN ('ASSOCIAR_PAE', 'DESASSOCIAR_PAE', 'REGISTRAR_RELATORIO', 'ARQUIVAR', 'REATIVAR', 'ANEXAR_DOCUMENTO')
            ORDER BY aud.criado_em DESC LIMIT 6
        ";
        $resEvUe = mysqli_query($conexao, $sqlEventosUe);
        if ($resEvUe) {
            while ($ev = mysqli_fetch_assoc($resEvUe)) {
                $acaoEv = $ev['acao'];
                $nomeAl = htmlspecialchars($ev['aluno_nome'] ?? 'Aluno');
                $detEv = json_decode($ev['detalhes'] ?? '', true) ?: [];
                $paeNomeEv = htmlspecialchars($detEv['pae_nome'] ?? 'Profissional');

                if ($acaoEv === 'ASSOCIAR_PAE') {
                    $notificacoes[] = [
                        'tipo' => 'success',
                        'icone' => 'bi-person-check-fill',
                        'titulo' => 'PAE Associado ao Aluno',
                        'mensagem' => "O PAE <strong>{$paeNomeEv}</strong> foi vinculado ao aluno <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'DESASSOCIAR_PAE') {
                    $notificacoes[] = [
                        'tipo' => 'warning',
                        'icone' => 'bi-person-dash-fill',
                        'titulo' => 'PAE Desassociado',
                        'mensagem' => "O PAE <strong>{$paeNomeEv}</strong> foi desvinculado do aluno <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'REGISTRAR_RELATORIO') {
                    $notificacoes[] = [
                        'tipo' => 'info',
                        'icone' => 'bi-journal-text',
                        'titulo' => 'Novo Relatório de PAE',
                        'mensagem' => "Novo relatório de atendimento registrado para o aluno <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'ARQUIVAR') {
                    $notificacoes[] = [
                        'tipo' => 'secondary',
                        'icone' => 'bi-archive-fill',
                        'titulo' => 'Aluno Arquivado',
                        'mensagem' => "O aluno <strong>{$nomeAl}</strong> foi arquivado no sistema.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'REATIVAR') {
                    $notificacoes[] = [
                        'tipo' => 'success',
                        'icone' => 'bi-arrow-counterclockwise',
                        'titulo' => 'Aluno Reativado',
                        'mensagem' => "O cadastro do aluno <strong>{$nomeAl}</strong> foi reativado.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $ev['id_registro'],
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                }
            }
        }

        // Alunos aprovados recentemente pela Educação Especial
        $sqlAprovados = "
            SELECT id_aluno, nome, COALESCE(data_deliberacao, data_cadastro) AS ref_data
            FROM alunos
            WHERE id_ue = $idEscola AND status_aprovacao = 'APROVADO'
            ORDER BY COALESCE(data_deliberacao, data_cadastro) DESC LIMIT 4
        ";
        $resApr = mysqli_query($conexao, $sqlAprovados);
        if ($resApr) {
            while ($row = mysqli_fetch_assoc($resApr)) {
                $notificacoes[] = [
                    'tipo' => 'success',
                    'icone' => 'bi-check-circle-fill',
                    'titulo' => 'Solicitação Aprovada',
                    'mensagem' => "O aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> foi aprovado pela Educação Especial.",
                    'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $row['id_aluno'],
                    'tempo' => !empty($row['ref_data']) ? date('d/m/Y', strtotime($row['ref_data'])) : "Aprovado",
                    'ref_time' => $row['ref_data'] ?? ''
                ];
            }
        }

        // Alunos com ajustes solicitados (PENDENTE_CORRECAO)
        $sqlCorrecao = "
            SELECT id_aluno, nome, COALESCE(data_deliberacao, data_cadastro) AS ref_data
            FROM alunos
            WHERE id_ue = $idEscola AND status_aprovacao = 'PENDENTE_CORRECAO'
            ORDER BY COALESCE(data_deliberacao, data_cadastro) DESC LIMIT 4
        ";
        $resCorr = mysqli_query($conexao, $sqlCorrecao);
        if ($resCorr) {
            while ($row = mysqli_fetch_assoc($resCorr)) {
                $notificacoes[] = [
                    'tipo' => 'warning',
                    'icone' => 'bi-exclamation-circle-fill',
                    'titulo' => 'Ajustes Solicitados',
                    'mensagem' => "O aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> teve ajustes solicitados pela Educação Especial.",
                    'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $row['id_aluno'],
                    'tempo' => !empty($row['ref_data']) ? date('d/m/Y', strtotime($row['ref_data'])) : "Ajuste",
                    'ref_time' => $row['ref_data'] ?? ''
                ];
            }
        }

        // Alunos reprovados que precisam de atenção/ajuste
        $sqlReprovados = "
            SELECT id_aluno, nome, motivo_reprovacao, COALESCE(data_deliberacao, data_cadastro) AS ref_data
            FROM alunos
            WHERE id_ue = $idEscola AND status_aprovacao = 'REPROVADO'
            ORDER BY COALESCE(data_deliberacao, data_cadastro) DESC LIMIT 4
        ";
        $resRep = mysqli_query($conexao, $sqlReprovados);
        if ($resRep) {
            while ($row = mysqli_fetch_assoc($resRep)) {
                $notificacoes[] = [
                    'tipo' => 'danger',
                    'icone' => 'bi-x-circle-fill',
                    'titulo' => 'Solicitação Reprovada',
                    'mensagem' => "O aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> foi reprovado pela Educação Especial. Verifique os motivos.",
                    'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $row['id_aluno'],
                    'tempo' => !empty($row['ref_data']) ? date('d/m/Y', strtotime($row['ref_data'])) : "Atenção",
                    'ref_time' => $row['ref_data'] ?? ''
                ];
            }
        }

        // Alunos aprovados mas que ainda não têm PAE associado
        $sqlSemPae = "
            SELECT COUNT(*) AS total
            FROM alunos a
            WHERE a.id_ue = $idEscola AND a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ";
        $resSemPae = mysqli_query($conexao, $sqlSemPae);
        $totalSemPae = $resSemPae ? (int) mysqli_fetch_assoc($resSemPae)['total'] : 0;
        if ($totalSemPae > 0) {
            $notificacoes[] = [
                'tipo' => 'warning',
                'icone' => 'bi-person-exclamation',
                'titulo' => 'Aguardando PAE',
                'mensagem' => "<strong>{$totalSemPae}</strong> aluno(s) aprovado(s) ainda aguardam vinculação de PAE.",
                'link' => $baseUrl . "views/alunos/listar.php",
                'tempo' => "Sem Cuidador"
            ];
        }
    }

    // 3. SUPERVISOR DA EMPRESA
    elseif (in_array($perfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
        $idEmpresa = idEmpresaSupervisor($conexao, $userId);
        $uresAtendidas = uresAtendidasEmpresa($conexao, $idEmpresa);

        if (!empty($uresAtendidas)) {
            $uresList = implode(',', $uresAtendidas);

            // Alunos aprovados que estão sem PAE nas UREs da empresa
            $sqlAlunosSemPae = "
                SELECT COUNT(*) AS total
                FROM alunos a
                JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
                WHERE ue.id_ure IN ($uresList) AND a.status_aprovacao = 'APROVADO'
                  AND NOT EXISTS (
                      SELECT 1 FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
                  )
            ";
            $resAlSemPae = mysqli_query($conexao, $sqlAlunosSemPae);
            $totalAlSemPae = $resAlSemPae ? (int) mysqli_fetch_assoc($resAlSemPae)['total'] : 0;
            if ($totalAlSemPae > 0) {
                $notificacoes[] = [
                    'tipo' => 'warning',
                    'icone' => 'bi-person-plus-fill',
                    'titulo' => 'Alunos Aguardando PAE',
                    'mensagem' => "Há <strong>{$totalAlSemPae}</strong> aluno(s) aprovado(s) pronto(s) para vinculação de cuidador.",
                    'link' => $baseUrl . "views/associacoes/gerenciar.php",
                    'tempo' => "Alocação"
                ];
            }
        }

        // PAEs inativos cadastrados
        $sqlPaesInativos = "SELECT COUNT(*) AS total FROM usuarios_pae WHERE id_empresa = $idEmpresa AND ativo = 0";
        $resInat = mysqli_query($conexao, $sqlPaesInativos);
        $totalInat = $resInat ? (int) mysqli_fetch_assoc($resInat)['total'] : 0;
        if ($totalInat > 0) {
            $notificacoes[] = [
                'tipo' => 'info',
                'icone' => 'bi-people-fill',
                'titulo' => 'PAEs Inativos',
                'mensagem' => "Você possui <strong>{$totalInat}</strong> PAE(s) inativo(s) cadastrado(s).",
                'link' => $baseUrl . "views/paes/listar.php",
                'tempo' => "Cadastro"
            ];
        }
    }

    // 4. ADMIN & SEDUC
    elseif (in_array($perfil, ['ADMIN', 'SEDUC'])) {
        // Alunos pendentes há mais de 5 dias em toda a rede
        $sqlPendentesCriticos = "
            SELECT COUNT(*) AS total
            FROM alunos
            WHERE status_aprovacao = 'PENDENTE' AND DATEDIFF(NOW(), data_cadastro) >= 5
        ";
        $resCrit = mysqli_query($conexao, $sqlPendentesCriticos);
        $totCrit = $resCrit ? (int) mysqli_fetch_assoc($resCrit)['total'] : 0;
        if ($totCrit > 0) {
            $notificacoes[] = [
                'tipo' => 'danger',
                'icone' => 'bi-exclamation-triangle-fill',
                'titulo' => 'Fila de Análise Lenta',
                'mensagem' => "Existem <strong>{$totCrit}</strong> aluno(s) pendente(s) há mais de 5 dias na rede.",
                'link' => $baseUrl . "views/alunos/listar.php?campo_filtro=6&busca=PENDENTE",
                'tempo' => "Alerta Geral"
            ];
        }

        // Alunos aprovados sem atendimento de PAE
        $sqlSemAtend = "
            SELECT COUNT(*) AS total
            FROM alunos a
            WHERE a.status_aprovacao = 'APROVADO'
              AND NOT EXISTS (
                  SELECT 1 FROM associacoes ass WHERE ass.id_aluno = a.id_aluno AND ass.ativo = 1
              )
        ";
        $resSemAt = mysqli_query($conexao, $sqlSemAtend);
        $totSemAt = $resSemAt ? (int) mysqli_fetch_assoc($resSemAt)['total'] : 0;
        if ($totSemAt > 0) {
            $notificacoes[] = [
                'tipo' => 'warning',
                'icone' => 'bi-person-x-fill',
                'titulo' => 'Alunos Aprovados sem PAE',
                'mensagem' => "Total de <strong>{$totSemAt}</strong> aluno(s) aprovado(s) sem cuidador alocado.",
                'link' => $baseUrl . "views/alunos/listar.php",
                'tempo' => "Rede Estadual"
            ];
        }
    }

    // 5. DIRIGENTE REGIONAL / SEFISC
    elseif (in_array($perfil, ['DIRIGENTE', 'USUARIO_SEFISC'])) {
        $idUre = idUreUsuario($conexao, $userId);
        if ($idUre > 0) {
            $sqlPendentesUre = "
                SELECT COUNT(*) AS total
                FROM alunos a
                JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
                WHERE ue.id_ure = $idUre AND a.status_aprovacao = 'PENDENTE'
            ";
            $resPUre = mysqli_query($conexao, $sqlPendentesUre);
            $totPUre = $resPUre ? (int) mysqli_fetch_assoc($resPUre)['total'] : 0;
            if ($totPUre > 0) {
                $notificacoes[] = [
                    'tipo' => 'warning',
                    'icone' => 'bi-hourglass-split',
                    'titulo' => 'Solicitações na Regional',
                    'mensagem' => "Há <strong>{$totPUre}</strong> solicitação(ões) em tramitação na sua Diretoria de Ensino.",
                    'link' => $baseUrl . "views/alunos/listar.php",
                    'tempo' => "Regional"
                ];
            }
        }
    }

    // 6. PAE (CUIDADOR)
    elseif ($perfil === 'PAE') {
        // Eventos diretos de vinculação / desvinculação deste PAE registrados em auditoria
        $sqlAuditPae = "
            SELECT aud.id_auditoria, aud.acao, aud.id_registro, aud.detalhes, aud.criado_em, a.nome AS aluno_nome
            FROM auditoria aud
            LEFT JOIN alunos a ON aud.id_registro = a.id_aluno
            WHERE aud.entidade = 'alunos'
              AND aud.acao IN ('ASSOCIAR_PAE', 'DESASSOCIAR_PAE', 'EDITAR', 'ANEXAR_DOCUMENTO', 'ARQUIVAR')
            ORDER BY aud.criado_em DESC LIMIT 15
        ";
        $resAudPae = mysqli_query($conexao, $sqlAuditPae);
        $idsAlunosAtivosPae = [];
        $resAtivosPae = mysqli_query($conexao, "SELECT id_aluno FROM associacoes WHERE id_pae = $userId AND ativo = 1");
        if ($resAtivosPae) {
            while ($rAt = mysqli_fetch_assoc($resAtivosPae)) {
                $idsAlunosAtivosPae[(int) $rAt['id_aluno']] = true;
            }
        }

        if ($resAudPae) {
            while ($ev = mysqli_fetch_assoc($resAudPae)) {
                $acaoEv = $ev['acao'];
                $idAl = (int) ($ev['id_registro'] ?? 0);
                $detEv = json_decode($ev['detalhes'] ?? '', true) ?: [];
                $idPaeEv = (int) ($detEv['id_pae'] ?? 0);
                $nomeAl = htmlspecialchars($ev['aluno_nome'] ?? ($detEv['aluno_nome'] ?? 'Aluno'));

                if ($acaoEv === 'ASSOCIAR_PAE' && $idPaeEv === $userId) {
                    $notificacoes[] = [
                        'tipo' => 'success',
                        'icone' => 'bi-person-check-fill',
                        'titulo' => 'Novo Aluno Associado',
                        'mensagem' => "Você foi associado ao atendimento do aluno <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $idAl,
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif ($acaoEv === 'DESASSOCIAR_PAE' && $idPaeEv === $userId) {
                    $notificacoes[] = [
                        'tipo' => 'warning',
                        'icone' => 'bi-person-dash-fill',
                        'titulo' => 'Associação Encerrada',
                        'mensagem' => "Você foi desvinculado do atendimento do aluno <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/relatorios/listar.php",
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif (isset($idsAlunosAtivosPae[$idAl]) && $acaoEv === 'EDITAR') {
                    $notificacoes[] = [
                        'tipo' => 'info',
                        'icone' => 'bi-pencil-square',
                        'titulo' => 'Ficha do Aluno Atualizada',
                        'mensagem' => "Os dados do aluno <strong>{$nomeAl}</strong> foram atualizados.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $idAl,
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                } elseif (isset($idsAlunosAtivosPae[$idAl]) && $acaoEv === 'ANEXAR_DOCUMENTO') {
                    $notificacoes[] = [
                        'tipo' => 'info',
                        'icone' => 'bi-paperclip',
                        'titulo' => 'Novo Documento do Aluno',
                        'mensagem' => "Um novo documento foi anexado à ficha de <strong>{$nomeAl}</strong>.",
                        'link' => $baseUrl . "views/alunos/visualizar.php?id=" . $idAl,
                        'tempo' => date('d/m/Y', strtotime($ev['criado_em'])),
                        'ref_time' => $ev['criado_em'] . '_' . $ev['id_auditoria']
                    ];
                }
            }
        }

        // Alunos em atendimento
        $sqlMeusAlunos = "
            SELECT COUNT(*) AS total
            FROM associacoes ass
            WHERE ass.id_pae = $userId AND ass.ativo = 1
        ";
        $resMeus = mysqli_query($conexao, $sqlMeusAlunos);
        $totMeus = $resMeus ? (int) mysqli_fetch_assoc($resMeus)['total'] : 0;
        if ($totMeus > 0) {
            $notificacoes[] = [
                'tipo' => 'info',
                'icone' => 'bi-journal-check',
                'titulo' => 'Relatórios Mensais',
                'mensagem' => "Você possui <strong>{$totMeus}</strong> aluno(s) ativo(s). Mantenha seus relatórios pedagógicos em dia.",
                'link' => $baseUrl . "views/relatorios/listar.php",
                'tempo' => "Acompanhamento"
            ];
        }
    }
    // Atribui uma chave única (hash) e rota normalizada para cada notificação
    foreach ($notificacoes as &$n) {
        $rotaRelativa = preg_replace('#^(\.\./)+#', '', $n['link']);
        $n['rota'] = $rotaRelativa;
        $refTime = $n['ref_time'] ?? '';
        $n['key'] = md5($n['titulo'] . '|' . $rotaRelativa . ($refTime !== '' ? '|' . $refTime : ''));
    }
    unset($n);

    // Filtra as notificações já marcadas como lidas pelo usuário
    if (!empty($notificacoes)) {
        $keys = array_map(function($n) use ($conexao) {
            return "'" . mysqli_real_escape_string($conexao, $n['key']) . "'";
        }, $notificacoes);
        $keysIn = implode(',', $keys);
        $perfilEscaped = mysqli_real_escape_string($conexao, $perfil);

        $sqlLidas = "SELECT notificacao_key FROM notificacoes_lidas WHERE id_usuario = $userId AND perfil = '$perfilEscaped' AND notificacao_key IN ($keysIn)";
        $resLidas = mysqli_query($conexao, $sqlLidas);
        $chavesLidas = [];
        if ($resLidas) {
            while ($row = mysqli_fetch_assoc($resLidas)) {
                $chavesLidas[$row['notificacao_key']] = true;
            }
        }

        $notificacoes = array_values(array_filter($notificacoes, function($n) use ($chavesLidas) {
            return !isset($chavesLidas[$n['key']]);
        }));
    }

    return $notificacoes;
}

/**
 * Obtém a linha do tempo completa do histórico da solicitação do aluno
 * (envio inicial, reprovações, solicitações de ajustes, reenvios, aprovações, arquivamentos e reativações).
 * Todas as datas são formatadas no padrão brasileiro DD/MM/YYYY e os dados de autoria contêm apenas Nome e CPF.
 */
function obterHistoricoSolicitacaoAluno($conexao, array $aluno, $filtroPaeId = 0): array {
    $idAluno = (int) ($aluno['id_aluno'] ?? 0);
    $filtroPaeId = (int) $filtroPaeId;
    $eventos = [];

    // Helper interno para buscar CPF de um usuário pelo nome ou ID se o registro antigo estiver sem CPF
    $resolverCpfUsuario = function($nome, $idUsuario = 0, $perfil = '') use ($conexao) {
        $nomeEsc = mysqli_real_escape_string($conexao, trim((string) $nome));
        $idUsuario = (int) $idUsuario;
        $perfil = strtoupper(trim((string) $perfil));

        if ($idUsuario > 0) {
            if ($perfil === 'ADMIN') {
                $r = mysqli_query($conexao, "SELECT cpf FROM admin WHERE id_admin = $idUsuario LIMIT 1");
            } elseif ($perfil === 'SEDUC') {
                $r = mysqli_query($conexao, "SELECT cpf FROM seduc WHERE id_seduc = $idUsuario LIMIT 1");
            } elseif (in_array($perfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'], true)) {
                $r = mysqli_query($conexao, "SELECT cpf FROM usuarios_ue WHERE id_usuario_ue = $idUsuario LIMIT 1");
            } elseif (in_array($perfil, ['SUPERVISOR', 'USUARIO_EMPRESA', 'EMPRESA'], true)) {
                $r = mysqli_query($conexao, "SELECT cpf FROM usuarios_supervisor WHERE id_usuario_supervisor = $idUsuario LIMIT 1");
            } elseif ($perfil === 'PAE') {
                $r = mysqli_query($conexao, "SELECT cpf FROM usuarios_pae WHERE id_pae = $idUsuario LIMIT 1");
            } else {
                $r = mysqli_query($conexao, "SELECT cpf FROM usuarios_ure WHERE id_usuario_ure = $idUsuario LIMIT 1");
            }
            if (!empty($r) && ($row = mysqli_fetch_assoc($r)) && !empty($row['cpf'])) {
                return formatarCPF($row['cpf']);
            }
        }

        if ($nomeEsc !== '') {
            $tabelasBusca = ['usuarios_ure', 'usuarios_ue', 'usuarios_supervisor', 'usuarios_pae', 'admin', 'seduc'];
            foreach ($tabelasBusca as $tb) {
                $r = mysqli_query($conexao, "SELECT cpf FROM `$tb` WHERE nome = '$nomeEsc' LIMIT 1");
                if ($r && ($row = mysqli_fetch_assoc($r)) && !empty($row['cpf'])) {
                    return formatarCPF($row['cpf']);
                }
            }
        }
        return 'Não informado';
    };

    // 1. Solicitação Enviada (Cadastro Inicial)
    $cadNome = trim($aluno['cadastrado_por_nome'] ?? '') ?: 'Equipe Escolar';
    $cadCpfRaw = preg_replace('/\D/', '', $aluno['cadastrado_por_cpf'] ?? '');
    $cadCpf = $cadCpfRaw !== '' ? formatarCPF($cadCpfRaw) : $resolverCpfUsuario($cadNome, (int) ($aluno['id_usuario_ue'] ?? 0), 'USUARIO_ESCOLA');

    $eventos[] = [
        'titulo' => 'Solicitação Enviada',
        'data' => !empty($aluno['data_cadastro']) ? date('d/m/Y', strtotime($aluno['data_cadastro'])) : '-',
        'timestamp' => !empty($aluno['data_cadastro']) ? strtotime($aluno['data_cadastro']) : 0,
        'nome' => $cadNome,
        'cpf' => $cadCpf,
        'pae_nome' => null,
        'pae_cpf' => null,
        'motivo_label' => null,
        'motivo' => null
    ];

    // 2. Busca registros de auditoria para este aluno
    $temDeliberacaoAuditoria = false;
    $temArquivamentoAuditoria = false;
    $temAssociacaoAuditoria = false;

    if ($idAluno > 0) {
        $sqlAudit = "
            SELECT id_auditoria, id_usuario, usuario_nome, usuario_cpf, usuario_perfil, acao, detalhes, criado_em
            FROM auditoria
            WHERE entidade = 'alunos' AND id_registro = $idAluno
            ORDER BY criado_em ASC, id_auditoria ASC
        ";
        $resAudit = mysqli_query($conexao, $sqlAudit);
        if ($resAudit) {
            while ($aud = mysqli_fetch_assoc($resAudit)) {
                $acao = strtoupper(trim($aud['acao'] ?? ''));
                $det = json_decode($aud['detalhes'] ?? '', true);
                if (!is_array($det)) {
                    $det = [];
                }

                $autorNome = trim($aud['usuario_nome'] ?? '') ?: 'Usuário';
                $autorCpfRaw = preg_replace('/\D/', '', $aud['usuario_cpf'] ?? '');
                $autorCpf = $autorCpfRaw !== ''
                    ? formatarCPF($autorCpfRaw)
                    : $resolverCpfUsuario($autorNome, (int) ($aud['id_usuario'] ?? 0), $aud['usuario_perfil'] ?? '');

                $dataBr = !empty($aud['criado_em']) ? date('d/m/Y', strtotime($aud['criado_em'])) : '-';
                $ts = !empty($aud['criado_em']) ? strtotime($aud['criado_em']) : 0;

                if ($acao === 'DELIBERAR' || $acao === 'APROVAR') {
                    $temDeliberacaoAuditoria = true;
                    $st = strtoupper(trim($det['status'] ?? ($acao === 'APROVAR' ? 'APROVADO' : '')));
                    $motivo = trim($det['motivo'] ?? '');

                    if ($st === 'APROVADO') {
                        $eventos[] = [
                            'titulo' => 'Atendimento Aprovado',
                            'data' => $dataBr,
                            'timestamp' => $ts,
                            'nome' => $autorNome,
                            'cpf' => $autorCpf,
                            'pae_nome' => null,
                            'pae_cpf' => null,
                            'motivo_label' => null,
                            'motivo' => null
                        ];
                    } elseif ($st === 'PENDENTE_CORRECAO') {
                        $eventos[] = [
                            'titulo' => 'Ajustes Solicitados',
                            'data' => $dataBr,
                            'timestamp' => $ts,
                            'nome' => $autorNome,
                            'cpf' => $autorCpf,
                            'pae_nome' => null,
                            'pae_cpf' => null,
                            'motivo_label' => 'Orientações de Correção:',
                            'motivo' => $motivo !== '' ? $motivo : null
                        ];
                    } elseif ($st === 'REPROVADO') {
                        $eventos[] = [
                            'titulo' => 'Solicitação Recusada',
                            'data' => $dataBr,
                            'timestamp' => $ts,
                            'nome' => $autorNome,
                            'cpf' => $autorCpf,
                            'pae_nome' => null,
                            'pae_cpf' => null,
                            'motivo_label' => 'Motivo:',
                            'motivo' => $motivo !== '' ? $motivo : null
                        ];
                    }
                } elseif ($acao === 'REENVIAR' || ($acao === 'EDITAR' && ($det['status_pos_edicao'] ?? '') === 'PENDENTE' && isset($det['status_anterior']))) {
                    $camposAlt = trim($det['campos_alterados'] ?? '');
                    $eventos[] = [
                        'titulo' => 'Solicitação Reenviada para Análise',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => $camposAlt !== '' ? 'Alterações:' : null,
                        'motivo' => $camposAlt !== '' ? $camposAlt : null
                    ];
                } elseif ($acao === 'EDITAR') {
                    $camposAlt = trim($det['campos_alterados'] ?? '');
                    $eventos[] = [
                        'titulo' => 'Dados Cadastrais Atualizados',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => $camposAlt !== '' ? 'Campos alterados:' : null,
                        'motivo' => $camposAlt !== '' ? $camposAlt : null
                    ];
                } elseif ($acao === 'ASSOCIAR_PAE') {
                    $temAssociacaoAuditoria = true;
                    $paeNome = trim($det['pae_nome'] ?? '') ?: 'Profissional de Apoio';
                    $paeCpfRaw = preg_replace('/\D/', '', $det['pae_cpf'] ?? '');
                    $paeCpf = $paeCpfRaw !== '' ? formatarCPF($paeCpfRaw) : $resolverCpfUsuario($paeNome, (int) ($det['id_pae'] ?? 0), 'PAE');
                    $eventos[] = [
                        'titulo' => 'PAE Associado ao Aluno',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'id_pae' => (int) ($det['id_pae'] ?? 0),
                        'pae_nome' => $paeNome,
                        'pae_cpf' => $paeCpf,
                        'motivo_label' => null,
                        'motivo' => null
                    ];
                } elseif ($acao === 'DESASSOCIAR_PAE') {
                    $temAssociacaoAuditoria = true;
                    $paeNome = trim($det['pae_nome'] ?? '') ?: 'Profissional de Apoio';
                    $paeCpfRaw = preg_replace('/\D/', '', $det['pae_cpf'] ?? '');
                    $paeCpf = $paeCpfRaw !== '' ? formatarCPF($paeCpfRaw) : $resolverCpfUsuario($paeNome, (int) ($det['id_pae'] ?? 0), 'PAE');
                    $eventos[] = [
                        'titulo' => 'PAE Desassociado do Aluno',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'id_pae' => (int) ($det['id_pae'] ?? 0),
                        'pae_nome' => $paeNome,
                        'pae_cpf' => $paeCpf,
                        'motivo_label' => null,
                        'motivo' => null
                    ];
                } elseif ($acao === 'ANEXAR_DOCUMENTO') {
                    $arqs = trim($det['arquivos'] ?? '');
                    $eventos[] = [
                        'titulo' => 'Documento Anexado',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => $arqs !== '' ? 'Arquivo(s):' : null,
                        'motivo' => $arqs !== '' ? $arqs : null
                    ];
                } elseif ($acao === 'REMOVER_DOCUMENTO') {
                    $arq = trim($det['arquivo'] ?? '');
                    $eventos[] = [
                        'titulo' => 'Documento Removido',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => $arq !== '' ? 'Arquivo:' : null,
                        'motivo' => $arq !== '' ? $arq : null
                    ];
                } elseif ($acao === 'REGISTRAR_RELATORIO') {
                    $tpRel = trim($det['tipo'] ?? 'Atendimento');
                    $eventos[] = [
                        'titulo' => 'Relatório de Atendimento Registrado',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => 'Tipo:',
                        'motivo' => 'Relatório ' . $tpRel
                    ];
                } elseif ($acao === 'ARQUIVAR') {
                    $temArquivamentoAuditoria = true;
                    $motivoArq = trim($det['motivo'] ?? '');
                    $eventos[] = [
                        'titulo' => 'Aluno Arquivado',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => $motivoArq !== '' ? 'Motivo:' : null,
                        'motivo' => $motivoArq !== '' ? $motivoArq : null
                    ];
                } elseif ($acao === 'REATIVAR') {
                    $eventos[] = [
                        'titulo' => 'Aluno Reativado',
                        'data' => $dataBr,
                        'timestamp' => $ts,
                        'nome' => $autorNome,
                        'cpf' => $autorCpf,
                        'pae_nome' => null,
                        'pae_cpf' => null,
                        'motivo_label' => null,
                        'motivo' => null
                    ];
                }
            }
        }
    }

    // 2.1 Fallback para associações existentes na tabela `associacoes` caso não tenham log em `auditoria`
    if (!$temAssociacaoAuditoria && $idAluno > 0) {
        $sqlAssocHist = "
            SELECT ass.id_associacao, ass.id_pae, ass.ativo, ass.data_inicio, ass.data_fim,
                   p.nome AS pae_nome, p.cpf AS pae_cpf, p.id_empresa,
                   sup.nome AS sup_nome, sup.cpf AS sup_cpf
            FROM associacoes ass
            JOIN usuarios_pae p ON ass.id_pae = p.id_pae
            LEFT JOIN usuarios_supervisor sup ON p.id_empresa = sup.id_empresa AND sup.ativo = 1
            WHERE ass.id_aluno = $idAluno
            ORDER BY ass.data_inicio ASC, ass.id_associacao ASC
        ";
        $resAssocHist = mysqli_query($conexao, $sqlAssocHist);
        if ($resAssocHist) {
            while ($assRow = mysqli_fetch_assoc($resAssocHist)) {
                $respNome = trim($assRow['sup_nome'] ?? '') ?: 'Supervisor da Empresa';
                $respCpfRaw = preg_replace('/\D/', '', $assRow['sup_cpf'] ?? '');
                $respCpf = $respCpfRaw !== '' ? formatarCPF($respCpfRaw) : 'Não informado';
                $paeNome = trim($assRow['pae_nome'] ?? '');
                $paeCpf = formatarCPF($assRow['pae_cpf'] ?? '');

                $dtIni = !empty($assRow['data_inicio']) ? date('d/m/Y', strtotime($assRow['data_inicio'])) : '-';
                $tsIni = !empty($assRow['data_inicio']) ? strtotime($assRow['data_inicio'] . ' 12:00:00') : 0;

                $eventos[] = [
                    'titulo' => 'PAE Associado ao Aluno',
                    'data' => $dtIni,
                    'timestamp' => $tsIni,
                    'nome' => $respNome,
                    'cpf' => $respCpf,
                    'id_pae' => (int) ($assRow['id_pae'] ?? 0),
                    'pae_nome' => $paeNome,
                    'pae_cpf' => $paeCpf,
                    'motivo_label' => null,
                    'motivo' => null
                ];

                if ((int) ($assRow['ativo'] ?? 1) === 0 && !empty($assRow['data_fim'])) {
                    $dtFim = date('d/m/Y', strtotime($assRow['data_fim']));
                    $tsFim = strtotime($assRow['data_fim'] . ' 18:00:00');
                    $eventos[] = [
                        'titulo' => 'PAE Desassociado do Aluno',
                        'data' => $dtFim,
                        'timestamp' => $tsFim,
                        'nome' => $respNome,
                        'cpf' => $respCpf,
                        'id_pae' => (int) ($assRow['id_pae'] ?? 0),
                        'pae_nome' => $paeNome,
                        'pae_cpf' => $paeCpf,
                        'motivo_label' => null,
                        'motivo' => null
                    ];
                }
            }
        }
    }

    // 3. Fallback para dados diretos na tabela `alunos` caso não haja log em `auditoria` (registros anteriores/seed)
    if (!$temDeliberacaoAuditoria && !empty($aluno['data_deliberacao'])) {
        $delibNome = trim($aluno['deliberado_por_nome'] ?? '') ?: 'Educação Especial';
        $delibCpfRaw = preg_replace('/\D/', '', $aluno['deliberado_por_cpf'] ?? '');
        $delibCpf = $delibCpfRaw !== '' ? formatarCPF($delibCpfRaw) : $resolverCpfUsuario($delibNome, 0, 'USUARIO_EDUCACAO_ESPECIAL');
        $dataDelibBr = date('d/m/Y', strtotime($aluno['data_deliberacao']));
        $tsDelib = strtotime($aluno['data_deliberacao']);
        $stAtual = $aluno['status_aprovacao'] ?? '';

        if ($stAtual === 'PENDENTE_CORRECAO') {
            $eventos[] = [
                'titulo' => 'Ajustes Solicitados',
                'data' => $dataDelibBr,
                'timestamp' => $tsDelib,
                'nome' => $delibNome,
                'cpf' => $delibCpf,
                'motivo_label' => !empty($aluno['motivo_reprovacao']) ? 'Orientações de Correção:' : null,
                'motivo' => !empty($aluno['motivo_reprovacao']) ? $aluno['motivo_reprovacao'] : null
            ];
        } elseif ($stAtual === 'REPROVADO') {
            $eventos[] = [
                'titulo' => 'Solicitação Recusada',
                'data' => $dataDelibBr,
                'timestamp' => $tsDelib,
                'nome' => $delibNome,
                'cpf' => $delibCpf,
                'motivo_label' => !empty($aluno['motivo_reprovacao']) ? 'Motivo:' : null,
                'motivo' => !empty($aluno['motivo_reprovacao']) ? $aluno['motivo_reprovacao'] : null
            ];
        } elseif ($stAtual === 'APROVADO' || $stAtual === 'ARQUIVADO') {
            $eventos[] = [
                'titulo' => 'Atendimento Aprovado',
                'data' => $dataDelibBr,
                'timestamp' => $tsDelib,
                'nome' => $delibNome,
                'cpf' => $delibCpf,
                'motivo_label' => null,
                'motivo' => null
            ];
        } elseif ($stAtual === 'PENDENTE') {
            // Se já teve data_deliberacao e está PENDENTE sem auditoria, houve reprovação/ajuste anterior e posterior reenvio
            $eventos[] = [
                'titulo' => 'Solicitação Analisada Anteriormente',
                'data' => $dataDelibBr,
                'timestamp' => $tsDelib,
                'nome' => $delibNome,
                'cpf' => $delibCpf,
                'motivo_label' => null,
                'motivo' => null
            ];
            $eventos[] = [
                'titulo' => 'Solicitação Reenviada para Análise',
                'data' => $dataDelibBr,
                'timestamp' => $tsDelib + 1,
                'nome' => $cadNome,
                'cpf' => $cadCpf,
                'motivo_label' => null,
                'motivo' => null
            ];
        }
    }

    if (!$temArquivamentoAuditoria && ($aluno['status_aprovacao'] ?? '') === 'ARQUIVADO' && !empty($aluno['data_arquivamento'])) {
        $arqNome = trim($aluno['arquivado_por_nome'] ?? '') ?: 'Usuário';
        $arqCpfRaw = preg_replace('/\D/', '', $aluno['arquivado_por_cpf'] ?? '');
        $arqCpf = $arqCpfRaw !== '' ? formatarCPF($arqCpfRaw) : $resolverCpfUsuario($arqNome, 0, $aluno['arquivado_por_perfil'] ?? '');
        $eventos[] = [
            'titulo' => 'Aluno Arquivado',
            'data' => date('d/m/Y', strtotime($aluno['data_arquivamento'])),
            'timestamp' => strtotime($aluno['data_arquivamento']),
            'nome' => $arqNome,
            'cpf' => $arqCpf,
            'motivo_label' => !empty($aluno['motivo_arquivamento']) ? 'Motivo:' : null,
            'motivo' => !empty($aluno['motivo_arquivamento']) ? $aluno['motivo_arquivamento'] : null
        ];
    }

    // Se foi informado filtro de PAE (ex: PAE logado visualizando a ficha), filtra para ver apenas quando foi associado a ele
    if ($filtroPaeId > 0) {
        $eventos = array_values(array_filter($eventos, function($ev) use ($filtroPaeId) {
            // Mostra apenas o evento de associação (ou desassociação) referente a esse PAE
            if (isset($ev['id_pae'])) {
                return (int) $ev['id_pae'] === $filtroPaeId;
            }
            return false;
        }));
    }

    // Ordena cronologicamente
    usort($eventos, function($a, $b) {
        return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
    });

    return $eventos;
}

/**
 * Retorna a URL de um arquivo estático local (CSS/JS) com versionamento automático baseado em filemtime().
 * Garante que alterações nos arquivos atualizem automaticamente a URL para invalidar o cache do navegador.
 */
function assetUrl($caminhoRelativo, $prefixoUrl = '') {
    $caminhoRelativoLimpo = preg_replace('/\?.*$/', '', ltrim(str_replace('\\', '/', $caminhoRelativo), './'));
    $caminhoFisico = dirname(__DIR__) . '/' . $caminhoRelativoLimpo;
    $versao = is_file($caminhoFisico) ? @filemtime($caminhoFisico) : false;
    $url = $prefixoUrl . $caminhoRelativoLimpo;
    return $versao ? ($url . '?v=' . $versao) : $url;
}

?>