# Doctrine resettable entity manager bundle

This bundle should be used with workloads where Symfony doesn't get initialized on each request, but stays in memory
and the same instance handles multiple requests, one after another (e.g. when using 
[Swoole Bundle](https://github.com/pixelfederation/swoole-bundle)).
Another use case would be message queue consuming (e.g. Symfony messenger), where it is needed
to clear (and possibly reset) the entity manager after processing a message. 

The best feature of this bundle is, that it wraps all configured entity managers 
into a `ResettableEntityManager` instance, which
is able to reset the entity manager when it gets stuck on an exception.
After each request the entity manager gets cleared or reset, if an exception occurred during request handling.

Also another feature is, that on each request start the entity manager connection gets pinged, so the connection
won't get closed after some period of time.

## Instalation

`composer require pixelfederation/doctrine-resettable-em-bundle`

## SETUP

```php
// config/bundles.php
return [
    //...
    \PixelFederation\DoctrineResettableEmBundle\PixelFederationDoctrineResettableEmBundle::class => ['all' => true]
    //...
];
```

```yaml
pixel_federation_doctrine_resettable_em:
  exclude_from_processing:
    # these entity managers won't be wrapped by the resettable entity manager:
    entity_managers:
        - readonly
    # these dbal connections won't be assigned to the keep alive handler
    dbal:
      - readonly
    # these redis cluster connections won't be assigned to the keep alive handler
    redis_cluster:
        - default
  # default 0 - if set, the connection ping operation will be executed each X seconds 
  # (instead of at the beginning of each request) 
  ping_interval: 10 
  # default false - if set to true, the app will checj if there is an active transaction
  # in the processed connection, and it will rollback the transaction
  check_active_transactions: true
  # for reader writer connections, each has to be defined as 'reader' or 'writer' to be able for the symfony
  # app to reconnect after db failover. currently only AWS Aurora is supported.
  failover_connections:  
    default: writer
  # redis clusters which need to be failed over should be registered here
  # it's really important to set default timeouts to a low value, e.g. 2 seconds, so the app won't block for too long
  redis_cluster_connections:
    default: 'RedisCluster' # connection name (can be literally anything) => redis cluster service id
```

## Migration from v5 to v6

Change all usages of `PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface` to `PixelFederation\DoctrineResettableEmBundle\RequestCycle\Initializer` 
