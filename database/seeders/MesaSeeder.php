<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MesaSeeder extends Seeder
{
    public function run(): void
    {

        DB::table('mesas')->insert([
            ['numero'=>1,'zona'=>'Corredor','numero_zona'=>1,'capacidad'=>4,'estado'=>'libre','pos_x'=>41,'pos_y'=>10,'ancho'=>110,'alto'=>60],
            ['numero'=>2,'zona'=>'Corredor','numero_zona'=>2,'capacidad'=>4,'estado'=>'libre','pos_x'=>56,'pos_y'=>10,'ancho'=>75,'alto'=>65],
            ['numero'=>3,'zona'=>'Corredor','numero_zona'=>3,'capacidad'=>4,'estado'=>'libre','pos_x'=>56,'pos_y'=>20,'ancho'=>75,'alto'=>65],
            ['numero'=>4,'zona'=>'Corredor','numero_zona'=>4,'capacidad'=>4,'estado'=>'libre','pos_x'=>56,'pos_y'=>30,'ancho'=>75,'alto'=>65],
            ['numero'=>5,'zona'=>'Corredor','numero_zona'=>5,'capacidad'=>4,'estado'=>'libre','pos_x'=>56,'pos_y'=>40,'ancho'=>75,'alto'=>65],
            ['numero'=>6,'zona'=>'Corredor','numero_zona'=>6,'capacidad'=>4,'estado'=>'libre','pos_x'=>40,'pos_y'=>40,'ancho'=>110,'alto'=>60],

            ['numero'=>7,'zona'=>'Salon','numero_zona'=>1,'capacidad'=>4,'estado'=>'libre','pos_x'=>39,'pos_y'=>20,'ancho'=>45,'alto'=>40],
            ['numero'=>8,'zona'=>'Salon','numero_zona'=>2,'capacidad'=>4,'estado'=>'libre','pos_x'=>46,'pos_y'=>20,'ancho'=>45,'alto'=>40],
            ['numero'=>9,'zona'=>'Salon','numero_zona'=>3,'capacidad'=>4,'estado'=>'libre','pos_x'=>45,'pos_y'=>29,'ancho'=>45,'alto'=>40],
            ['numero'=>10,'zona'=>'Salon','numero_zona'=>4,'capacidad'=>4,'estado'=>'libre','pos_x'=>39,'pos_y'=>29,'ancho'=>45,'alto'=>40],
            ['numero'=>11,'zona'=>'Salon','numero_zona'=>5,'capacidad'=>4,'estado'=>'libre','pos_x'=>33,'pos_y'=>29,'ancho'=>45,'alto'=>40],

            ['numero'=>12,'zona'=>'Area Verde','numero_zona'=>1,'capacidad'=>4,'estado'=>'libre','pos_x'=>30,'pos_y'=>56,'ancho'=>45,'alto'=>40],
            ['numero'=>13,'zona'=>'Area Verde','numero_zona'=>2,'capacidad'=>4,'estado'=>'libre','pos_x'=>24,'pos_y'=>56,'ancho'=>45,'alto'=>40],
            ['numero'=>14,'zona'=>'Area Verde','numero_zona'=>3,'capacidad'=>4,'estado'=>'libre','pos_x'=>18,'pos_y'=>56,'ancho'=>45,'alto'=>40],
            ['numero'=>15,'zona'=>'Area Verde','numero_zona'=>4,'capacidad'=>4,'estado'=>'libre','pos_x'=>12,'pos_y'=>56,'ancho'=>45,'alto'=>40],
            ['numero'=>16,'zona'=>'Area Verde','numero_zona'=>5,'capacidad'=>4,'estado'=>'libre','pos_x'=>5,'pos_y'=>60,'ancho'=>70,'alto'=>65],
            ['numero'=>17,'zona'=>'Area Verde','numero_zona'=>6,'capacidad'=>4,'estado'=>'libre','pos_x'=>5,'pos_y'=>68,'ancho'=>70,'alto'=>65],
            ['numero'=>18,'zona'=>'Area Verde','numero_zona'=>7,'capacidad'=>4,'estado'=>'libre','pos_x'=>5,'pos_y'=>76,'ancho'=>70,'alto'=>65],
            ['numero'=>19,'zona'=>'Area Verde','numero_zona'=>8,'capacidad'=>4,'estado'=>'libre','pos_x'=>5,'pos_y'=>84,'ancho'=>70,'alto'=>65],
            ['numero'=>20,'zona'=>'Area Verde','numero_zona'=>9,'capacidad'=>4,'estado'=>'libre','pos_x'=>18,'pos_y'=>84,'ancho'=>70,'alto'=>65],
            ['numero'=>21,'zona'=>'Area Verde','numero_zona'=>10,'capacidad'=>4,'estado'=>'libre','pos_x'=>30,'pos_y'=>84,'ancho'=>70,'alto'=>65],

            ['numero'=>22,'zona'=>'Area de Fuente','numero_zona'=>1,'capacidad'=>4,'estado'=>'libre','pos_x'=>45,'pos_y'=>67,'ancho'=>70,'alto'=>65],
            ['numero'=>23,'zona'=>'Area de Fuente','numero_zona'=>2,'capacidad'=>4,'estado'=>'libre','pos_x'=>41,'pos_y'=>82,'ancho'=>40,'alto'=>40],
            ['numero'=>24,'zona'=>'Area de Fuente','numero_zona'=>3,'capacidad'=>4,'estado'=>'libre','pos_x'=>47,'pos_y'=>82,'ancho'=>40,'alto'=>40],
            ['numero'=>25,'zona'=>'Area de Fuente','numero_zona'=>4,'capacidad'=>4,'estado'=>'libre','pos_x'=>53,'pos_y'=>82,'ancho'=>40,'alto'=>40],
            ['numero'=>26,'zona'=>'Area de Fuente','numero_zona'=>5,'capacidad'=>4,'estado'=>'libre','pos_x'=>59,'pos_y'=>82,'ancho'=>40,'alto'=>40],

            ['numero'=>27,'zona'=>'Area de Niños','numero_zona'=>1,'capacidad'=>4,'estado'=>'libre','pos_x'=>86,'pos_y'=>61,'ancho'=>75,'alto'=>65],
            ['numero'=>28,'zona'=>'Area de Niños','numero_zona'=>2,'capacidad'=>4,'estado'=>'libre','pos_x'=>86,'pos_y'=>72,'ancho'=>75,'alto'=>65],
            ['numero'=>29,'zona'=>'Area de Niños','numero_zona'=>3,'capacidad'=>4,'estado'=>'libre','pos_x'=>68,'pos_y'=>72,'ancho'=>75,'alto'=>65],
            ['numero'=>30,'zona'=>'Area de Niños','numero_zona'=>4,'capacidad'=>4,'estado'=>'libre','pos_x'=>68,'pos_y'=>82,'ancho'=>40,'alto'=>40],
            ['numero'=>31,'zona'=>'Area de Niños','numero_zona'=>5,'capacidad'=>4,'estado'=>'libre','pos_x'=>75,'pos_y'=>82,'ancho'=>40,'alto'=>40],
            ['numero'=>32,'zona'=>'Area de Niños','numero_zona'=>6,'capacidad'=>4,'estado'=>'libre','pos_x'=>82,'pos_y'=>82,'ancho'=>40,'alto'=>40],
            ['numero'=>33,'zona'=>'Area de Niños','numero_zona'=>7,'capacidad'=>4,'estado'=>'libre','pos_x'=>89,'pos_y'=>82,'ancho'=>40,'alto'=>40],
        
            ['numero'=>34,'zona'=>'Picnic','numero_zona'=>1,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ['numero'=>35,'zona'=>'Picnic','numero_zona'=>2,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ['numero'=>36,'zona'=>'Picnic','numero_zona'=>3,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ['numero'=>37,'zona'=>'Picnic','numero_zona'=>4,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ['numero'=>38,'zona'=>'Picnic','numero_zona'=>5,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ['numero'=>39,'zona'=>'Picnic','numero_zona'=>6,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ['numero'=>40,'zona'=>'Picnic','numero_zona'=>7,'capacidad'=>4,'estado'=>'libre', 'pos_x'=>null,'pos_y'=>null,'ancho'=>80,'alto'=>80],
            ]);
    }
}