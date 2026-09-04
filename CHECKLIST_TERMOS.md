# ✅ Checklist - Adicionar Script de Termos a Todas as Páginas

Adicione esta linha **ANTES do `</body>`** (após scripts do Bootstrap/Chart) em cada arquivo:

```html
<!-- Sistema de Termos de Uso -->
<script src="../assets/js/termos.js"></script>
```

## Views - Dashboard

- [ ] `views/dashboard.php` ✅ **JÁ FEITO**

## Views - Alunos

- [ ] `views/alunos/listar.php`
- [ ] `views/alunos/cadastrar.php`
- [ ] `views/alunos/editar.php`
- [ ] `views/alunos/visualizar.php`
- [ ] `views/alunos/pendentes.php`

## Views - Paes

- [ ] `views/paes/listar.php`
- [ ] `views/paes/cadastrar.php`
- [ ] `views/paes/editar.php`
- [ ] `views/paes/visualizar.php`

## Views - Associações

- [ ] `views/associacoes/gerenciar.php`

## Views - Relatórios

- [ ] `views/relatorios/listar.php`

## Views - Escolas

- [ ] `views/escolas/cadastrar.php`

## Views - Empresas

- [ ] `views/empresas/cadastrar.php`

## Views - UREs

- [ ] `views/ures/cadastrar.php`

## Views - Supervisores

- [ ] `views/supervisores/cadastrar.php`

## Views - Dirigentes

- [ ] `views/dirigentes/cadastrar.php`

## Views - Setores

- [ ] `views/setores/cadastrar.php`

## Views - Solicitações

- [ ] `views/solicitacoes/listar.php`
- [ ] `views/solicitacoes/analisar.php`

## Views - Usuários

- [ ] `views/usuarios/listar.php`

## Views - Usuários UE

- [ ] `views/usuarios_ue/cadastrar.php`

## Views - Perfil

- [ ] `views/perfil.php`

---

## 🚀 Passo a Passo para Adicionar

1. Abra o arquivo `.php`
2. Vá para o **FINAL** do arquivo (procure por `</html>`)
3. Localize `</body>` (a linha antes de `</html>`)
4. Procure pela última tag `</script>` antes de `</body>`
5. Depois dessa tag `</script>`, adicione uma linha vazia
6. Cole o código:
   ```html
   <!-- Sistema de Termos de Uso -->
   <script src="../assets/js/termos.js"></script>
   ```

### Exemplo Antes:
```html
<script>
    // código do gráfico
</script>

</body>
</html>
```

### Exemplo Depois:
```html
<script>
    // código do gráfico
</script>

<!-- Sistema de Termos de Uso -->
<script src="../assets/js/termos.js"></script>

</body>
</html>
```

---

## ⚠️ Pontos Importantes

✅ **O script DEVE ser carregado DEPOIS do Bootstrap**
✅ **O script deve estar DENTRO do `</body>`**
✅ **Coloque ANTES do `</html>`**
❌ **Não coloque no `<head>`**
❌ **Não coloque fora do `</body>`**

---

## 🧪 Como Verificar se Está Correto

1. Abra a página em **modo incógnito** (localStorage vazio)
2. O modal deve aparecer bloqueando tudo
3. Sem conseguir aceitar os termos, a página não funciona
4. Depois de aceitar, recarregue - modal NÃO deve aparecer

Se não aparecer o modal em incógnito = script não foi adicionado ou está em local errado.

---

## 📊 Progresso

Total de arquivos: **25**
Concluídos: **1** ✅
Faltando: **24** ⏳

---

**Dica:** Use buscar e substituir no seu editor para fazer isto mais rápido!
- Procure por: `</body>`
- Substitua por: 
```
<!-- Sistema de Termos de Uso -->
<script src="../assets/js/termos.js"></script>

</body>
```
