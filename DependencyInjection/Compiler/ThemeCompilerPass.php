<?php

/*
 * This file is part of the Liip/ThemeBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\ThemeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ThemeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->setAlias('templating.locator', 'liip_theme.templating_locator');

        $container->setAlias('templating.cache_warmer.template_paths', 'liip_theme.templating.cache_warmer.template_paths');

        if (!$container->getParameter('liip_theme.cache_warming')) {
            $container->getDefinition('liip_theme.templating.cache_warmer.template_paths')
                ->replaceArgument(2, null);
        }

        $twigFilesystemLoaderDefinition = $container->getDefinition('twig.loader.filesystem');
        $reflectionKlass = new \ReflectionClass($twigFilesystemLoaderDefinition->getClass());
        $twigFilesystemLoaderDefinition->setClass($container->getParameter('liip_theme.filesystem_loader.class'));

        /*
         * symfony/symfony commit #2d42689 introduced an optional 3rd argument to the constructor of
         * Symfony\Bundle\TwigBundle\Loader\FilesystemLoader. It is injected during the compiler pass of the Symfony
         * Twig bundle.
         *
         * As such the code below determines the number of arguments found in the twig FilesystemLoader constructor
         * using reflection and either replaces the 3rd argument with an instance of ActiveTheme or adds the ActiveTheme
         * as a new argument
         */
        if ($reflectionKlass->getConstructor()->getNumberOfParameters() == 3) {
            $twigFilesystemLoaderDefinition->replaceArgument(2, new Reference('liip_theme.active_theme'));
        } else {
            $twigFilesystemLoaderDefinition->addArgument(new Reference('liip_theme.active_theme'));
        }
    }
}
