<?php
declare(strict_types=1);

namespace DbMigrations;

use Phinx\Migration\AbstractMigration;

final class LbsReportAutographV2 extends AbstractMigration
{

    public function up(): void
    {
        //修改字段 int最多10长度，11没意义，而UNSIGNED无符号可以使int(10)数据量翻倍；另外int(1)的数据占位等同于int(10),所以改为tinyint(1)
        Db::execute("
            ALTER TABLE `lbs_xcx`.`lbs_report_autograph_v2`
            MODIFY COLUMN `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
            MODIFY COLUMN `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父id' AFTER `id`,
            MODIFY COLUMN `job_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '服务id(工作单或跟进单id)' AFTER `pid`,
            MODIFY COLUMN `job_type` tinyint(1) NULL DEFAULT NULL COMMENT '服务类型编号（1-工作单，2跟进单）' AFTER `job_id`,
            MODIFY COLUMN `conversion_flag` tinyint(1) UNSIGNED ZEROFILL NULL DEFAULT 1 COMMENT '1未转换，0已转换' AFTER `customer_signature`;
        ");
    }

    public function down():void{
        Db::execute("
            ALTER TABLE `lbs_xcx`.`lbs_report_autograph_v2`
            MODIFY COLUMN `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
            MODIFY COLUMN `pid` int(11)  NOT NULL DEFAULT 0 COMMENT '父id' AFTER `id`,
            MODIFY COLUMN `job_id` int(11) NULL DEFAULT NULL COMMENT '服务id(工作单或跟进单id)' AFTER `pid`,
            MODIFY COLUMN `job_type` int(1) NULL DEFAULT NULL COMMENT '服务类型编号（1-工作单，2跟进单）' AFTER `job_id`,
            MODIFY COLUMN `conversion_flag` int(1) ZEROFILL NULL DEFAULT 1 COMMENT '1未转换，0已转换' AFTER `customer_signature`;
        ");
    }
}
