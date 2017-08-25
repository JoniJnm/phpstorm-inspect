<?php

namespace ShopSys\PhpStormInspect;

use Symfony\Component\Filesystem\Filesystem;

class InspectionRunner
{
    const CACHE_DIR = 'caches';
    const INDEX_DIR = 'index';

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function clearCache($phpstormSystemPath)
    {
        if (is_dir($phpstormSystemPath . '/' . self::CACHE_DIR)) {
            $this->clearDirectory($phpstormSystemPath . '/' . self::CACHE_DIR);
        }

        if (is_dir($phpstormSystemPath . '/' . self::INDEX_DIR)) {
            $this->clearDirectory($phpstormSystemPath . '/' . self::INDEX_DIR);
        }
    }

    public function clearOutputDirectory($outputPath)
    {
        $files = glob($outputPath . '/*.xml');
        $this->filesystem->remove($files);
    }

    /**
     * @param string $inspectShExecutableFilepath
     * @param string $projectPath
     * @param string $inspectionProfileFilepath
     * @param string $outputPath
     * @param string[] $dirs
     * @param string $ideaPropertiesFilepath
     *
     * @throws \Exception
     */
    public function runInspection(
        $inspectShExecutableFilepath,
        $projectPath,
        $inspectionProfileFilepath,
        $outputPath,
        $dirs,
        $ideaPropertiesFilepath
    ) {
        $command = sprintf(
            'PHPSTORM_PROPERTIES=%s %s %s %s %s',
            escapeshellarg($ideaPropertiesFilepath),
            escapeshellarg($inspectShExecutableFilepath),
            escapeshellarg($projectPath),
            escapeshellarg($inspectionProfileFilepath),
            escapeshellarg($outputPath)
        );

        foreach ($dirs as $dir) {
            $command .= ' -d '.escapeshellarg($dir);
        }

        $command .= ' 2>&1';

        $returnCode = null;
        ob_start();
        passthru($command, $returnCode);
        $output = ob_get_clean();

        // PhpStorm exits without error when another instance is already running
        if ($returnCode === 0 && mb_strpos($output, 'Too Many Instances') !== false) {
            $returnCode = 1;
        }

        if ($returnCode !== 0) {
            throw new \Exception($output);
        }
    }

    private function clearDirectory($directory)
    {
        $files = glob($directory . '/*');
        $this->filesystem->remove($files);
    }
}
