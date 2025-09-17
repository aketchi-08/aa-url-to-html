<?php

namespace App\Http\Controllers;

use App\Models\ExtractSelector;
use Illuminate\Http\Request;

class ExtractSelectorController extends Controller
{
    public function store(Request $request) {
        $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'selector' => 'required|string',
        ]);
        ExtractSelector::create($request->all());
        return back();
    }

    public function update(Request $request, ExtractSelector $extractSelector) {
        $request->validate(['selector' => 'required|string']);
        $extractSelector->update($request->all());
        return back();
    }

    public function destroy(ExtractSelector $extractSelector) {
        $extractSelector->delete();
        return back();
    }
}
