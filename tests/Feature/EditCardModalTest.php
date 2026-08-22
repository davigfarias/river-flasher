<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('open fills the form from the card', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'word' => 'λόγος']);

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->assertSet('form.word', 'λόγος')
        ->assertSet('cardId', $card->id);
});

test('save persists the changes, closes the modal and notifies the page', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'word' => 'λόγος']);

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->set('form.word', 'ῥῆμα')
        ->call('save')
        ->assertDispatched('card-updated')
        ->assertDispatched('toast-show');

    expect($card->fresh()->word)->toBe('ῥῆμα');
});

test('it validates required fields on save', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->set('form.word', '')
        ->call('save')
        ->assertHasErrors(['form.word' => 'required']);
});

test('a card belonging to another token cannot be opened', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    $otherCard = Card::factory()->create(['deck_id' => $otherDeck->id]);

    Livewire::test('edit-card-modal')->call('open', $otherCard->id);
})->throws(ModelNotFoundException::class);

test('open exposes the card\'s current image url', function () {
    $card = Card::factory()->withImage()->create(['deck_id' => $this->deck->id]);

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->assertSet('currentImageUrl', $card->imageUrl());
});

test('saving without touching the image leaves it untouched', function () {
    Storage::fake('public');
    $card = Card::factory()->withImage()->create(['deck_id' => $this->deck->id]);
    $originalPath = $card->image_path;
    Storage::disk('public')->put($originalPath, 'contents');

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->set('form.word', 'updated')
        ->call('save');

    expect($card->fresh()->image_path)->toBe($originalPath);
    Storage::disk('public')->assertExists($originalPath);
});

test('uploading a new image replaces the stored file', function () {
    Storage::fake('public');
    $card = Card::factory()->withImage()->create(['deck_id' => $this->deck->id]);
    $originalPath = $card->image_path;
    Storage::disk('public')->put($originalPath, 'contents');

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->set('form.image', UploadedFile::fake()->image('new.jpg', 800, 800))
        ->call('save');

    $card->refresh();

    expect($card->image_path)->not->toBeNull()
        ->and($card->image_path)->not->toBe($originalPath);

    Storage::disk('public')->assertMissing($originalPath);
    Storage::disk('public')->assertExists($card->image_path);
});

test('checking remove image clears it and deletes the stored file', function () {
    Storage::fake('public');
    $card = Card::factory()->withImage()->create(['deck_id' => $this->deck->id]);
    $originalPath = $card->image_path;
    Storage::disk('public')->put($originalPath, 'contents');

    Livewire::test('edit-card-modal')
        ->call('open', $card->id)
        ->set('form.removeImage', true)
        ->call('save');

    expect($card->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($originalPath);
});
