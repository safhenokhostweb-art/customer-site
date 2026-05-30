<script>
/**
 * ChatSound — Web Audio API sound utility (no external files).
 * Usage: window.CS.play('send' | 'receive' | 'new_ticket' | 'notify')
 */
window.CS = (function () {
    var _ctx = null;
    var _enabled = localStorage.getItem('cs_sounds') !== '0';

    function ctx() {
        if (!_ctx) {
            try { _ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
        }
        if (_ctx && _ctx.state === 'suspended') {
            _ctx.resume();
        }
        return _ctx;
    }

    /* Each step: [frequency_hz, start_offset_sec, duration_sec, volume] */
    var sounds = {
        send:       [[880, 0, 0.07, .18], [1100, 0.06, 0.09, .14]],
        receive:    [[660, 0, 0.10, .22], [880, 0.09, 0.13, .20]],
        new_ticket: [[440, 0, 0.09, .28], [550, 0.11, 0.09, .26], [660, 0.22, 0.14, .28]],
        notify:     [[528, 0, 0.13, .24], [660, 0.11, 0.16, .22]],
    };

    function play(type) {
        if (!_enabled) return;
        var c = ctx();
        if (!c) return;
        try {
            var steps = sounds[type] || sounds.receive;
            var now = c.currentTime;
            steps.forEach(function (s) {
                var freq = s[0], delay = s[1], dur = s[2], vol = s[3];
                var osc = c.createOscillator();
                var gain = c.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                osc.connect(gain);
                gain.connect(c.destination);
                gain.gain.setValueAtTime(0.001, now + delay);
                gain.gain.linearRampToValueAtTime(vol, now + delay + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + delay + dur);
                osc.start(now + delay);
                osc.stop(now + delay + dur + 0.05);
            });
        } catch (e) {}
    }

    function toggle() {
        _enabled = !_enabled;
        localStorage.setItem('cs_sounds', _enabled ? '1' : '0');
        return _enabled;
    }

    function isEnabled() { return _enabled; }

    /* Unlock AudioContext on first user interaction (browsers require it) */
    function unlock() {
        var c = ctx();
        if (c && c.state === 'suspended') c.resume();
        document.removeEventListener('click', unlock);
        document.removeEventListener('keydown', unlock);
    }
    document.addEventListener('click', unlock, { once: true });
    document.addEventListener('keydown', unlock, { once: true });

    return { play: play, toggle: toggle, isEnabled: isEnabled };
})();
</script>
