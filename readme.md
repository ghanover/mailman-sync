# Sync emails with mailman

## Introduction

Basic subscribe/unsubscribe functionality to allow for keeping user lists in sync with a mailman list

## Installation

Require through composer

	composer require ghanover/mailman-sync

Or manually edit your composer.json file:

	"require": {
		"ghanover/mailman-sync": "~1.0"
	}

Publish the configuration file:

	php artisan vendor:publish

And edit the aliases array to include:

	'MailmanGateway' => \MailmanSync\Facades\MailmanGateway::class,

Register Service Provider in config/app.php providers array:

    \MailmanSync\SyncServiceProvider::class,

Config

    MAILMAN_ADMIN_URL=http://your.host/mailman
    MAILMAN_LISTS="{\"examplelist\":{\"password\":\"securepassword\"}}"

## Usage

### Basic example

	MailmanGateway::subscribe('mylist', 'user@example.com');
