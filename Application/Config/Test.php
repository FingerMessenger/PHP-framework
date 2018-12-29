<?php

namespace App\Config;

use App\Framework\Config;

class Test extends Config
{
    protected $config = array(
		'development' => array(
            'token' => 'abc'
		),
		'product' => array(
			
		)
    );
}