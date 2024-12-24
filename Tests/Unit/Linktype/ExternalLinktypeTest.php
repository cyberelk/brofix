<?php

declare(strict_types=1);
namespace Cyberelk\Brofix\Tests\Unit\Linktype;

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

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Cyberelk\Brofix\CheckLinks\ExcludeLinkTarget;
use Cyberelk\Brofix\CheckLinks\LinkTargetCache\LinkTargetPersistentCache;
use Cyberelk\Brofix\Linktype\ExternalLinktype;
use Cyberelk\Brofix\Tests\Unit\AbstractUnit;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExternalLinktypeTest extends AbstractUnit
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inializeLanguageServiceProphecy();
    }

    /**
     * @test
     */
    public function checkLinkWithExternalUrlNotFoundReturnsFalse(): void
    {
        $httpMethod = 'GET';
        $options = $this->getRequestHeaderOptions($httpMethod);
        $url = 'https://example.org/~not-existing-url';

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(404);

        $exceptionProphecy = $this->prophesize(ClientException::class);
        $exceptionProphecy->hasResponse()
            ->willReturn(true);
        $exceptionProphecy->getResponse()
            ->willReturn($responseProphecy->reveal());

        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $requestFactoryProphecy->request($url, $httpMethod, $options)
            ->willThrow($exceptionProphecy->reveal());
        $subject = $this->instantiateExternalLinktype($requestFactoryProphecy);

        $method = new \ReflectionMethod($subject, 'requestUrl');
        $method->setAccessible(true);
        $result = $method->invokeArgs($subject, [$url, $httpMethod, $options]);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function checkLinkWithExternalUrlNotFoundResultsNotFoundErrorType(): void
    {
        $httpMethod = 'GET';
        $options = $this->getRequestHeaderOptions($httpMethod);
        $url = 'https://example.org/~not-existing-url';

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(404);

        $exceptionProphecy = $this->prophesize(ClientException::class);
        $exceptionProphecy->hasResponse()
            ->willReturn(true);
        $exceptionProphecy->getResponse()
            ->willReturn($responseProphecy->reveal());

        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $requestFactoryProphecy->request($url, $httpMethod, $options)
            ->willThrow($exceptionProphecy->reveal());
        $subject = $this->instantiateExternalLinktype($requestFactoryProphecy);

        $method = new \ReflectionMethod($subject, 'requestUrl');
        $method->setAccessible(true);
        $method->invokeArgs($subject, [$url, $httpMethod, $options]);
        $errorParams = $subject->getErrorParams()->toArray();

        self::assertSame(ExternalLinktype::ERROR_TYPE_HTTP_STATUS_CODE, $errorParams['errorType']);
        self::assertSame(404, $errorParams['errno']);
    }

    /**
     * @param ObjectProphecy<RequestFactory>|null $requestFactoryProphecy
     * @return ExternalLinktype
     */
    private function instantiateExternalLinktype(ObjectProphecy $requestFactoryProphecy = null): ExternalLinktype
    {
        $requestFactoryProphecy = $requestFactoryProphecy ?: $this->prophesize(RequestFactory::class);

        $excludeLinkTargetProphecy = $this->prophesize(ExcludeLinkTarget::class);

        $linkTargetCacheProphycy = $this->prophesize(LinkTargetPersistentCache::class);

        return new ExternalLinktype(
            $requestFactoryProphecy->reveal(),
            $excludeLinkTargetProphecy->reveal(),
            $linkTargetCacheProphycy->reveal()
        );
    }

    /**
     * @param string $method
     * @return mixed[]
     */
    private function getRequestHeaderOptions(string $method): array
    {
        $options = [
            'cookies' => GeneralUtility::makeInstance(CookieJar::class),
            'allow_redirects' => ['strict' => true],
            'headers' => [
                'User-Agent' => 'Broken Link Fixer (https://example.org)',
                'Accept' => '*/*',
                'Accept-Language' => '*',
                'Accept-Encoding' => '*'
            ]
        ];

        if ($method === 'HEAD') {
            return $options;
        }
        return array_merge_recursive($options, ['headers' => ['Range' => 'bytes=0-4048']]);
    }
}
