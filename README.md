# storm-db

Drop `storm-db.yaml` in your home directory, or define an environment variable `STORM_DB_CONFIG` pointing to that file.


Nginx should have the following added to its php-fpm handler:

    fastcgi_param STORM_DB_CONFIG /var/www/whatever/.storm-db.yaml

Make sure its readable by the php-fpm user of that site.
