<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TeamMatePro\UseCaseBundle\TeamMateProUseCaseBundle;

final class TeamMateProUseCaseBundleTest extends TestCase
{
    #[Test]
    public function buildLoadsServicesYaml(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 2));

        $vendorPath = dirname(__DIR__, 2) . '/vendor/team-mate-pro/use-case-bundle';
        if (!is_dir($vendorPath . '/src')) {
            @mkdir($vendorPath, 0777, true);
            @symlink(dirname(__DIR__, 2) . '/src', $vendorPath . '/src');
        }

        $bundle = new TeamMateProUseCaseBundle();
        $bundle->build($container);

        self::assertTrue(
            $container->hasDefinition('TeamMatePro\UseCaseBundle\Utils\PartialUpdateService')
            || $container->has('TeamMatePro\UseCaseBundle\Utils\PartialUpdateService')
        );

        if (is_link($vendorPath . '/src')) {
            @unlink($vendorPath . '/src');
            @rmdir($vendorPath);
        }
    }

    #[Test]
    public function configureDefinesAllowOriginWithDefaultValue(): void
    {
        $treeBuilder = new TreeBuilder('team_mate_pro_use_case');
        $loader = new DefinitionFileLoader($treeBuilder, new FileLocator());
        $configurator = new DefinitionConfigurator($treeBuilder, $loader, '', '');

        $bundle = new TeamMateProUseCaseBundle();
        $bundle->configure($configurator);

        $config = (new \Symfony\Component\Config\Definition\Processor())->processConfiguration(
            new class ($treeBuilder) implements \Symfony\Component\Config\Definition\ConfigurationInterface {
                public function __construct(private TreeBuilder $treeBuilder)
                {
                }
                public function getConfigTreeBuilder(): TreeBuilder
                {
                    return $this->treeBuilder;
                }
            },
            []
        );

        self::assertSame('*', $config['allow_origin']);
    }
}
