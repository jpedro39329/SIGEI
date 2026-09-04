/**
 * Sistema de Termos de Uso - SIGEI
 * Gerencia a exibição e aceitação de termos na primeira visita
 */

class TermosDeUso {
    constructor() {
        this.chaveLocal = 'sigei_termos_aceitos';
        this.termosNaoAceitos = !this.verificarAceite();
    }

    /**
     * Verifica se o usuário já aceitou os termos
     */
    verificarAceite() {
        return localStorage.getItem(this.chaveLocal) === 'true';
    }

    /**
     * Marca os termos como aceitos
     */
    marcarComoAceito() {
        localStorage.setItem(this.chaveLocal, 'true');
    }

    /**
     * Exibe o modal de termos
     */
    exibirModal() {
        if (this.termosNaoAceitos) {
            // Aguarda o DOM estar completamente carregado
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.mostrarModal());
            } else {
                this.mostrarModal();
            }
        }
    }

    /**
     * Cria e exibe o modal
     */
    mostrarModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade terms-modal';
        modal.id = 'termos-modal';
        modal.setAttribute('data-bs-backdrop', 'static');
        modal.setAttribute('data-bs-keyboard', 'false');
        modal.setAttribute('tabindex', '-1');
        
        modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <div class="terms-modal-kicker">Bem-vindo</div>
                            <h5 class="modal-title mt-2">TERMO DE CIÊNCIA E CONSENTIMENTO PARA TRATAMENTO DE DADOS</h5>
                        </div>
                        <!-- Sem botão de fechar - usuário deve aceitar ou rejeitar -->
                    </div>
                    <div class="modal-body">
                        <div class="terms-modal-content" style="max-height: 350px; overflow-y: auto; padding-right: 10px;">
                            <h6>1. SOBRE O TRATAMENTO DOS DADOS</h6>
                            <p>Ao utilizar o sistema, o usuário declara estar ciente de que poderão ser tratados dados pessoais e dados pessoais sensíveis relacionados aos alunos cadastrados, nos termos da Lei nº 13.709/2018 – Lei Geral de Proteção de Dados Pessoais (LGPD).</p>
                            <p>O tratamento das informações será realizado exclusivamente para fins educacionais, administrativos e de acompanhamento dos alunos com necessidades educacionais especiais.</p>

                            <h6>2. DADOS TRATADOS</h6>
                            <p>De acordo com as funcionalidades e permissões do sistema, poderão ser tratados:</p>
                            <ul>
                                <li>nome e dados de identificação do aluno;</li>
                                <li>informações escolares e histórico acadêmico;</li>
                                <li>informações relacionadas ao acompanhamento pedagógico;</li>
                                <li>laudos e documentos relacionados às necessidades educacionais do aluno;</li>
                                <li>informações de saúde estritamente necessárias ao atendimento educacional;</li>
                                <li>dados dos responsáveis legais, quando necessários.</li>
                            </ul>
                            <p>Os dados relacionados à saúde, deficiência ou outras condições específicas são considerados <strong>dados pessoais sensíveis</strong> e receberão tratamento restrito.</p>

                            <h6>3. FINALIDADE</h6>
                            <p>As informações serão utilizadas exclusivamente para:</p>
                            <ul>
                                <li>realizar o cadastro e acompanhamento dos alunos;</li>
                                <li>auxiliar no atendimento educacional especializado (AEE);</li>
                                <li>registrar informações relevantes ao acompanhamento pedagógico;</li>
                                <li>consultar o histórico acadêmico;</li>
                                <li>gerar relatórios necessários à gestão escolar;</li>
                                <li>facilitar a organização e a continuidade do atendimento educacional.</li>
                            </ul>
                            <p>Os dados não serão utilizados para fins comerciais, publicitários ou para finalidades incompatíveis com o atendimento educacional.</p>

                            <h6>4. ACESSO ÀS INFORMAÇÕES</h6>
                            <p>O acesso aos dados será limitado conforme o perfil e as atribuições do usuário.</p>
                            <p>Dessa forma, gestores, professores e profissionais do AEE poderão visualizar somente as informações necessárias ao desempenho de suas respectivas funções, conforme as permissões definidas no sistema.</p>
                            <p>O usuário é responsável por manter suas credenciais de acesso em sigilo e por utilizar as informações exclusivamente para as finalidades relacionadas às suas atividades.</p>

                            <h6>5. SEGURANÇA E CONFIDENCIALIDADE</h6>
                            <p>O sistema deverá adotar medidas técnicas e administrativas destinadas a proteger as informações contra acessos não autorizados, perda, alteração, divulgação ou utilização indevida.</p>
                            <p>As informações acessadas pelo usuário possuem caráter restrito e não devem ser compartilhadas com pessoas não autorizadas.</p>

                            <h6>6. CIÊNCIA E CONSENTIMENTO</h6>
                            <p>Ao selecionar "Li e concordo", o usuário declara que:</p>
                            <ul>
                                <li>leu e compreendeu as informações apresentadas;</li>
                                <li>está ciente de que o sistema realiza o tratamento de dados pessoais e, quando aplicável, dados pessoais sensíveis;</li>
                                <li>concorda com o tratamento das informações para as finalidades apresentadas;</li>
                                <li>compromete-se a utilizar os dados exclusivamente para as atividades relacionadas à sua função;</li>
                                <li>compromete-se a manter sigilo sobre as informações às quais tiver acesso.</li>
                            </ul>
                            <p class="text-muted small">O usuário poderá consultar este termo novamente a qualquer momento por meio do sistema.</p>
                        </div>

                        <div class="terms-modal-check form-check mt-3 pt-3 border-top">
                            <input class="form-check-input" type="checkbox" id="aceitar-termos">
                            <label class="form-check-label fw-bold" for="aceitar-termos">
                                Li e concordo com o Termo de Ciência e Consentimento para Tratamento de Dados.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="btn-rejeitar-termos">
                            Rejeitar
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-aceitar-termos" disabled>
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Inicializar modal do Bootstrap
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Event listeners
        const checkboxAceitar = document.getElementById('aceitar-termos');
        const btnAceitar = document.getElementById('btn-aceitar-termos');
        const btnRejeitar = document.getElementById('btn-rejeitar-termos');

        // Ativar botão de aceitar apenas quando checkbox estiver marcado
        checkboxAceitar.addEventListener('change', () => {
            btnAceitar.disabled = !checkboxAceitar.checked;
        });

        // Aceitar termos
        btnAceitar.addEventListener('click', () => {
            this.marcarComoAceito();
            bsModal.hide();
            // Modal se fecha e o usuário continua navegando
        });

        // Rejeitar termos
        btnRejeitar.addEventListener('click', () => {
            alert('Você não pode continuar sem aceitar os termos de uso.');
            // Redirecionar para login ou página inicial
            window.location.href = '../controllers/auth/logout.php';
        });
    }
}

// Inicializar termos de uso quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    // Apenas executar em páginas internas (não no login)
    if (!document.body.classList.contains('page-login')) {
        const termos = new TermosDeUso();
        termos.exibirModal();
    }
});