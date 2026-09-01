<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE t_info_block_fields MODIFY COLUMN type ENUM('string','text','number','double','bool','date','datetime','image','file','entity','user','enum','button','table') NOT NULL COMMENT 'Тип поля'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE t_info_block_fields MODIFY COLUMN type ENUM('string','text','number','double','bool','date','datetime','image','file','entity','user','enum') NOT NULL COMMENT 'Тип поля'");
    }
};
