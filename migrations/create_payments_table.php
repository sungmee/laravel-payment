<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 入金状态
        // Pending - 待处理。
        // Void - 无效，当订单在付款前被取消的状态。
        // Refunded - 已退款。当订单在付款后被取消或退回时的状态。
        // Capture - 已付款。
        // Success - 支付流程已完成。
        // Fail - 支付失败。
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('gateway', 28);
            $table->integer('amount');
            $table->enum('status', ['PENDING', 'VOID', 'REFUNDED', 'CAPTURE', 'SUCCESS', 'FAIL'])->nullable();
            $table->json('metas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
