<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtractSelector extends Model
{
    use HasFactory;

    protected $fillable = ['domain_id', 'selector', 'mark'];

    public function domain() {
        return $this->belongsTo(Domain::class);
    }
}
