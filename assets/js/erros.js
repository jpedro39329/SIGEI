/**
 * SIGEI - SISTEMA CENTRAL DE TRATAMENTO DE ERROS E CONEXÃO
 * Gerencia desconexão de rede (Sem internet), erros HTTP e modais amigáveis
 */

(function () {
    'use strict';

    // ============================================================
    // 1. MODAL/CARD "SEM INTERNET" (OFFLINE / ONLINE)
    // ============================================================
    const OFFLINE_MODAL_ID = 'sigei-offline-modal';

    function criarModalOffline() {
        if (document.getElementById(OFFLINE_MODAL_ID)) return;

        const container = document.createElement('div');
        container.id = OFFLINE_MODAL_ID;
        container.style.cssText = `
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
            padding: 20px;
        `;

        container.innerHTML = `
            <div style="
                background: #ffffff;
                width: 100%;
                max-width: 400px;
                border-radius: 16px;
                padding: 32px 24px;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                border: 1px solid #e2e8f0;
                animation: sigeiFadeIn 0.25s ease-out;
            ">
                <div style="
                    width: 64px; height: 64px;
                    border-radius: 50%;
                    background: #fee2e2;
                    color: #ef4444;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 16px;
                ">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                        <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
                        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                        <line x1="12" y1="20" x2="12.01" y2="20"></line>
                    </svg>
                </div>
                <div style="font-size: 0.75rem; font-weight: 800; color: #ef4444; letter-spacing: 0.1em; margin-bottom: 4px;">SIGEI-NET-001</div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 8px;">Sem internet!</h3>
                <p style="font-size: 0.92rem; color: #64748b; line-height: 1.5; margin: 0 0 24px;">Verifique sua conexão e tente novamente.</p>
                <button type="button" id="btn-retestar-conexao" style="
                    background: #114ecf;
                    color: #ffffff;
                    border: none;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    font-weight: 700;
                    padding: 10px 24px;
                    cursor: pointer;
                    width: 100%;
                    transition: background 0.2s;
                ">Tentar novamente</button>
            </div>
        `;

        document.body.appendChild(container);

        document.getElementById('btn-retestar-conexao').addEventListener('click', function () {
            if (navigator.onLine) {
                container.style.display = 'none';
            } else {
                this.textContent = 'Ainda sem conexão...';
                setTimeout(() => {
                    this.textContent = 'Tentar novamente';
                }, 1500);
            }
        });
    }

    function exibirAvisoOffline() {
        criarModalOffline();
        const el = document.getElementById(OFFLINE_MODAL_ID);
        if (el) el.style.display = 'flex';
    }

    function ocultarAvisoOffline() {
        const el = document.getElementById(OFFLINE_MODAL_ID);
        if (el) el.style.display = 'none';
    }

    window.addEventListener('offline', exibirAvisoOffline);
    window.addEventListener('online', ocultarAvisoOffline);

    // ============================================================
    // 2. MODAL CENTRAL DE ERRO AMIGÁVEL COM CÓDIGO
    // ============================================================
    const ERROR_MODAL_ID = 'sigei-global-error-modal';

    window.sigeiExibirErro = function (titulo, mensagem, codigo) {
        let modal = document.getElementById(ERROR_MODAL_ID);
        if (!modal) {
            modal = document.createElement('div');
            modal.id = ERROR_MODAL_ID;
            modal.style.cssText = `
                position: fixed;
                top: 0; left: 0; width: 100vw; height: 100vh;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
                padding: 20px;
            `;

            modal.innerHTML = `
                <div style="
                    background: #ffffff;
                    width: 100%;
                    max-width: 420px;
                    border-radius: 16px;
                    padding: 32px 24px;
                    text-align: center;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                    border: 1px solid #e2e8f0;
                ">
                    <div style="
                        width: 60px; height: 60px;
                        border-radius: 50%;
                        background: #fee2e2;
                        color: #dc2626;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 16px;
                    ">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div id="sigei-error-code" style="font-size: 0.75rem; font-weight: 800; color: #dc2626; letter-spacing: 0.1em; margin-bottom: 4px;"></div>
                    <h3 id="sigei-error-title" style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 8px;"></h3>
                    <p id="sigei-error-msg" style="font-size: 0.92rem; color: #64748b; line-height: 1.5; margin: 0 0 24px;"></p>
                    <button type="button" id="btn-fechar-sigei-error" style="
                        background: #114ecf;
                        color: #ffffff;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        font-weight: 700;
                        padding: 10px 24px;
                        cursor: pointer;
                        width: 100%;
                    ">Fechar</button>
                </div>
            `;
            document.body.appendChild(modal);

            document.getElementById('btn-fechar-sigei-error').addEventListener('click', function () {
                modal.style.display = 'none';
            });
        }

        document.getElementById('sigei-error-title').textContent = titulo || 'Ocorreu um problema';
        document.getElementById('sigei-error-msg').textContent = mensagem || 'Não foi possível concluir esta operação.';
        document.getElementById('sigei-error-code').textContent = codigo ? String(codigo) : 'SIGEI-ERR-500';
        modal.style.display = 'flex';
    };

    // Inicialização ao carregar o DOM
    document.addEventListener('DOMContentLoaded', function () {
        if (!navigator.onLine) {
            exibirAvisoOffline();
        }
    });
})();

