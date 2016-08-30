### Naming Strategies
You can choose from 2 naming Strategies by default.
Replaces all non word chars with '-' and removes following '-'
Zicht\Bundle\FileManagerBundle\Mapping\DefaultNamingStrategy
Keeps the original file name
Zicht\Bundle\FileManagerBundle\Mapping\OriginalNamingStrategy

Or you can make your own naming strategy which has to implement
Zicht\Bundle\FileManagerBundle\Mapping\NamingStrategyInterface