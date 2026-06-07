<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>GTrack</title>

        {{-- Favicon --}}
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- GSAP for entrance + floating animations --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

        {{-- Mark that JS is on, so we can hide content until the intro plays (no-JS keeps it visible) --}}
        <script>document.documentElement.classList.add('js');</script>

        <style>
            body { font-family: 'Inter', sans-serif; }

            h1, h2, h3, .font-display {
                font-family: 'Inter', sans-serif;
                letter-spacing: -0.02em;
            }

            /* Hide until GSAP reveals — only when JS is available */
            html.js body { opacity: 0; }

            /* ===== White (form) side decoration ===== */
            .form-side {
                background-color: #ffffff;
                background-image:
                    radial-gradient(circle at 12% 18%, rgba(37, 99, 235, 0.06) 0, transparent 38%),
                    radial-gradient(circle at 90% 88%, rgba(6, 182, 212, 0.05) 0, transparent 40%),
                    radial-gradient(circle, #e5e7eb 1px, transparent 1px);
                background-size: auto, auto, 26px 26px;
            }

            .wt-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(2px);
                pointer-events: none;
            }

            /* ===== Deep blue branding panel (muted, premium) ===== */
            .brand-panel {
                background: linear-gradient(155deg, #0a1f4d 0%, #12317a 48%, #1d4ed8 100%);
            }

            @media (min-width: 1024px) {
                .brand-panel { clip-path: polygon(18% 0, 100% 0, 100% 100%, 0 100%); }
            }

            .ring-deco {
                position: absolute;
                border-radius: 50%;
                border: 1.5px solid rgba(255, 255, 255, 0.14);
                pointer-events: none;
            }

            /* Soft glow behind the logo on the blue side */
            .logo-glow {
                position: absolute;
                width: 220px; height: 220px;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                background: radial-gradient(circle, rgba(255,255,255,0.22) 0%, transparent 70%);
                border-radius: 50%;
                pointer-events: none;
            }

            /* ===== Brand wordmark (solid, professional) ===== */
            .brand-wordmark {
                font-family: 'Inter', sans-serif;
                font-weight: 800;
                letter-spacing: -0.04em;
                line-height: 1;
                text-shadow: 0 2px 20px rgba(0, 0, 0, 0.18);
            }
            .brand-accent {
                width: 52px;
                height: 4px;
                border-radius: 99px;
                background: linear-gradient(90deg, #22d3ee 0%, #38bdf8 100%);
                box-shadow: 0 0 12px rgba(56, 189, 248, 0.6);
            }

            /* ===== Interactive feature list ===== */
            .feat-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 9px 12px;
                border-radius: 12px;
                color: rgba(219, 234, 254, 0.85);
                cursor: default;
                position: relative;
                transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
                            background 0.35s ease, color 0.35s ease;
            }
            .feat-item:hover {
                background: rgba(255, 255, 255, 0.10);
                transform: translateX(8px);
                color: #ffffff;
            }
            .feat-check {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                flex-shrink: 0;
                transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1),
                            background 0.35s ease, color 0.35s ease;
            }
            .feat-item:hover .feat-check {
                background: #ffffff;
                color: #1d4ed8;
                transform: scale(1.15) rotate(360deg);
            }
            /* Glow accent line that appears on hover */
            .feat-item::before {
                content: '';
                position: absolute;
                left: 0;
                top: 50%;
                width: 3px;
                height: 0;
                border-radius: 3px;
                background: #ffffff;
                transform: translateY(-50%);
                transition: height 0.35s ease;
            }
            .feat-item:hover::before {
                height: 60%;
            }
        </style>
    </head>
    <body class="antialiased">
        @include('partials.loader')
        <div class="min-h-screen flex flex-col lg:flex-row">

            {{-- ===== FORM SIDE ===== --}}
            <div class="form-side flex-1 flex flex-col items-center justify-center px-6 py-12 relative overflow-hidden">

                {{-- Floating decorative orbs --}}
                <div class="wt-orb" style="width:120px;height:120px;top:14%;left:10%;background:rgba(59,130,246,0.10);"></div>
                <div class="wt-orb" style="width:80px;height:80px;bottom:18%;left:22%;background:rgba(6,182,212,0.10);"></div>
                <div class="wt-orb" style="width:60px;height:60px;top:24%;right:14%;background:rgba(99,102,241,0.10);"></div>

                {{-- Mobile logo (only visible on small screens) --}}
                <div class="lg:hidden mb-8 text-center relative z-10">
                    <img src="{{ asset('logo.png') }}" alt="GTrack" class="gs-mlogo w-16 h-16 mx-auto mb-3 drop-shadow-sm">
                    <h1 class="brand-wordmark text-gray-900 text-3xl">GTrack</h1>
                    <p class="text-gray-400 text-sm mt-0.5 font-medium">GCash Transaction Manager</p>
                </div>

                {{-- Card --}}
                <div class="form-card w-full max-w-sm relative z-10">
                    {{ $slot }}
                </div>

                <p class="mt-8 text-gray-400 text-xs relative z-10">© {{ date('Y') }} GTrack by Tristan Reboredo</p>
            </div>

            {{-- ===== BRANDING SIDE (hidden on mobile) ===== --}}
            <div class="brand-panel gs-brand hidden lg:flex lg:w-[48%] relative items-center justify-center overflow-hidden">

                {{-- Decorative rings --}}
                <div class="ring-deco gs-ring" style="width:520px;height:520px;top:-140px;right:-120px;"></div>
                <div class="ring-deco gs-ring" style="width:360px;height:360px;bottom:-100px;left:60px;"></div>
                <div class="ring-deco gs-ring" style="width:200px;height:200px;top:30%;right:18%;"></div>

                <div class="relative z-10 text-center px-12">
                    <div class="relative inline-block mb-6">
                        <div class="logo-glow gs-glow"></div>
                        <img src="{{ asset('logo.png') }}" alt="GTrack" class="gs-logo w-28 h-28 mx-auto drop-shadow-lg relative z-10">
                    </div>
                    <h2 class="gs-btext brand-wordmark text-white text-5xl mb-3">GTrack</h2>
                    <div class="gs-btext brand-accent mx-auto mb-5"></div>
                    <p class="gs-btext text-blue-100/90 text-base leading-relaxed max-w-xs mx-auto">
                        Manage your GCash cash-in and cash-out transactions in one place.
                    </p>

                    {{-- Mini feature points --}}
                    <div class="mt-10 space-y-1.5 text-left max-w-xs mx-auto">
                        <div class="gs-feat feat-item">
                            <span class="feat-check">✓</span>
                            <span class="text-sm font-medium">Real-time balance tracking</span>
                        </div>
                        <div class="gs-feat feat-item">
                            <span class="feat-check">✓</span>
                            <span class="text-sm font-medium">Automatic service charge computation</span>
                        </div>
                        <div class="gs-feat feat-item">
                            <span class="feat-check">✓</span>
                            <span class="text-sm font-medium">End-of-day reconciliation reports</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== Animations ===== --}}
        <script>
            (function () {
                var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (typeof gsap === 'undefined' || reduce) {
                    // No GSAP or user prefers reduced motion — just show everything
                    gsap && gsap.set ? gsap.set('body', { opacity: 1 }) : (document.body.style.opacity = 1);
                    return;
                }

                gsap.set('body', { opacity: 1 });

                var tl = gsap.timeline({ defaults: { ease: 'power3.out', duration: 0.8 } });

                // Blue panel slides in
                tl.from('.gs-brand', { xPercent: 12, opacity: 0, duration: 1.0 }, 0);

                // Rings scale up
                tl.from('.gs-ring', { scale: 0.6, opacity: 0, transformOrigin: 'center', stagger: 0.12, duration: 0.9 }, 0.2);

                // Logo pops with a soft bounce
                tl.from('.gs-logo', { scale: 0.4, opacity: 0, rotation: -12, ease: 'back.out(1.7)', duration: 0.9 }, 0.35);
                tl.from('.gs-glow', { scale: 0, opacity: 0, duration: 0.9 }, 0.4);

                // Brand text + features
                tl.from('.gs-btext', { y: 24, opacity: 0, stagger: 0.12 }, 0.55);
                tl.from('.gs-feat', { x: -18, opacity: 0, stagger: 0.12 }, 0.75);

                // Mobile logo (small screens)
                tl.from('.gs-mlogo', { scale: 0.5, opacity: 0, ease: 'back.out(1.7)' }, 0.2);

                // Form card contents stagger up
                tl.from('.form-card > *', { y: 22, opacity: 0, stagger: 0.1 }, 0.45);

                // Decorative orbs fade in
                tl.from('.wt-orb', { scale: 0, opacity: 0, stagger: 0.1, duration: 0.7 }, 0.3);

                // ===== Continuous floating loops =====
                gsap.to('.gs-ring', {
                    y: '+=16', duration: 6, ease: 'sine.inOut',
                    repeat: -1, yoyo: true, stagger: { each: 1.5, from: 'random' }
                });

                gsap.to('.wt-orb', {
                    y: '+=20', duration: 5, ease: 'sine.inOut',
                    repeat: -1, yoyo: true, stagger: { each: 1.2, from: 'random' }
                });

                // Gentle logo breathing
                gsap.to('.gs-logo', {
                    y: '-=8', duration: 3, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: 1.5
                });
            })();
        </script>
    </body>
</html>
