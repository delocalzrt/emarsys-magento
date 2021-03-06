# Emarsys Magento 1 Extension Developer Guide

## Prerequisites
To be able to pull image from Google Container Registry (GCR), you will have to authenticate to Google Cloud and
 configure docker to use those credentials.

First install Google Cloud SDK:
```
$ brew cask install google-cloud-sdk
```
Then login to your account:
```
$ gcloud auth login
```
This will bring up the browser: choose your account that is associated with the `ems-plugin` GCP project and grant the permissions.

After authentication set the default project:
```
$ gcloud config set project ems-plugins
```
The last step is to configure docker to use the credentials:
```
$ gcloud auth configure-docker
```

## Installation
To start development first copy `dev/.env.example` to `dev/.env` then use
```
$ make up
```
This will set up the containers, run Magento installation and installs the extension.

The web container will expose its port `80` to port `8886` on the host machine.

By default the Magento store will be available at http://magento1-dev.local:8886, but you have to add this to your `/etc/hosts` file first:
```
127.0.0.1 magento1-dev.local magento1-test.local
```

### Create test DB
Before you make any changes on the Magento instance, it's a good idea to create the test DB from a clean state. You can do this with the following command:
```
$ make create-test-db
```

---
## Usage
### Working with containers
Start all
```
$ make start
```
Stop all
```
$ make stop
```
Display status
```
$ make ps
```
Destroy all
```
$ make down
```
**Note:** MYSQL data is persisted in a Docker volume for faster rebuild. If you want to start from a clean state, you can delete `mage_magento-db` volume folder after `make down` by
```
$ docker volume rm mage_magento-db
```

Access the web container CLI as `www-data` user
```
$ make ssh
```
**Warning:** Do not run Magento CLI commands as root!

Execute single command in web container as `root`
```
$ make exec <command>
```

### Magento
If you want to run Magento CLI commands, you should use
```
$ make magento command=setup:upgrade
```
This will run the command as the `www-data` user in the container. If you run this without command parameter, you will get a list of available commands.

There are some frequently used Magento commands predefined:

Run `cache:flush` & `setup:upgrade`:
```
$ make upgrade
```

Clean the generated code folder and run `cache:flush`:
```
$ make flush
```

For debugging, use (This will `tail -f` Magento's `exception.log` file.):
```
$ make exception
```

**Uninstall** the extension in local instance (will remove connect token from `core_config_data`, drop extension migrations, delete module from `setup_module`, delete generated code and flush cache):
```
$ make uninstall
```
then you can **reinstall** it by calling `upgrade`:
```
$ make upgrade
```

### MYSQL
The MYSQL container exposes its connection port `3306` to port `13306` on the host machine. Credentials are defined in your `.env` file.

You can also use
```
$ make mysql
```
to enter the MYSQL CLI directly.

### Testing
Tests are run in NodeJS environment in a separate container. The node container does not run constantly, it boots up for one-off test runs.

Before the first run `npm` packages must be installed by
```
$ make npm-install
```
To run the tests use
```
$ make test
```

---
## Release

* Update the version in `app/code/community/Emartech/Emarsys/etc/config.xml`.
* Update the version in `magazine.json`.
* Create the extension package by `make package`.
* Commit, tag and push with tags.
* On **GitHub -> Releases** choose **Draft new release**
* Create the release and upload the package tar file by attaching it to the release.
* Change the version in the `LATEST_MAGENTO_MODULE_VERSIONS` environment variable on `ems-shopify-app-conn` and `ems-shopify-app-conn-staging`

## Codeship env
* [Install](https://documentation.codeship.com/pro/jet-cli/installation/) `jet`
* Download the `aes` key from [Codeship](https://app.codeship.com/projects/290273/configure) into the project directory.
* Run `$ jet encrypt codeship.env codeship.env.encrypted`
* Commit `codeship.env.encrypted` into the repo.
