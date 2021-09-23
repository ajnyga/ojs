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

use APP\facades\Repo;

use APP\handler\Handler;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;

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
        $router = $request->getRouter();
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

        $count = $context->getData('itemsPerPage') ? $context->getData('itemsPerPage') : Config::getVar('interface', 'items_per_page');
        $offset = $page > 1 ? ($page - 1) * $count : 0;

        $collector = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterBySectionIds([(int) $section->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDER_DIR_ASC);

        $total = Repo::submission()->getCount($collector);
        $result = Repo::submission()->getMany($collector->limit($count)->offset($offset));

        $submissions = [];
        $issueUrls = [];
        $issueNames = [];
        foreach ($result as $submission) {
            $submissions[] = $submission;
            $issue = Repo::issue()->getBySubmissionId($submission->getId());
            $issueUrls[$submission->getId()] = $router->url($request, $context->getPath(), 'issue', 'view', $issue->getBestIssueId(), null, null, true);
            $issueNames[$submission->getId()] = $issue->getIssueIdentification();
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
            'issueUrls' => $issueUrls,
            'issueNames' => $issueNames,
            'showingStart' => $showingStart,
            'showingEnd' => $showingEnd,
            'total' => $total,
            'nextPage' => $nextPage,
            'prevPage' => $prevPage,
        ]);

        $templateMgr->display('frontend/pages/sections.tpl');
    }
}
