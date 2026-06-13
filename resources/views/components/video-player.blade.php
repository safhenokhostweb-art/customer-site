@props([
    'video',        // HomepageVideo model
    'height' => '60vh',
    'class'  => '',
])
@php
    $type      = $video->video_type;
    $embedUrl  = $video->embedUrl();
    $ytId      = $video->youtubeId();
    $playerId  = 'vp_' . $video->id . '_' . uniqid();
    $opacity   = $video->overlay_opacity ?? 30;
    $thumbnail = $video->thumbnail_url
        ? \App\Helpers\ImageHelper::url($video->thumbnail_url)
        : ($ytId ? "https://img.youtube.com/vi/{$ytId}/maxresdefault.jpg" : null);
@endphp

<div id="{{ $playerId }}"
     class="vp-root relative overflow-hidden rounded-2xl bg-black {{ $class }}"
     style="height:{{ $height }}; min-height:260px;"
     x-data="videoPlayer('{{ $playerId }}', '{{ $type }}', @js($embedUrl), {{ $video->autoplay ? 'true' : 'false' }}, {{ $video->muted ? 'true' : 'false' }})">

    {{-- YouTube / Cloudflare iframe --}}
    @if($type !== 'mp4')
    <iframe
        x-ref="iframe"
        x-show="started"
        class="absolute inset-0 w-full h-full border-0"
        :src="iframeSrc"
        allow="autoplay; fullscreen; picture-in-picture"
        allowfullscreen
        loading="lazy">
    </iframe>
    @else
    {{-- Direct MP4/WebM --}}
    <video
        x-ref="mp4"
        x-show="started"
        class="absolute inset-0 w-full h-full object-cover"
        :muted="muted"
        :loop="{{ $video->loop ? 'true' : 'false' }}"
        playsinline
        preload="metadata">
        <source src="{{ $embedUrl }}" type="video/mp4">
        <source src="{{ $embedUrl }}" type="video/webm">
    </video>
    @endif

    {{-- Overlay (only when not started) --}}
    <template x-if="!started">
        <div class="absolute inset-0 flex flex-col items-center justify-center z-10 cursor-pointer"
             @click="play()">
            {{-- Background thumbnail --}}
            @if($thumbnail)
            <div class="absolute inset-0 bg-cover bg-center"
                 style="background-image:url('{{ $thumbnail }}')"></div>
            @endif
            {{-- Dark overlay --}}
            <div class="absolute inset-0" style="background:rgba(0,0,0,{{ $opacity / 100 }});"></div>

            {{-- Content --}}
            <div class="relative z-10 text-center px-6">
                @if($video->overlay_text)
                <p class="text-white text-lg md:text-2xl font-bold mb-4 drop-shadow">{{ $video->overlay_text }}</p>
                @endif

                {{-- Play button --}}
                <button type="button"
                        class="w-20 h-20 rounded-full flex items-center justify-center transition-transform duration-200 hover:scale-110 shadow-2xl"
                        style="background:rgba(255,255,255,0.18); border:3px solid rgba(255,255,255,0.6); backdrop-filter:blur(4px);"
                        aria-label="پخش ویدیو">
                    <svg class="w-10 h-10 text-white mr-[-4px]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>

                @if($video->title)
                <p class="text-white/90 text-base mt-4 font-semibold drop-shadow">{{ $video->title }}</p>
                @endif
                @if($video->subtitle)
                <p class="text-white/70 text-sm mt-1">{{ $video->subtitle }}</p>
                @endif
            </div>
        </div>
    </template>

    {{-- Controls bar (mp4 only — shown after started) --}}
    @if($type === 'mp4')
    <div x-show="started"
         class="absolute bottom-0 inset-x-0 z-20 px-4 py-3 flex items-center gap-3"
         style="background:linear-gradient(to top,rgba(0,0,0,.7),transparent);">
        {{-- Play/Pause --}}
        <button type="button" @click="togglePlay()" class="text-white hover:text-white/80 shrink-0">
            <svg x-show="!playing" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <svg x-show="playing"  class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6zm8-14v14h4V5z"/></svg>
        </button>
        {{-- Progress --}}
        <input type="range" min="0" max="100" step="0.1"
               :value="progress"
               @input="seekTo($event.target.value)"
               class="flex-1 h-1 accent-white cursor-pointer">
        {{-- Time --}}
        <span class="text-white text-xs font-mono shrink-0" x-text="timeStr"></span>
        {{-- Volume --}}
        <button type="button" @click="toggleMute()" class="text-white hover:text-white/80 shrink-0">
            <svg x-show="!muted" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6l-4 4H5v4h3l4 4V6zM19.07 4.93a10 10 0 010 14.14"/></svg>
            <svg x-show="muted"  class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
        </button>
        {{-- Fullscreen --}}
        <button type="button" @click="requestFullscreen()" class="text-white hover:text-white/80 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
        </button>
    </div>
    @endif

    {{-- CTA overlay (after playing) --}}
    @if($video->cta_text && $video->cta_url)
    <div x-show="started" class="absolute top-4 left-4 z-20">
        <a href="{{ $video->cta_url }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold shadow-lg transition hover:opacity-90"
           style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.3); color:#fff;">
            {{ $video->cta_text }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
function videoPlayer(uid, type, embedUrl, autoplay, initMuted) {
    return {
        started:  autoplay,
        playing:  false,
        muted:    initMuted,
        progress: 0,
        timeStr:  '0:00',
        iframeSrc: autoplay ? embedUrl : '',

        play() {
            this.started  = true;
            this.iframeSrc = embedUrl;
            if (type === 'mp4') {
                this.$nextTick(() => {
                    var v = this.$refs.mp4;
                    if (v) { v.muted = this.muted; v.play().catch(() => {}); this.playing = true; }
                });
            }
        },

        togglePlay() {
            var v = this.$refs.mp4;
            if (!v) return;
            if (v.paused) { v.play(); this.playing = true; }
            else          { v.pause(); this.playing = false; }
        },

        toggleMute() {
            var v = this.$refs.mp4;
            this.muted = !this.muted;
            if (v) v.muted = this.muted;
        },

        seekTo(pct) {
            var v = this.$refs.mp4;
            if (v && v.duration) { v.currentTime = v.duration * pct / 100; }
        },

        requestFullscreen() {
            var el = document.getElementById(uid);
            if (el && el.requestFullscreen) el.requestFullscreen();
        },

        fmtTime(s) {
            s = Math.floor(s || 0);
            return Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
        },

        initMp4() {
            var v = this.$refs.mp4;
            if (!v) return;
            v.addEventListener('timeupdate', () => {
                if (v.duration) { this.progress = v.currentTime / v.duration * 100; }
                this.timeStr = this.fmtTime(v.currentTime) + ' / ' + this.fmtTime(v.duration);
            });
            v.addEventListener('play',  () => this.playing = true);
            v.addEventListener('pause', () => this.playing = false);
            v.addEventListener('ended', () => this.playing = false);
        },

        init() {
            if (type === 'mp4') {
                this.$nextTick(() => this.initMp4());
            }
            if (autoplay && type === 'mp4') {
                this.$nextTick(() => { var v = this.$refs.mp4; if (v) { v.muted = true; v.play().catch(()=>{}); } });
            }
        }
    };
}
</script>
@endpush
@endonce
