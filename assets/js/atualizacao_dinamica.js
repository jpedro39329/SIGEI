/**
 * Atualização Dinâmica dos Dados - SIGEI
 * Sincroniza automaticamente telas abertas quando um aluno é aprovado, reprovado,
 * enviado para correção, arquivado, reativado ou tem seus dados/vínculos alterados.
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

    function obterBaseUrl() {
        return (window.SIGEI_CONFIG && window.SIGEI_CONFIG.baseUrl) ? window.SIGEI_CONFIG.baseUrl : '../';
    }

    function ehPaginaSomenteFormulario() {
        const path = window.location.pathname.toLowerCase();
        // Não interfere em telas dedicadas exclusivamente ao preenchimento de cadastro ou edição
        return path.endsWith('/cadastrar.php') || path.endsWith('/editar.php');
    }

    function usuarioInteragindoComFormularioOuModal() {
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
            // Mesmo em form GET, se o usuário estiver digitando ativamente na busca, evita piscar
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
        try {
            if (canal) {
                canal.postMessage(payload);
            }
        } catch (e) {}
        try {
            localStorage.setItem(STORAGE_EVENT_KEY, JSON.stringify(payload));
        } catch (e) {}
    }

    async function atualizarSecoesDaPagina() {
        if (atualizandoDOM || ehPaginaSomenteFormulario() || usuarioInteragindoComFormularioOuModal()) {
            return false;
        }

        atualizandoDOM = true;
        try {
            const resposta = await fetch(window.location.href, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SIGEI-Live-Update': '1'
                }
            });

            if (!resposta.ok) {
                return false;
            }

            const htmlTexto = await resposta.text();
            const parser = new DOMParser();
            const docNovo = parser.parseFromString(htmlTexto, 'text/html');

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
                // Preserva a aba ativa selecionada pelo usuário na tela atual
                const btnAtivoAtual = tabsSolicitacoesAtual.querySelector('.nav-link.active');
                const targetAtivo = btnAtivoAtual ? btnAtivoAtual.getAttribute('data-bs-target') : null;

                // Atualiza os badges das abas
                tabsSolicitacoesAtual.querySelectorAll('.nav-link[data-bs-target]').forEach((btnAtual) => {
                    const target = btnAtual.getAttribute('data-bs-target');
                    const btnNovo = tabsSolicitacoesNovo.querySelector(`.nav-link[data-bs-target="${target}"]`);
                    if (btnNovo && btnAtual.innerHTML !== btnNovo.innerHTML) {
                        btnAtual.innerHTML = btnNovo.innerHTML;
                    }
                });

                // Atualiza o conteúdo de cada painel de aba (#analise, #correcao, #reprovados)
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
                return true;
            }

            // 4. Tela de Análise ou Visualização de Aluno (views/solicitacoes/analisar.php e views/alunos/visualizar.php)
            if (document.body.classList.contains('page-solicitacao-analisar') || document.body.classList.contains('page-aluno-visualizar')) {
                const gridAtual = document.querySelector('.content .row.g-4');
                const gridNovo = docNovo.querySelector('.content .row.g-4');
                if (gridAtual && gridNovo && gridAtual.innerHTML !== gridNovo.innerHTML) {
                    gridAtual.innerHTML = gridNovo.innerHTML;
                    gridAtual.classList.add('sigei-live-updated');
                    setTimeout(() => gridAtual.classList.remove('sigei-live-updated'), 1300);
                }
                return true;
            }

            // 5. Demais telas com tabelas (Dashboard, Associações, Relatórios)
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
            }

            return true;
        } catch (err) {
            return false;
        } finally {
            atualizandoDOM = false;
        }
    }

    async function verificarAtualizacaoServidor(forcarAtualizacao = false) {
        if (document.visibilityState === 'hidden' && !forcarAtualizacao) {
            return;
        }

        try {
            const url = obterBaseUrl() + 'controllers/alunos/status_atualizacao.php?_t=' + Date.now();
            const resp = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            });

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

    function iniciarSincronizacao() {
        // 1. Configura BroadcastChannel para comunicação instantânea entre abas do mesmo navegador
        if (typeof BroadcastChannel !== 'undefined') {
            try {
                canal = new BroadcastChannel(CHANNEL_NAME);
                canal.onmessage = function () {
                    verificarAtualizacaoServidor(true);
                };
            } catch (e) {}
        }

        // 2. Fallback via evento storage para sincronização entre abas
        window.addEventListener('storage', function (e) {
            if (e.key === STORAGE_EVENT_KEY && e.newValue) {
                verificarAtualizacaoServidor(true);
            }
        });

        // 3. Se a URL atual acabou de redirecionar após uma ação (?msg=...), avisa imediatamente as outras abas
        const params = new URLSearchParams(window.location.search);
        if (params.has('msg')) {
            notificarOutrasAbas(params.get('msg'));
        }

        // 4. Consulta inicial para registrar o hash do estado atual
        verificarAtualizacaoServidor(false);

        // 5. Polling periódico leve
        timerPoll = setInterval(function () {
            verificarAtualizacaoServidor(false);
        }, POLL_INTERVAL_MS);

        // 6. Quando o usuário volta para a aba, verifica imediatamente
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

