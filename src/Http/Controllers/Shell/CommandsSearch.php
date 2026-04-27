<?php

namespace ChiefTools\SDK\Http\Controllers\Shell;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use ChiefTools\SDK\UI\CommandPalette\Query;
use ChiefTools\SDK\UI\CommandPalette\Manager;

class CommandsSearch
{
    public function __invoke(Request $request, Manager $commands): JsonResponse
    {
        $data = $request->validate([
            'q'     => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = new Query(
            query: trim((string)($data['q'] ?? '')),
            limit: (int)($data['limit'] ?? 8),
        );

        return response()->json([
            'data' => $commands->search($query)
                ->map(static fn ($item): array => $item->toArray())
                ->values(),
        ]);
    }
}
