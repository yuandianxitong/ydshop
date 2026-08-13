<?php
use think\facade\Route;

Route::get('agreement/:code', [\plugins\content_mgmt\controller\api\AgreementController::class, 'getByCode']);
