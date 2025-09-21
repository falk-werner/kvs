# Kegity Value Store

## Store API

### Create or Write an entry

    POST /store/v1/<bucket>/entry/<key>

    <value>

### Read a value

    GET /store/v1/<bucket>/entry/<key>

### Read all values

    GET /store/v1/<bucket>

### Read all keys

    GET /store/v1/<bucket>/keys

### Delete an entry

    DELETE /store/v1/<bucket>/entry/<key>

### Create Access Token

    POST /store/v1/<bucket>/token?access=read,write,all&not_valid_before=date&not_valid_after=data

The token will be send to the email address of the bucket own.

### Revoke all previously created Tokens

    DELETE /store/v1/<bucket>/token

Since we are unsing JWT, we cannot revoke a single token.
Instead of this, we revoke all tokens which are created before
a given point in time.

## Admin API

### Create a bucket

    POST /admin/v1/bucket?allow_anonymous_read=true&allow_anonymous_write=true&max_entries=100&max_entry_size

### Remove or Reset a bucket

    DELETE /admin/v1/bucket/<bucket>

## Run locally

Copy [src/kvs-config-sample.php](src/ksv-config-sample.php) to `src/kvs-config.php` and edit the configurations.
You also need a database available.

   cd src && php -S localhost:8000


## Run Tests

   phpunit --testdox tests


## Reference

- [Base64Url](https://datatracker.ietf.org/doc/html/rfc4648#section-5)
- [JWT](https://jwt.io/)