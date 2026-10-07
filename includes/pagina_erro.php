<?php
// ============================================================
// SIGEI - PÁGINA DE ERRO AMIGÁVEL (404 / 500)
// Autossuficiente: não depende de CSS/JS externos do projeto,
// funciona em qualquer profundidade de URL.
// ============================================================

function sigei_pagina_erro(int $codigo = 500, string $urlInicio = ''): void
{
    $is404 = ($codigo === 404);

    $titulo = $is404 ? 'Página não encontrada' : 'Ocorreu um problema';
    $texto  = $is404
        ? 'A página que você procura não existe ou foi removida.'
        : 'Não foi possível concluir esta operação.';

    $metodoPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
    $inicio = $urlInicio !== '' ? $urlInicio : '/';

    $icone = $is404
        ? '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8.5" y1="11" x2="13.5" y2="11"/></svg>'
        : '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="7.5" x2="12" y2="13"/><circle cx="12" cy="16.5" r="0.6" fill="currentColor"/></svg>';
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>SIGEI — <?php echo htmlspecialchars($titulo); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 24px; background: #f4f8ff; color: #1e293b;
      font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
    }
    .card {
      width: 100%; max-width: 420px; background: #fff; border: 1px solid #dbe6f5;
      border-radius: 14px; box-shadow: 0 8px 24px rgba(17, 78, 207, 0.08);
      padding: 36px 28px; text-align: center;
    }
    .icone {
      width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      background: #eef3fd; color: #114ecf;
    }
    .marca { font-size: .8rem; font-weight: 800; letter-spacing: .12em; color: #114ecf; margin-bottom: 6px; }
    h1 { font-size: 1.35rem; font-weight: 800; margin: 0 0 8px; }
    p { margin: 0 0 24px; color: #52637a; font-size: .95rem; line-height: 1.5; }
    .btn {
      display: inline-block; border: 0; cursor: pointer; text-decoration: none;
      background: #114ecf; color: #fff; font: inherit; font-weight: 700; font-size: .95rem;
      padding: 10px 26px; border-radius: 8px;
    }
    .btn:hover { background: #0038bb; }
    .btn:focus-visible { outline: 3px solid rgba(0, 56, 187, 0.25); outline-offset: 2px; }
  </style>
</head>
<body>
  <main class="card" role="alert">
    <div class="icone" aria-hidden="true"><?php echo $icone; ?></div>
    <div class="marca">SIGEI</div>
    <h1><?php echo htmlspecialchars($titulo); ?></h1>
    <p><?php echo htmlspecialchars($texto); ?></p>
    <?php if (!$is404): ?>
      <div style="font-size: 0.75rem; color: #94a3b8; margin-top: -16px; margin-bottom: 22px; font-family: monospace;">
        Código: SIGEI-ERR-<?php echo (int)$codigo; ?>
      </div>
    <?php endif; ?>
    <?php if ($is404): ?>
      <a class="btn" href="<?php echo htmlspecialchars($inicio); ?>"
         onclick="if (history.length > 1) { history.back(); return false; }">Voltar</a>
    <?php elseif ($metodoPost): ?>
      <button class="btn" type="button" onclick="history.back()">Tentar novamente</button>
    <?php else: ?>
      <button class="btn" type="button" onclick="location.reload()">Tentar novamente</button>
    <?php endif; ?>
  </main>
</body>
</html>
<?php
}

