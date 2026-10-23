<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Core\Actions\SwitchBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BusinessSelectionController
{
    public function branch(Request $request, SwitchBranch $action): RedirectResponse
    {
        $data = $request->validate(['branch_id' => 'required|integer']);
        $action->handle($request->user(), (int) $data['branch_id']);

        return redirect('/app');
    }

    public function language(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => 'required|in:en,bn']);
        $request->user()->forceFill(['locale' => $data['locale']])->save();

        return redirect('/app');
    }
}
