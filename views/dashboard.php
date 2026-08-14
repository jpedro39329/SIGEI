<?php
session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName  = $_SESSION['user_name'];
$userPerfil = $_SESSION['user_perfil'];
$userId    = $_SESSION['user_id'];

// ==================================================================
// 1. DEFINIÇÃO DAS CONSULTAS CONFORME PERFIL
// ==================================================================

// --- Alunos em atendimento (APROVADO + associação ativa) ---
$sqlAtendimento = "";
$sqlPendentes   = "";

switch ($userPerfil) {
    case 'ADMIN':
        // Admin: todos os alunos aprovados com cuidador (dados completos)
        $sqlAtendimento = "
            SELECT 
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                escola.nome AS escola_nome,
                cuidador.nome AS cuidador_nome,
                cuidador.cpf AS cuidador_cpf
            FROM alunos a
            JOIN associacoes ass ON a.id_aluno = ass.id_aluno AND ass.ativo = 1
            JOIN usuarios cuidador ON ass.id_cuidador = cuidador.id_usuario
            JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
            WHERE a.status_aprovacao = 'APROVADO'
            ORDER BY a.nome
        ";
        // Admin vê todos os pendentes (sem cuidador)
        $sqlPendentes = "
            SELECT 
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                escola.nome AS escola_nome
            FROM alunos a
            JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
            WHERE a.status_aprovacao = 'PENDENTE'
            ORDER BY a.data_cadastro DESC
        ";
        break;

    case 'UNIDADE_ESCOLAR':
        // Unidade Escolar: só vê os alunos da própria escola
        $sqlAtendimento = "
            SELECT 
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                escola.nome AS escola_nome,
                cuidador.nome AS cuidador_nome
            FROM alunos a
            JOIN associacoes ass ON a.id_aluno = ass.id_aluno AND ass.ativo = 1
            JOIN usuarios cuidador ON ass.id_cuidador = cuidador.id_usuario
            JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
            WHERE a.status_aprovacao = 'APROVADO'
              AND a.id_usuario_escola = $userId
            ORDER BY a.nome
        ";
        $sqlPendentes = "
            SELECT 
                a.nome AS aluno_nome,
                a.cpf AS aluno_cpf,
                escola.nome AS escola_nome
            FROM alunos a
            JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
            WHERE a.status_aprovacao = 'PENDENTE'
              AND a.id_usuario_escola = $userId
            ORDER BY a.data_cadastro DESC
        ";
        break;

    case 'EDUCACAO_ESPECIAL':
    case 'SETOR_FISCALIZACAO':
        // Educação Especial e Fiscalização: todos os alunos aprovados e pendentes
        // (apenas nome do cuidador, sem CPF)
        $sqlAtendimento = "
            SELECT 
                a.nome AS aluno_nome,
                escola.nome AS escola_nome,
                cuidador.nome AS cuidador_nome
            FROM alunos a
            JOIN associacoes ass ON a.id_aluno = ass.id_aluno AND ass.ativo = 1
            JOIN usuarios cuidador ON ass.id_cuidador = cuidador.id_usuario
            JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
            WHERE a.status_aprovacao = 'APROVADO'
            ORDER BY a.nome
        ";
        $sqlPendentes = "
            SELECT 
                a.nome AS aluno_nome,
                escola.nome AS escola_nome
            FROM alunos a
            JOIN usuarios escola ON a.id_usuario_escola = escola.id_usuario
            WHERE a.status_aprovacao = 'PENDENTE'
            ORDER BY a.data_cadastro DESC
        ";
        break;

    case 'EMPRESA_TERCEIRIZADA':
    // Empresa: alunos associados a cuidadores da própria empresa

    // Obtém o nome da empresa do usuário logado
    $sqlEmpresa = "SELECT empresa FROM usuarios WHERE id_usuario = $userId";
    $resEmpresa = mysqli_query($conexao, $sqlEmpresa);

    $empresaNome = mysqli_fetch_assoc($resEmpresa)['empresa'];
    $empresaNome = mysqli_real_escape_string($conexao, $empresaNome);

    $sqlAtendimento = "
        SELECT DISTINCT
            a.nome AS aluno_nome,
            a.deficiencia AS aluno_deficiencia,
            cuidador.nome AS cuidador_nome
        FROM alunos a
        JOIN associacoes ass 
            ON a.id_aluno = ass.id_aluno 
           AND ass.ativo = 1
        JOIN usuarios cuidador 
            ON ass.id_cuidador = cuidador.id_usuario
        WHERE a.status_aprovacao = 'APROVADO'
          AND cuidador.empresa = '$empresaNome'
        ORDER BY a.nome
    ";

    // Empresa não vê alunos pendentes
    $sqlPendentes = "";
    break;

    case 'CUIDADOR':
        // Cuidador: apenas os alunos diretamente associados a ele
        $sqlAtendimento = "
            SELECT 
                a.nome AS aluno_nome,
                a.deficiencia AS aluno_deficiencia
            FROM alunos a
            JOIN associacoes ass ON a.id_aluno = ass.id_aluno AND ass.ativo = 1
            WHERE a.status_aprovacao = 'APROVADO'
              AND ass.id_cuidador = $userId
            ORDER BY a.nome
        ";
        $sqlPendentes = "";
        break;

    default:
        // Perfil desconhecido: não mostra nada
        $sqlAtendimento = "";
        $sqlPendentes = "";
        break;
}

// Executa as consultas (se houver)
$alunosAtendimento = [];
if (!empty($sqlAtendimento)) {
    $resultAtendimento = mysqli_query($conexao, $sqlAtendimento);
    if ($resultAtendimento) {
        $alunosAtendimento = mysqli_fetch_all($resultAtendimento, MYSQLI_ASSOC);
    }
}

$alunosPendentes = [];
if (!empty($sqlPendentes)) {
    $resultPendentes = mysqli_query($conexao, $sqlPendentes);
    if ($resultPendentes) {
        $alunosPendentes = mysqli_fetch_all($resultPendentes, MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel SIGEI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-dashboard">

<div class="d-flex flex-wrap flex-md-nowrap">
    <!-- Sidebar -->   

    <?php require("navbar.php"); ?>    

    <!-- Conteúdo principal -->
    <div class="content">
        
        <!-- ========== SEÇÃO: ALUNOS EM ATENDIMENTO ========== -->
        <?php if (!empty($alunosAtendimento)): ?>
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body p-4">
                <h5 class="card-title">📋 Alunos em atendimento</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-custom">
                            <?php if ($userPerfil == 'ADMIN'): ?>
                                <tr>
                                    <th>Nome</th><th>CPF</th><th>Escola</th><th>Cuidador</th><th>CPF do Cuidador</th>
                                </tr>
                            <?php elseif ($userPerfil == 'UNIDADE_ESCOLAR'): ?>
                                <tr>
                                    <th>Nome</th><th>CPF</th><th>Escola</th><th>Cuidador</th>
                                </tr>
                            <?php elseif (in_array($userPerfil, ['EDUCACAO_ESPECIAL', 'SETOR_FISCALIZACAO'])): ?>
                                <tr>
                                    <th>Nome</th><th>Escola</th><th>Cuidador</th>
                                </tr>
                            <?php elseif ($userPerfil == 'EMPRESA_TERCEIRIZADA'): ?>
                                <tr>
                                    <th>Nome do Aluno</th><th>Deficiência</th><th>Cuidador</th>
                                </tr>
                            <?php elseif ($userPerfil == 'CUIDADOR'): ?>
                                <tr>
                                    <th>Nome do Aluno</th><th>Deficiência</th>
                                </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php foreach ($alunosAtendimento as $aluno): ?>
                                <tr>
                                    <?php if ($userPerfil == 'ADMIN'): ?>
                                        <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['aluno_cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['cuidador_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['cuidador_cpf']); ?></td>
                                    <?php elseif ($userPerfil == 'UNIDADE_ESCOLAR'): ?>
                                        <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['aluno_cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['cuidador_nome']); ?></td>
                                    <?php elseif (in_array($userPerfil, ['EDUCACAO_ESPECIAL', 'SETOR_FISCALIZACAO'])): ?>
                                        <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['escola_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['cuidador_nome']); ?></td>
                                    <?php elseif ($userPerfil == 'EMPRESA_TERCEIRIZADA'): ?>
                                        <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['aluno_deficiencia']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['cuidador_nome']); ?></td>
                                    <?php elseif ($userPerfil == 'CUIDADOR'): ?>
                                        <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aluno['aluno_deficiencia']); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-2">
                    <button class="btn-ver-mais">Ver mais →</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========== SEÇÃO: ALUNOS PENDENTES ========== -->
        <?php if (!empty($alunosPendentes) && in_array($userPerfil, ['ADMIN','UNIDADE_ESCOLAR','EDUCACAO_ESPECIAL','SETOR_FISCALIZACAO'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title">⏳ Alunos Pendentes</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-custom">
                            <tr>
                                <th>Nome</th>
                                <?php if (in_array($userPerfil, ['ADMIN', 'UNIDADE_ESCOLAR'])): ?>
                                    <th>CPF</th>
                                <?php endif; ?>
                                <th>Escola</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunosPendentes as $aluno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                    <?php if (in_array($userPerfil, ['ADMIN', 'UNIDADE_ESCOLAR'])): ?>
                                        <td><?php echo htmlspecialchars($aluno['aluno_cpf']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($aluno['escola_nome']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-2">
                    <button class="btn-ver-mais">Ver mais →</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <footer>
            SIGEI – Sistema de Gestão Integrada | Painel colaborativo
        </footer>
    </div>
</div>

</body>
</html>
