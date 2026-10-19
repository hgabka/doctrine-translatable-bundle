<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\DependencyInjection;

use Hgabka\Doctrine\TranslatableBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testFallbackLocaleDefaultsToEnglish(): void
    {
        $this->assertSame(['fallback_locale' => 'en'], $this->process([]));
    }

    public function testFallbackLocaleIsConfigurable(): void
    {
        $this->assertSame('hu', $this->process([['fallback_locale' => 'hu']])['fallback_locale']);
    }

    public function testLastConfigWins(): void
    {
        $config = $this->process([['fallback_locale' => 'hu'], ['fallback_locale' => 'de']]);

        $this->assertSame('de', $config['fallback_locale']);
    }

    public function testFallbackLocaleMustBeScalar(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->process([['fallback_locale' => ['hu']]]);
    }
}
