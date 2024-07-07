<?php declare(strict_types=1);

namespace App\Web\Favorites;

use App\Repository\FavouritesRepository;
use App\Web\Flash;
use App\Web\MainPresenter;
use Tracy\Debugger;

class FavoritesPresenter extends MainPresenter
{
	public function __construct(
		private readonly FavouritesRepository $favoritesRepository,
		FavoritesTemplate $template,
	) {
		$this->template = $template;
	}

	public function action(): void
	{
		if ($this->login->isLogged() === false) {
			$this->renderForbidden();
		}

		$this->template->favorites = $this->favoritesRepository->byUserId($this->user->getId());
		if ($this->isPostRequest()) {
			$action = $this->request->getPost('action');
			match ($action) {
				'delete' => $this->actionDelete(),
				'rename' => $this->actionRename(),
				default => $this->redirect('/favorites'),
			};
		}
	}

	private function actionDelete(): void
	{
		try {
			$id = (int)$this->request->getPost('id');
			$favorite = $this->favoritesRepository->byIdAndUserId($id, $this->user->getId());
			if ($favorite == null) {
				$this->flashMessage(sprintf('Favorite ID <b>%s</b> was not found.', $id), Flash::ERROR);
				return;
			}
			$this->favoritesRepository->remove($favorite->id);
			$this->flashMessage(sprintf('Favorite <b>%s</b> was deleted.', htmlspecialchars($favorite->title)), Flash::SUCCESS);
			$this->redirect('/favorites');
		} catch (\DomainException $exception) {
			$this->flashMessage($exception->getMessage(), Flash::ERROR);
		} catch (\Throwable $exception) {
			$message = sprintf(
				'Error occured while deleting favorite <b>%s</b>, try again later.',
				htmlspecialchars($favorite->title ?? 'N/A'),
			);
			$this->flashMessage($message, Flash::ERROR);
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	private function actionRename(): void
	{
		try {
			$id = (int)$this->request->getPost('id');
			$favorite = $this->favoritesRepository->byIdAndUserId($id, $this->user->getId());
			if ($favorite == null) {
				$this->flashMessage(sprintf('Favorite ID <b>%s</b> was not found.', $id), Flash::ERROR);
				return;
			}

			$newTitle = $this->request->getPost('title') ?? '';
			$this->favoritesRepository->rename($favorite->id, $newTitle);
			$this->flashMessage(sprintf('Favorite was renamed to <b>%s</b>.', $newTitle), Flash::SUCCESS);
			$this->redirect('/favorites');
		} catch (\DomainException $exception) {
			$this->flashMessage($exception->getMessage(), Flash::ERROR);
		} catch (\Throwable $exception) {
			$message = sprintf(
				'Error occured while renaming favorite from <b>%s</b> to <b>%s</b>, try again later.',
				htmlspecialchars($favorite->title ?? 'N/A'),
				$newTitle ?? 'N/A',
			);
			$this->flashMessage($message, Flash::ERROR);
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	public function beforeRender(): void
	{
		$this->setTemplateFilename('favorites.latte');
	}
}

