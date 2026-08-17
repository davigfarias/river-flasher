@php
    $links = [
        ['label' => 'Dashboard', 'icon' => 'squares-2x2', 'route' => 'dashboard'],
        ['label' => 'Study', 'icon' => 'academic-cap', 'route' => 'study'],
        ['label' => 'Create', 'icon' => 'plus-circle', 'route' => 'flashcards.create'],
    ];
@endphp

<nav class="hidden md:flex h-screen w-64 fixed left-0 top-0 bg-surface-container border-r border-outline-variant shadow-sm flex-col p-4 z-50">
    <div class="mb-8 flex items-center gap-4 px-2 pt-2">
       <flux:brand  logo="/img/river.png" name="Flasher" />
    </div>

    <flux:navlist class="flex-1">
        @foreach ($links as $link)
            <flux:navlist.item
                :icon="$link['icon']"
                :href="route($link['route'])"
                wire:navigate
                class="data-current:bg-secondary-container! data-current:text-on-secondary-container! hover:data-current:text-on-secondary-container!"
            >
                {{ $link['label'] }}
            </flux:navlist.item>
        @endforeach

        <flux:modal.trigger name="new-deck">
            <flux:navlist.item icon="plus" class="cursor-pointer">
                New Deck
            </flux:navlist.item>
        </flux:modal.trigger>
    </flux:navlist>

    <div class="mt-auto pt-4 border-t border-outline-variant">
        <flux:button :href="route('study')" wire:navigate icon="play" variant="primary" class="w-full justify-center">
            Start Session
        </flux:button>
    </div>
</nav>

<nav class="md:hidden fixed bottom-0 left-0 w-full bg-surface-container border-t border-outline-variant z-50 px-2 pt-1 pb-[calc(0.5rem+env(safe-area-inset-bottom))] flex justify-around items-center shadow-[0_-4px_10px_rgba(0,0,0,0.1)]">
    @foreach ($links as $link)
        @php $current = request()->routeIs($link['route']); @endphp
        <a
            href="{{ route($link['route']) }}"
            wire:navigate
            class="flex flex-col items-center gap-1 p-1 transition-colors {{ $current ? 'text-on-surface' : 'text-on-surface-variant hover:text-on-surface' }}"
        >
            <span class="w-14 h-8 rounded-full flex items-center justify-center {{ $current ? 'bg-secondary-container text-on-secondary-container' : '' }}">
                <flux:icon :icon="$link['icon']" variant="{{ $current ? 'solid' : 'outline' }}" class="size-5" />
            </span>
            <span class="text-[10px] font-medium leading-none">{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>
