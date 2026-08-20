<!-- "Perfect Match Neural Pulse" preloader — see css/loading-screen.css and js/loading-screen.js.
     If JavaScript is disabled, the noscript rule below hides it immediately so it can never
     block the site (dismissal is JS-driven, so without JS it would otherwise show forever). -->
<?php
// Post-login flag is set once in login.php and consumed here so the longer, more
// noticeable display only happens on the page the user lands on right after signing in.
$showLoginLoadingScreen = !empty($_SESSION['show_login_loading_screen']);
unset($_SESSION['show_login_loading_screen']);
?>
<noscript><style>#loading-screen{display:none!important;}</style></noscript>
<div id="loading-screen" class="loading-screen" role="status"<?= $showLoginLoadingScreen ? ' data-min-display="3000"' : '' ?>>
    <canvas id="loading-particles" class="loading-particles" aria-hidden="true"></canvas>

    <div class="loading-core">
        <div class="loading-aura" aria-hidden="true"></div>

        <svg class="loading-ring" viewBox="0 0 200 200" aria-hidden="true">
            <circle class="loading-ring-track" cx="100" cy="100" r="92"/>
            <circle class="loading-ring-sweep" cx="100" cy="100" r="92"/>
        </svg>

        <svg class="loading-glyph" viewBox="0 0 200 200" aria-hidden="true">
            <defs>
                <linearGradient id="loading-glyph-gradient" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#3b82f6"/>
                    <stop offset="1" stop-color="#22d3ee"/>
                </linearGradient>
            </defs>
            <path class="loading-glyph-path" d="M70 70 L130 130"/>
            <circle class="loading-glyph-path" cx="70" cy="70" r="40"/>
            <circle class="loading-glyph-path" cx="130" cy="130" r="40"/>
        </svg>

        <img src="<?= h(base_url('img/logo.png')) ?>" alt="" class="loading-logo" aria-hidden="true">
    </div>

    <div class="loading-percent" id="loading-percent" aria-hidden="true">0%</div>
    <div class="loading-status" id="loading-status">Scanning verified profiles&hellip;</div>
</div>
<script src="<?= h(base_url('js/loading-screen.js')) ?>"></script>
