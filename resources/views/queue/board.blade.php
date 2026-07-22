@extends('layouts.board')

@section('title', 'Live Queue Board')

@section('styles')
    <style>
        body {
            background-color: #f8f9fa;
            overflow: hidden;
            /* Prevent scrolling for dashboard feel */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .queue-board-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
        }

        /* Header */
        .board-header {
            margin-bottom: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            background-color: var(--prc-navy-850, #001f3f);
            background-image: linear-gradient(90deg, var(--prc-navy-800, #003366) 0%, var(--prc-navy-850, #001f3f) 45%, var(--prc-navy-900, #001a33) 100%);
            box-shadow: 0 0.75rem 1.75rem rgba(0, 0, 0, 0.10);
        }

        .board-header .text-primary {
            color: var(--prc-text-on-navy, #ffffff) !important;
        }

        .board-header .text-muted {
            color: rgba(214, 225, 240, 0.90) !important;
        }

        .board-header .clock-container {
            color: var(--prc-text-on-navy, #ffffff) !important;
        }

        /* Transactions Grid */
        .transactions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-auto-rows: 1fr;
            /* Equal height rows */
            gap: 1rem;
            flex: 1;
            /* Take remaining space */
            overflow-y: auto;
            /* Scroll if too many transactions */
        }

        /* Transaction Card */
        .transaction-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-top: 10px solid var(--prc-navy-800, #003366);
            /* Default color */
            transition: transform 0.2s;
        }

        .card-header {
            background-color: #fff;
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }

        .transaction-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #495057;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        .current-queue-number {
            font-size: 5rem;
            font-weight: 900;
            color: #212529;
            line-height: 1;
            text-align: center;
        }

        .ticket-entry {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100%;
        }

        .ticket-entry.single-call {
            padding-bottom: 1rem;
            margin-bottom: 0.5rem;
            width: 100%;
        }

        .ticket-entry.multi-call-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            border-bottom: 2px solid #dee2e6;
            padding: 0.35rem 0.5rem;
            margin-bottom: 0.35rem;
            text-align: left;
        }

        .ticket-entry.multi-call-row.compact-call-row {
            padding: 0.2rem 0.35rem;
            margin-bottom: 0.2rem;
            border-bottom: 1px solid #dee2e6;
        }

        .ticket-entry.multi-call-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .ticket-entry.multi-call-row .call-status-col {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .ticket-entry.multi-call-row .call-queue-col {
            font-weight: 900;
            text-align: center;
            flex: 1;
            margin: 0 0.5rem;
            line-height: 1;
        }

        .ticket-entry.multi-call-row .call-counter-col {
            font-weight: 700;
            color: var(--prc-navy-800, #003366);
            text-align: right;
            flex-shrink: 0;
            line-height: 1;
        }

        .card-body>.ticket-entry:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .ticket-entry .badge {
            margin-bottom: 0;
        }

        .ticket-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            width: 100%;
            align-items: start;
        }

        .ticket-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .counter-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--prc-navy-800, #003366);
            margin-top: 0.2rem;
            text-align: center;
        }

        .card-footer {
            background-color: #fff;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .next-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Priority Icon Styling */
        .priority-icon {
            color: #c72a2a;
            /* Accessible Blue */
            vertical-align: middle;
            display: inline-block;
        }

        .current-queue-number .priority-icon {
            font-size: 0.5em;
            /* Scaled relative to the large text */
            margin-left: 0.5rem;
        }

        .next-item .priority-icon {
            margin-right: 0.25rem;
            font-size: 1.1em;
            /* Slightly larger for visibility in list */
        }

        .next-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;

        }

        .next-item {
            background-color: #314d69;
            color: #ffffff;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Blinking Animation */
        @keyframes blink-text {
            0% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.05);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .blinking {
            animation: blink-text 1s infinite;
            color: #dc3545;
        }

        /* Connection Status */
        .connection-status {
            position: absolute;
            bottom: 0.5rem;
            right: 1rem;
            font-size: 0.8rem;
            color: #adb5bd;
            opacity: 0.7;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #198754;
            /* Green */
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-dot.offline {
            background-color: #dc3545;
            /* Red */
        }

        /* Clock */
        .clock-container {
            font-size: 1.5rem;
            font-weight: 600;
            color: #6c757d;
        }

        /* TTS Drawer + Link */
        :root {
            --prc-navy-900: #001a33;
            --prc-navy-850: #001f3f;
            --prc-navy-800: #003366;
            --prc-text-on-navy: #ffffff;
            --prc-muted-on-navy: #d6e1f0;

            --bs-primary: var(--prc-navy-850);
            --bs-primary-rgb: 0, 31, 63;
            --bs-link-color: var(--prc-navy-800);
            --bs-link-hover-color: var(--prc-navy-850);

            --tts-drawer-w: 360px;
            --tts-drawer-h: 280px;
        }

        .tts-link {
            position: fixed;
            bottom: 0.6rem;
            left: 1rem;
            /* Opposite of connection-status (which is bottom-right) */
            z-index: 11;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.12);
            border: 1px solid rgba(var(--bs-primary-rgb), 0.35);
            color: var(--bs-primary);
            text-decoration: none;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.08);
            transition: transform 160ms ease, opacity 160ms ease, background-color 160ms ease;
            opacity: 0.75;
        }

        .tts-link:hover,
        .tts-link:focus {
            opacity: 1;
            transform: translateY(-2px);
            background: rgba(var(--bs-primary-rgb), 0.18);
            outline: none;
        }

        .tts-drawer {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--tts-drawer-w);
            background: rgba(255, 255, 255, 0.98);
            border-right: 1px solid #dee2e6;
            box-shadow: 0.5rem 0 1.25rem rgba(0, 0, 0, 0.08);
            z-index: 10;
            transform: translateX(-100%);
            transition: transform 240ms ease-in-out;
            display: flex;
            flex-direction: column;
            padding: 0.75rem 1rem;
        }

        .tts-drawer.open {
            transform: translateX(0);
        }

        .queue-board-container.tts-open {
            margin-left: var(--tts-drawer-w);
            transition: margin-left 240ms ease-in-out;
        }

        .tts-drawer h6 {
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }

        .tts-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem 0;
        }

        .tts-row label {
            width: 5.5rem;
            font-size: 0.85rem;
            color: #495057;
        }

        .tts-row input[type="range"],
        .tts-row select {
            flex: 1;
        }

        .tts-muted {
            opacity: 0.5;
            pointer-events: none;
        }

        .tts-body {
            transition: max-height 240ms ease-in-out, opacity 160ms ease-in-out;
            overflow: hidden;
        }

        .tts-minibar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tts-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            background: #e9ecef;
            color: #495057;
        }

        @media (max-width: 1200px) {
            .transactions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .transactions-grid {
                grid-template-columns: 1fr;
            }

            .tts-drawer {
                left: 0;
                right: 0;
                top: auto;
                height: var(--tts-drawer-h);
                width: auto;
                transform: translateY(100%);
                border-right: none;
                border-top: 1px solid #dee2e6;
                box-shadow: 0 -0.5rem 1.25rem rgba(0, 0, 0, 0.08);
            }

            .tts-drawer.open {
                transform: translateY(0);
            }

            .queue-board-container.tts-open {
                margin-left: 0;
                padding-bottom: var(--tts-drawer-h);
            }
        }
    </style>
@endsection

@section('content')
<div class="queue-board-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center board-header">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ route('ext.prclogo') }}" alt="PRC Logo" style="height: 40px;">
            <div>
                <h1 class="h5 fw-bold m-0 text-primary">Professional Regulation Commission</h1>
                <h2 class="h5 text-muted m-0">Regional Office - CARAGA</h2>
            </div>
        </div>
        <div class="clock-container" id="clock">--:-- --</div>
    </div>

    <!-- Transactions Grid -->
    <div class="transactions-grid" id="transactions-grid">
        <!-- Cards will be injected here via JS -->
        <div class="d-flex justify-content-center align-items-center w-100 h-100" style="grid-column: 1 / -1;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <div class="connection-status" id="connection-status">
        <span class="status-dot"></span> <span id="status-text">Connected</span>
    </div>

    <!-- Audio Enable Prompt (Hidden by default) -->
    <div id="audio-enable-prompt" style="display: none; position: fixed; bottom: 5rem; right: 1rem; z-index: 20;">
        <button class="btn btn-primary shadow-lg d-flex align-items-center gap-2" onclick="enableAudioContext()">
            <i class="bi bi-volume-up-fill"></i>
            <span>Enable Audio</span>
        </button>
    </div>

    <!-- Accessible live region fallback when TTS unavailable -->
    <div id="tts-live-region" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

    <!-- TTS Link (collapsed by default) -->
    <a href="#" class="tts-link" id="tts-toggle-link" aria-controls="tts-panel" aria-expanded="false">
        <i class="bi bi-megaphone" aria-hidden="true"></i>
        <span class="visually-hidden">Audio announcements panel</span>
    </a>
    <!-- TTS Drawer -->
    <div class="tts-drawer" role="group" aria-label="Audio announcement settings" id="tts-panel">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Audio Announcements</h6>
            <button id="tts-close" type="button" class="btn btn-sm btn-outline-secondary" aria-label="Close panel"
                title="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tts-body">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="tts-enable">
                <label class="form-check-label" for="tts-enable">Enable announcements</label>
            </div>
            <div class="tts-row">
                <label for="tts-type">Type</label>
                <select id="tts-type" class="form-select form-select-sm">
                    <option value="tts">Text-to-Speech</option>
                    <option value="sfx">Sound Effect</option>
                </select>
            </div>
            <div class="tts-row" id="tts-voice-row">
                <label for="tts-voice">Voice</label>
                <select id="tts-voice" class="form-select form-select-sm"></select>
            </div>
            <div class="tts-row" id="tts-sfx-row" style="display:none">
                <label for="tts-sfx">Sound</label>
                <div class="input-group input-group-sm flex-grow-1" style="display:flex; gap:0.25rem;">
                    <select id="tts-sfx" class="form-select form-select-sm"></select>
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="tts-sfx-preview" title="Preview">
                        <i class="bi bi-play-fill"></i>
                    </button>
                </div>
            </div>
            <div class="tts-row">
                <label for="tts-volume">Volume</label>
                <input id="tts-volume" type="range" min="0" max="1" step="0.05" class="form-range">
            </div>
            <div class="tts-row" id="tts-rate-row">
                <label for="tts-rate">Speed</label>
                <input id="tts-rate" type="range" min="0.5" max="1.5" step="0.05" class="form-range">
            </div>
            <div class="tts-row" id="tts-accent-row">
                <label for="tts-accent">Variant</label>
                <select id="tts-accent" class="form-select form-select-sm">
                    <option value="tagalog">Tagalog</option>
                    <option value="cebuano">Cebuano</option>
                    <option value="ilocano">Ilocano</option>
                    <option value="none">None</option>
                </select>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        @verbatim
            // Update Clock
            function updateClock() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                document.getElementById('clock').innerText = now.toLocaleDateString('en-PH', options);
            }
            setInterval(updateClock, 1000);
            updateClock();

            // ---------------------------
            // Text-To-Speech (TTS) System
            // ---------------------------
            const tts = {
                supported: 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window,
                voices: [],
                voiceMap: {},
                queue: [],
                speaking: false,
                lastSpeakTime: 0,
                enabled: true,
                settings: {
                    type: 'tts', // 'tts' or 'sfx'
                    sfx: 'chime',
                    voiceURI: null,
                    volume: 0.9,
                    rate: 0.95,
                    langPrefs: ['en-US', 'en-PH', 'fil-PH', 'en-SG', 'en-GB'],
                    accent: 'tagalog'
                }
            };

            const SFX_CATALOG = {
                'general': [
                    { id: 'chime', label: 'Chime (Default)', file: 'chime.mp3' },
                    { id: 'chime-airport', label: 'Chime (Airport)', file: 'chime-airport.mp3' },
                    { id: 'chime-store', label: 'Chime (Store)', file: 'chime-store.mp3' },
                    { id: 'chime-clean', label: 'Chime (Clean)', file: 'chime-clean.mp3' },
                    { id: 'chime-up', label: 'Chime (Ascend)', file: 'chime-up.mp3' },
                    { id: 'beep', label: 'Beep', file: 'beep.mp3' },
                    { id: 'bell', label: 'Bell', file: 'bell.mp3' },
                    { id: 'ding', label: 'Ding', file: 'ding.mp3' },
                    { id: 'whoosh', label: 'Whoosh', file: 'whoosh.mp3' },
                    { id: 'click', label: 'Click', file: 'click.mp3' },
                    { id: 'pop', label: 'Pop', file: 'pop.mp3' }
                ],
                'queue': [
                    { id: 'next-in-line', label: 'Next in Line', file: 'next-in-line.mp3' },
                    { id: 'service-ready', label: 'Service Ready', file: 'service-ready.mp3' },
                    { id: 'queue-empty', label: 'Queue Empty', file: 'queue-empty.mp3' },
                    { id: 'wait-reminder', label: 'Wait Reminder', file: 'wait-reminder.mp3' }
                ]
            };

            const FIL_PHONETIC_DICT = {
                tagalog: {
                    'mga': 'ma-nga',
                    'ng': 'ng',
                    'pasensiya': 'pa-sén-sya',
                    'quezon': 'ké-zon',
                    'bayan': 'bá-yan',
                    'pangulo': 'pa-gú-lo',
                    'filipino': 'fi-li-pí-no',
                    'pilipino': 'pi-li-pí-no'
                },
                cebuano: {
                    'maayong': 'ma-á-yong',
                    'salamat': 'sa-lá-mat',
                    'daghang': 'dag-hang'
                },
                ilocano: {
                    'agyamanak': 'a-gya-ma-nak',
                    'naimbag': 'na-im-bág',
                    'apu': 'a-pú'
                }
            };

            function filDetectToken(token) {
                const t = token.toLowerCase();
                if (/[áéíóúñ]/i.test(token)) return true;
                if (/\b(ng|mga|si|ang|sa|kay|kay|mag|nag|pag)\b/.test(t)) return true;
                return false;
            }

            function filSyllabify(word) {
                const w = word.toLowerCase();
                const vowels = 'aeiouáéíóú';
                let out = '';
                for (let i = 0; i < w.length; i++) {
                    out += w[i];
                    const curr = w[i];
                    const next = w[i + 1] || '';
                    if (vowels.includes(curr) && next && !vowels.includes(next) && w[i + 2]) {
                        if (vowels.includes(w[i + 2])) out += '-';
                    }
                }
                return out;
            }

            function filApply(text, variant) {
                if (!variant || variant === 'none') return text;
                const dict = FIL_PHONETIC_DICT[variant] || {};
                const start = performance.now();
                const result = text.split(/(\s+)/).map(tok => {
                    const base = tok.trim().toLowerCase();
                    if (dict[base]) {
                        const mapped = dict[base];
                        return tok[0] === tok[0]?.toUpperCase() ? mapped.charAt(0).toUpperCase() + mapped.slice(1) : mapped;
                    }
                    if (filDetectToken(base)) return filSyllabify(tok);
                    return tok;
                }).join('');
                const dur = performance.now() - start;
                if (dur > 200) return text;
                return result;
            }

            function filToSSML(text, variant) {
                const t = filApply(text, variant);
                return '<speak><lang xml:lang="fil-PH">' + t + '</lang></speak>';
            }

            function ttsPopulateSFX() {
                const select = $('#tts-sfx');
                select.empty();
                for (const [category, items] of Object.entries(SFX_CATALOG)) {
                    const group = $('<optgroup>').attr('label', category.charAt(0).toUpperCase() + category.slice(1));
                    items.forEach(item => {
                        group.append(new Option(item.label, item.id));
                    });
                    select.append(group);
                }
                if (tts.settings.sfx) select.val(tts.settings.sfx);
            }

            // ---------------------------
            // SFX Synthesizer (Web Audio API)
            // ---------------------------
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

            function playTone(freq, type, duration, vol) {
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                gain.gain.setValueAtTime(vol, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
                return {
                    onended: (cb) => { setTimeout(cb, duration * 1000); },
                    stop: () => {
                        try { osc.stop(); } catch (e) { }
                    }
                };
            }

            const sfxGenerators = {
                'chime': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const t = audioCtx.currentTime;
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);

                    // Ding-Dong effect
                    osc.frequency.setValueAtTime(660, t); // E5
                    osc.frequency.setValueAtTime(554, t + 0.6); // C#5

                    gain.gain.setValueAtTime(0, t);
                    gain.gain.linearRampToValueAtTime(vol, t + 0.05);
                    gain.gain.setValueAtTime(vol, t + 0.6);
                    gain.gain.linearRampToValueAtTime(vol * 0.8, t + 0.65);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + 2.5);

                    osc.start(t);
                    osc.stop(t + 2.5);

                    return { onended: (cb) => setTimeout(cb, 2500) };
                },
                'chime-airport': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const t = audioCtx.currentTime;
                    // 4 notes: Low, High, Mid, Low (A3, E4, C#4, A3)
                    const notes = [440, 659.25, 554.37, 440];
                    const dur = 0.6;

                    notes.forEach((freq, i) => {
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t + (i * dur));
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);

                        gain.gain.setValueAtTime(0, t + (i * dur));
                        gain.gain.linearRampToValueAtTime(vol, t + (i * dur) + 0.1);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + (i * dur) + dur + 0.5);

                        osc.start(t + (i * dur));
                        osc.stop(t + (i * dur) + dur + 0.5);
                    });
                    return { onended: (cb) => setTimeout(cb, notes.length * dur * 1000 + 500) };
                },
                'chime-store': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const t = audioCtx.currentTime;
                    // High - Low (E5 - C5)
                    const notes = [659.25, 523.25];
                    const dur = 0.4;

                    notes.forEach((freq, i) => {
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'triangle'; // Brighter sound
                        osc.frequency.setValueAtTime(freq, t + (i * dur));
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);

                        gain.gain.setValueAtTime(0, t + (i * dur));
                        gain.gain.linearRampToValueAtTime(vol * 0.8, t + (i * dur) + 0.05);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + (i * dur) + 1.0);

                        osc.start(t + (i * dur));
                        osc.stop(t + (i * dur) + 1.0);
                    });
                    return { onended: (cb) => setTimeout(cb, 1500) };
                },
                'chime-clean': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const t = audioCtx.currentTime;
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);

                    // Single clean tone C5
                    osc.frequency.setValueAtTime(523.25, t);

                    gain.gain.setValueAtTime(0, t);
                    gain.gain.linearRampToValueAtTime(vol, t + 0.05);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + 2.0);

                    osc.start(t);
                    osc.stop(t + 2.0);
                    return { onended: (cb) => setTimeout(cb, 2000) };
                },
                'chime-up': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const t = audioCtx.currentTime;
                    // 3 notes ascending rapidly (C5, E5, G5)
                    const notes = [523.25, 659.25, 783.99];
                    const dur = 0.15;

                    notes.forEach((freq, i) => {
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t + (i * dur));
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);

                        gain.gain.setValueAtTime(0, t + (i * dur));
                        gain.gain.linearRampToValueAtTime(vol, t + (i * dur) + 0.05);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + (i * dur) + 1.0);

                        osc.start(t + (i * dur));
                        osc.stop(t + (i * dur) + 1.0);
                    });
                    return { onended: (cb) => setTimeout(cb, 1500) };
                },
                'beep': (vol) => playTone(880, 'sine', 0.2, vol),
                'bell': (vol) => {
                    // Simple Bell
                    return playTone(1200, 'triangle', 1.5, vol);
                },
                'ding': (vol) => playTone(1568, 'sine', 1.0, vol), // G6
                'whoosh': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const t = audioCtx.currentTime;
                    const bufferSize = audioCtx.sampleRate * 1; // 1 sec
                    const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
                    const data = buffer.getChannelData(0);
                    for (let i = 0; i < bufferSize; i++) {
                        data[i] = Math.random() * 2 - 1;
                    }
                    const noise = audioCtx.createBufferSource();
                    noise.buffer = buffer;
                    const filter = audioCtx.createBiquadFilter();
                    filter.type = 'lowpass';
                    filter.frequency.setValueAtTime(200, t);
                    filter.frequency.exponentialRampToValueAtTime(2000, t + 0.5);
                    filter.frequency.exponentialRampToValueAtTime(200, t + 1);

                    const gain = audioCtx.createGain();
                    gain.gain.setValueAtTime(vol * 0.5, t);
                    gain.gain.linearRampToValueAtTime(0, t + 1);

                    noise.connect(filter);
                    filter.connect(gain);
                    gain.connect(audioCtx.destination);
                    noise.start(t);
                    return { onended: (cb) => setTimeout(cb, 1000) };
                },
                'click': (vol) => playTone(2000, 'square', 0.05, vol * 0.5),
                'pop': (vol) => {
                    if (audioCtx.state === 'suspended') audioCtx.resume();
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.frequency.setValueAtTime(400, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(100, audioCtx.currentTime + 0.1);
                    gain.gain.setValueAtTime(vol, audioCtx.currentTime);
                    gain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 0.1);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.1);
                    return { onended: (cb) => setTimeout(cb, 100) };
                }
            };

            function ttsPlaySFX(id) {
                const vol = Number(tts.settings.volume) || 0.9;

                // 1. Check if we have a generator for this ID
                if (sfxGenerators[id]) {
                    return {
                        onended: null,
                        onerror: null,
                        play: async function () {
                            try {
                                // The generator plays immediately when called
                                const res = sfxGenerators[id](vol);
                                // Map the internal onended callback to the property
                                if (res.onended && this.onended) {
                                    res.onended(this.onended);
                                }
                            } catch (e) {
                                console.error("Synth Error", e);
                                if (this.onerror) this.onerror(e);
                            }
                        }
                    };
                }

                // 2. Check if it's a "queue" message (fallback to TTS)
                const queueItem = SFX_CATALOG.queue.find(x => x.id === id);
                if (queueItem) {
                    return {
                        onended: null,
                        onerror: null,
                        play: async function () {
                            const u = new SpeechSynthesisUtterance(queueItem.label);
                            u.volume = vol;
                            u.rate = 1.0;
                            u.onend = this.onended;
                            u.onerror = this.onerror;
                            window.speechSynthesis.speak(u);
                        }
                    };
                }

                // 3. Fallback to file (original logic) - kept just in case
                let file = null;
                for (const cat in SFX_CATALOG) {
                    const found = SFX_CATALOG[cat].find(x => x.id === id);
                    if (found) { file = found.file; break; }
                }
                if (!file) return null;

                // We know files likely don't exist, but legacy support...
                const audio = new Audio('/sounds/' + file);
                audio.volume = vol;
                return audio;
            }

            function ttsToggleUI(type) {
                if (type === 'sfx') {
                    $('#tts-voice-row, #tts-rate-row, #tts-accent-row').hide();
                    $('#tts-sfx-row').show();
                } else {
                    $('#tts-voice-row, #tts-rate-row, #tts-accent-row').show();
                    $('#tts-sfx-row').hide();
                }
            }

            function ttsLoadSettings() {
                try {
                    const raw = localStorage.getItem('tts.settings');
                    if (raw) {
                        const parsed = JSON.parse(raw);
                        Object.assign(tts.settings, parsed);
                    }
                    const enabled = localStorage.getItem('tts.enabled');
                    if (enabled === null) {
                        // Default to enabled for accessibility if not yet set
                        tts.enabled = true;
                    } else {
                        tts.enabled = enabled === 'true';
                    }
                } catch (_) { }
                $('#tts-enable').prop('checked', tts.enabled);
                $('#tts-volume').val(tts.settings.volume);
                $('#tts-rate').val(tts.settings.rate);
                $('#tts-accent').val(tts.settings.accent || 'tagalog');

                // New Settings
                $('#tts-type').val(tts.settings.type || 'tts');
                ttsToggleUI(tts.settings.type || 'tts');
                ttsPopulateSFX();
            }

            function ttsPersist() {
                localStorage.setItem('tts.settings', JSON.stringify(tts.settings));
                localStorage.setItem('tts.enabled', String(tts.enabled));
            }

            function ttsPopulateVoices() {
                if (!tts.supported) return;
                tts.voices = window.speechSynthesis.getVoices();
                tts.voiceMap = {};
                const select = $('#tts-voice');
                select.empty();
                // Prefer English with Filipino diction where available
                const preferred = [];
                const others = [];
                tts.voices.forEach(v => {
                    const opt = { name: v.name, lang: v.lang, uri: v.voiceURI, default: v.default };
                    const label = v.name + ' (' + v.lang + ')' + (v.default ? ' *' : '');
                    const isPreferred = tts.settings.langPrefs.some(pref => (v.lang || '').toLowerCase().startsWith(pref.toLowerCase()));
                    (isPreferred ? preferred : others).push({ label, value: v.voiceURI, v });
                    tts.voiceMap[v.voiceURI] = v;
                });
                const list = preferred.concat(others);
                list.forEach(vo => select.append(new Option(vo.label, vo.value)));

                // Choose existing setting or best match
                let chosen = tts.settings.voiceURI && tts.voiceMap[tts.settings.voiceURI] ? tts.settings.voiceURI : null;
                if (!chosen) {
                    const googleUs = tts.voices.find(v =>
                        (v.lang || '').toLowerCase().startsWith('en-us')
                        && (v.name || '').toLowerCase().includes('google us english')
                    );
                    if (googleUs) chosen = googleUs.voiceURI;
                }
                if (!chosen) {
                    const anyEnUs = tts.voices.find(v => (v.lang || '').toLowerCase().startsWith('en-us'));
                    if (anyEnUs) chosen = anyEnUs.voiceURI;
                }
                if (!chosen && preferred.length > 0) chosen = preferred[0].value;
                if (!chosen && list.length > 0) chosen = list[0].value;
                tts.settings.voiceURI = chosen;
                if (chosen) select.val(chosen);
                ttsPersist();
            }

            function ttsEnqueue(text) {
                if (!tts.supported) {
                    // Fallback: write to live region for screen readers
                    const region = document.getElementById('tts-live-region');
                    region.textContent = text;
                    return;
                }
                const transformed = filApply(text, tts.settings.accent);
                tts.queue.push(transformed);
                ttsProcess();
            }

            function ttsProcess() {
                if (!tts.enabled || tts.queue.length === 0) return;
                if (tts.speaking) {
                    // Check for stuck state (watchdog will handle, but we can double check here)
                    if (Date.now() - tts.lastSpeakTime > 6000) {
                        console.warn("TTS stuck detected in process, resetting...");
                        tts.speaking = false;
                        window.speechSynthesis.cancel();
                    } else {
                        return;
                    }
                }

                tts.lastSpeakTime = Date.now();

                // Check for Sound Effect Mode
                if (tts.settings.type === 'sfx') {
                    tts.queue.shift(); // Consume item
                    tts.speaking = true;
                    console.log('Playing SFX notification...');
                    const audio = ttsPlaySFX(tts.settings.sfx || 'chime');
                    if (audio) {
                        const onDone = () => { tts.speaking = false; ttsProcess(); };
                        audio.onended = onDone;
                        audio.onerror = (e) => { console.error("SFX Error", e); onDone(); };
                        audio.play().catch(e => {
                            console.error('SFX Play Error:', e);
                            tts.speaking = false;
                            ttsProcess();
                        });
                    } else {
                        tts.speaking = false; ttsProcess();
                    }
                    return;
                }

                const text = tts.queue.shift();
                console.log('Speaking:', text);

                // Combine Chime + TTS if desired (Optional feature: play chime then speak)
                // For now, we strictly follow the type.

                const u = new SpeechSynthesisUtterance(text);
                // Voice selection
                const voice = tts.settings.voiceURI ? tts.voiceMap[tts.settings.voiceURI] : null;
                if (voice) {
                    u.voice = voice;
                    u.lang = voice.lang;
                } else {
                    // Prefer English (Philippines) dialect, fallback to English
                    const v = (window.speechSynthesis.getVoices() || []).find(v =>
                        tts.settings.langPrefs.some(pref => (v.lang || '').toLowerCase().startsWith(pref.toLowerCase()))
                    ) || null;
                    if (v) {
                        u.voice = v;
                        u.lang = v.lang;
                    } else {
                        u.lang = 'en-US';
                    }
                }
                // Filipino diction approximation via rate and pitch
                u.rate = Number(tts.settings.rate) || 0.95;
                u.pitch = 1.05;
                u.volume = Number(tts.settings.volume) || 0.9;

                tts.speaking = true;
                u.onend = () => { tts.speaking = false; ttsProcess(); };
                u.onerror = (e) => {
                    console.error("TTS Error:", e);
                    tts.speaking = false;
                    ttsProcess();
                };
                try {
                    window.speechSynthesis.speak(u);
                } catch (e) {
                    console.error("TTS Speak Exception:", e);
                    tts.speaking = false;
                }
            }

            function ttsBindControls() {
                const container = $('.tts-drawer');
                if (!tts.supported) {
                    container.addClass('tts-muted');
                    $('#tts-status-help').text('TTS not supported; using on-screen announcements.');
                    return;
                }
                const panel = $('#tts-panel');
                const link = $('#tts-toggle-link');
                const closeBtn = $('#tts-close');
                const containerEl = document.querySelector('.queue-board-container');
                function openDrawer() {
                    panel.addClass('open');
                    containerEl.classList.add('tts-open');
                    link.attr('aria-expanded', 'true');
                    // Focus first control for accessibility
                    setTimeout(() => { $('#tts-enable').trigger('focus'); }, 10);
                }
                function closeDrawer() {
                    panel.removeClass('open');
                    containerEl.classList.remove('tts-open');
                    link.attr('aria-expanded', 'false');
                    link.trigger('focus');
                }
                link.on('click', function (e) {
                    e.preventDefault();
                    if (panel.hasClass('open')) closeDrawer(); else openDrawer();
                });
                closeBtn.on('click', function () { closeDrawer(); });
                $(document).on('keydown', function (e) {
                    if (e.key === 'Escape' && panel.hasClass('open')) {
                        closeDrawer();
                    }
                });
                $('#tts-enable').on('change', function () {
                    tts.enabled = $(this).is(':checked');
                    ttsPersist();
                    if (!tts.enabled) {
                        window.speechSynthesis.cancel();
                        tts.queue = [];
                        tts.speaking = false;
                    } else {
                        // Warm-up gesture requirement: play a brief, inaudible utterance if volume is 0
                        const warm = new SpeechSynthesisUtterance('');
                        try { window.speechSynthesis.speak(warm); } catch (_) { }
                    }
                });
                $('#tts-volume').on('input', function () {
                    tts.settings.volume = Number($(this).val());
                    ttsPersist();
                });
                $('#tts-rate').on('input', function () {
                    tts.settings.rate = Number($(this).val());
                    ttsPersist();
                });
                $('#tts-voice').on('change', function () {
                    tts.settings.voiceURI = $(this).val();
                    ttsPersist();
                });
                $('#tts-accent').on('change', function () {
                    tts.settings.accent = $(this).val();
                    ttsPersist();
                });
                $('#tts-type').on('change', function () {
                    tts.settings.type = $(this).val();
                    ttsToggleUI(tts.settings.type);
                    ttsPersist();
                });
                $('#tts-sfx').on('change', function () {
                    tts.settings.sfx = $(this).val();
                    ttsPersist();
                });
                $('#tts-sfx-preview').on('click', function () {
                    const audio = ttsPlaySFX($('#tts-sfx').val());
                    if (audio) audio.play().catch(e => console.error(e));
                });
            }

            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = function () {
                    ttsPopulateVoices();
                };
            }
            ttsLoadSettings();
            ttsBindControls();
            ttsPopulateVoices();
            if (tts.supported) {
                try { window.speechSynthesis.resume(); } catch (_) { }
                // Watchdog: if queue not empty but idle, kick the processor
                setInterval(() => {
                    if (tts.enabled && !tts.speaking && tts.queue.length > 0) {
                        ttsProcess();
                    } else if (tts.enabled && tts.speaking && Date.now() - tts.lastSpeakTime > 8000) {
                        console.warn("TTS watchdog: stuck for 8s, resetting.");
                        tts.speaking = false;
                        try { window.speechSynthesis.cancel(); } catch (e) { }
                        ttsProcess();
                    }
                }, 1000);
            }

            // Fetch Data
            let isFirstLoad = true;

            function playWelcomeMessage() {
                if (!tts.enabled) return;

                console.log('Attempting welcome message...');

                const onBlock = (e) => {
                    console.warn('Audio blocked, showing prompt:', e);
                    $('#audio-enable-prompt').fadeIn();
                };

                // Always try to resume context
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume().catch(() => { }); // catch here to prevent unhandled rejection
                }

                if (tts.settings.type === 'sfx') {
                    // Check context state first
                    if (audioCtx.state === 'suspended') {
                        // Try one more time with a short delay or fail
                        setTimeout(() => {
                            if (audioCtx.state === 'suspended') {
                                onBlock('AudioContext suspended');
                            } else {
                                playSfxWelcome();
                            }
                        }, 100);
                    } else {
                        playSfxWelcome();
                    }

                    function playSfxWelcome() {
                        console.log("Playing welcome SFX");
                        const audio = ttsPlaySFX('chime-airport');
                        if (audio) {
                            audio.onerror = onBlock;
                            audio.play().catch(onBlock);
                        }
                    }
                } else {
                    // TTS Welcome
                    const msg = new SpeechSynthesisUtterance("Welcome to the Live Queue Board");
                    msg.rate = tts.settings.rate || 1.0;
                    msg.volume = tts.settings.volume || 1.0;
                    if (tts.settings.voiceURI) {
                        const v = tts.voiceMap[tts.settings.voiceURI];
                        if (v) msg.voice = v;
                    }
                    msg.onerror = (e) => {
                        if (e.error === 'not-allowed' || e.error === 'network') onBlock(e);
                    };
                    try {
                        window.speechSynthesis.speak(msg);
                    } catch (e) {
                        onBlock(e);
                    }
                }
            }

            function fetchQueueData() {
                $.ajax({
        @endverbatim
                                                                                                                                                                    url: '{{ route('live-queue-board.data') }}',
                @verbatim
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                method: 'GET',
                            success: function (response) {
                                $('.status-dot').removeClass('offline').css('background-color', '#198754');
                                $('#status-text').text('Connected');
                                renderTransactions(response.transactions);

                                if (isFirstLoad) {
                                    // Populate lastAnnouncements to prevent flood, but DO NOT announce
                                    if (response.transactions && Array.isArray(response.transactions)) {
                                        response.transactions.forEach(tx => {
                                            if (tx && tx.serving && Array.isArray(tx.serving)) {
                                                const txKey = String(tx.id);
                                                tx.serving.forEach(ticket => {
                                                    const key = txKey + '-' + ticket.queue_number + '-' + ticket.counter_name;
                                                    const current = {
                                                        queue_number: ticket.queue_number || '',
                                                        called_at: ticket.called_at || '',
                                                        counter_name: ticket.counter_name || ''
                                                    };
                                                    lastAnnouncements.set(key, current);
                                                });
                                            }
                                        });
                                    }
                                    if (Array.isArray(response.reannouncements)) {
                                        response.reannouncements.forEach(ev => seenReannounceIds.add(ev.id));
                                    }

                                    isFirstLoad = false;

                                    // Attempt to play welcome message
                                    setTimeout(playWelcomeMessage, 500);
                                } else {
                                    detectNewCallsAndAnnounce(response.transactions);
                                    processReannouncements(response.reannouncements || []);
                                }
                            },
                            error: function (err) {
                                console.error("Failed to fetch queue data", err);
                                $('.status-dot').addClass('offline').css('background-color', '#dc3545');
                                $('#status-text').text('Reconnecting...');
                            }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        }

                    // Track recent calls to avoid duplicate announcements
                    const lastAnnouncements = new Map(); // key: transactionId, value: {queue_number, called_at}
                    const seenReannounceIds = new Set();

                    function detectNewCallsAndAnnounce(transactions) {
                        if (!transactions || !Array.isArray(transactions)) return;
                        transactions.forEach(tx => {
                            if (!tx || !tx.serving || !Array.isArray(tx.serving) || tx.serving.length === 0) return;
                            // Announce each new call in the array
                            const txKey = String(tx.id);
                            tx.serving.forEach(ticket => {
                                const key = txKey + '-' + ticket.queue_number + '-' + ticket.counter_name;
                                const prev = lastAnnouncements.get(key);
                                const current = {
                                    queue_number: ticket.queue_number || '',
                                    called_at: ticket.called_at || '',
                                    counter_name: ticket.counter_name || ''
                                };
                                const changed = !prev
                                    || prev.queue_number !== current.queue_number
                                    || prev.called_at !== current.called_at;
                                if (changed && ticket.queue_number && ticket.counter_name) {
                                    console.log("New call detected:", current);
                                    lastAnnouncements.set(key, current);
                                    const spoken = ticket.announcement || (() => {
                                        const counterDigits = String(current.counter_name).match(/\d+/);
                                        const counterNumber = counterDigits ? counterDigits[0] : current.counter_name;
                                        return 'Queue number ' + current.queue_number + ', please proceed to counter number ' + counterNumber + '.';
                                    })();
                                    ttsEnqueue(spoken);
                                }
                            });
                        });
                    }

                    function processReannouncements(items) {
                        if (!Array.isArray(items) || items.length === 0) return;
                        items.sort((a, b) => a.id - b.id);
                        items.forEach(ev => {
                            if (seenReannounceIds.has(ev.id)) return;
                            seenReannounceIds.add(ev.id);
                            const spoken = ev.announcement || (() => {
                                const counterDigits = String(ev.counter_name || '').match(/\d+/);
                                const counterNumber = counterDigits ? counterDigits[0] : (ev.counter_name || '');
                                return 'Queue number ' + ev.queue_number + ', please proceed to counter number ' + counterNumber + '.';
                            })();
                            ttsEnqueue(spoken);
                        });
                        // trim set to avoid infinite growth
                        if (seenReannounceIds.size > 200) {
                            const arr = Array.from(seenReannounceIds).slice(-100);
                            seenReannounceIds.clear();
                            arr.forEach(id => seenReannounceIds.add(id));
                        }
                    }

                    function statusBadgeHtml(status, isCompact = false) {
                        if (!status) {
                            return '';
                        }
                        const s = String(status).toLowerCase();
                        const badgePadding = isCompact ? 'px-2 py-1' : 'px-3 py-2';
                        if (s === 'called') {
                            return '<span class="badge bg-warning text-dark ' + badgePadding + '">CALLING</span>';
                        }
                        if (s === 'serving') {
                            return '<span class="badge bg-success ' + badgePadding + '">SERVING</span>';
                        }
                        return '<span class="badge bg-secondary ' + badgePadding + '">' + String(status).toUpperCase() + '</span>';
                    }

                    function renderTransactions(transactions) {
                        const container = $('#transactions-grid');

                        if (!transactions || transactions.length === 0) {
                            container.html(
                                '<div class="d-flex flex-column justify-content-center align-items-center h-100 w-100 text-muted" style="grid-column: 1 / -1;">' +
                                '<i class="bi bi-inbox fs-1"></i>' +
                                '<p class="mt-2">No transactions available</p>' +
                                '</div>'
                            );
                            return;
                        }

                        // First pass: render all cards with a starting font size to measure
                        let html = '';
                        transactions.forEach(tx => {
                            // Determine content for serving (limit to maximum 4 calls per transaction)
                            let servingHtml = '';
                            const servingTickets = (tx.serving && Array.isArray(tx.serving) ? tx.serving : []).slice(0, 4);
                            const ticketCount = servingTickets.length;

                            if (ticketCount > 0) {
                                if (ticketCount === 1) {
                                    // 1 ticket: centered single column
                                    servingTickets.forEach(ticket => {
                                        const blinkingClass = ticket.is_blinking ? 'blinking' : '';
                                        const statusBadge = statusBadgeHtml(ticket.status, false);
                                        const priorityIcon = ticket.is_priority
                                            ? '<i class="bi bi-person-wheelchair priority-icon" aria-label="Priority"></i>'
                                            : '';

                                        servingHtml +=
                                            '<div class="ticket-entry single-call">' +
                                            statusBadge +
                                            '<div class="current-queue-number ' + blinkingClass + '" data-tx-id="' + tx.id + '" data-ticket-q="' + ticket.queue_number + '">' +
                                            ticket.queue_number + priorityIcon +
                                            '</div>' +
                                            '<div class="counter-display" data-tx-id="' + tx.id + '" data-ticket-q="' + ticket.queue_number + '">' +
                                            ticket.counter_name +
                                            '</div>' +
                                            '</div>';
                                    });
                                } else {
                                    // 2, 3, or 4 tickets: 1 single row per call following template: [status] [queue number] [counter number]
                                    const is4Calls = ticketCount === 4;
                                    servingTickets.forEach((ticket) => {
                                        const blinkingClass = ticket.is_blinking ? 'blinking' : '';
                                        const statusBadge = statusBadgeHtml(ticket.status, true);
                                        const priorityIcon = ticket.is_priority
                                            ? '<i class="bi bi-person-wheelchair priority-icon" aria-label="Priority"></i>'
                                            : '';

                                        servingHtml +=
                                            '<div class="ticket-entry multi-call-row' + (is4Calls ? ' compact-call-row' : '') + '">' +
                                            '<div class="call-status-col">' + statusBadge + '</div>' +
                                            '<div class="current-queue-number call-queue-col ' + blinkingClass + '" data-tx-id="' + tx.id + '" data-ticket-q="' + ticket.queue_number + '">' +
                                            ticket.queue_number + priorityIcon +
                                            '</div>' +
                                            '<div class="counter-display call-counter-col" data-tx-id="' + tx.id + '" data-ticket-q="' + ticket.queue_number + '">' +
                                            ticket.counter_name +
                                            '</div>' +
                                            '</div>';
                                    });
                                }
                            } else {
                                servingHtml =
                                    '<div class="ticket-entry single-call">' +
                                    '<div class="current-queue-number text-muted" style="opacity: 0.3;">--</div>' +
                                    '<div class="counter-display text-muted" style="opacity: 0.5; font-weight: bold;">NO ACTIVE TICKET</div>' +
                                    '</div>';
                            }

                            let nextHtml = '';
                            if (tx.next_in_line && tx.next_in_line.length > 0) {
                                tx.next_in_line.forEach(item => {
                                    let num = item.number || item;
                                    let isPriority = item.is_priority || false;
                                    let priorityIcon = isPriority
                                        ? '<i class="bi bi-person-wheelchair priority-icon" aria-label="Priority"></i>'
                                        : '';

                                    nextHtml += '<span class="next-item">' + priorityIcon + num + '</span>';
                                });

                                if (tx.total_waiting > 5) {
                                    let moreCount = tx.total_waiting - 5;
                                    nextHtml += '<span class="next-item ms-auto">+ ' + moreCount + ' more</span>';
                                }
                            } else {
                                nextHtml = '<span class="text-danger fst-italic small">Waitinglist is empty!</span>';
                            }

                            html +=
                                '<div class="transaction-card" data-tx-id="' + tx.id + '">' +
                                '<div class="card-header">' +
                                '<div class="transaction-name" title="' + tx.name + '">' + tx.name + '</div>' +
                                '</div>' +
                                '<div class="card-body" data-tx-id="' + tx.id + '">' +
                                servingHtml +
                                '</div>' +
                                '<div class="card-footer">' +
                                '<span class="next-label" style="margin-top:-10px !important;">Next</span>' +
                                '<div class="next-list">' +
                                nextHtml +
                                '</div>' +
                                '</div>' +
                                '</div>';
                        });

                        container.html(html);

                        // Second pass: calculate optimal font size for each transaction's card body
                        transactions.forEach(tx => {
                            const servingTickets = (tx.serving && Array.isArray(tx.serving) ? tx.serving : []).slice(0, 4);
                            const ticketCount = servingTickets.length;
                            if (ticketCount === 0) return;

                            const card = container.find('.transaction-card[data-tx-id="' + tx.id + '"]');
                            const cardBody = card.find('.card-body');
                            const cardHeader = card.find('.card-header');
                            const cardFooter = card.find('.card-footer');
                            const entries = cardBody.find('.ticket-entry');

                            const isMultiRow = ticketCount >= 2;
                            const is4Calls = ticketCount === 4;

                            // Font size bounds: 2 and 3 calls share the same font scale; 4 calls use a smaller scale
                            let minFontSize = 0.5;
                            let maxFontSize = is4Calls ? 2.1 : (isMultiRow ? 3.2 : 8);
                            let optimalFontSize = maxFontSize;

                            // Apply temporary styles to measure
                            let test = (queueSize) => {
                                cardBody.css({
                                    'display': 'flex',
                                    'flex-direction': 'column',
                                    'justify-content': isMultiRow ? 'flex-start' : 'center',
                                    'align-items': 'center',
                                    'overflow': 'hidden',
                                    'height': 'auto'
                                });

                                entries.each((idx, el) => {
                                    const $el = $(el);
                                    const qNum = $el.find('.current-queue-number');
                                    const cDisplay = $el.find('.counter-display');
                                    const badge = $el.find('.badge');

                                    if ($el.hasClass('multi-call-row')) {
                                        qNum.css({
                                            'font-size': queueSize + 'rem',
                                            'line-height': '1',
                                            'margin-bottom': '0'
                                        });
                                        cDisplay.css({
                                            'font-size': Math.max(0.8, queueSize * 0.65) + 'rem',
                                            'line-height': '1',
                                            'margin-top': '0'
                                        });
                                        const badgeScale = is4Calls ? 0.42 : 0.52;
                                        badge.css({
                                            'font-size': Math.max(0.65, queueSize * badgeScale) + 'rem',
                                            'padding': is4Calls ? '0.15rem 0.35rem' : '0.25rem 0.5rem'
                                        });
                                    } else {
                                        qNum.css({
                                            'font-size': queueSize + 'rem',
                                            'line-height': '1.1',
                                            'margin-bottom': '-2.5rem'
                                        });
                                        cDisplay.css({
                                            'font-size': (queueSize / 3.5) + 'rem',
                                            'margin-top': '2rem'
                                        });
                                    }
                                });

                                // Calculate available space
                                const availableHeight = card.height() - cardHeader.outerHeight(true) - cardFooter.outerHeight(true) - 15;
                                const contentHeight = cardBody.outerHeight(true);
                                return contentHeight <= availableHeight;
                            };

                            // Perform binary search for optimal font size
                            for (let i = 0; i < 40; i++) {
                                const mid = (minFontSize + maxFontSize) / 2;
                                if (test(mid)) {
                                    optimalFontSize = mid;
                                    minFontSize = mid;
                                } else {
                                    maxFontSize = mid;
                                }
                            }

                            // Apply final optimal font size
                            test(optimalFontSize * 0.95);
                        });
                    }

                    // Initial load and polling

                    function enableAudioContext() {
                        console.log("User gesture: enabling audio context...");
                        if (audioCtx.state === 'suspended') {
                            audioCtx.resume().then(() => {
                                console.log("AudioContext resumed by user.");
                            }).catch(e => console.error("Failed to resume AudioContext", e));
                        }

                        // Reset TTS state
                        if (typeof window.speechSynthesis !== 'undefined') {
                            window.speechSynthesis.cancel();
                            tts.speaking = false;
                        }

                        $('#audio-enable-prompt').fadeOut();

                        // Play a short confirmation beep
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.frequency.value = 880;
                        gain.gain.value = 0.1;
                        osc.start();
                        osc.stop(audioCtx.currentTime + 0.1);

                        // Retry welcome message after a short delay
                        setTimeout(() => {
                            playWelcomeMessage();
                        }, 200);
                    }

                    // Start fetching
                    fetchQueueData();

                @endverbatim
        if (window.EventSource) {
            const eventSource = new EventSource('{{ route("queue.stream") }}');
            @verbatim

                    eventSource.onopen = function () {
                        $('#status-text').text('Connected (Stream)');
                        $('.status-dot').removeClass('offline');
                    };

                    eventSource.addEventListener('queue_created', function (e) {
                        console.log('Queue Created Event:', e.data);
                        fetchQueueData();
                    });

                    eventSource.addEventListener('queue_updated', function (e) {
                        console.log('Queue Updated Event:', e.data);
                        fetchQueueData();
                    });

                    eventSource.onerror = function (e) {
                        console.error('SSE Error:', e);
                        $('#status-text').text('Offline (Reconnecting...)');
                        $('.status-dot').addClass('offline');
                    };
                } else {
                    setInterval(fetchQueueData, 3000);
                }

                // Auto-reload and Enable Audio Logic
                $(document).ready(function () {
                    const urlParams = new URLSearchParams(window.location.search);

                    if (urlParams.has('init')) {
                        sessionStorage.setItem('should_auto_enable_audio', 'true');
                        // Navigate to clean URL (reload)
                        window.location.href = window.location.pathname;
                    } else if (sessionStorage.getItem('should_auto_enable_audio') === 'true') {
                        sessionStorage.removeItem('should_auto_enable_audio');

                        console.log('Auto-enabling audio from reload...');
                        setTimeout(() => {
                            enableAudioContext();
                            // Also trigger click on the button if it exists/visible
                            $('#audio-enable-prompt button').click();
                        }, 1000);
                    }
                });
            @endverbatim
    </script>
@endsection