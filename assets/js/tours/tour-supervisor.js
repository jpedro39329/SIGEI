/**
 * SIGEI - Módulo de Tour Guiado Interativo (Driver.js v1.3.1)
 * Roteiro Completo Multi-Página para o Perfil: Supervisor da Empresa Terceirizada
 */

(function () {
    'use strict';

    window.SIGEI_TOURS = window.SIGEI_TOURS || {};

    const STORAGE_TOUR_PREFIX = 'sigei_tour_supervisor_';
    const SESSION_TOUR_ATIVO  = 'sigei_tour_ativo';
    const SESSION_TOUR_ETAPA  = 'sigei_tour_etapa';

    class SigeiTourSupervisor {
        constructor() {
            this.config = window.SIGEI_CONFIG || {};
            this.userId = this.config.userId || 0;
            this.userPerfil = (this.config.userPerfil || '').toUpperCase();
            this.baseUrl = this.config.baseUrl || '../';
            this.driverObj = null;
            this.pageSteps = [];
            this.STEPS_CONFIG = this.buildStepsConfig();
            this.registrarDelegacaoGlobalDeCliques();
        }

        isPerfilSupervisor() {
            const p = this.userPerfil;
            return p === 'SUPERVISOR' || p === 'USUARIO_EMPRESA';
        }

        getStorageKey() {
            return STORAGE_TOUR_PREFIX + this.userId;
        }

        jaConcluiu() {
            try {
                return localStorage.getItem(this.getStorageKey()) === 'concluido';
            } catch (e) {
                return false;
            }
        }

        marcarConcluido() {
            try {
                localStorage.setItem(this.getStorageKey(), 'concluido');
                sessionStorage.removeItem(SESSION_TOUR_ATIVO);
                sessionStorage.removeItem(SESSION_TOUR_ETAPA);
            } catch (e) {}
        }

        limparEstadoParaReiniciar() {
            try {
                localStorage.removeItem(this.getStorageKey());
                sessionStorage.setItem(SESSION_TOUR_ATIVO, '1');
                sessionStorage.setItem(SESSION_TOUR_ETAPA, '0');
            } catch (e) {}
        }

        pularTour() {
            this.marcarConcluido();
            if (this.driverObj) {
                try {
                    this.driverObj.destroy();
                } catch (e) {}
                this.driverObj = null;
            }
            document.querySelectorAll('.driver-popover, .driver-overlay').forEach(el => el.remove());
            document.body.classList.remove('driver-active', 'driver-fade', 'driver-simple');
            document.documentElement.classList.remove('driver-active', 'driver-fade', 'driver-simple');
        }

        reiniciarTutorial() {
            this.limparEstadoParaReiniciar();
            window.location.href = this.baseUrl + 'views/dashboard.php';
        }

        modalTermosEstaVisivel() {
            const modal = document.getElementById('termos-modal');
            if (!modal) return false;
            const style = window.getComputedStyle(modal);
            return modal.classList.contains('show') || style.display !== 'none' || document.body.classList.contains('modal-open');
        }

        termosJaForamAceitos() {
            if (window.SIGEI_CONFIG && window.SIGEI_CONFIG.termoAceito === true) {
                return true;
            }
            try {
                const chaveLocal = 'sigei_termos_aceitos_' + this.userId + '_' + this.userPerfil;
                if (localStorage.getItem(chaveLocal) === 'true') {
                    return true;
                }
            } catch (e) {}
            return false;
        }

        getCurrentPageKey() {
            const path = window.location.pathname.replace(/\\/g, '/');
            if (path.includes('views/dashboard.php')) return 'views/dashboard.php';
            if (path.includes('views/alunos/listar.php')) return 'views/alunos/listar.php';
            if (path.includes('views/paes/listar.php')) return 'views/paes/listar.php';
            if (path.includes('views/associacoes/gerenciar.php')) return 'views/associacoes/gerenciar.php';
            if (path.includes('views/relatorios/listar.php')) return 'views/relatorios/listar.php';
            if (path.includes('views/perfil.php')) return 'views/perfil.php';
            return '';
        }

        registrarDelegacaoGlobalDeCliques() {
            document.addEventListener('click', (e) => {
                const btnPular = e.target.closest('#sigei-tour-btn-pular, .driver-popover-skip-btn');
                if (btnPular) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.pularTour();
                    return;
                }

                const btnNext = e.target.closest('.driver-popover-next-btn');
                if (btnNext && this.driverObj) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.acaoProximo();
                    return;
                }

                const btnPrev = e.target.closest('.driver-popover-prev-btn');
                if (btnPrev && this.driverObj) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.acaoAnterior();
                    return;
                }
            }, true);
        }

        acaoProximo() {
            if (!this.driverObj || !this.pageSteps.length) return;
            const activeLocalIdx = this.driverObj.getActiveIndex() ?? 0;
            const currentStepCfg = this.pageSteps[activeLocalIdx] || this.pageSteps[0];
            const nextLocalIdx   = activeLocalIdx + 1;

            if (nextLocalIdx < this.pageSteps.length) {
                const nextStepCfg = this.pageSteps[nextLocalIdx];
                try {
                    sessionStorage.setItem(SESSION_TOUR_ETAPA, String(nextStepCfg.index));
                } catch (e) {}
                this.driverObj.moveNext();
            } else {
                const nextGlobalIdx = currentStepCfg.index + 1;
                if (nextGlobalIdx >= this.STEPS_CONFIG.length) {
                    this.pularTour();
                } else {
                    this.redirecionarParaIndiceGlobal(nextGlobalIdx);
                }
            }
        }

        acaoAnterior() {
            if (!this.driverObj || !this.pageSteps.length) return;
            const activeLocalIdx = this.driverObj.getActiveIndex() ?? 0;
            const currentStepCfg = this.pageSteps[activeLocalIdx] || this.pageSteps[0];
            const prevLocalIdx   = activeLocalIdx - 1;

            if (prevLocalIdx >= 0) {
                const prevStepCfg = this.pageSteps[prevLocalIdx];
                try {
                    sessionStorage.setItem(SESSION_TOUR_ETAPA, String(prevStepCfg.index));
                } catch (e) {}
                this.driverObj.movePrevious();
            } else {
                const prevGlobalIdx = currentStepCfg.index - 1;
                if (prevGlobalIdx >= 0) {
                    this.redirecionarParaIndiceGlobal(prevGlobalIdx);
                }
            }
        }

        buildStepsConfig() {
            return [
                // ============================================================
                // BLOCO A — PAINEL INICIAL (views/dashboard.php)
                // ============================================================
                {
                    index: 0,
                    url: 'views/dashboard.php',
                    element: null,
                    fallback: null,
                    side: 'over',
                    align: 'center',
                    title: 'Boas-vindas ao Painel do Supervisor',
                    description: 'Bem-vindo ao sistema de gestão da empresa terceirizada. Este guia interativo apresentará o panorama operacional da empresa, o cadastro de cuidadores, a vinculação com estudantes e o acompanhamento dos relatórios.'
                },
                {
                    index: 1,
                    url: 'views/dashboard.php',
                    element: '#graficoSupervisor',
                    fallback: '.card',
                    side: 'bottom',
                    align: 'start',
                    title: 'Panorama Operacional da Empresa',
                    description: 'Neste painel você acompanha os indicadores de profissionais ativos, as associações em andamento e os estudantes elegíveis aos cuidados de um Profissional de Apoio Escolar que aguardam vinculação na Unidade Regional de Ensino.'
                },
                {
                    index: 2,
                    url: 'views/dashboard.php',
                    element: '#dropdownNotificacoes',
                    fallback: '.navbar-right-actions',
                    side: 'bottom',
                    align: 'end',
                    title: 'Central de Notificações',
                    description: 'Por meio deste ícone na barra superior você acompanha novas demandas aprovadas pela Educação Especial e avisos operacionais relevantes para a equipe.'
                },

                // ============================================================
                // BLOCO B — ALUNOS ELEGÍVEIS (views/alunos/listar.php)
                // ============================================================
                {
                    index: 3,
                    url: 'views/alunos/listar.php',
                    element: '.sigei-search-bar',
                    fallback: '.table-responsive',
                    side: 'bottom',
                    align: 'start',
                    title: 'Alunos Elegíveis para Atendimento',
                    description: 'Aqui são listados os estudantes elegíveis aos cuidados de um Profissional de Apoio Escolar já aprovados tecnicamente pela Educação Especial. Por questões de sigilo e proteção de dados, são exibidas apenas as informações cadastrais e operacionais necessárias para a alocação, sem exposição de laudos médicos.'
                },

                // ============================================================
                // BLOCO C — PROFISSIONAIS DE APOIO (views/paes/listar.php)
                // ============================================================
                {
                    index: 4,
                    url: 'views/paes/listar.php',
                    element: 'a[href="cadastrar.php"]',
                    fallback: '.btn-primary',
                    side: 'bottom',
                    align: 'end',
                    title: 'Gestão de Profissionais de Apoio Escolar',
                    description: 'Nesta área você gerencia os cuidadores contratados pela empresa. Utilize o botão de cadastro para incluir novos profissionais, editar dados cadastrais e anexar o contrato de trabalho.'
                },

                // ============================================================
                // BLOCO D — ASSOCIAÇÕES (views/associacoes/gerenciar.php)
                // ============================================================
                {
                    index: 5,
                    url: 'views/associacoes/gerenciar.php',
                    element: '.card',
                    fallback: '.table-responsive',
                    side: 'bottom',
                    align: 'start',
                    title: 'Associações entre Profissional e Estudante',
                    description: 'Nesta tela você realiza a vinculação entre o Profissional de Apoio Escolar e o estudante. O sistema aplica automaticamente a regra que limita cada Profissional de Apoio Escolar ao atendimento simultâneo de no máximo três estudantes.'
                },

                // ============================================================
                // BLOCO E — RELATÓRIOS (views/relatorios/listar.php)
                // ============================================================
                {
                    index: 6,
                    url: 'views/relatorios/listar.php',
                    element: '.sigei-search-bar',
                    fallback: '.table-responsive',
                    side: 'bottom',
                    align: 'start',
                    title: 'Acompanhamento de Relatórios',
                    description: 'Aqui você acompanha os relatórios diários e mensais enviados pelos profissionais vinculados à empresa, garantindo o registro contínuo da rotina de cuidados prestados.'
                },

                // ============================================================
                // BLOCO F — PERFIL E TERMOS DE USO (views/perfil.php)
                // ============================================================
                {
                    index: 7,
                    url: 'views/perfil.php',
                    element: '#tour-ee-perfil-termos',
                    fallback: '#btn-consultar-termo-perfil',
                    side: 'top',
                    align: 'start',
                    title: 'Perfil e Termos de Uso',
                    description: 'Na aba Perfil você pode consultar os seus dados cadastrais e acessar a qualquer momento os Termos de Uso e Privacidade aceitos no primeiro acesso.'
                },
                {
                    index: 8,
                    url: 'views/perfil.php',
                    element: '#btn-tutorial-perfil',
                    fallback: '.card-body',
                    side: 'left',
                    align: 'center',
                    title: 'Rever o Tutorial Quando Desejar',
                    description: 'Sempre que precisar relembrar o funcionamento do sistema, basta clicar no botão Tutorial para reiniciar este guia interativo a partir do painel inicial.'
                }
            ];
        }

        resolveSelector(step) {
            if (!step.element) return null;
            if (document.querySelector(step.element)) return step.element;
            if (step.fallback && document.querySelector(step.fallback)) return step.fallback;
            return null;
        }

        redirecionarParaIndiceGlobal(targetGlobalIndex) {
            const targetStep = this.STEPS_CONFIG[targetGlobalIndex];
            if (!targetStep) {
                this.pularTour();
                return;
            }

            try {
                sessionStorage.setItem(SESSION_TOUR_ATIVO, '1');
                sessionStorage.setItem(SESSION_TOUR_ETAPA, String(targetGlobalIndex));
            } catch (e) {}

            window.location.href = this.baseUrl + targetStep.url;
        }

        iniciarDriverNaPaginaAtual(startGlobalIndex) {
            const currentPageKey = this.getCurrentPageKey();
            if (!currentPageKey) return;

            this.pageSteps = this.STEPS_CONFIG.filter(s => s.url === currentPageKey);
            if (this.pageSteps.length === 0) return;

            const drvFactory = (window.driver && window.driver.js && typeof window.driver.js.driver === 'function')
                ? window.driver.js.driver
                : (typeof window.driver === 'function' ? window.driver : null);

            if (!drvFactory) return;

            const totalGlobal = this.STEPS_CONFIG.length;

            let localStartIndex = this.pageSteps.findIndex(s => s.index === startGlobalIndex);
            if (localStartIndex === -1) {
                localStartIndex = 0;
            }

            const driverSteps = this.pageSteps.map((st) => {
                const sel = this.resolveSelector(st);
                const isFirstGlobal = (st.index === 0);
                const isLastGlobal  = (st.index === totalGlobal - 1);

                const obj = {
                    popover: {
                        title: st.title,
                        description: st.description,
                        side: st.side || 'bottom',
                        align: st.align || 'start',
                        showButtons: isFirstGlobal ? ['next'] : ['previous', 'next'],
                        nextBtnText: isLastGlobal ? 'Concluir' : 'Próximo',
                        prevBtnText: 'Anterior',
                        doneBtnText: isLastGlobal ? 'Concluir' : 'Próximo',
                        progressText: (st.index + 1) + ' de ' + totalGlobal
                    }
                };
                if (sel) {
                    obj.element = sel;
                }
                return obj;
            });

            this.driverObj = drvFactory({
                showProgress: true,
                animate: true,
                smoothScroll: true,
                allowClose: false,
                escapeToClose: false,
                overlayColor: 'rgba(15, 23, 42, 0.76)',
                steps: driverSteps,
                onPopoverRender: (popover) => {
                    const activeLocalIdx = this.driverObj ? (this.driverObj.getActiveIndex() ?? localStartIndex) : localStartIndex;
                    const currentStepCfg = this.pageSteps[activeLocalIdx] || this.pageSteps[0];
                    this.customizarRodapePopover(popover, currentStepCfg, activeLocalIdx, totalGlobal);
                }
            });

            this.driverObj.drive(localStartIndex);
        }

        customizarRodapePopover(popover, currentStepCfg, activeLocalIdx, totalGlobal) {
            const popEl = (popover && popover.wrapper) ? popover.wrapper : document.querySelector('.driver-popover');
            if (!popEl) return;

            const footer = popEl.querySelector('.driver-popover-footer');
            if (!footer) return;

            footer.style.display = 'flex';

            const globalIndex = currentStepCfg ? currentStepCfg.index : 0;
            const isFirstGlobal = (globalIndex === 0);
            const isLastGlobal  = (globalIndex === totalGlobal - 1);

            const progressEl = footer.querySelector('.driver-popover-progress-text');
            if (progressEl) {
                progressEl.textContent = (globalIndex + 1) + ' de ' + totalGlobal;
            }

            const prevBtn = footer.querySelector('.driver-popover-prev-btn');
            if (prevBtn) {
                prevBtn.textContent = 'Anterior';
                prevBtn.disabled = false;
                prevBtn.removeAttribute('disabled');
                prevBtn.classList.remove('driver-popover-btn-disabled');
                prevBtn.style.display = isFirstGlobal ? 'none' : 'inline-flex';
            }

            const nextBtn = footer.querySelector('.driver-popover-next-btn');
            if (nextBtn) {
                nextBtn.textContent = isLastGlobal ? 'Concluir' : 'Próximo';
                nextBtn.disabled = false;
                nextBtn.removeAttribute('disabled');
                nextBtn.classList.remove('driver-popover-btn-disabled');
                nextBtn.style.display = 'inline-flex';
            }

            let btnPular = footer.querySelector('#sigei-tour-btn-pular');
            if (!btnPular) {
                btnPular = document.createElement('button');
                btnPular.id = 'sigei-tour-btn-pular';
                btnPular.type = 'button';
                btnPular.className = 'driver-popover-btn driver-popover-skip-btn';
                btnPular.textContent = 'Pular';
                footer.insertBefore(btnPular, footer.firstChild);
            }
            btnPular.disabled = false;
            btnPular.removeAttribute('disabled');
        }

        iniciarSeNecessario() {
            if (!this.isPerfilSupervisor()) return;

            if (!this.termosJaForamAceitos() || this.modalTermosEstaVisivel()) {
                return;
            }

            const currentPageKey = this.getCurrentPageKey();
            if (!currentPageKey) return;

            let tourAtivo = false;
            let etapaSalva = 0;

            try {
                tourAtivo = sessionStorage.getItem(SESSION_TOUR_ATIVO) === '1';
                const rawEtapa = sessionStorage.getItem(SESSION_TOUR_ETAPA);
                etapaSalva = rawEtapa !== null ? parseInt(rawEtapa, 10) : 0;
                if (isNaN(etapaSalva) || etapaSalva < 0) etapaSalva = 0;
            } catch (e) {}

            if (!tourAtivo && !this.jaConcluiu() && currentPageKey === 'views/dashboard.php') {
                tourAtivo = true;
                etapaSalva = 0;
                try {
                    sessionStorage.setItem(SESSION_TOUR_ATIVO, '1');
                    sessionStorage.setItem(SESSION_TOUR_ETAPA, '0');
                } catch (e) {}
            }

            if (!tourAtivo) return;

            const stepConfig = this.STEPS_CONFIG[etapaSalva];
            if (!stepConfig) {
                this.pularTour();
                return;
            }

            const targetIndex = (stepConfig.url === currentPageKey)
                ? etapaSalva
                : ((this.STEPS_CONFIG.find(s => s.url === currentPageKey) || {}).index ?? -1);

            if (targetIndex >= 0) {
                try {
                    sessionStorage.setItem(SESSION_TOUR_ETAPA, String(targetIndex));
                } catch (e) {}
                setTimeout(() => {
                    if (!this.modalTermosEstaVisivel()) {
                        this.iniciarDriverNaPaginaAtual(targetIndex);
                    }
                }, 150);
            }
        }
    }

    const tourManager = new SigeiTourSupervisor();
    window.sigeiTourManager = tourManager;

    window.sigeiReiniciarTutorial = function () {
        tourManager.reiniciarTutorial();
    };
    window.iniciarTourGuiado = window.sigeiReiniciarTutorial;

    window.addEventListener('sigei:termos_aceitos', function () {
        if (!tourManager.jaConcluiu()) {
            try {
                sessionStorage.setItem(SESSION_TOUR_ATIVO, '1');
                sessionStorage.setItem(SESSION_TOUR_ETAPA, '0');
            } catch (e) {}
            setTimeout(() => {
                tourManager.iniciarSeNecessario();
            }, 300);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => tourManager.iniciarSeNecessario());
    } else {
        tourManager.iniciarSeNecessario();
    }
})();
