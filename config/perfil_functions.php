<?php

// ============================================================
// FUNÇÕES AUXILIARES DE PERMISSÃO
// ============================================================

// Verifica se o usuário está logado
function estaLogado() {
    return isset($_SESSION['user_id']);
}

// Verifica se o usuário tem um dos perfis permitidos
function temPermissao($perfil, $perfisPermitidos) {
    if (in_array($perfil, $perfisPermitidos)) {
        return true;
    }
    // Compatibilidade entre sinônimos de perfil
    if (in_array('USUARIO_ESCOLA', $perfisPermitidos) && in_array($perfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
        return true;
    }
    if (in_array('SUPERVISOR', $perfisPermitidos) && in_array($perfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
        return true;
    }
    if (in_array('USUARIO_EMPRESA', $perfisPermitidos) && in_array($perfil, ['SUPERVISOR', 'USUARIO_EMPRESA'])) {
        return true;
    }
    return false;
}

// Retorna o caminho correto da página de login (views/ ou controllers/)
function urlLogin() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = strtolower(basename(dirname($script)));

    if ($dir === 'controllers') {
        return '../views/login.php';
    }

    return 'login.php';
}

// Redireciona para o login se não estiver logado
function exigirLogin() {
    if (!estaLogado()) {
        header("Location: " . urlLogin());
        exit();
    }
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
        die("Acesso negado.");
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

// Retorna o nome amigável do perfil
function nomePerfil($perfil) {
    $nomes = array(
        'ADMIN'                     => 'Administrador do Sistema',
        'SEDUC'                     => 'SEDUC-SP (Administração Geral)',
        'DIRIGENTE'                 => 'Coordenador Dirigente Regional de Ensino (URE)',
        'USUARIO_SEFISC'            => 'Seção de Fiscalização (SEFISC - URE)',
        'USUARIO_EDUCACAO_ESPECIAL' => 'Educação Especial (URE)',
        'SUPERVISOR'                => 'Supervisor da Empresa Contratada',
        'USUARIO_EMPRESA'           => 'Supervisor da Empresa Contratada',
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

?>