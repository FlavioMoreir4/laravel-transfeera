# Contribuindo para laravel-transfeera

## Ambiente

```bash
git clone https://github.com/flaviomoreir4/laravel-transfeera.git
cd laravel-transfeera
composer install
cp .env.example .env.testing  # opcional, para testes de integração
```

## Padrões

- PHP 8.3+, Laravel 12+
- **Pest 5** para testes (não PHPUnit diretamente)
- **PHPStan level 8** com `--memory-limit=512M`
- **Rector** para refatoração automática
- **Laravel Pint** para formatação PSR-12

## Comandos

```bash
composer test          # Rodar testes
composer analyse       # PHPStan level 8
composer format        # Pint PSR-12
composer rector        # Rector dry-run
composer rector-fix    # Rector apply
```

## Convenções

### Resources

- Todo Resource estende `BaseResource`
- Use métodos `getDTO()`, `postDTO()`, `getDTOList()` sempre que possível
- Crie Response DTOs para endpoints que ainda retornam `array` puro
- PHPDocs com exemplos de uso e tipos precisos

### Testes

- Testes Unitários no diretório `tests/Unit/`
- Testes de Feature (Resources) no diretório `tests/Feature/`
- Use `Http::fake()` com fixtures da documentação oficial
- Cobertura mínima: 90%

### Commits

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: descrição
fix: descrição
docs: descrição
chore: descrição
```

## Pull Request

1. Testes passando em PHP 8.3/8.4 com Laravel 12/13
2. PHPStan level 8 — 0 erros
3. Rector clean, Pint PSR-12
4. CHANGELOG.md atualizado na seção `[Unreleased]`

## Documentação

- Endpoints: consulte https://docs.transfeera.dev/reference/endpoints
- Não inferir schemas por analogia com outros endpoints
