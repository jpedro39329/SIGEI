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

    // ============================================================
    // 3. POP-UP PRÓPRIO DO SIGEI (AVISOS, SESSÃO E SEGURANÇA)
    // ============================================================
    const AVISO_MODAL_ID = 'sigei-custom-popup-modal';

    const VARIANTES_POPUP = {
        info: {
            corPrimaria: '#0d47a1',
            corFundoIcone: '#e0f2fe',
            corTextoIcone: '#0d47a1',
            codigoPadrao: 'SIGEI-INFO',
            svg: '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>'
        },
        warning: {
            corPrimaria: '#d97706',
            corFundoIcone: '#fef3c7',
            corTextoIcone: '#d97706',
            codigoPadrao: 'SIGEI-SESSAO',
            svg: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'
        },
        danger: {
            corPrimaria: '#dc2626',
            corFundoIcone: '#fee2e2',
            corTextoIcone: '#dc2626',
            codigoPadrao: 'SIGEI-SEC',
            svg: '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>'
        },
        success: {
            corPrimaria: '#16a34a',
            corFundoIcone: '#dcfce7',
            corTextoIcone: '#16a34a',
            codigoPadrao: 'SIGEI-OK',
            svg: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
        }
    };

    /**
     * Exibe o Pop-up Próprio do SIGEI (sem utilizar alert/confirm nativo)
     * @param {Object} opcoes { tipo, codigo, titulo, mensagem, textoPrimario, textoSecundario, onPrimario, onSecundario }
     */
    window.sigeiAviso = function (opcoes = {}) {
        let modal = document.getElementById(AVISO_MODAL_ID);
        if (!modal) {
            modal = document.createElement('div');
            modal.id = AVISO_MODAL_ID;
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.style.cssText = `
                position: fixed;
                top: 0; left: 0; width: 100vw; height: 100vh;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 99998;
                font-family: 'Nunito', 'Segoe UI', Arial, sans-serif;
                padding: 20px;
            `;

            modal.innerHTML = `
                <div style="
                    background: #ffffff;
                    width: 100%;
                    max-width: 440px;
                    border-radius: 18px;
                    padding: 32px 26px 26px;
                    text-align: center;
                    box-shadow: 0 22px 45px rgba(13, 71, 161, 0.18), 0 8px 20px rgba(0, 0, 0, 0.12);
                    border: 1px solid #e2e8f0;
                ">
                    <div id="sigei-popup-icon-box" style="
                        width: 64px; height: 64px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 16px;
                    ">
                        <svg id="sigei-popup-svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"></svg>
                    </div>
                    <div id="sigei-popup-code" style="font-size: 0.74rem; font-weight: 800; letter-spacing: 0.1em; margin-bottom: 4px; text-transform: uppercase;"></div>
                    <h3 id="sigei-popup-title" style="font-size: 1.28rem; font-weight: 800; color: #1e293b; margin: 0 0 8px;"></h3>
                    <p id="sigei-popup-msg" style="font-size: 0.94rem; color: #475569; line-height: 1.55; margin: 0 0 22px;"></p>
                    <div id="sigei-popup-countdown-box" style="
                        display: none;
                        background: #fffbeb;
                        border: 1px solid #fde68a;
                        color: #b45309;
                        border-radius: 10px;
                        padding: 10px 14px;
                        font-size: 0.9rem;
                        font-weight: 700;
                        margin-bottom: 20px;
                    ">
                        Tempo restante: <span id="sigei-popup-countdown-val">60</span>s
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" id="sigei-popup-btn-sec" style="
                            display: none;
                            flex: 1;
                            min-width: 130px;
                            background: #f1f5f9;
                            color: #334155;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            font-size: 0.95rem;
                            font-weight: 700;
                            padding: 11px 18px;
                            cursor: pointer;
                            font-family: inherit;
                        "></button>
                        <button type="button" id="sigei-popup-btn-pri" style="
                            flex: 1;
                            min-width: 150px;
                            background: #0d47a1;
                            color: #ffffff;
                            border: none;
                            border-radius: 10px;
                            font-size: 0.95rem;
                            font-weight: 700;
                            padding: 11px 20px;
                            cursor: pointer;
                            font-family: inherit;
                        "></button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        const tipo = (opcoes.tipo || 'info').toLowerCase();
        const cfg = VARIANTES_POPUP[tipo] || VARIANTES_POPUP.info;

        const iconBox = document.getElementById('sigei-popup-icon-box');
        const svgEl = document.getElementById('sigei-popup-svg');
        const codeEl = document.getElementById('sigei-popup-code');
        const titleEl = document.getElementById('sigei-popup-title');
        const msgEl = document.getElementById('sigei-popup-msg');
        const countdownBox = document.getElementById('sigei-popup-countdown-box');
        const btnPri = document.getElementById('sigei-popup-btn-pri');
        const btnSec = document.getElementById('sigei-popup-btn-sec');

        iconBox.style.background = cfg.corFundoIcone;
        iconBox.style.color = cfg.corTextoIcone;
        svgEl.innerHTML = cfg.svg;

        codeEl.style.color = cfg.corTextoIcone;
        codeEl.textContent = opcoes.codigo || cfg.codigoPadrao;
        titleEl.textContent = opcoes.titulo || 'Aviso do Sistema';
        msgEl.textContent = opcoes.mensagem || '';

        if (opcoes.exibirContador) {
            countdownBox.style.display = 'block';
        } else {
            countdownBox.style.display = 'none';
        }

        btnPri.textContent = opcoes.textoPrimario || 'Entendi';
        btnPri.style.background = opcoes.corBotaoPrimario || '#0d47a1';
        btnPri.onclick = function () {
            modal.style.display = 'none';
            if (typeof opcoes.onPrimario === 'function') {
                opcoes.onPrimario();
            }
        };

        if (opcoes.textoSecundario) {
            btnSec.style.display = 'inline-block';
            btnSec.textContent = opcoes.textoSecundario;
            btnSec.onclick = function () {
                modal.style.display = 'none';
                if (typeof opcoes.onSecundario === 'function') {
                    opcoes.onSecundario();
                }
            };
        } else {
            btnSec.style.display = 'none';
            btnSec.onclick = null;
        }

        modal.style.display = 'flex';
    };

    window.sigeiFecharAviso = function () {
        const modal = document.getElementById(AVISO_MODAL_ID);
        if (modal) modal.style.display = 'none';
    };

    // ============================================================
    // 4. MONITOR DE SESSÃO (AVISO PRÉ-EXPIRAÇÃO E ENCERRAMENTO)
    // ============================================================
    function iniciarMonitorSessao() {
        const cfg = window.SIGEI_CONFIG;
        if (!cfg || !cfg.userId || Number(cfg.userId) <= 0) {
            return;
        }

        const baseUrl = cfg.baseUrl || '../../';
        const timeoutSegundos = Number(cfg.sessaoTimeout) || 600; // 10 min
        const avisoSegundos = Number(cfg.sessaoAviso) || 540;     // 9 min

        let ultimoEventoAtividade = Date.now();
        let ultimoPingServidor = Date.now();
        let avisoExibido = false;

        function registrarAtividadeLocal() {
            if (avisoExibido) {
                // Se o pop-up de aviso já está aberto, exige clique em "Continuar conectado"
                return;
            }
            const agora = Date.now();
            ultimoEventoAtividade = agora;

            // Renova a sessão silenciosamente no servidor a cada 90s se houver atividade real
            if ((agora - ultimoPingServidor) > 90000) {
                ultimoPingServidor = agora;
                fetch(baseUrl + 'controllers/sessao.php?acao=ping', {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store'
                }).catch(function () {});
            }
        }

        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(function (ev) {
            window.addEventListener(ev, registrarAtividadeLocal, { passive: true });
        });

        function renovarSessaoManual() {
            avisoExibido = false;
            ultimoEventoAtividade = Date.now();
            ultimoPingServidor = Date.now();
            window.sigeiFecharAviso();

            fetch(baseUrl + 'controllers/sessao.php?acao=ping', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (res) {
                if (res.status === 401) {
                    window.location.href = baseUrl + 'controllers/sessao.php?acao=encerrar';
                }
            }).catch(function () {});
        }

        setInterval(function () {
            const inativoSegundos = Math.floor((Date.now() - ultimoEventoAtividade) / 1000);
            const restanteSegundos = Math.max(0, timeoutSegundos - inativoSegundos);

            if (restanteSegundos <= 0) {
                window.location.href = baseUrl + 'controllers/sessao.php?acao=encerrar';
                return;
            }

            if (inativoSegundos >= avisoSegundos) {
                if (!avisoExibido) {
                    avisoExibido = true;
                    window.sigeiAviso({
                        tipo: 'warning',
                        codigo: 'SIGEI-SES-001',
                        titulo: 'Sessão prestes a expirar',
                        mensagem: 'Você está inativo há alguns minutos. Por segurança, sua sessão no SIGEI será encerrada automaticamente se não houver confirmação.',
                        exibirContador: true,
                        textoPrimario: 'Continuar conectado',
                        textoSecundario: 'Sair agora',
                        onPrimario: renovarSessaoManual,
                        onSecundario: function () {
                            window.location.href = baseUrl + 'controllers/auth/logout.php';
                        }
                    });
                }
                const countEl = document.getElementById('sigei-popup-countdown-val');
                if (countEl) {
                    countEl.textContent = String(restanteSegundos);
                }
            }
        }, 1000);
    }

    // ============================================================
    // 5. DETECÇÃO AUTOMÁTICA DE AVISOS NA URL (SESSÃO / ACESSO)
    // ============================================================
    function verificarParametrosAvisoUrl() {
        try {
            const params = new URLSearchParams(window.location.search);
            const erro = params.get('erro');
            const aviso = params.get('aviso');

            if (aviso === 'sessao_expirada') {
                window.sigeiAviso({
                    tipo: 'warning',
                    codigo: 'SIGEI-SES-401',
                    titulo: 'Sessão expirada por inatividade',
                    mensagem: 'Sua sessão foi encerrada automaticamente após o período de inatividade. Faça login novamente para continuar utilizando o SIGEI.',
                    textoPrimario: 'Fazer login novamente'
                });
                params.delete('aviso');
                const novaQuery = params.toString();
                window.history.replaceState({}, document.title, window.location.pathname + (novaQuery ? '?' + novaQuery : ''));
            } else if (erro === 'acesso_negado' || erro === 'permissao') {
                window.sigeiAviso({
                    tipo: 'danger',
                    codigo: 'SIGEI-SEC-403',
                    titulo: 'Acesso não autorizado',
                    mensagem: 'Seu perfil atual não possui permissão para acessar o recurso solicitado ou o registro pertence a outra unidade/regional.',
                    textoPrimario: 'Entendi'
                });
                params.delete('erro');
                const novaQuery = params.toString();
                window.history.replaceState({}, document.title, window.location.pathname + (novaQuery ? '?' + novaQuery : ''));
            }
        } catch (e) {}
    }

    // Inicialização ao carregar o DOM
    document.addEventListener('DOMContentLoaded', function () {
        if (!navigator.onLine) {
            exibirAvisoOffline();
        }
        verificarParametrosAvisoUrl();
        iniciarMonitorSessao();
    });
})();

