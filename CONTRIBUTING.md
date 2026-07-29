# Contribuindo

## Padrão de Commits

Usamos **Conventional Commits** para manter o histórico legível e gerar changelogs automaticamente.

```
<tipo>(<escopo>): <descrição curta>

<corpo opcional (wrap em 72 caracteres)>

<rodapé opcional>
```

### Tipos

| Tipo     | Quando usar                       | Exemplo                                          |
|----------|-----------------------------------|--------------------------------------------------|
| `feat`   | Nova funcionalidade               | `feat(pix): add EMV parsing method`              |
| `fix`    | Correção de bug                   | `fix(auth): handle null expires_in response`     |
| `refactor` | Refatoração sem mudança de comportamento | `refactor(http): extract error mapping`     |
| `docs`   | Somente documentação              | `docs: add usage examples for BatchResource`     |
| `test`   | Adicionar ou atualizar testes     | `test(token): add renewal edge cases`            |
| `chore`  | Manutenção, dependências, tooling | `chore: upgrade pest to 3.x`                     |
| `ci`     | Pipeline CI/CD                    | `ci: add PHP 8.4 to test matrix`                 |
| `style`  | Formatação, espaços, lint         | `style: apply rector rules`                      |

### Escopo (opcional)

Indica a área do código: `auth`, `http`, `pix`, `batch`, `token`, `config`, etc.

### Breaking Changes

Adicione `!` após o tipo e inclua `BREAKING CHANGE` no rodapé:

```
feat(auth)!: change authentication flow

BREAKING CHANGE: client_credentials now requires client_secret
```

## Padrão de PRs

### Nomenclatura de Branches

```
<tipo>/<descrição-curta>
```

Exemplos: `feat/pix-automatico-webhooks`, `fix/token-expired-cache`, `chore/upgrade-pest`.

### Abertura de PR

1. **Título**: mesmo formato do Conventional Commit
2. **Template**: preencher o `PULL_REQUEST_TEMPLATE.md` com:
   - Summary do que foi feito
   - Test Plan com checkboxes
   - Breaking Changes se aplicável

### Checklist antes de abrir PR

- [ ] `composer test` — todos os testes passam
- [ ] `composer phpstan` — nível 8 sem erros
- - `composer rector` — sem mudanças inesperadas
- [ ] Cobertura adequada de testes para novas funcionalidades

## Pull Request Lifecycle

```
feature branch → PR (draft) → CI green → PR (ready) → review → merge (squash)
```

Usamos **squash merge** para manter o histórico limpo na `main`.
