<?php
// ============================================================
// SIGEI - PÁGINA DE ERRO AMIGÁVEL COM CARD (404 / 500)
// Exibe mensagem clara com o erro ocorrido, local e botão fechar/voltar
// ============================================================

function sigei_pagina_erro(int $codigo = 500, string $urlInicio = '', ?string $mensagemErro = null, ?string $arquivoErro = null, ?int $linhaErro = null): void
{
    $is404 = ($codigo === 404);

    $titulo = $is404 ? 'Página não encontrada' : 'Erro no Sistema';
    $texto  = $is404
        ? 'A página que você procura não existe ou foi removida.'
        : 'Ocorreu um problema ao processar a sua solicitação.';

    $inicio = $urlInicio !== '' ? $urlInicio : '/';

    // Se temos arquivo, exibir apenas o caminho relativo limpo
    $localLimpo = '';
    if ($arquivoErro) {
        $arquivoErroNorm = str_replace('\\', '/', $arquivoErro);
        $pos = strpos($arquivoErroNorm, 'SIGEI/');
        if ($pos !== false) {
            $localLimpo = substr($arquivoErroNorm, $pos);
        } else {
            $localLimpo = basename($arquivoErro);
        }
        if ($linhaErro) {
            $localLimpo .= ' (linha ' . $linhaErro . ')';
        }
    }
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
      padding: 24px; background: #f8fafc; color: #1e293b;
      font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
    }
    .card-erro {
      width: 100%; max-width: 520px; background: #fff; border: 1px solid #fee2e2;
      border-radius: 16px; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.08);
      padding: 32px 28px; text-align: center; position: relative;
    }
    .icone-erro {
      width: 60px; height: 60px; margin: 0 auto 16px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2;
    }
    .marca { font-size: .8rem; font-weight: 800; letter-spacing: .12em; color: #ef4444; margin-bottom: 6px; text-transform: uppercase; }
    h1 { font-size: 1.35rem; font-weight: 800; margin: 0 0 8px; color: #0f172a; }
    .subtexto { margin: 0 0 20px; color: #64748b; font-size: .95rem; line-height: 1.5; }
    .detalhe-box {
      background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
      padding: 14px 16px; text-align: left; margin-bottom: 24px; font-size: .88rem;
    }
    .detalhe-item { margin-bottom: 8px; word-break: break-word; }
    .detalhe-item:last-child { margin-bottom: 0; }
    .detalhe-label { font-weight: 700; color: #475569; display: block; font-size: .78rem; text-transform: uppercase; margin-bottom: 2px; }
    .detalhe-valor { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: #b91c1c; font-size: .85rem; }
    .detalhe-onde { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: #334155; font-size: .85rem; }
    .acoes { display: flex; gap: 10px; justify-content: center; }
    .btn-fechar {
      display: inline-flex; align-items: center; justify-content: center; border: 0; cursor: pointer; text-decoration: none;
      background: #0f172a; color: #fff; font: inherit; font-weight: 700; font-size: .95rem;
      padding: 10px 24px; border-radius: 8px; transition: background .15s ease;
    }
    .btn-fechar:hover { background: #334155; }
    .btn-voltar {
      display: inline-flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; cursor: pointer; text-decoration: none;
      background: #ffffff; color: #334155; font: inherit; font-weight: 700; font-size: .95rem;
      padding: 10px 20px; border-radius: 8px; transition: background .15s ease;
    }
    .btn-voltar:hover { background: #f1f5f9; }
  </style>
</head>
<body>
  <main class="card-erro" role="alert">
    <div class="icone-erro" aria-hidden="true">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
    </div>
    <div class="marca">SIGEI</div>
    <h1><?php echo htmlspecialchars($titulo); ?></h1>
    <p class="subtexto"><?php echo htmlspecialchars($texto); ?></p>

    <?php if (!$is404 && ($mensagemErro || $localLimpo)): ?>
      <div class="detalhe-box">
        <?php if ($mensagemErro): ?>
          <div class="detalhe-item">
            <span class="detalhe-label">Erro Identificado</span>
            <span class="detalhe-valor"><?php echo htmlspecialchars($mensagemErro); ?></span>
          </div>
        <?php endif; ?>
        <?php if ($localLimpo): ?>
          <div class="detalhe-item">
            <span class="detalhe-label">Onde Ocorreu</span>
            <span class="detalhe-onde"><?php echo htmlspecialchars($localLimpo); ?></span>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="acoes">
      <button class="btn-fechar" type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.href = '<?php echo htmlspecialchars($inicio); ?>'">
        Fechar
      </button>
      <button class="btn-voltar" type="button" onclick="window.location.reload()">
        Tentar Novamente
      </button>
    </div>
  </main>
</body>
</html>
<?php
}
