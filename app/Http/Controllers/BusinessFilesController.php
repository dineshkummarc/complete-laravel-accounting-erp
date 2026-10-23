<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Core\Actions\DownloadAttachment;
use App\Modules\Core\Actions\ExportBranches;
use App\Modules\Core\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BusinessFilesController
{
    public function attachment(Request $request, string $uuid, DownloadAttachment $action): StreamedResponse
    {
        return $action->handle($request->user(), $uuid);
    }

    public function branches(Request $request, ExportBranches $action): StreamedResponse
    {
        Gate::authorize('create', Branch::class);

        return response()->streamDownload(function () use ($request, $action): void {
            $stream = fopen('php://output', 'wb');
            if ($stream !== false) {
                $action->handle($request->user(), $stream);
                fclose($stream);
            }
        }, 'branches.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
