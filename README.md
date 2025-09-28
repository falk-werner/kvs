# Key Value Store

Simple key value store implemented in PHP.

## Features

- simple API
- cross origin
- [JSON-Patch](https://datatracker.ietf.org/doc/html/rfc6902)
- multi tenant _(by different buckets)_


## API

- [Store API (v1)](kvs-store-v1.openapi.yaml)
- [Admin API (v1)](kvs-admin-v1.openapi.yaml)


## Run locally

Copy [src/kvs-config-sample.php](src/ksv-config-sample.php) to `src/kvs-config.php` and edit the configurations.
You also need a database available.

   cd src && php -S localhost:8000


## Run Tests

   phpunit --testdox tests


## Reference

- [Base64Url](https://datatracker.ietf.org/doc/html/rfc4648#section-5)
- [JSON Patch](https://datatracker.ietf.org/doc/html/rfc6902)
