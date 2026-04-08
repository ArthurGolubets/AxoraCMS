<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_info_block_fields', function (Blueprint $table) {
            DB::statement("ALTER TABLE t_info_block_fields MODIFY COLUMN type ENUM('string','text','number','double','bool','date','datetime','image','file','entity','user','enum') NOT NULL COMMENT 'Тип поля'");
        });
    }

    public function down(): void
    {
        Schema::table('t_info_block_fields', function (Blueprint $table) {
            DB::statement("ALTER TABLE t_info_block_fields MODIFY COLUMN type ENUM('string','text','number','double','bool','date','datetime','image','file','entity','user') NOT NULL COMMENT 'Тип поля'");
        });
    }
};
