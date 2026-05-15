<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Transportasi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        /* ═══════════════════════════════════════════
           PAGE — exact viewport, no scroll
        ═══════════════════════════════════════════ */
        .page-bg {
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background: linear-gradient(
                180deg,
                #5bb3e8  0%,
                #7bc6f0  20%,
                #a4d9f7  45%,
                #c8ecfb  70%,
                #ddf3fc  88%,
                #eef9fe 100%
            );
        }

        /* ═══ SUN ═══ */
        .sun {
            position: absolute;
            top: 6%; right: 12%;
            width: 70px; height: 70px;
            border-radius: 50%;
            background: radial-gradient(circle at 40% 40%,
                #fffde7 0%, #fef08a 28%, #fbbf24 58%, rgba(251,191,36,0) 100%);
            box-shadow:
                0 0 0  10px rgba(253,224,71,.14),
                0 0 0  22px rgba(253,224,71,.08),
                0 0 55px 16px rgba(251,191,36,.22);
            animation: sunGlow 5s ease-in-out infinite;
            z-index: 1;
        }
        @keyframes sunGlow {
            0%,100% { box-shadow: 0 0 0 10px rgba(253,224,71,.14), 0 0 0 22px rgba(253,224,71,.08), 0 0 55px 16px rgba(251,191,36,.22); }
            50%      { box-shadow: 0 0 0 15px rgba(253,224,71,.18), 0 0 0 32px rgba(253,224,71,.1),  0 0 72px 22px rgba(251,191,36,.28); }
        }

        /* ═══ CLOUDS ═══ */
        .cloud {
            position: absolute;
            background: rgba(255,255,255,.97);
            border-radius: 100px;
            z-index: 1;
            box-shadow: 0 4px 16px rgba(120,180,230,.18);
        }
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: rgba(255,255,255,.97);
            border-radius: 50%;
        }
        .cloud-1 { width:140px; height:38px; top:11%; left:3%;  animation: cd1 26s ease-in-out infinite alternate; }
        .cloud-1::before { width:70px;  height:56px; top:-30px; left:22px; }
        .cloud-1::after  { width:48px;  height:42px; top:-20px; left:74px; }
        .cloud-2 { width:104px; height:28px; top:20%; left:34%; animation: cd2 34s ease-in-out infinite alternate; }
        .cloud-2::before { width:52px;  height:42px; top:-20px; left:16px; }
        .cloud-2::after  { width:36px;  height:30px; top:-12px; left:52px; }
        .cloud-3 { width:160px; height:42px; top:6%;  left:52%; animation: cd3 22s ease-in-out infinite alternate; }
        .cloud-3::before { width:80px;  height:64px; top:-34px; left:28px; }
        .cloud-3::after  { width:56px;  height:50px; top:-24px; left:86px; }
        .cloud-4 { width:84px;  height:22px; top:27%; right:5%; animation: cd4 30s ease-in-out infinite alternate; }
        .cloud-4::before { width:42px;  height:34px; top:-16px; left:12px; }
        .cloud-4::after  { width:28px;  height:24px; top:-10px; left:40px; }
        @keyframes cd1 { to { transform: translateX( 38px); } }
        @keyframes cd2 { to { transform: translateX(-30px); } }
        @keyframes cd3 { to { transform: translateX( 44px); } }
        @keyframes cd4 { to { transform: translateX(-24px); } }

        /* ═══ BIRDS ═══ */
        .bird-flock { position: absolute; z-index: 1; }
        .bf1 { top:16%; left:-130px; animation: bFly 22s linear infinite; }
        .bf2 { top:10%; left:-210px; animation: bFly 30s linear infinite; animation-delay:12s; }
        @keyframes bFly {
            from { transform: translateX(0); }
            to   { transform: translateX(calc(100vw + 280px)); }
        }

        /* ═══════════════════════════════════════════
           CARD WRAPPER
        ═══════════════════════════════════════════ */
        .card-wrapper {
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px 16px;
            position: relative;
            z-index: 10;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ═══════════════════════════════════════════
           LOGIN CARD — true glassmorphism
        ═══════════════════════════════════════════ */
        .login-card {
            width: 100%;
            max-width: 360px;
            border-radius: 18px;
            overflow: hidden;
            /* Glass applied to WHOLE card so header is also transparent */
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(24px) saturate(1.5);
            -webkit-backdrop-filter: blur(24px) saturate(1.5);
            border: 1px solid rgba(255,255,255,.22);
            box-shadow:
                0 16px 48px rgba(0,8,32,.45),
                0 4px 14px  rgba(0,8,32,.22),
                inset 0 1px 0 rgba(255,255,255,.28);
        }

        /* ── Slim transparent header ── */
        .card-header {
            background: rgba(0, 18, 60, 0.38);
            padding: 14px 22px 13px;
            border-bottom: 1.5px solid rgba(255,255,255,.14);
            border-bottom-color: #6ab04c;
        }
        .brand { display: flex; align-items: center; gap: 11px; }
        .brand-icon-wrap {
            width: 36px; height: 36px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-name    { font-size: 16px; font-weight: 800; color: #fff; line-height: 1.1; letter-spacing: .4px; }
        .brand-tagline { font-size: 8.5px; font-weight: 500; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: 1.6px; margin-top: 2px; }

        /* ── Form body — fully transparent, card handles the blur ── */
        .card-body {
            padding: 18px 22px 20px;
            background: transparent;
        }

        .form-title {
            font-size: 17px; font-weight: 700;
            color: #ffffff;
            margin-bottom: 2px;
        }
        .form-subtitle {
            font-size: 11.5px;
            color: rgba(190,215,255,.60);
            margin-bottom: 14px;
        }

        /* ── Error alert ── */
        .alert-error {
            display: flex; align-items: flex-start; gap: 8px;
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.35);
            border-left: 3px solid #e31c25;
            border-radius: 7px;
            padding: 8px 11px;
            margin-bottom: 12px;
            font-size: 12px;
            color: #fecaca;
        }

        /* ── Form groups ── */
        .form-group { margin-bottom: 11px; }
        .form-label {
            display: block;
            font-size: 9.5px; font-weight: 700;
            color: rgba(190,215,255,.75);
            text-transform: uppercase;
            letter-spacing: .9px;
            margin-bottom: 5px;
        }
        .input-wrapper { position: relative; }
        .form-input {
            display: block; width: 100%;
            padding: 8px 36px 8px 12px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 7px;
            font-size: 13.5px;
            color: #ffffff;
            background: rgba(255,255,255,.14);
            outline: none; appearance: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-input::placeholder { color: rgba(150,185,230,.38); }
        .form-input:focus {
            border-color: rgba(106,176,76,.70);
            background: rgba(255,255,255,.12);
            box-shadow: 0 0 0 3px rgba(106,176,76,.15);
        }
        .form-input.is-invalid {
            border-color: rgba(239,68,68,.60);
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
        }
        .input-icon {
            position: absolute; right: 11px; top: 50%;
            transform: translateY(-50%);
            font-size: 13px; color: rgba(170,205,255,.45);
            pointer-events: none; transition: color .2s;
        }
        .form-input:focus ~ .input-icon { color: rgba(106,176,76,.85); }
        .btn-toggle {
            position: absolute; right: 11px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; padding: 0;
            color: rgba(170,205,255,.45); cursor: pointer;
            font-size: 13px; line-height: 1; transition: color .2s;
        }
        .btn-toggle:hover { color: #fff; }

        /* ── Submit ── */
        .btn-login {
            display: block; width: 100%;
            padding: 9px;
            margin-top: 5px;
            border: none; border-radius: 7px;
            background: linear-gradient(135deg, #e31c25 0%, #b91c1c 100%);
            color: #fff; font-size: 13.5px; font-weight: 700;
            letter-spacing: .3px; cursor: pointer;
            box-shadow: 0 3px 14px rgba(227,28,37,.40);
            transition: transform .15s, box-shadow .2s;
        }
        .btn-login:hover  { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(227,28,37,.48); }
        .btn-login:active { transform: translateY(0);    box-shadow: 0 2px 6px  rgba(227,28,37,.28); }

        /* ── Demo credentials ── */
        .demo-card {
            margin-top: 12px;
            background: rgba(106,176,76,.10);
            border: 1px solid rgba(106,176,76,.22);
            border-radius: 8px;
            padding: 8px 12px;
        }
        .demo-title {
            font-size: 9px; font-weight: 700;
            color: rgba(180,255,140,.80);
            text-transform: uppercase; letter-spacing: 1px;
            display: flex; align-items: center; gap: 5px;
            margin-bottom: 6px;
        }
        .demo-row {
            display: flex; align-items: center;
            font-size: 11.5px; padding: 2px 0;
            border-bottom: 1px dashed rgba(106,176,76,.18);
        }
        .demo-row:last-child { border-bottom: none; }
        .demo-role { width: 78px; font-weight: 600; color: rgba(180,255,140,.75); flex-shrink: 0; }
        .demo-cred { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: rgba(200,225,255,.78); }

        .form-footer { text-align: center; font-size: 10.5px; color: rgba(170,205,255,.42); margin-top: 12px; }

        /* ═══════════════════════════════════════════
           BOTTOM SCENE — constrained to viewport slice
        ═══════════════════════════════════════════ */
        .bottom-scene {
            flex-shrink: 0;
            height: clamp(200px, 34vh, 300px);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 5;
        }

        /* ── Refinery ── */
        .refinery-wrap {
            flex: 1;
            min-height: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }
        .refinery-svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .flare {
            transform-box: fill-box; transform-origin: bottom center;
            animation: fFlicker .65s ease-in-out infinite;
        }
        @keyframes fFlicker {
            0%,100% { transform: scaleY(1)    scaleX(1);    opacity: 1;   }
            22%     { transform: scaleY(1.32) scaleX(.82);  opacity: .9;  }
            48%     { transform: scaleY(.74)  scaleX(1.18); opacity: .85; }
            72%     { transform: scaleY(1.12) scaleX(.92);  opacity: .95; }
        }
        .smoke {
            transform-box: fill-box; transform-origin: bottom center;
            animation: sRise 3s ease-out infinite;
        }
        .smoke.d2 { animation-delay: 1s; }
        .smoke.d3 { animation-delay: 2s; }
        @keyframes sRise {
            0%   { transform: translateY(0)    scale(1);   opacity: .5; }
            100% { transform: translateY(-42px) scale(3.2); opacity: 0;  }
        }

        /* ── Road — proper z-index hierarchy ── */
        .road-section {
            position: relative;
            height: clamp(72px, 10vh, 88px);
            flex-shrink: 0;
        }
        .road-bg {
            position: absolute; inset: 0;
            z-index: 1;
            background: #7a7a7a;
        }
        .road-edge-top {
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px; z-index: 2;
            background: rgba(255,255,255,.70);
        }
        .road-dashes {
            position: absolute; top: 50%; left: 0; right: 0;
            height: 4px; margin-top: -2px; z-index: 2;
            background-image: repeating-linear-gradient(
                90deg,
                #fbbf24 0, #fbbf24 24px,
                transparent 24px, transparent 60px
            );
            background-size: 60px 4px;
            animation: dashScroll 1.3s linear infinite;
        }
        @keyframes dashScroll {
            from { background-position: 0; }
            to   { background-position: -60px; }
        }
        .road-edge-bottom {
            position: absolute; bottom: 0; left: 0; right: 0;
            height: 3px; z-index: 2;
            background: rgba(255,255,255,.70);
        }

        /* z:10 — truck always above markings */
        .truck-layer {
            position: absolute; inset: 0;
            z-index: 10; overflow: hidden;
        }
        .truck-mover {
            position: absolute; bottom: 0;
            animation: truckDrive 15s linear infinite;
        }
        @keyframes truckDrive {
            from { left: -325px; }
            to   { left: calc(100% + 75px); }
        }
        .wspin {
            transform-box: fill-box; transform-origin: center;
            animation: wSpin 1.0s linear infinite;
        }
        @keyframes wSpin { to { transform: rotate(360deg); } }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 900px) {
            .card-wrapper { padding: 14px 12px; }
        }

        @media (max-width: 640px) {
            .card-header  { padding: 12px 18px 11px; }
            .card-body    { padding: 15px 18px 17px; }
            .brand-name   { font-size: 14px; }
            .form-title   { font-size: 15px; }
            .bottom-scene { height: clamp(170px, 28vh, 250px); }
            .truck-mover  { transform-origin: bottom left; transform: scale(.68); }
        }

        @media (max-width: 400px) {
            .card-header  { padding: 10px 15px 9px; }
            .card-body    { padding: 12px 15px 14px; }
            .form-subtitle { margin-bottom: 10px; }
            .form-group   { margin-bottom: 9px; }
            .bottom-scene { height: clamp(155px, 26vh, 230px); }
            .truck-mover  { transform: scale(.52); }
        }

        @media (max-height: 660px) {
            .demo-card    { display: none; }
            .bottom-scene { height: clamp(145px, 23vh, 210px); }
        }

        @media (max-height: 520px) {
            .card-header  { padding: 9px 18px 8px; }
            .card-body    { padding: 10px 18px 12px; }
            .form-group   { margin-bottom: 7px; }
            .form-subtitle { display: none; }
            .bottom-scene { height: clamp(125px, 21vh, 175px); }
        }
    </style>
</head>
<body>

<div class="page-bg">

    {{-- Sun --}}
    <div class="sun"></div>

    {{-- Clouds --}}
    <div class="cloud cloud-1"></div>
    <div class="cloud cloud-2"></div>
    <div class="cloud cloud-3"></div>
    <div class="cloud cloud-4"></div>

    {{-- Birds --}}
    <svg class="bird-flock bf1" width="105" height="34" viewBox="0 0 105 34" fill="none">
        <path d="M2 16 Q8 5 14 16 Q20 5 26 16"   stroke="rgba(255,255,255,.42)" stroke-width="2"   fill="none" stroke-linecap="round"/>
        <path d="M32 10 Q38 0 44 10 Q50 0 56 10" stroke="rgba(255,255,255,.42)" stroke-width="2"   fill="none" stroke-linecap="round"/>
        <path d="M62 18 Q68 7 74 18 Q80 7 86 18" stroke="rgba(255,255,255,.32)" stroke-width="1.7" fill="none" stroke-linecap="round"/>
    </svg>
    <svg class="bird-flock bf2" width="72" height="26" viewBox="0 0 72 26" fill="none">
        <path d="M2 12 Q7 2 12 12 Q17 2 22 12"  stroke="rgba(255,255,255,.3)"  stroke-width="1.6" fill="none" stroke-linecap="round"/>
        <path d="M28 7 Q33 0 38 7 Q43 0 48 7"   stroke="rgba(255,255,255,.3)"  stroke-width="1.6" fill="none" stroke-linecap="round"/>
    </svg>

    {{-- ═══ LOGIN CARD ═══ --}}
    <div class="card-wrapper">
        <div class="login-card">

            {{-- Navy header --}}
            <div class="card-header">
                <div class="brand">
                    <div class="brand-icon-wrap">
                        <svg width="30" height="22" viewBox="0 0 52 38" fill="none">
                            <rect x="1"  y="6"  width="32" height="20" rx="3" fill="white"/>
                            <rect x="1"  y="16" width="32" height="6"  fill="#e31c25"/>
                            <rect x="31" y="13" width="5"  height="10" rx="1.5" fill="rgba(255,255,255,.5)"/>
                            <rect x="35" y="10" width="16" height="16" rx="3" fill="white"/>
                            <rect x="43" y="12" width="8"  height="10" rx="1.5" fill="rgba(100,180,240,.7)"/>
                            <rect x="49" y="15" width="3"  height="7"  rx="1"   fill="#fef9c3"/>
                            <circle cx="10" cy="29" r="5" fill="rgba(255,255,255,.85)"/>
                            <circle cx="10" cy="29" r="2" fill="rgba(0,30,80,.5)"/>
                            <circle cx="24" cy="29" r="5" fill="rgba(255,255,255,.85)"/>
                            <circle cx="24" cy="29" r="2" fill="rgba(0,30,80,.5)"/>
                            <circle cx="43" cy="29" r="5" fill="rgba(255,255,255,.85)"/>
                            <circle cx="43" cy="29" r="2" fill="rgba(0,30,80,.5)"/>
                        </svg>
                    </div>
                    <div>
                        <div class="brand-name">TRANSPORTASI</div>
                        <div class="brand-tagline">Sistem Manajemen Kendaraan</div>
                    </div>
                </div>
            </div>

            {{-- Glass body --}}
            <div class="card-body">

                <h1 class="form-title">Selamat Datang</h1>
                <p class="form-subtitle">Masuk untuk mengakses sistem penjadwalan.</p>

                @if ($errors->any())
                <div class="alert-error">
                    <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;margin-top:1px"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" autocomplete="off">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="usernameInput">Username</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="usernameInput"
                                name="username"
                                value="{{ old('username') }}"
                                class="form-input {{ $errors->has('username') ? 'is-invalid' : '' }}"
                                placeholder="Masukkan username"
                                required autofocus>
                            <i class="bi bi-person-fill input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="passwordInput">Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="passwordInput"
                                name="password"
                                class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                placeholder="Masukkan password"
                                required>
                            <button type="button" class="btn-toggle" id="togglePw" tabindex="-1">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right" style="margin-right:7px"></i>Masuk ke Sistem
                    </button>
                </form>

                <div class="demo-card">
                    <div class="demo-title">
                        <i class="bi bi-info-circle-fill"></i> Akun Demo
                    </div>
                    <div class="demo-row">
                        <span class="demo-role">Dispatcher</span>
                        <span class="demo-cred">admin / admin123</span>
                    </div>
                    <div class="demo-row">
                        <span class="demo-role">Pegawai</span>
                        <span class="demo-cred">user1 / user123</span>
                    </div>
                    <div class="demo-row">
                        <span class="demo-role">Driver</span>
                        <span class="demo-cred">driver1 / driver123</span>
                    </div>
                </div>

                <p class="form-footer">&copy; {{ date('Y') }} Sistem Transportasi &mdash; AHP-SAW</p>
            </div>

        </div>
    </div>

    {{-- ═══ BOTTOM — Refinery + Road ═══ --}}
    <div class="bottom-scene">

        {{-- Industrial scene (colorful, bright day) --}}
        <div class="refinery-wrap">
        <svg class="refinery-svg" viewBox="0 0 1400 200"
             xmlns="http://www.w3.org/2000/svg"
             preserveAspectRatio="xMidYMax slice"
             aria-hidden="true">

            {{-- Grass ground --}}
            <rect x="0" y="172" width="1400" height="28" fill="#5ab552"/>
            <rect x="0" y="166" width="1400" height="9"  fill="#6dc96d"/>

            {{-- ══ LEFT COMPLEX ══ --}}
            <rect x="6"  y="128" width="9"  height="38" fill="#7a6040"/>
            <ellipse cx="10" cy="114" rx="22" ry="20" fill="#3d8c3d"/>
            <ellipse cx="10" cy="110" rx="17" ry="15" fill="#4fa84f"/>

            {{-- Chimney A —  tallest, red/white --}}
            <rect x="50" y="8"  width="28" height="158" fill="#d8d8d8"/>
            <rect x="50" y="8"  width="28" height="18"  fill="#e31c25"/>
            <rect x="50" y="44" width="28" height="18"  fill="#e31c25"/>
            <rect x="50" y="80" width="28" height="18"  fill="#e31c25"/>
            <rect x="50" y="116" width="28" height="18" fill="#e31c25"/>
            <rect x="45" y="5"  width="38" height="6"   rx="2" fill="#c0c0c0"/>
            <circle class="smoke"    cx="64" cy="1"  r="8"   fill="rgba(220,220,220,0.65)"/>
            <circle class="smoke d2" cx="70" cy="-2" r="6"   fill="rgba(220,220,220,0.45)"/>

            {{-- Chimney B — medium --}}
            <rect x="126" y="28"  width="22" height="138" fill="#d8d8d8"/>
            <rect x="126" y="28"  width="22" height="15"  fill="#e31c25"/>
            <rect x="126" y="60"  width="22" height="15"  fill="#e31c25"/>
            <rect x="126" y="92"  width="22" height="15"  fill="#e31c25"/>
            <rect x="126" y="124" width="22" height="15"  fill="#e31c25"/>
            <rect x="122" y="25"  width="30" height="6"   rx="2" fill="#c0c0c0"/>
            <circle class="smoke"    cx="137" cy="21" r="6"   fill="rgba(220,220,220,0.6)"/>
            <circle class="smoke d3" cx="143" cy="18" r="5"   fill="rgba(220,220,220,0.4)"/>

            {{-- Main large building left --}}
            <rect x="88"  y="76"  width="202" height="90" fill="#e8e8e8"/>
            <rect x="88"  y="76"  width="202" height="8"  fill="#d0d0d0"/>
            <rect x="88"  y="110" width="202" height="9"  fill="#e31c25"/>
            <rect x="88"  y="134" width="202" height="9"  fill="#e31c25"/>
            <rect x="100" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="120" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="140" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="160" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="180" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="200" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="220" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="240" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="260" y="87"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="100" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="120" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="140" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="160" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="180" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="200" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="220" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="240" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="260" y="102" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="100" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="130" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="160" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="190" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="220" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="250" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="168" y="150" width="20"  height="16" rx="2"   fill="#c8c8c8"/>

            {{-- Side building front left --}}
            <rect x="296" y="108" width="115" height="58" fill="#f0f0f0"/>
            <rect x="296" y="108" width="115" height="6"  fill="#ddd"/>
            <rect x="296" y="130" width="115" height="7"  fill="#e31c25"/>
            <rect x="305" y="116" width="12"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="325" y="116" width="12"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="345" y="116" width="12"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="365" y="116" width="12"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="385" y="116" width="12"  height="9"  rx="1.5" fill="#87c4e8"/>

            {{-- Pipes left --}}
            <rect x="88"  y="156" width="323" height="5" rx="2" fill="#c8c8c8"/>
            <rect x="88"  y="161" width="323" height="3" rx="1" fill="#b8b8b8"/>
            <rect x="120" y="153" width="5"   height="8" fill="#c0c0c0"/>
            <rect x="200" y="153" width="5"   height="8" fill="#c0c0c0"/>
            <rect x="320" y="153" width="5"   height="8" fill="#c0c0c0"/>
            <rect x="390" y="153" width="5"   height="8" fill="#c0c0c0"/>

            {{-- Tree left-center --}}
            <rect x="420" y="124" width="10" height="42" fill="#7a6040"/>
            <ellipse cx="425" cy="108" rx="28" ry="24" fill="#3d8c3d"/>
            <ellipse cx="425" cy="104" rx="22" ry="19" fill="#4fa84f"/>
            <ellipse cx="425" cy="101" rx="16" ry="13" fill="#5cb85c"/>

            {{-- ══ CENTER ══ --}}
            <rect x="558" y="126" width="10" height="40" fill="#7a6040"/>
            <ellipse cx="563" cy="110" rx="26" ry="22" fill="#3d8c3d"/>
            <ellipse cx="563" cy="106" rx="20" ry="18" fill="#4fa84f"/>

            <rect x="698" y="130" width="9"  height="36" fill="#7a6040"/>
            <ellipse cx="702" cy="116" rx="22" ry="20" fill="#3d8c3d"/>
            <ellipse cx="702" cy="113" rx="17" ry="15" fill="#4fa84f"/>

            <rect x="778" y="120" width="74" height="46" fill="#ebebeb"/>
            <rect x="778" y="120" width="74" height="6"  fill="#d8d8d8"/>
            <rect x="778" y="140" width="74" height="7"  fill="#e31c25"/>
            <rect x="787" y="128" width="11" height="8"  rx="1.5" fill="#87c4e8"/>
            <rect x="806" y="128" width="11" height="8"  rx="1.5" fill="#87c4e8"/>
            <rect x="826" y="128" width="11" height="8"  rx="1.5" fill="#87c4e8"/>

            <rect x="868" y="126" width="10" height="40" fill="#7a6040"/>
            <ellipse cx="873" cy="110" rx="26" ry="22" fill="#4a9e4a"/>
            <ellipse cx="873" cy="106" rx="20" ry="17" fill="#5cb85c"/>

            {{-- ══ RIGHT COMPLEX ══ --}}
            <rect x="938" y="114" width="82" height="52" fill="#f0f0f0"/>
            <rect x="938" y="114" width="82" height="5"  fill="#ddd"/>
            <rect x="938" y="134" width="82" height="7"  fill="#e31c25"/>
            <rect x="948" y="122" width="12" height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="968" y="122" width="12" height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="990" y="122" width="12" height="9"  rx="1.5" fill="#87c4e8"/>

            {{-- Main building right --}}
            <rect x="958"  y="78"  width="192" height="88" fill="#e8e8e8"/>
            <rect x="958"  y="78"  width="192" height="8"  fill="#d0d0d0"/>
            <rect x="958"  y="110" width="192" height="9"  fill="#e31c25"/>
            <rect x="958"  y="134" width="192" height="9"  fill="#e31c25"/>
            <rect x="970"  y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="990"  y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1010" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1030" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1050" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1070" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1090" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1110" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1130" y="89"  width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="970"  y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="990"  y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1010" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1030" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1050" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1070" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1090" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1110" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1130" y="103" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="970"  y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1002" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1034" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1066" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1098" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1130" y="147" width="13"  height="9"  rx="1.5" fill="#87c4e8"/>
            <rect x="1048" y="150" width="20"  height="16" rx="2"   fill="#c8c8c8"/>

            {{-- Chimney C — right tallest --}}
            <rect x="1168" y="6"   width="28" height="160" fill="#d8d8d8"/>
            <rect x="1168" y="6"   width="28" height="18"  fill="#e31c25"/>
            <rect x="1168" y="42"  width="28" height="18"  fill="#e31c25"/>
            <rect x="1168" y="78"  width="28" height="18"  fill="#e31c25"/>
            <rect x="1168" y="114" width="28" height="18"  fill="#e31c25"/>
            <rect x="1163" y="3"   width="38" height="6"   rx="2" fill="#c0c0c0"/>
            <circle class="smoke"    cx="1182" cy="-1" r="8"  fill="rgba(220,220,220,0.60)"/>
            <circle class="smoke d2" cx="1188" cy="-4" r="6"  fill="rgba(220,220,220,0.40)"/>

            {{-- Chimney D — right medium --}}
            <rect x="1238" y="26"  width="22" height="140" fill="#d8d8d8"/>
            <rect x="1238" y="26"  width="22" height="15"  fill="#e31c25"/>
            <rect x="1238" y="58"  width="22" height="15"  fill="#e31c25"/>
            <rect x="1238" y="90"  width="22" height="15"  fill="#e31c25"/>
            <rect x="1238" y="122" width="22" height="15"  fill="#e31c25"/>
            <rect x="1234" y="23"  width="30" height="6"   rx="2" fill="#c0c0c0"/>
            <circle class="smoke"    cx="1249" cy="19" r="6"  fill="rgba(220,220,220,0.55)"/>

            {{-- Chimney E — far right --}}
            <rect x="1316" y="48"  width="18" height="118" fill="#d8d8d8"/>
            <rect x="1316" y="48"  width="18" height="14"  fill="#e31c25"/>
            <rect x="1316" y="78"  width="18" height="14"  fill="#e31c25"/>
            <rect x="1316" y="108" width="18" height="14"  fill="#e31c25"/>
            <rect x="1312" y="45"  width="26" height="5"   rx="2" fill="#c0c0c0"/>

            {{-- Pipes right --}}
            <rect x="958"  y="156" width="258" height="5" rx="2" fill="#c8c8c8"/>
            <rect x="958"  y="161" width="258" height="3" rx="1" fill="#b8b8b8"/>
            <rect x="990"  y="153" width="5"   height="8" fill="#c0c0c0"/>
            <rect x="1080" y="153" width="5"   height="8" fill="#c0c0c0"/>
            <rect x="1160" y="153" width="5"   height="8" fill="#c0c0c0"/>

            {{-- Trees right --}}
            <rect x="1354" y="124" width="10" height="42" fill="#7a6040"/>
            <ellipse cx="1359" cy="108" rx="28" ry="24" fill="#3d8c3d"/>
            <ellipse cx="1359" cy="104" rx="22" ry="19" fill="#4fa84f"/>

            <rect x="1388" y="128" width="9"  height="38" fill="#7a6040"/>
            <ellipse cx="1392" cy="114" rx="20" ry="18" fill="#4a9e4a"/>
            <ellipse cx="1392" cy="111" rx="15" ry="13" fill="#5cb85c"/>

        </svg>
        </div>

        {{-- Road + Truck --}}
        <div class="road-section">
            <div class="road-bg"></div>
            <div class="road-edge-top"></div>
            <div class="road-dashes"></div>
            <div class="road-edge-bottom"></div>

            <div class="truck-layer">
                <div class="truck-mover">
                    <svg width="308" height="68" viewBox="0 0 308 68" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="150" cy="66" rx="130" ry="4" fill="#0a1020"/>

                        {{-- ════ TANK ════ --}}
                        <rect x="10"  y="10" width="214" height="34" rx="14" fill="#e8eef4"/>
                        <ellipse cx="10"  cy="27" rx="13" ry="17" fill="#e8eef4"/>
                        <rect x="222" y="14" width="8"   height="26" rx="3" fill="#ccd8e4"/>
                        <rect x="14"  y="12" width="207" height="10" rx="10" fill="#ffffff"/>
                        <rect x="14"  y="21" width="207" height="5"  fill="#f0f5fa"/>
                        <rect x="14"  y="38" width="207" height="5"  fill="#c0ccd8"/>
                        <rect x="14"  y="43" width="207" height="2"  fill="#aab8c8"/>
                        <rect x="10"  y="31" width="214" height="9"  fill="#e31c25"/>
                        <text x="52" y="38.5"
                              font-family="Arial Black, Arial, sans-serif"
                              font-weight="900" font-size="7.5" fill="#ffffff"
                              letter-spacing="2.5">PERTAMINA</text>
                        <line x1="74"  y1="10" x2="74"  y2="44" stroke="#b0bcc8" stroke-width="2"/>
                        <line x1="134" y1="10" x2="134" y2="44" stroke="#b0bcc8" stroke-width="2"/>
                        <line x1="194" y1="10" x2="194" y2="44" stroke="#b0bcc8" stroke-width="2"/>
                        <line x1="56"  y1="10" x2="56"  y2="44" stroke="#8899aa" stroke-width="1.2"/>
                        <line x1="52"  y1="18" x2="60"  y2="18" stroke="#8899aa" stroke-width="1"/>
                        <line x1="52"  y1="26" x2="60"  y2="26" stroke="#8899aa" stroke-width="1"/>
                        <line x1="52"  y1="34" x2="60"  y2="34" stroke="#8899aa" stroke-width="1"/>
                        <line x1="52"  y1="42" x2="60"  y2="42" stroke="#8899aa" stroke-width="1"/>
                        <rect x="100" y="7"  width="5" height="5" rx="1" fill="#8899aa"/>
                        <rect x="97"  y="5"  width="11" height="3" rx="1" fill="#6b7e96"/>
                        <rect x="148" y="7"  width="5" height="5" rx="1" fill="#8899aa"/>
                        <rect x="145" y="5"  width="11" height="3" rx="1" fill="#6b7e96"/>

                        {{-- ════ CAB ════ --}}
                        <rect x="230" y="10" width="70" height="36" rx="5" fill="#e31c25"/>
                        <rect x="232" y="4"  width="50" height="8"  rx="3" fill="#c0392b"/>
                        <rect x="232" y="11" width="68" height="2"  fill="#b91c1c"/>
                        <rect x="233" y="14" width="24" height="15" rx="3" fill="#a8d8f0"/>
                        <rect x="233" y="14" width="24" height="15" rx="3" fill="none" stroke="#c0392b" stroke-width="1"/>
                        <line x1="259" y1="10" x2="259" y2="46" stroke="#c0392b" stroke-width="2"/>
                        <rect x="261" y="13" width="28" height="18" rx="3" fill="#a8d8f0"/>
                        <rect x="261" y="13" width="28" height="18" rx="3" fill="none" stroke="#c0392b" stroke-width="1"/>
                        <rect x="261" y="13" width="28" height="4"  rx="2" fill="#87c4e8"/>
                        <rect x="285" y="20" width="9"  height="13" rx="2.5" fill="#fef9c3"/>
                        <rect x="287" y="22" width="5"  height="9"  rx="1.5" fill="#fefce8"/>
                        <rect x="286" y="34" width="9"  height="9"  rx="1.5" fill="#9b1c1c"/>
                        <line x1="287" y1="35" x2="294" y2="42"   stroke="#7f1d1d" stroke-width=".8"/>
                        <line x1="294" y1="35" x2="287" y2="42"   stroke="#7f1d1d" stroke-width=".8"/>
                        <line x1="290.5" y1="34" x2="290.5" y2="43" stroke="#7f1d1d" stroke-width=".7"/>
                        <rect x="284" y="44" width="12" height="5"  rx="1.5" fill="#9b1c1c"/>
                        <rect x="229" y="37" width="14" height="10" rx="2"   fill="#c0392b"/>
                        <line x1="229" y1="42" x2="243" y2="42"              stroke="#b91c1c" stroke-width="1"/>
                        <rect x="232" y="2"  width="5"  height="13" rx="2"   fill="#374151"/>
                        <rect x="230" y="1"  width="9"  height="3"  rx="1.5" fill="#4b5563"/>
                        <circle cx="235" cy="0" r="2.5" fill="#6b7280"/>
                        <rect x="228" y="46" width="14" height="3"  rx="1"   fill="#9b1c1c"/>

                        {{-- ════ WHEELS ════ --}}
                        <circle cx="268" cy="56" r="13" fill="#111827"/>
                        <circle cx="268" cy="56" r="9"  fill="#1f2937"/>
                        <g class="wspin">
                            <circle cx="268" cy="56" r="4"   fill="#374151"/>
                            <line x1="268" y1="47" x2="268" y2="65" stroke="#6b7280" stroke-width="1.8"/>
                            <line x1="259" y1="56" x2="277" y2="56" stroke="#6b7280" stroke-width="1.8"/>
                            <line x1="261.8" y1="49.8" x2="274.2" y2="62.2" stroke="#6b7280" stroke-width="1.3"/>
                            <line x1="274.2" y1="49.8" x2="261.8" y2="62.2" stroke="#6b7280" stroke-width="1.3"/>
                        </g>
                        <rect x="253" y="50" width="4"  height="13" rx="1" fill="#1f2937"/>

                        <circle cx="148" cy="56" r="11" fill="#111827"/>
                        <circle cx="148" cy="56" r="7.5" fill="#1f2937"/>
                        <g class="wspin">
                            <circle cx="148" cy="56" r="3.5" fill="#374151"/>
                            <line x1="148" y1="48.5" x2="148" y2="63.5" stroke="#6b7280" stroke-width="1.5"/>
                            <line x1="140.5" y1="56" x2="155.5" y2="56" stroke="#6b7280" stroke-width="1.5"/>
                            <line x1="142.7" y1="50.7" x2="153.3" y2="61.3" stroke="#6b7280" stroke-width="1.1"/>
                            <line x1="153.3" y1="50.7" x2="142.7" y2="61.3" stroke="#6b7280" stroke-width="1.1"/>
                        </g>

                        <circle cx="46"  cy="56" r="13" fill="#111827"/>
                        <circle cx="46"  cy="56" r="9"  fill="#1f2937"/>
                        <g class="wspin">
                            <circle cx="46"  cy="56" r="4"   fill="#374151"/>
                            <line x1="46"  y1="47" x2="46"  y2="65" stroke="#6b7280" stroke-width="1.8"/>
                            <line x1="37"  y1="56" x2="55"  y2="56" stroke="#6b7280" stroke-width="1.8"/>
                            <line x1="39.8" y1="49.8" x2="52.2" y2="62.2" stroke="#6b7280" stroke-width="1.3"/>
                            <line x1="52.2" y1="49.8" x2="39.8" y2="62.2" stroke="#6b7280" stroke-width="1.3"/>
                        </g>

                        <circle cx="68"  cy="56" r="13" fill="#111827"/>
                        <circle cx="68"  cy="56" r="9"  fill="#1f2937"/>
                        <g class="wspin">
                            <circle cx="68"  cy="56" r="4"   fill="#374151"/>
                            <line x1="68"  y1="47" x2="68"  y2="65" stroke="#6b7280" stroke-width="1.8"/>
                            <line x1="59"  y1="56" x2="77"  y2="56" stroke="#6b7280" stroke-width="1.8"/>
                            <line x1="61.8" y1="49.8" x2="74.2" y2="62.2" stroke="#6b7280" stroke-width="1.3"/>
                            <line x1="74.2" y1="49.8" x2="61.8" y2="62.2" stroke="#6b7280" stroke-width="1.3"/>
                        </g>

                        <rect x="79"  y="48" width="4"  height="14" rx="1" fill="#1f2937"/>
                        <rect x="44"  y="53" width="26" height="3"  rx="1" fill="#374151"/>
                        <rect x="8"   y="44" width="224" height="4"  rx="1" fill="#b0bcc8"/>
                        <rect x="220" y="42" width="14" height="8"  rx="2" fill="#8899aa"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>{{-- /.bottom-scene --}}

</div>{{-- /.page-bg --}}

<script>
(function () {
    var toggleBtn  = document.getElementById('togglePw');
    var pwInput    = document.getElementById('passwordInput');
    var toggleIcon = document.getElementById('toggleIcon');
    if (toggleBtn && pwInput) {
        toggleBtn.addEventListener('click', function () {
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                toggleIcon.className = 'bi bi-eye';
            } else {
                pwInput.type = 'password';
                toggleIcon.className = 'bi bi-eye-slash';
            }
        });
    }
    var userInput = document.getElementById('usernameInput');
    if (userInput) {
        var icon = userInput.parentElement.querySelector('.input-icon');
        if (icon) {
            userInput.addEventListener('focus', function () { icon.style.color = 'rgba(106,176,76,.9)'; });
            userInput.addEventListener('blur',  function () { icon.style.color = ''; });
        }
    }
})();
</script>
</body>
</html>
