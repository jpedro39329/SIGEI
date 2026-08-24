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

    // Tipos de arquivo permitidos
    $tiposPermitidos = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'application/pdf' => 'pdf'
    );

    $tipoArquivo = $arquivo['type'];
    if (!isset($tiposPermitidos[$tipoArquivo])) {
        return false;
    }

    // Tamanho máximo: 5MB
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // Gera nome único para o arquivo
    $extensao = $tiposPermitidos[$tipoArquivo];
    $nomeArquivo = date('YmdHis') . '_' . rand(1000, 9999) . '.' . $extensao;

    // Caminho completo da pasta
    $caminhoPasta = "../uploads/" . $pasta . "/";

    // Cria a pasta se não existir
    if (!is_dir($caminhoPasta)) {
        mkdir($caminhoPasta, 0777, true);
    }

    // Move o arquivo para a pasta
    $caminhoFinal = $caminhoPasta . $nomeArquivo;
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
        // Retorna o caminho relativo para salvar no banco
        return "uploads/" . $pasta . "/" . $nomeArquivo;
    }

    return false;
}

?>