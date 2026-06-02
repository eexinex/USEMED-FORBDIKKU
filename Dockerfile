FROM php:8.2-apache

# Install dependencies for PHP (PostgreSQL) and Python
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    python3 \
    python3-pip \
    python3-venv \
    supervisor \
    && docker-php-ext-install pdo_pgsql opcache mbstring curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable PHP OPcache for dramatically faster page loads
RUN echo "opcache.enable=1\n\
opcache.enable_cli=0\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=10000\n\
opcache.revalidate_freq=0\n\
opcache.validate_timestamps=0\n\
opcache.save_comments=1\n\
opcache.fast_shutdown=1" > /usr/local/etc/php/conf.d/opcache.ini

# Set up Python virtual environment
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Configure Apache to run on port 7860 (Hugging Face default) and set DocumentRoot to public
RUN sed -i 's/80/7860/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Enable Apache mod_rewrite and KeepAlive
RUN a2enmod rewrite \
    && echo "ServerName localhost\nDirectoryIndex index.html index.php\nErrorDocument 500 /500.html\nErrorDocument 502 /500.html\nErrorDocument 503 /500.html\nErrorDocument 504 /500.html\nTimeout 60\nKeepAlive Off\nMaxKeepAliveRequests 50\nKeepAliveTimeout 1" >> /etc/apache2/apache2.conf \
    && echo "RedirectMatch 302 ^/healthz\\.php$ /healthz.html" > /etc/apache2/conf-available/usemed-healthz.conf \
    && a2enconf usemed-healthz

# Copy all project files to Apache root
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Install Python requirements
WORKDIR /var/www/html/ml_service
RUN pip install --no-cache-dir -r requirements.txt

# Hugging Face rejects committed binary model files. Train artifacts during image build.
RUN python models/train.py --seed-sql /var/www/html/backend/database/agent_dataset_seed.sql

# Setup Supervisor to run Apache, FastAPI, and DB keep-alive
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 7860
EXPOSE 7860

# Start Supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
