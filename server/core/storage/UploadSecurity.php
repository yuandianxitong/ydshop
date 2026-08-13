<?php

/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace core\storage;

/**
 * 上传文件安全策略。
 *
 * 图片扩展名只能由服务端检测到的 MIME 类型决定；普通文件保留合法的
 * 原始扩展名，但拒绝可被 Web Server / 浏览器执行的脚本和可执行文件。
 */
final class UploadSecurity
{
    private const IMAGE_EXTENSIONS_BY_MIME = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /** @var list<string> */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * 允许公开存储的普通文件扩展名。
     *
     * 上传目录可通过 Web 访问，因此这里采用显式白名单，避免只靠危险扩展
     * 黑名单遗漏新的脚本/可执行格式。图片仍必须通过 MIME 映射进入。
     *
     * @var list<string>
     */
    private const SAFE_FILE_EXTENSIONS = [
        // 文档与表格
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt',

        // 压缩包
        'zip', 'rar', '7z', 'tar', 'gz', 'tgz',

        // 音视频
        'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac',
        'mp4', 'mov', 'avi', 'wmv', 'mpg', 'mpeg', '3gp', 'flv', 'rmvb', 'mkv', 'webm',
    ];

    /** @var list<string> */
    private const DANGEROUS_EXTENSIONS = [
        // PHP 与服务端脚本
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phtm', 'phps', 'phar',
        'cgi', 'fcgi', 'pl', 'pm', 'py', 'pyc', 'pyo', 'rb', 'lua',
        'sh', 'bash', 'zsh', 'fish', 'ksh', 'csh',
        'asp', 'aspx', 'asa', 'ashx', 'asmx', 'jsp', 'jspx', 'jspf',

        // 浏览器可执行内容
        'html', 'htm', 'shtml', 'shtm', 'xhtml', 'js', 'jse', 'mjs', 'cjs', 'svg', 'svgz',

        // 系统可执行文件与脚本
        'exe', 'dll', 'com', 'bat', 'cmd', 'msi', 'msp', 'scr',
        'ps1', 'ps1xml', 'psc1', 'psc2', 'vbs', 'vbe', 'wsf', 'wsh',
        'jar', 'war', 'ear', 'class', 'app', 'apk', 'ipa', 'so', 'dylib', 'bin', 'elf',
    ];

    /** @var list<string> */
    private const DANGEROUS_MIME_TYPES = [
        'application/x-httpd-php',
        'application/x-php',
        'text/x-php',
        'application/x-cgi',
        'application/x-perl',
        'application/x-python-code',
        'application/x-ruby',
        'application/x-sh',
        'application/x-csh',
        'text/x-shellscript',
        'application/x-executable',
        'application/x-pie-executable',
        'application/x-msdownload',
        'application/vnd.microsoft.portable-executable',
        'application/java-archive',
        'text/html',
        'application/xhtml+xml',
        'application/javascript',
        'text/javascript',
        'application/ecmascript',
        'text/ecmascript',
        'image/svg+xml',
    ];

    /**
     * 根据可信 MIME 获取图片扩展名，非允许图片返回 null。
     */
    public static function imageExtension(string $mimeType): ?string
    {
        return self::IMAGE_EXTENSIONS_BY_MIME[self::normalizeMimeType($mimeType)] ?? null;
    }

    /**
     * 解析普通上传文件的安全保存扩展名。
     *
     * 图片使用 MIME 映射；无扩展名文件使用不可执行的 dat；危险类型或
     * 伪造图片扩展名返回 null，由调用方拒绝上传。
     */
    public static function fileExtension(string $originalExtension, string $mimeType): ?string
    {
        $mimeType = self::normalizeMimeType($mimeType);
        if (in_array($mimeType, self::DANGEROUS_MIME_TYPES, true)) {
            return null;
        }

        $imageExtension = self::imageExtension($mimeType);
        if ($imageExtension !== null) {
            return $imageExtension;
        }

        $extension = self::normalizeExtension($originalExtension);
        if ($extension === '') {
            return 'dat';
        }

        if (!preg_match('/^[a-z0-9]{1,16}$/D', $extension)) {
            return null;
        }

        if (self::isDangerousExtension($extension)) {
            return null;
        }

        // jpg/png 等扩展名必须与真实图片 MIME 匹配，防止伪装文件。
        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        return in_array($extension, self::SAFE_FILE_EXTENSIONS, true) ? $extension : null;
    }

    public static function isDangerousExtension(string $extension): bool
    {
        return in_array(self::normalizeExtension($extension), self::DANGEROUS_EXTENSIONS, true);
    }

    private static function normalizeExtension(string $extension): string
    {
        return strtolower(ltrim(trim($extension), '.'));
    }

    private static function normalizeMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));
        return trim(explode(';', $mimeType, 2)[0]);
    }
}
