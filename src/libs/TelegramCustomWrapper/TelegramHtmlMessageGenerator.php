<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\HtmlMessageGenerator;

readonly class TelegramHtmlMessageGenerator extends HtmlMessageGenerator
{
	protected const NEWLINE = TelegramHelper::NEW_LINE;
}
