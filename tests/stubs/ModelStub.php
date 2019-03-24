<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\tests\stubs;

use yii\base\Model;
use yii\di\AbstractContainer;

class ModelStub extends Model
{
    public $id;
    public $title;
    public $hidden;

    public function __construct(array $config = [])
    {
        AbstractContainer::configure($this, $config);
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        return ['id' => $this->id, 'title' => $this->title];
    }
}
