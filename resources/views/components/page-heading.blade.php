@props(['section'])

<h1 {{ $attributes->merge(['class' => 'd-flex align-items-start gap-2']) }}>
    <x-section-icon :name="$section" class="flex-shrink-0" />
    <span class="text-break" style="min-width: 0;">{{ $slot }}</span>
</h1>