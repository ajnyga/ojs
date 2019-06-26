<?php

/**
 * @file api/v1/submissions/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

import('lib.pkp.api.v1.submissions.PKPSubmissionHandler');
import('classes.core.Services');

class SubmissionHandler extends PKPSubmissionHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->_endpoints['GET'][] = [
			'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}/galleys',
			'handler' => array($this, 'getGalleys'),
			'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR],
		];
	}

	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		$routeName = $this->getSlimRequest()->getAttribute('route')->getName();

		if (in_array($routeName, ['getGalleys'])) {
			import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
			$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get the galleys of a submission
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getGalleys($slimRequest, $response, $args) {
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$publishedSubmission = null;
		if ($submission && $context) {
			$publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO');
			$publishedSubmission = $publishedSubmissionDao->getPublishedSubmissionByBestSubmissionId(
				(int) $context->getId(),
				$submission->getId(),
				true
			);
		}

		if (!$submission || !$publishedSubmission) {
			return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
		}

		$data = array();

		$galleys = $publishedSubmission->getGalleys();
		if (!empty($galleys)) {
			$galleyService = Services::get('galley');
			$args = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
				'parent' => $publishedSubmission,
			);
			foreach ($galleys as $galley) {
				$data[] = $galleyService->getFullProperties($galley, $args);
			}
		}

		return $response->withJson($data, 200);
	}
}
