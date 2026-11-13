/**
 * SIGEI - Senha provisória
 * 1) Cadastro de usuários: preenche o campo "Senha" com uma senha provisória aleatória
 *    (campo marcado com data-senha-gerada) e adiciona um botão de copiar (somente ícone).
 * 2) Primeiro acesso: após aceitar os termos e concluir/pular o tutorial, exibe o modal
 *    "Criar nova senha" e grava a senha definitiva via controllers/auth/definir_senha.php.
 */
(function () {
    'use strict';

    // ---------- 1) GERADOR NO CADASTRO ----------
    function gerarSenha(tamanho) {
        var minus = 'abcdefghijkmnpqrstuvwxyz';
        var maius = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        var nums = '23456789';
        var todos = minus + maius + nums;

        function sorteio(conjunto) {
            var buf = new Uint32Array(1);
            var limite = Math.floor(4294967296 / conjunto.length) * conjunto.length;
            do { crypto.getRandomValues(buf); } while (buf[0] >= limite); // sem viés
            return conjunto.charAt(buf[0] % conjunto.length);
        }

        var chars = [sorteio(minus), sorteio(maius), sorteio(nums)];
        while (chars.length < tamanho) chars.push(sorteio(todos));
        for (var i = chars.length - 1; i > 0; i--) { // embaralha (Fisher-Yates)
            var b = new Uint32Array(1);
            crypto.getRandomValues(b);
            var j = b[0] % (i + 1);
            var t = chars[i]; chars[i] = chars[j]; chars[j] = t;
        }
        return chars.join('');
    }

    function copiar(texto) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(texto);
        }
        return new Promise(function (ok, erro) {
            var ta = document.createElement('textarea');
            ta.value = texto;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy') ? ok() : erro(); } catch (e) { erro(e); }
            document.body.removeChild(ta);
        });
    }

    function initGerador() {
        var campo = document.querySelector('input[data-senha-gerada]');
        if (!campo) return;

        var senha = gerarSenha(10);
        campo.type = 'text';
        campo.readOnly = true;
        campo.value = senha;
        campo.setAttribute('autocomplete', 'off');
        campo.placeholder = '';
        campo.style.fontFamily = 'monospace';
        campo.style.letterSpacing = '0.05em';

        // Confirmação preenchida automaticamente e escondida (senha gerada, não digitada)
        var confirma = document.getElementById('confirmar_senha');
        if (confirma) {
            confirma.value = senha;
            var bloco = confirma.closest('[class*="col-"]') || confirma.parentElement;
            if (bloco) bloco.style.display = 'none';
            confirma.removeAttribute('required');
        }

        // Grupo input + botão copiar (ícone)
        var grupo = document.createElement('div');
        grupo.className = 'input-group';
        campo.parentNode.insertBefore(grupo, campo);
        grupo.appendChild(campo);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-acao';
        btn.setAttribute('aria-label', 'Copiar senha provisória');
        btn.title = 'Copiar senha';
        btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        grupo.appendChild(btn);

        var ajuda = document.createElement('div');
        ajuda.className = 'form-text';
        ajuda.textContent = 'Senha provisória: o usuário deverá criá-la nova no primeiro acesso.';
        grupo.parentNode.appendChild(ajuda);

        btn.addEventListener('click', function () {
            copiar(campo.value).then(function () {
                btn.innerHTML = '<i class="bi bi-clipboard-check text-success"></i>';
                btn.title = 'Copiado!';
            }, function () {
                campo.select();
            });
            setTimeout(function () {
                btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                btn.title = 'Copiar senha';
            }, 1800);
        });
    }

    // ---------- 2) MODAL "CRIAR NOVA SENHA" ----------
    var PERFIS_COM_TOUR = ['USUARIO_EDUCACAO_ESPECIAL', 'USUARIO_ESCOLA', 'USUARIO_UE', 'ESCOLA',
        'SUPERVISOR', 'USUARIO_EMPRESA', 'PAE', 'USUARIO_SEFISC'];

    function tourConcluido(userId) {
        try {
            if (sessionStorage.getItem('sigei_tour_ativo') === '1') return false;
            for (var i = 0; i < localStorage.length; i++) {
                var k = localStorage.key(i);
                if (k.indexOf('sigei_tour_') === 0 && k.slice(-('_' + userId).length) === '_' + userId &&
                    localStorage.getItem(k) === 'concluido') {
                    return true;
                }
            }
        } catch (e) { return true; }
        return false;
    }

    function pronto(cfg) {
        if (cfg.termoAceito !== true) return false;
        if (document.getElementById('termos-modal')) return false;
        if (document.querySelector('.driver-popover')) return false;
        if (PERFIS_COM_TOUR.indexOf(String(cfg.userPerfil || '').toUpperCase()) !== -1) {
            return tourConcluido(cfg.userId);
        }
        return true;
    }

    function abrirModal(cfg) {
        if (document.getElementById('senha-provisoria-modal')) return;

        var el = document.createElement('div');
        el.className = 'modal fade terms-modal';
        el.id = 'senha-provisoria-modal';
        el.setAttribute('tabindex', '-1');
        el.setAttribute('data-bs-backdrop', 'static');
        el.setAttribute('data-bs-keyboard', 'false');
        function campoSenha(id, rotulo) {
            return '<div class="sp-field"><label class="form-label" for="' + id + '">' + rotulo + '</label>' +
                '<div class="sp-input">' +
                '<input type="password" class="form-control" id="' + id + '" autocomplete="new-password" maxlength="72" required>' +
                '<button type="button" class="sp-eye" data-alvo="' + id + '" aria-label="Mostrar senha" title="Mostrar senha"><i class="bi bi-eye"></i></button>' +
                '</div></div>';
        }
        function regra(id, texto) {
            return '<li id="' + id + '" class="sp-rule"><i class="bi bi-circle"></i><span>' + texto + '</span></li>';
        }

        el.innerHTML =
            '<div class="modal-dialog modal-dialog-centered sp-dialog"><div class="modal-content">' +
            '<form id="form-senha-provisoria" novalidate>' +
            '<div class="modal-header"><h5 class="modal-title">Criar nova senha</h5></div>' +
            '<div class="modal-body">' +
            campoSenha('sp-nova', 'Nova senha') +
            '<ul class="sp-rules" aria-live="polite">' +
            regra('sp-r-len', '8 caracteres ou mais') +
            regra('sp-r-letra', 'Uma letra') +
            regra('sp-r-num', 'Um número') +
            regra('sp-r-sim', 'Um símbolo (ex.: ! @ # $)') +
            '</ul>' +
            campoSenha('sp-confirmar', 'Confirmar senha') +
            '<ul class="sp-rules sp-rules-single" aria-live="polite">' + regra('sp-r-conf', 'As senhas conferem') + '</ul>' +
            '<div id="sp-erro" class="sp-erro" role="alert"></div>' +
            '</div>' +
            '<div class="modal-footer"><button type="submit" class="btn btn-primary px-4" id="sp-ok">Ok</button></div>' +
            '</form></div></div>';
        document.body.appendChild(el);

        var modal = null;
        if (window.bootstrap && bootstrap.Modal) {
            modal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
            modal.show();
        } else {
            el.classList.add('show');
            el.style.display = 'block';
        }
        setTimeout(function () { var i = document.getElementById('sp-nova'); if (i) i.focus(); }, 300);

        function avaliar(nova, conf) {
            return {
                len: nova.length >= 8,
                letra: /[A-Za-z]/.test(nova),
                num: /\d/.test(nova),
                sim: /[^A-Za-z0-9]/.test(nova),
                conf: nova !== '' && nova === conf
            };
        }
        function marcar(id, ok) {
            var li = document.getElementById(id);
            li.classList.toggle('ok', ok);
            li.querySelector('i').className = 'bi ' + (ok ? 'bi-check-circle-fill' : 'bi-circle');
        }
        function atualizar() {
            var st = avaliar(document.getElementById('sp-nova').value, document.getElementById('sp-confirmar').value);
            marcar('sp-r-len', st.len); marcar('sp-r-letra', st.letra); marcar('sp-r-num', st.num);
            marcar('sp-r-sim', st.sim); marcar('sp-r-conf', st.conf);
        }
        el.addEventListener('input', atualizar);
        el.addEventListener('click', function (ev) {
            var b = ev.target.closest('.sp-eye');
            if (!b) return;
            var inp = document.getElementById(b.dataset.alvo);
            var ver = inp.type === 'password';
            inp.type = ver ? 'text' : 'password';
            b.innerHTML = '<i class="bi ' + (ver ? 'bi-eye-slash' : 'bi-eye') + '"></i>';
            b.title = b.ariaLabel = ver ? 'Ocultar senha' : 'Mostrar senha';
            inp.focus();
        });

        var form = document.getElementById('form-senha-provisoria');
        var erro = document.getElementById('sp-erro');
        var ok = document.getElementById('sp-ok');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var nova = document.getElementById('sp-nova').value;
            var conf = document.getElementById('sp-confirmar').value;
            erro.textContent = '';

            var st = avaliar(nova, conf);
            if (!(st.len && st.letra && st.num && st.sim && st.conf)) {
                erro.textContent = !st.conf && st.len && st.letra && st.num && st.sim
                    ? 'As senhas não coincidem.' : 'A senha ainda não atende a todos os requisitos.';
                var campo = document.getElementById(st.len && st.letra && st.num && st.sim ? 'sp-confirmar' : 'sp-nova');
                campo.classList.remove('is-invalid-shake'); void campo.offsetWidth; campo.classList.add('is-invalid-shake');
                return;
            }

            ok.disabled = true;
            ok.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Salvando...';

            var corpo = 'csrf_token=' + encodeURIComponent(cfg.csrfToken) +
                '&nova_senha=' + encodeURIComponent(nova) +
                '&confirmar_senha=' + encodeURIComponent(conf);

            fetch(cfg.baseUrl + 'controllers/auth/definir_senha.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': cfg.csrfToken },
                body: corpo
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d && d.sucesso) {
                    cfg.senhaProvisoria = false;
                    if (modal) modal.hide(); else el.remove();
                    setTimeout(function () { el.remove(); document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); }); document.body.classList.remove('modal-open'); document.body.style.overflow = ''; }, 400);
                    // O token CSRF da sessão não muda, mas a sessão foi regenerada: recarrega o estado
                } else {
                    erro.textContent = (d && d.erro) || 'Não foi possível salvar a senha.';
                    ok.disabled = false; ok.textContent = 'Ok';
                }
            }).catch(function () {
                erro.textContent = 'Falha de conexão. Tente novamente.';
                ok.disabled = false; ok.textContent = 'Ok';
            });
        });
    }

    function initModal() {
        var cfg = window.SIGEI_CONFIG || {};
        if (cfg.senhaProvisoria !== true || !cfg.userId) return;

        var timer = setInterval(function () {
            if (cfg.senhaProvisoria !== true) { clearInterval(timer); return; }
            if (pronto(cfg)) {
                clearInterval(timer);
                abrirModal(cfg);
            }
        }, 700);
    }

    function init() {
        initGerador();
        initModal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
