FROM nginx

RUN apk add bash

# Create and enable nginx conf
RUN rm /etc/nginx/nginx.conf
COPY ./conf.d/nginx.conf /etc/nginx/nginx.conf
RUN mkdir -p /etc/nginx/sites-available/ && \
     mkdir -p /etc/nginx/sites-enabled/
COPY ./conf.d/app.conf /etc/nginx/sites-available/app.conf
RUN ln -s /etc/nginx/sites-available/app.conf /etc/nginx/sites-enabled/app.conf
