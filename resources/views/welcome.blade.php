<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-white">
        @php
            $navigation = [
                ['label' => 'Platform', 'href' => '#platform'],
                ['label' => 'Suites', 'href' => '#suites'],
                ['label' => 'Workflow', 'href' => '#workflow'],
                ['label' => 'Activity', 'href' => '#activity'],
            ];

            $suites = [
                [
                    'title' => 'AI Content Idea Suite',
                    'copy' => 'Campaign narratives, hooks, and briefs in one click.',
                    'bullets' => ['Audience intel', 'Brief sync', 'CMS export'],
                ],
                [
                    'title' => 'AI Image Idea Suite',
                    'copy' => 'Mood boards and shot lists that stay on-brand.',
                    'bullets' => ['Reference sync', 'Palette guardrails', 'Shareable links'],
                ],
                [
                    'title' => 'AI Video Idea Suite',
                    'copy' => 'Story arcs tuned to funnel data.',
                    'bullets' => ['Hook matrix', 'Storyboard assist', 'Schedule nudges'],
                ],
            ];
        @endphp

        <div class="relative isolate overflow-hidden">
            <div class="pointer-events-none absolute inset-0 opacity-70">
                <div class="absolute -left-32 top-10 h-64 w-64 rounded-full bg-zinc-300/40 blur-3xl dark:bg-zinc-500/20"></div>
                <div class="absolute bottom-10 right-0 h-72 w-72 rounded-full bg-zinc-200/60 blur-[120px] dark:bg-zinc-700/20"></div>
            </div>

            <header class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 pt-10 sm:px-8 lg:px-0">
                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="group inline-flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-900 backdrop-blur dark:border-white/10 dark:bg-white/10 dark:text-white">
                            <x-app-logo-icon class="size-6 text-zinc-900 dark:text-white" />
                        </span>
                        <span class="text-left text-sm font-semibold tracking-tight text-zinc-900 dark:text-white">
                            {{ config('app.name', 'Magika') }}
                            <span class="block text-xs font-normal uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Where the magic begin</span>
                        </span>
                    </a>
                    <nav class="hidden items-center gap-7 text-sm text-zinc-500 md:flex dark:text-zinc-300">
                        @foreach ($navigation as $item)
                            <a
                                href="{{ $item['href'] }}"
                                class="transition hover:text-zinc-900 dark:hover:text-white"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                    <div class="flex items-center gap-3 text-sm font-medium text-zinc-900 dark:text-white">
                        <a
                            href="{{ route('login') }}"
                            class="rounded-full border border-zinc-200 px-4 py-2 transition hover:border-zinc-900/30 dark:border-white/10 dark:hover:border-white/40"
                        >
                            Sign in
                        </a>
                        <button
                            type="button"
                            id="theme-toggle"
                            class="rounded-full border border-zinc-200 px-4 py-2 transition hover:border-zinc-900/30 dark:border-white/10 dark:hover:border-white/40"
                            aria-pressed="false"
                        >
                            Dark mode
                        </button>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-6xl px-6 pb-24 pt-16 sm:px-8 lg:px-0">
                <section id="platform" class="grid gap-12 lg:grid-cols-12 lg:gap-10">
                    <div class="space-y-8 lg:col-span-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.5em] text-zinc-500 dark:text-zinc-400">Signal-led GTM</p>
                        <h1 class="text-4xl font-semibold leading-tight text-zinc-900 sm:text-5xl dark:text-white">
                            Ship creative your data already approves.
                        </h1>
                        <p class="text-lg text-zinc-600 dark:text-zinc-300">
                            {{ config('app.name', 'Magika') }} fuses research, creative intel, and AI helpers so launches stay on-brand and on-time.
                        </p>
                        <div class="flex flex-col gap-4 text-sm text-zinc-700 sm:flex-row sm:text-base dark:text-zinc-200">
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center justify-center rounded-full bg-zinc-900 px-6 py-3 font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                            >
                                Request access
                            </a>
                            <a
                                href="#suites"
                                class="inline-flex items-center justify-center rounded-full border border-zinc-200 px-6 py-3 font-semibold text-zinc-900 transition hover:border-zinc-900/30 dark:border-white/20 dark:text-white dark:hover:border-white/40"
                            >
                                Explore the suites →
                            </a>
                        </div>
                        <dl class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-center dark:border-white/10 dark:bg-white/5">
                                <dt class="text-3xl font-semibold text-zinc-900 dark:text-white">4x</dt>
                                <dd class="text-xs uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Faster briefs</dd>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-center dark:border-white/10 dark:bg-white/5">
                                <dt class="text-3xl font-semibold text-zinc-900 dark:text-white">92%</dt>
                                <dd class="text-xs uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">On-brand assets</dd>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white p-4 text-center dark:border-white/10 dark:bg-white/5">
                                <dt class="text-3xl font-semibold text-zinc-900 dark:text-white">24/7</dt>
                                <dd class="text-xs uppercase tracking-[0.3em] text-zinc-500 dark:text-zinc-400">Creative insights</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-6 lg:col-span-6">
                        <div class="relative rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
                            <div class="absolute inset-x-0 top-0 h-16 rounded-t-3xl bg-gradient-to-b from-zinc-100 to-transparent dark:from-white/10"></div>
                            <div class="relative flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-300">
                                <span>Live campaign pulse</span>
                                <span class="text-xs text-emerald-600 dark:text-emerald-300">+18% lift</span>
                            </div>
                            <div class="relative mt-6 aspect-video overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-zinc-900/40">
                                <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-200 dark:stroke-white/10" />
                                <div class="relative h-full w-full p-6">
                                    <div class="space-y-3 text-sm">
                                        <p class="text-xs uppercase tracking-[0.6em] text-zinc-500 dark:text-zinc-400">Creative signals</p>
                                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-900/60">
                                            <span>UGC Hook Library</span>
                                            <span class="text-emerald-600 dark:text-emerald-300">+42%</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-900/40">
                                            <span>Paid Social Concepts</span>
                                            <span class="text-amber-600 dark:text-amber-300">+21%</span>
                                        </div>
                                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-zinc-900/40">
                                            <span>Email &amp; Lifecycle</span>
                                            <span class="text-sky-600 dark:text-sky-300">+9%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                                Insights, briefs, and launches live in the same view.
                            </p>
                        </div>
                    </div>
                </section>

                <section id="suites" class="mt-20 space-y-10">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.5em] text-zinc-500 dark:text-zinc-400">Creative intelligence</p>
                            <h2 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Suites built for every format.</h2>
                        </div>
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-zinc-900 transition hover:text-zinc-600 dark:text-white dark:hover:text-zinc-200">
                            See it in action →
                        </a>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-3">
                        @foreach ($suites as $suite)
                            <article class="flex flex-col gap-4 rounded-3xl border border-zinc-200 bg-white p-6 backdrop-blur dark:border-white/10 dark:bg-white/5">
                                <div class="inline-flex size-11 items-center justify-center rounded-2xl bg-zinc-100 text-sm font-semibold text-zinc-900 dark:bg-white/10 dark:text-white">
                                    {{ $loop->iteration }}
                                </div>
                                <h3 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $suite['title'] }}</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $suite['copy'] }}</p>
                                <ul class="space-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                                    @foreach ($suite['bullets'] as $bullet)
                                        <li class="flex items-center gap-2">
                                            <span class="inline-block size-1.5 rounded-full bg-emerald-500 dark:bg-emerald-300"></span>
                                            {{ $bullet }}
                                        </li>
                                    @endforeach
                                </ul>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="workflow" class="mt-20 grid gap-10 lg:grid-cols-2">
                    <div class="rounded-3xl border border-zinc-200 bg-white p-8 dark:border-white/10 dark:bg-zinc-900/60">
                        <p class="text-xs uppercase tracking-[0.5em] text-zinc-500 dark:text-zinc-400">Unified workflow</p>
                        <h2 class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-white">Brief, collaborate, approve.</h2>
                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                            One pipeline keeps strategy, creative, and leadership locked in.
                        </p>
                        <ol class="mt-6 space-y-4 text-sm text-zinc-800 dark:text-zinc-100">
                            <li class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="font-semibold text-zinc-900 dark:text-white">1. Pulse the market</p>
                                <p class="text-zinc-600 dark:text-zinc-300">Fresh audience, product, and trend intel.</p>
                            </li>
                            <li class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="font-semibold text-zinc-900 dark:text-white">2. Generate on-brand creative</p>
                                <p class="text-zinc-600 dark:text-zinc-300">Suites inherit your voice and guardrails.</p>
                            </li>
                            <li class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <p class="font-semibold text-zinc-900 dark:text-white">3. Ship with confidence</p>
                                <p class="text-zinc-600 dark:text-zinc-300">Approvals and schedules sync to the dashboard.</p>
                            </li>
                        </ol>
                    </div>
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs uppercase tracking-[0.5em] text-zinc-500 dark:text-zinc-400">Live data</p>
                            <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900/40">
                                <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-200">
                                    <span>Creative backlog</span>
                                    <span class="text-emerald-600 dark:text-emerald-300">72% ahead</span>
                                </div>
                                <div class="mt-4 h-2 rounded-full bg-zinc-200 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-sky-500 dark:from-emerald-300 dark:to-sky-300" style="width: 72%;"></div>
                                </div>
                            </div>
                            <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-white/10 dark:bg-zinc-900/40 dark:text-zinc-200">
                                <p class="font-semibold text-zinc-900 dark:text-white">Channel mix recommendations</p>
                                <p class="mt-2 text-zinc-600 dark:text-zinc-300">AI routes each asset to its best channel.</p>
                            </div>
                        </div>
                        <div id="activity" class="rounded-3xl border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-900/80">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold uppercase tracking-[0.4em] text-zinc-500 dark:text-zinc-400">Activity stream</p>
                                <a href="{{ route('activity.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 dark:text-white/70 dark:hover:text-white">
                                    View log →
                                </a>
                            </div>
                            <ul class="mt-4 space-y-3 text-sm text-zinc-700 dark:text-zinc-200">
                                <li class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">Alex queued TikTok hooks</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">AI Content Idea Suite · 2m ago</p>
                                </li>
                                <li class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">Maya approved a carousel board</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">AI Image Suite · 14m ago</p>
                                </li>
                                <li class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">CRM sync pushed new insights</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Automations · 28m ago</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="mt-24 rounded-3xl border border-zinc-200 bg-gradient-to-br from-white via-zinc-50 to-transparent p-10 text-center shadow-sm dark:border-white/10 dark:from-white/10 dark:via-white/5">
                    <p class="text-xs uppercase tracking-[0.5em] text-zinc-500 dark:text-zinc-200">Ready to build?</p>
                    <h2 class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-white">Give your team the creative advantage.</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-sm text-zinc-600 dark:text-zinc-200">
                        Plug into {{ config('app.name', 'Magika') }} and launch with focus.
                    </p>
                    <div class="mt-6 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center justify-center rounded-full bg-zinc-900 px-8 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                        >
                            Join the waitlist
                        </a>
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex items-center justify-center rounded-full border border-zinc-200 px-8 py-3 text-sm font-semibold text-zinc-900 transition hover:border-zinc-900/40 dark:border-white/30 dark:text-white dark:hover:border-white/60"
                        >
                            Peek at the dashboard
                        </a>
                    </div>
                </section>
            </main>
        </div>

        <script>
            (() => {
                const root = document.documentElement;
                const button = document.getElementById('theme-toggle');
                const storageKey = 'flux.appearance';
                const media = window.matchMedia('(prefers-color-scheme: dark)');

                if (!button) {
                    return;
                }

                const storedPreference = () => localStorage.getItem(storageKey);

                const applyAppearance = (appearance) => {
                    const applyDark = () => root.classList.add('dark');
                    const applyLight = () => root.classList.remove('dark');

                    if (appearance === 'dark') {
                        localStorage.setItem(storageKey, 'dark');
                        applyDark();
                    } else if (appearance === 'light') {
                        localStorage.setItem(storageKey, 'light');
                        applyLight();
                    } else {
                        localStorage.removeItem(storageKey);
                        media.matches ? applyDark() : applyLight();
                    }

                    if (typeof window.Flux?.applyAppearance === 'function') {
                        window.Flux.applyAppearance(appearance);
                    }
                };

                const currentAppearance = () => storedPreference() ?? (media.matches ? 'dark' : 'light');

                const syncLabel = () => {
                    const isDark = root.classList.contains('dark');
                    button.textContent = isDark ? 'Light mode' : 'Dark mode';
                    button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                };

                applyAppearance(currentAppearance());
                syncLabel();

                button.addEventListener('click', () => {
                    const next = root.classList.contains('dark') ? 'light' : 'dark';
                    applyAppearance(next);
                    syncLabel();
                });

                const handlePrefChange = () => {
                    if (storedPreference()) {
                        return;
                    }

                    applyAppearance('system');
                    syncLabel();
                };

                if (media.addEventListener) {
                    media.addEventListener('change', handlePrefChange);
                } else if (media.addListener) {
                    media.addListener(handlePrefChange);
                }
            })();
        </script>
        @fluxScripts
    </body>
</html>
