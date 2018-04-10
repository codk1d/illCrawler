[illumCrawler](https://github.com/rvasources/illumCrawler) - is crawler for web pages from darknet (tor network). You can use it for creating your own database of tor sites, own tor search engine and etc.

# Getting Started
1. The minimum required PHP version 5, MySQL.
2. Tor proxy (Socks5). It can be local tor proxy (example: Privoxy + tor).
3. Create database in MySQL.

        $ git clone https://github.com/rvasources/illumCrawler
4. Import .sql file to MySQL database.
5. Open `illum.php` file and change database data.

# Functions of illumCrawler
1. Crawling all pages of all domains in database (it saves title, metategs, text and url of page).
2. Getting and checking all new domains which got from scanned pages.
3. Updating content of outdated pages (1 time in 2 days).
4. Removing died sites from database.

# Information
Software has 4 functions which you can use with special prefixes. For example: `./illumcrawler.php --crawler`. This software working as a daemon (you can add it to crontab).

