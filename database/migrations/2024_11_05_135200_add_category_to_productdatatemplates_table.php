<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->enum('category', [
                'Construção',
                'Material de Construção',
                'Obras Geotécnicas',
                'Escavação e Estabilização',
                'Fundação e Estacas',
                'Estruturas de Retenção de Terra',
                'Concreto',
                'Aço',
                'Estruturas de Madeira',
                'Alvenaria e Tijolo',
                'Materiais Compostos e Especializados',
                'Paredes',
                'Telhados',
                'Revestimento',
                'Isolamento',
                'Janelas',
                'Portas',
                'Divisórias',
                'Tetos',
                'Pisos',
                'Tinta',
                'Revestimentos de Parede',
                'Sanitário',
                'Cozinha',
                'Ferrovias',
                'Vias Rodoviárias',
                'Sistemas de HVAC',
                'Sistemas Elétricos',
                'Plumbing',
                'Proteção Contra Incêndio',
                'Serviços Civis e de Utilidade',
                'Infraestrutura de TI',
                'Obras e Paisagismo',
                'Sistemas de Segurança e Proteção'

            ])->after('status')->default('Construção')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
