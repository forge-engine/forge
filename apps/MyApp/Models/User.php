<?php

namespace MyApp\Models;

use Forge\Core\Models\Model;

/**
 * @property string $name The name of the user.
 * @property string $email The email of the user.
 * @property int $id The ID of the user.
 */
class User extends Model
{
    protected static ?string $table = 'users';
    protected array $fillable = ['name', 'email'];

}