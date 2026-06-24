<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OccupancyGroupSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('application_occupancy_groups')->truncate();
        DB::table('occupancy_sub_groups')->truncate();
        DB::table('occupancy_groups')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $now = now();

        DB::table('occupancy_groups')->insert([
            ['id' => 1,  'code' => 'A', 'name' => 'Residential (Dwellings)',             'sort_order' => 1,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'code' => 'B', 'name' => 'Residential',                          'sort_order' => 2,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'code' => 'C', 'name' => 'Educational & Recreational',           'sort_order' => 3,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'code' => 'D', 'name' => 'Institutional',                        'sort_order' => 4,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'code' => 'E', 'name' => 'Commercial',                           'sort_order' => 5,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'code' => 'F', 'name' => 'Light Industrial',                     'sort_order' => 6,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'code' => 'G', 'name' => 'Medium Industrial',                    'sort_order' => 7,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'code' => 'H', 'name' => 'Assembly (Occupant Load < 1000)',      'sort_order' => 8,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'code' => 'I', 'name' => 'Assembly (Occupant Load > 1000)',      'sort_order' => 9,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'code' => 'J', 'name' => 'Agricultural & Accessories',           'sort_order' => 10, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('occupancy_sub_groups')->insert([
            // Group A
            ['id' => 1,  'occupancy_group_id' => 1,  'name' => 'Single',                                                                     'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'occupancy_group_id' => 1,  'name' => 'Duplex',                                                                     'sort_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'occupancy_group_id' => 1,  'name' => 'Residential R-1, R-2',                                                       'sort_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4,  'occupancy_group_id' => 1,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group B
            ['id' => 5,  'occupancy_group_id' => 2,  'name' => 'Hotel',                                                                      'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'occupancy_group_id' => 2,  'name' => 'Motel',                                                                      'sort_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'occupancy_group_id' => 2,  'name' => 'Townhouse',                                                                  'sort_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8,  'occupancy_group_id' => 2,  'name' => 'Dormitory',                                                                  'sort_order' => 4, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'occupancy_group_id' => 2,  'name' => 'Boardinghouse, Lodging House',                                               'sort_order' => 5, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'occupancy_group_id' => 2,  'name' => 'Residential R-3, R-4, R-5',                                                  'sort_order' => 6, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'occupancy_group_id' => 2,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group C
            ['id' => 12, 'occupancy_group_id' => 3,  'name' => 'School Building',                                                            'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'occupancy_group_id' => 3,  'name' => 'School Auditorium, Gymnasium',                                               'sort_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'occupancy_group_id' => 3,  'name' => 'Civic Center',                                                               'sort_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'occupancy_group_id' => 3,  'name' => 'Clubhouse',                                                                  'sort_order' => 4, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'occupancy_group_id' => 3,  'name' => 'Church, Mosque, Temple, Chapel',                                             'sort_order' => 5, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'occupancy_group_id' => 3,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group D
            ['id' => 18, 'occupancy_group_id' => 4,  'name' => 'Hospital or Similar Structure',                                              'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'occupancy_group_id' => 4,  'name' => 'Home for the Aged',                                                          'sort_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'occupancy_group_id' => 4,  'name' => 'Government Office',                                                          'sort_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 21, 'occupancy_group_id' => 4,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group E
            ['id' => 22, 'occupancy_group_id' => 5,  'name' => 'Banks',                                                                      'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 23, 'occupancy_group_id' => 5,  'name' => 'Store',                                                                      'sort_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 24, 'occupancy_group_id' => 5,  'name' => 'Shopping Center/Mall',                                                       'sort_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 25, 'occupancy_group_id' => 5,  'name' => 'Drinking/Dining Establishment',                                              'sort_order' => 4, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 26, 'occupancy_group_id' => 5,  'name' => 'Shop (Dress Shop, Tailoring, Barbershop, Etc.)',                             'sort_order' => 5, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 27, 'occupancy_group_id' => 5,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group F
            ['id' => 28, 'occupancy_group_id' => 6,  'name' => 'Factory/Plant (Using Incombustible/Non-Explosive Materials)',                 'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 29, 'occupancy_group_id' => 6,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group G
            ['id' => 30, 'occupancy_group_id' => 7,  'name' => 'Storage/Warehouse (For Hazardous/Highly Flammable Materials)',                'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 31, 'occupancy_group_id' => 7,  'name' => 'Factory (For Hazardous/Highly Materials)',                                    'sort_order' => 2, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 32, 'occupancy_group_id' => 7,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group H
            ['id' => 33, 'occupancy_group_id' => 8,  'name' => 'Theater, Auditorium, Convention Hall, Grandstand/Bleacher',                  'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 34, 'occupancy_group_id' => 8,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group I
            ['id' => 35, 'occupancy_group_id' => 9,  'name' => 'Coliseum, Sports Complex, Convention Center and Similar Structure',           'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 36, 'occupancy_group_id' => 9,  'name' => 'Others',                                                                     'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Group J
            ['id' => 37, 'occupancy_group_id' => 10, 'name' => 'J-1 Barn, Granary, Poultry House, Piggery, Grain Mill, Grain Silo',          'sort_order' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 38, 'occupancy_group_id' => 10, 'name' => 'J-1 Others',                                                                 'sort_order' => 99, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 39, 'occupancy_group_id' => 10, 'name' => 'J-2 Prv. Carport/Garage, Tower, Swimm. Pool, Fence Over 180m, Steel/Concr. Tank', 'sort_order' => 3, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 40, 'occupancy_group_id' => 10, 'name' => 'J-2 Others',                                                                 'sort_order' => 100, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
