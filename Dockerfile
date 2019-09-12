FROM php:7.3.9-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php","READER.php" ]