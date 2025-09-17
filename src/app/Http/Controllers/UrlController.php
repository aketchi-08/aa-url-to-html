<?php

namespace App\Http\Controllers;

use App\Models\Domain;
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

        // URLからドメインを抽出
        $host = parse_url($request->url, PHP_URL_HOST);

        if ($host) {
            // 小文字化 & www.除去
            $host = preg_replace('/^www\./i', '', strtolower($host));
        }

        // ドメインが存在しなければ作成
        $domain = Domain::firstOrCreate(
            ['name' => $host],
            ['user_id' => Auth::id()]
        );

        // HTML取得
        $client = new Client();
        $response = $client->get($request->url);
        $html = $response->getBody()->getContents();

        $filename = 'htmls/' . md5($request->url . now()) . '.html';
        Storage::put($filename, $html);

        // URL保存（domain_id 紐づけ）
        Url::create([
            'url'       => $request->url,
            'html_path' => $filename,
            'user_id'   => Auth::id(),
            'domain_id' => $domain->id,
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

        // --- URL 正規化 (www を削除) ---
        $parsed = parse_url(trim($request->url));
        $scheme = $parsed['scheme'] ?? 'http';
        $host   = $parsed['host'] ?? $parsed['path'] ?? '';
        $path   = $parsed['path'] ?? '';

        // www. を除去 & 小文字化
        $host = preg_replace('/^www\./i', '', strtolower($host));

        // 正規化したURLを再構築
        $normalizedUrl = $scheme . '://' . $host . $path;
        if (!empty($parsed['query'])) {
            $normalizedUrl .= '?' . $parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $normalizedUrl .= '#' . $parsed['fragment'];
        }

        // --- Domain の自動判定/作成 ---
        $domain = Domain::firstOrCreate(
            ['name' => $host],
            ['user_id' => auth()->id()]
        );

        // --- URLからHTMLを再取得 ---
        $client = new Client();
        $response = $client->get($normalizedUrl);
        $html = $response->getBody()->getContents();

        // --- HTMLファイルを保存 ---
        $filename = 'htmls/' . md5($normalizedUrl . now()) . '.html';
        Storage::put($filename, $html);

        // --- DB更新 ---
        $url->update([
            'url'       => $normalizedUrl,
            'html_path' => $filename,
            'domain_id' => $domain->id,
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
