<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('icon', 20);
            $table->string('color', 10)->default('#EEF2FF');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('service_categories')->insert([
            ['name'=>'Cleaning','icon'=>'🧹','color'=>'#EEF2FF','sort_order'=>1,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Plumbing','icon'=>'🔧','color'=>'#F0F9FF','sort_order'=>2,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Electrical','icon'=>'⚡','color'=>'#FEFCE8','sort_order'=>3,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Carpentry','icon'=>'🪵','color'=>'#FFF7ED','sort_order'=>4,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Painting','icon'=>'🎨','color'=>'#FDF4FF','sort_order'=>5,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Pest Control','icon'=>'🪲','color'=>'#F0FDF4','sort_order'=>6,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Appliance Repair','icon'=>'🔌','color'=>'#EFF6FF','sort_order'=>7,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Gardening','icon'=>'🌿','color'=>'#ECFDF5','sort_order'=>8,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Beauty','icon'=>'💆','color'=>'#FFF1F2','sort_order'=>9,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
    public function down(): void { Schema::dropIfExists('service_categories'); }
};