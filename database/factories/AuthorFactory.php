<?php

namespace Database\Factories;

use App\Modules\Content\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Chỉ dùng trong test. Không phải người có thật.
 *
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $name = 'Tác giả thử '.Str::upper(Str::random(4));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'job_title' => 'Biên tập viên',
            'is_active' => true,
        ];
    }
}
