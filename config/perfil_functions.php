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
    return in_array($perfil, $perfisPermitidos);
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
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) === 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

// Formata CNPJ para exibição (00.000.000/0000-00)
function formatarCNPJ($cnpj) {
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpj) === 14) {
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }
    return $cnpj;
}

// Formata CEP para exibição (00000-000)
function formatarCEP($cep) {
    $cep = preg_replace('/\D/', '', $cep);
    if (strlen($cep) === 8) {
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
    return $cep;
}

// Retorna o nome amigável do perfil
function nomePerfil($perfil) {
    $nomes = array(
        'ADMIN'                    => 'Administrador',
        'USUARIO_ESCOLA'           => 'Unidade Escolar',
        'USUARIO_EMPRESA'          => 'Empresa Terceirizada',
        'PAE'                      => 'Profissional de Apoio Escolar',
        'USUARIO_SEFISC'           => 'Seção de Fiscalização',
        'USUARIO_EDUCACAO_ESPECIAL'=> 'Educação Especial'
    );
    return $nomes[$perfil] ?? $perfil;
}

// Retorna o nome da escola do usuário logado (perfil USUARIO_ESCOLA)
function nomeEscolaUsuario($conexao, $userId) {
    $query = "SELECT e.nome FROM usuarios_escola ue
              JOIN escolas e ON ue.id_escola = e.id_escola
              WHERE ue.id_usuario_escola = $userId";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['nome'] ?? '';
}

// Retorna o nome da empresa do usuário logado (perfil USUARIO_EMPRESA)
function nomeEmpresaUsuario($conexao, $userId) {
    $query = "SELECT e.nome FROM usuarios_empresa ue
              JOIN empresas e ON ue.id_empresa = e.id_empresa
              WHERE ue.id_usuario_empresa = $userId";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['nome'] ?? '';
}

// Retorna o id_escola do usuário logado (perfil USUARIO_ESCOLA)
function idEscolaUsuario($conexao, $userId) {
    $query = "SELECT id_escola FROM usuarios_escola WHERE id_usuario_escola = $userId";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['id_escola'] ?? 0;
}

// Retorna o id_empresa do usuário logado (perfil USUARIO_EMPRESA)
function idEmpresaUsuario($conexao, $userId) {
    $query = "SELECT id_empresa FROM usuarios_empresa WHERE id_usuario_empresa = $userId";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['id_empresa'] ?? 0;
}

// Conta quantos alunos ativos um PAE possui
function contarAlunosPAE($conexao, $idPae) {
    $query = "SELECT COUNT(*) AS total FROM associacoes WHERE id_pae = $idPae AND ativo = 1";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

// Conta quantos PAEs ativos um aluno possui
function contarPAEsAluno($conexao, $idAluno) {
    $query = "SELECT COUNT(*) AS total FROM associacoes WHERE id_aluno = $idAluno AND ativo = 1";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

// Busca o PAE associado a um aluno (nome)
function nomePAEAluno($conexao, $idAluno) {
    $query = "SELECT p.nome FROM associacoes ass
              JOIN paes p ON ass.id_pae = p.id_pae
              WHERE ass.id_aluno = $idAluno AND ass.ativo = 1
              LIMIT 1";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['nome'] ?? 'Sem PAE';
}

?>