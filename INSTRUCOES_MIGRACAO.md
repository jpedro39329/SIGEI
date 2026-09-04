# Instruções de Migração - Novo Tema CSS e Termos de Uso

## 📋 Resumo das Alterações

O arquivo `assets/css/style.css` foi atualizado com:
- ✅ Novo tema visual unificado (SIGEI Blue, Teal, etc)
- ✅ Design consistente com login e seleção de perfis
- ✅ Estilos melhorados para formulários, tabelas e cartões
- ✅ Suporte integrado para modal de termos de uso
- ✅ Responsividade otimizada

## 🔧 Implementação do Modal de Termos de Uso

### Passo 1: Adicionar Script de Termos a Todas as Páginas Internas

Localize o `</body>` (final do arquivo) de cada página interna (dashboard, alunos, paes, etc) e adicione:

```html
<script src="../assets/js/termos.js"></script>
```

**Coloque a linha DEPOIS do script do Bootstrap, mas ANTES de fechar o `</body>`.**

#### Exemplo (dashboard.php):
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // ... seu código JavaScript existente ...
</script>

<!-- Adicione esta linha -->
<script src="../assets/js/termos.js"></script>

</body>
</html>
```

### Passo 2: O que Acontece

Quando um usuário entra pela primeira vez:
1. ✅ Um modal popup aparece bloqueando a navegação
2. ✅ O usuário deve ler e aceitar os termos (checkbox obrigatório)
3. ✅ Ao aceitar, os termos são marcados como aceitos no `localStorage`
4. ✅ Nas próximas visitas, o modal NÃO aparece mais
5. ⚠️ Se o usuário rejeita, é desconectado automaticamente

### Passo 3: Personalizar o Conteúdo dos Termos

Abra `assets/js/termos.js` e procure por:

```javascript
<div class="terms-modal-content">
    <!-- Conteúdo dos termos será preenchido aqui -->
    <p class="text-muted">
        Leia atentamente os termos de uso e política de privacidade antes de continuar.
    </p>
</div>
```

Substitua o conteúdo de exemplo por seus termos reais. O espaço já está formatado e pronto para receber:
- Texto
- Listas
- Títulos
- Qualquer HTML

### Passo 4: Controle de Acesso por Usuário (Já Funciona!)

O sistema mantém automaticamente:
- ✅ Cada usuário com suas próprias abas e permissões
- ✅ Separação de dados por perfil (USUARIO_ESCOLA, USUARIO_EDUCACAO_ESPECIAL, etc)
- ✅ Sidebar dinâmica conforme o perfil
- ✅ O navbar sempre com as informações corretas do usuário

**Você não precisa fazer nada!** O controle de acesso já está implementado no backend.

## 📄 Arquivos Modificados/Criados

| Arquivo | Status | Descrição |
|---------|--------|-----------|
| `assets/css/style.css` | ✏️ Modificado | CSS atualizado com novo tema |
| `assets/js/termos.js` | 🆕 Criado | Script de gerenciamento de termos |
| `includes/termos_include.php` | 🆕 Criado | Include opcional (não é necessário) |

## 🎨 Principais Mudanças de Estilo

### Cores (Agora Consistentes)
```css
--sigei-blue-900: #0d47a1     (Principal)
--sigei-blue-700: #1565c0     (Secundário)
--sigei-teal-600: #1a9e85     (Destaque)
--sigei-teal-400: #40d9b8     (Accent)
```

### Background
- Gradiente suave com tons de azul e teal
- Não parece "gerado por IA"
- Profissional e moderno

### Componentes
- ✅ Botões: altura aumentada (2.75rem), padding consistente
- ✅ Formulários: bordas mais grossas (2px), altura mínima (2.8rem)
- ✅ Tabelas: header com fundo azul claro, hover effects
- ✅ Cards: sombras sutis, bordas refinadas
- ✅ Modal: gradiente no header, checkbox styled

## 📱 Responsividade

O CSS agora é totalmente responsivo:
- ✅ Mobile (até 420px): botões em largura cheia
- ✅ Tablet (até 767px): layout adaptado
- ✅ Desktop: layout otimizado

## 🔗 Relacionamentos de Abas (Sem Alteração Necessária)

O sistema mantém cada usuário com acesso apenas às suas abas:

| Perfil | Abas Disponíveis |
|--------|------------------|
| USUARIO_ESCOLA | Alunos, Paes, Associações, Relatórios |
| USUARIO_EDUCACAO_ESPECIAL | Alunos, Pendências, Relatórios |
| PAE | Alunos Associados, Perfil |
| ADMIN | Todas |

**Esta funcionalidade continua 100% intacta!**

## ⚠️ Próximos Passos

1. **IMPORTANTE**: Adicione `<script src="../assets/js/termos.js"></script>` ao final do `</body>` de TODAS as páginas internas
   - dashboard.php ✅
   - alunos/listar.php ✅
   - alunos/cadastrar.php ✅
   - alunos/editar.php ✅
   - alunos/visualizar.php ✅
   - E TODAS as outras páginas internas...

2. Personalize o conteúdo dos termos em `assets/js/termos.js`

3. Teste em incógnito (localStorage vazio) para ver o modal aparecer

4. Após aceitar uma vez, teste normal para confirmar que NÃO aparece novamente

## 🧪 Testando

### Teste 1: Verificar novo tema
- [ ] Cores azul/teal consistentes
- [ ] Formulários com bordas mais visíveis
- [ ] Tabelas com header azul
- [ ] Botões com altura apropriada

### Teste 2: Modal de termos
- [ ] Abrir em incógnito (localStorage vazio)
- [ ] Modal deve aparecer bloqueando tudo
- [ ] Botão "Aceitar" deve estar desabilitado
- [ ] Marcar checkbox ativa o botão
- [ ] Clicar "Rejeitar" desconecta
- [ ] Recarregar página - modal NÃO deve aparecer

### Teste 3: Controle de acesso
- [ ] Login como cada tipo de usuário
- [ ] Verificar que as abas e dados correspondem ao perfil
- [ ] Tentar acessar URLs diretas de outros perfis (deve bloquear)

## 💡 Dúvidas Comuns

**P: O modal aparece toda vez?**
R: Não! Aparece apenas na primeira visita (verificado via localStorage)

**P: Posso customizar o modal?**
R: Sim! Edite `assets/js/termos.js` linhas 40-70

**P: E se o usuário limpar localStorage?**
R: O modal aparecerá novamente (comportamento esperado)

**P: Preciso fazer algo especial no banco de dados?**
R: Não! O localStorage é suficiente

**P: Os usuários mantêm suas abas separadas?**
R: Sim! O controle de acesso continua funcionando normalmente

---

**Data de atualização:** 02/09/2026
**Versão do CSS:** 2.0 (Tema Unificado)
