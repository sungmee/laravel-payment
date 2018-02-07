<?php

Route::match(['post', 'get'], '/payment/notify/offline', '\Sungmee\Payment\NotifyController@offlineNotify');
Route::match(['post', 'get'], '/payment/notify/page',    '\Sungmee\Payment\NotifyController@pageNotify');