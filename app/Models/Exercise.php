<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'trainer_id',
        'category_id',
        'name',
        'instructions',
        'youtube_url',
        'video_start_seconds',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Tem vídeo do YouTube ou pelo menos uma foto */
    public function scopeWithMedia(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNotNull('youtube_url')->orWhereHas('images'));
    }

    public function scopeWithoutMedia(Builder $query): void
    {
        $query->whereNull('youtube_url')->whereDoesntHave('images');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ExerciseImage::class)->orderBy('position');
    }

    public function hasMedia(): bool
    {
        return filled($this->youtube_url) || $this->images()->exists();
    }

    /** ID do vídeo a partir de youtube.com/watch?v=, youtu.be/ ou /shorts/ */
    public function youtubeId(): ?string
    {
        if (blank($this->youtube_url)) {
            return null;
        }

        preg_match('~(?:v=|youtu\.be/|/shorts/|/embed/)([A-Za-z0-9_-]{11})~', $this->youtube_url, $m);

        return $m[1] ?? null;
    }

    public function youtubeEmbedUrl(): ?string
    {
        $id = $this->youtubeId();

        if (! $id) {
            return null;
        }

        $start = $this->video_start_seconds ? '?start='.$this->video_start_seconds : '';

        return "https://www.youtube-nocookie.com/embed/{$id}{$start}";
    }
}
