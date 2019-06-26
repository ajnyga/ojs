<?php

/**
 * @file classes/article/SubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDAO
 * @ingroup article
 * @see Submission
 *
 * @brief Operations for retrieving and modifying Article objects.
 */

import('classes.article.Submission');
import('lib.pkp.classes.submission.PKPSubmissionDAO');

class SubmissionDAO extends PKPSubmissionDAO {

	/**
	 * Return a new data object.
	 * @return Submission
	 */
	public function newDataObject() {
		return new Submission();
	}

	/**
	 * @copydoc SchemaDAO::deleteById
	 */
	function deleteById($submissionId) {
		parent::deleteById($submissionId);

		// $articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		// $articleGalleyDao->deleteByArticleId($submissionId);

		// $articleSearchDao = DAORegistry::getDAO('ArticleSearchDAO');
		// $articleSearchDao->deleteSubmissionKeywords($submissionId);

		// // Delete article citations.
		// $citationDao = DAORegistry::getDAO('CitationDAO');
		// $citationDao->deleteBySubmissionId($submissionId);

		// $articleSearchIndex = Application::getSubmissionSearchIndex();
		// $articleSearchIndex->articleDeleted($submissionId);
		// $articleSearchIndex->submissionChangesFinished();

		$this->flushCache();
	}

	/**
	 * Change the status of the article
	 * @param $articleId int
	 * @param $status int
	 */
	function changeStatus($articleId, $status) {
		$this->update(
			'UPDATE submissions SET status = ? WHERE submission_id = ?',
			array((int) $status, (int) $articleId)
		);

		$this->flushCache();
	}

	/**
	 * Removes articles from a section by section ID
	 * @param $sectionId int
	 */
	function removeSubmissionsFromSection($sectionId) {
		$this->update(
			'UPDATE submissions SET section_id = null WHERE section_id = ?', (int) $sectionId
		);

		$this->flushCache();
	}
}
