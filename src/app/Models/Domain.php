<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public function urls()
    {
        return $this->hasMany(Url::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function extractSelectors() {
        return $this->hasMany(ExtractSelector::class);
    }

    public function removeSelectors() {
        return $this->hasMany(RemoveSelector::class);
    }

    // www を削除するアクセサ
    public function setNameAttribute($value)
    {
        // スキーム・パスを除去
        $host = parse_url(trim(strtolower($value)), PHP_URL_HOST);

        if (!$host) {
            // parse_url で取れなければそのまま
            $host = trim(strtolower($value));
        }

        // 先頭の www. を削除
        $host = preg_replace('/^www\./i', '', $host);

        $this->attributes['name'] = $host;
    }
}
