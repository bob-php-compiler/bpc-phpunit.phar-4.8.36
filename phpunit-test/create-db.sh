#!/bin/bash

echo "DROP DATABASE IF EXISTS our_phpunit_test;CREATE DATABASE our_phpunit_test DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;" | mysql -h127.0.0.1 -P3307 -uroot -p123456

cat>/tmp/our_phpunit_test.sql<<EOF
CREATE TABLE post (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  content varchar(1000) CHARACTER SET utf8 DEFAULT NULL,
  create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOF

mysql -h127.0.0.1 -P3307 -uroot -p123456 our_phpunit_test < /tmp/our_phpunit_test.sql

rm /tmp/our_phpunit_test.sql

echo "done"
