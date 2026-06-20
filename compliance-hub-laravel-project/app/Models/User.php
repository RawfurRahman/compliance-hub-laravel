<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // We remove the 'hashed' cast here to use the mutator below
        'otp_expires_at' => 'datetime',
    ];

    /**
     * Define the relationship between User and Role.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Helper function to check if the user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Interact with the user's password.
     * This is an attribute mutator that automatically hashes the password.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Hash::make($value),
        );
    }

    /**
     * Get the projects this user is assigned to (as an Auditor or Customer).
     */
    public function assignedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_user')->withTimestamps();
    }

    public function assignedMeetings()
    {
        return $this->belongsToMany(Meeting::class, 'meeting_user')->withTimestamps();
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function subUsers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function isPrimaryCustomer(): bool
    {
        return $this->hasRole('Customer') && is_null($this->parent_id);
    }

    public function getOrganizationUsers()
    {
        if ($this->isPrimaryCustomer()) {
            return $this->subUsers()->get()->push($this);
        } elseif ($this->parent_id) {
            return User::where('parent_id', $this->parent_id)->orWhere('id', $this->parent_id)->get();
        }
        return collect([$this]);
    }

    public function assignedFindings()
    {
        return $this->hasMany(PciDssFinding::class, 'assigned_to_user_id');
    }
}
