<?php
declare(strict_types=1);

namespace core\plugin;

class PluginManifest
{
    public const DISPLAY_INLINE    = 'inline';
    public const DISPLAY_WORKSPACE = 'workspace';

    private const ALLOWED_CATEGORIES = ['core', 'channel_market', 'value_added'];

    public string $code;
    public string $name;
    public string $version;
    public string $description = '';
    public string $author      = '';
    public string $category;
    public string $parentMenu;
    public string $displayMode;
    public string $icon        = '';
    public ?array $palette = null;
    public bool $recommended = false;
    public array $requires = [];
    public array $menus       = [];
    public array $permissions = [];
    public array $routes = [];
    public string $events = '';
    public array $raw = [];

    public static function fromArray(array $data): self
    {
        foreach (['code', 'name', 'version', 'category', 'parent_menu'] as $required) {
            if (empty($data[$required])) {
                throw new PluginException(
                    "manifest 缺少必填字段：$required",
                    PluginException::ERR_MANIFEST_INVALID
                );
            }
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $data['code'])) {
            throw new PluginException(
                "code 必须以小写字母开头、仅含小写字母数字下划线：{$data['code']}",
                PluginException::ERR_MANIFEST_INVALID
            );
        }

        if (!preg_match('/^\d+\.\d+\.\d+(?:-[\w\.]+)?$/', $data['version'])) {
            throw new PluginException(
                "version 必须为 SemVer 格式：{$data['version']}",
                PluginException::ERR_MANIFEST_INVALID
            );
        }

        if (!in_array($data['category'], self::ALLOWED_CATEGORIES, true)) {
            throw new PluginException(
                "category 必须为以下之一：" . implode(',', self::ALLOWED_CATEGORIES),
                PluginException::ERR_MANIFEST_INVALID
            );
        }

        $m = new self();
        $m->code        = $data['code'];
        $m->name        = $data['name'];
        $m->version     = $data['version'];
        $m->description = $data['description'] ?? '';
        $m->author      = $data['author'] ?? '';
        $m->category    = $data['category'];
        $m->parentMenu  = $data['parent_menu'];
        $m->displayMode = $data['display_mode']
            ?? ($data['parent_menu'] === 'Plugin' ? self::DISPLAY_WORKSPACE : self::DISPLAY_INLINE);
        $m->icon        = $data['icon'] ?? '';
        $m->palette     = $data['palette'] ?? null;
        $m->recommended = !empty($data['recommended']);
        $m->requires    = $data['requires'] ?? [];
        $m->menus       = $data['menus'] ?? [];
        $m->permissions = $data['permissions'] ?? [];
        $m->routes      = $data['routes'] ?? [];
        $m->events      = $data['events'] ?? '';
        $m->raw         = $data;

        return $m;
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new PluginException("manifest 文件不存在：$path", PluginException::ERR_MANIFEST_NOT_FOUND);
        }
        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            throw new PluginException("manifest 不是合法 JSON：$path", PluginException::ERR_MANIFEST_INVALID);
        }
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return $this->raw;
    }
}
