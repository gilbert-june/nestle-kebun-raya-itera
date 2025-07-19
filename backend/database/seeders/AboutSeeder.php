<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\About;

class AboutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $about = About::where('identifier', 'DEFAULT')->first();
        
        if (!$about) {
            About::create([
                'identifier' => 'DEFAULT',
                'title' => 'Tentang Nestle Kebun Raya ITERA',
                'description' => 'Nestle Kebun Raya ITERA adalah platform canggih yang didedikasikan untuk memajukan penelitian dan konservasi lingkungan. Misi kami adalah menghubungkan para peneliti untuk berkolaborasi dalam inisiatif pertanian berkelanjutan. Dengan teknologi mutakhir dan komitmen terhadap pelestarian lingkungan, kami membangun masa depan di mana alam dan teknologi bekerja bersama secara harmonis.',
                'image' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80',
            ]);
        }
    }
} 