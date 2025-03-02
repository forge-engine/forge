<?php

namespace Forge\Modules\ForgeApi;

use Forge\Http\Response;

class ApiResponse extends Response
{
    public function json(array $data, int $status = 200): self
    {
        print_r("json route");
        return parent::json([
            'status' => $status >= 400 ? 'error' : 'success',
            'code' => $status,
            'data' => $data,
        ])->setStatusCode($status);
    }

    public function error(string $message, int $status = 400): self
    {
        return $this->json([
            'error' => $message,
            'code' => $status
        ], $status);
    }

    public function paginated(array $data, int $total, int $perPage): self
    {
        return $this->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => 1//request()->getQuery('page', 1)
            ]
        ]);
    }
}