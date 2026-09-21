<?php
require_once "../config/init.php";

$perfil = trim($_GET['perfil'] ?? '');

$perfisDisponiveis = [
    'admin' => [
        'nome' => 'ADMIN',
        'desc' => 'Administrador Geral do Sistema',
        'tabela' => 'admin',
        'campo_id' => 'id_admin',
        'filtro_setor' => null
    ],
    'seduc' => [
        'nome' => 'SEDUC',
        'desc' => 'Órgão Central - SEDUC-SP',
        'tabela' => 'seduc',
        'campo_id' => 'id_seduc',
        'filtro_setor' => null
    ],
    'dirigente' => [
        'nome' => 'URE / DIRIGENTE',
        'desc' => 'Gabinete e Dirigência Regional de Ensino',
        'tabela' => 'usuarios_ure',
        'campo_id' => 'id_usuario_ure',
        'filtro_setor' => 'GABINETE'
    ],
    'educacao_especial' => [
        'nome' => 'EDUCAÇÃO ESPECIAL',
        'desc' => 'PEC - Equipe da Educação Especial (EEC)',
        'tabela' => 'usuarios_ure',
        'campo_id' => 'id_usuario_ure',
        'filtro_setor' => 'EDU_ESPECIAL'
    ],
    'sefisc' => [
        'nome' => 'SEFISC',
        'desc' => 'Setor de Fiscalização da Regional',
        'tabela' => 'usuarios_ure',
        'campo_id' => 'id_usuario_ure',
        'filtro_setor' => 'SEFISC'
    ],
    'escola' => [
        'nome' => 'ESCOLA (UE)',
        'desc' => 'Equipe Gestora da Unidade Escolar',
        'tabela' => 'usuarios_ue',
        'campo_id' => 'id_usuario_ue',
        'filtro_setor' => null
    ],
    'supervisor' => [
        'nome' => 'SUPERVISOR',
        'desc' => 'Supervisor da Empresa Contratada',
        'tabela' => 'usuarios_supervisor',
        'campo_id' => 'id_usuario_supervisor',
        'filtro_setor' => null
    ],
    'pae' => [
        'nome' => 'PAE',
        'desc' => 'Profissional de Apoio Escolar',
        'tabela' => 'usuarios_pae',
        'campo_id' => 'id_pae',
        'filtro_setor' => null
    ]
];

$usuarios = [];
$perfilConfig = null;

if ($perfil !== '') {
    if (!isset($perfisDisponiveis[$perfil])) {
        header("Location: acesso_rapido.php?erro=perfil_invalido");
        exit();
    }

    $perfilConfig = $perfisDisponiveis[$perfil];
    $tabela = $perfilConfig['tabela'];
    $campoId = $perfilConfig['campo_id'];
    $filtroSetor = $perfilConfig['filtro_setor'];

    if ($tabela === 'admin') {
        $sql = "SELECT id_admin, nome, 'Administrador Geral' AS vinculo_nome, '' AS setor, '' AS cargo, '' AS extra FROM `admin`";
    } elseif ($tabela === 'seduc') {
        $sql = "SELECT id_seduc, nome, 'Órgão Central' AS vinculo_nome, setor, cargo, '' AS extra FROM `seduc` WHERE ativo = 1";
    } elseif ($tabela === 'usuarios_ure') {
        if ($filtroSetor !== null) {
            $sql = "SELECT uu.id_usuario_ure, uu.nome, u.nome AS vinculo_nome, uu.setor, uu.cargo, '' AS extra 
                    FROM usuarios_ure uu
                    LEFT JOIN unidades_regionais u ON uu.id_ure = u.id_ure
                    WHERE uu.setor = '" . $conexao->real_escape_string($filtroSetor) . "' AND uu.ativo = 1";
        } else {
            $sql = "SELECT uu.id_usuario_ure, uu.nome, u.nome AS vinculo_nome, uu.setor, uu.cargo, '' AS extra 
                    FROM usuarios_ure uu
                    LEFT JOIN unidades_regionais u ON uu.id_ure = u.id_ure
                    WHERE uu.ativo = 1";
        }
    } elseif ($tabela === 'usuarios_supervisor') {
        $sql = "SELECT us.id_usuario_supervisor, us.nome, e.nome AS vinculo_nome, '' AS setor, 'Supervisor' AS cargo, CONCAT('CNPJ: ', e.cnpj) AS extra 
                FROM usuarios_supervisor us
                LEFT JOIN empresas e ON us.id_empresa = e.id_empresa
                WHERE us.ativo = 1";
    } elseif ($tabela === 'usuarios_ue') {
        $sql = "SELECT uue.id_usuario_ue, uue.nome, ue.nome AS vinculo_nome, '' AS setor, 'Gestor Escolar' AS cargo, CONCAT('CIE: ', ue.cie) AS extra 
                FROM usuarios_ue uue
                LEFT JOIN unidades_escolares ue ON uue.id_ue = ue.id_ue
                WHERE uue.ativo = 1";
    } elseif ($tabela === 'usuarios_pae') {
        $sql = "SELECT p.id_pae, p.nome, e.nome AS vinculo_nome, '' AS setor, 'PAE / Cuidador' AS cargo, '' AS extra 
                FROM usuarios_pae p
                LEFT JOIN empresas e ON p.id_empresa = e.id_empresa
                WHERE p.ativo = 1";
    }

    $res = $conexao->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }

    // Se tiver apenas 1 usuário, redireciona diretamente!
    if (count($usuarios) === 1) {
        $idUnico = (int) $usuarios[0][$campoId];
        header("Location: ../controllers/auth/acesso_rapido.php?tabela=" . urlencode($tabela) . "&id=" . $idUnico);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIGEI — Acesso Rápido de Desenvolvimento</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ===== RESET E BASE ===== */
    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Nunito', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #ddeeff;
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }

    .bg-svg {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      pointer-events: none;
    }

    .container {
      position: relative;
      z-index: 1;
      display: flex;
      width: 100%;
      min-height: 100vh;
      align-items: stretch;
    }

    /* ===== LADO ESQUERDO ===== */
    .caixa.esquerda {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      text-align: left;
      padding: 60px 80px 120px 160px;
      margin-top: -80px;
    }

    .logo-bloco {
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 20px;
    }

    .logo {
      width: 340px;
      height: auto;
      display: block;
      flex-shrink: 0;
    }

    .logo-texto h1 {
      color: #0d47a1;
      font-size: 4.8rem;
      font-weight: 800;
      margin: 0 0 6px 0;
      line-height: 1;
    }

    .logo-texto span {
      color: #1565c0;
      font-size: 1.3rem;
      font-weight: 600;
      display: block;
      line-height: 1.5;
    }

    .caixa.esquerda p {
      color: #1565c0;
      font-size: 1.25rem;
      line-height: 1.7;
      max-width: 600px;
      margin: 0;
    }

    .badge-dev {
      display: inline-block;
      background-color: #1a9e85;
      color: #ffffff;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin-bottom: 12px;
      text-transform: uppercase;
    }

    /* ===== LADO DIREITO ===== */
    .caixa.direita {
      flex: 0 0 33.333vw;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background-color: #ffffff;
      padding: 36px 36px;
      box-shadow: -6px 0 32px rgba(0, 0, 0, 0.08);
      max-height: 100vh;
      overflow-y: auto;
    }

    .section-label {
      color: #0d47a1;
      font-size: 1.6rem;
      font-weight: 800;
      text-align: center;
      margin: 0 0 2px 0;
    }

    .section-sub {
      color: #555;
      font-size: 0.88rem;
      text-align: center;
      margin: 0 0 16px 0;
    }

    .footer-note {
      color: #757575;
      font-size: 0.78rem;
      text-align: center;
      margin-top: 14px;
      margin-bottom: 0;
    }

    /* ===== LINKS DE PERFIL / USUÁRIOS ===== */
    .profile-link {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #f8fafc;
      padding: 10px 16px;
      margin-bottom: 8px;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
      border: 2px solid #e0e7ef;
    }

    .profile-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
      align-items: flex-start;
    }

    .profile-header-row {
      display: flex;
      gap: 8px;
      align-items: baseline;
      flex-wrap: wrap;
    }

    .profile-prefix {
      font-size: 0.85rem;
      color: #666666;
    }

    .profile-name {
      font-weight: 800;
      font-size: 1.15rem;
      color: #0d47a1;
    }

    .profile-desc {
      font-size: 0.82rem;
      color: #555555;
    }

    .profile-badge {
      font-size: 0.75rem;
      background-color: #e8f1ff;
      color: #0d47a1;
      padding: 2px 8px;
      border-radius: 6px;
      font-weight: 700;
      margin-top: 4px;
    }

    .arrow {
      color: #0d47a1;
      font-weight: bold;
      font-size: 1.2rem;
      transition: transform 0.2s ease;
      flex-shrink: 0;
      margin-left: 10px;
    }

    .profile-link:hover {
      background-color: #0d47a1;
      border-color: #0d47a1;
      transform: translateX(4px);
    }

    .profile-link:hover .profile-prefix,
    .profile-link:hover .profile-name,
    .profile-link:hover .profile-desc,
    .profile-link:hover .arrow {
      color: #ffffff;
    }

    .profile-link:hover .profile-badge {
      background-color: rgba(255, 255, 255, 0.25);
      color: #ffffff;
    }

    .btn-voltar-link {
      display: block;
      text-align: center;
      margin-top: 16px;
      color: #666;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      transition: color 0.2s ease;
    }

    .btn-voltar-link:hover {
      color: #0d47a1;
      text-decoration: underline;
    }

    .alerta-vazio {
      background-color: #fff3cd;
      color: #856404;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      text-align: center;
      margin-bottom: 12px;
      border: 1px solid #ffeeba;
    }

    /* ===== RESPONSIVO ===== */
    @media (max-width: 1024px) {
      .caixa.esquerda { padding: 40px 48px 80px 80px; }
      .logo { width: 260px; }
      .logo-texto h1 { font-size: 3.8rem; }
    }

    @media (max-width: 768px) {
      .container { flex-direction: column; }
      .caixa.esquerda {
        padding: 40px 24px 60px 24px;
        margin-top: 0;
        align-items: center;
        text-align: center;
      }
      .caixa.esquerda p { max-width: 100%; }
      .logo-bloco { flex-direction: column; align-items: center; text-align: center; }
      .logo { width: 200px; }
      .logo-texto h1 { font-size: 3rem; }
      .logo-texto span { font-size: 1.1rem; }
      .caixa.direita {
        flex: none;
        width: 100%;
        padding: 40px 28px;
        box-shadow: 0 -6px 32px rgba(0, 0, 0, 0.06);
      }
    }
  </style>
    <link rel="icon" type="image/png" href="../assets/imgs/favicon.png">
</head>
<body>

  <!-- ===== SVG DE FUNDO ===== -->
  <svg class="bg-svg" viewBox="0 0 1440 900" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice">
    <rect width="1440" height="900" fill="#ddeeff"/>
    <ellipse cx="200" cy="820" rx="280" ry="110" fill="#40d9b8"/>
    <ellipse cx="620" cy="790" rx="300" ry="120" fill="#40d9b8"/>
    <ellipse cx="1050" cy="810" rx="290" ry="115" fill="#40d9b8"/>
    <ellipse cx="1380" cy="830" rx="220" ry="105" fill="#40d9b8"/>
    <rect x="0" y="820" width="1440" height="80" fill="#40d9b8"/>
    <ellipse cx="100" cy="845" rx="260" ry="100" fill="#2ebfa0"/>
    <ellipse cx="480" cy="830" rx="310" ry="115" fill="#2ebfa0"/>
    <ellipse cx="870" cy="840" rx="295" ry="108" fill="#2ebfa0"/>
    <ellipse cx="1260" cy="835" rx="270" ry="110" fill="#2ebfa0"/>
    <rect x="0" y="845" width="1440" height="55" fill="#2ebfa0"/>
    <ellipse cx="250" cy="862" rx="300" ry="95" fill="#1a9e85"/>
    <ellipse cx="700" cy="855" rx="320" ry="100" fill="#1a9e85"/>
    <ellipse cx="1150" cy="860" rx="300" ry="95" fill="#1a9e85"/>
    <rect x="0" y="862" width="1440" height="38" fill="#1a9e85"/>
    <ellipse cx="150" cy="878" rx="270" ry="90" fill="#1565c0"/>
    <ellipse cx="560" cy="872" rx="310" ry="95" fill="#1565c0"/>
    <ellipse cx="980" cy="875" rx="300" ry="92" fill="#1565c0"/>
    <ellipse cx="1380" cy="873" rx="240" ry="88" fill="#1565c0"/>
    <rect x="0" y="878" width="1440" height="22" fill="#1565c0"/>
    <ellipse cx="320" cy="895" rx="350" ry="88" fill="#0d47a1"/>
    <ellipse cx="800" cy="892" rx="370" ry="90" fill="#0d47a1"/>
    <ellipse cx="1300" cy="895" rx="320" ry="85" fill="#0d47a1"/>
    <rect x="0" y="892" width="1440" height="8" fill="#0d47a1"/>
  </svg>

  <!-- ===== CONTAINER PRINCIPAL ===== -->
  <section class="container">
    
    <!-- LADO ESQUERDO -->
    <div class="caixa esquerda">
      <div class="logo-bloco">
        <img src="../assets/imgs/logo.png" alt="Logo SIGEI" class="logo">
        <div class="logo-texto">
          <h1>SIGEI</h1>
          <span>Sistema de Gestão Escolar<br>para a Inclusão</span>
        </div>
      </div>
      <p>Gestão eficiente para uma educação inclusiva.<br>O SIGEI centraliza informações, auxilia no acompanhamento de alunos elegíveis à educação especial e apoia a distribuição de Profissionais de Apoio Escolar.</p>
    </div>

    <!-- LADO DIREITO -->
    <div class="caixa direita">
      
      <?php if ($perfil === ''): ?>
        <!-- ETAPA 1: ESCOLHER PERFIL -->
        <div style="text-align: center;">
          <span class="badge-dev">Acesso rápido</span>
        </div>
        <h2 class="section-label">Acesso Administrativo</h2>
        <p class="section-sub">Selecione o perfil para entrar diretamente.</p>

        <?php foreach ($perfisDisponiveis as $slug => $pInfo): ?>
          <a href="acesso_rapido.php?perfil=<?php echo urlencode($slug); ?>" class="profile-link">
            <div class="profile-info">
              <div class="profile-header-row">
                <span class="profile-prefix">Perfil</span>
                <span class="profile-name"><?php echo htmlspecialchars($pInfo['nome']); ?></span>
              </div>
              <span class="profile-desc"><?php echo htmlspecialchars($pInfo['desc']); ?></span>
            </div>
            <span class="arrow">&#8594;</span>
          </a>
        <?php endforeach; ?>

        <a href="../index.html" class="btn-voltar-link">← Voltar para tela inicial</a>

      <?php else: ?>
        <!-- ETAPA 2: ESCOLHER USUÁRIO DENTRO DO PERFIL -->
        <div style="text-align: center;">
          <span class="badge-dev"> <?php echo htmlspecialchars($perfilConfig['nome']); ?></span>
        </div>
        <h2 class="section-label">Selecione o Usuário</h2>
        <p class="section-sub">Escolha a conta que deseja utilizar neste perfil.</p>

        <?php if (empty($usuarios)): ?>
          <div class="alerta-vazio">
            Nenhum usuário ativo encontrado para este perfil no banco de dados.
          </div>
        <?php else: ?>
          <?php foreach ($usuarios as $u): ?>
            <?php
              $idUsuario = (int) $u[$campoId];
              $nome = $u['nome'];
              $vinculo = $u['vinculo_nome'] ?? '';
              $cargo = $u['cargo'] ?? '';
              $setor = $u['setor'] ?? '';
              
              $detalhes = [];
              if ($vinculo) $detalhes[] = $vinculo;
              if ($cargo) $detalhes[] = $cargo;
              if ($setor) $detalhes[] = "Setor: " . $setor;
              $detalhesTexto = implode(" — ", $detalhes);
            ?>
            <a href="../controllers/auth/acesso_rapido.php?tabela=<?php echo urlencode($tabela); ?>&id=<?php echo $idUsuario; ?>" class="profile-link">
              <div class="profile-info">
                <span class="profile-name" style="font-size: 1.05rem;"><?php echo htmlspecialchars($nome); ?></span>
                <?php if ($detalhesTexto): ?>
                  <span class="profile-desc"><?php echo htmlspecialchars($detalhesTexto); ?></span>
                <?php endif; ?>
                <?php if (!empty($u['extra'])): ?>
                  <span class="profile-badge"><?php echo htmlspecialchars($u['extra']); ?></span>
                <?php endif; ?>
              </div>
              <span class="arrow">&#8594;</span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>

        <a href="acesso_rapido.php" class="btn-voltar-link">← Voltar aos perfis</a>

      <?php endif; ?>

      <p class="footer-note">Acesso exclusivo para desenvolvimento e testes.</p>
    </div>

  </section>

</body>
</html>

