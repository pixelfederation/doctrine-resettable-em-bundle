# 11.1.1

- `TransactionDiscardingDBALAliveKeeper` rolls back all transaction nesting levels instead of only the innermost one
- `TransactionDiscardingDBALAliveKeeper` closes the connection when it still believes it is in a transaction after
  discarding it (e.g. MySQL dropped all savepoints on a deadlock and DBAL leaked its nesting level), so the next request
  starts a real transaction instead of autocommitting under a phantom one; the rollback-only mark is cleared afterwards
- connections with auto-commit turned off are not closed after a clean discard

# 11.1.0

- add support for Symfony 8.1
- upgrade the test suite to PHPUnit 13 and make the functional test infrastructure compatible with Symfony 7.4 through 8.1
- upgrade and extend the CI and code-quality tooling

# 11.0.0

- remove support for PHP 8.3
- require PHP 8.4 or newer
- remove support for Symfony 7.3
- add support for Symfony 7.4 and 8.0
- require Doctrine Bundle 3.2 or newer and Doctrine ORM 3.6 or newer
- replace ProxyManager's `VirtualProxyInterface` with Symfony's `LazyObjectInterface` when detecting uninitialized lazy DBAL and Redis connections
- update `ResettableEntityManager` method signatures for Doctrine ORM 3.6
- remove the `symfony/proxy-manager-bridge` dependency

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
- add `disable_request_initializers` option to disable request initializers

# 6.0.0

- Change all usages of PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface to PixelFederation\DoctrineResettableEmBundle\RequestCycle\Initializer
