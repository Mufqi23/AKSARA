<div class="aksara-loader" id="aksaraPageLoader" aria-hidden="true">
    <div class="aksara-loader__box">
        <svg xmlns="http://www.w3.org/2000/svg" height="200px" width="200px" viewBox="0 0 200 200" class="pencil" focusable="false">
            <defs>
                <clipPath id="pencil-eraser">
                    <rect height="30" width="30" ry="5" rx="5"></rect>
                </clipPath>
            </defs>
            <circle transform="rotate(-113,100,100)" stroke-linecap="round" stroke-dashoffset="439.82" stroke-dasharray="439.82 439.82" stroke-width="2" stroke="currentColor" fill="none" r="70" class="pencil__stroke"></circle>
            <g transform="translate(100,100)" class="pencil__rotate">
                <g fill="none">
                    <circle transform="rotate(-90)" stroke-dashoffset="402" stroke-dasharray="402.12 402.12" stroke-width="30" stroke="#2b6cb0" r="64" class="pencil__body1"></circle>
                    <circle transform="rotate(-90)" stroke-dashoffset="465" stroke-dasharray="464.96 464.96" stroke-width="10" stroke="#3b82c4" r="74" class="pencil__body2"></circle>
                    <circle transform="rotate(-90)" stroke-dashoffset="339" stroke-dasharray="339.29 339.29" stroke-width="10" stroke="#1a2842" r="54" class="pencil__body3"></circle>
                </g>
                <g transform="rotate(-90) translate(49,0)" class="pencil__eraser">
                    <g class="pencil__eraser-skew">
                        <rect height="30" width="30" ry="5" rx="5" fill="#5ea8ef"></rect>
                        <rect clip-path="url(#pencil-eraser)" height="30" width="5" fill="#2b6cb0"></rect>
                        <rect height="20" width="30" fill="#e2e8f0"></rect>
                        <rect height="20" width="15" fill="#cbd5e1"></rect>
                        <rect height="20" width="5" fill="#94a3b8"></rect>
                        <rect height="2" width="30" y="6" fill="rgba(26, 40, 66, 0.22)"></rect>
                        <rect height="2" width="30" y="13" fill="rgba(26, 40, 66, 0.22)"></rect>
                    </g>
                </g>
                <g transform="rotate(-90) translate(49,-30)" class="pencil__point">
                    <polygon points="15 0,30 30,0 30" fill="#f3b664"></polygon>
                    <polygon points="15 0,6 30,0 30" fill="#d98a2b"></polygon>
                    <polygon points="15 0,20 10,10 10" fill="#1a2842"></polygon>
                </g>
            </g>
        </svg>
    </div>
    <span class="aksara-loader__label">AKSARA</span>
</div>
<script>
    (function () {
        var loader = document.getElementById('aksaraPageLoader');
        if (!loader) return;
        function hideLoader() {
            loader.classList.add('is-loaded');
        }
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(hideLoader, 420);
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(hideLoader, 420);
            }, { once: true });
        }
        setTimeout(hideLoader, 700);
    })();
</script>

