<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ScenePreset;
use Illuminate\Database\Seeder;

class ScenePresetSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array<string, string>>>
     */
    private array $presets = [
        'birthday' => [
            ['slug' => 'balloon_party', 'name' => 'Balloon Party', 'prompt_fragment' => 'vibrant birthday party room filled with colorful helium balloons, streamers, confetti, and a festive banner'],
            ['slug' => 'cake_candles', 'name' => 'Cake & Candles', 'prompt_fragment' => 'celebratory birthday cake glowing with candles on a decorated table, soft bokeh background'],
            ['slug' => 'confetti_blast', 'name' => 'Confetti Blast', 'prompt_fragment' => 'dynamic confetti explosion in mid-air with party hats and party favors in motion'],
            ['slug' => 'gift_table', 'name' => 'Gift Table', 'prompt_fragment' => 'beautifully wrapped birthday gifts arranged on a decorated table beneath warm festive lights'],
        ],
        'wedding' => [
            ['slug' => 'altar_garden', 'name' => 'Garden Altar', 'prompt_fragment' => 'romantic outdoor wedding altar draped in flowers and greenery, soft pastel petals'],
            ['slug' => 'ballroom_elegant', 'name' => 'Elegant Ballroom', 'prompt_fragment' => 'grand ballroom with crystal chandeliers, ivory drapery, and golden accents'],
            ['slug' => 'beach_sunset', 'name' => 'Beach Sunset', 'prompt_fragment' => 'tropical beach at golden sunset with soft waves and warm light'],
            ['slug' => 'chapel_flowers', 'name' => 'Flower Chapel', 'prompt_fragment' => 'intimate wedding chapel decorated with white flowers, candles, and elegant soft light'],
        ],
        'pets' => [
            ['slug' => 'dog_park', 'name' => 'Dog Park', 'prompt_fragment' => 'lush green dog park with trees, fetch toys, and a sunny open field'],
            ['slug' => 'cat_nap', 'name' => 'Cat Nap', 'prompt_fragment' => 'cozy sunlit window nook with soft cushions, yarn balls, and warm afternoon light'],
            ['slug' => 'aquarium', 'name' => 'Aquarium', 'prompt_fragment' => 'aquarium backdrop with soft blue light, bubbles, and tropical fish'],
            ['slug' => 'forest_walk', 'name' => 'Forest Walk', 'prompt_fragment' => 'peaceful woodland trail with filtered sunlight, leafy trees, and natural textures'],
        ],
        'family' => [
            ['slug' => 'living_room', 'name' => 'Living Room', 'prompt_fragment' => 'warm family living room with a soft sofa, framed photos, and a fireplace'],
            ['slug' => 'backyard_bbq', 'name' => 'Backyard BBQ', 'prompt_fragment' => 'sunny backyard barbecue with picnic table, string lights, and trees'],
            ['slug' => 'park_picnic', 'name' => 'Park Picnic', 'prompt_fragment' => 'open grassy park with a picnic blanket, basket, and blooming flowers'],
            ['slug' => 'family_kitchen', 'name' => 'Family Kitchen', 'prompt_fragment' => 'bright family kitchen with a wooden table, fresh flowers, and welcoming natural light'],
        ],
        'couples' => [
            ['slug' => 'rooftop_sunset', 'name' => 'Rooftop Sunset', 'prompt_fragment' => 'city rooftop at sunset with skyline silhouettes and warm golden light'],
            ['slug' => 'coffee_shop', 'name' => 'Coffee Shop', 'prompt_fragment' => 'intimate cafe corner with two mugs, books, and warm ambient lighting'],
            ['slug' => 'starry_night', 'name' => 'Starry Night', 'prompt_fragment' => 'open field under a starry night sky with a sliver of moonlight'],
            ['slug' => 'garden_walk', 'name' => 'Garden Walk', 'prompt_fragment' => 'romantic garden pathway surrounded by blooming flowers and gentle afternoon light'],
        ],
        'kids' => [
            ['slug' => 'playground', 'name' => 'Playground', 'prompt_fragment' => 'colorful playground with slides, swings, and a bright cloudy sky'],
            ['slug' => 'candy_world', 'name' => 'Candy World', 'prompt_fragment' => 'whimsical candy land with lollipops, gummy trees, and pastel skies'],
            ['slug' => 'space_adventure', 'name' => 'Space Adventure', 'prompt_fragment' => 'playful space scene with friendly rockets, planets, and twinkling stars'],
            ['slug' => 'storybook_castle', 'name' => 'Storybook Castle', 'prompt_fragment' => 'colorful storybook castle with friendly flags, soft clouds, and magical details'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->presets as $categorySlug => $rows) {
            $categories = Category::query()->where('slug', $categorySlug)->get();

            foreach ($categories as $category) {
                foreach ($rows as $index => $row) {
                    ScenePreset::firstOrCreate(
                        [
                            'category_id' => $category->id,
                            'slug' => $row['slug'],
                        ],
                        [
                            'name' => $row['name'],
                            'prompt_fragment' => $row['prompt_fragment'],
                            'sort_order' => $index,
                            'is_default' => $index === 0,
                        ],
                    );
                }
            }
        }
    }
}
