// ============================================================
// FUNÇÕES AUXILIARES DO SIGEI
// ============================================================

// Máscara de CPF (000.000.000-00)
function mascaraCPF(campo) {
    let value = campo.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    campo.value = value;
}

// Máscara de CNPJ (00.000.000/0000-00)
function mascaraCNPJ(campo) {
    let value = campo.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/, '$1.$2');
    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
    value = value.replace(/(\d{4})(\d)/, '$1-$2');
    campo.value = value;
}

// Máscara de telefone ((11) 99999-9999)
function mascaraTelefone(campo) {
    let value = campo.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
    value = value.replace(/(\d{5})(\d)/, '$1-$2');
    campo.value = value;
}

// Máscara de CEP (00000-000)
function mascaraCEP(campo) {
    let value = campo.value.replace(/\D/g, '');
    value = value.replace(/(\d{5})(\d)/, '$1-$2');
    campo.value = value;
}

// Mostra/oculta senha
function toggleSenha(id) {
    const input = document.getElementById(id);
    const btn = input.nextElementSibling;

    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Ocultar';
    } else {
        input.type = 'password';
        btn.textContent = 'Mostrar';
    }
}

// Confirmação antes de excluir/desassociar
function confirmarAcao(mensagem) {
    return confirm(mensagem);
}