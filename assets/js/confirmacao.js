/**
 * Sistema Unificado de Confirmação de Ações - SIGEI
 * Substitui confirm(), alert() e prompts nativos por um Modal Bootstrap moderno e seguro.
 */

(function () {
    'use strict';

    let modalInstance = null;
    let modalElement = null;
    let pendingResolver = null;

    /**
     * Mapeamento de estilos visuais (variantes)
     */
    const TIPOS_CONFIG = {
        danger: {
            btnClass: 'btn-danger',
            icon: 'bi-exclamation-triangle-fill',
            iconColor: '#dc3545',
            iconBg: '#fee2e2',
            titleDefault: 'Confirmar exclusão',
            actionDefault: 'Excluir'
        },
        warning: {
            btnClass: 'btn-warning text-dark',
            icon: 'bi-exclamation-circle-fill',
            iconColor: '#d97706',
            iconBg: '#fef3c7',
            titleDefault: 'Atenção',
            actionDefault: 'Continuar'
        },
        success: {
            btnClass: 'btn-success',
            icon: 'bi-check-circle-fill',
            iconColor: '#16a34a',
            iconBg: '#dcfce7',
            titleDefault: 'Confirmar aprovação',
            actionDefault: 'Aprovar'
        },
        primary: {
            btnClass: 'btn-primary',
            icon: 'bi-info-circle-fill',
            iconColor: '#0d47a1',
            iconBg: '#e0f2fe',
            titleDefault: 'Confirmar ação',
            actionDefault: 'Confirmar'
        }
    };

    /**
     * Garante a criação única do modal no DOM
     */
    function obterModalElement() {
        if (modalElement) return modalElement;

        const existente = document.getElementById('sigei-confirm-modal');
        if (existente) {
            modalElement = existente;
            return modalElement;
        }

        const container = document.createElement('div');
        container.innerHTML = `
            <div class="modal fade sigei-confirm-modal" id="sigei-confirm-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-body p-4 text-center">
                            <!-- Ícone Temático -->
                            <div class="sigei-confirm-icon-wrapper mx-auto mb-3" id="sigei-confirm-icon-box">
                                <i id="sigei-confirm-icon" class="bi"></i>
                            </div>

                            <!-- Título -->
                            <h5 class="fw-bold mb-2 text-dark" id="sigei-confirm-title">Confirmar ação</h5>

                            <!-- Mensagem Principal -->
                            <p class="text-muted mb-0 sigei-confirm-message" id="sigei-confirm-message">
                                Tem certeza de que deseja prosseguir?
                            </p>
                        </div>
                        <div class="modal-footer border-0 bg-light p-3 gap-2 d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary px-4 fw-semibold rounded-3" id="sigei-confirm-btn-cancel">
                                Cancelar
                            </button>
                            <button type="button" class="btn px-4 fw-semibold rounded-3" id="sigei-confirm-btn-confirm">
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `.trim();

        modalElement = container.firstElementChild;
        document.body.appendChild(modalElement);

        // Eventos dos botões
        const btnCancel = modalElement.querySelector('#sigei-confirm-btn-cancel');
        const btnConfirm = modalElement.querySelector('#sigei-confirm-btn-confirm');

        btnCancel.addEventListener('click', function () {
            fecharModal(false);
        });

        btnConfirm.addEventListener('click', function () {
            fecharModal(true);
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (pendingResolver) {
                pendingResolver(false);
                pendingResolver = null;
            }
        });

        return modalElement;
    }

    /**
     * Sanitiza HTML da mensagem permitindo apenas <br>, <small> e <strong> seguros
     */
    function sanitizarMensagemHTML(str) {
        const div = document.createElement('div');
        div.textContent = String(str || '');
        let seguro = div.innerHTML;
        seguro = seguro
            .replace(/&lt;br\s*\/?&gt;/gi, '<br>')
            .replace(/&lt;small(\s+class=&quot;text-muted&quot;)?&gt;/gi, '<small class="text-muted">')
            .replace(/&lt;\/small&gt;/gi, '</small>')
            .replace(/&lt;strong&gt;/gi, '<strong>')
            .replace(/&lt;\/strong&gt;/gi, '</strong>');
        return seguro;
    }

    /**
     * Fecha o modal resolvendo a Promise
     */
    function fecharModal(confirmado) {
        if (modalInstance) {
            modalInstance.hide();
        } else if (modalElement) {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            modalElement.setAttribute('aria-hidden', 'true');
            const backdrop = document.getElementById('sigei-confirm-fallback-backdrop');
            if (backdrop) backdrop.remove();
        }
        if (pendingResolver) {
            pendingResolver(confirmado);
            pendingResolver = null;
        }
    }

    /**
     * Submete uma URL de controller via POST com token CSRF
     */
    function submeterLinkViaPost(href) {
        try {
            const urlObj = new URL(href, window.location.href);
            // Se for logout, redirecionamento direto é suficiente
            if (urlObj.pathname.endsWith('/logout.php')) {
                window.location.href = href;
                return;
            }
            // Se apontar para um controller de ação, envia via POST com csrf_token
            if (urlObj.pathname.indexOf('/controllers/') !== -1) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = urlObj.origin + urlObj.pathname + urlObj.search;
                form.style.display = 'none';

                urlObj.searchParams.forEach(function (val, key) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = val;
                    form.appendChild(input);
                });

                const csrfToken = (window.SIGEI_CONFIG && window.SIGEI_CONFIG.csrfToken) ? window.SIGEI_CONFIG.csrfToken : '';
                if (csrfToken && !urlObj.searchParams.has('csrf_token')) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = csrfToken;
                    form.appendChild(csrfInput);
                }

                document.body.appendChild(form);
                form.submit();
                return;
            }
        } catch (err) {
            // Fallback caso URL seja inválida
        }
        window.location.href = href;
    }

    /**
     * Função global reutilizável
     * @param {Object} opcoes
     * @returns {Promise<boolean>}
     */
    window.confirmarAcao = function (opcoes = {}) {
        return new Promise((resolve) => {
            const el = obterModalElement();

            const type = (opcoes.type || opcoes.variante || 'danger').toLowerCase();
            const config = TIPOS_CONFIG[type] || TIPOS_CONFIG.danger;

            const title = opcoes.title || opcoes.titulo || config.titleDefault;
            const message = opcoes.message || opcoes.mensagem || 'Tem certeza de que deseja realizar esta ação?<br><small class="text-muted">Esta ação não poderá ser desfeita.</small>';
            const actionText = opcoes.actionText || opcoes.btnConfirmar || config.actionDefault;
            const cancelText = opcoes.cancelText || opcoes.btnCancelar || 'Cancelar';
            const iconClass = opcoes.icon || config.icon;

            // Elementos visuais
            const iconBox = el.querySelector('#sigei-confirm-icon-box');
            const iconEl = el.querySelector('#sigei-confirm-icon');
            const titleEl = el.querySelector('#sigei-confirm-title');
            const messageEl = el.querySelector('#sigei-confirm-message');
            const btnCancel = el.querySelector('#sigei-confirm-btn-cancel');
            const btnConfirm = el.querySelector('#sigei-confirm-btn-confirm');

            // Configurar ícone e cores
            iconBox.style.backgroundColor = config.iconBg;
            iconEl.className = `bi ${iconClass}`;
            iconEl.style.color = config.iconColor;
            iconEl.style.fontSize = '1.75rem';

            // Configurar textos com sanitização segura
            titleEl.textContent = title;
            messageEl.innerHTML = sanitizarMensagemHTML(message);
            btnCancel.textContent = cancelText;
            btnConfirm.textContent = actionText;

            // Configurar classe do botão de confirmação
            btnConfirm.className = `btn px-4 fw-semibold rounded-3 ${opcoes.btnClass || config.btnClass}`;

            pendingResolver = resolve;

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                modalInstance = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                modalInstance.show();
            } else {
                // Fallback DOM próprio do SIGEI caso Bootstrap JS não esteja carregado (sem usar window.confirm nativo)
                el.style.display = 'block';
                el.classList.add('show');
                el.removeAttribute('aria-hidden');
                if (!document.getElementById('sigei-confirm-fallback-backdrop')) {
                    const backdrop = document.createElement('div');
                    backdrop.id = 'sigei-confirm-fallback-backdrop';
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            }
        });
    };

    /**
     * Interceptador Automático com Delegação de Eventos
     * Captura qualquer link, botão ou formulário com data-confirm="true" OU .btn-confirmar-exclusao
     */
    document.addEventListener('click', async function (e) {
        // Busca elemento com data-confirm="true" ou classe .btn-confirmar-exclusao
        const trigger = e.target.closest('[data-confirm="true"], .btn-confirmar-exclusao');
        if (!trigger) return;

        // Se for um link <a>
        if (trigger.tagName === 'A' && trigger.getAttribute('href') && trigger.getAttribute('href') !== '#') {
            e.preventDefault();
            e.stopPropagation();

            const confirmado = await extrairEConfirmar(trigger);
            if (confirmado) {
                submeterLinkViaPost(trigger.href);
            }
            return;
        }

        // Se for um botão de submit dentro de form
        if (trigger.tagName === 'BUTTON' || (trigger.tagName === 'INPUT' && trigger.type === 'submit')) {
            const form = trigger.closest('form');
            if (form && !form.hasAttribute('data-confirm')) {
                e.preventDefault();
                e.stopPropagation();

                const confirmado = await extrairEConfirmar(trigger);
                if (confirmado) {
                    // Adiciona flag temporária para não re-interceptar
                    form._sigeiConfirmado = true;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(trigger);
                    } else {
                        form.submit();
                    }
                }
            }
        }
    }, true);

    /**
     * Interceptador para formulários com data-confirm="true"
     */
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!form || !form.matches || !form.matches('[data-confirm="true"]')) {
            return;
        }

        if (form._sigeiConfirmado) {
            delete form._sigeiConfirmado;
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const confirmado = await extrairEConfirmar(form);
        if (confirmado) {
            form._sigeiConfirmado = true;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    }, true);

    /**
     * Extrai os atributos data-* e dispara a confirmação
     */
    function extrairEConfirmar(el) {
        const type = el.dataset.confirmType || el.dataset.confirmVariant || 'danger';
        const title = el.dataset.confirmTitle || (el.getAttribute('title') && el.getAttribute('title') !== 'Excluir' ? el.getAttribute('title') : 'Confirmar exclusão');
        const message = el.dataset.confirmMessage || el.dataset.msg || 'Tem certeza de que deseja excluir este registro?<br><small class="text-muted">Esta ação não poderá ser desfeita.</small>';
        const actionText = el.dataset.confirmAction || el.dataset.confirmBtn || 'Sim, excluir';
        const cancelText = el.dataset.confirmCancel || 'Cancelar';
        const icon = el.dataset.confirmIcon || '';

        return window.confirmarAcao({
            type,
            title,
            message,
            actionText,
            cancelText,
            icon
        });
    }

})();

