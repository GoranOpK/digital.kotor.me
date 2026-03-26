<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $values = [
            'Koncerti',
            'Predstave',
            'Izložbe',
            'Sportski događaji',
            'Književne večeri',
            'Filmske projekcije',
            'Radionice',
            'Promocije publikacija',
            'Performansi',
            'Filmski festivali',
            'Likovne manifestacije',
            'Prezentacije',
            'Paneli o kulturi',
            'Manifestacije u organizaciji Mjesnih zajednica',
            'Manifestacije u organizaciji NVU',
        ];

        $enumList = collect($values)
            ->map(fn (string $value) => "'" . addslashes($value) . "'")
            ->implode(',');

        DB::statement("ALTER TABLE cultural_events MODIFY kategorija ENUM($enumList) NOT NULL");
    }

    public function down(): void
    {
        $values = [
            'Koncerti',
            'Predstave',
            'Izložbe',
            'Književne večeri',
            'Filmske projekcije',
            'Radionice',
            'Promocije publikacija',
            'Performansi',
            'Filmski festivali',
            'Likovne manifestacije',
            'Prezentacije',
            'Paneli o kulturi',
            'Manifestacije u organizaciji Mjesnih zajednica',
            'Manifestacije u organizaciji NVU',
        ];

        $enumList = collect($values)
            ->map(fn (string $value) => "'" . addslashes($value) . "'")
            ->implode(',');

        DB::statement("ALTER TABLE cultural_events MODIFY kategorija ENUM($enumList) NOT NULL");
    }
};

