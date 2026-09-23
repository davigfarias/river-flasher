<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Paradigmas de flexão — Grego (Koiné)
|--------------------------------------------------------------------------
|
| Cada paradigma é uma tabela de terminações. O motor de formas
| (App\Support\Grammar\Paradigm + App\Actions\GenerateInflectedForm)
| concatena `stem . ending` para gerar cada forma flexionada de um cartão.
|
| Regra do projeto: NENHUMA regra gramatical vive em PHP. Paradigma novo =
| entrada aqui + caso novo no dataset de ouro (tests/Unit/Grammar).
|
| `endings` é indexado por caso (nom|gen|dat|acc|voc) e número (sg|pl).
| Uma terminação `null` marca uma forma imprevisível pela concatenação
| (ex.: nominativo singular da 3ª declinação) — nesse caso o cartão
| precisa de `nom_sg_override` preenchido.
|
| AÇÃO DO USUÁRIO (não do agente): conferir cada linha contra a gramática
| de referência e acrescentar os paradigmas que faltam (1ª decl. α-pura,
| α-impura, masculina em -ας/-ης, adjetivos). O agente não inventa
| paradigma de memória.
|
*/

return [

    'paradigms' => [

        'noun-2-masc' => [
            'label' => '2ª declinação masculina (-ος)',
            'pos' => 'noun',
            'gender' => 'masc',
            'endings' => [
                'nom' => ['sg' => 'ος', 'pl' => 'οι'],
                'gen' => ['sg' => 'ου', 'pl' => 'ων'],
                'dat' => ['sg' => 'ῳ', 'pl' => 'οις'],
                'acc' => ['sg' => 'ον', 'pl' => 'ους'],
                'voc' => ['sg' => 'ε', 'pl' => 'οι'],
            ],
        ],

        'noun-2-neut' => [
            'label' => '2ª declinação neutra (-ον)',
            'pos' => 'noun',
            'gender' => 'neut',
            'endings' => [
                'nom' => ['sg' => 'ον', 'pl' => 'α'],
                'gen' => ['sg' => 'ου', 'pl' => 'ων'],
                'dat' => ['sg' => 'ῳ', 'pl' => 'οις'],
                'acc' => ['sg' => 'ον', 'pl' => 'α'],
                'voc' => ['sg' => 'ον', 'pl' => 'α'],
            ],
        ],

        'noun-1-fem-eta' => [
            'label' => '1ª declinação feminina em -η',
            'pos' => 'noun',
            'gender' => 'fem',
            'endings' => [
                'nom' => ['sg' => 'η', 'pl' => 'αι'],
                'gen' => ['sg' => 'ης', 'pl' => 'ων'],
                'dat' => ['sg' => 'ῃ', 'pl' => 'αις'],
                'acc' => ['sg' => 'ην', 'pl' => 'ας'],
                'voc' => ['sg' => 'η', 'pl' => 'αι'],
            ],
        ],

        // 3ª declinação: o nominativo singular é imprevisível a partir do
        // radical (do genitivo). O cartão fornece `nom_sg_override`.
        'noun-3' => [
            'label' => '3ª declinação (radical do genitivo)',
            'pos' => 'noun',
            'gender' => null,
            'endings' => [
                'nom' => ['sg' => null, 'pl' => 'ες'],
                'gen' => ['sg' => 'ος', 'pl' => 'ων'],
                'dat' => ['sg' => 'ι', 'pl' => 'σι'],
                'acc' => ['sg' => 'α', 'pl' => 'ας'],
                'voc' => ['sg' => null, 'pl' => 'ες'],
            ],
        ],

        // Verbos: `endings` é indexado por pessoa (1|2|3) e número (sg|pl).
        // Paradigma de referência λύω (verbo regular em -ω), stem λυ.
        // AÇÃO DO USUÁRIO: conferir contra a gramática de referência.
        'verb-pres-act' => [
            'label' => 'Presente indicativo ativo',
            'pos' => 'verb',
            'voice' => 'act',
            'endings' => [
                '1' => ['sg' => 'ω', 'pl' => 'ομεν'],
                '2' => ['sg' => 'εις', 'pl' => 'ετε'],
                '3' => ['sg' => 'ει', 'pl' => 'ουσιν'],
            ],
        ],

        // Médio e passivo do presente são morfologicamente idênticos em
        // grego koiné — mesma tabela de terminações, slugs separados só
        // para refletir a voz pretendida no cartão.
        'verb-pres-mid' => [
            'label' => 'Presente indicativo médio',
            'pos' => 'verb',
            'voice' => 'mid',
            'endings' => [
                '1' => ['sg' => 'ομαι', 'pl' => 'ομεθα'],
                '2' => ['sg' => 'ῃ', 'pl' => 'εσθε'],
                '3' => ['sg' => 'εται', 'pl' => 'ονται'],
            ],
        ],

        'verb-pres-pass' => [
            'label' => 'Presente indicativo passivo',
            'pos' => 'verb',
            'voice' => 'pass',
            'endings' => [
                '1' => ['sg' => 'ομαι', 'pl' => 'ομεθα'],
                '2' => ['sg' => 'ῃ', 'pl' => 'εσθε'],
                '3' => ['sg' => 'εται', 'pl' => 'ονται'],
            ],
        ],

        // Futuro: o marcador de tempo σ entra na própria terminação —
        // não muda a estrutura do motor (stem . ending), só o conteúdo
        // da terminação. Só ativo e médio; o futuro passivo koiné usa
        // outro radical (aoristo passivo + θησ) e fica de fora por ora.
        'verb-fut-act' => [
            'label' => 'Futuro indicativo ativo',
            'pos' => 'verb',
            'voice' => 'act',
            'endings' => [
                '1' => ['sg' => 'σω', 'pl' => 'σομεν'],
                '2' => ['sg' => 'σεις', 'pl' => 'σετε'],
                '3' => ['sg' => 'σει', 'pl' => 'σουσιν'],
            ],
        ],

        'verb-fut-mid' => [
            'label' => 'Futuro indicativo médio',
            'pos' => 'verb',
            'voice' => 'mid',
            'endings' => [
                '1' => ['sg' => 'σομαι', 'pl' => 'σομεθα'],
                '2' => ['sg' => 'σῃ', 'pl' => 'σεσθε'],
                '3' => ['sg' => 'σεται', 'pl' => 'σονται'],
            ],
        ],
    ],
];
