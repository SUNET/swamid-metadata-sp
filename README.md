# swamid-metadata-sp
docker run --net docker -v /path/to/sql:/docker-entrypoint-initdb.d -v /var/lib/mysql:/var/lib/mysql --name mariadb -e MARIADB_ROOT_PASSWORD=my-secret-pw -e MARIADB_DATABASE=metadata -e MARIADB_USER=metdata_admin -e MARIADB_PASSWORD=adminpwd -d mariadb:latest

docker run --net docker --hostname metadata.org.edu -v /path/to/web:/var/www/ -v /etc/ssl:/etc/ssl -v /etc/dehydrated/certs/metadata.org.edu:/etc/dehydrated -v /etc/shibboleth/certs:/etc/shibboleth/certs -p 443:443 -d --name swamid-metadata-sp swamid-metadata-sp:latest
