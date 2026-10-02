<?php
// Inclua sua sessão ou gerador de token CSRF se necessário aqui
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIGEI — RECUPERAÇÃO DE SENHA</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ===== RESET E BASE ===== */
    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Times New Roman', Times, serif;
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

    /* ===== LADO DIREITO (RECUPERAÇÃO) ===== */
    .caixa.direita {
      flex: 0 0 33.333vw;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background-color: #ffffff;
      padding: 60px 48px;
      box-shadow: -6px 0 32px rgba(0, 0, 0, 0.08);
    }

    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .login-header .section-label {
      color: #0d47a1;
      font-size: 1.8rem;
      font-weight: 800;
      margin: 0 0 4px 0;
    }

    .login-header .section-sub {
      color: #555;
      font-size: 0.95rem;
      margin: 0;
    }

    /* ===== FORMULÁRIO ===== */
    .login-form .form-group {
      margin-bottom: 20px;
    }

    .login-form label {
      display: block;
      font-weight: 700;
      font-size: 0.9rem;
      color: #0d47a1;
      margin-bottom: 6px;
    }

    .login-form .form-control {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e0e7ef;
      border-radius: 10px;
      font-size: 1rem;
      font-family: inherit;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      background-color: #f8fafc;
    }

    .login-form .form-control:focus {
      outline: none;
      border-color: #0d47a1;
      box-shadow: 0 0 0 4px rgba(13, 71, 161, 0.12);
      background-color: #ffffff;
    }

    .login-form .form-control::placeholder {
      color: #aab;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      background-color: #0d47a1;
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      transition: background-color 0.2s ease, transform 0.1s ease;
      margin-top: 8px;
    }

    .btn-login:hover {
      background-color: #0a3a87;
    }

    .btn-login:active {
      transform: scale(0.98);
    }

    .login-links {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      margin-top: 20px;
    }

    .login-links .link-btn {
      background: none;
      border: none;
      color: #0d47a1;
      font-size: 0.9rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      padding: 4px 8px;
      transition: color 0.2s ease;
      text-decoration: none;
    }

    .login-links .link-btn:hover {
      color: #1a6bc4;
      text-decoration: underline;
    }

    /* ===== RESPONSIVO ===== */
    @media (max-width: 1024px) {
      .caixa.esquerda {
        padding: 40px 48px 80px 80px;
      }
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
      .caixa.direita {
        flex: none;
        width: 100%;
        padding: 40px 28px;
        box-shadow: 0 -6px 32px rgba(0, 0, 0, 0.06);
      }
    }
  </style>
    <link rel="icon" type="image/png" href="../../assets/imgs/favicon.png">
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
        <img src="../../assets/imgs/logo.png" alt="Logo SIGEI" class="logo">
        <div class="logo-texto">
          <h1>SIGEI</h1>
          <span>Sistema de Gestão Escolar<br>para a Inclusão</span>
        </div>
      </div>
      <p>Gestão eficiente para uma educação inclusiva.<br>O SIGEI centraliza informações, auxilia no acompanhamento de alunos elegíveis à educação especial e apoia a distribuição de Profissionais de Apoio Escolar.</p>
    </div>

    <!-- LADO DIREITO (RECUPERAÇÃO DE SENHA) -->
    <div class="caixa direita">
      
      <div class="login-header">
        <h2 class="section-label">Recuperar Senha</h2>
        <p class="section-sub">Informe seu e-mail cadastrado para redefinir sua senha</p>
      </div>

      <form class="login-form" action="../../controllers/auth/processa_esqueci_senha.php" method="POST">
        <div class="form-group">
          <label for="email">E-mail Cadastrado</label>
          <input type="email" name="email" id="email" class="form-control" maxlength="150" required placeholder="seu.email@exemplo.com">
        </div>
        
        <button type="submit" class="btn-login">Redefinir Senha</button>
        
        <div class="login-links">
          <a href="login.php" class="link-btn">← Voltar para o Login</a>
        </div>
      </form>

    </div>

  </section>

</body>
</html>