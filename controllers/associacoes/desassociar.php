<?php
require_once "../../config/init.php";

exigirPerfil(array('ADMIN', 'SEDUC', 'SUPERVISOR', 'USUARIO_EMPRESA'));
exigirTokenCSRF();

$id_associacao = (int) ($_POST['id_associacao'] ?? 0);

if ($id_associacao <= 0) {
    die("Associação inválida.");
}

// Desativa a associação
$stmt = $conexao->prepare("UPDATE associacoes SET ativo = 0 WHERE id_associacao = ?");
$stmt->bind_param("i", $id_associacao);

$redirectPae = (int) ($_POST['redirect_pae'] ?? 0);

if ($stmt->execute()) {
    if ($redirectPae > 0) {
        header("Location: ../../views/associacoes/visualizar.php?id=" . $redirectPae . "&msg=removido");
    } else {
        header("Location: ../../views/associacoes/gerenciar.php?msg=removido");
    }
    exit();
}

echo "Erro ao desassociar: " . $stmt->error;
?>