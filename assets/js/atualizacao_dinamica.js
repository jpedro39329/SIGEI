/**
 * Atualização Dinâmica dos Dados - SIGEI
 * Sincroniza automaticamente telas abertas quando um aluno é aprovado, reprovado,
 * enviado para correção, arquivado, reativado ou tem seus dados/vínculos alterados.
 * Inclui controle de concorrência via AbortController, deduplicação de eventos cross-tab,
 * pesquisa dinâmica com debounce e skeleton loading suave.
 */

(function () {
    if (document.body && document.body.classList.contains('page-login')) {
        return;
    }

    const POLL_INTERVAL_MS = 8000; // Consulta leve a cada 8 segundos quando a aba está visível
    const CHANNEL_NAME = 'sigei_sincronizacao_dados';
    const STORAGE_EVENT_KEY = 'sigei_ultimo_evento_atualizacao';

    let ultimoHash = null;
    let atualizandoDOM = false;
    let timerPoll = null;
    let canal = null;
    let ultimoEventoCrossTabTs = 0;
    let pollAbortController = null;
    let domAbortController = null;

    function obterBaseUrl() {
        return (window.SIGEI_CONFIG && window.SIGEI_CONFIG.baseUrl) ? window.SIGEI_CONFIG.baseUrl : '../';
    }

    function ehPaginaSomenteFormulario() {
        const path = window.location.pathname.toLowerCase();
        // Não interfere em telas dedicadas exclusivamente ao preenchimento de cadastro ou edição
        return path.endsWith('/cadastrar.php') || path.endsWith('/editar.php');
    }

    function usuarioInteragindoComFormularioOuModal() {
        // Se o tour guiado estiver ativo ou visível, pausa atualizações dinâmicas do DOM para não interromper o Driver.js
        if (document.body.classList.contains('driver-active') || document.querySelector('.driver-popover')) {
            return true;
        }
        try {
            if (sessionStorage.getItem('sigei_tour_ativo') === '1') {
                return true;
            }
        } catch (e) {}

        // Se houver qualquer modal Bootstrap aberto, aguarda o fechamento para não interromper a ação
        if (document.querySelector('.modal.show')) {
            return true;
        }

        // Se o foco estiver em um campo de entrada que não seja o filtro de busca da listagem
        const ativo = document.activeElement;
        if (ativo && ['INPUT', 'TEXTAREA', 'SELECT'].includes(ativo.tagName)) {
            const form = ativo.closest('form');
            const metodo = form ? (form.getAttribute('method') || 'GET').toUpperCase() : 'GET';
            if (metodo === 'POST') {
                return true;
            }
            // Mesmo em form GET, se o usuário estiver digitando ativamente na busca, evita piscar no polling automático
            if (ativo.tagName === 'INPUT' && ativo.type === 'text') {
                return true;
            }
        }

        return false;
    }

    function notificarOutrasAbas(motivo) {
        const payload = {
            motivo: motivo || 'atualizacao',
            timestamp: Date.now()
        };
        ultimoEventoCrossTabTs = payload.timestamp;
        try {
            if (canal) {
                canal.postMessage(payload);
            }
        } catch (e) {}
        try {
            localStorage.setItem(STORAGE_EVENT_KEY, JSON.stringify(payload));
        } catch (e) {}
    }

    function aplicarEstadoSkeleton(ativo) {
        const alvos = document.querySelectorAll('.content .table-responsive');
        alvos.forEach(function (el) {
            if (ativo) {
                el.classList.add('sigei-skeleton-loading');
            } else {
                el.classList.remove('sigei-skeleton-loading');
            }
        });
    }

    function sincronizarBarrasPaginacao(docNovo) {
        const barrasAtuais = document.querySelectorAll('.sigei-pagination-bar');
        const barrasNovas = docNovo.querySelectorAll('.sigei-pagination-bar');
        if (barrasAtuais.length === barrasNovas.length && barrasAtuais.length > 0) {
            barrasAtuais.forEach(function (barraAtual, idx) {
                const barraNova = barrasNovas[idx];
                if (barraNova && barraAtual.innerHTML !== barraNova.innerHTML) {
                    barraAtual.innerHTML = barraNova.innerHTML;
                }
            });
            return;
        }
        // Caso uma barra tenha surgido ou desaparecido após filtragem
        const cardsAtuais = document.querySelectorAll('.content .card-body');
        const cardsNovos = docNovo.querySelectorAll('.content .card-body');
        if (cardsAtuais.length === cardsNovos.length) {
            cardsAtuais.forEach(function (cardAtual, idx) {
                const cardNovo = cardsNovos[idx];
                if (!cardNovo) return;
                const pagAtual = cardAtual.querySelector('.sigei-pagination-bar');
                const pagNova = cardNovo.querySelector('.sigei-pagination-bar');
                if (pagAtual && !pagNova) {
                    pagAtual.remove();
                } else if (!pagAtual && pagNova) {
                    cardAtual.appendChild(pagNova.cloneNode(true));
                }
            });
        }
    }

    async function atualizarSecoesDaPagina(urlAlvo, exibirSkeleton = false) {
        const isNavegacaoExplicita = Boolean(urlAlvo);
        if (!isNavegacaoExplicita && (atualizandoDOM || ehPaginaSomenteFormulario() || usuarioInteragindoComFormularioOuModal())) {
            return false;
        }

        if (domAbortController) {
            try {
                domAbortController.abort();
            } catch (e) {}
        }
        domAbortController = typeof AbortController !== 'undefined' ? new AbortController() : null;

        atualizandoDOM = true;
        if (exibirSkeleton) {
            aplicarEstadoSkeleton(true);
        }

        const targetHref = urlAlvo || window.location.href;

        try {
            const fetchOpts = {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SIGEI-Live-Update': '1'
                }
            };
            if (domAbortController) {
                fetchOpts.signal = domAbortController.signal;
            }

            const resposta = await fetch(targetHref, fetchOpts);

            if (!resposta.ok) {
                return false;
            }

            const htmlTexto = await resposta.text();
            const parser = new DOMParser();
            const docNovo = parser.parseFromString(htmlTexto, 'text/html');

            if (isNavegacaoExplicita && targetHref !== window.location.href) {
                window.history.replaceState({}, '', targetHref);
            }

            // 1. Atualiza o sino de notificações da navbar (apenas se o dropdown não estiver aberto)
            const dropdownNotifAtual = document.getElementById('dropdownNotificacoes')?.closest('.dropdown');
            const dropdownNotifNovo = docNovo.getElementById('dropdownNotificacoes')?.closest('.dropdown');
            if (dropdownNotifAtual && dropdownNotifNovo && !dropdownNotifAtual.querySelector('.dropdown-menu.show')) {
                if (dropdownNotifAtual.innerHTML !== dropdownNotifNovo.innerHTML) {
                    dropdownNotifAtual.innerHTML = dropdownNotifNovo.innerHTML;
                }
            }

            // 2. Telas de Solicitações (views/solicitacoes/listar.php e views/alunos/pendentes.php)
            const tabsSolicitacoesAtual = document.getElementById('solicitacoesTabs');
            const tabsSolicitacoesNovo = docNovo.getElementById('solicitacoesTabs');
            if (tabsSolicitacoesAtual && tabsSolicitacoesNovo) {
                const btnAtivoAtual = tabsSolicitacoesAtual.querySelector('.nav-link.active');
                const targetAtivo = btnAtivoAtual ? btnAtivoAtual.getAttribute('data-bs-target') : null;

                tabsSolicitacoesAtual.querySelectorAll('.nav-link[data-bs-target]').forEach((btnAtual) => {
                    const target = btnAtual.getAttribute('data-bs-target');
                    const btnNovo = tabsSolicitacoesNovo.querySelector(`.nav-link[data-bs-target="${target}"]`);
                    if (btnNovo && btnAtual.innerHTML !== btnNovo.innerHTML) {
                        btnAtual.innerHTML = btnNovo.innerHTML;
                    }
                });

                ['#analise', '#correcao', '#reprovados'].forEach((idPane) => {
                    const paneAtual = document.querySelector(idPane);
                    const paneNovo = docNovo.querySelector(idPane);
                    if (paneAtual && paneNovo && paneAtual.innerHTML !== paneNovo.innerHTML) {
                        paneAtual.innerHTML = paneNovo.innerHTML;
                        paneAtual.classList.add('sigei-live-updated');
                        setTimeout(() => paneAtual.classList.remove('sigei-live-updated'), 1300);
                    }
                });

                if (targetAtivo) {
                    const paneAtivo = document.querySelector(targetAtivo);
                    if (paneAtivo) {
                        paneAtivo.classList.add('show', 'active');
                    }
                }
                return true;
            }

            // 3. Tela de Listagem de Alunos (views/alunos/listar.php)
            const tabsAlunosAtual = document.getElementById('alunosTabs');
            const tabsAlunosNovo = docNovo.getElementById('alunosTabs');
            if (tabsAlunosAtual && tabsAlunosNovo) {
                if (tabsAlunosAtual.innerHTML !== tabsAlunosNovo.innerHTML) {
                    tabsAlunosAtual.innerHTML = tabsAlunosNovo.innerHTML;
                }
                const tabelaAtual = document.querySelector('.content .table-responsive');
                const tabelaNova = docNovo.querySelector('.content .table-responsive');
                if (tabelaAtual && tabelaNova && tabelaAtual.innerHTML !== tabelaNova.innerHTML) {
                    tabelaAtual.innerHTML = tabelaNova.innerHTML;
                    tabelaAtual.classList.add('sigei-live-updated');
                    setTimeout(() => tabelaAtual.classList.remove('sigei-live-updated'), 1300);
                }
                sincronizarBarrasPaginacao(docNovo);
                return true;
            }

            // 4. Tela de Análise ou Visualização de Aluno (views/solicitacoes/analisar.php e views/alunos/visualizar.php)
            if (document.body.classList.contains('page-solicitacao-analisar') || document.body.classList.contains('page-alunos-visualizar') || document.body.classList.contains('page-aluno-visualizar')) {
                const gridAtual = document.querySelector('.content .row.g-4');
                const gridNovo = docNovo.querySelector('.content .row.g-4');
                if (gridAtual && gridNovo && gridAtual.innerHTML !== gridNovo.innerHTML) {
                    gridAtual.innerHTML = gridNovo.innerHTML;
                    gridAtual.classList.add('sigei-live-updated');
                    setTimeout(() => gridAtual.classList.remove('sigei-live-updated'), 1300);
                }
                return true;
            }

            // 5. Demais telas com tabelas (Dashboard, Associações, Relatórios, Escolas, PAEs, etc.)
            const tabelasAtuais = document.querySelectorAll('.content .table-responsive');
            const tabelasNovas = docNovo.querySelectorAll('.content .table-responsive');
            if (tabelasAtuais.length > 0 && tabelasAtuais.length === tabelasNovas.length) {
                tabelasAtuais.forEach((tabAtual, idx) => {
                    const tabNova = tabelasNovas[idx];
                    if (tabNova && tabAtual.innerHTML !== tabNova.innerHTML) {
                        tabAtual.innerHTML = tabNova.innerHTML;
                        tabAtual.classList.add('sigei-live-updated');
                        setTimeout(() => tabAtual.classList.remove('sigei-live-updated'), 1300);
                    }
                });
                sincronizarBarrasPaginacao(docNovo);
            }

            return true;
        } catch (err) {
            return false;
        } finally {
            atualizandoDOM = false;
            if (exibirSkeleton) {
                aplicarEstadoSkeleton(false);
            }
        }
    }

    async function verificarAtualizacaoServidor(forcarAtualizacao = false) {
        if (document.visibilityState === 'hidden' && !forcarAtualizacao) {
            return;
        }

        if (pollAbortController) {
            try {
                pollAbortController.abort();
            } catch (e) {}
        }
        pollAbortController = typeof AbortController !== 'undefined' ? new AbortController() : null;

        try {
            const url = obterBaseUrl() + 'controllers/alunos/status_atualizacao.php?_t=' + Date.now();
            const fetchOpts = {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            };
            if (pollAbortController) {
                fetchOpts.signal = pollAbortController.signal;
            }

            const resp = await fetch(url, fetchOpts);

            if (!resp.ok) return;
            const dados = await resp.json();
            if (!dados || !dados.autenticado || !dados.hash) return;

            if (ultimoHash === null) {
                ultimoHash = dados.hash;
                if (forcarAtualizacao) {
                    await atualizarSecoesDaPagina();
                }
                return;
            }

            if (dados.hash !== ultimoHash || forcarAtualizacao) {
                const hashAnterior = ultimoHash;
                ultimoHash = dados.hash;
                const atualizou = await atualizarSecoesDaPagina();
                if (!atualizou) {
                    // Se o usuário estava digitando ou com modal aberto, mantém o hash anterior para atualizar assim que concluir
                    ultimoHash = hashAnterior;
                }
            }
        } catch (e) {}
    }

    function processarEventoCrossTab(payload) {
        const ts = payload && payload.timestamp ? Number(payload.timestamp) : 0;
        if (ts > 0 && Math.abs(ts - ultimoEventoCrossTabTs) < 250) {
            return; // Ignora disparo duplicado entre BroadcastChannel e localStorage
        }
        if (ts > 0) {
            ultimoEventoCrossTabTs = ts;
        }
        verificarAtualizacaoServidor(true);
    }

    function configurarBuscaComDebounce() {
        const inputsBusca = document.querySelectorAll('.sigei-search-input');
        inputsBusca.forEach(function (input) {
            const form = input.closest('form');
            if (!form || (form.getAttribute('method') || 'GET').toUpperCase() !== 'GET') {
                return;
            }
            let timerDebounce = null;
            input.addEventListener('input', function () {
                if (timerDebounce) {
                    clearTimeout(timerDebounce);
                }
                timerDebounce = setTimeout(function () {
                    const formData = new FormData(form);
                    const params = new URLSearchParams();
                    formData.forEach(function (val, key) {
                        if (String(val).trim() !== '') {
                            params.set(key, String(val));
                        }
                    });
                    const action = form.getAttribute('action') || window.location.pathname;
                    const qs = params.toString();
                    const targetUrl = action + (qs ? '?' + qs : '');
                    atualizarSecoesDaPagina(targetUrl, true);
                }, 320);
            });
        });
    }

    function iniciarSincronizacao() {
        // 1. Configura BroadcastChannel para comunicação instantânea entre abas do mesmo navegador
        if (typeof BroadcastChannel !== 'undefined') {
            try {
                canal = new BroadcastChannel(CHANNEL_NAME);
                canal.onmessage = function (ev) {
                    processarEventoCrossTab(ev ? ev.data : null);
                };
            } catch (e) {}
        }

        // 2. Fallback via evento storage para sincronização entre abas (deduplicado com BroadcastChannel)
        window.addEventListener('storage', function (e) {
            if (e.key === STORAGE_EVENT_KEY && e.newValue) {
                let parsed = null;
                try {
                    parsed = JSON.parse(e.newValue);
                } catch (err) {}
                processarEventoCrossTab(parsed);
            }
        });

        // 3. Se a URL atual acabou de redirecionar após uma ação (?msg=...), avisa imediatamente as outras abas
        const params = new URLSearchParams(window.location.search);
        if (params.has('msg')) {
            notificarOutrasAbas(params.get('msg'));
        }

        // 4. Configura debounce e cancelamento via AbortController nas barras de pesquisa
        configurarBuscaComDebounce();

        // 5. Consulta inicial para registrar o hash do estado atual
        verificarAtualizacaoServidor(false);

        // 6. Polling periódico leve
        timerPoll = setInterval(function () {
            verificarAtualizacaoServidor(false);
        }, POLL_INTERVAL_MS);

        // 7. Quando o usuário volta para a aba, verifica imediatamente
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                verificarAtualizacaoServidor(false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarSincronizacao);
    } else {
        iniciarSincronizacao();
    }
})();
