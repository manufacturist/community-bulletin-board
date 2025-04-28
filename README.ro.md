[![Licență: CC BY-NC 4.0](https://licensebuttons.net/l/by-nc/4.0/80x15.png)](https://creativecommons.org/licenses/by-nc/4.0/)

[English](./README.md) | [Română](./README.ro.md)

# Avizierul comunitar ("Community Bulletin Board" sau "CBB")

O aplicație web de avizier cu acces pe bază de invitație, sigură și ieftină, cu stocare criptată.

CBB permite comunității dvs. să posteze pe un avizier digital. Administratorul invită membrii prin email, și
fiecare membru poate avea până la 2 postări active (implicit) pentru a păstra totul organizat.

Administratorul poate:

* Promova utilizatori la rangul de Administrator
* Ajusta numărul maxim de postări pe utilizator (0-5)
* Șterge utilizatori sau anunțurile acestora

## Cuprins

- [Găzduire în Cloud (recomandat)](#găzduire-în-cloud-recomandat)
- [Configurare Hetzner](#configurare-hetzner)
- [Găzduire alternativă](#găzduire-alternativă)
- [Dezvoltare și Testare](#dezvoltare-și-testare)
    - [Dezvoltare Locală](#dezvoltare-locală)
    - [Rularea Testelor](#rularea-testelor)
    - [Analiză Statică](#analiză-statică)
    - [Traduceri](#traduceri)
- [Note Tehnice](#note-tehnice)
- [Licență](#licență)

## Găzduire în Cloud (recomandat)

1. Faceți un fork al proiectului
2. Proiectul folosește GitHub Actions care sunt deja configurate pentru a funcționa cu FTP pentru deploy
    - Pipeline-ul va valida și testa codul, după care îl va pune pe server în mod automat
    - Pentru ca procesul să funcționeze, va trebui să adăugați următoarele secrete / variabile în repository:
        - Deploy:
            - `FTP_SERVER`: Adresa serverului de găzduire
            - `FTP_USERNAME`: Numele de utilizator FTP
            - `FTP_PASSWORD`: Parola FTP
            - `FTP_PORT`: Portul FTP (de obicei 21)
        - Baza de date:
            - `DB_HOST`: Gazda bazei de date
            - `DB_PORT`: Portul bazei de date
            - `DB_NAME`: Numele bazei de date
            - `DB_USERNAME`: Numele de utilizator pentru baza de date
            - `DB_PASSWORD`: Parola pentru baza de date
        - Criptare (șiruri hexazecimale de 32 de caractere):
            - `CRYPTO_ENCRYPTION_KEY`: Cheia de criptare
            - `CRYPTO_HMAC_KEY`: Cheia HMAC
            - `CRYPTO_PEPPER`: Valoarea pepper
        - Email:
            - `EMAIL_SMTP_HOST`: Numele de gazdă al serverului SMTP
            - `EMAIL_SMTP_PORT`: Portul SMTP
            - `EMAIL_SMTP_USERNAME`: Numele de utilizator SMTP
            - `EMAIL_SMTP_PASSWORD`: Parola utilizatorului SMTP
        - Setări aplicație:
            - `APP_URL`: URL-ul de bază al aplicației (utilizat pentru link-urile de invitație)
            - `APP_OWNER_EMAIL`: Adresa de email a proprietarului site-ului. Necesar pentru prima invitație de
              utilizator
            - `APP_PUBLIC_ENDPOINT`: Una dintre `none`, `public` sau orice alt șir de caractere. Dacă este `public`, 
              anunțurile din comunitate vor fi expuse fară detaliile personale (număr de telefon, nume și link) pe un
              link care poate fi accesat de către oricine, spre exemplu `communitatea-ta.com/anunțuri`. Dacă folosești
              un alt șir de caractere, atunci acesta va fi folosit ca și un slug pentru a crea un link: 
              `linkul-meu-secret` => `comunitatea-ta.com/linkul-meu-secret`
            - `APP_LOCALE` (variabilă): Localizare pentru aplicație (`en_US`, `en_UK` sau `ro_RO`)
            - `APP_MAX_ACTIVE_POSTS_DEFAULT` (variabilă): Numărul maxim implicit de postări active per utilizator
        - GitHub Actions:
            - `PIPELINE_ENFORCE_C_LOCALE` (variabilă): `true` pentru a impune localizarea dorită prin
              intermediul localizării C, dacă serverul nu are suport pentru aceasta

Deploy ușor pe [Hetzner Webhosting](https://www.hetzner.com/webhosting/). Avantajele sunt:

1. Include o înregistrare de domeniu, **fără** taxă anuală de reînnoire
2. Permite rularea PHP
3. Oferă o instanță de bază de date MariaDB
4. Oferă serviciu de email pentru domeniu
5. Trafic nelimitat <sup>A</sup>

Costurile includ o taxă de configurare unică de ~10 EUR și o taxă lunară de ~2 EUR pentru găzduire.

<sup>A</sup> Soluția va rula într-un mediu partajat, ceea ce înseamnă că va rula alături de alte site-uri web pe
același calculator. Dacă viteza de încărcare devine o problemă, puteți să optați pentru un plan mai bun de găzduire.

## Configurare Hetzner

TODO: Hetzner Webhosting

## Găzduire alternativă

Fișierul [docker-compose.yaml](./docker-compose-all.yaml) are toate informațiile necesare.

Dacă preferați un proces alternativ de găzduire, dați un semn. Voi adăuga o referință la procesul vostru aici.

## Dezvoltare și Testare

### Dezvoltare Locală

Pentru a rula aplicația local:

```bash
docker compose up
```

```bash
php -S 0.0.0.0:8000 -t ./public
```

### Rularea Testelor

Testele sunt rulate cu PHPUnit:

```bash
vendor/bin/phpunit ./tests
```

Majoritatea testelor sunt teste de integrare. Testele API rulează împotriva unei versiuni dockerizate a aplicației web.

### Analiză Statică

Analiza statică este efectuată cu PHPStan și Psalm:

```bash
vendor/bin/phpstan analyse ./src --level 10
```

```bash
vendor/bin/psalm --no-cache
``` 

### Traduceri

Aplicația suportă mai multe limbi prin i18n. După modificarea fișierelor de traducere, trebuie să le recompilați rulând:

```bash
./i18n.sh
```

## Note Tehnice

* Curățarea datelor învechite se face cu o șansă de 2% per request
* S-au evitat cron job-urile pentru ca veneau cu un cost extra

## Licență

<p>
<a property="dct:title" rel="cc:attributionURL" href="https://github.com/manufacturist/community-bulletin-board">community-bulletin-board</a> by 
<a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://github.com/manufacturist/"> Ioan-Gabriel Lazarovici-Georgiu</a> is licensed under 
<a href="https://creativecommons.org/licenses/by-nc/4.0" target="_blank" rel="license noopener noreferrer" style="display:inline-block;"> CC BY-NC 4.0</a>
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/nc.svg" alt="">
</p>