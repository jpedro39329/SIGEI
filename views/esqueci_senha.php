<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIGEI — Recuperação de Senha</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 0;
      font-family: 'Nunito', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #ddeeff; min-height: 100vh; position: relative; overflow-x: hidden;
    }
    .bg-svg { position: fixed; bottom: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
    .container { position: relative; z-index: 1; display: flex; width: 100%; min-height: 100vh; align-items: stretch; }
    .caixa.esquerda { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; text-align: left; padding: 60px 80px 120px 160px; margin-top: -80px; }
    .logo-bloco { display: flex; align-items: center; gap: 24px; margin-bottom: 20px; }
    .logo { width: 340px; height: auto; display: block; flex-shrink: 0; }
    .logo-texto h1 { color: #0d47a1; font-size: 4.8rem; font-weight: 800; margin: 0 0 6px 0; line-height: 1; }
    .logo-texto span { color: #1565c0; font-size: 1.3rem; font-weight: 600; display: block; line-height: 1.5; }
    .caixa.esquerda p { color: #1565c0; font-size: 1.25rem; line-height: 1.7; max-width: 600px; margin: 0; }
    .caixa.direita { flex: 0 0 33.333vw; display: flex; flex-direction: column; justify-content: center; background-color: #ffffff; padding: 36px 36px; box-shadow: -6px 0 32px rgba(0, 0, 0, 0.08); max-height: 100vh; overflow-y: auto; }
    .section-label { color: #0d47a1; font-size: 1.6rem; font-weight: 800; text-align: center; margin: 0 0 4px 0; }
    .section-sub { color: #555; font-size: 0.88rem; text-align: center; margin: 0 0 24px 0; }
    .form-group { margin-bottom: 18px; display: flex; flex-direction: column; }
    .form-group label { color: #0d47a1; font-weight: 700; font-size: 0.95rem; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 12px 14px; font-size: 1rem; border: 2px solid #e0e7ef; border-radius: 8px; background-color: #f8fafc; font-family: inherit; outline: none; }
    .form-control:focus { border-color: #0d47a1; background-color: #ffffff; }
    .btn-submit { width: 100%; padding: 12px; background-color: #0d47a1; color: #ffffff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background-color 0.2s ease; margin-top: 8px; }
    .btn-submit:hover { background-color: #1565c0; }
    .back-link { display: block; text-align: center; margin-top: 16px; color: #1565c0; text-decoration: none; font-weight: 700; font-size: 0.9rem; }
    .footer-note { color: #757575; font-size: 0.78rem; text-align: center; margin-top: 24px; margin-bottom: 0; }
  </style>
</head>
<body>
  <svg class="bg-svg" viewBox="0 0 1440 900" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice">
    <rect width="1440" height="900" fill="#ddeeff"/>
    <rect x="0" y="820" width="1440" height="80" fill="#40d9b8"/>
    <rect x="0" y="845" width="1440" height="55" fill="#2ebfa0"/>
    <rect x="0" y="862" width="1440" height="38" fill="#1a9e85"/>
    <rect x="0" y="878" width="1440" height="22" fill="#1565c0"/>
    <rect x="0" y="892" width="1440" height="8" fill="#0d47a1"/>
  </svg>

  <section class="container">
    <div class="caixa esquerda">
      <div class="logo-bloco">
        <img src="assets/imgs/logo.png" alt="Logo SIGEI" class="logo">
        <div class="logo-texto">
          <h1>SIGEI</h1>
          <span>Sistema de Gestão Escolar<br>para a Inclusão</span>
        </div>
      </div>
      <p>Gestão eficiente para uma educação inclusiva.</p>
    </div>

    <div class="caixa direita">
      <h2 class="section-label">Recuperar Senha</h2>
      <p class="section-sub">Informe o e-mail cadastrado para receber o link.</p>

      <form action="../controllers/processa_esqueci_senha.php" method="POST">
  <div class="form-group">
    <label for="email">E-mail Cadastrado:</label>
    <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" required>
  </div>

  <button type="submit" class="btn-submit">Enviar Link de Recuperação</button>
</form>
        
      </form>

      <a href="index.php" class="back-link">&#8592; Voltar para a Seleção de Perfil</a>
      <p class="footer-note">Todos os acessos são monitorados e registrados.</p>
    </div>
  </section>
</body>
</html>