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
    ],
];
