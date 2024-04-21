FROM nginx:latest as development
ADD nginx.conf /etc/nginx/conf.d

FROM nginx:latest as production
ADD nginx.conf /etc/nginx/conf.d
ADD fastcgi-php.conf /etc/nginx