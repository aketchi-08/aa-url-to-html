<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::withCount('urls')->latest()->paginate(10);
        return view('domains.index', compact('domains'));
    }

    public function create()
    {
        return view('domains.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:domains,name',
        ]);

        Domain::create([
            'name' => $request->name,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('domains.index')->with('success', 'ドメインを登録しました');
    }

    public function show(Domain $domain)
    {
        $urls = $domain->urls()->latest()->paginate(10);
        return view('domains.show', compact('domain', 'urls'));
    }

    public function edit(Domain $domain)
    {
        return view('domains.edit', compact('domain'));
    }

    public function update(Request $request, Domain $domain)
    {
        $request->validate([
            'name' => 'required|unique:domains,name,' . $domain->id,
        ]);

        $domain->update(['name' => $request->name]);

        return redirect()->route('domains.index')->with('success', 'ドメインを更新しました');
    }

    public function destroy(Domain $domain)
    {
        $domain->delete();
        return redirect()->route('domains.index')->with('success', 'ドメインを削除しました');
    }
}
