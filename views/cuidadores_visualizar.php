<?php
$id = isset($_GET['id']) ? '?id=' . (int) $_GET['id'] : '';
header("Location: paes_visualizar.php" . $id);
exit();
?>
