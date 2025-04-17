<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Validation;

use Forge\Core\Validation\Validator;

final class ForgeAuthValidate
{
    public function __construct()
    {
    }

    public static function login(array $data): void
    {
        $rules = [
            'identifier' => ["required"],
            "password" => ["required"]
        ];

        static::validate($data, $rules);
    }

    public static function register(array $data): void
    {
        $rules = [
            "identifier" => ["required", "min:3", "unique:users,identifier"],
            "password" => ["required", "min:8"]
        ];

        $customMessages = [
            "required" => "The :field field is required!",
            "min" => "The :field field must be at least :value characters.",
            "unique" => "The :field is already taken."
        ];

        static::validate($data, $rules, $customMessages);
    }

    private static function validate(array $data, array $rules, array $customMessages = []): void
    {
        $validator = new Validator($data, $rules, $customMessages);
        $validator->validate();
    }
}
