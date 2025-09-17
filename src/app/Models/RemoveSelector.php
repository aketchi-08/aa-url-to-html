<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoveSelector extends Model
{
    use HasFactory;

    protected $fillable = ['domain_id', 'selector'];

    public function domain() {
        return $this->belongsTo(Domain::class);
    }
}
