#!/bin/bash

################################################################
##
##   MySQL Database To Amazon S3
##   Written By: YONG MOOK KIM
################################################################

NOW=$(date +"%Y-%m-%d")

BACKUP_DIR="/tmp"
MYSQL_HOST="localhost"
MYSQL_PORT="3306"
MYSQL_USER="root"
MYSQL_PASSWORD="f@tG1rl!" 
DATABASE_NAME="paintchip"

AMAZON_S3_BUCKET="s3://paintchip/backup/mysql/"
AMAZON_S3_BIN="aws"

FOLDERS_TO_BACKUP=("/home/mkyong/bk1" "/home/mkyong/bk2")

#################################################################

mkdir -p ${BACKUP_DIR}

backup_mysql(){
         mysqldump -h ${MYSQL_HOST} \
           -P ${MYSQL_PORT} \
           -u ${MYSQL_USER} \
           -p${MYSQL_PASSWORD} ${DATABASE_NAME} | gzip > ${BACKUP_DIR}/${DATABASE_NAME}-${NOW}.sql.gz
}

# backup any folders?
backup_files(){
echo 'hello'
       # tar -cvzf ${BACKUP_DIR}/backup-files-${NOW}.tar.gz ${FOLDERS_TO_BACKUP[@]}

}

upload_s3(){
        ${AMAZON_S3_BIN} s3 cp ${BACKUP_DIR}/${DATABASE_NAME}-${NOW}.sql.gz ${AMAZON_S3_BUCKET}
}

backup_mysql
upload_s3