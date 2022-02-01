FROM debian:buster
MAINTAINER bjorn@sunet.se
RUN echo 'debconf debconf/frontend select Noninteractive' | debconf-set-selections
RUN apt-get -q update && \
	apt-get -y upgrade && \
	apt-get -y install apache2 libapache2-mod-shib libapache2-mod-php php-mysql php-xml php-curl gettext-base git && \
	a2enmod ssl headers
ENV SP_HOSTNAME sp.example.com
ENV SP_CONTACT noc@sunet.se
ENV SP_ABOUT /
ENV DEFAULT_LOGIN md.nordu.net
ADD md-signer2.crt /etc/shibboleth/md-signer2.crt
ADD shibboleth2.xml /tmp/shibboleth2.xml
ADD attribute-map.xml /etc/shibboleth/attribute-map.xml
ADD default-ssl.conf /etc/apache2/sites-available/default-ssl.conf 
ADD start.sh /start.sh
ADD shibd.logger /etc/shibboleth/shibd.logger
RUN a2ensite default-ssl && \
	mkdir -p /etc/shibboleth/certs /etc/shibboleth/metadata /var/cache/shibboleth/metadata && \
	chown _shibd /etc/shibboleth/metadata /var/cache/shibboleth/metadata && \
	chmod a+rx /start.sh
EXPOSE 443
ENTRYPOINT ["/start.sh"]
