<?php

/**
 * @file pages/sections/SectionsHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionsHandler
 * @ingroup pages_articles
 *
 * @brief Handle requests for sections functions.
 *
 */

use APP\handler\Handler;

use APP\template\TemplateManager;
use PKP\submission\PKPSubmission;

class SectionsHandler extends Handler
{
    /** sections associated with the request **/
    public $sections;

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
        $this->addPolicy(new ContextRequiredPolicy($request));

        import('classes.security.authorization.OjsJournalMustPublishPolicy');
        $this->addPolicy(new OjsJournalMustPublishPolicy($request));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * View a section
     *
     * @param $args array [
     *		@option string Section ID
     *		@option string page number
     * ]
     *
     * @param $request PKPRequest
     *
     * @return null|JSONMessage
     */
    public function section($args, $request)
    {
        $sectionUrlPath = $args[0] ?? null;
        $page = isset($args[1]) && ctype_digit((string) $args[1]) ? (int) $args[1] : 1;
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : CONTEXT_ID_NONE;

        // The page $arg can only contain an integer that's not 1. The first page
        // URL does not include page $arg
        if (isset($args[1]) && (!ctype_digit((string) $args[1]) || $args[1] == 1)) {
            $request->getDispatcher()->handle404();
            exit;
        }

        if (!$sectionUrlPath || !$contextId) {
            $request->getDispatcher()->handle404();
            exit;
        }

        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = $sectionDao->getByContextId($contextId);

        $sectionExists = false;
        while ($section = $sections->next()) {
            if ($section->getData('urlPath') === $sectionUrlPath) {
                $sectionExists = true;
                break;
            }
        }

        // If section does not exist or is not browsable
        if (!$sectionExists || $section->getNotBrowsable()) {
            $request->getDispatcher()->handle404();
            exit;
        }

        import('classes.submission.Submission'); // Import status constants

        $params = [
            'contextId' => $contextId,
            'count' => $context->getData('itemsPerPage'),
            'offset' => $page ? ($page - 1) * $context->getData('itemsPerPage') : 0,
            'orderBy' => 'datePublished',
            'sectionIds' => [(int) $section->getId()],
            'status' => PKPSubmission::STATUS_PUBLISHED,
        ];

        $result = Services::get('submission')->getMany($params);
        $total = Services::get('submission')->getMax($params);

        if ($page > 1 && !$result->valid()) {
            $request->getDispatcher()->handle404();
            exit;
        }

        $submissions = [];
        foreach ($result as $submission) {
            $submissions[] = $submission;
        }

        $showingStart = $params['offset'] + 1;
        $showingEnd = min($params['offset'] + $params['count'], $params['offset'] + count($submissions));
        $nextPage = $total > $showingEnd ? $page + 1 : null;
        $prevPage = $showingStart > 1 ? $page - 1 : null;

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'section' => $section,
            'sectionUrlPath' => $sectionUrlPath,
            'submissions' => $submissions,
            'showingStart' => $showingStart,
            'showingEnd' => $showingEnd,
            'total' => $total,
            'nextPage' => $nextPage,
            'prevPage' => $prevPage,
        ]);

        $templateMgr->display('frontend/pages/sections.tpl');
    }
}
