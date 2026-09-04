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
                            <h5 class="modal-title mt-2">Termos de Uso e Política de Privacidade</h5>
                        </div>
                        <!-- Sem botão de fechar - usuário deve aceitar ou rejeitar -->
                    </div>
                    <div class="modal-body">
                        <div class="terms-modal-content">
                            <!-- Conteúdo dos termos será preenchido aqui -->
                            <p class="text-muted">
                                Leia atentamente os termos de uso e política de privacidade antes de continuar.
                            </p>
                        </div>

                        <div class="terms-modal-check form-check">
                            <input class="form-check-input" type="checkbox" id="aceitar-termos">
                            <label class="form-check-label" for="aceitar-termos">
                                Declaro que li e concordo com os termos de uso e política de privacidade
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="btn-rejeitar-termos">
                            Rejeitar
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-aceitar-termos" disabled>
                            Aceitar
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
