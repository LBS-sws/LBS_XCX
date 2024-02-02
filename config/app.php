<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 应用地址
    'app_host'         => Env::get('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app'      => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    'app_map'          => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],

    // 异常页面的模板文件
    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => true,
    // 开启多应用
    'auto_multi_app'   => true,
    //xinuapp配置
    //测试服  https://appuat.lbsapps.cn
    //正式服  https://app.lbsapps.cn
    'uapp_url'   => 'https://appuat.lbsapps.cn',
    //新U 接口列表
    'uapi_list' =>[
        'edit_token'=>'/web/ajax/editJobToken.php', //编辑新U token
        'edit_job_status' =>'/web/ajax/editJobStatus.php', // 编辑工作单状态
        'edit_remarks' =>'/web/ajax/editTechRemarks.php', // 编辑技术员备注
    ],
    //智能设备接口
    'smarttech_mousetrap_device_api' =>[
        'SigfoxDevice'=>'https://rodent.lbs-smarttech.com/api/sigfox-device-list-mobile',
        'NbiotDevice' =>'https://rodent.lbs-smarttech.com/api/nbiot-device-list-mobile',
    ],
    'smarttech_mousetrap_trigger_api' =>[
        'Sigfox_trigger'=>'https://rodent.lbs-smarttech.com/api/sigfox-msg-mobile',
        'Nbiot_trigger' =>'https://rodent.lbs-smarttech.com/api/nbiot-msg-mobile',
    ],
    'smarttech_mousetrap_client_api' =>[
        'client'=>'https://rodent.lbs-smarttech.com/api/estate-mobile'
    ],
];
