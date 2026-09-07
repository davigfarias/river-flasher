<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Writes short Koine Greek practice sentences from a fixed vocabulary,
 * with a morphological analysis of every token. Only ever called from the
 * "Gerar frases" screen action — never from a study or drill request.
 *
 * The model slug can be swapped for any Groq text model that honours a
 * JSON schema.
 */
#[Provider(Lab::Groq)]
#[Model('openai/gpt-oss-120b')]
#[Temperature(0.4)]
class GreekSentenceWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You write very short Koine Greek sentences to drill noun inflection.

        Hard rules:
        - 3 to 6 words per sentence.
        - Use ONLY the content words (lemmas) given in the prompt. You may add
          articles (ὁ/ἡ/τό and their forms) and, at most, one simple copula or
          common verb if the prompt allows it.
        - At least one noun must appear in the grammatical case the prompt asks for.
        - `text` MUST equal the token `surface` values joined by single spaces —
          no punctuation, no leading/trailing spaces.
        - For every noun token, give its `case` (nom|gen|dat|acc|voc) and
          `number` (sg|pl). For non-nouns leave `case` and `number` null.
        - `lemma` is the dictionary form; `surface` is the inflected form as it
          appears in the sentence.
        - Natural meaning matters less than simplicity and correct morphology.

        Return only the structured object. No prose.
        PROMPT;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sentences' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'text' => $schema->string()->required(),
                    'translation_pt' => $schema->string()->required(),
                    'tokens' => $schema->array()->items(
                        $schema->object(fn ($schema) => [
                            'surface' => $schema->string()->required(),
                            'lemma' => $schema->string()->required(),
                            'case' => $schema->string()->enum(['nom', 'gen', 'dat', 'acc', 'voc']),
                            'number' => $schema->string()->enum(['sg', 'pl']),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
