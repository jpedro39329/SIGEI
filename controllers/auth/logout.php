<?php

// inicia sessão
session_start();


// destrói tudo
session_destroy();


// volta para login
header("Location: ../../views/login.php");
exit();

?>
