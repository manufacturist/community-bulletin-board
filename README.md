[![License: CC BY-NC 4.0](https://licensebuttons.net/l/by-nc/4.0/80x15.png)](https://creativecommons.org/licenses/by-nc/4.0/)

[English](./README.md) | [Română](./README.ro.md)

# Community Bulletin Board (CBB)

A low-cost, secure, invite-only bulletin board web app with encrypted storage.

CBB allows your community to post on a digital bulletin board. The Admin invites members via email, and each member
can have up to 2 posts (by default) at a time to keep things organized.

The Admin can:

* Promote users to Admin
* Adjust the maximum number of posts per user (0-5)
* Remove users or their posts

## Table of Contents

- [Cloud Hosting (recommended)](#cloud-hosting-recommended)
- [Hetzner Configuration](#hetzner-configuration)
- [Alternative Hosting](#alternative-hosting)
- [Development and Testing](#development-and-testing)
    - [Local Development](#local-development)
    - [Running Tests](#running-tests)
    - [Static Analysis](#static-analysis)
    - [Translations](#translations)
- [Technical Notes](#technical-notes)
- [License](#license)

## Cloud Hosting (recommended)

1. Fork the project
2. The project uses GitHub Actions which are already set up to work with FTP deployment
    - The pipeline will automatically validate, test and deploy the code
    - For it to work, you'll need to add the following secrets / variables to your repository:
        - Deployment:
            - `FTP_SERVER`: Your hosting server address
            - `FTP_USERNAME`: Your FTP username
            - `FTP_PASSWORD`: Your FTP password
            - `FTP_PORT`: Your FTP port (usually 21)
        - Database:
            - `DB_HOST`: Your database host
            - `DB_PORT`: Your database port
            - `DB_NAME`: Your database name
            - `DB_USERNAME`: Your database username
            - `DB_PASSWORD`: Your database password
        - Encryption (32 character hex strings expected):
            - `CRYPTO_ENCRYPTION_KEY`: Your encryption key
            - `CRYPTO_HMAC_KEY`: Your HMAC key
            - `CRYPTO_PEPPER`: Your pepper value
        - Email:
            - `EMAIL_SMTP_HOST`: SMTP server hostname
            - `EMAIL_SMTP_PORT`: SMTP port (usually 587)
            - `EMAIL_SMTP_USERNAME`: SMTP username
            - `EMAIL_SMTP_PASSWORD`: SMTP password
        - Application settings:
            - `APP_URL`: Base URL of your application (used for invitation links)
            - `APP_OWNER_EMAIL`: Email address of the site owner. Required for the first user invitation
            - `APP_LOCALE` (variable): Default locale for the application (one of `en_US`, `en_UK`, `ro_RO`)
            - `APP_MAX_ACTIVE_POSTS_DEFAULT` (variable): Default maximum number of active posts per user
        - GitHub Actions:
            - `PIPELINE_ENFORCE_C_LOCALE` (variable): Set to `true` to enforce your desired locale via the C
              locale, if the server lacks support for it

Easily deployable on [Hetzner Webhosting](https://www.hetzner.com/webhosting/). The advantages are:

1. Includes one domain registration with **no** annual renewal fee
2. Allows serving PHP
3. Provides a MariaDB instance
4. Provides email service for the domain
5. Unlimited traffic <sup>A</sup>

The costs include a one-time setup fee of ~10 EUR and a recurring monthly fee of ~2 EUR for hosting.

<sup>A</sup> The solution will run on a shared environment, meaning it will run alongside other websites on
the same machine. If loading speed becomes an issue, you could upgrade to a better hosting plan.

## Hetzner Configuration

TODO: Hetzner Webhosting

## Alternative Hosting

The [docker-compose.yaml](./docker-compose-all.yaml) file has all that you require.

If you prefer a different hosting process, please share it with us. I will add a reference to your repository here.

## Development and Testing

### Local Development

To run the application locally:

```bash
docker compose up
```

```bash
php -S 0.0.0.0:8000 -t ./public
```

### Running Tests

Tests are run with PHPUnit:

```bash
vendor/bin/phpunit ./tests
```

Most tests are integration tests. The API tests run against a dockerized version of the web app.

### Static Analysis

Static analysis is performed with PHPStan and Psalm:

```bash
vendor/bin/phpstan analyse ./src --level 10
```

```bash
vendor/bin/psalm --no-cache
``` 

### Translations

The application supports multiple languages through i18n. After modifying translation files, you must
recompile them by running:

```bash
./i18n.sh
```

## Technical Notes

* Clean-up of stale data is done with a 2% trigger chance per request
* Using cron jobs implies an extra cost

## License

<p>
<a property="dct:title" rel="cc:attributionURL" href="https://github.com/manufacturist/community-bulletin-board">community-bulletin-board</a> by 
<a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://github.com/manufacturist/"> Ioan-Gabriel Lazarovici-Georgiu</a> is licensed under 
<a href="https://creativecommons.org/licenses/by-nc/4.0" target="_blank" rel="license noopener noreferrer" style="display:inline-block;"> CC BY-NC 4.0</a>
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/nc.svg" alt="">
</p>
