/**
 * SIGEI - UX Helpers
 * 1) Postel's Law: tolera colagem com lixo (pontos, espaços, parênteses) em campos com
 *    data-mask="cpf|telefone|cep|cnpj" (ou name/id equivalente) e formata em tempo real.
 *    Complementa mascaras.js (que já valida); aqui apenas normaliza na colagem.
 * 2) Doherty Threshold: evita duplo envio e dá feedback imediato no submit.
 * 3) Shake em campos inválidos (.is-invalid-shake).
 */
(function () {
    'use strict';

    var MASCARAS = {
        cpf: { max: 11, fmt: function (n) {
            return n.replace(/^(\d{3})(\d)/, '$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1-$2');
        } },
        cnpj: { max: 14, fmt: function (n) {
            return n.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        } },
        cep: { max: 8, fmt: function (n) { return n.replace(/^(\d{5})(\d)/, '$1-$2'); } },
        telefone: { max: 11, fmt: function (n) {
            if (n.length === 0) return '';
            if (n.length <= 2) return '(' + n;
            if (n.length <= 6) return '(' + n.slice(0, 2) + ') ' + n.slice(2);
            if (n.length <= 10) return '(' + n.slice(0, 2) + ') ' + n.slice(2, 6) + '-' + n.slice(6);
            return '(' + n.slice(0, 2) + ') ' + n.slice(2, 3) + ' ' + n.slice(3, 7) + '-' + n.slice(7);
        } }
    };

    function detectarTipo(input) {
        var dm = (input.getAttribute('data-mask') || '').toLowerCase();
        if (MASCARAS[dm]) return dm;
        var ref = ((input.name || '') + ' ' + (input.id || '')).toLowerCase();
        if (/cnpj/.test(ref)) return 'cnpj';
        if (/cpf/.test(ref)) return 'cpf';
        if (/cep/.test(ref)) return 'cep';
        if (/telefone|celular/.test(ref) || input.type === 'tel') return 'telefone';
        return null;
    }

    function aplicar(input, tipo) {
        var m = MASCARAS[tipo];
        function formatar() {
            var n = (input.value || '').trim().replace(/\D/g, '').slice(0, m.max);
            input.value = m.fmt(n);
        }
        input.addEventListener('paste', function (e) {
            var texto = (e.clipboardData || window.clipboardData);
            if (!texto) return;
            e.preventDefault();
            var n = String(texto.getData('text')).trim().replace(/\D/g, '').slice(0, m.max);
            input.value = m.fmt(n);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
        input.addEventListener('blur', function () {
            if (input.value) formatar();
        });
    }

    function initMascaras() {
        document.querySelectorAll('input').forEach(function (input) {
            if (input.type === 'hidden' || input.type === 'password' || input.type === 'file') return;
            var tipo = detectarTipo(input);
            if (tipo) aplicar(input, tipo);
        });
    }

    // Doherty Threshold / prevenção de duplo clique
    function initSubmit() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            if (form.method && form.method.toLowerCase() === 'get') return;
            if (form.hasAttribute('data-no-loading')) return;

            // Executa após demais handlers; só prossegue se o envio não foi cancelado.
            setTimeout(function () {
                if (e.defaultPrevented) return;
                if (form.dataset.sigeiSubmitting === '1') return;
                form.dataset.sigeiSubmitting = '1';

                var btn = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                if (!btn) return;

                var largura = btn.getBoundingClientRect().width;
                var rotulo = (btn.textContent || btn.value || '').toLowerCase();
                var texto = /salv|cadastr|atualiz|editar|gravar/.test(rotulo) ? 'Salvando...' : 'Processando...';

                btn.style.minWidth = largura + 'px';
                if (btn.tagName === 'BUTTON') {
                    btn.dataset.sigeiOriginal = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                        '<span class="btn-loading-text">' + texto + '</span>';
                } else {
                    btn.dataset.sigeiOriginal = btn.value;
                    btn.value = texto;
                }
                btn.setAttribute('aria-busy', 'true');
                btn.disabled = true;
            }, 0);
        });

        // Restaura ao voltar pelo histórico (bfcache)
        window.addEventListener('pageshow', function (ev) {
            if (!ev.persisted) return;
            document.querySelectorAll('form[data-sigei-submitting="1"]').forEach(function (f) {
                delete f.dataset.sigeiSubmitting;
                f.querySelectorAll('[data-sigei-original]').forEach(function (b) {
                    if (b.tagName === 'BUTTON') b.innerHTML = b.dataset.sigeiOriginal; else b.value = b.dataset.sigeiOriginal;
                    b.disabled = false;
                    b.style.minWidth = '';
                    b.removeAttribute('aria-busy');
                });
            });
        });
    }

    // Shake em campos inválidos (validação HTML5 nativa)
    function initShake() {
        document.addEventListener('invalid', function (e) {
            var el = e.target;
            if (!el || !el.classList) return;
            el.classList.remove('is-invalid-shake');
            void el.offsetWidth; // reinicia animação
            el.classList.add('is-invalid-shake');
            setTimeout(function () { el.classList.remove('is-invalid-shake'); }, 400);
        }, true);
    }

    function init() {
        initMascaras();
        initSubmit();
        initShake();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
