{{-- ============================================================
     Global loading state — top progress bar + button spinners
     Pure vanilla JS/CSS, no dependencies. Works on every page.
     Include with: @include('partials.loader')
     ============================================================ --}}

{{-- Top progress bar --}}
<div id="gt-progress" aria-hidden="true"></div>

<style>
    #gt-progress {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        width: 0%;
        opacity: 0;
        z-index: 9999;
        background: linear-gradient(90deg, #0070FF 0%, #00C2FF 100%);
        box-shadow: 0 0 10px rgba(0, 145, 255, 0.6);
        transition: width 0.2s ease, opacity 0.3s ease;
        pointer-events: none;
    }

    /* Button spinner */
    .gt-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: gt-spin 0.6s linear infinite;
        vertical-align: -2px;
        margin-right: 7px;
    }
    @keyframes gt-spin { to { transform: rotate(360deg); } }

    @media (prefers-reduced-motion: reduce) {
        #gt-progress { transition: opacity 0.2s ease; }
        .gt-spinner { animation-duration: 1.2s; }
    }
</style>

<script>
    (function () {
        var bar = document.getElementById('gt-progress');
        var timer = null;
        var width = 0;

        function start() {
            if (timer) return;            // already running
            width = 8;
            bar.style.opacity = '1';
            bar.style.width = width + '%';
            timer = setInterval(function () {
                width += (90 - width) * 0.10;   // ease toward 90%
                bar.style.width = width + '%';
                if (width > 89) { clearInterval(timer); timer = null; }
            }, 200);
        }

        function reset() {
            if (timer) { clearInterval(timer); timer = null; }
            width = 0;
            bar.style.opacity = '0';
            bar.style.width = '0%';
        }

        // Start when navigating via internal links
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;            // anchors
            if (a.target === '_blank' || a.hasAttribute('download')) return;
            if (href.indexOf('javascript:') === 0) return;
            if (a.origin && a.origin !== location.origin) return;   // external
            start();
        });

        // Start on form submit + show button spinner
        document.addEventListener('submit', function (e) {
            start();
            var form = e.target;
            var btn = form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
            if (btn && !btn.dataset.gtLoading) {
                btn.dataset.gtLoading = '1';
                btn.dataset.gtOriginal = btn.innerHTML;
                btn.disabled = true;
                btn.style.opacity = '0.85';
                btn.style.cursor = 'wait';
                btn.innerHTML = '<span class="gt-spinner"></span>Please wait...';
            }
        });

        // New page (or back/forward restore) → hide the bar cleanly
        window.addEventListener('pageshow', reset);
    })();
</script>
