<?php

namespace App\Http\Controllers;

use App\Models\RemoveSelector;
use Illuminate\Http\Request;

class RemoveSelectorController extends Controller
{
    public function store(Request $request) {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'selector' => 'required|string',
        ]);
        RemoveSelector::create($request->all());
        return back();
    }

    public function update(Request $request, RemoveSelector $removeSelector) {
        $request->validate(['selector' => 'required|string']);
        $removeSelector->update($request->all());
        return back();
    }

    public function destroy(RemoveSelector $removeSelector) {
        $removeSelector->delete();
        return back();
    }
}
