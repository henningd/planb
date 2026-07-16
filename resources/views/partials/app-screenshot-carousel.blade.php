{{--
    Carousel mit echten App-Store-Screenshots der Notfall-App, in einem
    Geräterahmen. Reines HTML/CSS + vanilla JS (Initialisierung siehe
    welcome.blade.php) – keine zusätzliche Abhängigkeit.

    Parameter:
      $carouselId  eindeutige ID (z. B. 'iphone')
      $device      'iphone' | 'ipad' – steuert Rahmen und Seitenverhältnis
      $shots       Array von ['file' => 'iphone-01-home.webp', 'caption' => '…']
      $srLabel     Bezeichnung für Screenreader (z. B. 'iPhone-Screenshots')
--}}
@php
    $device = $device ?? 'iphone';
    $isPhone = $device === 'iphone';
    $srLabel = $srLabel ?? 'App-Screenshots';
    // Rahmenmaße orientieren sich am bisherigen Mockup (partials/notfall-app-mockup).
    $frameOuter = $isPhone ? 'rounded-[2.6rem] p-2.5 max-w-[290px]' : 'rounded-[1.8rem] p-3 max-w-4xl';
    $frameInner = $isPhone ? 'rounded-[2.1rem] aspect-[700/1514]' : 'rounded-[1.1rem] aspect-[4/3]';
    // Native Maße der abgelegten Dateien – verhindert Layout-Sprünge beim Laden.
    $imgW = $isPhone ? 700 : 1400;
    $imgH = $isPhone ? 1514 : 1050;
@endphp

<div class="relative" data-carousel id="{{ $carouselId }}">
    <div class="absolute -inset-6 rounded-[3rem] bg-gradient-to-tr from-indigo-500/25 via-indigo-400/10 to-transparent blur-2xl" aria-hidden="true"></div>

    <div class="relative">
        {{-- Geräterahmen --}}
        <div class="mx-auto bg-slate-800 ring-1 ring-slate-700 shadow-2xl {{ $frameOuter }}">
            <div
                class="relative overflow-hidden bg-slate-100 {{ $frameInner }}"
                role="group"
                aria-roledescription="Karussell"
                aria-label="{{ $srLabel }}"
                data-carousel-viewport
            >
                @foreach ($shots as $i => $shot)
                    <figure
                        class="absolute inset-0 transition-opacity duration-500 motion-reduce:transition-none {{ $i === 0 ? 'opacity-100' : 'opacity-0' }}"
                        data-carousel-slide
                        @if ($i !== 0) aria-hidden="true" @endif
                    >
                        <img
                            src="{{ asset('images/app/'.$shot['file']) }}"
                            alt="{{ $shot['caption'] }}"
                            width="{{ $imgW }}"
                            height="{{ $imgH }}"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-cover"
                        >
                    </figure>
                @endforeach
            </div>
        </div>

        {{-- Steuerung: Zurück · Punkte · Weiter --}}
        <div class="mt-5 flex items-center justify-center gap-4">
            <button
                type="button"
                data-carousel-prev
                aria-label="Vorheriger Screenshot"
                aria-controls="{{ $carouselId }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-800/80 text-slate-300 ring-1 ring-slate-700 transition hover:bg-slate-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>

            <div class="flex items-center gap-2">
                @foreach ($shots as $i => $shot)
                    <button
                        type="button"
                        data-carousel-dot
                        data-index="{{ $i }}"
                        aria-controls="{{ $carouselId }}"
                        aria-label="Screenshot {{ $i + 1 }}: {{ $shot['caption'] }}"
                        @if ($i === 0) aria-current="true" @endif
                        class="h-2 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 {{ $i === 0 ? 'w-6 bg-indigo-400' : 'w-2 bg-slate-600 hover:bg-slate-500' }}"
                    ></button>
                @endforeach
            </div>

            <button
                type="button"
                data-carousel-next
                aria-label="Nächster Screenshot"
                aria-controls="{{ $carouselId }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-800/80 text-slate-300 ring-1 ring-slate-700 transition hover:bg-slate-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>

        <p class="mt-3 text-center text-sm text-slate-400" data-carousel-caption aria-live="polite">
            {{ $shots[0]['caption'] }}
        </p>
    </div>
</div>
