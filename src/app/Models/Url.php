<?php

namespace App\Models;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class Url extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'html_path',
        'user_id',
        'domain_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    // www を削除して正規化
    public function setUrlAttribute($value)
    {
        // URL全体をパース
        $parsed = parse_url(trim($value));

        // スキーム・ホスト・パスを分解
        $scheme = $parsed['scheme'] ?? 'http';
        $host   = $parsed['host'] ?? $parsed['path'] ?? ''; // host が無い場合 path に入る
        $path   = $parsed['path'] ?? '';

        // www.を除去
        $host = preg_replace('/^www\./i', '', strtolower($host));

        // URLを再構築
        $normalized = $scheme . '://' . $host . $path;

        // クエリ・フラグメントがあれば追加
        if (!empty($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }
        if (!empty($parsed['fragment'])) {
            $normalized .= '#' . $parsed['fragment'];
        }

        $this->attributes['url'] = $normalized;
    }

    /**
     * HTML をストレージに保存し、パスをDBに記録する
     * 常に上書き保存
     */
    public function saveHtml(string $html): string
    {
        $parsed = parse_url($this->url);
        $host = preg_replace('/^www\./i', '', strtolower($parsed['host'] ?? ''));
        $path = $parsed['path'] ?? '/';

        if ($path === '/' || !pathinfo($path, PATHINFO_EXTENSION)) {
            $path = rtrim($path, '/'); // 最後の / を削除
            $path .= '/_index.html';   // _index.html を付与
        }

        // $filePath = "htmls/{$host}/" . ltrim($path, '/');
        $filePath = "htmls/{$host}{$path}";

        // 上書き保存
        Storage::disk('public')->put($filePath, $html);

        // DBに保存パスを更新
        $this->html_path = $filePath;
        $this->save();

        return $filePath;
    }

    /**
     * HTML ファイルが存在するかチェック
     */
    public function getHtmlExistsAttribute(): bool
    {
        if (!$this->html_path) {
            return false;
        }
        return Storage::disk('public')->exists($this->html_path);
    }

    public function saveHtmlWithTemplateAndAssets(Domain $domain, string $html)
    {
        $parsed = parse_url($this->url);
        $host = preg_replace('/^www\./i', '', strtolower($parsed['host'] ?? ''));
        $path = $parsed['path'] ?? '/';

        if ($path === '/' || !pathinfo($path, PATHINFO_EXTENSION)) {
            $path = rtrim($path, '/'); // 最後の / を削除
            $path .= '/_index.html';   // _index.html を付与
        }

        // $filePath = "htmls/{$host}/" . ltrim($path, '/');
        $filePath = "htmls/{$host}{$path}";

        // --- 抽出部分 ---
        $crawler = new Crawler($html);
        $extractSelectors = $domain->extractSelectors->pluck('selector')->toArray();
        $extractedHtml = '';
        foreach ($extractSelectors as $sel) {
            $node = $crawler->filter($sel);
            if ($node->count()) $extractedHtml .= $node->html();
        }

        // --- 削除対象 ---
        $removeSelectors = $domain->removeSelectors->pluck('selector')->toArray();

        // $remove_target = ['source', 'img', 'meta', 'link', 'br', 'hr'];
        $remove_target = ['source'];
        foreach ($removeSelectors as $sel) {
            $lowerSel = strtolower($sel);

            // void element は正規表現で削除
            if (in_array($lowerSel, $remove_target)) {
                $extractedHtml = preg_replace("#<{$lowerSel}\b[^>]*>#i", '', $extractedHtml);
                continue;
            }

            // 通常の要素は DOM で削除
            $crawler2 = new Crawler($extractedHtml);
            $crawler2->filter($sel)->each(function ($node) use (&$extractedHtml) {
                $outer = $node->getNode(0)->ownerDocument->saveHTML($node->getNode(0));
                $extractedHtml = str_replace($outer, '', $extractedHtml);
            });
        }

        // --- 画像ダウンロード & 相対パス変換 ---
        $crawler3 = new Crawler($extractedHtml);
        $crawler3->filter('img')->each(function($node) use (&$extractedHtml, $filePath) {
            $src = $node->attr('src');
            if (!$src) return;

            // --- 絶対URLに変換（相対や / で始まるのも解決）---
            $baseUrl = $this->url;
            $absUrl  = (string) \GuzzleHttp\Psr7\UriResolver::resolve(
                new \GuzzleHttp\Psr7\Uri($baseUrl),
                new \GuzzleHttp\Psr7\Uri($src)
            );

            $imgParsed = parse_url($absUrl);
            $imgFile   = basename($imgParsed['path'] ?? 'image.png');

            // --- HTMLと同じディレクトリに保存 ---
            $htmlDir   = dirname($filePath); // 例: htmls/example.com/path/result
            $savePath  = "{$htmlDir}/{$imgFile}"; 

            try {
                $response = Http::get($absUrl);
                if ($response->successful()) {
                    Storage::disk('public')->put($savePath, $response->body());

                    // --- HTML内の参照を ./ファイル名 に変更 ---
                    $relativePath = './' . $imgFile;
                    $extractedHtml = str_replace($src, $relativePath, $extractedHtml);
                }
            } catch (\Exception $e) {}
        });

        // --- テンプレート埋め込み ---
        $templatePath = "template/{$host}/template.html";
        if (!Storage::disk('public')->exists($templatePath)) {
            throw new \Exception("テンプレートが存在しません: {$templatePath}");
        }
        $templateHtml = Storage::disk('public')->get($templatePath);
        $finalHtml = str_replace('{{ content }}', $extractedHtml, $templateHtml);

        // --- ディレクトリ作成 ---
        $dir = dirname(storage_path('app/public/' . $filePath));
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        Storage::disk('public')->put($filePath, $finalHtml);

        $this->html_path = $filePath;
        $this->save();

        return $filePath;
    }

    /** 相対パス計算 */
    private function getRelativePath(string $fromHtml,string $toAsset): string
    {
        $fromParts = explode('/',dirname($fromHtml));
        $toParts   = explode('/', $toAsset);

        while(count($fromParts)&&count($toParts)&&$fromParts[0]===$toParts[0]){
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativeParts = array_fill(0,count($fromParts),'..');
        return implode('/', array_merge($relativeParts,$toParts));
    }
}
