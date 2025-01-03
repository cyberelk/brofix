<?php

declare(strict_types=1);
namespace Cyberelk\Brofix\Hooks;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cyberelk\Brofix\Repository\BrokenLinkRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

final class PageCalloutsHook implements SingletonInterface
{
    private bool $showPageCalloutBrokenLinksExist = false;

    public function __construct(private BrokenLinkRepository $brokenLinkRepository, ExtensionConfiguration $extensionConfiguration)
    {
        $this->showPageCalloutBrokenLinksExist = (bool)$extensionConfiguration->get('brofix', 'showPageCalloutBrokenLinksExist');
    }

    /**
     * Create flash message for showing information about broken links in page module
     *
     * @param mixed[] $pageInfo
     * @return array{'title'?: string, 'message'?: string, 'state'?: int}:
     */
    public function addMessages(array $pageInfo): array
    {
        // check extension configuration
        if (!$this->showPageCalloutBrokenLinksExist) {
            return [];
        }

        if (!$pageInfo || !is_array($pageInfo)) {
            return [];
        }
        $pageId = (int)($pageInfo['uid']);
        if ($pageId === 0) {
            return [];
        }

        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        if (!$beUser->isAdmin() && !$beUser->check('modules', 'web_brofix')) {
            // no output in case the user does not have access to the "brofix" module
            return [];
        }
        // check user settings (default is 1)
        if (((bool)($beUser->uc['tx_brofix_showPageCalloutBrokenLinksExist'] ?? false)) === false) {
            return [];
        }

        $lang = $this->getLanguageService();

        $count = $this->brokenLinkRepository->getLinkCountForPage($pageId);
        if ($count == 0) {
            // no broken links to report
            return [];
        }

        $message = '<p>' . sprintf(
            ($count === 1 ? $lang->sL('LLL:EXT:brofix/Resources/Private/Language/locallang.xlf:count_singular_broken_links_found_for_page')
                : $lang->sL('LLL:EXT:brofix/Resources/Private/Language/locallang.xlf:count_plural_broken_links_found_for_page'))
                ?: '%d broken links were found on this page',
            $count . '</p>'
        );
        $message .= '<p>' . ($lang->sL('LLL:EXT:brofix/Resources/Private/Language/Module/locallang.xlf:goto') ?: '');
        $message .= ' <a class="btn btn-info" href="' . $this->createBackendUri($pageId) . '">'
            . ($lang->sL('LLL:EXT:brofix/Resources/Private/Language/Module/locallang_mod.xlf:mlang_tabs_tab') ?: 'Brofix')
            . '</a></p>';
        return [
            'title' => '',
            'message' => $message,
            'state' => InfoboxViewHelper::STATE_WARNING
        ];
    }

    protected function createBackendUri(int $pageId, string $route = 'web_brofix'): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($route, ['id' => $pageId]);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
