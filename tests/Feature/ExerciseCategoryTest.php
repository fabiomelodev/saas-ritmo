<?php

namespace Tests\Feature;

use App\Livewire\Auth\RegisterTrainer;
use App\Livewire\Trainer\Categories;
use App\Livewire\Trainer\Exercises\Edit as ExerciseEdit;
use App\Livewire\Trainer\Sheets\Edit as SheetEdit;
use App\Livewire\Trainer\Sheets\Index as SheetIndex;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseImage;
use App\Models\User;
use App\Models\WorkoutSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExerciseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trainer = User::factory()->trainer()->create();
        $this->actingAs($this->trainer);
    }

    private function category(string $name): Category
    {
        return $this->trainer->categories()->where('name', $name)->firstOrFail();
    }

    public function test_new_trainer_starts_with_default_categories(): void
    {
        auth()->logout();

        Livewire::test(RegisterTrainer::class)
            ->set('name', 'Novo Treinador')
            ->set('email', 'novo@example.com')
            ->set('password', 'senha-segura')
            ->call('register');

        $trainer = User::where('email', 'novo@example.com')->first();

        $this->assertSame(Category::DEFAULTS, $trainer->categories()->pluck('name')->all());
    }

    public function test_library_filters_by_category_and_media(): void
    {
        $legs = $this->category('Membros inferiores');
        $core = $this->category('Core');

        $withVideo = Exercise::create(['trainer_id' => $this->trainer->id, 'category_id' => $legs->id, 'name' => 'Agachamento', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $withPhoto = Exercise::create(['trainer_id' => $this->trainer->id, 'category_id' => $legs->id, 'name' => 'Drop jump']);
        ExerciseImage::create(['exercise_id' => $withPhoto->id, 'path' => 'x.jpg']);
        Exercise::create(['trainer_id' => $this->trainer->id, 'category_id' => $core->id, 'name' => 'Prancha']);
        Exercise::create(['trainer_id' => $this->trainer->id, 'name' => 'Sem categoria']);

        $names = fn ($component) => $component->get('exercises')->pluck('name')->all();

        $c = Livewire::test(SheetIndex::class);
        $this->assertCount(4, $names($c));
        $this->assertSame(2, $c->get('withoutMediaCount'));

        $c->set('category', (string) $legs->id);
        $this->assertSame(['Agachamento', 'Drop jump'], $names($c));

        $c->set('category', '')->set('media', 'without');
        $this->assertSame(['Prancha', 'Sem categoria'], $names($c));

        $c->set('media', 'with');
        $this->assertSame(['Agachamento', 'Drop jump'], $names($c));

        $c->set('media', '')->set('category', 'none');
        $this->assertSame(['Sem categoria'], $names($c));

        $c->call('clearFilters');
        $this->assertCount(4, $names($c));
    }

    public function test_exercise_editor_selects_and_creates_category(): void
    {
        $exercise = Exercise::create(['trainer_id' => $this->trainer->id, 'name' => 'Ponte de glúteo']);

        Livewire::test(ExerciseEdit::class, ['exercise' => $exercise])
            ->set('creatingCategory', true)
            ->set('newCategory', 'Glúteos')
            ->call('addCategory')
            ->assertHasNoErrors()
            ->call('save');

        $this->assertSame('Glúteos', $exercise->fresh()->category->name);

        // Não aceita categoria repetida nem de outro treinador
        Livewire::test(ExerciseEdit::class, ['exercise' => $exercise])
            ->set('newCategory', 'Core')
            ->call('addCategory')
            ->assertHasErrors('newCategory');

        $foreign = Category::create(['trainer_id' => User::factory()->trainer()->create()->id, 'name' => 'Alheia']);

        Livewire::test(ExerciseEdit::class, ['exercise' => $exercise])
            ->set('category_id', (string) $foreign->id)
            ->call('save')
            ->assertHasErrors('category_id');
    }

    public function test_deleting_category_keeps_exercises_uncategorized(): void
    {
        $core = $this->category('Core');
        $exercise = Exercise::create(['trainer_id' => $this->trainer->id, 'category_id' => $core->id, 'name' => 'Prancha']);

        Livewire::test(Categories::class)
            ->call('delete', $core->id);

        $this->assertModelMissing($core);
        $this->assertNull($exercise->fresh()->category_id);
    }

    public function test_rename_and_reorder_categories(): void
    {
        $core = $this->category('Core');

        Livewire::test(Categories::class)
            ->set("names.{$core->id}", 'Core e estabilidade')
            ->call('rename', $core->id)
            ->assertHasNoErrors()
            ->call('move', $core->id, -1);

        $names = $this->trainer->categories()->pluck('name')->all();

        $this->assertSame('Core e estabilidade', $names[1]);
        $this->assertSame('Membros superiores', $names[2]);
    }

    public function test_new_exercise_in_mobility_block_gets_mobility_category(): void
    {
        $sheet = WorkoutSheet::create(['trainer_id' => $this->trainer->id, 'name' => 'Treino A']);

        Livewire::test(SheetEdit::class, ['sheet' => $sheet])
            ->call('removeBlock', 0)
            ->call('addBlock', 'mobility')
            ->set('blocks.0.items.0.exercise_name', 'Hip rock')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Mobilidade', Exercise::where('name', 'Hip rock')->first()->category->name);
    }

    public function test_categories_page_opens(): void
    {
        $this->get(route('trainer.categories'))->assertOk()->assertSee('Pliometria');
    }
}
