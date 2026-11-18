<style id="auth-solar-industrial-css">
    :root {
        --auth-solar-primary: #FF7900;
        --auth-solar-primary-hover: #E06600;
        --auth-solar-primary-soft: rgba(255, 121, 0, 0.2);
        --auth-solar-bg: #0B0B0B;
        --auth-solar-surface: #1E1E1E;
        --auth-solar-surface-card: #151515;
        --auth-solar-input: #12121F;
        --auth-solar-border: #2A2A2A;
        --auth-solar-border-hover: rgba(255, 121, 0, 0.5);
        --auth-solar-text: #F5F5F5;
        --auth-solar-text-muted: #A3A3A3;
        --auth-login-primary: #FF7900;
        --auth-login-primary-soft: rgba(255, 121, 0, 0.2);
        --auth-login-hero-end: #050508;
    }
    .auth-login-body {
        margin: 0;
        min-height: 100vh;
        font-family: Vazirmatn, Tahoma, sans-serif;
        background: var(--auth-solar-bg) !important;
        color: var(--auth-solar-text) !important;
    }
    .auth-login-grid {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1fr;
    }
    @media (min-width: 900px) {
        .auth-login-grid {
            grid-template-columns: minmax(440px, 1.05fr) minmax(0, 1.2fr);
        }
    }
    .auth-login-panel {
        background: var(--auth-solar-bg) !important;
        padding: 2rem 1.25rem 2.5rem;
    }
    .auth-login-brand span {
        font-weight: 800 !important;
        font-size: 1.25rem !important;
        color: #fff !important;
        letter-spacing: -0.02em !important;
    }
    .auth-login-brand span strong {
        color: var(--auth-solar-primary) !important;
    }
    .auth-login-card {
        background: var(--auth-solar-surface) !important;
        border-radius: 1.25rem !important;
        padding: 2rem 1.75rem 1.75rem !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), 0 0 0 1px var(--auth-solar-border) !important;
        border: 1px solid var(--auth-solar-border) !important;
    }
    .auth-login-card h1 {
        font-size: 1.4rem !important;
        font-weight: 800 !important;
        color: #fff !important;
    }
    .auth-login-card .auth-login-lead {
        color: var(--auth-solar-text-muted) !important;
        font-size: 0.875rem !important;
    }
    .auth-login-body .form-label {
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        color: var(--auth-solar-text) !important;
    }
    .auth-login-body .form-control {
        width: 100% !important;
        padding: 0.75rem 0.95rem !important;
        background: var(--auth-solar-input) !important;
        border: 1px solid var(--auth-solar-border) !important;
        border-radius: 0.75rem !important;
        font-size: 1rem !important;
        color: #fff !important;
        transition: all 0.2s ease !important;
    }
    .auth-login-body .form-control::placeholder {
        color: #666 !important;
    }
    .auth-login-body .form-control:focus {
        outline: none !important;
        border-color: var(--auth-solar-primary) !important;
        box-shadow: 0 0 0 3px var(--auth-solar-primary-soft) !important;
        background: #181828 !important;
    }
    .auth-login-remember {
        color: var(--auth-solar-text-muted) !important;
        font-size: 0.875rem !important;
    }
    .auth-login-remember input {
        accent-color: var(--auth-solar-primary) !important;
    }
    .auth-login-body .btn-primary {
        background: linear-gradient(135deg, var(--auth-solar-primary) 0%, var(--auth-solar-primary-hover) 100%) !important;
        color: #fff !important;
        box-shadow: 0 4px 15px rgba(255, 121, 0, 0.35) !important;
        padding: 0.8rem 1.5rem !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
    }
    .auth-login-body .btn-primary:hover {
        box-shadow: 0 6px 20px rgba(255, 121, 0, 0.5) !important;
        opacity: 1 !important;
    }
    .auth-login-body .btn-primary:active {
        transform: translateY(0) !important;
    }
    .auth-login-body .alert-danger {
        background: rgba(239, 68, 68, 0.15) !important;
        border: 1px solid rgba(239, 68, 68, 0.4) !important;
        color: #fca5a5 !important;
    }
    .auth-login-body .alert-success {
        background: rgba(34, 197, 94, 0.15) !important;
        border: 1px solid rgba(34, 197, 94, 0.4) !important;
        color: #86efac !important;
    }
    .auth-login-footer-text {
        color: var(--auth-solar-text-muted) !important;
        font-size: 0.875rem !important;
    }
    .auth-login-footer-text a {
        color: var(--auth-solar-primary) !important;
        font-weight: 700 !important;
    }
    .auth-login-hero {
        background: linear-gradient(135deg, #12121F 0%, #050508 100%) !important;
        border-right: 1px solid var(--auth-solar-border) !important;
    }
    .auth-solar-sun-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 1.5rem;
        background: rgba(255, 121, 0, 0.15);
        border: 1px solid rgba(255, 121, 0, 0.3);
        border-radius: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--auth-solar-primary);
        box-shadow: 0 0 30px rgba(255, 121, 0, 0.2);
    }
    .auth-login-hero h2 {
        font-size: 1.6rem !important;
        font-weight: 800 !important;
        color: #fff !important;
    }
    .auth-login-hero h2 span {
        color: var(--auth-solar-primary) !important;
    }
    .auth-login-hero p {
        color: var(--auth-solar-text-muted) !important;
        font-size: 0.95rem !important;
        line-height: 1.8 !important;
    }
    .auth-solar-glow {
        position: absolute;
        width: 450px;
        height: 450px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 121, 0, 0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .auth-solar-glow-1 { top: -100px; left: -100px; }
    .auth-solar-glow-2 { bottom: -100px; right: -100px; }
</style>
