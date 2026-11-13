/**
 * SIGEI - Módulo de Tour Guiado Interativo (Driver.js v1.3.1)
 * Roteiro Completo Multi-Página para o Perfil: Educação Especial
 */

(function () {
    'use strict';

    window.SIGEI_TOURS = window.SIGEI_TOURS || {};

    const STORAGE_TOUR_PREFIX = 'sigei_tour_educacao_especial_';
    const STORAGE_TOUR_ALT    = 'sigei_tour_edu_especial_';
    const SESSION_TOUR_ATIVO  = 'sigei_tour_ativo';
    const SESSION_TOUR_ETAPA  = 'sigei_tour_etapa';
    const SESSION_ANALISE_URL = 'sigei_tour_analise_url';

    class SigeiTourEducacaoEspecial {
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

        isPerfilEducacaoEspecial() {
            const p = this.userPerfil;
            return p === 'USUARIO_EDUCACAO_ESPECIAL' || p === 'EDU_ESPECIAL' || p === 'EDUCACAO_ESPECIAL';
        }

        getStorageKey() {
            return STORAGE_TOUR_PREFIX + this.userId;
        }

        getAltStorageKey() {
            return STORAGE_TOUR_ALT + this.userId;
        }

        jaConcluiu() {
            try {
                return (
                    localStorage.getItem(this.getStorageKey()) === 'concluido' ||
                    localStorage.getItem(this.getAltStorageKey()) === 'concluido'
                );
            } catch (e) {
                return false;
            }
        }

        marcarConcluido() {
            try {
                localStorage.setItem(this.getStorageKey(), 'concluido');
                localStorage.setItem(this.getAltStorageKey(), 'concluido');
                sessionStorage.removeItem(SESSION_TOUR_ATIVO);
                sessionStorage.removeItem(SESSION_TOUR_ETAPA);
                sessionStorage.removeItem(SESSION_ANALISE_URL);
            } catch (e) {}
        }

        limparEstadoParaReiniciar() {
            try {
                localStorage.removeItem(this.getStorageKey());
                localStorage.removeItem(this.getAltStorageKey());
                sessionStorage.setItem(SESSION_TOUR_ATIVO, '1');
                sessionStorage.setItem(SESSION_TOUR_ETAPA, '0');
                sessionStorage.removeItem(SESSION_ANALISE_URL);
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
            if (path.includes('views/solicitacoes/listar.php')) return 'views/solicitacoes/listar.php';
            if (path.includes('views/solicitacoes/analisar.php')) return 'views/solicitacoes/analisar.php';
            if (path.includes('views/alunos/listar.php')) return 'views/alunos/listar.php';
            if (path.includes('views/perfil.php')) return 'views/perfil.php';
            return '';
        }

        capturarUrlPrimeiraSolicitacao() {
            const seletoresPrioridade = [
                '#analise a[href*="analisar.php"]',
                '#correcao a[href*="analisar.php"]',
                '#reprovados a[href*="analisar.php"]',
                'a[href*="analisar.php"]'
            ];
            for (const sel of seletoresPrioridade) {
                const link = document.querySelector(sel);
                if (link && link.href) {
                    try {
                        sessionStorage.setItem(SESSION_ANALISE_URL, link.href);
                    } catch (e) {}
                    return link.href;
                }
            }
            return null;
        }

        obterUrlSalvaDeAnalise() {
            try {
                return sessionStorage.getItem(SESSION_ANALISE_URL) || null;
            } catch (e) {
                return null;
            }
        }

        ativarAbaBootstrap(tabButtonSelector) {
            const tabEl = document.querySelector(tabButtonSelector);
            if (!tabEl) return;
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                    const tabInstance = bootstrap.Tab.getOrCreateInstance(tabEl);
                    tabInstance.show();
                } else {
                    tabEl.click();
                }
            } catch (e) {
                try { tabEl.click(); } catch (err) {}
            }
        }

        /**
         * Delegação de eventos na fase de CAPTURA (document.addEventListener(..., true))
         * Isso garante que os cliques em "Pular", "Anterior" e "Próximo/Concluir" sejam interceptados
         * ANTES que o Driver.js bloqueie botões com `disabled` (como o botão Anterior no 1º passo de uma subpágina)
         * ou capture o evento no overlay.
         */
        registrarDelegacaoGlobalDeCliques() {
            document.addEventListener('click', (e) => {
                if (!this.driverObj) return;

                const target = e.target;
                if (!target || typeof target.closest !== 'function') return;

                // 1. Botão Pular
                const skipBtn = target.closest('#sigei-tour-btn-pular, .driver-popover-skip-btn');
                if (skipBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    this.pularTour();
                    return;
                }

                // 2. Botão Anterior
                const prevBtn = target.closest('.driver-popover-prev-btn');
                if (prevBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    this.acaoAnterior();
                    return;
                }

                // 3. Botão Próximo / Concluir
                const nextBtn = target.closest('.driver-popover-next-btn');
                if (nextBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    this.acaoProximo();
                    return;
                }
            }, true);
        }

        acaoProximo() {
            if (!this.driverObj || !this.pageSteps.length) return;
            const totalGlobal = this.STEPS_CONFIG.length;
            const activeLocalIdx = this.driverObj.getActiveIndex() ?? 0;
            const currentStepCfg = this.pageSteps[activeLocalIdx] || this.pageSteps[0];
            const nextLocalIdx   = activeLocalIdx + 1;

            if (nextLocalIdx < this.pageSteps.length) {
                const nextStepCfg = this.pageSteps[nextLocalIdx];
                if (typeof nextStepCfg.onBeforeShow === 'function') {
                    try { nextStepCfg.onBeforeShow(); } catch (e) {}
                }
                try {
                    sessionStorage.setItem(SESSION_TOUR_ETAPA, String(nextStepCfg.index));
                } catch (e) {}
                this.driverObj.moveNext();
            } else {
                const nextGlobalIdx = currentStepCfg.index + 1;
                if (nextGlobalIdx >= totalGlobal) {
                    this.pularTour();
                } else {
                    this.redirecionarParaIndiceGlobal(nextGlobalIdx, 'next');
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
                if (typeof prevStepCfg.onBeforeShow === 'function') {
                    try { prevStepCfg.onBeforeShow(); } catch (e) {}
                }
                try {
                    sessionStorage.setItem(SESSION_TOUR_ETAPA, String(prevStepCfg.index));
                } catch (e) {}
                this.driverObj.movePrevious();
            } else {
                const prevGlobalIdx = currentStepCfg.index - 1;
                if (prevGlobalIdx >= 0) {
                    this.redirecionarParaIndiceGlobal(prevGlobalIdx, 'prev');
                }
            }
        }

        /**
         * Configuração Global Ordenada de Todas as 14 Etapas (STEPS_CONFIG)
         */
        buildStepsConfig() {
            return [
                // ============================================================
                // BLOCO A — PAINEL INICIAL (views/dashboard.php)
                // ============================================================
                {
                    index: 0,
                    url: 'views/dashboard.php',
                    element: null, // Escurece a tela toda e centraliza o card
                    fallback: null,
                    side: 'over',
                    align: 'center',
                    title: 'Boas-vindas ao Painel da Educação Especial',
                    description: 'Bem-vindo ao sistema de gestão da Educação Especial da Unidade Regional de Ensino. Este guia interativo apresentará todas as telas, recursos de análise documental e ferramentas de acompanhamento dos estudantes elegíveis aos cuidados de um Profissional de Apoio Escolar.'
                },
                {
                    index: 1,
                    url: 'views/dashboard.php',
                    element: '#tour-ee-dashboard-indicadores',
                    fallback: '#graficoEducacao',
                    side: 'bottom',
                    align: 'start',
                    title: 'Gráfico Estatístico do Painel',
                    description: 'Este gráfico apresenta visualmente a distribuição das solicitações pendentes de análise, os cadastros aprovados e o total de estudantes já em atendimento na Unidade Regional de Ensino.'
                },
                {
                    index: 2,
                    url: 'views/dashboard.php',
                    element: '#tour-ee-dashboard-tabela',
                    fallback: '.table-responsive',
                    side: 'top',
                    align: 'start',
                    title: 'Fila Rápida de Pendências',
                    description: 'Aqui são listadas as solicitações mais recentes enviadas pelas Unidades Escolares que aguardam avaliação, oferecendo acesso direto para iniciar a análise técnica.'
                },
                {
                    index: 3,
                    url: 'views/dashboard.php',
                    element: '#dropdownNotificacoes',
                    fallback: '.navbar-right-actions',
                    side: 'bottom',
                    align: 'end',
                    title: 'Central de Notificações',
                    description: 'Por meio deste ícone na barra superior você visualiza avisos recentes do sistema, como novas solicitações cadastradas pelas escolas e reenvios de documentação atualizada.'
                },

                // ============================================================
                // BLOCO B — ABA DE SOLICITAÇÕES E SUB-ABAS (views/solicitacoes/listar.php)
                // ============================================================
                {
                    index: 4,
                    url: 'views/solicitacoes/listar.php',
                    element: '#analise-tab',
                    fallback: '#solicitacoesTabs',
                    side: 'bottom',
                    align: 'start',
                    title: 'Sub-aba Para Análise',
                    description: 'Nesta primeira sub-aba concentram-se todos os novos cadastros encaminhados pelas Unidades Escolares que aguardam a triagem e o parecer técnico da equipe de Educação Especial.',
                    onBeforeShow: () => {
                        this.ativarAbaBootstrap('#analise-tab');
                        this.capturarUrlPrimeiraSolicitacao();
                    }
                },
                {
                    index: 5,
                    url: 'views/solicitacoes/listar.php',
                    element: '#correcao-tab',
                    fallback: '#solicitacoesTabs',
                    side: 'bottom',
                    align: 'start',
                    title: 'Sub-aba Em Correção',
                    description: 'Esta sub-aba reúne as solicitações que foram devolvidas para a Unidade Escolar realizar ajustes cadastrais ou complementar laudos médicos e documentos pedagógicos.',
                    onBeforeShow: () => {
                        this.ativarAbaBootstrap('#correcao-tab');
                        this.capturarUrlPrimeiraSolicitacao();
                    }
                },
                {
                    index: 6,
                    url: 'views/solicitacoes/listar.php',
                    element: '#reprovados-tab',
                    fallback: '#solicitacoesTabs',
                    side: 'bottom',
                    align: 'start',
                    title: 'Sub-aba Recusados',
                    description: 'Aqui ficam registrados os pedidos que foram indeferidos tecnicamente, preservando o histórico completo e a justificativa apresentada para consulta.',
                    onBeforeShow: () => {
                        this.ativarAbaBootstrap('#reprovados-tab');
                        this.capturarUrlPrimeiraSolicitacao();
                    }
                },

                // ============================================================
                // BLOCO C — FICHA DE ANÁLISE DO ESTUDANTE (views/solicitacoes/analisar.php)
                // ============================================================
                {
                    index: 7,
                    url: 'views/solicitacoes/analisar.php',
                    element: '#tour-ee-analise-dados',
                    fallback: '#tour-ee-analise-principal',
                    side: 'right',
                    align: 'start',
                    title: 'Dados Cadastrais e Clínicos do Estudante',
                    description: 'Na ficha de análise você confere as informações pessoais do estudante, os dados da Unidade Escolar de origem, o diagnóstico clínico e a descrição detalhada dos cuidados necessários.'
                },
                {
                    index: 8,
                    url: 'views/solicitacoes/analisar.php',
                    element: '#tour-ee-analise-laudos',
                    fallback: '#tour-ee-analise-principal',
                    side: 'top',
                    align: 'start',
                    title: 'Laudos Médicos e Documentos Pedagógicos',
                    description: 'Nesta seção encontram-se os arquivos anexados pela Unidade Escolar. Você pode abrir e inspecionar na tela o termo de responsabilidade, os laudos médicos e os documentos pedagógicos.'
                },
                {
                    index: 9,
                    url: 'views/solicitacoes/analisar.php',
                    element: '#tour-ee-analise-pdf',
                    fallback: '.page-solicitacao-analisar .content',
                    side: 'bottom',
                    align: 'end',
                    title: 'Gerar Documento da Ficha',
                    description: 'Utilize este botão para gerar a versão para impressão ou exportar o arquivo completo da ficha do estudante para registro e arquivamento institucional.'
                },
                {
                    index: 10,
                    url: 'views/solicitacoes/analisar.php',
                    element: '#tour-ee-analise-deliberacao',
                    fallback: '#tour-ee-analise-acoes',
                    side: 'left',
                    align: 'start',
                    title: 'Decisão Técnica da Solicitação',
                    description: 'Após conferir toda a documentação, utilize este painel para aprovar a elegibilidade dos estudantes elegíveis aos cuidados de um Profissional de Apoio Escolar, solicitar correções à escola ou recusar o pedido de forma fundamentada.'
                },

                // ============================================================
                // BLOCO D — ABA DE ALUNOS (views/alunos/listar.php)
                // ============================================================
                {
                    index: 11,
                    url: 'views/alunos/listar.php',
                    element: '#tour-ee-alunos-card',
                    fallback: '.content',
                    side: 'top',
                    align: 'start',
                    title: 'Consulta Geral de Estudantes',
                    description: 'Na aba de Alunos é possível pesquisar e acompanhar todos os estudantes cadastrados nas escolas da Unidade Regional de Ensino, verificando a situação atual do atendimento e o Profissional de Apoio Escolar vinculado.'
                },

                // ============================================================
                // BLOCO E — ABA DE PERFIL E TERMOS DE USO (views/perfil.php)
                // ============================================================
                {
                    index: 12,
                    url: 'views/perfil.php',
                    element: '#tour-ee-perfil-termos',
                    fallback: '#btn-consultar-termo-perfil',
                    side: 'top',
                    align: 'center',
                    title: 'Dados do Perfil e Termos de Uso',
                    description: 'Na página de Perfil você confere seus dados cadastrais e pode consultar integralmente os Termos de Uso e Privacidade aceitos no seu primeiro acesso ao sistema.'
                },
                {
                    index: 13,
                    url: 'views/perfil.php',
                    element: '#btn-tutorial-perfil',
                    fallback: '.card-form',
                    side: 'left',
                    align: 'center',
                    title: 'Botão Tutorial',
                    description: 'Sempre que desejar rever as instruções de uso do sistema, basta acessar seu Perfil e clicar neste botão Tutorial para reiniciar o guia completo.'
                }
            ];
        }

        resolveSelector(step) {
            if (!step.element && !step.fallback) {
                return undefined;
            }
            if (step.element && document.querySelector(step.element)) {
                return step.element;
            }
            if (step.fallback && document.querySelector(step.fallback)) {
                return step.fallback;
            }
            return '.content';
        }

        redirecionarParaIndiceGlobal(targetGlobalIndex, direcao = 'next') {
            const total = this.STEPS_CONFIG.length;
            if (targetGlobalIndex < 0) return;
            if (targetGlobalIndex >= total) {
                this.pularTour();
                return;
            }

            const targetStep = this.STEPS_CONFIG[targetGlobalIndex];
            const currentPageKey = this.getCurrentPageKey();

            if (targetStep.url === 'views/solicitacoes/analisar.php' && currentPageKey !== 'views/solicitacoes/analisar.php') {
                const urlAnalise = this.capturarUrlPrimeiraSolicitacao() || this.obterUrlSalvaDeAnalise();
                if (urlAnalise) {
                    try {
                        sessionStorage.setItem(SESSION_TOUR_ATIVO, '1');
                        sessionStorage.setItem(SESSION_TOUR_ETAPA, String(targetGlobalIndex));
                    } catch (e) {}
                    window.location.href = urlAnalise;
                    return;
                } else {
                    if (direcao === 'next') {
                        return this.redirecionarParaIndiceGlobal(11, 'next');
                    } else {
                        return this.redirecionarParaIndiceGlobal(6, 'prev');
                    }
                }
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

            if (typeof this.pageSteps[localStartIndex].onBeforeShow === 'function') {
                try {
                    this.pageSteps[localStartIndex].onBeforeShow();
                } catch (e) {}
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

        /**
         * Garante que os botões Anterior, Próximo/Concluir e Pular estejam sempre habilitados,
         * visíveis e clicáveis (removendo `disabled` que o Driver.js coloca no 1º item local da página)
         */
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

            // Habilita e exibe o botão Anterior em todas as etapas exceto na etapa 0 global
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

            // Injeta ou atualiza o botão Pular no lado esquerdo do rodapé
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
            if (!this.isPerfilEducacaoEspecial()) return;

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

            // Primeiro acesso no painel após aceitar os termos
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

    const tourManager = new SigeiTourEducacaoEspecial();
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
