<?php
declare(strict_types=1);

namespace DbMigrations;

use Phinx\Migration\AbstractMigration;

final class LbsEvaluates extends AbstractMigration
{
    public function up(): void
    {
        Db::execute("
            CREATE TABLE `lbs_evaluates` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '客户签名评价表主键id',
              `question` mediumtext NOT NULL COMMENT '问题内容',
              `score` int(3) NOT NULL DEFAULT '0' COMMENT '问题得分',
              `total_score` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '问题总分',
              `staff_id` varchar(32) unsigned NOT NULL COMMENT '员工id',
              `order_id` varchar(20) NOT NULL COMMENT '跟进单/服务单/合约 id',
              `order_type` tinyint(1) NOT NULL COMMENT '订单类型 1:joborder 2:followuporder',
              `customer_id` varchar(20) NOT NULL COMMENT '客户id',
              `create_time` datetime DEFAULT NULL COMMENT '创建时间',
              `update_time` datetime DEFAULT NULL COMMENT '更新时间',
              PRIMARY KEY (`id`),
              KEY `idx` (`order_id`,`order_type`) USING BTREE
            ) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COMMENT='客户签名评价表';
        ");

        //修改字段
        Db::execute("
            ALTER TABLE `lbs_xcx`.`lbs_evaluates` MODIFY COLUMN `question` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '问题内容' AFTER `id`;
        ");

        //添加触发器
        Db::execute("
            CREATE TRIGGER `trg_ins_lbs_evaluates` AFTER INSERT ON `lbs_evaluates` FOR EACH ROW BEGIN
              INSERT INTO datasync.sync_data_log(db_id, tab_id, action_id, json_data)
              VALUES ('lbs_xcx', 'lbs_evaluates', 'insert', JSON_OBJECT(
                'id',NEW.id,'question',NEW.question,'score',NEW.score,'total_score',NEW.total_score,'staff_id',NEW.staff_id,'order_id',NEW.order_id,'order_type',NEW.order_type,'customer_id',NEW.customer_id,'create_time',NEW.create_time,'update_time',NEW.update_time
              ));
            END;
            
            CREATE TRIGGER `trg_upd_lbs_evaluates` AFTER UPDATE ON `lbs_evaluates` FOR EACH ROW BEGIN
              INSERT INTO datasync.sync_data_log(db_id, tab_id, action_id, json_data)
              VALUES ('lbs_xcx', 'lbs_evaluates', 'update', JSON_OBJECT(
                'id',NEW.id,'question',NEW.question,'score',NEW.score,'total_score',NEW.total_score,'staff_id',NEW.staff_id,'order_id',NEW.order_id,'order_type',NEW.order_type,'customer_id',NEW.customer_id,'create_time',NEW.create_time,'update_time',NEW.update_time
              ));
            END;
            
            CREATE TRIGGER `trg_del_lbs_evaluates` BEFORE DELETE ON `lbs_evaluates` FOR EACH ROW BEGIN
              INSERT INTO datasync.sync_data_log(db_id, tab_id, action_id, json_data)
              VALUES ('lbs_xcx', 'lbs_evaluates', 'delete', JSON_OBJECT(
                'id',OLD.id,'question',OLD.question,'score',OLD.score,'total_score',OLD.total_score,'staff_id',OLD.staff_id,'order_id',OLD.order_id,'order_type',OLD.order_type,'customer_id',OLD.customer_id,'create_time',OLD.create_time,'update_time',OLD.update_time
              ));
            END;
        ");
    }

    public function down(): void
    {
        Db::execute('DROP TABLE lbs_evaluates');

        //修改字段
        Db::execute("
            ALTER TABLE `lbs_xcx`.`lbs_evaluates` MODIFY COLUMN `question` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '问题内容' AFTER `id`;
        ");
    }
}
