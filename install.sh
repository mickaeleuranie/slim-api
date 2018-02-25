#! /bin/bash

echo "You are going to configure your Slim API."

PROMPT="true"

for i in "$@"
do
case $i in
    -d=*|--dbname=*)
    DB_NAME="${i#*=}"
    DB_NAME_TEST="${i#*=}_phpunit"
    shift # past argument=value
    ;;
    -u=*|--dbuser=*)
    DB_USER="${i#*=}"
    shift # past argument=value
    ;;
    -p=*|--dbpassword=*)
    DB_PASSWORD="${i#*=}"
    shift # past argument=value
    ;;
    -y|--yes)
    PROMPT="false"
    shift # past argument with no value
    ;;
    *)
          # unknown option
    ;;
esac
done

if [ "${PROMPT}" == "true" ]; then
    read -p "Continue (y/n)? " -n 1 -r
    echo    # move to a new line
else
    REPLY="y"
fi

if [[ $REPLY =~ ^[Yy]$ ]]
then

    composer install

    while getopts ":d:u:p:" opt; do
        case $opt in
            d) DB_NAME="$OPTARG"
            ;;
            u) DB_USER="$OPTARG"
            ;;
            p) DB_PASSWORD="$OPTARG"
            ;;
            \?) echo "Invalid option -$OPTARG" >&2
            ;;
        esac
    done

    # read database name
    if [ -z ${DB_NAME+x} ]; then
        while true
        read -p "Database name: " DB_NAME
        do
            if [[ ! -z "$DB_NAME" ]]; then
                DB_NAME_TEST="${DB_NAME}_phpunit"
                break
            else
                echo "Database name can't be empty"
            fi
        done
    fi

    # read database username
    if [ -z ${DB_USER+x} ]; then
        while true
        read -p "Database username: " DB_USER
        do
            if [[ ! -z "$DB_USER" ]]; then
                break
            else
                echo "Database username can't be empty"
            fi
        done
    fi

    # read database password
    if [ -z ${DB_PASSWORD+x} ]; then
        read -p "Database password: " DB_PASSWORD
    fi

    echo "###################################"
    echo "Configuration:"
    echo "Database name: $DB_NAME"
    echo "Database name (phpunit): $DB_NAME_TEST"
    echo "Database username: $DB_USER"
    echo "Database password: $DB_PASSWORD"
    echo -e "\033[31mIf database with same name than the one you are going to define exists, all of its data will be lost \033[0m"
    if [ "${PROMPT}" == "true" ]; then
        while true
            read -p "Continue (y/n)? " -n 1 -r
            echo    # move to a new line
        do
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                echo "###################################"
                break

            elif [[ $REPLY =~ ^[Nn]$ ]]; then
                echo
                echo "See you soon!"
                exit 1

            else
                echo "Database name can't be empty"
            fi
        done
    fi

    echo "Creating local environment file from given parameters"
    cp .env.example .env
    sed -i.bak s/%DB_NAME%/"$DB_NAME"/ .env
    rm .env.bak
    sed -i.bak s/%DB_NAME_TEST%/"$DB_NAME_TEST"/ .env
    rm .env.bak
    sed -i.bak s/%DB_USER%/"$DB_USER"/ .env
    rm .env.bak
    sed -i.bak s/%DB_PASSWORD%/"$DB_PASSWORD"/ .env
    rm .env.bak

    echo "done"
    echo "----"
    echo "Creating database"

    # replace values inside schema.sql file
    cp ./db/schema.sql ./db/schema.sql.tmp
    sed -i.bak s/%DB_NAME%/"$DB_NAME"/ ./db/schema.sql.tmp
    sed -i.bak s/%DB_NAME_TEST%/"$DB_NAME_TEST"/ ./db/schema.sql.tmp
    rm ./db/schema.sql.tmp.bak

    # execute sql queries
    mysql -h 127.0.0.1 -u"$DB_USER" -p"$DB_PASSWORD" < ./db/schema.sql.tmp

    if [ $? -ne 0 ]; then
        echo -e "\033[31mError, please try again \033[0m"
        exit 1
    fi

    echo "done"
    echo "----"

    echo "Creating test database for PHPUnit"

    # replace values inside test_schema.sql fixtures file
    cp ./tests/fixtures/test_schema.example.sql ./tests/fixtures/test_schema.sql
    sed -i.bak s/%DB_NAME_TEST%/"$DB_NAME_TEST"/ ./tests/fixtures/test_schema.sql
    rm ./tests/fixtures/test_schema.sql.bak

    # execute sql queries
    mysql -h 127.0.0.1 -u"$DB_USER" -p"$DB_PASSWORD" < ./tests/fixtures/test_schema.sql

    if [ $? -ne 0 ]; then
        echo -e "\033[31mError, please try again \033[0m"
        exit 1
    fi

    echo "done"
    echo "----"

    echo "Copy configuration files"
    cp phpunit.xml.dist phpunit.xml

    if [ $? -ne 0 ]; then
        echo -e "\033[31mError, please try again \033[0m"
    else
        echo -e "\033[32mDone! \033[0m"
    fi

else
    echo -e "\033[32mBye! \033[0m"
fi