FROM php:8.2-apache

# Install dependencies for PHP (PostgreSQL) and Python
RUN apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    python3-pip \
    python3-venv \
    supervisor \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set up Python virtual environment
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Configure Apache to run on port 7860 (Hugging Face default) and set DocumentRoot to public
RUN sed -i 's/80/7860/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy all project files to Apache root
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Install Python requirements
WORKDIR /var/www/html/ml_service
RUN pip install --no-cache-dir -r requirements.txt

# Setup Supervisor to run both Apache and FastAPI
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 7860
EXPOSE 7860

# Start Supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
