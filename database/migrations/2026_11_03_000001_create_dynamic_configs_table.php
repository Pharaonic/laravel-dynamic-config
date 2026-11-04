<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('pharaonic.dynamic-config.connection'))
            ->create(config('pharaonic.dynamic-config.table', 'dynamic_configs'), function (Blueprint $table) {
                $table->id();
                $table->string('identifier')->nullable();
                $table->string('key');
                $table->text('value');
                $table->timestamps();

                // Most databases let several NULL identifiers share a key, so
                // the package keeps the global scope unique itself.
                $table->unique(['identifier', 'key']);
            });
    }

    public function down(): void
    {
        Schema::connection(config('pharaonic.dynamic-config.connection'))
            ->dropIfExists(config('pharaonic.dynamic-config.table', 'dynamic_configs'));
    }
};
