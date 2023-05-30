<?php declare(strict_types=1);

namespace App\Compiler;

use Composer\Pcre\Preg;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class Compiler
{
    private $version;

    private $branchAliasVersion = '';

    private $versionDate;

    private $privateKey;

    public function compile(string $version): void
    {
        $filesystem = new Filesystem();
        $compiledDirectory = realpath(__DIR__.'/../../bin/compiled');
        $filesystem->remove($compiledDirectory);
        $filesystem->mkdir($compiledDirectory);
        $pharFile = sprintf('%s/dploy.phar', $compiledDirectory);
        $finalFile = sprintf('%s/dploy', $compiledDirectory);
        $signatureFile = sprintf('%s/dploy.sig', $compiledDirectory);
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }
        $phar = new \Phar($pharFile, 0, 'dploy.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA512);
        $phar->startBuffering();
        $finderSort = static function ($a, $b): int {
            return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
        };
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->notName('InstalledVersions.php')
            ->in(__DIR__.'/..')
            ->sort($finderSort)
        ;
        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }
        $envFile = new \SplFileInfo(__DIR__.'/../../.env');
        $this->addFile($phar, $envFile);
        // Add vendor files
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->notPath('/\/(composer\.(json|lock)|[A-Z]+\.md(?:own)?|\.gitignore|appveyor.yml|phpunit\.xml\.dist|phpstan\.neon\.dist|phpstan-config\.neon|phpstan-baseline\.neon)$/')
            ->notPath('/bin\/(jsonlint|validate-json|simple-phpunit|phpstan|phpstan\.phar)(\.bat)?$/')
            ->notPath('justinrainbow/json-schema/demo/')
            ->notPath('justinrainbow/json-schema/dist/')
            ->notPath('composer/installed.json')
            ->notPath('composer/LICENSE')
            ->notPath('keys/private.key')
            ->notPath('bin/patch-type-declarations')
            ->notPath('bin/var-dump-server')
            ->notPath('bin/yaml-lint')
            ->notPath('psr/cache/LICENSE.txt')
            ->notPath('symfony/console/Resources/bin/hiddeninput.exe')
            ->notPath('symfony/console/Resources/completion.bash')
            ->notPath('symfony/console/Resources/completion.fish')
            ->notPath('symfony/console/Resources/completion.zsh')
            ->notPath('symfony/dependency-injection/Loader/schema/dic/services/services-1.0.xsd')
            ->notPath('symfony/error-handler/Resources/assets/')
            ->notPath('symfony/var-dumper/Resources/css/htmlDescriptor.css')
            ->notPath('symfony/var-dumper/Resources/js/htmlDescriptor.js')
            ->notPath('symfony/runtime/Internal/autoload_runtime.template')
            ->notPath('symfony/routing/Loader/schema/routing/routing-1.0.xsd')
            ->notPath('symfony/framework-bundle/Resources/config/schema/symfony-1.0.xsd')
            ->notPath('symfony/framework-bundle/Resources/config/routing/errors.xml')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->in(__DIR__.'/../../vendor/')
            ->in(__DIR__.'/../../config/')
            ->in(__DIR__.'/../../data/')
            ->in(__DIR__.'/../../var/')
            ->sort($finderSort)
        ;
        $extraFiles = [];
        $unexpectedFiles = [];
        foreach ($finder as $file) {
            if (false !== ($index = array_search($file->getRealPath(), $extraFiles, true))) {
                unset($extraFiles[$index]);
            } elseif (!Preg::isMatch('{(^LICENSE$|\.php$)}', $file->getFilename())) {
                //$unexpectedFiles[] = (string) $file;
            }
            if (Preg::isMatch('{\.php[\d.]*$}', $file->getFilename())) {
                $this->addFile($phar, $file);
            } else {
                $this->addFile($phar, $file, false);
            }
        }
        if (count($extraFiles) > 0) {
            throw new \RuntimeException('These files were expected but not added to the phar, they might be excluded or gone from the source package:'.PHP_EOL.var_export($extraFiles, true));
        }
        if (count($unexpectedFiles) > 0) {
            throw new \RuntimeException('These files were unexpectedly added to the phar, make sure they are excluded or listed in $extraFiles:'.PHP_EOL.var_export($unexpectedFiles, true));
        }
        $envFile = dirname(__FILE__).'/../../.env';
        $envFileContent = file_get_contents($envFile);
        $this->addDployBin($phar);
        $phar->addFromString('composer.json', '');
        $phar->addFromString('.env', $envFileContent);
        $phar['.env']->chmod(0777);
        $stub = $this->getStub();
        $phar->setStub($stub);
        //$phar->compressFiles(\Phar::GZ);
        $phar->stopBuffering();
        $privateKey = $this->getPrivateKey();
        $private = openssl_get_privatekey(file_get_contents($privateKey));
        openssl_sign((string)file_get_contents($pharFile), $signature, $private, OPENSSL_ALGO_SHA384);
        file_put_contents($signatureFile, base64_encode($signature));
        rename($pharFile, $finalFile);
        unset($phar);
    }

    public function setPrivateKey(string $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    private function getRelativeFilePath(\SplFileInfo $file): string
    {
        $realPath = (string)$file->getRealPath();
        $pathPrefix = dirname(__DIR__, 2).DIRECTORY_SEPARATOR;

        $pos = strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

        return strtr($relativePath, '\\', '/');
    }

    private function addFile(\Phar $phar, \SplFileInfo $file, bool $strip = true): void
    {
        $path = $this->getRelativeFilePath($file);
        $content = file_get_contents((string) $file);
        if ($strip) {
            //$content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === $file->getFilename()) {
            $content = "\n".$content."\n";
        }
        if ($path === 'src/Composer/Composer.php') {
            $content = strtr(
                $content,
                [
                    '@package_version@' => $this->version,
                    '@package_branch_alias_version@' => $this->branchAliasVersion,
                    '@release_date@' => $this->versionDate->format('Y-m-d H:i:s'),
                ]
            );
            $content = Preg::replace('{SOURCE_VERSION = \'[^\']+\';}', 'SOURCE_VERSION = \'\';', $content);
        }
        $phar->addFromString($path, $content);
        $phar[$path]->chmod(0777);
    }

    private function addDployBin(\Phar $phar): void
    {
        $content = file_get_contents(__DIR__.'/../../bin/dploy');
        //$content = Preg::replace('{^#!/usr/bin/env php8.2\s*}', '', $content);
        $phar->addFromString('bin/dploy', $content);
    }

    private function stripWhitespace(string $source): string
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = Preg::replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = Preg::replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = Preg::replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub(): string
    {
        $stub = <<<'EOF'
#!/usr/bin/env php8.2
<?php

if (!class_exists('Phar')) {
    echo 'PHP\'s phar extension is missing.' . PHP_EOL;
    exit(1);
}

Phar::mapPhar('dploy.phar');

EOF;

        return $stub . <<<'EOF'
require 'phar://dploy.phar/bin/dploy';

__HALT_COMPILER();
EOF;
    }
}
