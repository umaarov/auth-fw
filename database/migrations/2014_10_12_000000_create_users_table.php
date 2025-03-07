<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token', 64)->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(false);
            $table->integer('failed_login_attempts')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_until')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->json('settings')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
