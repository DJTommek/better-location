<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use unreal4u\TelegramAPI\Telegram;

enum DatetimeFormat: string
{
	/** Displays the time relative to the current time. Cannot be combined with any other control characters. */
	case RELATIVE = 'r';
	/** Displays the day of the week in the user's localized language. */
	case WEEK_DAY = 'w';
	/** Displays the date in short form (e.g., “17.03.22”). */
	case DATE_SHORT = 'd';
	/** Displays the date in long form (e.g., “March 17, 2022”). */
	case DATE_LONG = 'D';
	/** Displays the time in short form (e.g., “22:45”). */
	case TIME_SHORT = 't';
	/** Displays the time in long form (e.g., “22:45:00”). */
	case TIME_LONG = 'T';
}
