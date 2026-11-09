// Mode Masak: one step per screen, a per-step timer, and the screen kept awake.
// Progress lives in the browser only; the server hears about it once, on "Selesai".
export default function cookMode({ steps, startAt = 0 }) {
    return {
        steps,
        index: Math.min(Math.max(startAt, 0), Math.max(steps.length - 1, 0)),
        remaining: null,
        running: false,
        done: false,
        saving: false,
        timesCooked: null,
        startedAt: Date.now(),
        tick: null,
        wakeLock: null,

        init() {
            this.resetTimer();
            this.tick = setInterval(() => this.onTick(), 1000);
            this.keepAwake();
            this._onVisible = () => {
                if (document.visibilityState === 'visible' && !this.done) this.keepAwake();
            };
            document.addEventListener('visibilitychange', this._onVisible);
        },

        destroy() {
            clearInterval(this.tick);
            document.removeEventListener('visibilitychange', this._onVisible);
            this.wakeLock?.release?.();
        },

        get step() {
            return this.steps[this.index] ?? { text: '', timer: null, chips: [] };
        },

        get isFirst() {
            return this.index === 0;
        },

        get isLast() {
            return this.index === this.steps.length - 1;
        },

        get clock() {
            const total = Math.max(this.remaining ?? 0, 0);
            const m = String(Math.floor(total / 60)).padStart(2, '0');
            const s = String(total % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        get timeUp() {
            return !!this.step.timer && this.remaining === 0;
        },

        get timerLabel() {
            if (this.running) return 'Jeda';
            if (this.timeUp) return 'Ulangi';
            if (this.remaining !== null && this.remaining < this.step.timer) return 'Lanjutkan';
            return 'Mulai timer';
        },

        get timerHint() {
            if (this.running) return 'Timer berjalan. Kamu tetap bisa pindah langkah kapan saja.';
            if (this.timeUp) return 'Waktunya habis. Lanjut ke langkah berikutnya.';
            return 'Tekan mulai begitu bahan masuk wajan. Ada bunyi pengingat saat waktunya habis.';
        },

        resetTimer() {
            this.running = false;
            this.remaining = this.step.timer || null;
        },

        go(i) {
            if (i < 0 || i >= this.steps.length) return;
            this.index = i;
            this.resetTimer();
        },

        prev() {
            this.go(this.index - 1);
        },

        next() {
            if (this.isLast) {
                this.finish();
            } else {
                this.go(this.index + 1);
            }
        },

        toggleTimer() {
            if (!this.step.timer) return;
            if (this.timeUp) {
                this.remaining = this.step.timer;
                this.running = true;
                return;
            }
            this.running = !this.running;
        },

        onTick() {
            if (!this.running || this.remaining === null) return;
            this.remaining -= 1;
            if (this.remaining <= 0) {
                this.remaining = 0;
                this.running = false;
                this.alarm();
            }
        },

        alarm() {
            navigator.vibrate?.([300, 150, 300]);
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [0, 0.35, 0.7].forEach((offset) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.value = 880;
                    gain.gain.setValueAtTime(0.25, ctx.currentTime + offset);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + offset + 0.3);
                    osc.connect(gain).connect(ctx.destination);
                    osc.start(ctx.currentTime + offset);
                    osc.stop(ctx.currentTime + offset + 0.3);
                });
            } catch (e) {
                // Sound is a nice-to-have; the on-screen state already says time is up.
            }
        },

        async keepAwake() {
            try {
                this.wakeLock = await navigator.wakeLock?.request('screen');
            } catch (e) {
                this.wakeLock = null;
            }
        },

        async finish() {
            this.running = false;
            this.saving = true;
            const minutes = Math.max(1, Math.round((Date.now() - this.startedAt) / 60000));
            try {
                this.timesCooked = await this.$wire.finish(minutes);
            } finally {
                this.saving = false;
                this.done = true;
                this.wakeLock?.release?.();
            }
        },

        restart() {
            this.done = false;
            this.startedAt = Date.now();
            this.go(0);
            this.keepAwake();
        },
    };
}
