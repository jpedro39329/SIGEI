/**
 * Central de Acessibilidade e Preferências - SIGEI
 * Gerencia tema (claro, escuro, dispositivo), idioma, alto contraste e tamanho da fonte.
 * Salva as preferências em localStorage sem alterar o banco de dados.
 */

(function () {
    const STORAGE_KEY = 'sigei_preferencias_acessibilidade';
    const BASE_FONT_PX = 17; // Base definida em style.css (html { font-size: 17px })
    const MIN_SCALE = 85;
    const MAX_SCALE = 130;
    const STEP_SCALE = 5;

    const padrao = {
        tema: 'claro',        // 'claro' | 'escuro' | 'dispositivo'
        idioma: 'pt-BR',      // 'pt-BR' padrão ativo
        altoContraste: false, // boolean
        escalaFonte: 100      // percentual (85 a 130)
    };

    function carregarPreferencias() {
        try {
            const salvo = localStorage.getItem(STORAGE_KEY);
            if (!salvo) return { ...padrao };
            const parsed = JSON.parse(salvo);
            return {
                tema: ['claro', 'escuro', 'dispositivo'].includes(parsed.tema) ? parsed.tema : padrao.tema,
                idioma: 'pt-BR', // Mantém o português como idioma oficial certificado
                altoContraste: Boolean(parsed.altoContraste),
                escalaFonte: typeof parsed.escalaFonte === 'number'
                    ? Math.min(MAX_SCALE, Math.max(MIN_SCALE, parsed.escalaFonte))
                    : padrao.escalaFonte
            };
        } catch (e) {
            return { ...padrao };
        }
    }

    function salvarPreferencias(prefs) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        } catch (e) {}
    }

    function resolverTemaEfetivo(temaEscolhido) {
        if (temaEscolhido === 'escuro') return 'dark';
        if (temaEscolhido === 'dispositivo') {
            const prefereEscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            return prefereEscuro ? 'dark' : 'light';
        }
        return 'light';
    }

    function aplicarPreferenciasNoDOM(prefs) {
        const root = document.documentElement;
        const temaEfetivo = resolverTemaEfetivo(prefs.tema);

        root.setAttribute('data-sigei-theme', temaEfetivo);
        root.setAttribute('data-bs-theme', temaEfetivo);

        if (prefs.altoContraste) {
            root.setAttribute('data-sigei-contrast', 'high');
        } else {
            root.removeAttribute('data-sigei-contrast');
        }

        const escala = Math.min(MAX_SCALE, Math.max(MIN_SCALE, Number(prefs.escalaFonte) || 100));
        const pxCalculado = ((BASE_FONT_PX * escala) / 100).toFixed(2);
        root.style.fontSize = pxCalculado + 'px';

        if (prefs.idioma) {
            root.setAttribute('lang', prefs.idioma === 'pt-BR' ? 'pt-br' : prefs.idioma);
        }
    }

    // Aplica imediatamente para evitar flash visual no carregamento
    let estadoAtual = carregarPreferencias();
    aplicarPreferenciasNoDOM(estadoAtual);

    // Escuta mudanças no tema do sistema operacional quando em modo 'dispositivo'
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const listenerTemaSistema = function () {
            if (estadoAtual.tema === 'dispositivo') {
                aplicarPreferenciasNoDOM(estadoAtual);
            }
        };
        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', listenerTemaSistema);
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(listenerTemaSistema);
        }
    }

    // Sincroniza preferências entre abas abertas
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY) {
            estadoAtual = carregarPreferencias();
            aplicarPreferenciasNoDOM(estadoAtual);
            atualizarInterfacePainel();
        }
    });

    function atualizarInterfacePainel() {
        const painel = document.getElementById('sigeiA11yPanel');
        if (!painel) return;

        // Botões de tema
        const botoesTema = painel.querySelectorAll('[data-a11y-theme]');
        botoesTema.forEach((btn) => {
            const ativo = btn.getAttribute('data-a11y-theme') === estadoAtual.tema;
            btn.setAttribute('aria-pressed', ativo ? 'true' : 'false');
        });

        // Switch de alto contraste
        const chkContraste = document.getElementById('sigeiA11yContraste');
        if (chkContraste) {
            chkContraste.checked = Boolean(estadoAtual.altoContraste);
        }

        // Indicador e botões de tamanho de fonte
        const badgeFonte = document.getElementById('sigeiA11yFontScaleBadge');
        if (badgeFonte) {
            badgeFonte.textContent = estadoAtual.escalaFonte + '%';
        }
        const btnDiminuir = document.getElementById('sigeiA11yFontDecrease');
        const btnAumentar = document.getElementById('sigeiA11yFontIncrease');
        const btnPadrao = document.getElementById('sigeiA11yFontReset');
        if (btnDiminuir) btnDiminuir.disabled = estadoAtual.escalaFonte <= MIN_SCALE;
        if (btnAumentar) btnAumentar.disabled = estadoAtual.escalaFonte >= MAX_SCALE;
        if (btnPadrao) btnPadrao.disabled = estadoAtual.escalaFonte === 100;

        // Select de idioma
        const selIdioma = document.getElementById('sigeiA11yIdioma');
        if (selIdioma) {
            selIdioma.value = 'pt-BR';
        }
    }

    function construirPainelAcessibilidade() {
        if (document.getElementById('sigeiA11yFab')) {
            return;
        }

        // Injeta regras base para garantir funcionamento mesmo sem cache atualizado de style.css
        if (!document.getElementById('sigeiA11yFallbackStyle')) {
            const st = document.createElement('style');
            st.id = 'sigeiA11yFallbackStyle';
            st.textContent = `
                .sigei-a11y-fab {
                    position: fixed !important;
                    right: 1.25rem !important;
                    bottom: 1.25rem !important;
                    z-index: 9999 !important;
                    width: 3.4rem !important;
                    height: 3.4rem !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    padding: 0 !important;
                    border-radius: 0.65rem !important;
                    border: 2px solid #0038bb !important;
                    background: #0038bb !important;
                    color: #ffffff !important;
                    box-shadow: 0 4px 14px rgba(0, 56, 187, 0.28) !important;
                    cursor: pointer !important;
                }
                .sigei-a11y-backdrop {
                    position: fixed !important;
                    inset: 0 !important;
                    background: rgba(15, 23, 42, 0.25) !important;
                    z-index: 9998 !important;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.15s ease, visibility 0.15s ease;
                }
                .sigei-a11y-backdrop.show {
                    opacity: 1 !important;
                    visibility: visible !important;
                }
                .sigei-a11y-panel {
                    position: fixed !important;
                    right: 1.25rem !important;
                    bottom: 5.1rem !important;
                    width: min(320px, calc(100vw - 2rem)) !important;
                    max-height: calc(100vh - 6rem) !important;
                    overflow-y: auto !important;
                    background: #ffffff;
                    color: #1e293b;
                    border: 1px solid #cbd5e1;
                    border-radius: 0.5rem !important;
                    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15) !important;
                    z-index: 10000 !important;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.15s ease, visibility 0.15s ease;
                }
                .sigei-a11y-panel.show {
                    opacity: 1 !important;
                    visibility: visible !important;
                }
            `;
            document.head.appendChild(st);
        }

        // Garante Bootstrap Icons em todas as páginas (inclusive index e login)
        if (!document.querySelector('link[href*="bootstrap-icons"]')) {
            const linkIcons = document.createElement('link');
            linkIcons.rel = 'stylesheet';
            linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
            document.head.appendChild(linkIcons);
        }

        // Botão fixo flutuante
        const fab = document.createElement('button');
        fab.type = 'button';
        fab.id = 'sigeiA11yFab';
        fab.className = 'sigei-a11y-fab';
        fab.setAttribute('aria-label', 'Acessibilidade e Preferências');
        fab.setAttribute('aria-expanded', 'false');
        fab.setAttribute('aria-controls', 'sigeiA11yPanel');
        fab.title = 'Acessibilidade';
        fab.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4.143A1.071 1.071 0 1 0 8 2a1.071 1.071 0 0 0 0 2.143m-4.668 1.47 3.24.316v2.5l-.323 4.585A.383.383 0 0 0 7 13.14l.826-4.017c.045-.18.301-.18.346 0L9 13.139a.383.383 0 0 0 .752-.125L9.43 8.43v-2.5l3.239-.316a.38.38 0 0 0-.047-.756H3.379a.38.38 0 0 0-.047.756Z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8"/></svg>`;

        // Backdrop
        const backdrop = document.createElement('div');
        backdrop.id = 'sigeiA11yBackdrop';
        backdrop.className = 'sigei-a11y-backdrop';
        backdrop.setAttribute('aria-hidden', 'true');

        // Painel
        const panel = document.createElement('section');
        panel.id = 'sigeiA11yPanel';
        panel.className = 'sigei-a11y-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'false');
        panel.setAttribute('aria-labelledby', 'sigeiA11yTitle');
        panel.setAttribute('aria-hidden', 'true');

        panel.innerHTML = `
            <div class="sigei-a11y-header">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <strong id="sigeiA11yTitle" style="font-size:0.98rem;margin:0;">Preferências</strong>
                </div>
                <button type="button" id="sigeiA11yCloseBtn" aria-label="Fechar painel de preferências" style="background:transparent;border:0;font-size:1.35rem;line-height:1;cursor:pointer;padding:0.1rem 0.35rem;color:inherit;">&times;</button>
            </div>
            <div class="sigei-a11y-body">
                <!-- 1. Tema -->
                <div class="sigei-a11y-section">
                    <div class="sigei-a11y-label" id="sigeiA11yThemeLabel">
                        <span>Tema</span>
                    </div>
                    <div class="sigei-a11y-theme-grid" role="group" aria-labelledby="sigeiA11yThemeLabel">
                        <button type="button" class="sigei-a11y-option-btn" data-a11y-theme="claro" aria-pressed="true">
                            <span>Claro</span>
                        </button>
                        <button type="button" class="sigei-a11y-option-btn" data-a11y-theme="escuro" aria-pressed="false">
                            <span>Escuro</span>
                        </button>
                        <button type="button" class="sigei-a11y-option-btn" data-a11y-theme="dispositivo" aria-pressed="false">
                            <span>Sistema</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Alto Contraste -->
                <div class="sigei-a11y-section">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                        <label class="sigei-a11y-label" for="sigeiA11yContraste" style="cursor:pointer;margin-bottom:0;">
                            <span>Alto Contraste</span>
                        </label>
                        <input class="form-check-input" type="checkbox" role="switch" id="sigeiA11yContraste" aria-label="Ativar modo de alto contraste" style="cursor:pointer;width:1.25rem;height:1.25rem;">
                    </div>
                </div>

                <!-- 3. Tamanho da Fonte -->
                <div class="sigei-a11y-section">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.45rem;">
                        <div class="sigei-a11y-label" style="margin-bottom:0;">
                            <span>Tamanho da Fonte</span>
                        </div>
                        <span class="sigei-a11y-badge-scale" id="sigeiA11yFontScaleBadge" aria-live="polite">100%</span>
                    </div>
                    <div class="sigei-a11y-font-controls" role="group" aria-label="Controles de tamanho da fonte">
                        <button type="button" class="sigei-a11y-font-btn" id="sigeiA11yFontDecrease" title="Diminuir fonte" aria-label="Diminuir tamanho da fonte">
                            A-
                        </button>
                        <button type="button" class="sigei-a11y-font-btn" id="sigeiA11yFontReset" title="Tamanho padrão" aria-label="Restaurar tamanho padrão da fonte">
                            Padrão
                        </button>
                        <button type="button" class="sigei-a11y-font-btn" id="sigeiA11yFontIncrease" title="Aumentar fonte" aria-label="Aumentar tamanho da fonte">
                            A+
                        </button>
                    </div>
                </div>

                <!-- 4. Idioma -->
                <div class="sigei-a11y-section">
                    <label class="sigei-a11y-label" for="sigeiA11yIdioma">
                        <span>Idioma</span>
                    </label>
                    <select class="form-select form-select-sm" id="sigeiA11yIdioma" style="width:100%;padding:0.4rem 0.6rem;border-radius:0.4rem;border:1px solid #cbd5e1;">
                        <option value="pt-BR" selected>Português (Brasil)</option>
                        <option value="es-ES" disabled>Español (Indisponível)</option>
                        <option value="en-US" disabled>English (Indisponível)</option>
                    </select>
                </div>

                <!-- Botão de Restaurar Tudo -->
                <div style="padding-top:0.25rem;">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="sigeiA11yResetAll" style="width:100%;padding:0.45rem 0.75rem;border-radius:0.4rem;border:1px solid #cbd5e1;background:transparent;cursor:pointer;font-weight:600;">
                        Restaurar padrão
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(backdrop);
        document.body.appendChild(panel);
        document.body.appendChild(fab);

        function abrirPainel() {
            panel.classList.add('show');
            backdrop.classList.add('show');
            panel.setAttribute('aria-hidden', 'false');
            fab.setAttribute('aria-expanded', 'true');
            atualizarInterfacePainel();
            const primeiroBotao = panel.querySelector('[data-a11y-theme="' + estadoAtual.tema + '"]') || panel.querySelector('button');
            if (primeiroBotao) primeiroBotao.focus();
        }

        function fecharPainel(devolverFoco = true) {
            panel.classList.remove('show');
            backdrop.classList.remove('show');
            panel.setAttribute('aria-hidden', 'true');
            fab.setAttribute('aria-expanded', 'false');
            if (devolverFoco) fab.focus();
        }

        fab.addEventListener('click', function () {
            if (panel.classList.contains('show')) {
                fecharPainel(true);
            } else {
                abrirPainel();
            }
        });

        backdrop.addEventListener('click', function () {
            fecharPainel(false);
        });

        document.getElementById('sigeiA11yCloseBtn')?.addEventListener('click', function () {
            fecharPainel(true);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.classList.contains('show')) {
                fecharPainel(true);
            }
        });

        // Eventos de Tema
        panel.querySelectorAll('[data-a11y-theme]').forEach((btn) => {
            btn.addEventListener('click', function () {
                estadoAtual.tema = this.getAttribute('data-a11y-theme') || 'claro';
                salvarPreferencias(estadoAtual);
                aplicarPreferenciasNoDOM(estadoAtual);
                atualizarInterfacePainel();
            });
        });

        // Evento de Alto Contraste
        document.getElementById('sigeiA11yContraste')?.addEventListener('change', function () {
            estadoAtual.altoContraste = Boolean(this.checked);
            salvarPreferencias(estadoAtual);
            aplicarPreferenciasNoDOM(estadoAtual);
            atualizarInterfacePainel();
        });

        // Eventos de Fonte
        document.getElementById('sigeiA11yFontDecrease')?.addEventListener('click', function () {
            estadoAtual.escalaFonte = Math.max(MIN_SCALE, estadoAtual.escalaFonte - STEP_SCALE);
            salvarPreferencias(estadoAtual);
            aplicarPreferenciasNoDOM(estadoAtual);
            atualizarInterfacePainel();
        });

        document.getElementById('sigeiA11yFontIncrease')?.addEventListener('click', function () {
            estadoAtual.escalaFonte = Math.min(MAX_SCALE, estadoAtual.escalaFonte + STEP_SCALE);
            salvarPreferencias(estadoAtual);
            aplicarPreferenciasNoDOM(estadoAtual);
            atualizarInterfacePainel();
        });

        document.getElementById('sigeiA11yFontReset')?.addEventListener('click', function () {
            estadoAtual.escalaFonte = 100;
            salvarPreferencias(estadoAtual);
            aplicarPreferenciasNoDOM(estadoAtual);
            atualizarInterfacePainel();
        });

        // Evento de Idioma
        document.getElementById('sigeiA11yIdioma')?.addEventListener('change', function () {
            this.value = 'pt-BR';
            estadoAtual.idioma = 'pt-BR';
            salvarPreferencias(estadoAtual);
        });

        // Evento Restaurar Tudo
        document.getElementById('sigeiA11yResetAll')?.addEventListener('click', function () {
            estadoAtual = { ...padrao };
            salvarPreferencias(estadoAtual);
            aplicarPreferenciasNoDOM(estadoAtual);
            atualizarInterfacePainel();
        });

        atualizarInterfacePainel();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', construirPainelAcessibilidade);
    } else {
        construirPainelAcessibilidade();
    }
})();

