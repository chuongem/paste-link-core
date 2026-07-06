<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_links', function (Blueprint $table) {
            $table->string('password_hash')->nullable()->after('is_active');
            $table->timestamp('expires_at')->nullable()->after('password_hash');
            $table->unsignedBigInteger('view_count')->default(0)->after('expires_at');
            $table->unsignedBigInteger('download_count')->default(0)->after('view_count');
            $table->timestamp('last_viewed_at')->nullable()->after('download_count');
            $table->timestamp('last_downloaded_at')->nullable()->after('last_viewed_at');

            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('share_links', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'expires_at']);
            $table->dropColumn([
                'password_hash',
                'expires_at',
                'view_count',
                'download_count',
                'last_viewed_at',
                'last_downloaded_at',
            ]);
        });
    }
};
