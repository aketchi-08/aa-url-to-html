<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        $filePath = $host . $path;

        // 上書き保存
        Storage::disk('public')->put($filePath, $html);

        // DBに保存パスを更新
        $this->html_path = $filePath;
        $this->save();

        return $filePath;
    }
}
