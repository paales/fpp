<?php

/**
 * This file is part of prolic/fpp.
 * (c) 2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fpp;

const dump = '\Fpp\dump';

function dump(DefinitionCollection $collection, callable $locatePsrPath, callable $loadTemplate, callable $replace): void
{
    $codePrefix = <<<CODE
<?php

// this file is auto-generated by prolic/fpp
// don't edit this file manually

declare(strict_types=1);


CODE;

    $data = [];

    foreach ($collection->definitions() as $definition) {
        $constructors = $definition->constructors();

        $isEnum = false;
        $enum = new Deriving\Enum();

        foreach ($definition->derivings() as $deriving) {
            if ($deriving->equals($enum)) {
                $isEnum = true;
                break;
            }
        }

        if (1 === \count($constructors)) {
            $constructor = $constructors[0];
            $file = $locatePsrPath($definition, $constructor);
            $code = $codePrefix . $replace($loadTemplate($definition, $constructor), $definition, $constructor, $collection);
            $data[$file] = \substr($code, 0, -1);
        } elseif ($isEnum) {
            $file = $locatePsrPath($definition, null);
            $code = $codePrefix . $replace($loadTemplate($definition, null), $definition, null, $collection);
            $data[$file] = \substr($code, 0, -1);
        } else {
            $createBaseClass = true;

            foreach ($constructors as $constructor) {
                $name = \str_replace($definition->namespace() . '\\', '', $constructor->name());

                if ($definition->name() === $name) {
                    $createBaseClass = false;
                }

                $file = $locatePsrPath($definition, $constructor);
                $code = $codePrefix . $replace($loadTemplate($definition, $constructor), $definition, $constructor, $collection);
                $data[$file] = \substr($code, 0, -1);
            }

            if ($createBaseClass) {
                $file = $locatePsrPath($definition, null);
                $code = $codePrefix . $replace($loadTemplate($definition, null), $definition, null, $collection);
                $data[$file] = \substr($code, 0, -1);
            }
        }
    }

    foreach ($data as $file => $code) {
        $dir = \dirname($file);

        if (! \is_dir($dir)) {
            \mkdir($dir, 0777, true);
        }

        \file_put_contents($file, $code);
    }
}
