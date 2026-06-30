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
            --primary:#5B5EF6; --primary-2:#7D82FF; --accent:#7078FF;
            --text:#111827; --muted:#6B7280;
            --ring:rgba(91,94,246,.16);
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

        /* ===================== BACKGROUND — multi-layer light ===================== */
        .bg{ position:fixed; inset:0; z-index:0; pointer-events:none; }
        /* base wash + soft color light */
        .bg-base{
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background:
                radial-gradient(120% 90% at 50% -10%, #FFFFFF 0%, rgba(255,255,255,0) 42%),
                radial-gradient(70% 60% at 12% 12%, rgba(123,130,255,.20), transparent 60%),
                radial-gradient(70% 60% at 88% 8%,  rgba(120,170,255,.18), transparent 60%),
                radial-gradient(80% 70% at 85% 96%, rgba(140,150,255,.18), transparent 62%),
                radial-gradient(80% 70% at 10% 92%, rgba(150,180,255,.14), transparent 60%),
                linear-gradient(180deg,#F8F9FF 0%,#E8EEFF 55%,#EEF2FF 100%);
        }
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
            background-image:radial-gradient(rgba(91,94,246,.08) 1px, transparent 1.4px);
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
        .stage{ position:relative; z-index:2; width:100%; max-width:498px; perspective:1400px; }

        /* ===================== LOGO / BRAND ===================== */
        .brand{ text-align:center; margin-bottom:30px; padding-top:4px; animation:fade .9s ease both; }
        .logo-badge{
            position:relative; width:64px; height:64px; margin:0 auto 22px; border-radius:18px;
            display:flex; align-items:center; justify-content:center; overflow:hidden;
            background:#fff;
            box-shadow:0 10px 28px -8px rgba(91,94,246,.7), inset 0 1px 0 rgba(255,255,255,.45);
        }
        .logo-badge img{ width:100%; height:100%; object-fit:cover; border-radius:18px; }
        .logo-badge::after{ /* glow behind icon */
            content:""; position:absolute; inset:-30%; z-index:-1; border-radius:50%;
            background:radial-gradient(circle,rgba(91,94,246,.45),transparent 70%); filter:blur(20px);
        }
        .brand h1{ margin:0; font-size:31px; font-weight:700; letter-spacing:-1px; line-height:1.05; color:#0F172A; }
        .brand h1 b{ font-weight:800; background:linear-gradient(120deg,var(--primary),var(--primary-2)); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .brand p{ margin:10px 0 0; font-size:13.5px; font-weight:450; color:var(--muted); letter-spacing:.2px; }
        .badge{
            position:relative; display:inline-flex; align-items:center; gap:7px; margin-top:18px;
            padding:8px 15px; border-radius:999px; font-size:11.5px; font-weight:600; letter-spacing:.2px;
            color:var(--primary); background:rgba(255,255,255,.45);
            backdrop-filter:blur(12px) saturate(140%); -webkit-backdrop-filter:blur(12px) saturate(140%);
            box-shadow:0 6px 18px -8px rgba(91,94,246,.35);
        }
        .badge::before{ /* gradient border ring */
            content:""; position:absolute; inset:0; border-radius:999px; padding:1px; pointer-events:none;
            background:linear-gradient(135deg, rgba(91,94,246,.7), rgba(125,130,255,.15));
            -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite:xor; mask-composite:exclude;
        }
        .badge svg{ opacity:.9; }

        /* ===================== GLASS CARD ===================== */
        .card{
            position:relative; border-radius:32px; padding:40px 38px 34px;
            background:linear-gradient(160deg, rgba(255,255,255,.62), rgba(255,255,255,.42));
            border:1px solid rgba(255,255,255,.5);
            backdrop-filter:blur(30px) saturate(150%); -webkit-backdrop-filter:blur(30px) saturate(150%);
            box-shadow:
                0 1px 2px rgba(16,24,40,.04),
                0 14px 30px -10px rgba(60,70,120,.16),
                0 44px 90px -28px rgba(50,60,110,.30),
                inset 0 1px 0 rgba(255,255,255,.9),
                inset 0 0 0 1px rgba(255,255,255,.18);
            transform-style:preserve-3d; transition:transform .25s cubic-bezier(.22,1,.36,1);
            animation:cardUp .9s cubic-bezier(.22,1,.36,1) both; animation-delay:.1s;
        }
        .card::before{ /* top sheen highlight */
            content:""; position:absolute; inset:0; border-radius:32px; pointer-events:none;
            background:linear-gradient(160deg, rgba(255,255,255,.55), rgba(255,255,255,0) 34%);
        }

        .alert{ position:relative; margin-bottom:20px; padding:12px 15px; border-radius:14px; font-size:13px; }
        .alert-error{ background:rgba(254,226,226,.7); border:1px solid rgba(248,113,113,.4); color:#b42318; }
        .alert-ok{ background:rgba(220,252,231,.7); border:1px solid rgba(134,239,172,.5); color:#15803d; }

        /* ===================== FIELDS ===================== */
        .field{ margin-bottom:20px; }
        .field label{ display:block; font-size:13px; font-weight:600; color:#1F2937; margin-bottom:9px; letter-spacing:.1px; }
        .input-wrap{ position:relative; }
        .input-wrap .ic{ position:absolute; left:18px; top:50%; transform:translateY(-50%); color:#9aa3b2; pointer-events:none; transition:color .25s; }
        .input-wrap .eye{ position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#9aa3b2; cursor:pointer; background:none; border:0; padding:5px; display:flex; transition:color .2s; }
        .input-wrap .eye:hover{ color:var(--primary); }
        .input-wrap input{
            width:100%; height:58px; border-radius:18px; padding:0 18px 0 50px;
            font-size:15px; color:var(--text); font-family:inherit; font-weight:450;
            background:rgba(255,255,255,.5);
            border:1px solid rgba(16,24,40,.07);
            box-shadow:inset 0 1px 2px rgba(16,24,40,.05), inset 0 0 0 1px rgba(255,255,255,.4);
            outline:none; transition:border-color .25s, box-shadow .25s, background .25s;
        }
        .input-wrap input::placeholder{ color:#a6adbb; font-weight:450; }
        .input-wrap input:hover{ background:rgba(255,255,255,.66); border-color:rgba(16,24,40,.12); }
        .input-wrap input:focus{
            background:rgba(255,255,255,.85); border-color:var(--primary);
            box-shadow:0 0 0 4px var(--ring), inset 0 1px 2px rgba(16,24,40,.04);
        }
        .input-wrap input:focus ~ .ic{ color:var(--primary); }
        .input-wrap.invalid input{ border-color:#f87171; box-shadow:0 0 0 4px rgba(248,113,113,.14); }
        .err{ color:#dc2626; font-size:12px; margin:7px 2px 0; }

        .row{ display:flex; align-items:center; justify-content:space-between; margin:4px 2px 26px; }
        .check{ display:flex; align-items:center; gap:9px; font-size:13.5px; color:var(--muted); cursor:pointer; user-select:none; }
        .check input{ width:18px; height:18px; border-radius:6px; accent-color:var(--primary); cursor:pointer; }
        .link{ font-size:13.5px; font-weight:600; color:var(--primary); text-decoration:none; transition:opacity .2s; }
        .link:hover{ text-decoration:underline; opacity:.85; }

        /* ===================== BUTTON ===================== */
        .btn{
            position:relative; width:100%; height:58px; border:0; border-radius:18px; cursor:pointer;
            font-family:inherit; font-size:15px; font-weight:700; color:#fff; letter-spacing:.2px;
            display:flex; align-items:center; justify-content:center; gap:10px; overflow:hidden;
            background:linear-gradient(135deg,#5B5EF6,#7D82FF);
            box-shadow:0 10px 24px -8px rgba(91,94,246,.6), 0 2px 6px rgba(91,94,246,.3), inset 0 1px 0 rgba(255,255,255,.35);
            transition:transform .3s ease, box-shadow .3s ease;
        }
        .btn::after{ /* sheen sweep on hover */
            content:""; position:absolute; top:0; left:-60%; width:40%; height:100%;
            background:linear-gradient(100deg, transparent, rgba(255,255,255,.35), transparent);
            transform:skewX(-18deg); transition:left .6s ease;
        }
        .btn:hover{ transform:translateY(-2px); box-shadow:0 18px 38px -8px rgba(91,94,246,.7), 0 4px 12px rgba(91,94,246,.4), inset 0 1px 0 rgba(255,255,255,.4); }
        .btn:hover::after{ left:120%; }
        .btn:active{ transform:translateY(1px); box-shadow:0 8px 18px -8px rgba(91,94,246,.55); }
        .btn .arrow{ transition:transform .3s ease; }
        .btn:hover .arrow{ transform:translateX(4px); }

        /* ===================== DIVIDER ===================== */
        .divider{ display:flex; align-items:center; gap:14px; margin:24px 0 2px; }
        .divider::before,.divider::after{ content:""; flex:1; height:1px; background:linear-gradient(90deg,transparent,rgba(16,24,40,.10),transparent); }
        .divider span{ font-size:11px; font-weight:600; letter-spacing:1.4px; text-transform:uppercase; color:#9aa3b2; display:inline-flex; align-items:center; gap:6px; }

        /* ===================== FOOTER ===================== */
        .foot{ text-align:center; margin-top:26px; font-size:12px; color:var(--muted); animation:fade .9s ease both; animation-delay:.8s; }
        .foot .lc{ display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:9px; }
        .foot a{ color:var(--muted); text-decoration:none; transition:color .2s; }
        .foot a:hover{ color:var(--primary); }
        .foot .dot{ opacity:.35; }
        .foot .status{ display:inline-flex; align-items:center; gap:5px; }
        .foot .status i{ width:7px; height:7px; border-radius:50%; background:#22c55e; display:inline-block; box-shadow:0 0 0 3px rgba(34,197,94,.16); }

        /* ===================== ANIMATIONS ===================== */
        @keyframes fade{ from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:translateY(0)} }
        @keyframes cardUp{ from{opacity:0; transform:translateY(22px) scale(.97)} to{opacity:1; transform:translateY(0) scale(1)} }
        @keyframes slideIn{ from{opacity:0; transform:translateY(12px)} to{opacity:1; transform:translateY(0)} }
        .stagger{ opacity:0; animation:slideIn .65s cubic-bezier(.22,1,.36,1) both; }
        .d1{ animation-delay:.34s } .d2{ animation-delay:.44s } .d3{ animation-delay:.54s } .d4{ animation-delay:.64s } .d5{ animation-delay:.74s }

        @media (max-width:560px){
            .card{ padding:30px 24px 26px; border-radius:28px; }
            .brand h1{ font-size:27px; }
            .stage{ max-width:440px; }
        }
        @media (prefers-reduced-motion:reduce){
            *{ animation:none !important; transition:none !important; }
            .card{ transform:none !important; }
        }
    </style>
</head>
<body>
    <div class="bg-base"></div>
    <div class="orb o1"></div><div class="orb o2"></div><div class="orb o3"></div>
    <div class="bg-dots"></div>
    <div class="bg-vignette"></div>
    <div class="bg-grain"></div>

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
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
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
