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

    public function saveHtmlWithTemplateAndAssets(string $html)
    {
        $parsed = parse_url($this->url);
        $host = preg_replace('/^www\./i', '', strtolower($parsed['host'] ?? ''));
        $path = $parsed['path'] ?? '/';

        if (substr($path, -1) === '/') {
            $path .= 'index';
        }
        if (!str_ends_with($path, '.html')) {
            $path .= '.html';
        }

        // HTML 保存先
        $filePath = "htmls/{$host}/" . ltrim($path, '/');

        // --- HTML解析 ---
        $crawler = new Crawler($html);

        // 抽出対象部分
        $contentNode = $crawler->filter('article.style-itrjxe');
        $extractedHtml = $contentNode->count() ? $contentNode->html() : '';

        // --- 不要なdivを削除 ---
        $crawler2 = new Crawler($extractedHtml);
        $crawler2->filter('div.style-rwy56f')->each(function (Crawler $node) use (&$extractedHtml) {
            // 削除するために HTML を置換
            $extractedHtml = str_replace($node->outerHtml(), '', $extractedHtml);
        });

        // --- 画像処理 ---
        $crawler = new Crawler($extractedHtml);

        $crawler->filter('img')->each(function ($node) use (&$extractedHtml, $filePath) {
            $src = $node->attr('src');
            if (!$src) return;

            // 外部URLのみ対象
            if (!preg_match('#^https?://#', $src)) return;

            $imgParsed = parse_url($src);
            $imgHost = preg_replace('/^www\./i', '', strtolower($imgParsed['host'] ?? ''));
            $imgPath = ltrim($imgParsed['path'] ?? '', '/');

            // 保存先: images/{imgHost}/path/to/file
            $savePath = "images/{$imgHost}/" . $imgPath;

            try {
                $response = Http::get($src);
                if ($response->successful()) {
                    Storage::disk('public')->put($savePath, $response->body());

                    // HTML の相対パスに変換
                    $relativePath = $this->getRelativePath($filePath, $savePath);

                    $extractedHtml = str_replace($src, $relativePath, $extractedHtml);
                }
            } catch (\Exception $e) {
                // ダウンロード失敗は無視
            }
        });

        // --- テンプレート読み込み ---
        $templatePath = "template/{$host}/template.html";
        if (!Storage::disk('public')->exists($templatePath)) {
            throw new \Exception("テンプレートが存在しません: {$templatePath}");
        }
        $templateHtml = Storage::disk('public')->get($templatePath);

        // --- 埋め込み ---
        $finalHtml = str_replace('{{ content }}', $extractedHtml, $templateHtml);

        // --- HTML 保存 ---
        Storage::disk('public')->put($filePath, $finalHtml);

        $this->html_path = $filePath;
        $this->save();

        return $filePath;
    }

    /**
     * 保存先HTMLから画像までの相対パスを計算
     */
    private function getRelativePath(string $fromHtml, string $toAsset): string
    {
        $fromParts = explode('/', dirname($fromHtml));
        $toParts   = explode('/', $toAsset);

        while (count($fromParts) && count($toParts) && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativeParts = array_fill(0, count($fromParts), '..');
        return implode('/', array_merge($relativeParts, $toParts));
    } 
}
