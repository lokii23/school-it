<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedNotification extends Model
{
    protected $fillable = ['user_id', 'actor_id', 'type', 'post_id', 'comment_id', 'data', 'read_at'];

    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
    public function post()  { return $this->belongsTo(Post::class); }

    // A new notification. You are never notified about your own actions.
public static function sendNotification(
    int $to,
    int $actor,
    string $type,
    int $postId,
    ?int $commentId = null
): void {
    if ($to === $actor) {
        return;
    }

    static::create([
        'user_id' => $to,
        'actor_id' => $actor,
        'type' => $type,
        'post_id' => $postId,
        'comment_id' => $commentId,
    ]);
}

    // One notification per person per post. Changing your reaction updates it instead of adding another.
    public static function reaction(Post $post, int $actor, string $reaction): void
    {
        if ($post->user_id === $actor) {
            return;
        }

        $n = static::firstOrNew([
            'user_id' => $post->user_id, 'actor_id' => $actor, 'post_id' => $post->id, 'type' => 'reaction',
        ]);

        $n->data = ['reaction' => $reaction];
        $n->save();
    }
}