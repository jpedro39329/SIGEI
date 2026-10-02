<?php
require_once __DIR__ . '/config/database.php';

echo "Conexão OK com banco " . $banco . "\n";

$res = mysqli_query($conexao, "SELECT COUNT(*) as tot FROM alunos");
echo "Total de alunos atual: " . mysqli_fetch_assoc($res)['tot'] . "\n";

$resUe = mysqli_query($conexao, "SELECT id_ue, nome, id_ure FROM unidades_escolares");
echo "\n--- Unidades Escolares ---\n";
while ($r = mysqli_fetch_assoc($resUe)) {
    echo "ID: {$r['id_ue']} | Nome: {$r['nome']} | URE: {$r['id_ure']}\n";
}

$resPae = mysqli_query($conexao, "SELECT id_pae, nome, id_empresa FROM usuarios_pae");
echo "\n--- PAEs ---\n";
while ($r = mysqli_fetch_assoc($resPae)) {
    echo "ID: {$r['id_pae']} | Nome: {$r['nome']} | Empresa: {$r['id_empresa']}\n";
}

$resAssoc = mysqli_query($conexao, "SELECT id_pae, COUNT(*) as qtd FROM associacoes WHERE ativo = 1 GROUP BY id_pae");
echo "\n--- Associações Ativas por PAE ---\n";
while ($r = mysqli_fetch_assoc($resAssoc)) {
    echo "PAE {$r['id_pae']}: {$r['qtd']} alunos\n";
}
