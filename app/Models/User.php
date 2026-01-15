<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Add this:
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // <-- add HasRoles here

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
	
    public function microsoftAccount()
    {
        return $this->hasOne(\App\Models\MicrosoftAccount::class);
    }

    public function ownedCalendarEvents()
    {
        return $this->hasMany(\App\Models\CalendarEvent::class, 'owner_user_id');
    }

    public function assignedCalendarEvents()
    {
        return $this->hasMany(\App\Models\CalendarEvent::class, 'assigned_to_user_id');
    }

    // 👉 ADD THIS RIGHT HERE
    public function calendarPreference()
    {
        return $this->hasOne(\App\Models\UserCalendarPreference::class);
    }
	
}
