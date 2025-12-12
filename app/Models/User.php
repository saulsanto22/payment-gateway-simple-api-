<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
            'role' => UserRole::class,
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    /**
     * Check if user is merchant
     */
    public function isMerchant(): bool
    {
        return $this->role === UserRole::MERCHANT;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(UserRole|string $role): bool
    {
        if ($role instanceof UserRole) {
            return $this->role === $role;
        }

        return $this->role->value === $role;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * Method ini return ID user yang akan disimpan di JWT payload
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Return primary key (id)
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * Method ini return data tambahan yang mau disimpan di JWT payload
     * Contoh: role, email, permissions
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->value,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
