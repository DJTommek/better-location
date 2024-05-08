<?php declare(strict_types=1);

namespace App\Web\Homepage;

use App\Web\MainPresenter;

class HomepagePresenter extends MainPresenter
{
	public function beforeRender(): void
	{
		$this->setTemplateFilename('homepage.latte');
	}
}

