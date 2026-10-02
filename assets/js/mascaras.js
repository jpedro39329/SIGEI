/**
 * SIGEI - Formatação e Máscaras de Entrada (Telefone, CPF, CEP, CNPJ)
 */
document.addEventListener('DOMContentLoaded', function () {
    // 1. Função de máscara para Telefone: (11) 9 1004-3438 ou (11) 1004-3438
    function formatarTelefoneInput(valor) {
        if (!valor) return '';
        var numeros = valor.replace(/\D/g, '').slice(0, 11);
        var len = numeros.length;

        if (len === 0) return '';
        if (len <= 2) return '(' + numeros;
        if (len <= 6) return '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2);
        if (len <= 10) {
            // (11) 4034-1100
            return '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2, 6) + '-' + numeros.slice(6);
        }
        // 11 dígitos: (11) 9 1004-3438
        return '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2, 3) + ' ' + numeros.slice(3, 7) + '-' + numeros.slice(7);
    }

    // Aplica a máscara em todos os inputs de telefone (por name, type ou id)
    var inputsTelefone = document.querySelectorAll('input[name*="telefone" i], input[type="tel"], input[id*="telefone" i]');
    inputsTelefone.forEach(function (input) {
        // Formata o valor inicial já presente no input (se houver)
        if (input.value) {
            input.value = formatarTelefoneInput(input.value);
        }

        input.addEventListener('input', function (e) {
            var cursorPosition = input.selectionStart;
            var valorAnterior = input.value;
            input.value = formatarTelefoneInput(input.value);
        });

        input.addEventListener('blur', function () {
            input.value = formatarTelefoneInput(input.value);
        });
    });

    // 2. Máscara genérica para CPF
    function formatarCPFInput(valor) {
        if (!valor) return '';
        var n = valor.replace(/\D/g, '').slice(0, 11);
        if (n.length <= 3) return n;
        if (n.length <= 6) return n.slice(0, 3) + '.' + n.slice(3);
        if (n.length <= 9) return n.slice(0, 3) + '.' + n.slice(3, 6) + '.' + n.slice(6);
        return n.slice(0, 3) + '.' + n.slice(3, 6) + '.' + n.slice(6, 9) + '-' + n.slice(9);
    }

    var inputsCPF = document.querySelectorAll('input[name*="cpf" i], input[id*="cpf" i]');
    inputsCPF.forEach(function (input) {
        if (input.value) {
            input.value = formatarCPFInput(input.value);
        }
        input.addEventListener('input', function () {
            input.value = formatarCPFInput(input.value);
        });
    });

    // 3. Validação de tamanho e limite de anexos (Máx 5MB)
    var inputsArquivo = document.querySelectorAll('input[type="file"]');
    var TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024; // 5 MB

    inputsArquivo.forEach(function (input) {
        input.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                for (var i = 0; i < this.files.length; i++) {
                    var arquivo = this.files[i];
                    if (arquivo.size > TAMANHO_MAXIMO_BYTES) {
                        alert('O arquivo "' + arquivo.name + '" excede o limite máximo permitido de 5 MB.');
                        this.value = ''; // Limpa o input
                        return;
                    }
                }
            }
        });
    });
});

