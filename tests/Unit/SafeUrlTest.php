<?php

namespace hexa_package_publication_onboarding\Tests\Unit;

use hexa_package_publication_onboarding\Support\SafeUrl;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SafeUrlTest extends TestCase
{
    public function test_same_origin_accepts_query_and_fragment_components(): void
    {
        self::assertTrue(SafeUrl::sameOrigin(
            'https://hexaprwire.com/?feed=rss_publication&publication=rich-reporter#releases',
            'https://hexaprwire.com',
        ));
        self::assertFalse(SafeUrl::sameOrigin(
            'https://example.com/?feed=rss_publication&publication=rich-reporter',
            'https://hexaprwire.com',
        ));
    }

    public function test_same_origin_still_rejects_credentials(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SafeUrl::sameOrigin('https://user:password@hexaprwire.com/feed', 'https://hexaprwire.com');
    }

    public function test_url_builders_remain_strict_about_query_and_fragment_components(): void
    {
        foreach ([
            fn (): string => SafeUrl::origin('https://hexaprwire.com/?feed=rss_publication'),
            fn (): string => SafeUrl::wpAdmin('https://example.com/wp-admin/#dashboard', 'https://example.com'),
            fn (): string => SafeUrl::https('https://hexaprwire.com/release?preview=true'),
        ] as $callback) {
            try {
                $callback();
                self::fail('Expected a strict URL builder to reject query or fragment components.');
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
