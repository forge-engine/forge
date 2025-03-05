<?php

namespace Forge\Modules\ForgeAuth\DTO;

use Forge\Modules\ForgeExplicitOrm\DataTransferObjects\BaseDTO;

class UserDTO extends BaseDTO
{
    public int $id;
    public string $email;
    public string $password;
    public string $created_at;
    public ?string $updated_at = null;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
    }
}