<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentForm extends Model
{
    protected $fillable = [
        'name',
        'email',
        'secret',
        'email_verified_at',
        'email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }
}
