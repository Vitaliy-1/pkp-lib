<?php

/**
 * @file tests/classes/bodyText/PublicationPdfMetadataTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationPdfMetadataTest
 *
 * @ingroup tests_classes_bodyText
 *
 * @brief Test how the PDF header displays localized publication metadata.
 */

namespace PKP\tests\classes\bodyText;

use APP\publication\Publication;
use PKP\affiliation\Affiliation;
use PKP\author\Author;
use PKP\context\Context;
use PKP\core\DataObject;
use PKP\core\Registry;
use PKP\site\Site;
use PKP\tests\PKPTestCase;

class PublicationPdfMetadataTest extends PKPTestCase
{
    private Publication $publication;
    private Context $context;

    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'site'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $site = new Site();
        $site->setPrimaryLocale('en');
        Registry::set('site', $site);
        $this->mockRequest();

        $this->context = $this->createStub(Context::class);
        $this->context->method('getPrimaryLocale')->willReturn('en');
        $this->context->method('getLocalizedName')->willReturn('Test Journal');
        $this->publication = new Publication();
        $this->publication->setData('locale', 'fr_CA');
    }

    private function renderMetadata(?DataObject $issue = null): string
    {
        return view('pkp::bodyText.pdfMetadata', [
            'publication' => $this->publication,
            'context' => $this->context,
            'issue' => $issue,
        ])->render();
    }

    public function testRendersLocalizedMetadataWithoutAnIssue(): void
    {
        $this->publication->setData('title', 'An <i>example</i>', 'en');
        $this->publication->setData('abstract', '<p>English abstract</p>', 'en');
        $this->publication->setData('abstract', '<p>Résumé</p>', 'fr_CA');
        $this->publication->setData('keywords', [['name' => 'A&B']], 'en');

        $html = $this->renderMetadata();

        self::assertStringContainsString('An <i>example</i>', $html);
        self::assertStringContainsString('<p>Résumé</p>', $html);
        self::assertStringNotContainsString('English abstract', $html);
        self::assertStringContainsString('A&amp;B', $html);
        self::assertStringContainsString('<i>Test Journal</i>', $html);
        self::assertStringNotContainsString('<i>Test Journal</i>,', $html);
    }

    public function testUsesFirstAvailableLocaleForEachAffiliation(): void
    {
        $author = new Author();
        $author->setData('contributorType', 'PERSON');
        $author->setData('givenName', 'Alice & Bob', 'fr_CA');
        $author->setData('submissionLocale', 'de');
        foreach ([
            ['fr_CA' => 'Université', 'en' => 'University'],
            ['en' => 'Research Centre', 'de' => 'Forschungszentrum'],
            ['de' => 'Institut'],
            ['it' => 'Istituto'],
        ] as $names) {
            $affiliation = new Affiliation();
            $affiliation->setName($names);
            $author->addAffiliation($affiliation);
        }
        $this->publication->setData('authors', [$author]);

        $html = $this->renderMetadata();

        self::assertStringContainsString('Alice &amp; Bob', $html);
        self::assertStringContainsString('<i>Université; Research Centre; Institut</i>', $html);
    }

    public function testOmitsHiddenAuthors(): void
    {
        $author = new Author();
        $author->setData('contributorType', 'PERSON');
        $author->setData('givenName', 'Alice', 'fr_CA');
        $this->publication->setData('authors', [$author]);
        $this->publication->setData('hideAuthor', true);

        self::assertStringNotContainsString('Alice', $this->renderMetadata());
    }

    public function testCitationShowsVolumeAndOriginalPageRangeButNotHiddenYear(): void
    {
        $this->publication->setData('pages', '2 - 9');
        $issue = new DataObject();
        $issue->setData('volume', 12);
        $issue->setData('showVolume', true);
        $issue->setData('year', 2026);
        $issue->setData('showYear', false);

        $html = $this->renderMetadata($issue);

        self::assertStringContainsString(__('issue.vol', locale: 'fr_CA') . ' 12', $html);
        self::assertStringContainsString(__('issue.pagesAbbreviated', locale: 'fr_CA') . ' 2 - 9', $html);
        self::assertStringNotContainsString('2026', $html);
    }
}
