<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('finance/accounts/Index', [
            'accounts' => Account::query()
                ->with('parent:id,name')
                ->orderBy('code')
                ->get()
                ->map(fn (Account $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'type' => $a->type,
                    'parent' => $a->parent?->name,
                    'balance' => $a->balance(),
                    'is_active' => $a->is_active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('account.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
        ]);

        Account::create($data);

        return back()->with('success', 'Account created.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        abort_unless($request->user()->can('account.manage'), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code,'.$account->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'is_active' => ['boolean'],
        ]);

        $account->update($data);

        return back()->with('success', 'Account updated.');
    }
}
