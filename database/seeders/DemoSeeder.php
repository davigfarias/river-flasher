<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoSeeder extends Seeder
{
    /**
     * Fixed local access code, so logging in locally is always known.
     * Production tokens are minted with `php artisan token:generate`.
     */
    private const string DEMO_CODE = '1234';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $token = AccessToken::query()->updateOrCreate(
            ['name' => 'River'],
            ['token' => hash('sha256', self::DEMO_CODE)],
        );

        // Re-running `db:seed` without `--fresh` shouldn't duplicate decks.
        if ($token->decks()->exists()) {
            return;
        }

        $decks = $this->createDecks($token);

        // Filler cards are created first and the curated demo words last,
        // so "recently added" (ordered by created_at) surfaces the real
        // content rather than faker gibberish.
        $this->createHebrewAlphabetCards($decks['hebrew-alphabet']);
        $this->createFillerHistory($token, $decks);
        $this->createFeaturedCards($decks);
        $this->createRecentCards($decks);
    }

    /**
     * @return array<string, Deck>
     */
    private function createDecks(AccessToken $token): array
    {
        $decks = [
            [
                'slug' => 'greek-nt-vocab',
                'name' => 'Koine Greek — New Testament Vocab',
            ],
            [
                'slug' => 'hebrew-genesis',
                'name' => 'Biblical Hebrew — Genesis 1–3',
            ],
            [
                'slug' => 'greek-verbs',
                'name' => 'Greek Verb Paradigms',
            ],
            [
                'slug' => 'hebrew-alphabet',
                'name' => 'Hebrew Alphabet & Niqqud',
            ],
        ];

        return collect($decks)
            ->mapWithKeys(fn (array $deck) => [
                $deck['slug'] => Deck::create([...$deck, 'access_token_id' => $token->id]),
            ])
            ->all();
    }

    /**
     * The five cards shown on the original study-page mockup.
     *
     * @param  array<string, Deck>  $decks
     */
    private function createFeaturedCards(array $decks): void
    {
        $cards = [
            ['deck' => 'greek-nt-vocab', 'language' => Language::Greek, 'pos' => 'Noun', 'word' => 'ἀγάπη', 'transliteration' => 'agápē', 'definition' => 'Selfless, unconditional love; charity.', 'example' => 'ἡ ἀγάπη μακροθυμεῖ.', 'translation' => 'Love is patient.'],
            ['deck' => 'hebrew-genesis', 'language' => Language::Hebrew, 'pos' => 'Noun', 'word' => 'שָׁלוֹם', 'transliteration' => 'shalom', 'definition' => 'Peace; completeness; wellbeing.', 'example' => 'שָׁלוֹם עֲלֵיכֶם', 'translation' => 'Peace be upon you.'],
            ['deck' => 'greek-nt-vocab', 'language' => Language::Greek, 'pos' => 'Noun', 'word' => 'λόγος', 'transliteration' => 'lógos', 'definition' => 'Word, speech, reason, account.', 'example' => 'ἐν ἀρχῇ ἦν ὁ λόγος.', 'translation' => 'In the beginning was the Word.'],
            ['deck' => 'hebrew-genesis', 'language' => Language::Hebrew, 'pos' => 'Noun', 'word' => 'חֶסֶד', 'transliteration' => 'chesed', 'definition' => 'Steadfast love; loyal kindness.', 'example' => 'טוֹב־וְיָשָׁר יְהוָה', 'translation' => 'Good and upright is the LORD.'],
            ['deck' => 'greek-nt-vocab', 'language' => Language::Greek, 'pos' => 'Adjective', 'word' => 'πιστός', 'transliteration' => 'pistós', 'definition' => 'Faithful, trustworthy, reliable.', 'example' => 'πιστός ἐστιν ὁ καλῶν.', 'translation' => 'Faithful is the one who calls.'],
        ];

        $this->createCards($decks, $cards);
    }

    /**
     * The "recently added" words shown on the original create-page mockup.
     *
     * @param  array<string, Deck>  $decks
     */
    private function createRecentCards(array $decks): void
    {
        $cards = [
            ['deck' => 'greek-nt-vocab', 'language' => Language::Greek, 'pos' => 'Noun', 'word' => 'χάρις', 'transliteration' => 'cháris', 'definition' => 'Grace; favor freely given; unmerited kindness.', 'example' => 'τῇ γὰρ χάριτί ἐστε σεσῳσμένοι', 'translation' => 'For by grace you have been saved.'],
            ['deck' => 'hebrew-genesis', 'language' => Language::Hebrew, 'pos' => 'Noun', 'word' => 'בְּרֵאשִׁית', 'transliteration' => 'bereshit', 'definition' => 'In the beginning; first, beginning.', 'example' => 'בְּרֵאשִׁית בָּרָא אֱלֹהִים', 'translation' => 'In the beginning God created.'],
            ['deck' => 'hebrew-genesis', 'language' => Language::Hebrew, 'pos' => 'Noun', 'word' => 'אוֹר', 'transliteration' => 'or', 'definition' => 'Light.', 'example' => 'וַיֹּאמֶר אֱלֹהִים יְהִי אוֹר', 'translation' => 'And God said, Let there be light.'],
            ['deck' => 'hebrew-genesis', 'language' => Language::Hebrew, 'pos' => 'Adjective', 'word' => 'טוֹב', 'transliteration' => 'tov', 'definition' => 'Good.', 'example' => 'וַיַּרְא אֱלֹהִים כִּי־טוֹב', 'translation' => 'And God saw that it was good.'],
            ['deck' => 'greek-verbs', 'language' => Language::Greek, 'pos' => 'Verb', 'word' => 'λύω', 'transliteration' => 'lýō', 'definition' => 'I loose, release, destroy.', 'example' => 'λύω τὸν δοῦλον.', 'translation' => 'I release the servant.'],
            ['deck' => 'greek-verbs', 'language' => Language::Greek, 'pos' => 'Verb', 'word' => 'εἰμί', 'transliteration' => 'eimí', 'definition' => 'I am, exist.', 'example' => 'ἐγώ εἰμι ἡ ὁδός.', 'translation' => 'I am the way.'],
            ['deck' => 'greek-verbs', 'language' => Language::Greek, 'pos' => 'Verb', 'word' => 'γινώσκω', 'transliteration' => 'ginṓskō', 'definition' => 'I know, come to know, understand.', 'example' => 'γινώσκω τὰ ἐμά.', 'translation' => 'I know my own.'],
        ];

        $this->createCards($decks, $cards);
    }

    private function createHebrewAlphabetCards(Deck $deck): void
    {
        $letters = [
            ['word' => 'א', 'transliteration' => 'alef', 'definition' => 'The first letter of the Hebrew alphabet; a silent consonant.'],
            ['word' => 'ב', 'transliteration' => 'bet', 'definition' => 'The second letter; "b" or "v" depending on the dagesh.'],
            ['word' => 'ג', 'transliteration' => 'gimel', 'definition' => 'The third letter; pronounced "g" as in "go".'],
            ['word' => 'ד', 'transliteration' => 'dalet', 'definition' => 'The fourth letter; pronounced "d".'],
        ];

        // Already studied — this deck should show as caught up.
        foreach ($letters as $letter) {
            Card::factory()->studied()->create([
                'deck_id' => $deck->id,
                'language' => Language::Hebrew,
                'pos' => 'Letter',
                'word' => $letter['word'],
                'transliteration' => $letter['transliteration'],
                'definition' => $letter['definition'],
                'example' => null,
                'translation' => null,
            ]);
        }
    }

    /**
     * @param  array<string, Deck>  $decks
     * @param  array<int, array<string, mixed>>  $cards
     */
    private function createCards(array $decks, array $cards): void
    {
        foreach ($cards as $card) {
            $deck = $decks[$card['deck']];

            Card::create([
                'deck_id' => $deck->id,
                'language' => $card['language'],
                'pos' => $card['pos'],
                'word' => $card['word'],
                'transliteration' => $card['transliteration'],
                'definition' => $card['definition'],
                'example' => $card['example'],
                'translation' => $card['translation'],
            ]);
        }
    }

    /**
     * ~30 days of mixed-state filler cards and review history, so the
     * dashboard's streak, activity chart, and reinforce counts all have
     * something real to show immediately after seeding.
     *
     * @param  array<string, Deck>  $decks
     */
    private function createFillerHistory(AccessToken $token, array $decks): void
    {
        /** @var Collection<int, Card> $fillerCards */
        $fillerCards = collect();

        foreach ($decks as $deck) {
            $fillerCards->push(Card::factory()->create(['deck_id' => $deck->id]));
            $fillerCards->push(Card::factory()->studied()->create(['deck_id' => $deck->id]));
            $fillerCards->push(Card::factory()->struggling()->create(['deck_id' => $deck->id]));
        }

        // Guarantee today and yesterday are always populated, so the
        // streak and "last 7 days" strip never start out empty by luck.
        Review::factory()->create([
            'card_id' => $fillerCards->first()->id,
            'access_token_id' => $token->id,
            'reviewed_at' => now(),
        ]);

        Review::factory()->create([
            'card_id' => $fillerCards->last()->id,
            'access_token_id' => $token->id,
            'reviewed_at' => now()->subDay(),
        ]);

        foreach (range(29, 0) as $daysAgo) {
            // A handful of quiet days (but never today or yesterday) keeps
            // the activity chart looking like a real study habit rather
            // than a perfectly unbroken line.
            if ($daysAgo > 1 && random_int(1, 10) === 1) {
                continue;
            }

            foreach (range(1, random_int(1, 4)) as $ignored) {
                Review::factory()->create([
                    'card_id' => $fillerCards->random()->id,
                    'access_token_id' => $token->id,
                    'reviewed_at' => now()->subDays($daysAgo)->setTime(random_int(7, 21), random_int(0, 59)),
                ]);
            }
        }
    }
}
