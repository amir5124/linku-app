FROM php:8.2-apache

# Salin semua file ke folder root Apache
COPY . /var/www/html/

# Pastikan izin file benar agar bisa diakses
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Ekspos port 80
EXPOSE 80