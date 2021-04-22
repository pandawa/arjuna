<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Provider;

use Pandawa\Arjuna\Mapper\RegistryMapper;
use Pandawa\Component\Loader\ChainLoader;
use Pandawa\Component\Module\AbstractModule;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * @mixin AbstractModule
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait ArjunaProviderTrait
{
    protected $arjunaPath = 'Resources/arjuna';

    protected function registerArjunaProvider(): void
    {
        if (file_exists($this->app->getCachedConfigPath())) {
            return;
        }

        if (null === $this->registry()) {
            return;
        }

        $basePath = $this->getCurrentPath() . '/' . trim($this->arjunaPath, '/');
        $loader = ChainLoader::create();

        if (is_dir($basePath)) {
            /** @var SplFileInfo $file */
            foreach (Finder::create()->in($basePath) as $file) {
                if (!empty($mappers = $loader->load((string) $file))) {
                    $this->mergeConfig('arjuna.event_mappers', $mappers);
                }
            }
        }
    }

    private function registry(): ?RegistryMapper
    {
        if (app()->has(RegistryMapper::class)) {
            return app()->get(RegistryMapper::class);
        }

        return null;
    }
}
