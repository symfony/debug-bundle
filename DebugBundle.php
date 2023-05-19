<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DebugBundle;

use Symfony\Bundle\DebugBundle\DependencyInjection\Compiler\DumpDataCollectorPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DebugBundle extends Bundle
{
    /**
     * @return void
     */
    public function boot()
    {
        if ($this->container->getParameter('kernel.debug')) {
            $container = $this->container;

            // This code is here to lazy load the dump stack. This default
            // configuration is overridden in CLI mode on 'console.command' event.
            // The dump data collector is used by default, so dump output is sent to
            // the WDT. In a CLI context, if dump is used too soon, the data collector
            // will buffer it, and release it at the end of the script.
            VarDumper::setHandler(function ($var, string $label = null) use ($container) {
                $dumper = $container->get('data_collector.dump');
                $cloner = $container->get('var_dumper.cloner');
                $handler = function ($var, string $label = null) use ($dumper, $cloner) {
                    $var = $cloner->cloneVar($var);
                    if (null !== $label) {
                        $var = $var->withContext(['label' => $label]);
                    }

                    $dumper->dump($var);
                };
                VarDumper::setHandler($handler);
                $handler($var, $label);
            });
        }
    }

    /**
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DumpDataCollectorPass());
    }

    /**
     * @return void
     */
    public function registerCommands(Application $application)
    {
        // noop
    }
}
