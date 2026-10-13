<?php

namespace PHPinnacle\Eidos\Tests\Fixtures\Discovery;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_discovered_products';
}
