<?php
declare(strict_types=1);

/**
 * Plugin event registration — loaded by PluginManager::registerEvents when status=installed.
 */
use think\facade\Event;

Event::listen('user.register', \plugins\new_user_gift\listener\NewUserGiftListener::class);
