<?php
/**
 * @file classes/services/PublicationService.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationService
 * @ingroup services
 *
 * @brief Extends the base publication service class with app-specific
 *  requirements.
 */
namespace APP\Services;

use \Application;
use \Services;
use \PKP\Services\PKPPublicationService;

class PublicationService extends PKPPublicationService {

	/**
	 * Initialize hooks for extending PKPPublicationService
	 */
	public function __construct() {
		\HookRegistry::register('Publication::delete', [$this, 'deletePublication']);
		\HookRegistry::register('Publication::validate', [$this, 'validatePublication']);
		\HookRegistry::register('Publication::version', [$this, 'versionPublication']);
	}

	/**
	 * Make additional validation checks
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option array Validation errors already identified
	 *		@option string One of the VALIDATE_ACTION_* constants
	 *		@option array The props being validated
	 *		@option array The locales accepted for this object
	 *    @option string The primary locale for this object
	 * ]
	 */
	public function validatePublication($hookName, $args) {
		$errors =& $args[0];
		$action = $args[1];
		$props = $args[2];
		$allowedLocales = $args[3];
		$primaryLocale = $args[4];

		// Ensure that the specified section exists
		if (isset($props['sectionId'])) {
			$section = Application::get()->getSectionDAO()->getById($props['sectionId']);
			if (!$section) {
				$errors['sectionId'] = [__('publication.invalidSection')];
			}
		}

		// Get the section so we can validate section abstract requirements
		if (!$section && isset($props['id'])) {
			$publication = Services::get('publication')->get($props['id']);
			$section = Services::get('section')->get($publication->getData('sectionId'));
		}

		if ($section) {

			// Require abstracts if the section requires them
			if ($action === VALIDATE_ACTION_ADD && !$section->getData('abstractsNotRequired') && empty($props['abstract'])) {
				$errors['abstract'][$primaryLocale] = [__('author.submit.form.abstractRequired')];
			}

			if (isset($props['abstract']) && empty($errors['abstract'])) {

				// Require abstracts in the primary language if the section requires them
				if (!$section->getData('abstractsNotRequired')) {
					if (empty($props['abstract'][$primaryLocale])) {
						if (!isset($errors['abstract'])) {
							$errors['abstract'] = [];
						};
						$errors['abstract'][$primaryLocale] = [__('author.submit.form.abstractRequired')];
					}
				}

				// Check the word count on abstracts
				foreach ($allowedLocales as $localeKey) {
					if (empty($props['abstract'][$localeKey])) {
						continue;
					}
					$wordCount = preg_split('/\s+/', trim(str_replace('&nbsp;', ' ', strip_tags($props['abstract'][$localeKey]))));
					$wordCountLimit = $section->getData('wordCount');
					if ($wordCountLimit && $wordCount > $wordCountLimit) {
						if (!isset($errors['abstract'])) {
							$errors['abstract'] = [];
						};
						$errors['abstract'][$localeKey] = [__('publication.wordCountLong', ['limit' => $wordCountLimit, 'count' => $wordCount])];
					}
				}
			}
		}

		// Ensure that the issueId exists
		if (isset($props['issueId']) && empty($errors['issueId'])) {
			$issue = Services::get('issue')->get($props['issueId']);
			if (!$issue) {
				$errors['issueId'] = [__('publication.invalidIssue')];
			}
		}
	}

	/**
	 * Copy OJS-specific objects when a new publication version is created
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option Publication The new version of the publication
	 *		@option Publication The old version of the publication
	 *		@option Request
	 * ]
	 */
	public function versionPublication($hookName, $args) {
		$newPublication = $args[0];
		$oldPublication = $args[1];
		$request = $args[2];

		$galleys = $oldPublication->getData('galleys');
		if (!empty($galleys)) {
			foreach ($galleys as $galley) {
				$newGalley = clone $galley;
				$newGalley->setData('id', null);
				$newGalley->setData('publicationId', $newPublication->getId());
				Services::get('galley')->add($newGalley, $request);
			}
		}

		$newPublication->setData('galleys', $this->get($newPublication->getId())->getData('galleys'));
	}

	/**
	 * Delete OJS-specific objects when a publication is deleted
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option Publication The publication being deleted
	 * ]
	 */
	public function deletePublication($hookName, $args) {
		$publication = $args[0];

		$galleys = Services::get('galley')->getMany(['publicationIds' => $publication->getId()]);
		foreach ($galleys as $galley) {
			Services::get('galley')->delete($galley);
		}
	}

	/**
	 * Is this publication published?
	 *
	 * @param Publication $publication
	 * @param array $dependencies [
	 * 		@option ASSOC_TYPE_ISSUE
	 * ]
	 * @return boolean
	 */
	public function isPublished($publication, $dependencies = []) {
		$isPublished = parent::isPublished($publication);

		// If a publication is assigned to an issue, require the issue
		// to be published before the publication is considered published.
		if ($isPublished && $publication->getData('issueId')) {
			if (!isset($dependencies[ASSOC_TYPE_ISSUE])) {
				return false;
			}
			$issue = $dependencies[ASSOC_TYPE_ISSUE];
			$isPublished = $issue->getData('published') && $issue->getData('datePublished') < \Core::getCurrentDate();
		}

		return $isPublished;
	}
}
