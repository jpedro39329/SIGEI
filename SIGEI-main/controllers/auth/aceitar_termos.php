<?php
require_once "../../config/init.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit();
}

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não autenticado.']);
    exit();
}

$idUsuario = (int) ($_SESSION['user_id'] ?? 0);
$perfil    = trim((string) ($_SESSION['user_perfil'] ?? ''));

if ($idUsuario <= 0 || $perfil === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Dados de sessão inválidos.']);
    exit();
}

$sucesso = registrarAceiteTermo($conexao, $idUsuario, $perfil);

if ($sucesso) {
    $_SESSION['termos_aceitos'] = true;
    echo json_encode(['sucesso' => true, 'mensagem' => 'Termo aceito com sucesso.']);
    exit();
}

http_response_code(500);
echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar aceite no banco de dados.']);
exit();

