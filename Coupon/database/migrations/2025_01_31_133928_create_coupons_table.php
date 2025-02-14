<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('product_type');
            $table->longText('category_id')->nullable();
            $table->longText('subcategory_id')->nullable();
            $table->longText('product_id')->nullable();
            $table->string('coupon_type');
            $table->string('coupon_value');
            $table->string('quantity');
            $table->string('quantity_value');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
