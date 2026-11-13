<?php
// ============================================================
// SIGEI - PÁGINA DE ERRO AMIGÁVEL COM CARD (404 / 500)
// Exibe mensagem clara com o erro ocorrido, diagnóstico traduzido,
// local, mensagem técnica e rastreamento (stack trace).
// ============================================================

function sigei_traduzir_diagnostico_erro(?string $mensagem): ?string
{
    if (empty($mensagem)) {
        return null;
    }

    $msg = (string) $mensagem;

    // Diagnósticos específicos do MySQL / Banco de dados
    if (stripos($msg, 'Unknown database') !== false) {
        return 'Banco de dados não encontrado (verifique o nome exato e letras maiúsculas/minúsculas no config/database.php ou se o banco foi criado no phpMyAdmin).';
    }

    if (stripos($msg, 'Access denied') !== false) {
        return 'Falha na autenticação com o banco de dados (usuário ou senha incorretos no config/database.php).';
    }

    if (stripos($msg, 'Connection refused') !== false || stripos($msg, 'No such file or directory') !== false) {
        return 'O servidor MySQL/MariaDB parece estar desligado ou inacessível.';
    }

    if (stripos($msg, "doesn't exist") !== false || stripos($msg, 'Unknown column') !== false) {
        return 'Estrutura do banco desatualizada (tabela ou coluna ausente — verifique se o banco/sql.txt foi importado corretamente).';
    }

    // Para outros erros/exceções PHP, exibe a mensagem de forma legível
    return $msg;
}

function sigei_pagina_erro(
    int $codigo = 500,
    string $urlInicio = '',
    ?string $mensagemErro = null,
    ?string $arquivoErro = null,
    ?int $linhaErro = null,
    ?string $traceErro = null
): void {
    $is404 = ($codigo === 404);

    $titulo = $is404 ? 'Página não encontrada' : 'Erro no Sistema';
    $texto  = $is404
        ? 'A página que você procura não existe ou foi removida.'
        : 'Ocorreu um problema ao processar a sua solicitação.';

    $inicio = $urlInicio !== '' ? $urlInicio : '/';

    $diagnostico = (!$is404 && $mensagemErro !== null) ? sigei_traduzir_diagnostico_erro($mensagemErro) : null;

    // Caminho amigável/relativo do arquivo
    $arquivoExibicao = $arquivoErro;
    if ($arquivoExibicao !== null) {
        $raizProjeto = realpath(__DIR__ . '/..');
        if ($raizProjeto && strpos($arquivoExibicao, $raizProjeto) === 0) {
            $arquivoExibicao = ltrim(substr($arquivoExibicao, strlen($raizProjeto)), DIRECTORY_SEPARATOR);
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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 24px; background: #f8fafc; color: #1e293b;
      font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
    }
    .card-erro {
      width: 100%; max-width: <?php echo (!$is404 && $diagnostico !== null) ? '640px' : '460px'; ?>;
      background: #fff; border: 1px solid #fee2e2;
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
    
    /* Bloco de Diagnóstico */
    .box-diagnostico {
      text-align: left; background: #fff5f5; border: 1px solid #fecaca;
      border-radius: 10px; padding: 16px; margin: 0 0 20px; font-size: .9rem;
    }
    .box-diagnostico-titulo {
      display: flex; align-items: center; gap: 6px;
      font-size: .82rem; font-weight: 800; text-transform: uppercase;
      letter-spacing: .05em; color: #b91c1c; margin-bottom: 8px;
    }
    .box-diagnostico-msg {
      color: #991b1b; font-weight: 700; line-height: 1.45; margin-bottom: 12px;
    }
    .box-diagnostico-meta {
      font-size: .82rem; color: #475569; margin-bottom: 8px; word-break: break-all;
    }
    .box-diagnostico-meta strong {
      color: #1e293b;
    }
    .box-diagnostico-orig {
      font-family: 'JetBrains Mono', Consolas, monospace; font-size: .8rem;
      background: #fee2e2; color: #7f1d1d; padding: 8px 10px; border-radius: 6px;
      word-break: break-word; line-height: 1.4; margin-bottom: 10px;
    }
    .box-diagnostico details {
      margin-top: 8px;
    }
    .box-diagnostico summary {
      cursor: pointer; font-size: .8rem; font-weight: 700; color: #64748b;
      user-select: none; transition: color .15s ease;
    }
    .box-diagnostico summary:hover {
      color: #0f172a;
    }
    .box-diagnostico pre {
      font-family: 'JetBrains Mono', Consolas, monospace; font-size: .75rem;
      background: #1e293b; color: #f1f5f9; padding: 12px; border-radius: 6px;
      overflow-x: auto; max-height: 200px; margin: 8px 0 0; line-height: 1.4;
    }

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

    <?php if (!$is404 && $diagnostico !== null): ?>
    <section class="box-diagnostico">
      <div class="box-diagnostico-titulo">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        Diagnóstico do Erro
      </div>

      <div class="box-diagnostico-msg">
        <?php echo htmlspecialchars($diagnostico); ?>
      </div>

      <?php if ($arquivoExibicao !== null && $linhaErro !== null): ?>
      <div class="box-diagnostico-meta">
        <strong>Origem:</strong> <?php echo htmlspecialchars($arquivoExibicao); ?> na linha <strong><?php echo (int) $linhaErro; ?></strong>
      </div>
      <?php endif; ?>

      <?php if (!empty($mensagemErro)): ?>
      <div class="box-diagnostico-orig">
        <?php echo htmlspecialchars($mensagemErro); ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($traceErro)): ?>
      <details>
        <summary>Ver rastreamento completo (Stack Trace)</summary>
        <pre><?php echo htmlspecialchars($traceErro); ?></pre>
      </details>
      <?php endif; ?>
    </section>
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
