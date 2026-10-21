/**
 * SIGEI - Formatação, Máscaras e Validações de Entrada (Front-end)
 * Gerencia máscaras e validações para CPF, CNPJ, Telefone, CEP, Datas, E-mail e Campos Numéricos.
 */
document.addEventListener('DOMContentLoaded', function () {
    // 1. Máscara para Telefone: (11) 9 1004-3438 ou (11) 1004-3438
    function formatarTelefoneInput(valor) {
        if (!valor) return '';
        var numeros = valor.replace(/\D/g, '').slice(0, 11);
        var len = numeros.length;

        if (len === 0) return '';
        if (len <= 2) return '(' + numeros;
        if (len <= 6) return '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2);
        if (len <= 10) {
            return '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2, 6) + '-' + numeros.slice(6);
        }
        return '(' + numeros.slice(0, 2) + ') ' + numeros.slice(2, 3) + ' ' + numeros.slice(3, 7) + '-' + numeros.slice(7);
    }

    var inputsTelefone = document.querySelectorAll('input[name*="telefone" i], input[type="tel"], input[id*="telefone" i]');
    inputsTelefone.forEach(function (input) {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('maxlength', '16');
        if (input.value) {
            input.value = formatarTelefoneInput(input.value);
        }

        function validarCampoTelefone() {
            var limpo = input.value.replace(/\D/g, '');
            if (limpo.length > 0 && (limpo.length < 10 || limpo.length > 11 || /^(\d)\1+$/.test(limpo))) {
                input.setCustomValidity('Informe um telefone válido com DDD (10 ou 11 dígitos).');
            } else {
                input.setCustomValidity('');
            }
        }

        input.addEventListener('input', function () {
            input.value = formatarTelefoneInput(input.value);
            validarCampoTelefone();
        });

        input.addEventListener('blur', function () {
            input.value = formatarTelefoneInput(input.value);
            validarCampoTelefone();
        });
    });

    // 2. Máscara e validação para CPF (000.000.000-00)
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
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('maxlength', '14');
        if (input.value) {
            input.value = formatarCPFInput(input.value);
        }

        function validarCampoCPF() {
            var limpo = input.value.replace(/\D/g, '');
            if (limpo.length > 0 && (limpo.length !== 11 || /^(\d)\1{10}$/.test(limpo))) {
                input.setCustomValidity('Informe um CPF válido com 11 dígitos numéricos.');
            } else {
                input.setCustomValidity('');
            }
        }

        input.addEventListener('input', function () {
            input.value = formatarCPFInput(input.value);
            validarCampoCPF();
        });

        input.addEventListener('blur', function () {
            input.value = formatarCPFInput(input.value);
            validarCampoCPF();
        });
    });

    // 3. Máscara e validação para CNPJ (00.000.000/0000-00)
    function formatarCNPJInput(valor) {
        if (!valor) return '';
        var n = valor.replace(/\D/g, '').slice(0, 14);
        if (n.length <= 2) return n;
        if (n.length <= 5) return n.slice(0, 2) + '.' + n.slice(2);
        if (n.length <= 8) return n.slice(0, 2) + '.' + n.slice(2, 5) + '.' + n.slice(5);
        if (n.length <= 12) return n.slice(0, 2) + '.' + n.slice(2, 5) + '.' + n.slice(5, 8) + '/' + n.slice(8);
        return n.slice(0, 2) + '.' + n.slice(2, 5) + '.' + n.slice(5, 8) + '/' + n.slice(8, 12) + '-' + n.slice(12);
    }

    var inputsCNPJ = document.querySelectorAll('input[name*="cnpj" i], input[id*="cnpj" i]');
    inputsCNPJ.forEach(function (input) {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('maxlength', '18');
        if (input.value) {
            input.value = formatarCNPJInput(input.value);
        }

        function validarCampoCNPJ() {
            var limpo = input.value.replace(/\D/g, '');
            if (limpo.length > 0 && (limpo.length !== 14 || /^(\d)\1{13}$/.test(limpo))) {
                input.setCustomValidity('Informe um CNPJ válido com 14 dígitos numéricos.');
            } else {
                input.setCustomValidity('');
            }
        }

        input.addEventListener('input', function () {
            input.value = formatarCNPJInput(input.value);
            validarCampoCNPJ();
        });

        input.addEventListener('blur', function () {
            input.value = formatarCNPJInput(input.value);
            validarCampoCNPJ();
        });
    });

    // 4. Máscara e validação para CEP (00000-000)
    function formatarCEPInput(valor) {
        if (!valor) return '';
        var n = valor.replace(/\D/g, '').slice(0, 8);
        if (n.length <= 5) return n;
        return n.slice(0, 5) + '-' + n.slice(5);
    }

    var inputsCEP = document.querySelectorAll('input[name="cep" i], input[id*="cep" i]');
    inputsCEP.forEach(function (input) {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('maxlength', '9');
        if (input.value) {
            input.value = formatarCEPInput(input.value);
        }

        function validarCampoCEP() {
            var limpo = input.value.replace(/\D/g, '');
            if (limpo.length > 0 && (limpo.length !== 8 || /^(\d)\1{7}$/.test(limpo))) {
                input.setCustomValidity('Informe um CEP válido com 8 dígitos numéricos.');
            } else {
                input.setCustomValidity('');
            }
        }

        input.addEventListener('input', function () {
            input.value = formatarCEPInput(input.value);
            validarCampoCEP();
        });

        input.addEventListener('blur', function () {
            input.value = formatarCEPInput(input.value);
            validarCampoCEP();
        });
    });

    // 5. Campos estritamente numéricos (CIE, UA, UGE, Código de verificação)
    var seletoresNumericos = [
        { selector: 'input[name="cie"]', max: 10 },
        { selector: 'input[name="ua"]', max: 20 },
        { selector: 'input[name="uge"]', max: 20 },
        { selector: 'input[name="codigo"][maxlength="6"]', max: 6 }
    ];

    seletoresNumericos.forEach(function (regra) {
        document.querySelectorAll(regra.selector).forEach(function (input) {
            input.setAttribute('inputmode', 'numeric');
            if (!input.getAttribute('maxlength') || parseInt(input.getAttribute('maxlength'), 10) > regra.max) {
                input.setAttribute('maxlength', String(regra.max));
            }
            input.addEventListener('input', function () {
                var limpo = this.value.replace(/\D/g, '').slice(0, regra.max);
                if (this.value !== limpo) {
                    this.value = limpo;
                }
            });
        });
    });

    // 6. Validação de E-mail
    var inputsEmail = document.querySelectorAll('input[type="email"], input[name="email"]');
    inputsEmail.forEach(function (input) {
        if (!input.getAttribute('maxlength')) {
            input.setAttribute('maxlength', '150');
        }
        input.addEventListener('input', function () {
            var val = this.value.trim();
            if (val !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                this.setCustomValidity('Informe um endereço de e-mail válido.');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    // 7. Validação de Datas (Data de Nascimento e Datas de Contrato)
    var hojeISO = new Date().toISOString().split('T')[0];
    var inputsDataNasc = document.querySelectorAll('input[name="data_nascimento"]');
    inputsDataNasc.forEach(function (input) {
        if (!input.getAttribute('min')) input.setAttribute('min', '1900-01-01');
        if (!input.getAttribute('max')) input.setAttribute('max', hojeISO);

        input.addEventListener('change', function () {
            if (this.value && this.value > hojeISO) {
                this.setCustomValidity('A data de nascimento não pode ser uma data futura.');
            } else if (this.value && this.value < '1900-01-01') {
                this.setCustomValidity('Informe uma data de nascimento válida.');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    var inputInicioContrato = document.querySelector('input[name="data_inicio_contrato"]');
    var inputFimContrato = document.querySelector('input[name="data_fim_contrato"]');
    if (inputInicioContrato && inputFimContrato) {
        function validarIntervaloContrato() {
            if (inputInicioContrato.value && inputFimContrato.value) {
                if (inputFimContrato.value < inputInicioContrato.value) {
                    inputFimContrato.setCustomValidity('A data de término do contrato deve ser igual ou posterior à data de início.');
                } else {
                    inputFimContrato.setCustomValidity('');
                }
            } else {
                inputFimContrato.setCustomValidity('');
            }
        }
        inputInicioContrato.addEventListener('change', validarIntervaloContrato);
        inputFimContrato.addEventListener('change', validarIntervaloContrato);
    }

    // 8. Validação de tamanho e limite de anexos (Máx 5MB)
    var inputsArquivo = document.querySelectorAll('input[type="file"]');
    var TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024; // 5 MB

    inputsArquivo.forEach(function (input) {
        input.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                for (var i = 0; i < this.files.length; i++) {
                    var arquivo = this.files[i];
                    if (arquivo.size > TAMANHO_MAXIMO_BYTES) {
                        alert('O arquivo "' + arquivo.name + '" excede o limite máximo permitido de 5 MB.');
                        this.value = '';
                        return;
                    }
                }
            }
        });
    });
});
