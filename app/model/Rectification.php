<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 整改记录模型（员工上传整改后图片）
 */
class Rectification extends Model
{
    protected $name = 'rectifications';

    protected $autoWriteTimestamp = false;

    /** 对应的问题记录 */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class, 'inspection_id');
    }

    /** 整改员工 */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
