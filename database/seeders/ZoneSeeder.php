<?php

namespace Ingenius\Shipment\Database\Seeders;

use Illuminate\Database\Seeder;
use Ingenius\Shipment\Models\Zone;
use Ingenius\Shipment\Enums\ZoneType;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cubanProvinces = [
            'Pinar del Río' => [
                'Consolación del Sur',
                'Guane',
                'La Palma',
                'Los Palacios',
                'Mantua',
                'Minas de Matahambre',
                'Pinar del Río',
                'San Juan y Martínez',
                'San Luis',
                'Sandino',
                'Viñales'
            ],
            'Artemisa' => [
                'Alquízar',
                'Artemisa',
                'Bauta',
                'Caimito',
                'Guanajay',
                'Güira de Melena',
                'Mariel',
                'San Antonio de los Baños',
                'San Cristóbal',
                'Candelaria',
                'Bahía Honda'
            ],
            'La Habana' => [
                'Arroyo Naranjo',
                'Boyeros',
                'Centro Habana',
                'Cerro',
                'Cotorro',
                'Diez de Octubre',
                'Guanabacoa',
                'Habana del Este',
                'Habana Vieja',
                'La Lisa',
                'Marianao',
                'Playa',
                'Plaza de la Revolución',
                'Regla',
                'San Miguel del Padrón'
            ],
            'Mayabeque' => [
                'Batabanó',
                'Bejucal',
                'Güines',
                'Jaruco',
                'Madruga',
                'Melena del Sur',
                'Nueva Paz',
                'Quivicán',
                'San José de las Lajas',
                'San Nicolás',
                'Santa Cruz del Norte'
            ],
            'Matanzas' => [
                'Calimete',
                'Cárdenas',
                'Ciénaga de Zapata',
                'Colón',
                'Jagüey Grande',
                'Jovellanos',
                'Limonar',
                'Los Arabos',
                'Martí',
                'Matanzas',
                'Pedro Betancourt',
                'Perico',
                'Unión de Reyes',
                'Varadero'
            ],
            'Villa Clara' => [
                'Caibarién',
                'Camajuaní',
                'Cifuentes',
                'Corralillo',
                'Encrucijada',
                'Manicaragua',
                'Placetas',
                'Quemado de Güines',
                'Ranchuelo',
                'Remedios',
                'Sagua la Grande',
                'Santa Clara',
                'Santo Domingo'
            ],
            'Cienfuegos' => [
                'Abreus',
                'Aguada de Pasajeros',
                'Cienfuegos',
                'Cruces',
                'Cumanayagua',
                'Lajas',
                'Palmira',
                'Rodas'
            ],
            'Sancti Spíritus' => [
                'Cabaiguán',
                'Fomento',
                'Jatibonico',
                'La Sierpe',
                'Sancti Spíritus',
                'Taguasco',
                'Trinidad',
                'Yaguajay'
            ],
            'Ciego de Ávila' => [
                'Baraguá',
                'Bolivia',
                'Chambas',
                'Ciego de Ávila',
                'Ciro Redondo',
                'Florencia',
                'Majagua',
                'Morón',
                'Primero de Enero',
                'Venezuela'
            ],
            'Camagüey' => [
                'Camagüey',
                'Carlos Manuel de Céspedes',
                'Esmeralda',
                'Florida',
                'Guáimaro',
                'Jimaguayú',
                'Minas',
                'Najasa',
                'Nuevitas',
                'Santa Cruz del Sur',
                'Sibanicú',
                'Sierra de Cubitas',
                'Vertientes'
            ],
            'Las Tunas' => [
                'Amancio',
                'Colombia',
                'Jesús Menéndez',
                'Jobabo',
                'Las Tunas',
                'Manatí',
                'Puerto Padre',
                'Majibacoa'
            ],
            'Holguín' => [
                'Antilla',
                'Báguanos',
                'Banes',
                'Cacocum',
                'Calixto García',
                'Cueto',
                'Frank País',
                'Gibara',
                'Holguín',
                'Mayarí',
                'Moa',
                'Rafael Freyre',
                'Sagua de Tánamo',
                'Urbano Noris'
            ],
            'Granma' => [
                'Bartolomé Masó',
                'Bayamo',
                'Buey Arriba',
                'Campechuela',
                'Cauto Cristo',
                'Guisa',
                'Jiguaní',
                'Manzanillo',
                'Media Luna',
                'Niquero',
                'Pilón',
                'Río Cauto',
                'Yara'
            ],
            'Santiago de Cuba' => [
                'Contramaestre',
                'Mella',
                'Palma Soriano',
                'San Luis',
                'Santiago de Cuba',
                'Segundo Frente',
                'Songo-La Maya',
                'Tercer Frente',
                'Guamá'
            ],
            'Guantánamo' => [
                'Baracoa',
                'Caimanera',
                'El Salvador',
                'Guantánamo',
                'Imías',
                'Maisí',
                'Manuel Tames',
                'Niceto Pérez',
                'San Antonio del Sur',
                'Yateras'
            ],
            'Isla de la Juventud' => [
                'Nueva Gerona'
            ]
        ];

        foreach ($cubanProvinces as $provinceName => $municipalities) {
            // Create province only if it doesn't exist
            $province = Zone::firstOrCreate(
                [
                    'name' => $provinceName,
                    'type' => ZoneType::PROVINCE->value
                ],
                [
                    'parent_id' => null,
                    'active' => false
                ]
            );

            // Create municipalities for this province
            foreach ($municipalities as $municipalityName) {
                Zone::firstOrCreate(
                    [
                        'name' => $municipalityName,
                        'parent_id' => $province->id,
                        'type' => ZoneType::MUNICIPALITY->value
                    ],
                    [
                        'active' => false
                    ]
                );
            }
        }
    }
}