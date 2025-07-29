<?php declare(strict_types=1);

namespace App\IgnoreFilter;

use App\User;

final readonly class IgnoreFilter
{
	/**
	 * @param list<int> $ignoredSenderIds
	 */
	public function __construct(
		public array $ignoredSenderIds,
	) {
	}

	public function isSenderIgnored(int $userId): bool
	{
		return in_array($userId, $this->ignoredSenderIds, true);
	}

	public function matches(User $user): bool
	{
		if ($this->isSenderIgnored($user->getId())) {
			return true;
		}

		return false;
	}
}
