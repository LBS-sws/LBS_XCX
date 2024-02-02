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
            $id = json_decode($id);
            //检查是否为同一种设备
            $equipments = Db::table('lbs_service_equipments')
                ->whereIn('id',$id)
                ->column('equipment_type_id','equipment_type_id');
            if($equipments){
                //检查是否存在智能捕鼠设备
                if(in_array(245,$equipments)) return error(-1,'智能老鼠感应器不需要编辑！');
                $equipment_type_ids = array_unique($equipments);
                if(count($equipment_type_ids) > 1) return error(-1,'请选择同一种设备！');
            }
            //检查锁状态
            $redis = new Redis();
            foreach ($id as $key=>$eid){
                //进入页面后就开始获取 lock_key
                $lock_key = 'lock_equipment_'.$eid;
                $lock_content = $redis->has($lock_key);
                $msgArr=[];
                // 判断 如果为空的话 就加锁
                if(!$lock_content){
                    $redis->set($lock_key,$staff_id,3600*24);
                }else{
                    $lock_content = $redis->get($lock_key);
                    if($staff_id != $lock_content) $msgArr[] = '此设备已锁定,ID:【'.$eid.'】-'.$staff_id;
                }
            }
            if(empty($msgArr)){
                return success(0,'success',[]);
            }else{
                $msg = implode('。',$msgArr);
                return error(-1,$msg);
            }
        }
        return error(-1,'请选择设备！');
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
