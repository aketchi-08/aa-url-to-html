<?php

namespace App\Http\Controllers;

use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class UrlController extends Controller
{
    /**
     * 一覧
     */
    public function index()
    {
        $urls = Url::with('user')->latest()->paginate(10);
        return view('urls.index', compact('urls'));
    }

    /**
     * 新規登録フォーム
     */
    public function create()
    {
        return view('urls.create');
    }

    /**
     * 保存処理
     */
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        // URLからHTMLを取得
        $client = new Client();
        $response = $client->get($request->url);
        $html = $response->getBody()->getContents();

        // HTMLファイルを保存
        $filename = 'htmls/' . md5($request->url . now()) . '.html';
        Storage::put($filename, $html);

        // DBに保存
        Url::create([
            'url'       => $request->url,
            'html_path' => $filename,
            'user_id'   => Auth::id(),
        ]);

        return redirect()->route('urls.index')->with('success', 'URLを保存しました');
    }

    /**
     * HTMLプレビュー
     */
    public function show(Url $url)
    {
        return view('urls.show', compact('url'));
    }

    /**
     * HTMLファイルを直接返す（iframe用 or ダウンロード用）
     */
    public function download($id)
    {
        $url = Url::findOrFail($id);

        if (!$url->html_path || !Storage::exists($url->html_path)) {
            abort(404, 'HTMLファイルが存在しません');
        }

        $html = Storage::get($url->html_path);
        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * 編集フォーム
     */
    public function edit(Url $url)
    {
        return view('urls.edit', compact('url'));
    }

    /**
     * 更新処理（URLを変更 → HTML再取得）
     */
    public function update(Request $request, Url $url)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        // URLからHTMLを再取得
        $client = new Client();
        $response = $client->get($request->url);
        $html = $response->getBody()->getContents();

        // HTMLファイルを保存
        $filename = 'htmls/' . md5($request->url . now()) . '.html';
        Storage::put($filename, $html);

        // DB更新
        $url->update([
            'url'       => $request->url,
            'html_path' => $filename,
        ]);

        return redirect()->route('urls.index')->with('success', 'URLを更新しました');
    }

    /**
     * 削除処理
     */
    public function destroy(Url $url)
    {
        // HTMLファイルも削除
        if ($url->html_path && Storage::exists($url->html_path)) {
            Storage::delete($url->html_path);
        }

        $url->delete();

        return redirect()->route('urls.index')->with('success', 'URLを削除しました');
    }
}
