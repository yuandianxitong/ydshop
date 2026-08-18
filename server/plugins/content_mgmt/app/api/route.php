<?php
use think\facade\Route;

Route::get('agreement/:code', [\plugins\content_mgmt\api\controller\AgreementController::class, 'getByCode']);
