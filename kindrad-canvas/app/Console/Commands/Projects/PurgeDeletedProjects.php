<?php

namespace App\Console\Commands\Projects;

use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('projects:purge-deleted')]
#[Description('Permanently delete projects soft-deleted more than 30 days ago and remove their S3 files')]
class PurgeDeletedProjects extends Command
{
    public function handle(): int
    {
        $projects = Project::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(30))
            ->with(['sourceImage', 'generations'])
            ->get();

        $disk = Storage::disk(config('generation.disk'));

        foreach ($projects as $project) {
            $sourceImage = $project->sourceImage;

            if ($sourceImage !== null) {
                $disk->delete($sourceImage->path);
                $project->source_image_id = null;
                $project->saveQuietly();
                $sourceImage->delete();
            }

            foreach ($project->generations as $generation) {
                if ($generation->result_path !== null) {
                    $disk->delete($generation->result_path);
                }
            }

            $project->generations()->delete();
            $project->forceDelete();
        }

        $this->info("Purged {$projects->count()} projects.");

        return self::SUCCESS;
    }
}
