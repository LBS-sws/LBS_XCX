<?php

declare (strict_types = 1);

namespace app\technician\controller;

class Test{
    public function index(){
        $img = 'https://dss2.bdstatic.com/8_V1bjqh_Q23odCf/pacific/1990894160.png?x=0&y=0&h=150&w=242&vh=150.00&vw=242.00&oh=150.00&ow=242.00';
        $res = imagecreatefrom($img);
        var_dump($res);
    }
    
}