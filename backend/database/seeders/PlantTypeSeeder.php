<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlantType;
use App\Models\PlantTypeImage;

class PlantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plantTypes = [
            [
                'name' => 'Anggrek Dendrobium',
                'description' => '<strong>Anggrek Dendrobium</strong> adalah salah satu jenis anggrek yang populer di Indonesia. Tanaman ini memiliki bunga yang indah dengan warna-warna yang beragam, mulai dari putih, kuning, hingga ungu. <br><br>Anggrek Dendrobium cocok untuk ditanam di daerah tropis dan dapat tumbuh dengan baik di dalam greenhouse dengan kelembaban yang tepat.',
                'images' => [
                    'https://images.unsplash.com/photo-1566554273541-37a9ca77b91f?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop'
                ]
            ],
            [
                'name' => 'Kaktus Hias',
                'description' => '<strong>Kaktus Hias</strong> adalah tanaman sukulen yang sangat tahan terhadap kekeringan. Tanaman ini memiliki bentuk yang unik dan menarik, membuatnya populer sebagai tanaman hias indoor maupun outdoor. <br><br>Kaktus membutuhkan sinar matahari yang cukup dan penyiraman yang tidak terlalu sering. Di greenhouse, kaktus dapat tumbuh optimal dengan pengaturan suhu dan kelembaban yang tepat.',
                'images' => [
                    'https://images.unsplash.com/photo-1509423350716-97f9360b4e09?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop'
                ]
            ],
            [
                'name' => 'Bunga Mawar',
                'description' => '<strong>Bunga Mawar</strong> adalah salah satu bunga yang paling populer di dunia. Dengan kelopak yang lembut dan aroma yang harum, mawar sering digunakan sebagai simbol cinta dan keindahan. <br><br>Di greenhouse, mawar dapat tumbuh sepanjang tahun dengan perawatan yang tepat. Tanaman ini membutuhkan sinar matahari penuh, tanah yang subur, dan penyiraman yang teratur.',
                'images' => [
                    'https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop'
                ]
            ],
            [
                'name' => 'Tanaman Herbal',
                'description' => '<strong>Tanaman Herbal</strong> seperti basil, mint, dan rosemary adalah tanaman yang tidak hanya indah tetapi juga bermanfaat untuk kesehatan. Tanaman ini dapat digunakan sebagai bumbu masakan atau bahan obat tradisional. <br><br>Di greenhouse, tanaman herbal dapat tumbuh optimal dengan pengaturan suhu yang hangat dan kelembaban yang moderat. Tanaman ini juga membutuhkan sinar matahari yang cukup untuk menghasilkan minyak esensial yang berkualitas.',
                'images' => [
                    'https://images.unsplash.com/photo-1594736797933-d0401ba2fe65?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1593691509543-c55fb32e5cee?w=800&h=600&fit=crop'
                ]
            ]
        ];

        foreach ($plantTypes as $plantTypeData) {
            $plantType = PlantType::create([
                'name' => $plantTypeData['name'],
                'description' => $plantTypeData['description']
            ]);

            // Create images for each plant type
            foreach ($plantTypeData['images'] as $index => $imageUrl) {
                PlantTypeImage::create([
                    'plant_type_id' => $plantType->id,
                    'image' => 'plant-types/sample-' . $plantType->id . '-' . $index . '.jpg',
                    'is_thumbnail' => $index === 0 // First image as thumbnail
                ]);
            }
        }

        $this->command->info('Plant types seeded successfully!');
    }
} 