<?php

namespace Sungmee\LaraPay;

use Illuminate\Database\Eloquent\Model as M;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends M
{
    use SoftDeletes;

    /**
     * 需要被转换成日期的属性。
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * 应该被转换成原生类型的属性。
     *
     * @var array
     */
    protected $casts = [
        'metas' => 'array',
    ];

    /**
     * 应该在在模型数组或 JSON 中被隐藏的属性。
     *
     * @var array
     */
    protected $hidden = ['deleted_at'];


    /**
     * 获得拥有此入金的用户。
     */
    public function user()
    {
        $userModel = config('pay.userModel');
        return $this->belongsTo($userModel);
    }
}