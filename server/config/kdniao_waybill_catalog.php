<?php
declare(strict_types=1);

/**
 * 快递鸟电子面单：常用公司业务类型（ExpType）与模版尺寸（TemplateSize）目录。
 * 对照快递鸟《快递公司快递业务类型》与技术文档 6.2 电子面单模板规格整理。
 *
 * @return array<string, array{name:string, exp_types:list<array{value:string,label:string}>, template_sizes:list<array{value:string,label:string}>}>
 */
return [
    'SF' => [
        'name' => '顺丰速运',
        'exp_types' => [
            ['value' => '1', 'label' => '顺丰特快'],
            ['value' => '2', 'label' => '顺丰标快'],
            ['value' => '6', 'label' => '顺丰即日'],
            ['value' => '111', 'label' => '冷运特快'],
            ['value' => '112', 'label' => '冷运标快'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '150', 'label' => '二联 100×150'],
            ['value' => '180', 'label' => '二联 100×180'],
            ['value' => '210', 'label' => '三联 100×210'],
        ],
    ],
    'YTO' => [
        'name' => '圆通速递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
            ['value' => '2', 'label' => '圆准达'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '180', 'label' => '二联 100×180'],
        ],
    ],
    'ZTO' => [
        'name' => '中通快递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
            ['value' => '21', 'label' => '中通好快'],
            ['value' => '22', 'label' => '中通标快'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '1301', 'label' => '一联 76×130（好快/标快）'],
            ['value' => '180', 'label' => '二联 100×180'],
        ],
    ],
    'STO' => [
        'name' => '申通快递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '1301', 'label' => '一联 76×130（新）'],
            ['value' => '180', 'label' => '二联 100×180'],
            ['value' => '18003', 'label' => '三联 100×180'],
        ],
    ],
    'YD' => [
        'name' => '韵达速递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '180', 'label' => '二联 100×180'],
        ],
    ],
    'HTKY' => [
        'name' => '百世快递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '180', 'label' => '二联 100×180'],
        ],
    ],
    'JD' => [
        'name' => '京东快递',
        'exp_types' => [
            ['value' => '1', 'label' => '京东标快'],
            ['value' => '2', 'label' => '京东特快'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '110', 'label' => '二联 100×110'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '1101', 'label' => '二联 100×110（隐私）'],
            ['value' => '1301', 'label' => '一联 76×130（隐私）'],
        ],
    ],
    'EMS' => [
        'name' => 'EMS',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '1301', 'label' => '一联 76×130（新）'],
            ['value' => '1501', 'label' => '二联 100×150'],
            ['value' => '1801', 'label' => '二联 100×180'],
        ],
    ],
    'JTSD' => [
        'name' => '极兔速递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版（二联 180）'],
            ['value' => '130', 'label' => '一联 76×130'],
        ],
    ],
    'UC' => [
        'name' => '优速快递',
        'exp_types' => [
            ['value' => '1', 'label' => '标准快递'],
        ],
        'template_sizes' => [
            ['value' => '', 'label' => '默认模版'],
            ['value' => '130', 'label' => '一联 76×130'],
            ['value' => '180', 'label' => '二联 100×180'],
        ],
    ],
];
