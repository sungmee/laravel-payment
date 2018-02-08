<?php

Route::match(['post', 'get'], '/payment/notify/offline', '\Sungmee\LaraPay\NotifyController@offlineNotify');
Route::match(['post', 'get'], '/payment/notify/page',    '\Sungmee\LaraPay\NotifyController@pageNotify');