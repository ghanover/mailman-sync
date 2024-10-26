# Sync emails with mailman3

## Introduction

Basic subscribe/unsubscribe functionality to allow for keeping user lists in sync with a mailman list

## Installation

Require through composer (until I get off my butt and get it in packagist, you'll have to add the VCS manually)

	composer require ghanover/mailman-sync

Or manually edit your composer.json file:

	"require": {
		"ghanover/mailman-sync": "dev-master"
	}

Publish the configuration file:

	php artisan vendor:publish

##### app/config.php
Edit the aliases array to include:

	'MailmanGateway' => \MailmanSync\Facades\MailmanGateway::class,

Register Service Provider in providers array:

    \MailmanSync\SyncServiceProvider::class,

Config

    MAILMAN_ADMIN_URL=http://your.host:8001/3.1/
    MAILMAN_LISTS="{\"examplelist.domain\":{\"user\":\"restadmin\",\"password\":\"securepassword\"}}"

## Usage

### Basic example

	MailmanGateway::subscribe('mylist', 'user@example.com');

## Testing

You can test locally without having to set up mailman by adding MAILMAN_MOCK=true to your .env. This will use local files in storage/app/ to mimic the members list. 

At this point in time, the mock feature has no way to test failures.
