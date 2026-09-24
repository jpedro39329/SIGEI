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

        // Solicitações pendentes há mais de 3 dias (urgentes)
        $sqlUrgentes = "
            SELECT a.id_aluno, a.nome, DATEDIFF(NOW(), a.data_cadastro) AS dias_pendente
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE a.status_aprovacao = 'PENDENTE' AND DATEDIFF(NOW(), a.data_cadastro) >= 3 $whereUre
            ORDER BY a.data_cadastro ASC LIMIT 3
        ";
        $resUrgentes = mysqli_query($conexao, $sqlUrgentes);
        if ($resUrgentes) {
            while ($row = mysqli_fetch_assoc($resUrgentes)) {
                $dias = (int) $row['dias_pendente'];
                $notificacoes[] = [
                    'tipo' => 'danger',
                    'icone' => 'bi-clock-history',
                    'titulo' => 'Solicitação Pendente Crítica',
                    'mensagem' => "Aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> aguarda análise há {$dias} dias.",
                    'link' => $baseUrl . "views/solicitacoes/analisar.php?id=" . $row['id_aluno'],
                    'tempo' => "Há {$dias} dias"
                ];
            }
        }

        // Novas solicitações recentes (menos de 3 dias)
        $sqlRecentes = "
            SELECT COUNT(*) AS total
            FROM alunos a
            JOIN unidades_escolares ue ON a.id_ue = ue.id_ue
            WHERE a.status_aprovacao = 'PENDENTE' AND DATEDIFF(NOW(), a.data_cadastro) < 3 $whereUre
        ";
        $resRec = mysqli_query($conexao, $sqlRecentes);
        $totalRec = $resRec ? (int) mysqli_fetch_assoc($resRec)['total'] : 0;
        if ($totalRec > 0) {
            $notificacoes[] = [
                'tipo' => 'warning',
                'icone' => 'bi-inbox-fill',
                'titulo' => 'Novas Solicitações',
                'mensagem' => "Você possui <strong>{$totalRec}</strong> solicitação(ões) recente(s) para analisar.",
                'link' => $baseUrl . "views/solicitacoes/listar.php",
                'tempo' => "Fila da URE"
            ];
        }
    }

    // 2. UNIDADE ESCOLAR (USUARIO_ESCOLA / UE)
    elseif (in_array($perfil, ['USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA'])) {
        $idEscola = idEscolaUsuario($conexao, $userId);

        // Alunos aprovados recentemente pela Educação Especial
        $sqlAprovados = "
            SELECT id_aluno, nome
            FROM alunos
            WHERE id_ue = $idEscola AND status_aprovacao = 'APROVADO'
            ORDER BY data_cadastro DESC LIMIT 2
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
                    'tempo' => "Aprovado"
                ];
            }
        }

        // Alunos reprovados que precisam de atenção/ajuste
        $sqlReprovados = "
            SELECT id_aluno, nome, motivo_reprovacao
            FROM alunos
            WHERE id_ue = $idEscola AND status_aprovacao = 'REPROVADO'
            ORDER BY data_cadastro DESC LIMIT 2
        ";
        $resRep = mysqli_query($conexao, $sqlReprovados);
        if ($resRep) {
            while ($row = mysqli_fetch_assoc($resRep)) {
                $notificacoes[] = [
                    'tipo' => 'danger',
                    'icone' => 'bi-x-circle-fill',
                    'titulo' => 'Solicitação Reprovada',
                    'mensagem' => "Aluno <strong>" . htmlspecialchars($row['nome']) . "</strong> foi reprovado. Verifique os motivos.",
                    'link' => $baseUrl . "views/alunos/pendentes.php?aba=reprovados",
                    'tempo' => "Atenção"
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

    return $notificacoes;
}

?>