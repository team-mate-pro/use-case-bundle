<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use TeamMatePro\TestsBundle\TeamMateProTestsBundle;
use TeamMatePro\UseCaseBundle\TeamMateProUseCaseBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TeamMateProTestsBundle(),
            new TeamMateProUseCaseBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
        ]);

        $container->extension('security', [
            'providers' => [
                'in_memory' => ['memory' => null],
            ],
            'firewalls' => [
                'main' => ['security' => false],
            ],
        ]);
    }
}
