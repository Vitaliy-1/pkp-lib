<?php

/**
 * @file lib/pkp/pages/bodyText/BodyTextHandler.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BodyTextHandler
 *
 * @ingroup pages_bodyText
 *
 * @brief Render publication metadata for body text PDFs.
 */

namespace PKP\pages\bodyText;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\template\TemplateManager;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PublicationAccessPolicy;
use PKP\security\authorization\WorkflowStageAccessPolicy;
use PKP\security\Role;

class BodyTextHandler extends Handler
{
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment([
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SITE_ADMIN,
            Role::ROLE_ID_SUB_EDITOR,
            Role::ROLE_ID_ASSISTANT,
        ], ['renderPdfMetadata']);
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        $this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', WORKFLOW_STAGE_ID_PRODUCTION));
        $this->addPolicy(new PublicationAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Return the HTML that appears before the article text in both the PDF preview and the final PDF
     *
     * @param array $args Path arguments
     *
     * @throws \Exception
     */
    public function renderPdfMetadata(array $args, Request $request): string
    {
        $publication = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_PUBLICATION);
        $context = $request->getContext();
        $issueId = $publication->getData('issueId');
        $issue = $issueId ? Repo::issue()->get($issueId, $context->getId()) : null;

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign(compact('publication', 'context', 'issue'));

        // Prevent browsers from storing unpublished PDF metadata.
        header('Cache-Control: no-store');
        return $templateMgr->fetch('pkp::bodyText.pdfMetadata');
    }
}
