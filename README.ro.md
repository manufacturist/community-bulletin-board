[![Licență: CC BY-NC 4.0](https://licensebuttons.net/l/by-nc/4.0/80x15.png)](https://creativecommons.org/licenses/by-nc/4.0/)

[English](./README.md) | [Română](./README.ro.md)

# Avizierul comunitar ("Community Bulletin Board" sau "CBB")

O aplicație web de avizier cu acces pe bază de invitație, sigură și ieftină, cu stocare criptată.

CBB permite comunității dvs. să posteze pe un avizier digital. Administratorul invită membrii prin email, și
fiecare membru poate avea până la 2 postări active pentru a păstra totul organizat.

Administratorul poate:

* Promova utilizatori la rangul de Administrator
* Ajusta numărul maxim de postări pe utilizator
* Șterge utilizatori sau anunțurile acestora

## Găzduire

Ușor de găzduit pe [Hetzner Webhosting](https://www.hetzner.com/webhosting/). Avantajele sunt:

1. Include o înregistrare de domeniu, **fără** taxă anuală de reînnoire
2. Permite rularea PHP
3. Oferă o instanță de bază de date MariaDB
4. Oferă serviciu de email pentru domeniu
5. Trafic nelimitat <sup>A</sup>

Costurile includ o taxă de configurare unică de ~10 EUR și o taxă lunară de ~2 EUR pentru găzduire.

<sup>A</sup> Soluția va rula într-un mediu partajat, ceea ce înseamnă că va rula alături de alte site-uri web pe
același calculator. Dacă viteza de încărcare devine o problemă, puteți să optați pentru un planul mai bun de găzduire.

## Găzduire proprie

Fișierul [docker-compose.yaml](./docker-compose-all.yaml) are toate informațiile necesare.

## Alternative

Dacă preferați un proces alternativ de găzduire, puteți să îl împărtășiți cu noi. Voi adăuga o referință la
repository-ul tău aici.

### Licență

<p>
<a property="dct:title" rel="cc:attributionURL" href="https://github.com/manufacturist/community-bulletin-board">community-bulletin-board</a> by 
<a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://github.com/manufacturist/"> Ioan-Gabriel Lazarovici-Georgiu</a> is licensed under 
<a href="https://creativecommons.org/licenses/by-nc/4.0" target="_blank" rel="license noopener noreferrer" style="display:inline-block;"> CC BY-NC 4.0</a>
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg" alt="">
<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/nc.svg" alt="">
</p>