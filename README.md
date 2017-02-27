# distribution-upgrades

Contains script resources for upgrading Flow and Neos distributions.
Currently this is meant to upgrade Flow 3.x installations to Flow 4.0 and
Neos 2.x installations to 3.0. Maybe this script will become a generalized
distribution upgrade helper in the future.


## Build
 The `build` folder contains a script to create a phar file containing the updater.
 If you change the content of `src/UpgradePackages.json`, you will need to rebuild the phar.
 
## Usage
Execute the `DistributionUpgrader.phar` and point it towards the Neos distribution you
want to upgrade. Example:
```bash
build/DistributionUpgrader.phar /path/to/your/Neos/distribution
```
After running this, you'll need to run `composer update` to install the new Neos 3.0 packages.
Hint: Only packages in the Neos or Flowpack namespace are modified. If you use any other
Neos packages, you will need to replace them manually with versions compatible to 3.0.