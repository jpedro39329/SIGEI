<?php
require_once '../../config/init.php';
require_once '../upload.php';

exigirPerfil(array('SUPERVISOR', 'USUARIO_EMPRESA', 'ADMIN', 'SEDUC'));
exigirTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../views/paes/listar.php");
    exit();
}

$userPerfil = $_SESSION['user_perfil'];
$userId = (int) $_SESSION['user_id'];

$nome = trim($_POST['nome'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';
$ativo = (int) ($_POST['ativo'] ?? 1);

if (in_array($userPerfil, ['ADMIN', 'SEDUC'])) {
    $idEmpresa = (int) ($_POST['id_empresa'] ?? 0);
} else {
    $idEmpresa = idEmpresaSupervisor($conexao, $userId);
}

if ($nome === '' || $cpf === '' || $senha === '' || $idEmpresa <= 0) {
    header("Location: ../../views/paes/cadastrar.php?erro=" . urlencode("Preencha todos os campos obrigatórios."));
    exit();
}

if ($senha !== $confirmarSenha) {
    header("Location: ../../views/paes/cadastrar.php?erro=" . urlencode("As senhas não coincidem."));
    exit();
}

$sqlVerifica = "SELECT id_pae FROM usuarios_pae WHERE cpf = ?";
$stmtVerifica = $conexao->prepare($sqlVerifica);
$stmtVerifica->bind_param("s", $cpf);
$stmtVerifica->execute();
$resultVerifica = $stmtVerifica->get_result();

if ($resultVerifica->num_rows > 0) {
    header("Location: ../../views/paes/cadastrar.php?erro=" . urlencode("CPF já cadastrado para outro PAE."));
    exit();
}

$contratoArquivo = null;
if (isset($_FILES['contrato_arquivo']) && $_FILES['contrato_arquivo']['error'] === UPLOAD_ERR_OK) {
    $contratoArquivo = uploadArquivo($_FILES['contrato_arquivo'], 'contratos');

    if ($contratoArquivo === false) {
        header("Location: ../../views/paes/cadastrar.php?erro=" . urlencode("Erro ao enviar contrato. Envie PDF, JPG ou PNG de até 5MB."));
        exit();
    }
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios_pae (nome, cpf, senha, email, telefone, contrato_arquivo, id_empresa, ativo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("ssssssii", $nome, $cpf, $senhaHash, $email, $telefone, $contratoArquivo, $idEmpresa, $ativo);

if ($stmt->execute()) {
    header("Location: ../../views/paes/listar.php?msg=salvo");
    exit();
}

header("Location: ../../views/paes/cadastrar.php?erro=" . urlencode("Erro ao cadastrar PAE: " . $stmt->error));
exit();
?>
