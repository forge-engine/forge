<?php
declare(strict_types=1);

namespace Forge\Traits;

use DateTimeImmutable;

trait DTOHelper
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
	
		if (isset($data["created_at"])) {
			$data["created_at"] =
				$data["created_at"] instanceof DateTimeImmutable
					? $data["created_at"]->format("Y-m-d H:i:s")
					: $data["created_at"];
			unset($data["created_at"]);
		}
		if (isset($data["updated_at"])) {
			$data["updated_at"] =
				$data["updated_at"] instanceof DateTimeImmutable
					? $data["updated_at"]->format("Y-m-d H:i:s")
					: $data["updated_at"];
			unset($data["updated_at"]);
		}
	
		if (isset($data["deleted_at"])) {
			$data["deleted_at"] =
				$data["deleted_at"] instanceof DateTimeImmutable
					? $data["deleted_at"]->format("Y-m-d H:i:s")
					: $data["deleted_at"];
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
					$key === "created_at" &&
					$value instanceof DateTimeImmutable
				) {
					$updateData["created_at"] = $value->format("Y-m-d H:i:s");
				} elseif (
					$key === "updated_at" &&
					$value instanceof DateTimeImmutable
				) {
					$updateData["updated_at"] = $value->format("Y-m-d H:i:s");
				} elseif (
					$key === "deleted_at" &&
					$value instanceof DateTimeImmutable
				) {
					$updateData["deleted_at"] = $value->format("Y-m-d H:i:s");
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