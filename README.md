[![License: CC BY-NC 4.0](https://licensebuttons.net/l/by-nc/4.0/80x15.png)](https://creativecommons.org/licenses/by-nc/4.0/)

[English](./README.md) | [Română](./README.ro.md)

# Community Bulletin Board (CBB)

A low-cost, secure, invite-only bulletin board web app with encrypted storage.

CBB allows your community to post on an online bulletin board. The Admin invites members via email, and
each member can have up to 2 active posts (by default) at a time to keep things organized.

The Admin can:

* Remove users or their posts
* Adjust the maximum number of posts per user (0-5)
* Promote other users to Admin (or demote them to member only)

Supports three interface themes: the classic one which is `cork`, and the `light` & `dark` ones.

All the data stored in the database is encrypted. It is decrypted when it is retrieved and served
to the users. Only the administrators are able to read personal data.

## Table of Contents

- [Cloud Hosting (recommended)](#cloud-hosting-recommended)
    - [With Hetzner](#with-hetzner)
- [Alternative Hosting](#alternative-hosting)
- [Development and Testing](#development-and-testing)
    - [Local Development](#local-development)
    - [Running Tests](#running-tests)
    - [Static Analysis](#static-analysis)
    - [Translations](#translations)
- [Technical Details](#technical-details)
    - [Dependencies](#dependencies)
    - [Notes](#notes)
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
            - `APP_PUBLIC_ENDPOINT`: One of `none`, `public` or any custom string. If `public`, the community posts
              will be exposed without any personal details (phone number, name and link) on a link that can be accessed
              by anyone, e.g. `your-community.com/posts`. If you use a custom string, then that string will be used as
              the slug of the endpoint, e.g. `my-secret-url` => `your-community.com/my-secret-url`
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

### With Hetzner

TODO: Hetzner Webhosting

## Alternative Hosting

The [docker-compose.yaml](./docker-compose-all.yaml) file has all that you require. You can also check the
GitHub actions [pipeline.yaml](.github/workflows/pipeline.yml) file to better understand the CI/CD process.
It's written generically to work with SFTP / FTP, no matter what the used cloud provider is.

If you prefer a different hosting process, please share it with everybody else. I will add a reference to
your repository here.

## Development and Testing

### Local Development

To run the application locally, run the MariaDB instance first:

```bash
docker compose up
```

And then the server with one of:

```bash
php -S localhost:8000 -t ./public
```

```bash
php -S 0.0.0.0:8000 -t ./public
```

:warning: Use `0.0.0.0:8000` if you want to access it in your local network with other devices

Now that everything is up and running, access the `/install` endpoint on
[http://localhost:8000/install](http://localhost:8000/install) to create the `owner` user invitation
and to complete it.

### Running Tests

Before running the tests for the first time or after modifying the code, you will need to rebuild
the docker image:

```bash
docker build -t community-bulletin-board .
```

Tests are run with PHPUnit:

```bash
vendor/bin/phpunit ./tests
```

Most tests are integration tests. The API tests run against a dockerized version of the web app.

### Static Analysis

Static analysis is performed with `PHPStan` and `Psalm`:

```bash
vendor/bin/phpstan analyse ./src --level 10 --memory-limit 256M
```

```bash
vendor/bin/psalm --no-cache
``` 

### Translations

The application supports multiple languages through i18n. After modifying the translation files,
you must recompile them by running:

```bash
./i18n.sh
```

## Technical Details

This project requires PHP `8.4` to run.

### Dependencies

| Dependency                     | Explanation                                                                   |
|:-------------------------------|:------------------------------------------------------------------------------|
| ext-pdo                        | Extension for accessing databases                                             |
| ext-openssl                    | Extension for encryption / decryption operations                              |
| ext-gettext                    | Extension for internationalisation (translations)                             |
| php-di/php-di                  | Dependency injection required for twig usage (me thinks)                      |
| slim/slim                      | Micro-framework for writing lightweight Web Apps and APIs                     |
| slim/psr7                      | PSR-7 implementation for Slim 4                                               |
| slim/http                      | PSR-7 object decorators                                                       |
| slim/twig-view                 | Allows the rendering of `.twig` files                                         |
| phpmyadmin/twig-i18n-extension | Allows the usage of `gettext` in `.twig` files for page rendered translations |
| phpmailer/phpmailer            | Used just for the invitation email                                            |
| jms/serializer                 | A lightweight JSON decoder / encoder used for the API endpoints               |
| vlucas/phpdotenv               | Reading .env file and loading the variables into the php `$_ENV` variable     |

### Notes

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
