<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\order;

use core\base\Validate;

class OrderInvoiceValidate extends Validate
{
    protected $rule = [
        'admin_remark'    => 'max:500',
        'file_url'        => 'require|url|max:500',
        'type'            => 'in:personal,company',
        'invoice_type'    => 'in:electronic,paper',
        'title'           => 'max:120',
        'tax_no'          => 'max:40',
        'recipient_email' => 'email|max:80',
        'content'         => 'max:255',
        'status'          => 'in:pending,processing,issued,cancelled',
    ];

    protected $message = [
        'file_url.require' => '发票文件 URL 必填',
        'file_url.url'     => '发票文件 URL 不合法',
        'recipient_email.email' => '邮箱格式不正确',
    ];

    protected $scene = [
        'process' => ['admin_remark'],
        'issue'   => ['file_url', 'admin_remark'],
        'cancel'  => ['admin_remark'],
        'update'  => ['type', 'invoice_type', 'title', 'tax_no', 'recipient_email', 'content', 'status', 'admin_remark'],
    ];
}
