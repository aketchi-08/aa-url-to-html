<?php

namespace App\Models;

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

        // ディレクトリ構造を保つ
        if (substr($path, -1) === '/') {
            $path .= 'index';
        }

        // .html 拡張子を必ず付与
        if (!str_ends_with($path, '.html')) {
            $path .= '.html';
        }

        // 保存先パス（publicディスク配下）
        $filePath = 'htmls/' . $host . $path;

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

        if (substr($path, -1) === '/') $path .= 'index';
        if (!str_ends_with($path, '.html')) $path .= '.html';

        $filePath = "htmls/{$host}/" . ltrim($path, '/');

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
        $crawler2 = new Crawler($extractedHtml);
        foreach ($removeSelectors as $sel) {
            $crawler2->filter($sel)->each(function($node) use (&$extractedHtml){
                $outer = $node->getNode(0)->ownerDocument->saveHTML($node->getNode(0));
                $extractedHtml = str_replace($outer, '', $extractedHtml);
            });
        }

        // --- 画像ダウンロード & 相対パス変換 ---
        $crawler3 = new Crawler($extractedHtml);
        $crawler3->filter('img')->each(function($node) use (&$extractedHtml, $filePath) {
            $src = $node->attr('src');
            if (!$src || !preg_match('#^https?://#', $src)) return;

            $imgParsed = parse_url($src);
            $imgHost = preg_replace('/^www\./i','',$imgParsed['host'] ?? '');
            $imgPath = ltrim($imgParsed['path'] ?? '', '/');
            $savePath = "images/{$imgHost}/{$imgPath}";

            try {
                $response = Http::get($src);
                if ($response->successful()) {
                    Storage::disk('public')->put($savePath, $response->body());
                    $relativePath = $this->getRelativePath($filePath, $savePath);
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
