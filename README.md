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
//...
PixelFederation\CommandProcessingBundle\PixelFederationDoctrineResettableEmBundle::class => ['all' => true]
//...
```

```yaml
pixel_federation_doctrine_resettable_em:
  # default 0 - if set, the connection ping operation will be executed each X seconds 
  # (instead of at the beginning of each request) 
  ping_interval: 10 
```
