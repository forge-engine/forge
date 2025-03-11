<?php

namespace Forge\Core\Dto;

use DateTimeImmutable;
use JsonSerializable;

abstract readonly class BaseDto implements JsonSerializable
{
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toCreate(): array
    {
        $data = $this->toArray();
        unset($data["id"]);

        if (isset($data["createdAt"])) {
            $data["created_at"] =
                $data["createdAt"] instanceof DateTimeImmutable
                    ? $data["createdAt"]->format("Y-m-d H:i:s")
                    : $data["createdAt"];
            unset($data["createdAt"]);
        }
        if (isset($data["updatedAt"])) {
            $data["updated_at"] =
                $data["updatedAt"] instanceof DateTimeImmutable
                    ? $data["updatedAt"]->format("Y-m-d H:i:s")
                    : $data["updatedAt"];
            unset($data["updatedAt"]);
        }

        return $data;
    }

    public function toUpdate(): array
    {
        $data = $this->toArray();
        $updateData = [];
        unset($data["id"]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                if (
                    $key === "createdAt" &&
                    $value instanceof DateTimeImmutable
                ) {
                    $updateData["created_at"] = $value->format("Y-m-d H:i:s");
                } elseif (
                    $key === "updatedAt" &&
                    $value instanceof DateTimeImmutable
                ) {
                    $updateData["updated_at"] = $value->format("Y-m-d H:i:s");
                } else {
                    $updateData[$key] = $value;
                }
            }
        }
        return $updateData;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
