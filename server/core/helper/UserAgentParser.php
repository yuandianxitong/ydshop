<?php
declare(strict_types=1);

namespace core\helper;

class UserAgentParser
{
    public static function parse(string $ua): string
    {
        if ($ua === '') {
            return 'unknown';
        }
        if (stripos($ua, 'MicroMessenger') !== false) {
            return 'mp_weixin';
        }
        if (stripos($ua, 'YDShop-App') !== false) {
            return 'app';
        }
        if (preg_match('#(iPhone|Android|iPad|Mobile)#i', $ua)) {
            return 'h5';
        }
        return 'unknown';
    }
}
