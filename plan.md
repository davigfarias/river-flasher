# Plano: Drills morfológicos estilo Duolingo (River Flasher)

> Documento de trabalho para execução incremental pelo Claude Code.
> Executar **uma fatia por vez**. Não avançar para a fatia seguinte sem os testes da anterior passando.

---

## 0. Antes de escrever qualquer código

1. Rodar as skills/ferramentas de contexto do projeto (Laravel Boost, `search-docs` para Livewire 4, Flux UI, Pest v4).
2. Ler `app/Models/Card.php`, as migrations existentes de `cards` e a estrutura atual de decks.
3. **Não presumir** nomes de colunas: confirmar no schema real antes de escrever migration.
4. Reportar ao usuário o que encontrou antes de começar a Fatia 1.

---

## 1. Objetivo

Treinar **reconhecimento e produção de formas flexionadas** (declinações gregas, depois hebraico), não vocabulário e não sintaxe.

O usuário já tem ~300 cartões em produção com: palavra, transliteração, significado, classe gramatical, frase de exemplo, tradução da frase.

Dois exercícios, ambos inspirados no Duolingo:

| Tipo | Duolingo chama de | Prioridade |
|---|---|---|
| Frase com lacuna, banco de palavras | *Tap Complete* | **Fase 1** |
| Frase inteira embaralhada para remontar | *Translate Tap* | Fase 2 |
| Lacuna com radical fixo + blocos de terminação | (variação própria) | Fase 2 |

---

## 2. Regras não negociáveis

- **Nunca paradigma procedural.** OO ou funcional. Value objects imutáveis, services injetados, repositories para acesso a dados.
- **Nenhuma regra gramatical hardcoded em PHP.** Toda terminação vive em arquivo de config. Regra nova = entrada no config + caso no dataset de teste.
- **IA nunca em tempo de execução.** IA só em comando artisan offline, com fila de revisão humana.
- **IA nunca corrige.** A correção é comparação determinística de string.
- **Migrations apenas aditivas.** Colunas novas em `cards` são todas `nullable`. Base em produção com meses de dados: nada de renomear, remover ou tornar obrigatório.
- **Livewire puro, sem Alpine.js e sem JavaScript custom.** Interações por `wire:click`. Se algo parecer exigir JS, parar e perguntar.
- **Sem pacote novo** sem aprovação explícita do usuário.

---

## 3. Fatia 1 — Tabela de paradigmas (config)

Criar `config/grammar/greek.php`. Estrutura:

```php
return [
    'paradigms' => [
        'noun-2-masc' => [
            'label' => '2ª declinação masculina (-ος)',
            'pos' => 'noun',
            'gender' => 'masc',
            'endings' => [
                'nom' => ['sg' => 'ος', 'pl' => 'οι'],
                'gen' => ['sg' => 'ου', 'pl' => 'ων'],
                'dat' => ['sg' => 'ῳ',  'pl' => 'οις'],
                'acc' => ['sg' => 'ον', 'pl' => 'ους'],
                'voc' => ['sg' => 'ε',  'pl' => 'οι'],
            ],
        ],
        'noun-2-neut' => [
            'label' => '2ª declinação neutra (-ον)',
            'pos' => 'noun',
            'gender' => 'neut',
            'endings' => [
                'nom' => ['sg' => 'ον', 'pl' => 'α'],
                'gen' => ['sg' => 'ου', 'pl' => 'ων'],
                'dat' => ['sg' => 'ῳ',  'pl' => 'οις'],
                'acc' => ['sg' => 'ον', 'pl' => 'α'],
                'voc' => ['sg' => 'ον', 'pl' => 'α'],
            ],
        ],
        'noun-1-fem-eta' => [
            'label' => '1ª declinação feminina em -η',
            'pos' => 'noun',
            'gender' => 'fem',
            'endings' => [
                'nom' => ['sg' => 'η',  'pl' => 'αι'],
                'gen' => ['sg' => 'ης', 'pl' => 'ων'],
                'dat' => ['sg' => 'ῃ',  'pl' => 'αις'],
                'acc' => ['sg' => 'ην', 'pl' => 'ας'],
                'voc' => ['sg' => 'η',  'pl' => 'αι'],
            ],
        ],
        // 3ª declinação: nominativo singular é imprevisível.
        // Ver `nom_sg_override` no cartão (Fatia 2).
        'noun-3' => [
            'label' => '3ª declinação (radical do genitivo)',
            'pos' => 'noun',
            'gender' => null,
            'endings' => [
                'nom' => ['sg' => null, 'pl' => 'ες'],
                'gen' => ['sg' => 'ος',  'pl' => 'ων'],
                'dat' => ['sg' => 'ι',   'pl' => 'σι'],
                'acc' => ['sg' => 'α',   'pl' => 'ας'],
                'voc' => ['sg' => null,  'pl' => 'ες'],
            ],
        ],
    ],
];
```

> **Ação do usuário (não do Claude Code):** conferir cada linha contra a gramática de referência
> e completar os paradigmas que faltam (1ª decl. α-pura, α-impura, masculina em -ας/-ης, adjetivos).
> O Claude Code não deve inventar paradigmas de memória.

**Aceite da fatia:** `php artisan tinker` consegue ler o config e listar os paradigmas.

---

## 4. Fatia 2 — Migrations

### 4.1 Colunas em `cards` (todas nullable)

- `stem` (string) — radical. Para 3ª declinação, o radical do genitivo (σάρξ → `σαρκ`).
- `paradigm_slug` (string) — chave do config.
- `gender` (string) — `masc` | `fem` | `neut`.
- `nom_sg_override` (string) — forma do nominativo singular quando irregular (3ª declinação).

Cartões sem esses campos simplesmente não entram nos drills. Nada quebra.

### 4.2 Tabela `sentences`

| coluna | tipo | nota |
|---|---|---|
| `id` | id | |
| `text` | text | frase completa em grego |
| `translation_pt` | text | tradução em português |
| `source` | string | `manual` \| `ai` |
| `status` | string | `pending` \| `approved` \| `rejected` |
| `grammar_focus` | string, nullable | ex.: `dat`, `gen-pl` |
| `deck_id` | fk, nullable | de qual deck saiu o vocabulário |
| timestamps | | |

### 4.3 Tabela `sentence_tokens`

| coluna | tipo | nota |
|---|---|---|
| `id` | id | |
| `sentence_id` | fk cascade | |
| `position` | integer | ordem na frase |
| `surface` | string | forma como aparece na frase |
| `card_id` | fk nullable | vínculo com o cartão (lexema) |
| `case` | string, nullable | `nom` `gen` `dat` `acc` `voc` |
| `number` | string, nullable | `sg` \| `pl` |
| `is_target` | boolean | se pode virar lacuna |

Token como linha é o eixo do sistema: lacuna, blocos, distratores e validação leem todos daqui.

**Aceite:** migrations rodam e revertem sem erro; nenhum teste existente quebra.

---

## 5. Fatia 3 — Domínio: gerador de formas

Namespace `App\Domain\Grammar`. Sem dependência de Eloquent.

- `Feature` (enum ou VO): `GrammaticalCase`, `GrammaticalNumber`.
- `Paradigm` — value object imutável construído a partir do config. Método `endingFor(GrammaticalCase $case, GrammaticalNumber $number): ?string`.
- `ParadigmRepository` — carrega paradigmas do config, retorna `Paradigm`. Interface + implementação.
- `FormGenerator` — `generate(string $stem, Paradigm $paradigm, GrammaticalCase $case, GrammaticalNumber $number): string`. Concatena. Se a terminação for `null` e houver `nom_sg_override`, usa o override.
- `FormComparator` — `matches(string $expected, string $given): bool`. Compara **normalizando**: remove acentos, espíritos e iota subscrito, e faz `mb_strtolower`. Usar `Normalizer::normalize(..., Normalizer::FORM_D)` e remover marcas combinantes.

> **Limitação assumida e documentada:** a concatenação acerta as letras mas não move o acento
> (ἄνθρωπος → ἀνθρώπου). Por isso a comparação ignora diacríticos. O objetivo do usuário é
> terminação, não acentuação. Não tentar resolver acento nesta versão.

### Testes de ouro (obrigatórios)

Criar `tests/Unit/Grammar/FormGeneratorTest.php` com um **dataset Pest** contendo o paradigma completo (10 formas) de pelo menos três palavras, copiado da gramática de referência do usuário:

- `λόγος` (2ª masc)
- `γραφή` (1ª fem em -η)
- `ἔργον` (2ª neutra)

Toda forma gerada tem que bater com a tabela. Este teste é o contrato do motor.

### Comando de verificação

`php artisan grammar:paradigm {lexeme}` — imprime a declinação completa em tabela no terminal, para o usuário conferir contra o livro ao cadastrar cada paradigma novo.

---

## 6. Fatia 4 — Geração de frases por IA (offline)

Comando: `php artisan sentences:generate --deck=3 --case=dat --count=15`

### Fluxo

1. `SentenceGenerationService` busca no deck os cartões com `stem` e `paradigm_slug` preenchidos.
2. Monta o prompt com **apenas esse vocabulário**.
3. Chama a API (adapter `SentenceSource`, implementação para o provedor que o usuário usar).
4. Valida a resposta.
5. Persiste como `status = pending`.

### Formato de saída exigido da IA

Resposta **somente JSON**, sem markdown, sem preâmbulo:

```json
{
  "sentences": [
    {
      "text": "ὁ ἀπόστολος γράφει τῷ ἀνθρώπῳ",
      "translation_pt": "o apóstolo escreve ao homem",
      "tokens": [
        {"surface": "ὁ", "lemma": "ὁ", "case": "nom", "number": "sg"},
        {"surface": "ἀπόστολος", "lemma": "ἀπόστολος", "case": "nom", "number": "sg"},
        {"surface": "γράφει", "lemma": "γράφω", "case": null, "number": null},
        {"surface": "τῷ", "lemma": "ὁ", "case": "dat", "number": "sg"},
        {"surface": "ἀνθρώπῳ", "lemma": "ἄνθρωπος", "case": "dat", "number": "sg"}
      ]
    }
  ]
}
```

Instruções a incluir no prompt: frases curtas (3 a 6 palavras), usar **exclusivamente** os lemas fornecidos, pelo menos um substantivo no caso pedido, sentido natural mas simplicidade acima de elegância.

### Regras de rejeição automática (`SentenceValidator`)

Descartar a frase **sem mostrar ao usuário** se:

1. Algum `lemma` não corresponde a um cartão do deck.
2. Para todo token com `case` e `number` preenchidos e cartão com `stem`/`paradigm_slug`: `FormGenerator` gera a forma esperada e `FormComparator` não bate com `surface`.
3. Nenhum token no caso pedido pelo `--case`.
4. `text` não é igual à junção dos `surface` por espaço.

O comando imprime no final: geradas / aceitas / rejeitadas, com o motivo de cada rejeição.

**Aceite:** teste com resposta de IA falsificada (fixture JSON) — uma frase válida passa, uma com forma morfologicamente errada é rejeitada.

---

## 7. Fatia 5 — Tela de revisão (Livewire)

Rota `/sentences/review`. Componente Livewire com Flux UI.

- Lista as frases `pending`, uma por vez.
- Mostra frase, tradução e os tokens com sua anotação.
- Botões: **Aprovar**, **Rejeitar**, **Editar tradução**.
- Aprovar → `status = approved`; a partir daí a frase entra no pool de drills.

Sem JS. Navegação por `wire:click`.

---

## 8. Fatia 6 — Drill *Tap Complete*

Rota `/drills/{deck}`. Componente Livewire.

### Montagem do exercício (`DrillItemBuilder`)

1. Sorteia uma `sentence` aprovada do deck com o foco gramatical escolhido.
2. Sorteia um token com `is_target = true` e `card_id` preenchido.
3. Substitui esse token por uma lacuna na exibição.
4. Monta o banco de palavras: a forma correta + **3 distratores gerados pelo `FormGenerator` a partir do mesmo cartão em outros casos/números**.
5. Embaralha.

> Distrator é sempre o mesmo lexema em outra flexão. Nunca outra palavra.
> A armadilha tem que ser gramatical, não lexical.

### Correção

`FormComparator` entre o bloco escolhido e o `surface` do token. Feedback imediato: acerto/erro, forma correta, e a análise (`dativo singular`).

### Sessão

12 exercícios por sessão. No fim, resumo: acertos e quais casos/números falharam.

**Aceite:** dá para completar uma sessão inteira ponta a ponta com dados de seed.

---

## 9. Fase 2 (não fazer agora)

- **Translate Tap**: remontar a frase inteira. Exige coluna `accepted_orders` em `sentences`, porque grego tem ordem livre de palavras. Só depois da Fase 1 rodando.
- **Forja da palavra**: a lacuna mostra o radical fixo e blocos de terminação; o usuário monta `ἀνθρωπ` + `ῳ`.
- **Hebraico**: mesma estrutura, `config/grammar/hebrew.php` (plural ־ים/־ות, construto, sufixos pronominais). Reaproveita todo o domínio.
- **Agendamento por traço gramatical**: registrar erro por `case`+`number` e priorizar os fracos na montagem da sessão.

---

## 10. Ordem de execução resumida

1. Ler o schema atual e reportar
2. Config de paradigmas
3. Migrations aditivas
4. Domínio + testes de ouro + `grammar:paradigm`
5. Comando de geração + validador (com fixtures)
6. Tela de revisão
7. Drill Tap Complete
8. Parar. Usar. Só então Fase 2.
