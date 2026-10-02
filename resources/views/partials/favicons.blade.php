{{-- Favicon + PWA / Add-to-Home-Screen icons (dipakai di layout & login) --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/icon-32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/icon-16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/icon-180.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" content="#5B5EF6">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="SevenKey">

{{-- Auto-fullscreen: browser memblokir fullscreen saat load, jadi dipicu pada
     interaksi pertama (klik/tap/tombol). Dilewati bila sudah berjalan sebagai
     PWA/home-screen (Android display:fullscreen, iOS standalone) yang memang
     sudah tanpa bar browser. --}}
<script>
(function () {
    var isApp = (window.matchMedia && (
            window.matchMedia('(display-mode: fullscreen)').matches ||
            window.matchMedia('(display-mode: standalone)').matches
        )) || window.navigator.standalone === true;
    if (isApp) return; // sudah fullscreen sebagai app

    function goFullscreen() {
        var el = document.documentElement;
        var req = el.requestFullscreen || el.webkitRequestFullscreen ||
                  el.mozRequestFullScreen || el.msRequestFullscreen;
        var active = document.fullscreenElement || document.webkitFullscreenElement;
        if (req && !active) {
            try { var p = req.call(el); if (p && p.catch) p.catch(function () {}); } catch (e) {}
        }
        cleanup();
    }
    function cleanup() {
        ['pointerdown', 'touchend', 'keydown'].forEach(function (ev) {
            document.removeEventListener(ev, goFullscreen, true);
        });
    }
    // Picu pada gesture pertama (pointer/tap/keyboard). iOS Safari biasa tidak
    // mengizinkan fullscreen web — di sana gunakan "Add to Home Screen".
    ['pointerdown', 'touchend', 'keydown'].forEach(function (ev) {
        document.addEventListener(ev, goFullscreen, { capture: true, once: false });
    });
})();
</script>
