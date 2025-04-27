# Schema

## Tables

meta
    version
    schema_version

account
    id

email
    id
    account_id
    email

bucket
    id
    account_id
    name
    allow_anonymous_read
    allow_anonymous_write
    token_valid_after
    max_entries
    max_entry_size

key_value_store
    id
    bucket_id
    key
    value
