<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use HasinHayder\Tyro\Concerns\HasTyroRoles;
use HasinHayder\TyroLogin\Traits\HasTwoFactorAuth;
use HasinHayder\TyroDashboard\Traits\HasProfilePhoto;

class User extends Authenticatable
{
    use HasApiTokens, HasTyroRoles, HasTwoFactorAuth, HasProfilePhoto;


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

    // ইউজারের শিক্ষক প্রোফাইল পাওয়ার জন্য
    public function teacherProfile()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute()
    {
        if (isset($this->use_gravatar) && $this->use_gravatar && $this->email) {
            return $this->gravatar_url;
        }

        if ($this->profile_photo_path) {
            return str_starts_with($this->profile_photo_path, 'http')
                ? $this->profile_photo_path
                : '/storage/' . $this->profile_photo_path;
        }

        return $this->defaultProfilePhotoUrl();
    }
}
