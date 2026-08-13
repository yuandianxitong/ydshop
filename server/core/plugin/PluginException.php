<?php
declare(strict_types=1);

namespace core\plugin;

use Exception;

class PluginException extends Exception
{
    public const ERR_MANIFEST_INVALID     = 1001;
    public const ERR_MANIFEST_NOT_FOUND   = 1002;
    public const ERR_CODE_CONFLICT        = 2001;
    public const ERR_MENU_NAME_CONFLICT   = 2002;
    public const ERR_PERMISSION_CONFLICT  = 2003;
    public const ERR_PARENT_MENU_MISSING  = 2004;
    public const ERR_DEPENDENCY_MISSING   = 2005;
    public const ERR_VERSION_INCOMPATIBLE = 2006;
    public const ERR_ZIP_INVALID          = 3001;
    public const ERR_DIR_EXISTS           = 3002;
    public const ERR_SQL_FAILED           = 4001;
    public const ERR_MIGRATION_FAILED     = 4002;
}
