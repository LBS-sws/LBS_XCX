<?php
declare (strict_types = 1);

namespace app\technician\controller;
use think\cache\driver\Redis;
use think\facade\Db;


class CheckLockStatus
{
    /**
     * 判断有没有加锁
     * */
    public function index($id = '111',$staff_id = '403527')
    {
        if(!(empty($id) || empty($staff_id))){
            $redis = new Redis();
            //进入页面后就开始获取 lock_key
            $lock_key = 'lock_equipment_'.$id;
            $lock_content = $redis->has($lock_key);
            // 判断 如果为空的话 就加锁
            if(!$lock_content){
                $is_locked = $redis->set($lock_key,$staff_id,3600*24);
                if($is_locked){
                    return success(0,'success',[]);
                }
            }else{
                $lock_content = $redis->get($lock_key);
                if($staff_id == $lock_content){
                    //继续执行 并且保持锁
                    return success(0,'success',[]);
                }else{
                    //告诉其他人，当前设备已经有人操作了
                    return error(-1,'此设备已锁定,ID:【'.$id.'】-'.$staff_id);
                }
            }
        }
    }
    /**
     * 编辑完成 解锁
     * */
    public function lock($id = '111',$staff_id = '403527'){
        $redis = new Redis();
        //进入页面后就开始获取 lock_key
        $lock_key = 'lock_equipment_'.$id;
        $lock_content = $redis->has($lock_key);
        if($lock_content){
            $redis->delete($lock_key);
            return success(0,'success',[]);
        }else{
            return error(-1,"请稍后再试！");
        }
    }

}
