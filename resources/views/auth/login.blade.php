<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SevenKey ERP</title>
    @include('partials.favicons')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root{
            --primary:#E5E336; --primary-2:#E5E336; --accent:#E5E336;
            --text:#111827; --muted:#6B7280;
            --ring:rgba(229,227,54,.30);
        }
        *{ box-sizing:border-box; }
        html,body{ height:100%; }
        body{
            margin:0; font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
            color:var(--text);
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:28px; position:relative; overflow:hidden;
            -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility;
            background:#F1F4FF;
        }

        /* ===================== BACKGROUND — foto brand ===================== */
        /* Desktop & tablet: landscape. Mobile: potrait (lihat media query di bawah). */
        .bg-base{
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background:#0b1020 url("{{ asset('bglandscape.png') }}") center center / cover no-repeat;
            transition:filter .7s ease, transform .8s cubic-bezier(.22,1,.36,1);
        }
        /* Sebelum diketuk: background terang & tajam (tidak gelap).
           Saat form muncul: background di-blur ringan — TETAP terang, tidak digelapkan. */
        body.revealed-bg .bg-base{ filter:blur(10px); transform:scale(1.06); }
        /* floating soft light orbs (very subtle, desaturated) */
        .orb{ position:fixed; z-index:0; border-radius:50%; pointer-events:none; filter:blur(80px); will-change:transform; }
        .orb.o1{ width:520px;height:520px; left:-140px; top:-160px; background:radial-gradient(circle,rgba(150,160,255,.45),transparent 70%); opacity:.7; animation:drift1 22s ease-in-out infinite; }
        .orb.o2{ width:460px;height:460px; right:-120px; top:-120px; background:radial-gradient(circle,rgba(160,190,255,.40),transparent 70%); opacity:.65; animation:drift2 26s ease-in-out infinite; }
        .orb.o3{ width:560px;height:560px; right:-140px; bottom:-200px; background:radial-gradient(circle,rgba(175,165,255,.38),transparent 70%); opacity:.6; animation:drift1 30s ease-in-out infinite; }
        @keyframes drift1{ 0%,100%{transform:translate3d(0,0,0)} 50%{transform:translate3d(26px,22px,0)} }
        @keyframes drift2{ 0%,100%{transform:translate3d(0,0,0)} 50%{transform:translate3d(-22px,18px,0)} }
        /* faint dot grid */
        .bg-dots{
            position:fixed; inset:0; z-index:0; pointer-events:none; opacity:.45;
            background-image:radial-gradient(rgba(200,198,30,.08) 1px, transparent 1.4px);
            background-size:26px 26px;
            -webkit-mask-image:radial-gradient(ellipse 62% 55% at 50% 48%,#000 25%,transparent 72%);
                    mask-image:radial-gradient(ellipse 62% 55% at 50% 48%,#000 25%,transparent 72%);
        }
        /* subtle vignette to focus center */
        .bg-vignette{
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background:radial-gradient(120% 100% at 50% 50%, transparent 55%, rgba(60,70,120,.10) 100%);
        }
        /* fine grain */
        .bg-grain{
            position:fixed; inset:0; z-index:1; pointer-events:none; opacity:.04; mix-blend-mode:soft-light;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* ===================== STAGE ===================== */
        /* Disembunyikan dulu; muncul dari tengah saat layar diketuk. */
        .stage{
            position:relative; z-index:2; width:100%; max-width:498px; perspective:1400px;
            opacity:0; transform:scale(.8); pointer-events:none;
            transition:opacity .6s ease, transform .8s cubic-bezier(.22,1,.36,1);
        }
        .stage.revealed{ opacity:1; transform:scale(1); pointer-events:auto; }

        /* Petunjuk "ketuk untuk masuk" (tampil sebelum form muncul) */
        .tap-hint{
            position:fixed; inset:0; z-index:3; display:flex; flex-direction:column;
            align-items:center; justify-content:center; gap:18px; text-align:center; padding:24px;
            cursor:pointer; transition:opacity .55s ease, visibility .55s;
        }
        .tap-hint.hide{ opacity:0; visibility:hidden; pointer-events:none; }
        .tap-hint .ring{
            width:84px; height:84px; border-radius:50%; display:flex; align-items:center; justify-content:center;
            color:#fff; background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.4);
            backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);
            animation:hintPulse 2.1s ease-in-out infinite;
        }
        .tap-hint p{ margin:0; color:#fff; font-size:16px; font-weight:700; letter-spacing:.3px; text-shadow:0 2px 10px rgba(0,0,0,.55); }
        .tap-hint small{ color:rgba(255,255,255,.78); font-size:12.5px; letter-spacing:.4px; text-shadow:0 2px 8px rgba(0,0,0,.5); }
        @keyframes hintPulse{
            0%,100%{ transform:scale(1); box-shadow:0 0 0 0 rgba(255,255,255,.28); }
            50%{ transform:scale(1.09); box-shadow:0 0 0 18px rgba(255,255,255,0); }
        }

        /* ===================== LOGO / BRAND ===================== */
        .brand{ text-align:center; margin-bottom:30px; padding-top:4px; animation:fade .9s ease both; }
        .logo-badge{
            position:relative; width:64px; height:64px; margin:0 auto 22px; border-radius:18px;
            display:flex; align-items:center; justify-content:center; overflow:hidden;
            background:#fff;
            box-shadow:0 10px 28px -8px rgba(200,198,30,.7), inset 0 1px 0 rgba(255,255,255,.45);
        }
        .logo-badge img{ width:100%; height:100%; object-fit:cover; border-radius:18px; }
        .logo-badge::after{ /* glow behind icon */
            content:""; position:absolute; inset:-30%; z-index:-1; border-radius:50%;
            background:radial-gradient(circle,rgba(200,198,30,.45),transparent 70%); filter:blur(20px);
        }
        .brand h1{ margin:0; font-size:31px; font-weight:700; letter-spacing:-1px; line-height:1.05; color:#F6F7FB; }
        .brand h1 b{ font-weight:800; color:var(--primary); }
        .brand p{ margin:10px 0 0; font-size:13.5px; font-weight:450; color:rgba(233,233,237,.62); letter-spacing:.2px; }
        .badge{
            position:relative; display:inline-flex; align-items:center; gap:7px; margin-top:18px;
            padding:8px 15px; border-radius:999px; font-size:11.5px; font-weight:600; letter-spacing:.2px;
            color:var(--primary); background:rgba(255,255,255,.07);
            backdrop-filter:blur(12px) saturate(140%); -webkit-backdrop-filter:blur(12px) saturate(140%);
            box-shadow:0 6px 18px -8px rgba(200,198,30,.35);
        }
        .badge::before{ /* gradient border ring */
            content:""; position:absolute; inset:0; border-radius:999px; padding:1px; pointer-events:none;
            background:linear-gradient(135deg, rgba(200,198,30,.7), rgba(230,228,90,.15));
            -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite:xor; mask-composite:exclude;
        }
        .badge svg{ opacity:.9; }

        /* ===================== GLASS CARD ===================== */
        .card{
            position:relative; border-radius:32px; padding:40px 38px 34px;
            background:linear-gradient(160deg, rgba(28,28,32,.74), rgba(16,16,20,.64));
            border:1px solid rgba(255,255,255,.14);
            backdrop-filter:blur(30px) saturate(150%); -webkit-backdrop-filter:blur(30px) saturate(150%);
            box-shadow:
                0 1px 2px rgba(0,0,0,.20),
                0 14px 30px -10px rgba(0,0,0,.32),
                0 44px 90px -28px rgba(0,0,0,.48),
                inset 0 1px 0 rgba(255,255,255,.12),
                inset 0 0 0 1px rgba(255,255,255,.05);
            transform-style:preserve-3d; transition:transform .25s cubic-bezier(.22,1,.36,1);
            animation:cardUp .9s cubic-bezier(.22,1,.36,1) both; animation-delay:.1s;
        }
        .card::before{ /* top sheen highlight */
            content:""; position:absolute; inset:0; border-radius:32px; pointer-events:none;
            background:linear-gradient(160deg, rgba(255,255,255,.10), rgba(255,255,255,0) 34%);
        }

        .alert{ position:relative; margin-bottom:20px; padding:12px 15px; border-radius:14px; font-size:13px; }
        .alert-error{ background:rgba(254,226,226,.7); border:1px solid rgba(248,113,113,.4); color:#b42318; }
        .alert-ok{ background:rgba(220,252,231,.7); border:1px solid rgba(134,239,172,.5); color:#15803d; }

        /* ===================== FIELDS ===================== */
        .field{ margin-bottom:20px; }
        .field label{ display:block; font-size:13px; font-weight:600; color:#E9E9ED; margin-bottom:9px; letter-spacing:.1px; }
        .input-wrap{ position:relative; }
        .input-wrap .ic{ position:absolute; left:18px; top:50%; transform:translateY(-50%); color:rgba(233,233,237,.5); pointer-events:none; transition:color .25s; }
        .input-wrap .eye{ position:absolute; right:15px; top:50%; transform:translateY(-50%); color:rgba(233,233,237,.5); cursor:pointer; background:none; border:0; padding:5px; display:flex; transition:color .2s; }
        .input-wrap .eye:hover{ color:var(--primary); }
        .input-wrap input{
            width:100%; height:58px; border-radius:18px; padding:0 18px 0 50px;
            font-size:15px; color:#F3F4F6; font-family:inherit; font-weight:450;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.14);
            box-shadow:inset 0 1px 2px rgba(0,0,0,.15);
            outline:none; transition:border-color .25s, box-shadow .25s, background .25s;
        }
        .input-wrap input::placeholder{ color:rgba(233,233,237,.42); font-weight:450; }
        .input-wrap input:hover{ background:rgba(255,255,255,.10); border-color:rgba(255,255,255,.22); }
        .input-wrap input:focus{
            background:rgba(255,255,255,.12); border-color:var(--primary);
            box-shadow:0 0 0 4px var(--ring), inset 0 1px 2px rgba(0,0,0,.12);
        }
        .input-wrap input:focus ~ .ic{ color:var(--primary); }
        .input-wrap.invalid input{ border-color:#f87171; box-shadow:0 0 0 4px rgba(248,113,113,.14); }
        .err{ color:#dc2626; font-size:12px; margin:7px 2px 0; }

        .row{ display:flex; align-items:center; justify-content:space-between; margin:4px 2px 26px; }
        .check{ display:flex; align-items:center; gap:9px; font-size:13.5px; color:rgba(233,233,237,.74); cursor:pointer; user-select:none; }
        .check input{ width:18px; height:18px; border-radius:6px; accent-color:var(--primary); cursor:pointer; }
        .link{ font-size:13.5px; font-weight:600; color:var(--primary); text-decoration:none; transition:opacity .2s; }
        .link:hover{ text-decoration:underline; opacity:.85; }

        /* ===================== BUTTON ===================== */
        .btn{
            position:relative; width:100%; height:58px; border:0; border-radius:18px; cursor:pointer;
            font-family:inherit; font-size:15px; font-weight:800; color:#1a1a12; letter-spacing:.2px;
            display:flex; align-items:center; justify-content:center; gap:10px; overflow:hidden;
            background:linear-gradient(135deg,#E5E336,#D8D62E);
            box-shadow:0 10px 24px -8px rgba(229,227,54,.6), 0 2px 6px rgba(180,178,20,.3), inset 0 1px 0 rgba(255,255,255,.5);
            transition:transform .3s ease, box-shadow .3s ease;
        }
        .btn::after{ /* sheen sweep on hover */
            content:""; position:absolute; top:0; left:-60%; width:40%; height:100%;
            background:linear-gradient(100deg, transparent, rgba(255,255,255,.35), transparent);
            transform:skewX(-18deg); transition:left .6s ease;
        }
        .btn:hover{ transform:translateY(-2px); box-shadow:0 18px 38px -8px rgba(229,227,54,.7), 0 4px 12px rgba(180,178,20,.4), inset 0 1px 0 rgba(255,255,255,.55); }
        .btn:hover::after{ left:120%; }
        .btn:active{ transform:translateY(1px); box-shadow:0 8px 18px -8px rgba(180,178,20,.55); }
        .btn .arrow{ transition:transform .3s ease; }
        .btn:hover .arrow{ transform:translateX(4px); }

        /* ===================== DIVIDER ===================== */
        .divider{ display:flex; align-items:center; gap:14px; margin:24px 0 2px; }
        .divider::before,.divider::after{ content:""; flex:1; height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,.16),transparent); }
        .divider span{ font-size:11px; font-weight:600; letter-spacing:1.4px; text-transform:uppercase; color:rgba(233,233,237,.5); display:inline-flex; align-items:center; gap:6px; }

        /* ===================== FOOTER ===================== */
        .foot{ text-align:center; margin-top:26px; font-size:12px; color:rgba(255,255,255,.88); text-shadow:0 1px 4px rgba(0,0,0,.45); animation:fade .9s ease both; animation-delay:.8s; }
        .foot .lc{ display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:9px; }
        .foot a{ color:rgba(255,255,255,.88); text-decoration:none; transition:color .2s; }
        .foot a:hover{ color:#fff; }
        .foot .dot{ opacity:.35; }
        .foot .status{ display:inline-flex; align-items:center; gap:5px; }
        .foot .status i{ width:7px; height:7px; border-radius:50%; background:#22c55e; display:inline-block; box-shadow:0 0 0 3px rgba(34,197,94,.16); }

        /* ===================== ANIMATIONS ===================== */
        @keyframes fade{ from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:translateY(0)} }
        @keyframes cardUp{ from{opacity:0; transform:translateY(22px) scale(.97)} to{opacity:1; transform:translateY(0) scale(1)} }
        @keyframes slideIn{ from{opacity:0; transform:translateY(12px)} to{opacity:1; transform:translateY(0)} }
        .stagger{ opacity:0; animation:slideIn .65s cubic-bezier(.22,1,.36,1) both; }
        .d1{ animation-delay:.34s } .d2{ animation-delay:.44s } .d3{ animation-delay:.54s } .d4{ animation-delay:.64s } .d5{ animation-delay:.74s }

        /* Mobile → background potrait */
        @media (max-width:768px){
            .bg-base{ background-image:url("{{ asset('bgpotrait.png') }}"); }
        }
        /* ===== Mobile: padatkan form agar proporsional (tidak terlalu panjang) ===== */
        @media (max-width:600px){
            body{ padding:16px; }
            .stage{ max-width:430px; }
            .brand{ margin-bottom:16px; }
            .logo-badge{ width:52px; height:52px; margin-bottom:12px; border-radius:15px; }
            .brand h1{ font-size:24px; }
            .brand p{ font-size:12.5px; margin-top:6px; }
            .badge{ margin-top:11px; padding:6px 12px; font-size:11px; }
            .card{ padding:24px 20px 20px; border-radius:26px; }
            .field{ margin-bottom:13px; }
            .field label{ margin-bottom:6px; font-size:12.5px; }
            .input-wrap input{ height:50px; border-radius:14px; font-size:14.5px; padding:0 16px 0 46px; }
            .input-wrap .ic{ left:15px; }
            .row{ margin:2px 2px 16px; }
            .btn{ height:52px; border-radius:14px; font-size:14.5px; }
            .divider{ margin:16px 0 0; }
            .foot{ margin-top:16px; }
        }
        /* HP kecil: lebih padat lagi + sembunyikan badge biar ringkas */
        @media (max-width:380px){
            .badge{ display:none; }
            .brand{ margin-bottom:12px; }
            .input-wrap input{ height:47px; }
            .btn{ height:49px; }
            .field{ margin-bottom:11px; }
        }
        @media (prefers-reduced-motion:reduce){
            *{ animation:none !important; transition:none !important; }
            .card{ transform:none !important; }
        }
    </style>
</head>
<body>
    <div class="bg-base"></div>
    <div class="bg-grain"></div>

    {{-- Petunjuk ketuk (hilang saat layar diketuk) --}}
    <div class="tap-hint" id="tapHint">
        <div class="ring">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M9 11V6a2 2 0 0 1 4 0v5"/><path d="M13 11V4a2 2 0 0 1 4 0v9"/>
                <path d="M17 11a2 2 0 0 1 4 0v3a7 7 0 0 1-7 7h-2a7 7 0 0 1-6-4l-2.5-4a2 2 0 0 1 3.4-2.1L9 13"/>
            </svg>
        </div>
        <p>Ketuk untuk masuk</p>
        <small>Tap anywhere to continue</small>
    </div>

    <div class="stage">

        {{-- Card --}}
        <div class="card" id="card">

            {{-- Logo --}}
            <div class="brand">
                <div class="logo-badge"><img src="{{ asset('icons/logo.png') }}" alt="SevenKey"></div>
                <h1>SevenKey <b>ERP</b></h1>
                <p>Fashion Retail Management Platform</p>
                <span class="badge">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-4z"/></svg>
                    Enterprise Cloud ERP
                </span>
            </div>

            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if(session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field stagger d1">
                    <label for="email">Email</label>
                    <div class="input-wrap @error('email') invalid @enderror">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               autocomplete="username" placeholder="nama@perusahaan.com">
                        <span class="ic">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        </span>
                    </div>
                    @error('email')<p class="err">{{ $message }}</p>@enderror
                </div>

                <div class="field stagger d2">
                    <label for="password">Kata Sandi</label>
                    <div class="input-wrap @error('password') invalid @enderror">
                        <input id="password" type="password" name="password" required
                               autocomplete="current-password" placeholder="Masukkan kata sandi Anda">
                        <span class="ic">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        </span>
                        <button type="button" class="eye" onclick="togglePw()" aria-label="Lihat sandi">
                            <svg id="eyeIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="err">{{ $message }}</p>@enderror
                </div>

                <div class="row stagger d3">
                    <label class="check">
                        <input type="checkbox" name="remember"> Ingat saya
                    </label>
                    @if(Route::has('password.request'))
                        <a class="link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                    @endif
                </div>

                <button type="submit" class="btn stagger d4">
                    Masuk
                    <span class="arrow"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </button>

                <div class="divider stagger d5">
                    <span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-4z"/></svg>
                        Login Aman
                    </span>
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="foot">
            <div class="lc">
                <span>© {{ date('Y') }} SevenKey ERP</span>
                <span class="dot">•</span>
                <span class="status"><i></i>Status</span>
                <span class="dot">•</span>
                <a href="#">Privacy</a>
                <span class="dot">•</span>
                <a href="#">Terms</a>
                <span class="dot">•</span>
                <span>v2.4.1</span>
            </div>
        </div>
    </div>

    <script>
        function togglePw(){
            var p=document.getElementById('password'); var i=document.getElementById('eyeIcon');
            if(p.type==='password'){ p.type='text'; i.innerHTML='<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.2M6.6 6.6A18 18 0 0 0 2 11s3.5 7 10 7a10.8 10.8 0 0 0 2.1-.2"/>'; }
            else{ p.type='password'; i.innerHTML='<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>'; }
        }

        // Reveal: form muncul dari tengah saat layar diketuk/klik
        (function(){
            var stage=document.querySelector('.stage');
            var hint=document.getElementById('tapHint');
            var done=false;
            function reveal(){
                if(done) return; done=true;
                document.body.classList.add('revealed-bg'); // background nge-blur (tidak gelap)
                stage.classList.add('revealed');
                if(hint) hint.classList.add('hide');
                setTimeout(function(){ var em=document.getElementById('email'); if(em && window.innerWidth>700) em.focus(); }, 560);
                ['pointerdown','keydown','touchend'].forEach(function(ev){ document.removeEventListener(ev, reveal, true); });
            }
            // Jika baru submit & ada error/notif, langsung tampilkan form.
            var hasMsg = {{ ($errors->any() || session('error') || session('status')) ? 'true' : 'false' }};
            if(hasMsg){ reveal(); return; }
            ['pointerdown','keydown','touchend'].forEach(function(ev){ document.addEventListener(ev, reveal, { capture:true }); });
        })();

        // Tilt halus mengikuti mouse (maks 2°)
        (function(){
            var card=document.getElementById('card');
            if(!card || window.matchMedia('(prefers-reduced-motion:reduce)').matches) return;
            var raf;
            window.addEventListener('mousemove',function(e){
                if(window.innerWidth<700) return;
                cancelAnimationFrame(raf);
                raf=requestAnimationFrame(function(){
                    var cx=window.innerWidth/2, cy=window.innerHeight/2;
                    var rx=((e.clientY-cy)/cy)*-2, ry=((e.clientX-cx)/cx)*2;
                    card.style.transform='rotateX('+rx.toFixed(2)+'deg) rotateY('+ry.toFixed(2)+'deg)';
                });
            });
            window.addEventListener('mouseleave',function(){ card.style.transform='rotateX(0) rotateY(0)'; });
        })();
    </script>
</body>
</html>
