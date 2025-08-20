<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'login_attempts',
        'locked_until',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function isLocked(): bool
    {
        return $this->locked_until && Carbon::now()->lt($this->locked_until);
    }

    public function incrementLoginAttempts()
    {
        $this->login_attempts++;
        
        if ($this->login_attempts >= 5) {
            $this->locked_until = Carbon::now()->addMinutes(30);
        }
        
        $this->save();
    }

    public function resetLoginAttempts()
    {
        $this->login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }
}