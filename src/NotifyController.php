<?php

namespace Sungmee\LaraPay;

use Sungmee\Pay\Events\Capture;
use App\Http\Controllers\Controller;

class NotifyController extends Controller
{
    /**
     * 第三方平台支付实例。
     *
     * @var \Sungmee\LaraPay\Platforms\Platform
     */
    protected $platform;

    /**
     * 用户支付成功后，同步跳转页面。
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->platform   = new Pay();
        $this->redirectTo = config('pay.redirectTo');
    }

    /**
     * 第三方平台异步通知应答。
     *
     * @param  \Illuminate\Http\Request $request
     * @return string
     */
    public function offlineNotify(Request $request)
    {
        return $this->answer($this->platform->offlineNotify($request));
    }

    /**
     * 第三方平台页面通知应答。
     *
     * @param \Illuminate\Http\Request $request
     * @return string|\Illuminate\Http\RedirectResponse
     */
    public function pageNotify(Request $request)
    {
        return $this->answer($this->platform->pageNotify($request), false);
    }

    /**
     * 第三方平台通知应答。
     *
     * @param  array    $result     第三方平台通知的验签和处理结果
     * @param  bool     $offline    第三方平台入金结果通知方式：异步|同步
     * @return string|\Illuminate\Http\RedirectResponse
     */
    protected function answer($result, $offline = true)
    {
        // 验证失败
        if (! $result['validate']) {
            return $offline ? $payment['answer'] : 'Payment Failed.';
        }

        // 第一次验证成功，返回的是入金记录 ID
        if ($result('first') && $result['payment']) {
            $this->handle($result['payment'], $offline);
        }

        // 第 N 次验证成功，返回的是入金记录的状态
        return $offline ? $result['answer'] : redirect($this->redirectTo);
    }

    /**
     * 产生用户已付款事件，以及记录日志
     * @param \Sungmee\LaraPay\Payment      $payment    付款模型
     * @param bool                          $offline    第三方平台入金结果通知方式：异步|同步
     * @return void
     */
    protected function handle(Payment $payment, bool $offline)
    {
        Capture::dispatch($payment);

        $type = $offline ? 'Offline' : 'Page';
        info("#{$payment->id} payment {$type} Notify.");
    }
}