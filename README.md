[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zicht/filemanager-bundle/badges/quality-score.png?b=release%2F4.6.x)](https://scrutinizer-ci.com/g/zicht/filemanager-bundle/?branch=release%2F4.6.x)
[![Code Coverage](https://scrutinizer-ci.com/g/zicht/filemanager-bundle/badges/coverage.png?b=release%2F4.6.x)](https://scrutinizer-ci.com/g/zicht/filemanager-bundle/?branch=release%2F4.6.x)
[![Build Status](https://scrutinizer-ci.com/g/zicht/filemanager-bundle/badges/build.png?b=release%2F4.6.x)](https://scrutinizer-ci.com/g/zicht/filemanager-bundle/build-status/release/4.6.x)

### Naming Strategies
You can choose from 2 naming Strategies by default.
Replaces all non word chars with '-' and removes following '-'
Zicht\Bundle\FileManagerBundle\Mapping\DefaultNamingStrategy
Keeps the original file name
Zicht\Bundle\FileManagerBundle\Mapping\OriginalNamingStrategy

Or you can make your own naming strategy which has to implement
Zicht\Bundle\FileManagerBundle\Mapping\NamingStrategyInterface

# Maintainer(s)
* Oskar van Velden <oskar@zicht.nl>
