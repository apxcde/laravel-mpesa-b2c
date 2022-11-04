<?php

namespace Apxcde\LaravelMpesaB2c\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaB2CTransaction extends Model
{
    protected $primaryKey = 'originator_conversation_id';

    protected $keyType = 'string';

    public $incrementing = false;
}
