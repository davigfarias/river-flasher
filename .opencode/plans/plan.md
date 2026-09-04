# Plano: Importação de Flashcards via CSV

## Contexto

Atualmente, a criação de flashcards é feita um por um, o que é demorado para decks grandes. Este plano implementa a importação em massa via arquivo `.csv`, lendo colunas pelo **título** (não por posição), e inserindo tudo de uma vez no deck selecionado.

## Visão Geral

### O que mudar

1. **Unificar a modal de "novo baralho" e "baralho por tema"** — hoje são dois modais separados. Vamos combinar em uma única modal com abas/opções:
   - **Criar baralho** (o modal atual)
   - **Baralho por tema** (o fluxo atual de mover cartões existentes)
   - **Importar CSV** (novo)

2. **Nova funcionalidade: Importar CSV** — upload de arquivo `.csv` com colunas mapeadas pelo título, inserção em massa no deck selecionado.

3. **Regra de idioma** — manter a mesma regra existente: o deck deve conter apenas cartões de um idioma (hebraico OU grego). Se o CSV misturar idiomas, alertar o usuário. Se o deck já tiver cartões, verificar compatibilidade.

## Fluxo de Importação

### 1. Usuário abre a modal unificada

A modal terá 3 abas ou cards clicáveis:
- **Criar novo baralho** — pede nome, cria deck vazio
- **Mover por tema** — fluxo existente (selecionar tag + deck destino)
- **Importar CSV** — novo fluxo

### 2. Usuário seleciona "Importar CSV"

A interface mostra:
- **Seletor de deck destino** — dropdown com decks existentes + opção "Criar novo deck" (que expande campo de nome)
- **Upload do arquivo `.csv`** — input do tipo file, aceita apenas `.csv`
- **Preview/validação** — antes de inserir, o sistema:
  - Lê o CSV e mapeia colunas pelo título
  - Detecta o idioma dos cartões (hebraico/grego) — pode ser coluna `language` no CSV ou inferência
  - Verifica se o deck destino é compatível (mesmo idioma ou vazio)
  - Mostra preview: quantos cartões serão importados, idioma detectado, amostra das primeiras linhas

### 3. Validação e Inserção

- Se o deck já tem cartões de idioma diferente → **erro com toast explicativo**
- Se o CSV mistura idiomas → **erro com toast explicativo** (regra: todos os cartões do CSV devem ser do mesmo idioma)
- Se tudo OK → inserção em massa via `DB::table('cards')->insert()` dentro de uma transação

### 4. Feedback

- Toast de sucesso: "X cartões importados com sucesso para o deck 'Nome do Deck'"
- Se houver linhas com erro (campos obrigatórios faltando), toast com contagem de erros

## Especificação do CSV

### Colunas esperadas (pelo título, case-insensitive)

| Coluna | Obrigatória | Descrição |
|--------|-------------|-----------|
| `word` | Sim | A palavra/card original |
| `definition` | Sim | Definição/tradução |
| `language` | Sim* | `el` (grego) ou `he` (hebraico) — *obrigatória se não houver coluna e o deck estiver vazio |
| `pos` | Não | Part of speech |
| `category` | Não | Categoria lexical |
| `transliteration` | Não | Transliteração |
| `example` | Não | Exemplo de uso |
| `translation` | Não | Tradução alternativa |
| `is_difficult` | Não | `true`/`false` ou `1`/`0` |

### Mapeamento de colunas

O sistema deve:
1. Normalizar os títulos das colunas (trim, lowercase, remover acentos)
2. Mapear variações comuns: `Word`, `WORD`, `word`, `palavra`, etc.
3. Se uma coluna obrigatória estiver faltando, rejeitar o arquivo com mensagem clara

## Estrutura de Arquivos

### Novos arquivos

```
app/
  Actions/
    ImportCardsFromCsv.php          # Action principal: parse + insert
  Livewire/
    Components/
      CsvImportModal.php           # Componente Livewire da modal (ou extensão do existente)
    Forms/
      CsvImportForm.php            # Formulário: deck selector + file upload
  Services/
    CsvParser.php                  # Parser do CSV com mapeamento de colunas

resources/views/
  components/
    ⚡csv-import-section.blade.php # Seção de importação dentro da modal
```

### Arquivos modificados

```
resources/views/components/⚡new-deck-modal.blade.php  # Unificar com deck-from-tag + CSV
resources/views/pages/⚡decks/decks.blade.php          # Botão que abre a modal unificada
storage/framework/views/livewire/classes/...            # Re-compilar após mudanças
```

## Detalhes Técnicos

### CsvParser Service

```php
// app/Services/CsvParser.php
class CsvParser
{
    private const COLUMN_MAP = [
        'word'           => 'word',
        'palabra'        => 'word',
        'definition'     => 'definition',
        'definicao'      => 'definition',
        'definición'     => 'definition',
        'language'       => 'language',
        'idioma'         => 'language',
        'pos'            => 'pos',
        'parte de fala'  => 'pos',
        'category'       => 'category',
        'categoria'      => 'category',
        'transliteration'=> 'transliteration',
        'transliteracao' => 'transliteration',
        'example'        => 'example',
        'exemplo'        => 'example',
        'translation'    => 'translation',
        'traducao'       => 'translation',
        'is_difficult'   => 'is_difficult',
        'dificil'        => 'is_difficult',
    ];

    public function parse(string $csvContent): array
    // Retorna: ['headers' => [...], 'rows' => [...], 'errors' => [...]]
    // Cada row é um array associativo com os campos mapeados

    public function detectLanguage(array $rows): ?Language
    // Detecta idioma a partir dos dados (coluna language ou inferência)
}
```

### ImportCardsFromCsv Action

```php
// app/Actions/ImportCardsFromCsv.php
class ImportCardsFromCsv
{
    public function handle(Deck $deck, array $rows, Language $language): ImportResult
    {
        // 1. Validar que deck é compatível com o idioma
        // 2. Filtrar linhas com campos obrigatórios faltando
        // 3. Preparar array para bulk insert
        // 4. DB::transaction: inserir todos os cartões
        // 5. Retornar ImportResult (sucesso, count, errors)
    }
}
```

### Livewire Component

O componente da modal unificada precisa de:
- **Estado:** `activeTab` (criar | tema | csv)
- **Para CSV:** `selectedDeckId`, `csvFile`, `parsedData`, `detectedLanguage`, `importing`
- **Ações:** `parseCsv()`, `validateImport()`, `executeImport()`

### Linguagem dos Cartões no CSV

Duas abordagens possíveis (a decidir):

**Opção A:** Coluna `language` obrigatória no CSV — usuário deve especificar `el` ou `he` para cada linha (ou uma vez por arquivo).

**Opção B:** Coluna `language` opcional — se ausente, o idioma é inferido do deck destino (ou o usuário seleciona na interface antes do upload).

**Recomendação:** Opção B com fallback — se o deck já tem idioma, usar esse. Se vazio, pedir ao usuário que selecione. A coluna `language` no CSV é opcional e serve para override/validação.

## Regras de Negócio

1. **Um deck = um idioma** — regra existente, manter
2. **CSV deve ter todos os cartões do mesmo idioma** — rejeitar CSVs mistos
3. **Campos obrigatórios:** `word` e `definition` — linhas sem eles são ignoradas (com aviso)
4. **Imagens:** não suportadas no CSV (manter `image_path` como null para cartões importados)
5. **is_difficult:** default `false` se não especificado
6. **is_active:** default `true` para todos os importados
7. **Contadores:** `aced_count`, `missed_count`, `last_reviewed_at` = null/0 (cartões novos)

## Ordem de Implementação

1. Criar `CsvParser` service
2. Criar `ImportCardsFromCsv` action
3. Criar/estender componente Livewire da modal unificada
4. Atualizar a view da modal com as 3 abas
5. Testes: unitários para o parser, feature tests para a importação
6. Compilar views (Blaze)
7. Verificar lint/formato (`vendor/bin/pint --dirty`)

## Testes

### CsvParser
- CSV com colunas em português
- CSV com colunas em inglês
- CSV com colunas mistas
- CSV faltando coluna obrigatória
- CSV vazio
- CSV com BOM UTF-8
- CSV com encoding diferente (Latin-1)

### ImportCardsFromCsv
- Importar em deck vazio (define idioma)
- Importar em deck com cartões do mesmo idioma (OK)
- Importar em deck com cartões de idioma diferente (bloqueado)
- CSV com linhas inválidas (campos obrigatórios faltando)
- CSV com idiomas mistos (bloqueado)

### Livewire
- Upload de arquivo e preview funcionando
- Seleção de deck e detecção de idioma
- Toast de sucesso após importação
- Toast de erro com detalhes

---

# Fase 3: Reorganização Visual do Flashcard com Imagem

## Contexto

Cartões com imagem atualmente mostram **imagem + termo** na frente e **definição + detalhes** no verso. A proposta é inverter: quando o cartão tem imagem, a frente mostra **somente a imagem** e o verso concentra **todas as informações** (termo + definição + transliteração + exemplo + tradução).

## Comportamento Atual vs Proposto

### Sem imagem (sem mudança)

| Frente | Verso |
|--------|-------|
| word + POS badge | definition, transliteration, example, translation |

### Com imagem (mudança)

| Frente | Verso |
|--------|-------|
| **somente a imagem** (sem word, sem POS badge) | **word** + definition + transliteration + example + translation |

## Detalhes da Implementação

### Arquivo: `resources/views/pages/⚡study/study.blade.php`

**Frente do card (atual → proposto):**

Atual (linhas 37-53):
- Mostra POS badge (top-left)
- Mostra imagem (se existe)
- Mostra `word` (grande, centralizado)
- Texto "Toque para virar"

Proposto:
- Se `$this->card->imageUrl()` existe → mostrar **somente a imagem** (sem badge, sem word, sem hint de virar — a imagem fala por si)
- Se não tem imagem → manter como está (badge + word + hint)

**Verso do card (atual → proposto):**

Atual (linhas 55-73):
- Mostra definition
- Mostra transliteration (se existe)
- Mostra example + translation (se existem, com divisor)

Proposto (apenas quando tem imagem):
- Mostra **word** (novo — grandes, centralizado, no topo)
- Mostra definition
- Mostra transliteration (se existe)
- Mostra example + translation (se existem, com divisor)
- **Sem mudança** quando não tem imagem (word já está na frente)

### Lógica condicional

```blade
@if ($this->card->imageUrl())
    {{-- FRENTE: só imagem --}}
    {{-- VERSO: word + tudo mais --}}
@else
    {{-- FRENTE: word + badge (como hoje) --}}
    {{-- VERSO: definition + detalhes (como hoje) --}}
@endif
```

### Ajustes de layout para frente com imagem

- A imagem deve ocupar mais espaço (tlz `max-h-60 md:max-h-72` em vez de `max-h-40 md:max-h-48`)
- Centralizada tanto horizontal quanto verticalmente
- Sem `mb-4` (não tem word abaixo)
- Borda `border-t` mantida (continua indicando que é a frente)

### Ajustes de layout para verso com imagem

- Adicionar `word` no topo do verso (apenas quando card tem imagem)
- Usar `text-display-lg` para o word (mesmo estilo da frente atual)
- Adicionar `mb-4` ou divisor entre word e definition para separar visualmente

## Arquivos a modificar

```
resources/views/pages/⚡study/study.blade.php   # Lógica condicional frente/verso
```

## Testes

- Cartão **com imagem**: frente mostra só imagem, verso mostra word + definition + detalhes
- Cartão **sem imagem**: frente mostra word + badge, verso mostra definition + detalhes (como hoje)
- Flip funciona normalmente nos dois casos
- Keyboard shortcuts (Space, 1, 2) funcionam nos dois casos
