<?php

declare(strict_types=1);

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

namespace Cyberelk\Brofix\FormEngine;

use TYPO3\CMS\Backend\Form\FormDataGroup\OrderedProviderList;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data provider group for checking if field should be checked for
 * broken links.
 *
 * @internal
 */
class FieldShouldBeCheckedWithFlexform implements FormDataGroupInterface
{
    /**
     * Compile form data
     *
     * @param mixed[] $result Initialized result array
     * @return mixed[] Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result): array
    {
        /**
         * @var OrderedProviderList $orderedProviderList
         */
        $orderedProviderList = GeneralUtility::makeInstance(OrderedProviderList::class);
        $orderedProviderList->setProviderList(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['brofixFieldShouldBeCheckedWithFlexform']
        );

        return $orderedProviderList->compile($result);
    }
}
