<?php

/**
 * @file controllers/modals/review/ReviewerSubmissionMetadataHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSubmissionMetadataHandler
 * @ingroup controllers_modals_submissionMetadata
 *
 * @brief Display submission metadata to reviewers.
 */

// Import the base Handler.
import('classes.handler.Handler');

class ReviewerSubmissionMetadataHandler extends handler {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_REVIEWER), array('display'));
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display metadata
	 */
	function display($args, $request) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$metadata = $submission->getLocalizedTitle();
		if ($reviewAssignment->getReviewMethod() != SUBMISSION_REVIEW_METHOD_DOUBLEBLIND) { /* SUBMISSION_REVIEW_METHOD_BLIND or _OPEN */
			$metadata .= $submission->getAuthorString();			
		}
		$metadata .= $submission->getLocalizedAbstract();	
		return new JSONMessage(true, $metadata);
	}


}

