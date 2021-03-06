<?php

namespace App\Models;

use App\Notifications\TimedIn;
use App\Notifications\TimedOut;
use EditorJS\EditorJS;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'time',
        'type',
        'location',
        'notes',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            switch ($model->type) {
                case 'in':
                    $model->user->notify(new TimedIn($model));
                    break;
                case 'out':
                    $model->user->notify(new TimedOut($model));
                    break;
            }
        });
    }

    /**
     * Get IP Address alias.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ip()
    {
        return $this->belongsTo(IPAddress::class, 'ip_address', 'v4');
    }

    /**
     * Get user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get time attribute.
     *
     * @param  string $time
     * @return \Illuminate\Support\Carbon
     */
    public function getTimeAttribute($time)
    {
        return Carbon::parse($time);
    }

    /**
     * Get number of entry.
     *
     * @return int
     */
    public function getEntryAttribute()
    {
        $index = self::select('time')
            ->where('user_id', $this->user_id)
            ->where('type', $this->type)
            ->orderBy('time')
            ->get()
            ->map(function ($model) {
                return $model->getOriginal('time');
            })
            ->search($this->attributes['time']);

        return $index + 1;
    }
}
