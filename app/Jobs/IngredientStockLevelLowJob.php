<?php

namespace App\Jobs;

use App\Mail\IngredientLowStockAlertMail;
use App\Models\Ingredient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class IngredientStockLevelLowJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $ingredientId)
    {
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        // force uniqueness in tests to prevent duplicate rejection
        if (isTestingEnvironment()) {
            return uniqid("test_");
        }

        return $this->ingredientId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ingredient = Ingredient::query()->findOrFail($this->ingredientId);

        Mail::to($ingredient->merchant->email)->send(new IngredientLowStockAlertMail($this->ingredientId));
    }
}
