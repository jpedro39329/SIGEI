<?php

// ============================================================
// SIGEI - INICIALIZAÇÃO CENTRAL
// Sessão + conexão + funções auxiliares
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/perfil_functions.php';
require_once __DIR__ . '/auditoria.php';

