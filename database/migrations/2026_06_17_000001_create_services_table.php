<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon');
            $table->string('tag');
            $table->decimal('price', 8, 2);
            $table->string('price_label');
            $table->string('color', 10)->default('#EEF2FF');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default services
        DB::table('services')->insert([
            ['title'=>'Safai',       'icon'=>'🧹','tag'=>'cleaning', 'price'=>299,'price_label'=>'₹299/visit','color'=>'#EEF2FF','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Khana',       'icon'=>'🍳','tag'=>'cooking',  'price'=>499,'price_label'=>'₹499/din',  'color'=>'#F0FDF4','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Babysitter',  'icon'=>'👶','tag'=>'care',     'price'=>399,'price_label'=>'₹399/din',  'color'=>'#FFF7ED','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Gardening',   'icon'=>'🌿','tag'=>'outdoor',  'price'=>249,'price_label'=>'₹249/visit','color'=>'#ECFDF5','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Driver',      'icon'=>'🚗','tag'=>'transport','price'=>599,'price_label'=>'₹599/din',  'color'=>'#EFF6FF','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Pet Care',    'icon'=>'🐕','tag'=>'care',     'price'=>199,'price_label'=>'₹199/din',  'color'=>'#FDF4FF','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Plumber',     'icon'=>'🔧','tag'=>'repair',   'price'=>349,'price_label'=>'₹349/visit','color'=>'#F0F9FF','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'Electrician', 'icon'=>'⚡','tag'=>'repair',   'price'=>399,'price_label'=>'₹399/visit','color'=>'#FEFCE8','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
