<?php

namespace App\Models;

use CodeIgniter\Model;

abstract class BaseAppModel extends Model
{
    protected $useTimestamps = true;
    protected $returnType = 'array';
}
