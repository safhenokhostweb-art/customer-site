<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeCanvas\EntityLinkCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Canvas overhaul Unit 4: search for the editor's unified link picker.
 * Reads the live database on every call, so a page or product created in
 * the dashboard is offered immediately; results carry the URL the link
 * would resolve to right now.
 */
class ThemeCanvasLinkEntityController extends Controller
{
    public function __invoke(Request $request, EntityLinkCatalog $catalog): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'entity' => ['nullable', 'string', 'in:' . implode(',', array_keys(EntityLinkCatalog::ENTITIES))],
        ]);
        $entity = $data['entity'] ?? null;

        return response()->json([
            'entities' => EntityLinkCatalog::ENTITIES,
            'results' => $catalog->search((string) ($data['q'] ?? ''), $entity, $entity !== null ? 20 : 6),
        ]);
    }
}
