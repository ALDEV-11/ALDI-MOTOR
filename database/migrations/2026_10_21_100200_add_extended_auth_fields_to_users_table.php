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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'no_hp')) {
                $table->dropColumn('no_hp');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 25)->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'whatsapp_opt_in')) {
                $table->boolean('whatsapp_opt_in')->default(true)->after('phone');
            }

            // Ubah tipe kolom role menjadi string agar fleksibel dengan PHP Enum
            $table->string('role', 30)->default('customer')->change();

            if (! Schema::hasColumn('users', 'no_show_count')) {
                $table->unsignedInteger('no_show_count')->default(0)->after('role');
            }

            if (! Schema::hasColumn('users', 'blocked_until')) {
                $table->timestamp('blocked_until')->nullable()->after('no_show_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'whatsapp_opt_in',
                'no_show_count',
                'blocked_until',
            ]);
        });
    }
};
