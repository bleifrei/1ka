FROM php:7.4-apache-buster

# Apache packages
RUN apt-get update -y \
	&& apt-get upgrade -y \
    && apt-get install -y \
        libgd-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxml2-dev \
        zlib1g-dev \
		#texlive-full \
		#latex2rtf \
        libzip-dev \
        zip \
	&& apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP configure freetype
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/

# PHP modules
RUN /usr/local/bin/docker-php-ext-install \
        mysqli \
        pdo \
        pdo_mysql \
        bcmath \
        soap \
        gd \
        zip

# Enable rewrite module
RUN a2enmod rewrite

