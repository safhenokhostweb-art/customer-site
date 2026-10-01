<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canvas Slot Binding (Phase 11 follow-up — Test-Layout-Validation)
    |--------------------------------------------------------------------------
    |
    | When enabled, the web middleware DecorateCanvasLayoutSlots re-renders
    | the theme chrome's <header>/<footer> through
    | App\Services\ThemeCanvas\LayoutSlotBindingService::renderSlot(), adding
    | the explicit data-layout-slot="header|footer" markers to the SAME
    | content the runtime document already produced — the chrome stays the
    | Theme's own (variant-driven), only the slot annotation layer is added.
    |
    | The flag exists so this wiring can roll out alongside the collaborator's
    | in-flight ThemeRuntimeDocument/canvas-theme work and be disabled with a
    | single toggle (no code change) if that work lands differently.
    |
    */

    'slot_binding_enabled' => env('CANVAS_SLOT_BINDING_ENABLED', true),

];
