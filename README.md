# GDPR Dump

A drop-in replacement for mysqldump that optionally sanitizes DB fields for better GDPR conformity.

It is based on the [ifsnop/mysqldump\-php](https://github.com/ifsnop/mysqldump-php) library, 
and can in principle dump any database that PDO supports. 

## How to use

*Scroll down a bit for usage with Drush.*

There are presently two ways of manipulating data, 
the first is by manipulating the actual SQL queries that are run on the server (given by the gdpr-expressions path), 
and the second is by replacing column output before the dump is generated (given by the gdpr-replacements option).


```
$ ../vendor/bin/mysqldump drupal --host=mariadb --user=drupal --password=xxxxxxxx users_field_data --gdpr-expressions='{"users_field_data":{"name":"uid","mail":"uid","pass":"\"\""}}' --debug-sql
...
--
-- Dumping data for table `users_field_data`
--

/* SELECT `uid`,`langcode`,`preferred_langcode`,`preferred_admin_langcode`,uid as name,"" as pass,uid as mail,`timezone`,`status`,`created`,`changed`,`access`,`login`,uid as init,`default_langcode` FROM `users_field_data` */

INSERT INTO `users_field_data` VALUES (0,'en','en',NULL,'0','','0','',0,1523397207,1523397207,0,0,'0',1);
INSERT INTO `users_field_data` VALUES (1,'en','en',NULL,'1','','1','UTC',1,1523397207,1523397207,0,0,'1',1);
```

The fields to obfuscate are passed via a `--gdpr-expressions` parameter.
Note that we use `uid` expression to satisfy unique keys.

The same without obfuscation:

```
$ ../vendor/bin/mysqldump drupal --host=mariadb --user=drupal --password=xxxxxxxx users_field_data --debug-sql
...
--
-- Dumping data for table `users_field_data`
--

/* SELECT `uid`,`langcode`,`preferred_langcode`,`preferred_admin_langcode`,`name`,`pass`,`mail`,`timezone`,`status`,`created`,`changed`,`access`,`login`,`init`,`default_langcode` FROM `users_field_data` */

INSERT INTO `users_field_data` VALUES (0,'en','en',NULL,'',NULL,NULL,'',0,1523397207,1523397207,0,0,NULL,1);
INSERT INTO `users_field_data` VALUES (1,'en','en',NULL,'admin','$S$Eb6kZl.9OFjoa69Z05pzUhaZJ6vpKaGZVpnjAxxLJ7ip0zOwanEV','admin@example.com','UTC',1,1523397207,1523397207,0,0,'admin@example.com',1);
```

### Using gdpr-replacements

This uses [Faker](https://packagist.org/packages/fzaninotto/faker) for most of the column sanitization.

Presently, the tool searches for the "gdpr-replacements" option, either passed as a command line argument, or as part of a [MySql options file](https://dev.mysql.com/doc/refman/8.0/en/option-files.html).

The "gdpr-replacements" option expects a JSON string with the following format

```
{"tableName" : {"columnName1": {"formatter": "formatterType", ...}, {"columnName2": {"formatter": "formatterType"}, ...}, ...}
```
Where *formatterType* is one of the following
* **name** - generates a name
* **phoneNumber** - generates a phone number
* **username** - generates a random user name
* **password** - generates a random password
* **email** - generates a random email address
* **safeEmail** - same but with @example.org
* **date** - generates a date
* **longText** - generates a sentence
* **number** - generates a number
* **randomText** - generates a sentence
* **text** - generates a paragraph
* **uri** - generates a URI
* **clear** - generates an empty string

This will replace the given column's value with Faker output.

You can also save replacements mapping to JSON file and use it with `--gdpr-replacements-file` option.

## Use with drush

As this mimicks mysqldump, it can be use with drush, backup_migrate and any tool that uses mysqldump.
Example for your local Docker instance:

```
$ export PATH=/var/www/html/vendor/bin:$PATH
$ which mysqldump
/var/www/html/vendor/bin/mysqldump
$ drush sql-dump --tables-list=users_field_data --extra-dump=$'--gdpr-expressions=\'{"users_field_data":{"name":"uid","mail":"uid","init":"uid","pass":"\\"\\""}}\' --debug-sql'
```

On Staging, Accept or Production environments you probably want to do the following (replace `environment.nl`):
```
$ export PATH=/data/www/environment.nl/current/vendor/bin:$PATH
$ which mysqldump
/data/www/environment.nl/current/vendor/bin/mysqldump
$ pwd
/data/www/environment.nl/current
$ drush sql-dump --extra-dump='--gdpr-replacements-file=../gdpr-replacements.json' --result-file=~/gdpr-dump.sql
```
This runs in the docroot, but most repositories have the `gdpr-replacements.json` in the root folder, hence the `../`.

If your project does not have a `gdpr-replacements.json` please use the template from this project and add one.

To save diskspace, run it like so:
```
$ drush sql-dump --extra-dump='--gdpr-replacements-file=../gdpr-replacements.json' --result-file=~/gdpr-dump.sql --structure-tables-list="batch,cache_*,cachetags,flood,history,sessions,queue,watchdog,webform_submission,webform_submission_data"
```
#### Gitlab-CI `copy-x-database-to-x` jobs
If your repo has a `gdpr-replacements.json`, you can manually trigger a job to copy the database do Accept/Staging environment, after a deployment.
These jobs will use the `gdpr-replacements.json` if it's available.

#### Exclude table content
`drush sql-dump` takes an argument called `--structure-tables-list`. From the docs:
"A comma-separated list of tables to include for structure, but not data."

The Gitlab-CI `copy-x-database-to-x` jobs have been extended with a new parameter: `STRUCTURE_TABLES_LIST`
You can configure the parameter in your .gitlab-ci.yml like so:
`STRUCTURE_TABLES_LIST: "batch,cache_*,cachetags,flood,history,sessions,queue,watchdog,webform_submission,webform_submission_data"`

### MySqlOptions file

You are able to have your gdpr-expressions/replacement options set in a mysql options file file.
It is to appear under the `[mysqldump]` section.

So, for example, you might have `/etc/my.cnf` with the following content

```
[mysqldump]
gdpr-replacements='{"fakertest":{"name": {"formatter":"name"}, "telephone": {"formatter":"phoneNumber"}}}'

```

## Local development & testing

You can exercise this tool against any running Drupal project's MySQL database without
touching the Drupal codebase at all — you only need network access to its DB port.

### Prerequisites

* PHP CLI with the `pdo_mysql` extension (check with `php -m | grep pdo_mysql`)
* Composer
* A running Drupal site's MySQL/MariaDB container with its port exposed to the host.
  Most `docker-compose.yml` setups already do this, e.g.:
  ```yaml
  mysql:
    ports:
      - "3306:3306"
  ```
  Grab the credentials from that `docker-compose.yml` (or the container's env vars) —
  typically `MYSQL_USER` / `MYSQL_PASSWORD` / `MYSQL_DATABASE`.

### Setup

```
$ composer install
```

That's it — no patched vendor packages or Composer plugins are required. (An earlier
version of this tool patched a private property in `ifsnop/mysqldump-php` open via
`cweagans/composer-patches`; it's since been replaced with a small `ReflectionProperty`
read in `MysqldumpGdpr::tableColumnTypes()`, so nothing needs to be patched in the
vendored library anymore.)

### Running against the other project's database

Point `mysqldump` at the host/port where the other project's DB is exposed, e.g. for a
docker-compose Drupal site with MySQL published on `127.0.0.1:3306`:

```
$ php mysqldump --host=127.0.0.1 --port=3306 --user=drupal --password=drupal drupal \
    users_field_data \
    --gdpr-expressions='{"users_field_data":{"name":"uid","mail":"uid","pass":"\"\""}}' \
    --debug-sql
```

or with the Faker-based replacements:

```
$ php mysqldump --host=127.0.0.1 --port=3306 --user=drupal --password=drupal drupal \
    users_field_data \
    --gdpr-replacements='{"users_field_data":{"name":{"formatter":"clear"},"mail":{"formatter":"safeEmail"}}}'
```

Since this only reads from the source DB and writes SQL to stdout, it's safe to run
against a live-but-local development database — it never mutates the source. Redirect
to a file with `> dump.sql`, or pipe straight into a local MySQL client to load it into
a scratch database:

```
$ php mysqldump --host=127.0.0.1 --port=3306 --user=drupal --password=drupal drupal \
    --gdpr-replacements-file=./gdpr-replacements-template.json \
    | mysql -h127.0.0.1 -uroot -p my_scratch_db
```

Compare the sanitized output against a plain dump (drop the `--gdpr-*` flag) to confirm
the fields you expect are actually being transformed.

### Running the test suite

```
$ vendor/bin/phpunit
```

## Status and further development

Currently this is a proof of concept to spark a community process.
Especially the `--gdpr-expressions` option is neither handy to write for humans, nor does it scale well.
Here we might need better options.

## Contributors notes

* Note that the project follows [PSR-2](https://www.php-fig.org/psr/psr-2/) for formatting. 
