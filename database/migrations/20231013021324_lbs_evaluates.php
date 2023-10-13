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
              `staff_id` int(10) unsigned NOT NULL COMMENT '员工id',
              `order_id` varchar(20) NOT NULL COMMENT '跟进单/服务单/合约 id',
              `order_type` tinyint(1) NOT NULL COMMENT '订单类型 1:joborder 2:followuporder',
              `customer_id` varchar(20) NOT NULL COMMENT '客户id',
              `create_time` datetime DEFAULT NULL COMMENT '创建时间',
              `update_time` datetime DEFAULT NULL COMMENT '更新时间',
              PRIMARY KEY (`id`),
              KEY `idx` (`order_id`,`order_type`) USING BTREE
            ) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COMMENT='客户签名评价表';
        ");
    }

    public function down(): void
    {
        Db::execute('DROP TABLE lbs_evaluates');
    }
}
