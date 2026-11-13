/**
 * SIGEI - Banner de Consentimento de Cookies e Privacidade (LGPD)
 * Exibe aviso discreto e funcional em todas as páginas públicas e autenticadas.
 */
(function () {
    'use strict';

    var CHAVE_STORAGE = 'sigei_cookies_aceitos';

    function jaConsentiu() {
        try {
            return localStorage.getItem(CHAVE_STORAGE) === 'true';
        } catch (e) {
            return false;
        }
    }

    function registrarConsentimento() {
        try {
            localStorage.setItem(CHAVE_STORAGE, 'true');
        } catch (e) {}

        var banner = document.getElementById('sigei-cookie-banner');
        if (banner) {
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(20px)';
            setTimeout(function () {
                if (banner && banner.parentNode) {
                    banner.parentNode.removeChild(banner);
                }
            }, 300);
        }
    }

    function criarBanner() {
        if (jaConsentiu() || document.getElementById('sigei-cookie-banner')) {
            return;
        }

        var container = document.createElement('div');
        container.id = 'sigei-cookie-banner';
        container.setAttribute('role', 'dialog');
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-label', 'Consentimento de Cookies');
        
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.maxWidth = '420px';
        container.style.backgroundColor = '#ffffff';
        container.style.color = '#1e293b';
        container.style.borderRadius = '12px';
        container.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1)';
        container.style.border = '1px solid #e2e8f0';
        container.style.padding = '18px 20px';
        container.style.zIndex = '99999';
        container.style.fontFamily = "'Nunito', 'Segoe UI', system-ui, -apple-system, sans-serif";
        container.style.fontSize = '0.9rem';
        container.style.lineHeight = '1.45';
        container.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';

        container.innerHTML = 
            '<div style="display:flex;align-items:flex-start;gap:12px;">' +
                '<div style="color:#0038bb;font-size:1.3rem;line-height:1;margin-top:2px;">' +
                    '<i class="bi bi-shield-check"></i>' +
                '</div>' +
                '<div style="flex:1;">' +
                    '<strong style="display:block;font-size:0.95rem;color:#0f172a;margin-bottom:4px;">Privacidade e Cookies</strong>' +
                    '<p style="margin:0 0 12px 0;color:#64748b;font-size:0.83rem;">' +
                        'O SIGEI utiliza cookies essenciais para manter sua sessão segura e garantir o funcionamento das políticas de proteção de dados (LGPD).' +
                    '</p>' +
                    '<div style="display:flex;justify-content:flex-end;gap:8px;">' +
                        '<button type="button" id="sigei-btn-cookie-aceitar" class="btn btn-sm btn-primary" style="padding:6px 18px;border-radius:6px;font-weight:600;font-size:0.82rem;background:#0038bb;border-color:#0038bb;color:#fff;cursor:pointer;">' +
                            'Entendi e Aceito' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(container);

        setTimeout(function () {
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 100);

        var btn = document.getElementById('sigei-btn-cookie-aceitar');
        if (btn) {
            btn.addEventListener('click', registrarConsentimento);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', criarBanner);
    } else {
        criarBanner();
    }
})();
