{{-- Phase 0.4 (ADR-063): the local Vazirmatn faces now live in the shared
     theme-runtime package, so this host and the platform Canvas preview load
     the same fonts the same way. Output is unchanged. --}}
@include('theme-runtime::css.fonts')
