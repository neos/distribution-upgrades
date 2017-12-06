<?php

if (PHP_SAPI !== 'cli') {
    echo(sprintf("This script was executed with a '%s' PHP binary. Please use a command line (CLI) PHP binary.", PHP_SAPI) . PHP_EOL);
    exit(1);
}

if (!isset($argv[1])) {
    echo "No installation path given! Please give the absolute path of the installation you want to upgrade." . PHP_EOL;
    echo 'Usage: ' . $argv[0] . ' <INSTALLATION_PATH>' . PHP_EOL;
    exit(1);
}

$modifiedFiles = [];
$baseDirectory = realpath($argv[1]);

/**
 * Replaces an array of requires (packageName => version) by an array with new package names and versions.
 *
 * @param array $currentRequires
 * @return array
 */
function replacePackageNamesAndVersions($currentRequires)
{
    $replacementPackages = [];
    if (file_exists('UpgradePackages.json')) {
        $replacementPackages = json_decode(file_get_contents('UpgradePackages.json'), true);
    } else {
        echo 'No list of upgraded packages found!' . PHP_EOL;
    }
    $newRequires = [];
    foreach ($currentRequires as $packageName => $version) {
        if (!array_key_exists($packageName, $replacementPackages)) {
            $newRequires[$packageName] = $version;
            continue;
        }
        $newRequires[$replacementPackages[$packageName]['replacement']] = $replacementPackages[$packageName]['version'];
    }

    return $newRequires;
}

#
# https://github.com/neos/flow-development-collection/issues/801
# Additionally updates package names and versions required in global composer.json
#
$manifestPath = $baseDirectory . DIRECTORY_SEPARATOR . 'composer.json';
if (file_exists($manifestPath)) {
    echo 'Adapting composer.json...' . PHP_EOL;

    $composerManifestContent = file_get_contents($manifestPath);
    $composerManifestContent = str_replace('TYPO3\\\\Flow', 'Neos\\\\Flow', $composerManifestContent);
    echo 'Replaced installer scripts namespace.' . PHP_EOL;

    $replacementPackages = [];
    if (file_exists('UpgradePackages.json')) {
        $replacementPackages = json_decode(file_get_contents('UpgradePackages.json'), true);
    }
    $composerManifest = json_decode($composerManifestContent, true);
    if (isset($composerManifest['require'])) {
        $composerManifest['require'] = replacePackageNamesAndVersions($composerManifest['require']);
    }
    if (isset($composerManifest['require-dev'])) {
        $composerManifest['require-dev'] = replacePackageNamesAndVersions($composerManifest['require-dev']);
    }

    $composerManifestContent = json_encode($composerManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $modifiedFiles[] = DIRECTORY_SEPARATOR . 'composer.json';
    file_put_contents($manifestPath, $composerManifestContent);
    echo 'Finished composer.json' . PHP_EOL;
}
echo PHP_EOL;

#
# https://github.com/neos/flow-development-collection/issues/802
#
echo 'Adapting Configuration...' . PHP_EOL;
$yamlFiles = new RegexIterator(
    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDirectory . DIRECTORY_SEPARATOR . 'Configuration')),
    '/^.+\.yaml$/i',
    RecursiveRegexIterator::GET_MATCH
    );

$yamlFiles = array_map(function ($element) {
    return $element[0];
}, iterator_to_array($yamlFiles));

foreach ($yamlFiles as $filePath) {
    $replacementCount = 0;
    $yamlContent = file_get_contents($filePath);
    $yamlContent = str_replace('TYPO3CR', 'ContentRepository', $yamlContent, $count);
    $replacementCount += $count;
    $yamlContent = str_replace('TYPO3', 'Neos', $yamlContent, $count);
    $replacementCount += $count;
    $yamlContent = str_replace('TypoScript', 'Fusion', $yamlContent, $count);
    $replacementCount += $count;

    if ($replacementCount > 0) {
        $modifiedFiles[] = str_replace($baseDirectory, '', $filePath);
        file_put_contents($filePath, $yamlContent);
    }
}
echo 'Done adapting configuration.' . PHP_EOL;

#
# https://github.com/neos/flow-development-collection/issues/800
#
echo 'Adapting front controllers...' . PHP_EOL;
foreach (['flow', 'flow.bat', 'Web/index.php'] as $frontControllerFile) {
    $absoluteFrontControllerPath = $baseDirectory . DIRECTORY_SEPARATOR . $frontControllerFile;
    if (!file_exists($absoluteFrontControllerPath)) {
        continue;
    }
    $replacementCount = 0;
    $frontControllerContent = file_get_contents($absoluteFrontControllerPath);
    $frontControllerContent = str_replace('Framework/TYPO3.Flow/Classes/TYPO3/Flow/Core', 'Framework/Neos.Flow/Classes/Core', $frontControllerContent, $count);
    $replacementCount += $count;
    $frontControllerContent = str_replace('TYPO3', 'Neos', $frontControllerContent, $count);
    $replacementCount += $count;

    if ($replacementCount > 0) {
        $modifiedFiles[] = str_replace($baseDirectory, '', $absoluteFrontControllerPath);
        file_put_contents($absoluteFrontControllerPath, $frontControllerContent);
    }
}
echo 'Done adapting front controllers.' . PHP_EOL;

#
# No ticket, just cleanup as these files are no longer needed since Flow 4.0
#
echo 'Removing leftover "IncludeCachedConfiguration" files...' . PHP_EOL;
$includeConfigurationFiles = new RegexIterator(
    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDirectory . DIRECTORY_SEPARATOR . 'Configuration')),
    '/^.+IncludeCachedConfigurations\.php$/i',
    RecursiveRegexIterator::GET_MATCH
);
foreach ($includeConfigurationFiles as $filePath) {
    unlink($filePath[0]);
}
echo 'Done removing leftover "IncludeCachedConfiguration" files' . PHP_EOL;


echo PHP_EOL . PHP_EOL . 'The upgrading script finished.' . PHP_EOL;

if (count($modifiedFiles)) {
    echo 'The following files have been changed by it and depending on' . PHP_EOL;
    echo 'your version control and deployment strategy you have to make sure' . PHP_EOL;
    echo 'that the files are also changed on any other systems you have:' . PHP_EOL;
    echo '------------------------------------------------------------------' . PHP_EOL;
    foreach ($modifiedFiles as $modifiedFile) {
        echo $modifiedFile . PHP_EOL;
    }
} else {
    echo 'Nothing was modified.' . PHP_EOL;
}
