<?php

namespace app\technician\model;

use think\Model;

class Risks extends Model
{
    protected $table = 'lbs_service_risks';

    public function getCtAttr($value,$data)
    {
        return date('Y-m-d',strtotime($data['creat_time']));
    }

    public function getSiteImgAttr($value,$data)
    {
        return !empty($data['site_photos']) ? explode(',',$data['site_photos']) : [] ;
    }
}
