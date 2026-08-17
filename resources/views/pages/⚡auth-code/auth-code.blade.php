<div class="w-full max-w-md bg-surface-container-high rounded-xl border border-outline-variant/30 shadow-[0_10px_30px_rgba(0,0,0,0.2)] p-6 md:p-8 flex flex-col items-center text-center transition-shadow duration-300 hover:shadow-[0_15px_40px_rgba(0,0,0,0.3)]">
    <div class="mb-6 w-20 h-20 rounded-full bg-surface flex items-center justify-center border border-outline-variant/50 shadow-sm">
        <flux:icon.droplet class="size-8 text-primary" />
    </div>

    <h1 class="text-headline-lg-mobile md:text-headline-lg text-on-surface mb-2">Enter Access Code</h1>
    <p class="text-body-md text-on-surface-variant mb-8">Please enter the 4-digit code provided to join the study session.</p>

    <form wire:submit="joinSession" class="w-full flex flex-col gap-6">
        <flux:otp wire:model="code" length="4" submit="auto" class="w-full justify-between" />

        @error('code')
            <flux:text class="text-error text-label-sm -mt-4">{{ $message }}</flux:text>
        @enderror

        <flux:button type="submit" variant="primary" icon:trailing="arrow-right" class="w-full justify-center min-h-12">
            Start Studying
        </flux:button>
    </form>
</div>