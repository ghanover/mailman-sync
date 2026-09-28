# Sync emails with mailman3

## Introduction

Basic subscribe/unsubscribe functionality to allow for keeping user lists in sync with a mailman list.

## Requirements

- PHP 8.2 or newer (including PHP 8.4)
- Laravel 11

## Installation

```bash
composer require ghanover/mailman-sync
php artisan vendor:publish --tag=mailmansync-config
```

Laravel discovers the service provider and the `MailmanGateway` facade automatically. If discovery is disabled, register `MailmanSync\SyncServiceProvider` in `bootstrap/providers.php` and import `MailmanSync\Facades\MailmanGateway` where you call it.

### Configuration

```
MAILMAN_ADMIN_URL=http://your.host:8001/3.1/
MAILMAN_LISTS="{\"examplelist.domain\":{\"user\":\"restadmin\",\"password\":\"securepassword\"}}"
```

Set `MAILMAN_MOCK=true` to store list membership in `storage/app` instead of calling Mailman. The mock does not simulate API failures.

## Usage

```php
MailmanGateway::subscribe('mylist', 'user@example.com');
```

The same gateway is available from the container as `MailmanSync\MailmanGatewayInterface`. When `MAILMAN_MOCK` is enabled, that binding is the file-backed mock.

A custom implementation of that interface must use the same typed signatures: `string` arguments, `bool` returns from `subscribe`, `unsubscribe`, and `change`, and an `array` return from `roster`.

## Testing

```bash
composer test
```
