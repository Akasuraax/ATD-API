<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'forname',
        'email',
        'password',
        'phone_country',
        'phone_number',
        'gender',
        'birth_date',
        'address',
        'zipcode',
        'siret_number',
        'compagny',
        'status',
        'ban',
        'notification',
        'archive'
    ];

    protected $guarded = [
        'id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'ban' => 'boolean',
        'notification' => 'boolean',
        'archive' => 'boolean',
    ];

    public static function find(mixed $userId)
    {
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'have_roles', 'id_user', 'id_role')->withTimestamps();
    }


    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'sends', 'id_user', 'id_ticket')->withTimestamps();
    }

    public function activities(){
        return $this->belongsToMany(Activity::class, 'participates', 'id_user', 'id_activity')->withPivot('count', 'archive');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'id_user');
    }

    public function demands(){
        return $this->hasMany(Demand::class, 'id_user');
    }

    public function visits(){
        return $this->hasMany(Visit::class, 'id_volunteer');
    }
    public function messages(){
        return $this->hasMany(Message::class, 'id_user');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $roleIds = $this->roles->pluck('id')->toArray();
        $activityIds = $this->activities->pluck('id')->toArray();
        $ticketIds = $this->tickets->pluck('id')->toArray();
        $this->roles()->updateExistingPivot($roleIds, ['archive' => true]);
        $this->activities()->updateExistingPivot($activityIds, ['archive' => true]);
        $this->files()->update(['archive' => true]);
        $this->demands()->update(['archive' => true]);
        $this->visits()->update(['archive' => true]);
        $this->tickets()->updateExistingPivot($ticketIds, ['archive' => true]);
    }
}
