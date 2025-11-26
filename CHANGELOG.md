# 10.0.0

- rm support for PHP 8.2
- add support for PHP 8.4
- add support for PHP 8.5
- rm support for SF 6.4
- rm support for SF 7.0
- rm support for SF 7.1
- rm support for SF 7.2
- add support for sf 7.3
- rm `PixelFederation\DoctrineResettableEmBundle\DependencyInjection\Parameters` and move constants to `PixelFederation\DoctrineResettableEmBundle\DependencyInjection\PixelFederationDoctrineResettableEmExtension`
- move constants from `PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\AliveKeeperPass` to `PixelFederation\DoctrineResettableEmBundle\DependencyInjection\PixelFederationDoctrineResettableEmExtension`
- `PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType` was changed from value object to enum

# 6.0.0

- Change all usages of PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface to PixelFederation\DoctrineResettableEmBundle\RequestCycle\Initializer
