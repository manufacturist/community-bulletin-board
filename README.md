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

## Cloud Hosting (recommended)

1. Fork the project to your own GitHub account
2. The project uses GitHub Actions which are already set up to work with FTP deployment
    - The pipeline will automatically validate, test and deploy the code
    - For it to work, you'll need to add the following secrets to your repository:
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
        - `EMAIL_ADAPTER`: Email adapter type ('smtp' or 'logging')
        - `EMAIL_SMTP_HOST`: SMTP server hostname (required for 'smtp' adapter)
        - `EMAIL_SMTP_USERNAME`: SMTP username (required for 'smtp' adapter)
        - `EMAIL_SMTP_PASSWORD`: SMTP password (required for 'smtp' adapter)
        - `EMAIL_SMTP_PORT`: SMTP port, defaults to 587 (optional for 'smtp' adapter)
        - `EMAIL_FROM_NAME`: Sender name, defaults to 'Community Bulletin Board' (optional)
        - `APP_URL`: Base URL of your application (used for invitation links)
      - Optional:
        - `GITHUB_TOKEN`: A GitHub access token for getting the latest changes from upstream (see [fork-rebase.yml](.github/optional/fork-rebase.yml))

Easily deployable on [Hetzner Webhosting](https://www.hetzner.com/webhosting/). The advantages are:

1. Includes one domain registration with **no** annual renewal fee
2. Allows serving PHP
3. Provides a MariaDB instance
4. Provides email service for the domain
5. Unlimited traffic <sup>A</sup>

The costs include a one-time setup fee of ~10 EUR and a recurring monthly fee of ~2 EUR for hosting.

<sup>A</sup> The solution will run on a shared environment, meaning it will run alongside other websites on
the same machine. If loading speed becomes an issue, you could upgrade to a better hosting plan.

### Email Configuration

The application supports two email adapter types:

1. **SMTP Adapter** (`EMAIL_ADAPTER=smtp`): Sends actual emails via SMTP. Requires proper SMTP configuration.
2. **Logging Adapter** (`EMAIL_ADAPTER=logging`): Logs email content to error_log instead of sending real emails. Useful for development and testing.

For production environments, use the SMTP adapter to ensure users receive invitation emails. For development or testing, the logging adapter can be used to avoid sending real emails.

## Alternative Hosting

The [docker-compose.yaml](./docker-compose-all.yaml) file has all that you require.

If you prefer a different hosting process, please share it with us. I will add a reference to your repository here.

### License

<p>
<a property="dct:title" rel="cc:attributionURL" href="https://github.com/manufacturist/community-bulletin-board">community-bulletin-board</a> by 
<a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://github.com/manufacturist/"> Ioan-Gabriel Lazarovici-Georgiu</a> is licensed under 
<a href="https://creativecommons.org/licenses/by-nc/4.0" target="_blank" rel="license noopener noreferrer" style="display:inline-block;"> CC BY-NC 4.0</a>
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/nc.svg" alt="">
</p>
