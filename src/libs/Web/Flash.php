<?php declare(strict_types=1);

namespace App\Web;

enum Flash: string
{
	case SUCCESS = 'success';
	case INFO = 'info';
	case WARNING = 'warning';
	case ERROR = 'danger';
}
