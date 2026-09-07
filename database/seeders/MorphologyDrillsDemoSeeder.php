<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\Language;
use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Sentence;
use Illuminate\Database\Seeder;

/**
 * Demo data for the morphology drills: one Greek deck of fully annotated
 * 2nd/1st/3rd-declension nouns, plus a few approved sentences so
 * "Gerar frases" and "Praticar" both work the moment you log in.
 *
 * Idempotent — safe to re-run. Attaches to the lowest-id non-revoked
 * access token (the one you log in with locally).
 */
class MorphologyDrillsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $token = AccessToken::query()->whereNull('revoked_at')->orderBy('id')->first()
            ?? AccessToken::factory()->create(['name' => 'Demo']);

        $deck = Deck::query()->firstOrCreate([
            'access_token_id' => $token->id,
            'name' => 'Grego — Substantivos (drills)',
        ]);

        foreach ($this->nouns() as $noun) {
            Card::query()->updateOrCreate(
                ['deck_id' => $deck->id, 'word' => $noun['word']],
                [
                    'language' => Language::Greek,
                    'pos' => 'Substantivo',
                    'transliteration' => $noun['translit'],
                    'definition' => $noun['definition'],
                    'stem' => $noun['stem'],
                    'paradigm_slug' => $noun['paradigm'],
                    'gender' => $noun['gender'],
                    'nom_sg_override' => $noun['override'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        if ($deck->sentences()->exists()) {
            return;
        }

        foreach ($this->sentences() as $data) {
            $this->makeSentence($token->id, $deck->id, $data);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function nouns(): array
    {
        return [
            ['word' => 'ἄνθρωπος', 'translit' => 'ánthrōpos', 'definition' => 'homem, ser humano', 'stem' => 'ἀνθρωπ', 'paradigm' => 'noun-2-masc', 'gender' => Gender::Masculine],
            ['word' => 'λόγος', 'translit' => 'lógos', 'definition' => 'palavra, razão', 'stem' => 'λογ', 'paradigm' => 'noun-2-masc', 'gender' => Gender::Masculine],
            ['word' => 'ἀπόστολος', 'translit' => 'apóstolos', 'definition' => 'apóstolo, enviado', 'stem' => 'ἀποστολ', 'paradigm' => 'noun-2-masc', 'gender' => Gender::Masculine],
            ['word' => 'δοῦλος', 'translit' => 'doûlos', 'definition' => 'servo, escravo', 'stem' => 'δουλ', 'paradigm' => 'noun-2-masc', 'gender' => Gender::Masculine],
            ['word' => 'ἔργον', 'translit' => 'érgon', 'definition' => 'obra, trabalho', 'stem' => 'ἐργ', 'paradigm' => 'noun-2-neut', 'gender' => Gender::Neuter],
            ['word' => 'τέκνον', 'translit' => 'téknon', 'definition' => 'filho, criança', 'stem' => 'τεκν', 'paradigm' => 'noun-2-neut', 'gender' => Gender::Neuter],
            ['word' => 'γραφή', 'translit' => 'graphḗ', 'definition' => 'escritura, escrito', 'stem' => 'γραφ', 'paradigm' => 'noun-1-fem-eta', 'gender' => Gender::Feminine],
            ['word' => 'ἐντολή', 'translit' => 'entolḗ', 'definition' => 'mandamento', 'stem' => 'ἐντολ', 'paradigm' => 'noun-1-fem-eta', 'gender' => Gender::Feminine],
            ['word' => 'σάρξ', 'translit' => 'sárx', 'definition' => 'carne', 'stem' => 'σαρκ', 'paradigm' => 'noun-3', 'gender' => Gender::Feminine, 'override' => 'σάρξ'],
        ];
    }

    /**
     * Hand-built so the tokens' cases/numbers match the surfaces exactly.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sentences(): array
    {
        return [
            [
                'text' => 'ὁ ἀπόστολος γράφει τῷ ἀνθρώπῳ',
                'translation' => 'o apóstolo escreve ao homem',
                'focus' => GrammaticalCase::Dative,
                'tokens' => [
                    ['ὁ', null, null, null],
                    ['ἀπόστολος', 'ἀπόστολος', GrammaticalCase::Nominative, GrammaticalNumber::Singular],
                    ['γράφει', null, null, null],
                    ['τῷ', null, null, null],
                    ['ἀνθρώπῳ', 'ἄνθρωπος', GrammaticalCase::Dative, GrammaticalNumber::Singular],
                ],
            ],
            [
                'text' => 'ὁ δοῦλος βλέπει τὸ ἔργον τοῦ ἀνθρώπου',
                'translation' => 'o servo vê a obra do homem',
                'focus' => GrammaticalCase::Genitive,
                'tokens' => [
                    ['ὁ', null, null, null],
                    ['δοῦλος', 'δοῦλος', GrammaticalCase::Nominative, GrammaticalNumber::Singular],
                    ['βλέπει', null, null, null],
                    ['τὸ', null, null, null],
                    ['ἔργον', 'ἔργον', GrammaticalCase::Accusative, GrammaticalNumber::Singular],
                    ['τοῦ', null, null, null],
                    ['ἀνθρώπου', 'ἄνθρωπος', GrammaticalCase::Genitive, GrammaticalNumber::Singular],
                ],
            ],
            [
                'text' => 'πιστεύω τῇ γραφῇ καὶ ταῖς ἐντολαῖς',
                'translation' => 'creio na escritura e nos mandamentos',
                'focus' => GrammaticalCase::Dative,
                'tokens' => [
                    ['πιστεύω', null, null, null],
                    ['τῇ', null, null, null],
                    ['γραφῇ', 'γραφή', GrammaticalCase::Dative, GrammaticalNumber::Singular],
                    ['καὶ', null, null, null],
                    ['ταῖς', null, null, null],
                    ['ἐντολαῖς', 'ἐντολή', GrammaticalCase::Dative, GrammaticalNumber::Plural],
                ],
            ],
            [
                'text' => 'οἱ ἀπόστολοι κηρύσσουσι τοῖς τέκνοις',
                'translation' => 'os apóstolos pregam às crianças',
                'focus' => GrammaticalCase::Dative,
                'tokens' => [
                    ['οἱ', null, null, null],
                    ['ἀπόστολοι', 'ἀπόστολος', GrammaticalCase::Nominative, GrammaticalNumber::Plural],
                    ['κηρύσσουσι', null, null, null],
                    ['τοῖς', null, null, null],
                    ['τέκνοις', 'τέκνον', GrammaticalCase::Dative, GrammaticalNumber::Plural],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeSentence(int $tokenId, int $deckId, array $data): void
    {
        $cardsByWord = Card::query()->where('deck_id', $deckId)->get()->keyBy('word');

        $sentence = Sentence::create([
            'access_token_id' => $tokenId,
            'deck_id' => $deckId,
            'text' => $data['text'],
            'translation_pt' => $data['translation'],
            'source' => SentenceSource::Manual,
            'status' => SentenceStatus::Approved,
            'grammar_focus' => $data['focus']->value,
        ]);

        foreach ($data['tokens'] as $position => [$surface, $lemma, $case, $number]) {
            $card = $lemma !== null ? $cardsByWord->get($lemma) : null;

            $sentence->tokens()->create([
                'position' => $position,
                'surface' => $surface,
                'card_id' => $card?->id,
                'grammatical_case' => $case,
                'grammatical_number' => $number,
                'is_target' => $card !== null && $case !== null && $number !== null,
            ]);
        }
    }
}
