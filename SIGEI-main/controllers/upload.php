<?php

// ============================================================
// FUNÇÃO GENÉRICA DE UPLOAD DE ARQUIVOS
// ============================================================

// Faz upload de um arquivo e retorna o caminho salvo
// Uso: uploadArquivo($_FILES['campo'], 'fotos')
// Retorna: caminho do arquivo ou false em caso de erro
function uploadArquivo($arquivo, $pasta) {
    // Verifica se o arquivo foi enviado sem erro
    if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Pastas permitidas
    $pastasPermitidas = array('fotos', 'laudos', 'contratos', 'documentos');
    if (!in_array($pasta, $pastasPermitidas)) {
        return false;
    }

    // Diretório raiz absoluto do projeto
    $raizProjeto = dirname(__DIR__);
    $caminhoPasta = $raizProjeto . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $pasta . DIRECTORY_SEPARATOR;

    // Se a pasta de destino não existir, cria automaticamente
    if (!is_dir($caminhoPasta)) {
        if (!mkdir($caminhoPasta, 0777, true) && !is_dir($caminhoPasta)) {
            return false;
        }
    }

    // Tipos de arquivo permitidos (MIME -> extensão)
    $tiposPermitidos = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'application/pdf' => 'pdf'
    );

    // Valida o tipo REAL do conteúdo do arquivo (ignora o MIME informado pelo navegador)
    $mimeReal = null;

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {
            $mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mimeReal === null) {
        // Fallback: usa o MIME enviado pelo cliente
        $mimeReal = $arquivo['type'];
    }

    if (!isset($tiposPermitidos[$mimeReal])) {
        return false;
    }

    // Tamanho máximo: 5MB
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // Gera nome único para o arquivo
    $extensao = $tiposPermitidos[$mimeReal];
    $nomeArquivo = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extensao;

    // Move o arquivo para a pasta de destino
    $caminhoFinal = $caminhoPasta . $nomeArquivo;
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
        // Retorna o caminho relativo para salvar no banco
        return "uploads/" . $pasta . "/" . $nomeArquivo;
    }

    return false;
}

?>