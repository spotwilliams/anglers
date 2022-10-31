<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property integer id
 * @property integer club_id
 * @property Collection buddies
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_friend_of_user' => 'boolean',
        'last_trip_at' => 'datetime',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function friends()
    {
        return $this->belongsToMany(related: User::class, table: 'friends', foreignPivotKey: 'user_id', relatedPivotKey: 'friend_id')->withTimestamps();
    }

    public function scopeVisibleTo($query, User $user)
    {
        $query->where(function ($query) use ($user) {
            $query->where('club_id', $user->club_id)
             ->orWhereIn('id', $user->friends()->select('friend_id'));

        });
    }


    public function scopeWithIsFriendOfUser($query, User $user)
    {
        // add a new column to the result set
        $query->addSelect([
            'is_friend_of_user' => Friend::query()
                // if count gives us a value bigger than one, it means that that particular row (aka user) is friend of the user (parameter)
                // friends [user_id, friend_id].
                ->selectRaw('count(1)')
                // if the user is friend (second part of the relation) of ...
                ->whereColumn(first: 'users.id', operator: '=', second: 'friends.friend_id')
                // the owner of this relation (first part of the relation)
                ->where('friends.user_id', $user->id)
        ]);
    }

    public function scopeOrderByFriendsFirst($query, User $user)
    {
        $query->orderBy(function ($query) use ($user) {
            $query
                ->from('friends')
                ->selectRaw('true')
                ->whereColumn(first: 'friends.friend_id', operator: '=', second: 'users.id')
                ->where('user_id', $user->id)
                ->limit(1);
        }, 'asc');
    }

    public function scopeWithLastTripDate($query)
    {
        $query->addSelect(['last_trip_at' =>
            Trip::query()
                ->select('went_at')
                ->from('trips')
                ->whereColumn('user_id', 'users.id')
                ->latest('went_at')
                ->limit(1)
        ]);
    }

    public function scopeWithLastTripLake($query)
    {
        $query->addSelect(['last_trip_lake' =>
            Trip::query()
                ->select('lake')
                ->from('trips')
                ->whereColumn('user_id', 'users.id')
                ->latest('went_at')
                ->limit(1)]);
    }


    public function lastTrip()
    {
        // In this case, laravel assumes that user's table has a column named last_trip_id
        // we will make laravel believes that columns exists by using a subquery
        return $this->belongsTo(Trip::class);
    }

    public function scopeWithLastTrip($query)
    {
        $query->addSelect(['last_trip_id' => Trip::query()
            ->select('id')
            ->from('trips')
            ->whereColumn('user_id', 'users.id')
            ->latest('went_at')
            ->limit(1)
        ])->with('lastTrip');
    }
}
